SET sql_mode = '';

--
DROP TABLE IF EXISTS `kbp_entry_draft`;
--
CREATE TABLE IF NOT EXISTS `kbp_entry_draft` (
  `id_key` varchar(32) NOT NULL,
  `entry_id` int(10) unsigned NOT NULL,
  `entry_type` tinyint(4) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `entry_obj` longtext NOT NULL,
  `date_saved` timestamp NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  KEY `entry_id` (`entry_id`,`entry_type`),
  KEY `id_key` (`id_key`(3))
) TYPE=MyISAM;

--
DROP TABLE IF EXISTS `kbp_entry_rule`;
--
CREATE TABLE IF NOT EXISTS `kbp_entry_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_type` tinyint(3) unsigned NOT NULL,
  `directory` varchar(255) NOT NULL DEFAULT '',
  `parse_child` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `description` text NOT NULL,
  `date_executed` datetime NOT NULL,
  `entry_obj` text NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `entry_type` (`entry_type`)
) TYPE=MyISAM;

--
DROP TABLE IF EXISTS `kbp_export`;
--
CREATE TABLE IF NOT EXISTS `kbp_export` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `filetype` varchar(100) NOT NULL,
  `export_option` text NOT NULL,
  `active` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) TYPE=MyISAM;

--
DROP TABLE IF EXISTS `kbp_export_data`;
--
CREATE TABLE IF NOT EXISTS `kbp_export_data` (
  `export_id` int(10) NOT NULL DEFAULT '0',
  `export_type` tinyint(4) unsigned NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `export_data` longblob NOT NULL,
  `export_result` text NOT NULL,
  `content_type` varchar(100) NOT NULL,
  KEY `export_id` (`export_id`)
) TYPE=MyISAM;

--
DROP TABLE IF EXISTS `kbp_kb_entry_history`;
--
CREATE TABLE IF NOT EXISTS `kbp_kb_entry_history` (
  `entry_id` int(10) unsigned NOT NULL,
  `revision_num` tinyint(3) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `date_posted` timestamp NOT NULL,
  `comment` text NOT NULL,
  `entry_data` mediumtext NOT NULL,
  KEY `entry_id` (`entry_id`)
) TYPE=MyISAM;

--
DROP TABLE IF EXISTS `kbp_log_search`;
--
CREATE TABLE IF NOT EXISTS `kbp_log_search` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `date_search` timestamp NOT NULL,
  `search_type` tinyint(4) NOT NULL DEFAULT '0',
  `search_option` text NOT NULL,
  `search_string` varchar(255) NOT NULL,
  `user_ip` int(11) unsigned NOT NULL DEFAULT '0',
  `exitcode` tinyint(4) NOT NULL DEFAULT '0'
) TYPE=MyISAM;

--
DROP TABLE IF EXISTS `kbp_priv_module`;
--
CREATE TABLE IF NOT EXISTS `kbp_priv_module` (
  `id` smallint(6) NOT NULL DEFAULT '0',
  `parent_id` smallint(6) NOT NULL DEFAULT '0',
  `parent_setting_id` tinyint(1) NOT NULL DEFAULT '0',
  `module_name` varchar(30) NOT NULL DEFAULT '0',
  `menu_name` varchar(50) NOT NULL DEFAULT '',
  `use_in_sub_menu` enum('NO','YES_DEFAULT','YES_NOT_DEFAULT') DEFAULT NULL,
  `as_sub_menu` tinyint(1) NOT NULL DEFAULT '0',
  `by_default` varchar(30) NOT NULL DEFAULT '',
  `own_priv` tinyint(1) NOT NULL DEFAULT '0',
  `check_priv` tinyint(1) NOT NULL DEFAULT '1',
  `status_priv` tinyint(1) NOT NULL DEFAULT '0',
  `what_priv` varchar(50) DEFAULT NULL,
  `extra_priv` varchar(255) DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) TYPE=MyISAM;

