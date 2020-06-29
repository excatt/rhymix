<?php

namespace Rhymix\Framework;

/**
 * The DB class.
 */
class DB
{
	/**
	 * Singleton instances.
	 */
	protected static $_instances = array();
	
	/**
	 * Connection handle.
	 */
	protected $_handle;
	
	/**
	 * Prefix and other connection settings.
	 */
	protected $_prefix = '';
	protected $_charset = 'utf8mb4';
	protected $_engine = 'innodb';
	
	/**
	 * Information about the last executed statement.
	 */
	protected $_last_stmt;
	
	/**
	 * Elapsed time.
	 */
	protected $_query_time = 0;
	protected $_total_time = 0;
	
	/**
	 * Error codes.
	 */
	protected $_errno = 0;
	protected $_errstr = '';
	
	/**
	 * Transaction level.
	 */
	protected $_transaction_level = 0;
	
	/**
	 * Properties for backward compatibility.
	 */
	public $db_type = 'mysql';
	public $db_version = '';
	public $use_prepared_statements = true;
	
	/**
	 * Get a singleton instance of the DB class.
	 * 
	 * @param string $type
	 * @return self
	 */
	public static function getInstance(string $type = 'master'): self
	{
		// If an instance already exists, return it.
		if (isset(self::$_instances[$type]))
		{
			return self::$_instances[$type];
		}
		
		// Check if configuration exists for the selected DB type.
		$config = Config::get('db.' . $type) ?: array();
		if (!count($config))
		{
			throw new Exceptions\DBError('DB type \'' . $type . '\' is not configured.');
		}
		
		// Create an instance and return it.
		return self::$_instances[$type] = new self($config);
	}
   
	/**
	 * Constructor.
	 * 
	 * @param string $type
	 */
	public function __construct(array $config)
	{
		// Save important config values to the instance.
		$this->_prefix = $config['prefix'] ?: $this->_prefix;
		$this->_charset = $config['charset'] ?: $this->_charset;
		$this->_engine = $config['engine'] ?: $this->_engine;
		
		// Connect to the DB.
		$dsn = 'mysql:host=' . $config['host'];
		$dsn .= (isset($config['port']) && $config['port'] != 3306) ? (';port=' . $config['port']) : '';
		$dsn .= ';dbname=' . $config['database'];
		$dsn .= ';charset=' . $this->_charset;
		$options = array(
			\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_EMULATE_PREPARES => false,
			\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
		);
		try
		{
			$this->_handle = new \PDO($dsn, $config['user'], $config['pass'], $options);
		}
		catch (\PDOException $e)
		{
			throw new Exceptions\DBError($e->getMessage(), $e->getCode());
		}
		
		// Get the DB version.
		$this->db_version = $this->_handle->getAttribute(\PDO::ATTR_SERVER_VERSION);
	}
	
	/**
	 * Get the raw PDO handle.
	 * 
	 * @return PDO
	 */
	public function getHandle(): \PDO
	{
		return $this->_handle;
	}
	
	/**
	 * Execute an XML-defined query.
	 * 
	 * @param string $query_id
	 * @param array $args
	 * @param array $columns
	 * @param string $type
	 * @return \BaseObject
	 */
	public function executeQuery(string $query_id, $args = [], $column_list = []): \BaseObject
	{
		// Validate the args.
		if (is_object($args))
		{
			$args = get_object_vars($args);
		}
		if (!is_array($args))
		{
			return $this->setError(-1, 'Invalid query arguments.');
		}
		
		// Force the column list to a numerical array.
		$column_list = is_array($column_list) ? array_values($column_list) : array();
		
		// Start measuring elapsed time.
		$class_start_time = microtime(true);
		
		// Get the name of the XML file.
		$parts = explode('.', $query_id);
		if (count($parts) === 2)
		{
			array_unshift($parts, 'modules');
		}
		$filename = \RX_BASEDIR . $parts[0] . '/' . $parts[1] . '/queries/' . $parts[2] . '.xml';
		if (!Storage::exists($filename))
		{
			return $this->setError(-1, 'Query \'' . $query_id . '\' does not exist.');
		}
		
		// Parse and cache the XML file.
		$cache_key = sprintf('query:%s:%d', $filename, filemtime($filename));
		$query = Cache::get($cache_key);
		if (!$query)
		{
			$query = Parsers\DBQueryParser::loadXML($filename);
			if ($query)
			{
				Cache::set($cache_key, $query, 0, true);
			}
			else
			{
				return $this->setError(-1, 'Query \'' . $query_id . '\' cannot be parsed.');
			}
		}
		
		// Get the query string and parameters.
		try
		{
			$query_string = $query->getQueryString($this->_prefix, $args, $column_list);
			$query_params = $query->getQueryParams();
		}
		catch (Exceptions\QueryError $e)
		{
			return $this->setError(-1, $e->getMessage());
		}
		
		// Prepare and execute the query.
		try
		{
			$query_start_time = microtime(true);
			$this->_last_stmt = $this->_query($query_string, $query_params);
			$query_elapsed_time = microtime(true) - $query_start_time;
			$result = $this->_fetch($this->_last_stmt);
		}
		catch (\PDOException $e)
		{
			return $this->setError(-1, $e->getMessage());
		}
		
		// Compose the result object.
		$output = new \BaseObject;
		$output->add('_query', $query_string);
		$output->add('_elapsed_time', sprintf("%0.5f", $query_elapsed_time));
		$output->data = $result;
		
		// Compose statistics about elapsed time.
		$class_elapsed_time = microtime(true) - $class_start_time;
		$this->_query_time += $query_elapsed_time;
		$this->_total_time += $class_elapsed_time;
		
		// Return the complete result.
		return $output;
	}
	
