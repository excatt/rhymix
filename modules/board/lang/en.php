<?php
$lang->board = 'Board';
$lang->except_notice = 'Exclude Notices';
$lang->use_bottom_list = 'Display Bottom List';
$lang->customize_bottom_list = 'Customize Bottom List';
$lang->use_anonymous = 'Use Anonymous';
$lang->anonymous_except_admin = 'Except Admin about Anonymous';
$lang->anonymous_name = 'Anonymous Name for Display';
$lang->cmd_manage_menu = 'Manage Menus';
$lang->list_target_item = 'Target Item';
$lang->list_display_item = 'Display Item';
$lang->summary = 'Summary';
$lang->thumbnail = 'Thumbnail';
$lang->last_post = 'Last post';
$lang->board_management = 'Board Management';
$lang->search_result = 'Search Result';
$lang->consultation = 'Consultation';
$lang->secret = 'Secret';
$lang->thisissecret = 'This is a secret article.';
$lang->admin_mail = 'Administrator\'s Mail';
$lang->update_log = 'Update Log';
$lang->cmd_board_list = 'Boards List';
$lang->cmd_module_config = 'Common Board Setting';
$lang->cmd_board_info = 'Board Info';
$lang->cmd_list_setting = 'List Setting';
$lang->cmd_list_items = 'Displayed Items and Order';
$lang->cmd_create_board = 'Create a new board';
$lang->cmd_manage_selected_board = 'Manage Selected Board';
$lang->about_layout_setup = 'You can manually modify board layout code. Insert or manage the widget code anywhere you want';
$lang->about_board_category = 'You can make board categories. When board category is broken, try rebuilding the cache file manually.';
$lang->about_except_notice = 'Notices will not be displayed in the normal list.<br />Caution: using this option can increase DB load if you have many visitors and lots of articles.';
$lang->about_use_bottom_list = 'Display the list at the bottom when viewing an article.';
$lang->about_customize_bottom_list = 'Calculating the bottom list consumes a lot of server resources.<br />You may be able to reduce DB load by not calculating it exactly when not needed.<br />This should have no effect on SEO.';
$lang->about_use_anonymous_part1 = 'Hide the author\'s nickname to turn this board into an anonymous board.';
$lang->about_use_anonymous_part2 = 'It is more useful if you also hide the nickname in the skin.<br>Please also turn off document history, or the author\'s information may be revealed by the history.';
$lang->about_anonymous_except_admin = 'Administrator members\' nicknames would not be hidden.';
$lang->about_anonymous_name = 'You can customize the anonymous name that is displayed instead of the author\'s nickname.<br><b>$NUM</b> will be replaced with a random number that is unique to each member. (e.g. anon_$NUM → anon_12345678)<br><b>$DAILYNUM</b> will be replaced with a random number that is unique to each member but changes every day.<br><b>$DOCNUM</b> will be replaced with a random number that is unique to each member and changes from document to document.<br><b>$DOCDAILYNUM</b> will be replaced with a random number that is unique to each member and changes every day from document to document.';
$lang->about_board = 'This module is for creating and managing boards.';
$lang->about_consultation = 'Non-administrator members would see their own articles. Non-members would not be able to write articles when using consultation.';
$lang->about_secret = 'Users will be able to write secret articles or comments.';
$lang->about_admin_mail = 'A mail will be sent when an article or comment is submitted. Mails can be sent to mutiple mail addresses if connecting addresses with commas(,).';
$lang->about_list_config = 'If using list-style skin, you may arrange items to display. However, this feature might not be availble for non-official skins. If you double-click target items and display items, then you can add / remove them';
$lang->about_use_status = 'Please select status that can be selected when you write article.';
$lang->about_protect_comment = 'Prevent updating or deleting a comment if it has children.';
$lang->about_update_log = 'Store a log of every version of a document every time it is updated.';
$lang->skip_bottom_list_for_olddoc = 'Do not calculate the bottom list exactly when viewing an old article.';
$lang->skip_bottom_list_for_robot = 'Do not calculate the bottom list exactly when a robot is visiting.';
$lang->msg_not_enough_point = 'Your point is not enough to write an article in this board.';
$lang->write_comment = 'Write a comment';
$lang->msg_not_allow_comment = 'This article is not allowed to write comment.';
$lang->no_board_instance = 'There is no board created.';
$lang->choose_board_instance = 'Please choose one or more board instance.';
$lang->comment_status = 'Comments';
$lang->category_settings = 'Category settings';
$lang->hide_category = 'Hide categories';
$lang->about_hide_category = 'You can disable a category feature.';
$lang->allow_no_category = 'Do not require category';
$lang->about_allow_no_category = 'Allow users to write documents without selecting a category.';
$lang->protect_content = 'Protect Content';
$lang->protect_comment = 'Protect Comment';
$lang->protect_admin_content = 'Protect Admin Content';
$lang->protect_regdate = 'Update/Delete Time Limit';
$lang->cancel_vote = 'Vote Cancellation';
$lang->filter_specialchars = 'Block Abuse of Unicode Symbols';
$lang->document_length_limit = 'Limit Document Size';
$lang->comment_length_limit = 'Limit Comment Size';
$lang->about_document_length_limit = 'Restrict documents that are too large. This limit may be triggered by copying and pasting a web page that contains a lot of unnecessary tags.';
$lang->about_comment_length_limit = 'Restrict comments that are too large.';
$lang->update_order_on_comment = 'Update Document on New Comment';
$lang->about_update_order_on_comment = 'When a new comment is posted, update the update timestamp of the parent document. This is needed for forums.';
$lang->non_login_vote = 'Allow guest votes';
$lang->about_cancel_vote = 'Allow users to cancel their votes';
$lang->about_filter_specialchars = 'Prevent use of excessive Unicode accents, RLO characters, and other symbols that hinder readability.';
$lang->document_force_to_move = 'Delete to Trash';
$lang->about_document_force_to_move = 'When a document is deleted, move to Trash instead of deleting it permamently.';
$lang->about_non_login_vote = 'Allow users who are not logged in to vote on articles.';
$lang->about_protect_regdate = 'Prevent updating or deleting a document or comment after a certain amount of time has passed. (Unit: day)';
$lang->about_protect_content = 'Prevent updating a document if there are comments on it.';
$lang->about_protect_admin_content = 'Prevent updating or deleting a document or comment written by the administrator, even by a user who is permitted to manage the board.';
$lang->msg_protect_delete_content = 'You cannot delete a document with comments on it.';
$lang->msg_protect_update_content = 'You cannot update a document with comments on it.';
$lang->msg_admin_document_no_modify = 'You cannot edit the administrator\'s document.';
$lang->msg_admin_comment_no_modify = 'You cannot edit the administrator\'s comment.';
$lang->msg_board_delete_protect_comment = 'You cannot delete a comment when there are replies.';
$lang->msg_board_update_protect_comment = 'You cannot update a comment when there are replies.';
$lang->msg_protect_regdate_document = 'You cannot update or delete a document after %d days.';
$lang->msg_protect_regdate_comment = 'You cannot update or delete a comment after %d days.';
$lang->msg_dont_have_update_log = 'This document has no update log.';
$lang->msg_content_too_long = 'The content is too long.';
$lang->original_letter = 'Original';
$lang->msg_warning_update_log = '<span class="x_label x_label-important">Warning!</span> This can massively increase the size of your database.';
$lang->comment_delete_message = 'Leave Placeholder for Deleted Comment';
$lang->about_comment_delete_message = 'When a comment is deleted, leave a placeholder saying that it has been deleted.';
$lang->cmd_only_p_comment = 'Only if there are replies';
$lang->cmd_all_comment_message = 'Always';
$lang->cmd_do_not_message = 'Never';
$lang->delete_placeholder = 'Delete Placeholder';
$lang->msg_document_notify_mail = '[%s] The new post : %s';
$lang->cmd_board_combined_board = 'Combined Board';
$lang->about_board_combined_board = 'You can use this board to view documents from other boards. Press the Ctrl key and click to select multiple boards.<br />Caution: view permissions for the current board will apply to all affected documents.';
$lang->cmd_board_include_modules = 'Include Boards';
$lang->cmd_board_include_modules_none = '(None)';
$lang->cmd_board_include_days = 'Include Duration';
$lang->about_board_include_days = 'Only combine recent documents. If this value is set to 0, all documents from selected boards will be combined.<br />Durations shorter than 1 day can be set as fractions of a day, e.g. 0.25 days = 6 hours.';
$lang->cmd_board_include_notice = 'Include Notices';