--
INSERT INTO `kbp_priv_module` (`id`, `parent_id`, `parent_setting_id`, `module_name`, `menu_name`, `use_in_sub_menu`, `as_sub_menu`, `by_default`, `own_priv`, `check_priv`, `status_priv`, `what_priv`, `extra_priv`, `sort_order`, `active`) VALUES
(0, 0, 0, 'all', '', 'NO', 0, '', 0, 1, 0, NULL, NULL, 0, 1),
(1, 0, 0, 'users', 'Users', 'NO', 0, 'user', 0, 1, 0, NULL, NULL, 29, 1),
(3, 0, 0, 'setting', 'Settings', 'NO', 0, 'kb_setting', 0, 1, 0, NULL, NULL, 200, 1),
(100, 0, 3, 'knowledgebase', 'KnowledgeBase', 'NO', 0, 'kb_entry', 0, 1, 0, NULL, NULL, 5, 1),
(12, 1, 0, 'priv', 'Privileges', 'NO', 0, '', 0, 1, 0, NULL, NULL, 4, 1),
(101, 100, 0, 'kb_entry', 'Questions', 'NO', 0, '', 1, 1, 2, NULL, NULL, 1, 1),
(102, 100, 0, 'kb_category', 'Categories', 'NO', 0, '', 0, 1, 0, NULL, NULL, 15, 1),
(104, 100, 0, 'kb_comment', 'Comments', 'NO', 0, '', 0, 1, 0, NULL, NULL, 3, 1),
(105, 100, 0, 'kb_glossary', 'Glossary', 'NO', 0, '', 0, 1, 0, NULL, NULL, 5, 1),
(131, 3, 3, 'admin_setting', 'Admin', 'NO', 0, '', 0, 1, 0, 'select,update', NULL, 1, 1),
(10, 1, 0, 'user', 'Users', 'NO', 0, '', 1, 1, 0, NULL, 'self_login', 1, 1),
(8, 0, 0, 'feedback', 'Feedback', 'NO', 0, 'feedback', 0, 1, 0, NULL, NULL, 9, 1),
(130, 3, 0, 'kb_setting', 'KnowledgeBase', 'NO', 0, '', 0, 1, 0, 'select,update', NULL, 2, 1),
(108, 100, 0, 'kb_rate', 'Rating Comments', 'NO', 0, '', 0, 1, 0, NULL, NULL, 4, 1),
(200, 0, 3, 'file', 'Files', 'NO', 0, 'file_entry', 0, 1, 0, NULL, NULL, 6, 1),
(202, 200, 0, 'file_category', 'Categories', 'NO', 0, '', 0, 1, 0, NULL, NULL, 2, 1),
(109, 100, 0, 'kb_client', 'Client View', 'NO', 0, '', 0, 0, 0, NULL, NULL, 23, 1),
(132, 3, 0, 'letter_template', 'Letter Template', 'NO', 0, '', 0, 1, 0, 'select,update', NULL, 12, 1),
(133, 3, 0, 'file_setting', 'Files', 'NO', 0, '', 0, 1, 0, 'select,update', NULL, 5, 1),
(14, 1, 0, 'role', 'Roles', 'NO', 0, '', 0, 1, 0, NULL, NULL, 3, 1),
(2, 0, 0, 'log', 'Logs', 'NO', 0, 'cron_log', 0, 1, 0, 'select', NULL, 201, 1),
(201, 200, 0, 'file_entry', 'Files', 'NO', 0, '', 1, 1, 2, NULL, NULL, 1, 1),
(134, 3, 0, 'email_setting', 'Email', 'NO', 0, '', 0, 1, 0, 'select,update', NULL, 11, 1),
(5, 0, 0, 'account', 'My Account', 'NO', 0, 'account_user', 0, 0, 0, NULL, NULL, 220, 0),
(204, 200, 0, 'file_rule', 'Local Files Rules', 'NO', 0, '', 0, 1, 0, NULL, NULL, 4, 1),
(61, 6, 0, 'export_kb', 'Export KB', 'NO', 0, '', 0, 1, 0, NULL, NULL, 1, 1),
(80, 8, 0, 'feedback', 'Feedback', 'NO', 0, '', 0, 1, 0, NULL, NULL, 1, 1),
(203, 200, 0, 'file_bulk', 'Bulk Actions', 'NO', 0, '', 0, 1, 0, 'insert', NULL, 3, 1),
(43, 4, 0, 'help_about', 'About', 'NO', 0, '', 0, 0, 0, NULL, NULL, 10, 1),
(44, 4, 0, 'help_licence', 'Licence', 'NO', 0, '', 0, 0, 0, 'select', NULL, 11, 0),
(41, 4, 0, 'help', 'Help', 'NO', 0, '', 0, 0, 0, NULL, NULL, 1, 1),
(42, 4, 0, 'help_faq', 'FAQ', 'NO', 0, '', 0, 0, 0, NULL, NULL, 2, 0),
(136, 3, 0, 'backup', 'Backups', 'NO', 0, '', 0, 1, 0, 'select,update', NULL, 20, 0),
(137, 3, 0, 'role', 'Roles', 'NO', 0, '', 0, 2, 0, NULL, NULL, 21, 0),
(138, 3, 0, 'list_setting', 'Lists', 'NO', 0, '', 0, 1, 0, NULL, NULL, 15, 1),
(11, 1, 0, 'company', 'Companies', 'NO', 0, '', 0, 1, 0, NULL, NULL, 2, 1),
(139, 3, 0, 'priv', 'Priviledges', 'NO', 0, '', 0, 2, 0, NULL, NULL, 22, 0),
(107, 100, 0, 'article_template', 'Article Template', 'NO', 0, '', 0, 1, 0, NULL, NULL, 16, 1),
(7, 0, 0, 'import', 'Import', 'NO', 0, 'import_user', 0, 1, 0, 'select,insert', NULL, 190, 1),
(72, 7, 0, 'import_article', 'Import Articles', 'NO', 0, '', 0, 1, 0, 'select,insert', NULL, 2, 1),
(71, 7, 0, 'import_user', 'Import Users', 'NO', 0, '', 0, 1, 0, 'select,insert', NULL, 1, 1),
(74, 7, 0, 'kb_entry', 'Articles', 'NO', 0, '', 0, 2, 0, NULL, NULL, 10, 1),
(141, 3, 0, 'template', 'Template', 'NO', 0, 'email_template', 0, 1, 0, NULL, NULL, 0, 0),
(73, 7, 0, 'user', 'User', 'NO', 0, '', 0, 2, 0, NULL, NULL, 9, 1),
(79, 7, 0, 'spacer', '7', 'NO', 0, '', 0, 0, 0, NULL, NULL, 8, 1),
(9, 0, 0, 'home', 'Home', 'NO', 0, 'home', 0, 1, 0, NULL, NULL, 1, 1),
(90, 9, 0, 'home', 'Home', 'NO', 0, '', 0, 0, 0, NULL, NULL, 1, 1),
(150, 3, 0, 'licence_setting', 'Licence', 'NO', 0, '', 0, 1, 0, 'select,insert,update', NULL, 0, 1),
(45, 4, 0, 'help_request', 'Support Request', 'NO', 0, '', 0, 0, 0, NULL, NULL, 5, 0),
(400, 0, 0, 'report', 'Reports', 'NO', 0, 'report_usage', 0, 1, 0, NULL, NULL, 30, 1),
(401, 400, 0, 'report_home', 'Reports', 'NO', 0, '', 0, 1, 0, 'select', NULL, 1, 0),
(81, 8, 0, 'kb_comment', 'Comments', 'NO', 0, '', 0, 2, 0, NULL, NULL, 5, 1),
(4, 0, 0, 'help', 'Help', 'NO', 0, 'help', 0, 0, 0, NULL, NULL, 220, 1),
(51, 5, 0, 'account_user', 'My Account', 'NO', 0, '', 0, 0, 0, NULL, NULL, 1, 1),
(52, 5, 0, 'account_setting', 'Settings', 'NO', 0, '', 0, 0, 0, NULL, NULL, 10, 1),
(82, 8, 0, 'kb_rate', 'Rating Comments', 'NO', 0, '', 0, 2, 0, NULL, NULL, 4, 1),
(420, 0, 0, 'trigger', 'Triggers', 'NO', 0, 'trigger_entry', 0, 1, 0, NULL, NULL, 35, 0),
(421, 420, 0, 'trigger_entry', 'Triggers', 'NO', 0, '', 1, 1, 0, NULL, NULL, 1, 0),
(75, 7, 0, 'import_glossary', 'Import Glossary', 'NO', 0, '', 0, 1, 0, 'select,insert', NULL, 3, 1),
(21, 2, 0, 'cron_log', 'Cron Logs', 'NO', 0, '', 0, 1, 0, 'select', NULL, 1, 1),
(300, 0, 0, 'news', 'News', 'NO', 0, 'news_entry', 0, 1, 0, NULL, NULL, 2, 1),
(301, 300, 0, 'news_entry', 'News', 'NO', 0, '', 0, 1, 0, NULL, NULL, 1, 1),
(142, 3, 0, 'field_setting', 'Custom Fields', 'NO', 0, '', 0, 1, 0, NULL, NULL, 16, 0),
(402, 400, 0, 'report_usage', 'Usage', 'NO', 0, '', 0, 1, 0, 'select', NULL, 2, 1),
(403, 400, 0, 'report_stat', 'Stat', 'NO', 0, 'rs_article', 0, 1, 0, 'select', NULL, 3, 1),
(4032, 403, 0, 'rs_article', 'Articles', 'NO', 0, '', 0, 0, 0, NULL, NULL, 2, 1),
(4033, 403, 0, 'rs_file', 'Files', 'NO', 0, '', 0, 0, 0, NULL, NULL, 3, 1),
(4034, 403, 0, 'rs_user', 'Users', 'NO', 0, '', 0, 0, 0, NULL, NULL, 6, 1),
(4031, 403, 0, 'rs_summary', 'Summary', 'NO', 0, '', 0, 0, 0, NULL, NULL, 1, 0),
(53, 5, 0, 'account_subsc', 'Subscriptions', 'NO', 0, '', 0, 0, 0, NULL, NULL, 2, 1),
(22, 2, 0, 'login_log', 'Login Logs', 'NO', 0, '', 0, 1, 0, 'select', NULL, 6, 1),
(6, 0, 0, 'export', 'Export', 'NO', 0, 'export_kb', 0, 1, 0, NULL, NULL, 191, 1),
(24, 2, 0, 'search_log', 'Search Log', 'NO', 0, '', 0, 1, 0, 'select', NULL, 8, 1),
(23, 2, 0, 'mail_pool', 'Mail Pool', 'NO', 0, '', 0, 1, 0, 'select', NULL, 3, 1),
(4036, 403, 0, 'rs_news', 'News', 'NO', 0, '', 0, 0, 0, NULL, NULL, 4, 1),
(4035, 403, 0, 'rs_feedback', 'Feedback', 'NO', 0, '', 0, 0, 0, NULL, NULL, 5, 1),
(4037, 403, 0, 'rs_subscriber', 'Subscribers', 'NO', 0, '', 0, 0, 0, NULL, NULL, 7, 0),
(76, 7, 0, 'kb_glossary', 'Glossary', 'NO', 0, '', 0, 2, 0, NULL, NULL, 11, 1),
(140, 3, 0, 'plugin_setting', 'Plugins', 'NO', 0, '', 0, 1, 0, 'select,insert,update', NULL, 20, 1),
(91, 9, 0, 'kbpreport', 'Setup Report', 'NO', 0, '', 0, 1, 0, 'select,update', NULL, 2, 1),
(15, 1, 0, 'ban', 'Ban', 'NO', 0, '', 0, 1, 0, NULL, NULL, 6, 0);