	/**
	 * Execute a literal query string.
	 * 
	 * This method should not be public, as it starts with an underscore.
	 * But since there are many legacy apps that rely on it, we will leave it public.
	 * 
	 * @param string $query_string
	 * @param array $query_params
	 * @return \PDOStatement
	 */
	public function _query(string $query_string, array $query_params = []): \PDOStatement
	{
		if (count($query_params))
		{
			$stmt = $this->_handle->prepare($query_string);
			$stmt->execute($query_params);
		}
		else
		{
			$stmt = $this->_handle->query($query_string);
		}
		
		return $stmt;
	}
	
	/**
	 * Fetch results from a query.
	 * 
	 * This method should not be public, as it starts with an underscore.
	 * But since there are many legacy apps that rely on it, we will leave it public.
	 * 
	 * @param \PDOStatement $stmt
	 * @param int $last_index
	 * @return array|object
	 */
	public function _fetch(\PDOStatement $stmt, int $last_index = 0)
	{
		$result = array();
		$index = $last_index;
		$step = $last_index !== 0 ? -1 : 1;
		
		while ($row = $stmt->fetchObject())
		{
			$result[$index] = $row;
			$index += $step;
		}
		
		$stmt->closeCursor();
		
		if ($last_index === 0 && count($result) === 1)
		{
			return $result[0];
		}
		else
		{
			return $result;
		}
	}
	
	/**
	 * Begin a transaction.
	 * 
	 * @return int
	 */
	public function begin(): int
	{
		if (!$this->_handle->inTransaction())
		{
			try
			{
				$this->_handle->beginTransaction();
				$result = 'success';
			}
			catch (\PDOException $e)
			{
				$result = 'error';
			}
			$this->setQueryLog(array('query' => 'START TRANSACTION', 'result' => $result));
		}
		$this->_transaction_level++;
		return $this->_transaction_level;
	}
	
	/**
	 * Roll back a transaction.
	 * 
	 * @return int
	 */
	public function rollback(): int
	{
		if ($this->_handle->inTransaction() && $this->_transaction_level === 1)
		{
			try
			{
				$this->_handle->rollBack();
				$result = 'success';
			}
			catch (\PDOException $e)
			{
				$result = 'error';
			}
			$this->setQueryLog(array('query' => 'ROLLBACK', 'result' => $result));
		}
		$this->_transaction_level--;
		return $this->_transaction_level;
	}
	
	/**
	 * Commit a transaction.
	 * 
	 * @return int
	 */
	public function commit(): int
	{
		if ($this->_handle->inTransaction() && $this->_transaction_level === 1)
		{
			try
			{
				$this->_handle->commit();
				$result = 'success';
			}
			catch (\PDOException $e)
			{
				$result = 'error';
			}
			$this->setQueryLog(array('query' => 'COMMIT', 'result' => $result));
		}
		$this->_transaction_level--;
		return $this->_transaction_level;
	}
	
	/**
	 * Get the number of rows affected by the last statement.
	 * 
	 * @return int
	 */
	public function getAffectedRows(): int
	{
		return $this->_last_stmt ? intval($this->_last_stmt->rowCount()) : 0;
	}
	
	/**
	 * Get the auto-incremented ID generated by the last statement.
	 * 
	 * @return int
	 */
	public function getInsertID(): int
	{
		return intval($this->_handle->lastInsertId());
	}
	
	/**
	 * Get the next global sequence value.
	 */
	public function getNextSequence()
	{
		$this->_query(sprintf('INSERT INTO `%ssequence` (seq) VALUES (0)', $this->_prefix));
		$sequence = $this->getInsertID();
		if($sequence % 10000 == 0)
		{
			$this->_query(sprintf('DELETE FROM `%ssequence` WHERE seq < %d', $this->_prefix, $sequence));
		}
		return $sequence;
	}
	
	/**
	 * Check if a password is valid according to MySQL's old password hashing algorithm.
	 * 
	 * @param string $password
	 * @param string $saved_password
	 * @return bool
	 */
	public function isValidOldPassword(string $password, string $saved_password): bool
	{
		$stmt = $this->_query('SELECT' . ' ' . 'PASSWORD(?) AS pw1, OLD_PASSWORD(?) AS pw2', array($password, $password));
		$result = $this->_fetch($stmt);
		if ($result->pw1 === $saved_password || $result->pw2 === $saved_password)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Check if a table exists.
	 *
	 * @param string $table_name
	 * @return bool
	 */
	public function isTableExists(string $table_name): bool
	{
		return true;
	}
	
	/**
	 * Create a table.
	 * 
	 * @param string $filename
	 * @param string $content
	 * @return BaseObject
	 */
	public function createTable(string $filename = '', string $content = ''): \BaseObject
	{
		$table = Parsers\DBTableParser::loadXML($filename, $content);
		$query = $table->getCreateQuery($this->_prefix, $this->_charset, $this->_engine);
		
		return new \BaseObject;
	}
	
	/**
	 * Drop a table.
	 * 
	 * @param string $table_name
	 * @return BaseObject
	 */
	public function dropTable(string $table_name): \BaseObject
	{
		return new \BaseObject;
	}
	
	/**
	 * Check if a column exists.
	 *
	 * @param string $table_name
	 * @param string $column_name
	 * @return bool
	 */
	public function isColumnExists(string $table_name, string $column_name): bool
	{
		return true;
	}
	
	/**
	 * Add a column.
	 * 
	 * @param string $table_name
	 * @param string $column_name
	 * @param string $type
	 * @param string $size
	 * @param mixed $default
	 * @param bool $notnull
	 * @param string $after_column
	 * @return BaseObject
	 */
	public function addColumn(string $table_name, string $column_name, string $type = 'number', $size = null, $default = null, $notnull = false, $after_column = null): \BaseObject
	{
		return new \BaseObject;
	}
	
	/**
	 * Modify a column.
	 * 
	 * @param string $table_name
	 * @param string $type
	 * @param string $size
	 * @param mixed $default
	 * @param bool $notnull
	 * @return BaseObject
	 */
	public function modifyColumn(string $table_name, string $column_name, string $type = 'number', $size = null, $default = null, $notnull = false): \BaseObject
	{
		return new \BaseObject;
	}
	
	/**
	 * Drop a column.
	 * 
	 * @param string $table_name
	 * @param string $column_name
	 * @return BaseObject
	 */
	public function dropColumn(string $table_name, string $column_name): \BaseObject
	{
		return new \BaseObject;
	}
	
	/**
	 * Get column information.
	 *
	 * @param string $table_name
	 * @param string $column_name
	 * @return Parsers\DBTable\Column;
	 */
	public function getColumnInfo(string $table_name, string $column_name): Parsers\DBTable\Column
	{
		return new Parsers\DBTable\Column;
	}
	
	/**
	 * Check if an index exists.
	 * 
	 * @param string $table_name
	 * @param string $index_name
	 * @return boolean
	 */
	function isIndexExists(string $table_name, string $index_name): bool
	{
		return true;
	}
	
	/**
	 * Add an index.
	 * 
	 * @param string $table_name
	 * @param string $index_name
	 * @param array $columns
	 * @param bool $unique
	 * @return \BaseObject
	 */
	function addIndex(string $table_name, string $index_name, $columns, $unique = false): \BaseObject
	{
		if (!is_array($columns))
		{
			$columns = array($columns);
		}
		
		return new \BaseObject;
	}
	
	/**
	 * Drop an index.
	 * 
	 * @param string $table_name
	 * @param string $index_name
	 * @return BaseObject
	 */
	function dropIndex(string $table_name, string $index_name): \BaseObject
	{
		return new \BaseObject;
	}
	
	/**
	 * Escape a string according to current DB settings.
	 */
	public function addQuotes($str): string
	{
		if (is_numeric($str))
		{
			return strval($str);
		}
		else
		{
			return $this->_handle->quote($str);
		}
	}
	
	/**
	 * Find out the best supported character set.
	 * 
	 * @return string
	 */
	public function getBestSupportedCharset(): string
	{
		$output = $this->_fetch($this->_query("SHOW CHARACTER SET LIKE 'utf8%'"), 1);
		$utf8mb4_support = ($output && count(array_filter($output, function($row) {
			return $row->Charset === 'utf8mb4';
		})));
		return $utf8mb4_support ? 'utf8mb4' : 'utf8';
	}
	
	/**
	 * Check if the last statement produced an error.
	 * 
	 * @return bool
	 */
	public function isError(): bool
	{
		return $this->_errno !== 0 ? true : false;
	}
	
	/**
	 * Get the last error information.
	 * 
	 * @return \BaseObject
	 */
	public function getError(): \BaseObject
	{
		return new \BaseObject($this->_errno, $this->_errstr);
	}
	
	/**
	 * Set error information to instance properties.
	 * 
	 * @param int $errno
	 * @param string $errstr
	 * @return BaseObject
	 */
	public function setError(int $errno = 0, string $errstr = 'success', bool $page_handler = false): \BaseObject
	{
		$this->_errno = $errno;
		$this->_errstr = $errstr;
		$output = new \BaseObject($errno, $errstr);
		if ($page_handler)
		{
			
		}
		
		return $output;
	}
	
	/**
	 * Send an entry to the query log for debugging.
	 * 
	 * @param array $log
	 * @return void
	 */
	public function setQueryLog(array $log)
	{
		Debug::addQuery($log);
	}
	
	/**
	 * ========================== DEPRECATED METHODS ==========================
	 * ==================== KEPT FOR COMPATIBILITY WITH XE ====================
	 */
	
	/**
	 * Old alias to getInstance().
	 * 
	 * @deprecated
	 * @return self
	 */
	public static function create(): self
	{
		return self::getInstance();
	}
	
	/**
	 * Old alias to $stmt->fetchObject().
	 * 
	 * @deprecated
	 * @param \PDOStatement $stmt
	 * @return object|false
	 */
	public function db_fetch_object(\PDOStatement $stmt)
	{
		return $stmt->fetchObject();
	}
	
	/**
	 * Old alias to $stmt->closeCursor().
	 * 
	 * @deprecated
	 * @param \PDOStatement $stmt
	 * @return bool
	 */
	public function db_free_result(\PDOStatement $stmt): bool
	{
		return $stmt->closeCursor();
	}
	
	/**
	 * Old alias to getInsertID().
	 * 
	 * @deprecated
	 * @return int
	 */
	public function db_insert_id(): int
	{
		return $this->getInsertID();
	}
	
	/**
	 * Get the list of supported database drivers.
	 * 
	 * @deprecated
	 * @return array
	 */
	public static function getSupportedList(): array
	{
		return array(
			(object)array(
				'db_type' => 'mysql',
				'enable' => extension_loaded('pdo_mysql'),
			),
		);
	}
	
	/**
	 * Get the list of enabled database drivers.
	 * 
	 * @deprecated
	 * @return array
	 */
	public static function getEnableList(): array
	{
		return array_filter(self::getSupportedList(), function($item) {
			return $item->enable;
		});
	}
   
	/**
	 * Get the list of disabled database drivers.
	 * 
	 * @deprecated
	 * @return array
	 */
	public static function getDisableList(): array
	{
		return array_filter(self::getSupportedList(), function($item) {
			return !$item->enable;
		});
	}
	
	/**
	 * Check if the current instance is supported.
	 * 
	 * @deprecated
	 * @return bool
	 */
	public function isSupported(): bool
	{
		return true;
	}
	
	/**
	 * Check if the current instance is connected.
	 * 
	 * @deprecated
	 * @return bool
	 */
	public function isConnected(): bool
	{
		return true;
	}
	
	/**
	 * Close the DB connection.
	 * 
	 * @deprecated
	 * @return bool
	 */
	public function close(): bool
	{
		return true;
	}
	
	/**
	 * Methods related to table creation.
	 * 
	 * @deprecated
	 * @return void
	 */
	public function createTableByXmlFile($filename)
	{
		$output = $this->createTable($filename);
		return $output->toBool();
	}
	public function createTableByXml($xml_doc)
	{
		$output = $this->createTable('', $xml_doc);
		return $output->toBool();
	}
	public function _createTable($xml_doc)
	{
		$output = $this->createTable('', $xml_doc);
		return $output->toBool();
	}
	
	/**
	 * Methods related to the click count cache feature.
	 * 
	 * @deprecated
	 * @return bool
	 */
	public function getCountCache(): bool
	{
		return false;
	}
	public function putCountCache(): bool
	{
		return false;
	}
	public function resetCountCache(): bool
	{
		return false;
	}
	
	/**
	 * Other deprecated methods.
	 */
	public function getParser(): bool
	{
		return false;
	}
	public function _getSlaveConnectionStringIndex(): int
	{
		return 0;
	}
	public function _getConnection(): \PDO
	{
		return $this->getHandle();
	}
	public function _dbInfoExists(): bool
	{
		return true;
	}
}