--
DROP TABLE IF EXISTS `kbp_setting`;
--
CREATE TABLE IF NOT EXISTS `kbp_setting` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_module_id` int(10) unsigned NOT NULL DEFAULT '0',
  `group_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `input_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `options` varchar(100) NOT NULL DEFAULT '',
  `setting_key` varchar(255) NOT NULL DEFAULT '',
  `messure` varchar(10) NOT NULL DEFAULT '',
  `range` varchar(255) NOT NULL DEFAULT '',
  `default_value` varchar(255) NOT NULL DEFAULT '',
  `sort_order` float NOT NULL DEFAULT '0',
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `skip_default` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `module_id` (`module_id`)
) TYPE=MyISAM;

--
INSERT INTO `kbp_setting` (`id`, `module_id`, `user_module_id`, `group_id`, `input_id`, `options`, `setting_key`, `messure`, `range`, `default_value`, `sort_order`, `required`, `skip_default`, `active`) VALUES
(1, 100, 0, 10, 1, '', 'allow_comments', '', '0,1,2', '1', 12, 0, 0, 1),
(2, 100, 0, 12, 4, '', 'allow_rating', '', '', '1', 1, 0, 0, 1),
(3, 100, 0, 11, 1, '', 'allow_post_entry', '', '0,1,2', '1', 11, 0, 0, 0),
(4, 100, 0, 3, 1, '', 'num_most_viewed_entries', '', '0,3,5,10,15', '5', 8, 0, 0, 1),
(5, 100, 0, 3, 1, '', 'num_recently_posted_entries', '', '0,3,5,10,15', '5', 7, 0, 0, 1),
(6, 100, 0, 3, 1, '', 'num_entries_per_page', '', '10,15,20', '10', 6, 0, 0, 1),
(7, 100, 0, 8, 1, 'onchange="populateSelect(myOptions[this.value]);"', 'view_format', '', 'default,left', 'left', 2, 0, 0, 1),
(8, 100, 0, 6, 4, '', 'show_hits', '', '', '1', 7, 0, 0, 1),
(9, 100, 0, 10, 1, '', 'comment_policy', '', '1,2,3', '1', 13, 0, 0, 1),
(10, 100, 0, 1, 2, '', 'site_title', '', '', 'Your Company :: Knowledgebase', 1, 0, 1, 1),
(11, 100, 0, 1, 2, '', 'support_email', '', '', 'your@email.com', 10, 0, 1, 0),
(12, 100, 0, 3, 4, '', 'show_glossary_link', '', '', '1', 18, 0, 0, 1),
(13, 100, 0, 8, 2, '', 'page_to_load', '', '', 'Default', 1, 0, 0, 1),
(14, 100, 0, 3, 1, '', 'category_sort_order', '', 'name,sort_order', 'sort_order', 10, 0, 0, 0),
(15, 100, 0, 6, 4, '', 'show_send_link', '', '', '1', 8, 0, 0, 1),
(16, 100, 0, 3, 1, '', 'show_num_entries', '', '0,1', '1', 10.1, 0, 0, 1),
(17, 100, 0, 1, 4, '', 'show_title_nav', '', '', '1', 12, 0, 0, 1),
(104, 100, 0, 3, 1, '', 'entry_sort_order', '', 'name,sort_order,added_desc,added_asc,updated_desc,updated_asc,hits_desc,hits_asc', 'sort_order', 1, 0, 0, 1),
(19, 100, 0, 1, 2, '', 'nav_title', '', '', 'KB Home', 6, 0, 1, 1),
(20, 200, 0, 1, 2, '', 'file_dir', '', '', '[document_root_parent]/kb_file/', 10, 1, 1, 1),
(21, 200, 0, 1, 4, '', 'file_extract', '', '', '1', 15, 0, 0, 1),
(22, 200, 0, 1, 2, '', 'file_denied_extensions', '', '', 'php,php3,php5,phtml,asp,aspx,ascx,jsp,cfm,cfc,pl,bat,exe,dll,reg,cgi', 13, 0, 0, 1),
(106, 100, 0, 2, 1, '', 'register_captcha', '', 'no,yes', 'yes', 7, 0, 0, 1),
(23, 200, 0, 1, 2, '', 'file_max_filesize', '', '', '2048', 11, 0, 0, 1),
(24, 200, 0, 1, 1, '', 'file_store', '', 'dir,db', 'dir', 0, 0, 0, 0),
(25, 200, 0, 1, 2, '', 'file_allowed_extensions', '', '', '', 12, 0, 0, 1),
(26, 200, 0, 1, 1, '', 'file_rename_policy', '', 'date_Ymd-His,date_Ymd,date_Y,suffics_3', 'date_Ymd-His', 10, 0, 0, 0),
(27, 200, 0, 3, 1, '', 'num_most_viewed_entries', '', '0,3,5,10,15', '5', 3, 0, 0, 1),
(28, 200, 0, 3, 1, '', 'num_recently_posted_entries', '', '0,3,5,10,15', '5', 2, 0, 0, 1),
(29, 200, 0, 3, 1, '', 'num_entries_per_page', '', '10,15,20', '10', 1, 0, 0, 1),
(30, 200, 0, 3, 1, '', 'category_sort_order', '', 'name,sort_order', 'sort_order', 4, 0, 0, 0),
(31, 200, 0, 3, 1, '', 'show_num_entries', '', '0,1', '1', 5, 0, 0, 1),
(34, 100, 0, 3, 4, '', 'show_file_link', '', '', '1', 17, 0, 0, 1),
(33, 100, 0, 2, 4, '', 'kb_register_access', '', '', '0', 1, 0, 0, 1),
(35, 100, 0, 2, 1, '', 'private_policy', '', '1,2', '1', 16, 0, 0, 1),
(36, 200, 0, 2, 1, '', 'private_policy', '', '1,2', '1', 0, 0, 0, 1),
(49, 134, 0, 2, 2, '', 'smtp_port', '', '', '25', 7, 0, 0, 1),
(38, 100, 0, 2, 4, '', 'register_policy', '', '', '1', 2, 0, 0, 1),
(39, 100, 0, 2, 2, 'size="10"', 'auth_expired', '', '', '60', 14, 0, 0, 1),
(40, 134, 0, 2, 1, '', 'mailer', '', 'mail,smtp,sendmail', 'mail', 3, 1, 0, 1),
(41, 134, 0, 1, 2, '', 'from_email', '', '', '', 2, 1, 1, 1),
(42, 134, 0, 1, 2, '', 'from_name', '', '', 'Support Team', 3, 0, 0, 1),
(43, 134, 0, 2, 2, '', 'sendmail_path', '', '', '/usr/sbin/sendmail', 4, 0, 0, 1),
(109, 100, 0, 11, 2, 'size="10"', 'contact_attachment', '', '', '1', 12, 0, 0, 1),
(45, 134, 0, 2, 2, '', 'smtp_user', '', '', '', 9, 0, 0, 1),
(46, 134, 0, 2, 5, '', 'smtp_pass', '', '', '', 10, 0, 0, 1),
(47, 134, 0, 2, 2, '', 'smtp_host', '', '', '', 6, 0, 0, 1),
(50, 100, 0, 5, 2, 'size="10"', 'preview_article_limit', '', '', '300', 3, 0, 0, 1),
(51, 100, 0, 5, 4, '', 'preview_show_comments', '', '', '1', 8, 0, 0, 1),
(52, 100, 0, 5, 4, '', 'preview_show_rating', '', '', '0', 7, 0, 0, 1),
(53, 100, 0, 5, 4, '', 'preview_show_hits', '', '', '0', 10, 0, 0, 1),
(54, 100, 0, 5, 4, '', 'preview_show_date', '', '', '1', 5, 0, 0, 1),
(55, 100, 0, 6, 4, '', 'show_author', '', '', '0', 1.5, 0, 0, 1),
(56, 134, 0, 1, 2, '', 'from_mailer', '', '', 'KBMailer', 1, 0, 0, 1),
(57, 100, 0, 2, 4, '', 'register_confirmation', '', '', '1', 8, 0, 0, 0),
(58, 100, 0, 3, 1, '', 'num_entries_category', '', '0,all,3,5,10,15,20', '5', 8.2, 0, 0, 1),
(59, 100, 0, 7, 2, '', 'rss_title', '', '', 'Knowledgebase RSS', 2, 0, 1, 1),
(60, 100, 0, 7, 3, 'rows="2" style="width: 100%"', 'rss_description', '', '', '', 3, 0, 1, 1),
(61, 100, 0, 7, 1, '', 'rss_generate', '', 'none,one,top', 'one', 1, 0, 0, 1),
(62, 100, 0, 1, 3, 'rows="2" style="width: 100%"', 'site_keywords', '', '', '', 2, 0, 1, 1),
(63, 100, 0, 1, 3, 'rows="2" style="width: 100%"', 'site_description', '', '', '', 3, 0, 1, 1),
(64, 100, 0, 3, 1, '', 'num_category_cols', '', '0,1,2,3,4,5', '3', 10.2, 0, 0, 1),
(65, 200, 0, 3, 1, '', 'num_category_cols', '', '0,1,2,3,4,5', '3', 6, 0, 0, 1),
(66, 100, 0, 2, 4, '', 'register_approval', '', '', '0', 3, 0, 0, 1),
(105, 100, 0, 10, 1, '', 'comment_captcha', '', 'no,yes,yes_no_reg', 'yes_no_reg', 12.1, 0, 0, 1),
(101, 100, 0, 1, 2, '', 'header_title', '', '', 'Knowledgebase', 5, 0, 1, 1),
(102, 100, 0, 11, 1, '', 'allow_contact', '', '0,1,2', '1', 11.2, 0, 0, 1),
(103, 100, 0, 8, 1, '', 'view_template', '', '1', 'default', 3, 0, 0, 1),
(107, 100, 0, 11, 1, '', 'contact_captcha', '', 'no,yes,yes_no_reg', 'no', 11.3, 0, 0, 1),
(108, 100, 0, 11, 1, '', 'entry_captcha', '', 'no,yes,yes_no_reg', '', 11.1, 0, 0, 0),
(111, 100, 0, 2, 1, '', 'register_user_priv', '', 'dinamic', '0', 5, 0, 0, 1),
(112, 100, 0, 2, 1, '', 'register_user_role', '', 'dinamic', '0', 6, 0, 0, 1),
(110, 100, 0, 11, 4, '', 'contact_attachment_email', '', '', '0', 12.8, 0, 0, 1),
(114, 100, 0, 6, 4, '', 'show_print_link', '', '', '1', 2, 0, 0, 1),
(115, 100, 0, 3, 2, 'style="width: 100%"', 'entry_prefix_pattern', '', '', '', 16, 0, 0, 1),
(116, 100, 0, 3, 2, 'size="10"', 'entry_id_padding', '', '', '', 15.8, 0, 0, 1),
(44, 134, 0, 2, 4, '', 'smtp_auth', '', '', '1', 8, 0, 0, 1),
(117, 100, 0, 2, 1, '', 'auth_captcha', '', 'no,yes', 'no', 9, 0, 0, 0),
(118, 200, 0, 3, 1, '', 'entry_sort_order', '', 'filename,name,sort_order,added_desc,added_asc,updated_desc,updated_asc,hits_desc,hits_asc', 'sort_order', 1, 0, 0, 1),
(119, 100, 0, 11, 2, '', 'contact_attachment_ext', '', '', '', 12.5, 0, 0, 1),
(120, 100, 0, 6, 4, '', 'show_entry_block', '', '', '1', 1.2, 0, 0, 1),
(149, 1, 1, 3, 1, '', 'num_entries_per_page', '', '10,20,40', '10', 2, 0, 0, 1),
(122, 1, 1, 3, 2, 'size="10"', 'app_width', '', '', '980px', 1, 1, 0, 1),
(123, 1, 0, 2, 2, 'size="10"', 'auth_expired', '', '', '60', 0, 0, 0, 1),
(130, 100, 0, 1, 2, '', 'nav_extra', '', '', '', 11, 0, 1, 1),
(126, 100, 0, 1, 1, '', 'mod_rewrite', '', '1,2,3,9', '1', 15, 0, 0, 1),
(127, 1, 0, 1, 1, '', 'auth_captcha', '', 'no,yes', 'no', 1, 0, 0, 1),
(128, 1, 0, 5, 2, 'style="width: 100%"', 'html_editor_upload_dir', '', '', '[document_root]/kb_upload/', 1, 1, 1, 1),
(129, 100, 0, 3, 4, '', 'show_map_link', '', '', '1', 19, 0, 0, 0),
(131, 100, 0, 2, 1, '', 'login_policy', '', '1,2,9', '1', 11, 0, 0, 1),
(132, 100, 0, 8, 4, '', 'view_header', '', '', '1', 7, 0, 0, 1),
(133, 100, 0, 1, 3, 'rows="2" style="width: 100%"', 'footer_info', '', '', '', 9, 0, 1, 0),
(134, 150, 0, 1, 2, '', 'license_key', '', '', '', 1, 1, 1, 1),
(135, 100, 0, 8, 1, '', 'view_menu_type', '', 'tree,followon,top_tree', 'followon', 5, 0, 0, 1),
(136, 1, 0, 6, 2, 'style="width: 100%"', 'cache_dir', '', '', '[document_root_parent]/kb_cache/', 2, 1, 1, 0),
(137, 100, 0, 3, 1, '', 'nav_prev_next', '', 'yes,yes_no_others', 'yes', 8.4, 0, 0, 1),
(138, 134, 0, 1, 2, '', 'noreply_email', '', '', '[noreply_email]', 4, 1, 0, 1),
(139, 150, 0, 1, 2, '', 'license_key2', '', '', '', 2, 0, 0, 1),
(140, 150, 0, 1, 2, '', 'license_key3', '', '', '', 3, 0, 0, 1),
(141, 200, 0, 1, 2, '', 'file_extract_pdf', '', '', 'off', 18, 0, 1, 1),
(142, 100, 0, 6, 4, '', 'show_private_block', '', '', '1', 1.3, 0, 0, 1),
(143, 100, 0, 10, 1, '', 'num_comments_per_page', '', '10,20,30,40,50', '50', 13.3, 0, 0, 1),
(144, 100, 0, 10, 4, '', 'comments_entry_page', '', '', '0', 13.4, 0, 0, 1),
(145, 1, 0, 5, 4, '', 'entry_date_updateable', '', '', '0', 1, 1, 1, 0),
(146, 1, 1, 3, 1, '', 'entry_sort_order', '', 'name,added_desc,added_asc,updated_desc,updated_asc', 'updated_desc', 5, 0, 0, 1),
(147, 1, 1, 3, 1, '', 'file_sort_order', '', 'filename,added_desc,added_asc,updated_desc,updated_asc', 'updated_desc', 7, 0, 0, 1),
(150, 0, 1, 3, 1, '', 'home_page', '', '1,2,4', '1', 2, 0, 0, 0),
(148, 100, 0, 12, 4, '', 'allow_rating_comment', '', '', '1', 2, 0, 0, 1),
(151, 100, 0, 14, 4, '', 'show_news_link', '', '', '0', 2, 0, 0, 1),
(152, 150, 0, 1, 2, '', 'license_key4', '', '', '', 3, 0, 0, 1),
(153, 100, 0, 14, 1, '', 'num_news_entries', '', '0,1,2,3,5', '1', 5, 0, 0, 1),
(154, 100, 0, 14, 4, '', 'module_news', '', '', '1', 1, 0, 0, 1),
(155, 134, 0, 2, 1, '', 'smtp_secure', '', 'none,ssl,tls', 'none', 6.8, 0, 0, 1),
(156, 200, 0, 1, 2, '', 'file_extract_doc', '', '', 'off', 19, 0, 1, 1),
(160, 134, 0, 1, 2, '', 'admin_email', '', '', '', 7, 0, 1, 1),
(157, 100, 0, 6, 4, '', 'show_pdf_link', '', '', '1', 3, 0, 0, 1),
(158, 100, 0, 15, 1, '', 'allow_subscribe_news', '', '0,2,3', '2', 1, 0, 0, 1),
(162, 0, 0, 5, 4, '', 'hpb_article', '', '', '1', 2, 0, 1, 1),
(161, 100, 0, 6, 2, '', 'show_author_format', '', '', '[last_name] [short_first_name].', 1.6, 0, 0, 1),
(163, 0, 0, 5, 4, '', 'hpb_file', '', '', '1', 2, 0, 1, 1),
(164, 0, 0, 5, 4, '', 'hpb_user', '', '', '1', 2, 0, 1, 1),
(165, 0, 0, 5, 4, '', 'hpb_rating', '', '', '1', 2, 0, 1, 1),
(166, 0, 0, 5, 4, '', 'hpb_comment', '', '', '1', 2, 0, 1, 1),
(167, 100, 0, 15, 1, '', 'allow_subscribe_entry', '', '0,2,3', '2', 2, 0, 0, 1),
(168, 1, 0, 8, 4, '', 'cron_mail_critical', '', '', '1', 1, 0, 0, 1),
(169, 200, 0, 5, 4, '', 'preview_show_hits', '', '', '1', 4, 0, 0, 1),
(170, 134, 0, 3, 2, '', 'mass_mail_send_per_hour', '', '', '250', 1, 0, 0, 1),
(171, 100, 0, 3, 1, '', 'entry_published', '', '0,1', '1', 8.1, 0, 0, 1),
(172, 100, 0, 6, 4, '', 'show_send_link_article', '', '', '0', 9, 0, 0, 1),
(173, 100, 0, 3, 1, '', 'search_default', '', 'all,article,file', 'all', 0.1, 0, 0, 1),
(174, 100, 0, 3, 4, '', 'search_disable_all', '', '', '0', 21, 0, 0, 0),
(175, 1, 0, 10, 2, '', 'entry_history_max', '', '', '10', 1, 1, 0, 1),
(176, 100, 0, 12, 1, '', 'rating_type', '', '1,2', '1', 3, 0, 0, 1),
(177, 100, 0, 10, 1, '', 'allow_subscribe_comment', '', '0,2', '2', 13.1, 0, 0, 1),
(178, 1, 0, 10, 2, '', 'entry_history_days', '', '', '365', 2, 1, 0, 0),
(179, 140, 0, 1, 2, '', 'plugin_export_key', '', '', 'demo', 1, 0, 0, 1),
(180, 140, 0, 1, 2, '', 'plugin_htmldoc_path', '', '', 'off', 2, 0, 0, 1),
(181, 140, 0, 1, 4, '', 'show_pdf_category_link', '', '', '0', 3, 0, 0, 1),
(182, 100, 0, 6, 1, '', 'article_block_position', '', 'right,bottom', 'right', 1, 0, 0, 1),
(183, 1, 1, 10, 2, '', 'entry_autosave', '', '', '3', 3, 1, 0, 1),
(184, 200, 0, 1, 1, '', 'directory_missed_file_policy', '', 'dinamic', 'none', 25, 0, 0, 1),
(185, 1, 0, 2, 4, '', 'account_password_old', '', '', '1', 3, 0, 0, 1),
(186, 200, 0, 1, 2, '', 'file_param_pdf', '', '', '', 18.1, 0, 1, 0),
(187, 200, 0, 1, 2, '', 'file_param_doc', '', '', '', 19.1, 0, 1, 0),
(188, 100, 0, 6, 4, '', 'show_pdf_link_entry_info', '', '', '1', 3.1, 0, 0, 1),
(189, 140, 0, 1, 2, '', 'htmldoc_fontsize', '', '', '10', 5, 0, 0, 1),
(190, 140, 0, 1, 1, '', 'htmldoc_bodyfont', '', 'Arial,Courier,Helvetica,Monospace,Sans Mono,Sans,Serif,Times', 'Arial', 4, 0, 0, 1),
(191, 100, 0, 10, 2, '', 'comments_author_format', '', '', '[username]', 13.4, 0, 0, 1),
(192, 100, 0, 1, 2, '', 'menu_extra', '', '', '', 13, 0, 0, 1);

--
DROP TABLE IF EXISTS `kbp_stuff_category`;
--
CREATE TABLE IF NOT EXISTS `kbp_stuff_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) TYPE=MyISAM  ;

--
DROP TABLE IF EXISTS `kbp_stuff_data`;
--
CREATE TABLE IF NOT EXISTS `kbp_stuff_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `data_key` varchar(30) NOT NULL DEFAULT '',
  `date_posted` timestamp NOT NULL,
  `data_string` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `data_key` (`data_key`)
) TYPE=MyISAM;

--
DROP TABLE IF EXISTS `kbp_stuff_entry`;
--
CREATE TABLE IF NOT EXISTS `kbp_stuff_entry` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL DEFAULT '0',
  `author_id` int(10) unsigned NOT NULL DEFAULT '0',
  `updater_id` int(10) unsigned NOT NULL DEFAULT '0',
  `filedata` longblob NOT NULL,
  `filename` varchar(255) NOT NULL DEFAULT '',
  `filesize` int(10) unsigned NOT NULL DEFAULT '0',
  `filetype` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `date_updated` timestamp NOT NULL,
  `date_posted` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  KEY `filename` (`filename`(4)),
  KEY `category_id` (`category_id`),
  FULLTEXT KEY `title` (`title`,`description`)
) TYPE=MyISAM;

--
INSERT IGNORE kbp_entry_hits (entry_id, entry_type, hits) SELECT id, 1, hits FROM kbp_kb_entry;


--
ALTER TABLE kbp_user_to_role ADD INDEX (`user_id`);


--
ALTER TABLE `kbp_kb_entry` ADD `history_comment` TEXT NULL DEFAULT NULL AFTER `date_posted`,
						   ADD `date_commented` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER `date_posted`;


--
UPDATE `kbp_letter_template` SET `body` = NULL, `extra_tags` = 'new_article,updated_article,commented_article,new_file,updated_file' WHERE `id` = 27;


--
ALTER TABLE `kbp_file_entry` ADD `md5hash` VARCHAR( 32 ) NOT NULL AFTER `filetext`;


--
INSERT INTO `kbp_letter_template` VALUES(28, 'subscription_comment', '', '', '[noreply_email]', '', '[email]', '[first_name] [last_name]', '', '', '', '', NULL, '', NULL, '', 'content', 'link', 1, 2, 1, 1, 112);


--
ALTER TABLE `kbp_email_pool` ADD `failed_message` TEXT NOT NULL AFTER `failed`;



--
ALTER TABLE `kbp_entry_hits` ADD `date_hit` TIMESTAMP NOT NULL;

  
--
ALTER TABLE `kbp_log_login` ADD `username` VARCHAR(50) NOT NULL AFTER `user_ip`,
                            ADD `active` TINYINT NOT NULL DEFAULT '1',
                            ADD INDEX (`username`(3));
 

--
UPDATE `kbp_log_login` SET `active` = 0;



--
UPDATE `kbp_letter_template` SET `from_email` = '[sender_email]', `skip_field` = 'to', `extra_tags` = 'message,entry_title,sender_email' WHERE `id` = 1;



--
ALTER TABLE `kbp_log_cron` DROP INDEX `date_finished`;



--
ALTER TABLE `kbp_email_pool` CHANGE `failed_message` `failed_message` TEXT NULL DEFAULT NULL;


--
ALTER TABLE `kbp_file_entry` CHANGE `filetext` `filetext` MEDIUMTEXT NOT NULL;


--
UPDATE `kbp_letter_template` SET `extra_tags` = 'author_email,updater_email,title,rating,message' WHERE `id` = 23;
