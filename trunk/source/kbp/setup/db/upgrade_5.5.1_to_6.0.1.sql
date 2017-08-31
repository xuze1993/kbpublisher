SET sql_mode = '';

--
DROP TABLE IF EXISTS `kbp_entry_draft_to_category`;
--
CREATE TABLE IF NOT EXISTS `kbp_entry_draft_to_category` (
  `draft_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `category_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  KEY `entry_id` (`draft_id`)
) ENGINE=MyISAM ;

--
DROP TABLE IF EXISTS `kbp_entry_draft_workflow_to_assignee`;
--
CREATE TABLE IF NOT EXISTS `kbp_entry_draft_workflow_to_assignee` (
  `draft_id` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `draft_workflow_id` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `assignee_id` int(11) UNSIGNED NOT NULL DEFAULT '0',
  KEY `draft_id` (`draft_id`),
  KEY `draft_workflow_id` (`draft_workflow_id`)
) ENGINE=MyISAM ;

--
DROP TABLE IF EXISTS `kbp_log_sphinx`;
--
CREATE TABLE IF NOT EXISTS `kbp_log_sphinx` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date_executed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `entry_type` tinyint(3) UNSIGNED NOT NULL,
  `action_type` tinyint(3) UNSIGNED NOT NULL,
  `output` text,
  `exitcode` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `action_type` (`action_type`)
) ENGINE=MyISAM  ;

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
  `by_default` varchar(30) NOT NULL DEFAULT '',
  `own_priv` tinyint(1) NOT NULL DEFAULT '0',
  `check_priv` tinyint(1) NOT NULL DEFAULT '1',
  `status_priv` tinyint(1) NOT NULL DEFAULT '0',
  `what_priv` varchar(50) DEFAULT NULL,
  `extra_priv` varchar(255) DEFAULT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM ;

--
INSERT INTO `kbp_priv_module` (`id`, `parent_id`, `parent_setting_id`, `module_name`, `menu_name`, `use_in_sub_menu`, `by_default`, `own_priv`, `check_priv`, `status_priv`, `what_priv`, `extra_priv`, `sort_order`, `active`) VALUES
(0, 0, 0, 'all', '', 'NO', '', 0, 1, 0, NULL, NULL, 0, 1),
(1, 0, 0, 'users', 'Users', 'NO', 'user', 0, 1, 0, NULL, NULL, 29, 1),
(3, 0, 0, 'setting', 'Settings', 'NO', 'public_setting/kbc_setting', 0, 1, 0, NULL, NULL, 200, 1),
(100, 0, 3, 'knowledgebase', 'KnowledgeBase', 'NO', 'kb_entry', 0, 1, 0, NULL, NULL, 5, 1),
(12, 1, 0, 'priv', 'Privileges', 'NO', '', 0, 1, 0, NULL, NULL, 4, 1),
(101, 100, 0, 'kb_entry', 'Questions', 'NO', '', 1, 1, 0, NULL, 'draft', 1, 1),
(102, 100, 0, 'kb_category', 'Categories', 'NO', '', 0, 1, 0, NULL, NULL, 15, 1),
(104, 100, 0, 'kb_comment', 'Comments', 'NO', '', 2, 1, 0, NULL, NULL, 6, 1),
(105, 100, 0, 'kb_glossary', 'Glossary', 'NO', '', 0, 1, 0, NULL, NULL, 10, 1),
(131, 3, 3, 'admin_setting', 'Admin', 'NO', '', 0, 1, 0, 'select,update', NULL, 1, 1),
(10, 1, 0, 'user', 'Users', 'NO', '', 1, 1, 0, NULL, 'self_login', 1, 1),
(8, 0, 0, 'feedback', 'Feedback', 'NO', 'feedback', 0, 1, 0, NULL, NULL, 9, 1),
(130, 3, 0, 'public_setting', 'Public Area', 'NO', 'kbc_setting', 0, 1, 0, 'select,update', NULL, 2, 1),
(108, 100, 0, 'kb_rate', 'Rating Comments', 'NO', '', 2, 1, 0, NULL, NULL, 7, 1),
(200, 0, 3, 'file', 'Files', 'NO', 'file_entry', 0, 1, 0, NULL, NULL, 6, 1),
(202, 200, 0, 'file_category', 'Categories', 'NO', '', 0, 1, 0, NULL, NULL, 7, 1),
(1342, 134, 0, 'letter_template', 'Letter Template', NULL, '', 0, 0, 0, 'select,update', NULL, 3, 1),
(14, 1, 0, 'role', 'Roles', 'NO', '', 0, 1, 0, NULL, NULL, 3, 1),
(2, 0, 0, 'log', 'Logs', 'NO', 'cron_log', 0, 1, 0, 'select', NULL, 201, 1),
(201, 200, 0, 'file_entry', 'Files', 'NO', '', 1, 1, 0, NULL, 'draft', 1, 1),
(134, 3, 0, 'email_setting', 'Email', NULL, 'email_setting', 0, 1, 0, 'select,update', NULL, 11, 1),
(5, 0, 0, 'account', 'My Account', 'NO', 'account_user', 0, 0, 0, NULL, NULL, 220, 0),
(204, 200, 0, 'file_rule', 'Local Files Rules', 'NO', '', 0, 1, 0, NULL, NULL, 5, 1),
(61, 6, 0, 'export_kb', 'Export KB', 'NO', '', 0, 1, 0, NULL, NULL, 3, 1),
(80, 8, 0, 'feedback', 'Feedback', 'NO', '', 0, 1, 0, NULL, NULL, 1, 1),
(205, 200, 0, 'file_bulk', 'Bulk Actions', 'NO', '', 0, 1, 0, 'insert', 'draft', 4, 1),
(43, 4, 0, 'help_about', 'About', 'NO', '', 0, 0, 0, NULL, NULL, 10, 1),
(44, 4, 0, 'help_licence', 'Licence', 'NO', '', 0, 0, 0, 'select', NULL, 11, 0),
(41, 4, 0, 'help', 'Help', 'NO', '', 0, 0, 0, NULL, NULL, 1, 1),
(42, 4, 0, 'help_faq', 'FAQ', 'NO', '', 0, 0, 0, NULL, NULL, 2, 0),
(136, 3, 0, 'backup', 'Backups', 'NO', '', 0, 1, 0, 'select,update', NULL, 20, 0),
(2202, 220, 0, 'list_tool', 'Lists', 'NO', '', 0, 1, 0, NULL, NULL, 3, 1),
(11, 1, 0, 'company', 'Companies', 'NO', '', 0, 1, 0, NULL, NULL, 2, 1),
(107, 100, 0, 'article_template', 'Article Template', 'NO', '', 0, 1, 0, NULL, NULL, 16, 1),
(7, 0, 0, 'import', 'Import', 'NO', 'import_user', 0, 1, 0, 'insert', NULL, 190, 1),
(72, 7, 0, 'import_article', 'Import Articles', 'NO', '', 0, 1, 0, 'insert', NULL, 2, 1),
(71, 7, 0, 'import_user', 'Import Users', 'NO', '', 0, 1, 0, 'insert', NULL, 1, 1),
(74, 7, 0, 'kb_entry', 'Articles', 'NO', '', 0, 2, 0, NULL, NULL, 10, 1),
(73, 7, 0, 'user', 'User', 'NO', '', 0, 2, 0, NULL, NULL, 9, 1),
(79, 7, 0, 'spacer', '7', 'NO', '', 0, 0, 0, NULL, NULL, 8, 1),
(9, 0, 0, 'home', 'Home', 'NO', 'home', 0, 1, 0, 'select', NULL, 1, 1),
(90, 9, 0, 'home', 'Dashboard', 'NO', '', 0, 0, 0, NULL, NULL, 1, 1),
(150, 3, 0, 'licence_setting', 'Licence', 'NO', '', 0, 1, 0, 'select,update', NULL, 30, 1),
(45, 4, 0, 'help_request', 'Support Request', 'NO', '', 0, 0, 0, NULL, NULL, 5, 0),
(400, 0, 0, 'report', 'Reports', 'NO', 'report_usage', 0, 1, 0, 'select', NULL, 30, 1),
(81, 8, 0, 'kb_comment', 'Comments', 'NO', '', 0, 2, 0, NULL, NULL, 5, 1),
(4, 0, 0, 'help', 'Help', 'NO', 'help', 0, 0, 0, NULL, NULL, 220, 1),
(51, 5, 0, 'account_user', 'My Account', 'NO', '', 0, 0, 0, NULL, NULL, 1, 1),
(52, 5, 0, 'account_setting', 'Settings', 'NO', '', 0, 0, 0, NULL, NULL, 10, 1),
(82, 8, 0, 'kb_rate', 'Rating Comments', 'NO', '', 0, 2, 0, NULL, NULL, 4, 1),
(98, 0, 0, 'trash', 'Trash', NULL, '', 0, 1, 0, 'select,update,delete', NULL, 999, 1),
(75, 7, 0, 'import_glossary', 'Import Glossary', 'NO', '', 0, 1, 0, 'insert', NULL, 3, 1),
(21, 2, 0, 'cron_log', 'Cron Logs', 'NO', '', 0, 1, 0, 'select', NULL, 1, 1),
(300, 0, 0, 'news', 'News', 'NO', 'news_entry', 0, 1, 0, NULL, NULL, 2, 1),
(301, 300, 0, 'news_entry', 'News', 'NO', '', 0, 1, 0, NULL, NULL, 1, 1),
(2203, 220, 0, 'field_tool', 'Custom Fields', 'NO', 'ft_article', 0, 1, 0, NULL, NULL, 7, 1),
(402, 400, 0, 'report_usage', 'Usage', 'NO', '', 0, 1, 0, 'select', NULL, 2, 1),
(403, 400, 0, 'report_stat', 'Stat', 'NO', 'rs_article', 0, 1, 0, 'select', NULL, 5, 1),
(4032, 403, 0, 'rs_article', 'Articles', 'NO', '', 0, 0, 0, NULL, NULL, 2, 1),
(4033, 403, 0, 'rs_file', 'Files', 'NO', '', 0, 0, 0, NULL, NULL, 3, 1),
(4034, 403, 0, 'rs_user', 'Users', 'NO', '', 0, 0, 0, NULL, NULL, 10, 1),
(4031, 403, 0, 'rs_summary', 'Summary', 'NO', '', 0, 0, 0, NULL, NULL, 1, 0),
(53, 5, 0, 'account_subsc', 'Subscriptions', 'NO', '', 0, 0, 0, NULL, NULL, 2, 1),
(22, 2, 0, 'login_log', 'Login Logs', 'NO', '', 0, 1, 0, 'select', NULL, 6, 1),
(6, 0, 0, 'export', 'Export', 'NO', 'export_kb2', 0, 1, 0, NULL, NULL, 191, 1),
(24, 2, 0, 'search_log', 'Search Log', 'NO', '', 0, 1, 0, 'select', NULL, 8, 1),
(23, 2, 0, 'mail_pool', 'Mail Pool', 'NO', '', 0, 1, 0, 'select', NULL, 3, 1),
(4036, 403, 0, 'rs_news', 'News', 'NO', '', 0, 0, 0, NULL, NULL, 4, 1),
(4035, 403, 0, 'rs_feedback', 'Feedback', 'NO', '', 0, 0, 0, NULL, NULL, 7, 1),
(4037, 403, 0, 'rs_subscriber', 'Subscribers', 'NO', '', 0, 0, 0, NULL, NULL, 11, 0),
(76, 7, 0, 'kb_glossary', 'Glossary', 'NO', '', 0, 2, 0, NULL, NULL, 11, 1),
(140, 3, 0, 'plugin_setting', 'Plugins', 'NO', 'export_setting', 0, 1, 0, 'select,update', NULL, 29, 1),
(91, 9, 0, 'kbpreport', 'Setup Report', 'NO', '', 0, 1, 0, 'select,update', NULL, 3, 1),
(15, 1, 0, 'ban', 'Ban', 'NO', '', 0, 1, 0, NULL, NULL, 6, 1),
(160, 161, 0, 'ldap_setting', 'LDAP', 'NO', '', 0, 1, 0, 'select,update', NULL, 2, 1),
(500, 0, 3, 'trouble', 'Troubleshooters', 'NO', 'trouble_entry', 0, 1, 0, NULL, NULL, 7, 0),
(501, 500, 0, 'trouble_entry', 'Questions', 'NO', '', 1, 1, 0, NULL, NULL, 1, 1),
(502, 500, 0, 'trouble_category', 'Categories', 'NO', '', 0, 1, 0, NULL, NULL, 15, 1),
(504, 500, 0, 'trouble_comment', 'Comments', 'NO', '', 2, 1, 0, NULL, NULL, 3, 1),
(505, 500, 0, 'trouble_rate', 'Rating Comments', 'NO', '', 2, 1, 0, NULL, NULL, 4, 1),
(506, 500, 0, 'trouble_template', 'Templates', 'NO', '', 0, 1, 0, NULL, NULL, 16, 1),
(22032, 2203, 0, 'ft_file', 'Files', 'NO', '', 0, 0, 0, NULL, NULL, 2, 1),
(22031, 2203, 0, 'ft_article', 'Articles', 'NO', '', 0, 0, 0, NULL, NULL, 1, 1),
(1301, 130, 0, 'kbc_setting', 'Common', 'NO', '', 0, 0, 0, NULL, NULL, 1, 1),
(1302, 130, 0, 'kba_setting', 'Articles', 'NO', '', 0, 0, 0, NULL, NULL, 2, 1),
(1303, 130, 0, 'kbf_setting', 'Files', 'NO', '', 0, 0, 0, NULL, NULL, 3, 1),
(1306, 130, 0, 'kbt_setting', 'Troubleshooters', 'NO', '', 0, 0, 0, NULL, NULL, 4, 0),
(4038, 403, 0, 'rs_search', 'Search', 'NO', '', 0, 0, 0, NULL, NULL, 14, 1),
(2204, 220, 0, 'tag_tool', 'Tags', 'NO', '', 0, 1, 0, NULL, NULL, 5, 1),
(1341, 134, 0, 'email_setting', 'Email', NULL, '', 0, 0, 0, 'select,update', NULL, 1, 1),
(62, 6, 0, 'export_kb2', 'Export KB to HTML', 'NO', '', 0, 1, 0, NULL, NULL, 1, 1),
(99, 99, 0, 'trash', 'Trash', NULL, '', 0, 1, 0, 'select,update,delete', NULL, 1, 1),
(220, 0, 0, 'tool', 'Tools', 'NO', 'tool', 0, 1, 0, NULL, NULL, 199, 1),
(2205, 220, 0, 'trigger', 'Triggers', 'NO', 'tr_article', 0, 1, 0, NULL, NULL, 8, 0),
(2206, 220, 0, 'automation', 'Automations', 'NO', 'am_article', 0, 1, 0, NULL, NULL, 9, 1),
(22051, 2205, 0, 'tr_article', 'Articles', 'NO', '', 0, 0, 0, NULL, NULL, 1, 1),
(22061, 2206, 0, 'am_article', 'Articles', 'NO', '', 0, 0, 0, NULL, NULL, 1, 1),
(2201, 220, 0, 'tool', 'Tools', 'NO', '', 0, 0, 0, NULL, NULL, 1, 1),
(103, 100, 0, 'kb_draft', 'Drfats', 'NO', '', 1, 1, 0, 'select,insert,update,delete', NULL, 2, 1),
(63, 6, 0, 'export_article', 'Export Articles', 'NO', '', 0, 1, 0, NULL, NULL, 2, 1),
(404, 400, 0, 'report_entry', 'Entry Usage', 'NO', '', 0, 1, 0, 'select', NULL, 3, 1),
(106, 100, 0, 'kb_featured', 'Featured', 'NO', '', 0, 1, 0, 'select,insert,update,delete', NULL, 3, 1),
(2207, 220, 0, 'workflow', 'Workflows', 'NO', 'wf_article', 0, 1, 0, NULL, NULL, 12, 1),
(203, 200, 0, 'file_draft', 'Drfats', 'NO', '', 1, 1, 0, 'select,insert,update,delete', NULL, 2, 1),
(92, 9, 0, 'kbpstat', 'Statistic', 'NO', '', 0, 1, 0, 'select', NULL, 2, 1),
(22062, 2206, 0, 'am_file', 'Files', 'NO', '', 0, 0, 0, NULL, NULL, 2, 1),
(22072, 2207, 0, 'wf_file', 'Files', 'NO', '', 0, 0, 0, NULL, NULL, 2, 1),
(22071, 2207, 0, 'wf_article', 'Articles', 'NO', '', 0, 0, 0, NULL, NULL, 1, 1),
(600, 0, 3, 'forum', 'Forum', 'NO', 'forum_entry', 0, 1, 0, NULL, NULL, 8, 1),
(601, 600, 0, 'forum_entry', 'Topics', 'NO', '', 0, 1, 0, NULL, NULL, 1, 1),
(602, 600, 0, 'forum_category', 'Forums', 'NO', '', 0, 1, 0, NULL, NULL, 4, 1),
(1307, 130, 0, 'kbforum_setting', 'Forum', 'NO', '', 0, 0, 0, '', NULL, 5, 1),
(603, 600, 0, 'ban', 'Banned Users', 'NO', '', 0, 2, 0, NULL, NULL, 5, 1),
(604, 600, 0, 'forum_featured', 'Pinned Topics', 'NO', '', 0, 1, 0, 'select,insert,update,delete', NULL, 3, 1),
(405, 400, 0, 'report_user', 'User Views', 'NO', '', 0, 1, 0, 'select', NULL, 4, 1),
(22033, 2203, 0, 'ft_news', 'News', 'NO', '', 0, 0, 0, NULL, NULL, 3, 1),
(22034, 2203, 0, 'ft_feedback', 'Feedback', 'NO', '', 0, 0, 0, NULL, NULL, 4, 1),
(22035, 2203, 0, 'spacer', '', 'NO', '', 0, 0, 0, NULL, NULL, 5, 1),
(22036, 2203, 0, 'ft_range', 'Field Ranges', 'NO', '', 0, 0, 0, NULL, NULL, 6, 1),
(4039, 403, 0, 'rs_forum', 'Forum', 'NO', '', 0, 0, 0, NULL, NULL, 5, 1),
(22063, 2206, 0, 'am_email', 'Incoming Mail', 'NO', '', 0, 1, 0, NULL, NULL, 3, 1),
(1401, 140, 0, 'export_setting', 'Export', 'NO', '', 0, 0, 0, NULL, NULL, 1, 1),
(1402, 140, 0, 'sphinx_setting', 'Sphinx Search', 'NO', '', 0, 0, 0, NULL, NULL, 2, 1),
(25, 2, 0, 'sphinx_log', 'Sphinx', 'NO', '', 0, 1, 0, 'select', NULL, 9, 1),
(162, 161, 0, 'saml_setting', 'SAML', 'NO', '', 0, 1, 0, 'select,update', NULL, 1, 1),
(161, 3, 0, 'auth_setting', 'Authentication Provider', 'NO', 'saml_setting', 0, 1, 0, 'select', NULL, 22, 1),
(163, 161, 0, 'rauth_setting', 'Remote', 'NO', '', 0, 1, 0, 'select,update', NULL, 3, 1);

--
DROP TABLE IF EXISTS `kbp_setting`;
--
CREATE TABLE IF NOT EXISTS `kbp_setting` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `module_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_module_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `tab_id` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `group_id` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `input_id` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `options` varchar(100) NOT NULL DEFAULT '',
  `setting_key` varchar(255) NOT NULL DEFAULT '',
  `messure` varchar(10) NOT NULL DEFAULT '',
  `range` varchar(255) NOT NULL DEFAULT '',
  `default_value` text NOT NULL,
  `sort_order` float NOT NULL DEFAULT '0',
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `skip_default` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `module_id` (`module_id`)
) ENGINE=MyISAM  ;

--
INSERT INTO `kbp_setting` (`id`, `module_id`, `user_module_id`, `tab_id`, `group_id`, `input_id`, `options`, `setting_key`, `messure`, `range`, `default_value`, `sort_order`, `required`, `skip_default`, `active`) VALUES
(1, 100, 0, 0, 10, 1, '', 'allow_comments', '', '0,1,2', '1', 12, 0, 0, 1),
(2, 100, 0, 0, 12, 4, '', 'allow_rating', '', '', '1', 1, 0, 0, 1),
(4, 100, 0, 0, 3, 1, '', 'num_most_viewed_entries', '', '0,3,5,10,15', '5', 8, 0, 0, 1),
(5, 100, 0, 0, 3, 1, '', 'num_recently_posted_entries', '', '0,3,5,10,15', '5', 7, 0, 0, 1),
(6, 100, 0, 0, 3, 1, '', 'num_entries_per_page', '', '10,15,20', '10', 1, 0, 0, 1),
(7, 2, 0, 0, 8, 1, 'onchange="populateSelect(this.value);"', 'view_format', '', 'default,left,fixed', 'left', 2, 0, 0, 1),
(8, 100, 0, 0, 6, 4, '', 'show_hits', '', '', '1', 7, 0, 0, 1),
(9, 100, 0, 0, 10, 1, '', 'comment_policy', '', '1,2,3', '1', 13, 0, 0, 1),
(10, 2, 0, 0, 1, 2, '', 'site_title', '', '', 'Your Company :: Knowledgebase', 1, 0, 1, 1),
(12, 2, 0, 0, 1, 4, '', 'module_glossary', '', '', '1', 21, 0, 0, 1),
(13, 2, 0, 0, 8, 6, '', 'page_to_load', '', '', 'Default', 1, 0, 0, 1),
(14, 100, 0, 0, 3, 1, '', 'category_sort_order', '', 'name,sort_order', 'sort_order', 10, 0, 0, 0),
(15, 100, 0, 0, 13, 1, '', 'show_send_link', '', '0,1,2', '1', 1, 0, 0, 1),
(16, 100, 0, 0, 3, 1, '', 'show_num_entries', '', '0,1', '1', 10.1, 0, 0, 0),
(17, 2, 0, 0, 1, 4, '', 'show_title_nav', '', '', '1', 10, 0, 0, 1),
(104, 100, 0, 0, 3, 1, '', 'entry_sort_order', '', 'name,sort_order,added_desc,added_asc,updated_desc,updated_asc,hits_desc,hits_asc', 'sort_order', 1, 0, 0, 1),
(19, 2, 0, 0, 1, 2, '', 'nav_title', '', '', 'KB Home', 8, 0, 1, 1),
(20, 1, 0, 0, 11, 2, '', 'file_dir', '', '', '[document_root_parent]/kb_file/', 10, 1, 1, 1),
(21, 1, 0, 0, 11, 4, '', 'file_extract', '', '', '1', 15, 0, 0, 1),
(22, 1, 0, 0, 11, 2, '', 'file_denied_extensions', '', '', 'php,php3,php5,phtml,asp,aspx,ascx,jsp,cfm,cfc,pl,bat,exe,dll,reg,cgi', 13, 0, 0, 1),
(106, 2, 0, 0, 2, 1, '', 'register_captcha', '', 'no,yes', 'yes', 7, 0, 0, 1),
(23, 1, 0, 0, 11, 2, '', 'file_max_filesize', '', '', '2048', 11, 0, 0, 1),
(25, 1, 0, 0, 11, 2, '', 'file_allowed_extensions', '', '', '', 12, 0, 0, 1),
(26, 1, 0, 0, 11, 1, '', 'file_rename_policy', '', 'date_Ymd-His,date_Ymd,date_Y,suffics_3', 'date_Ymd-His', 10, 0, 0, 0),
(27, 200, 0, 0, 3, 1, '', 'num_most_viewed_entries', '', '0,3,5,10,15', '5', 3, 0, 0, 1),
(28, 200, 0, 0, 3, 1, '', 'num_recently_posted_entries', '', '0,3,5,10,15', '5', 2, 0, 0, 1),
(29, 200, 0, 0, 3, 1, '', 'num_entries_per_page', '', '10,15,20', '10', 1, 0, 0, 1),
(30, 200, 0, 0, 3, 1, '', 'category_sort_order', '', 'name,sort_order', 'sort_order', 4, 0, 0, 0),
(31, 200, 0, 0, 3, 1, '', 'show_num_entries', '', '0,1', '1', 5, 0, 0, 0),
(34, 2, 0, 0, 1, 4, '', 'module_file', '', '', '1', 20, 0, 0, 1),
(33, 2, 0, 0, 2, 4, '', 'kb_register_access', '', '', '0', 1, 0, 0, 1),
(35, 2, 0, 0, 2, 1, '', 'private_policy', '', '1,2', '1', 16, 0, 0, 1),
(234, 100, 0, 0, 13, 1, '', 'send_link_captcha', '', 'no,yes,yes_no_reg', 'yes', 3, 0, 0, 1),
(49, 134, 0, 0, 2, 2, '', 'smtp_port', '', '', '25', 7, 0, 0, 1),
(38, 2, 0, 0, 2, 4, '', 'register_policy', '', '', '1', 2, 0, 0, 1),
(40, 134, 0, 0, 2, 1, 'onchange="toggleSMTPSettings(this.value);"', 'mailer', '', 'dinamic', 'mail', 3, 1, 0, 1),
(41, 134, 0, 0, 1, 2, '', 'from_email', '', '', '', 2, 1, 1, 1),
(42, 134, 0, 0, 1, 2, '', 'from_name', '', '', 'Support Team', 3, 0, 0, 1),
(43, 134, 0, 0, 2, 2, '', 'sendmail_path', '', '', '/usr/sbin/sendmail', 4, 0, 0, 1),
(109, 2, 0, 0, 11, 2, 'size="10"', 'contact_attachment', '', '', '1', 12, 0, 0, 1),
(45, 134, 0, 0, 2, 2, '', 'smtp_user', '', '', '', 9, 0, 0, 1),
(46, 134, 0, 0, 2, 5, '', 'smtp_pass', '', '', '', 10, 0, 0, 1),
(47, 134, 0, 0, 2, 2, '', 'smtp_host', '', '', '', 6, 0, 0, 1),
(50, 100, 0, 0, 5, 2, 'size="10"', 'preview_article_limit', '', '', '300', 3, 0, 0, 1),
(51, 100, 0, 0, 5, 4, '', 'preview_show_comments', '', '', '1', 8, 0, 0, 1),
(52, 100, 0, 0, 5, 4, '', 'preview_show_rating', '', '', '0', 7, 0, 0, 1),
(53, 100, 0, 0, 5, 4, '', 'preview_show_hits', '', '', '0', 10, 0, 0, 1),
(54, 100, 0, 0, 5, 4, '', 'preview_show_date', '', '', '1', 5, 0, 0, 1),
(55, 100, 0, 0, 6, 4, '', 'show_author', '', '', '0', 1.5, 0, 0, 1),
(56, 134, 0, 0, 1, 2, '', 'from_mailer', '', '', 'KBMailer', 1, 0, 0, 1),
(58, 100, 0, 0, 3, 1, '', 'num_entries_category', '', '0,all,3,5,10,15,20', '5', 8.2, 0, 0, 1),
(59, 2, 0, 0, 7, 2, '', 'rss_title', '', '', 'Knowledgebase RSS', 2, 0, 1, 1),
(60, 2, 0, 0, 7, 3, 'rows="2" style="width: 100%"', 'rss_description', '', '', '', 3, 0, 1, 1),
(61, 2, 0, 0, 7, 1, '', 'rss_generate', '', 'none,one,top', 'one', 1, 0, 0, 1),
(62, 2, 0, 0, 1, 3, 'rows="2" style="width: 100%"', 'site_keywords', '', '', '', 2, 0, 1, 1),
(63, 2, 0, 0, 1, 3, 'rows="2" style="width: 100%"', 'site_description', '', '', '', 3, 0, 1, 1),
(64, 100, 0, 0, 3, 1, '', 'num_category_cols', '', '0,1,2,3,4,5', '3', 10.2, 0, 0, 1),
(65, 200, 0, 0, 3, 1, '', 'num_category_cols', '', '0,1,2,3,4,5', '3', 6, 0, 0, 1),
(66, 2, 0, 0, 2, 4, '', 'register_approval', '', '', '0', 3, 0, 0, 1),
(105, 100, 0, 0, 10, 1, '', 'comment_captcha', '', 'no,yes,yes_no_reg', 'yes_no_reg', 12.1, 0, 0, 1),
(101, 2, 0, 0, 1, 2, '', 'header_title', '', '', 'Knowledgebase', 7, 0, 1, 1),
(102, 2, 0, 0, 11, 1, '', 'allow_contact', '', '0,1,2', '1', 11.2, 0, 0, 1),
(103, 2, 0, 0, 8, 1, '', 'view_template', '', '1', 'default', 3, 0, 0, 1),
(107, 2, 0, 0, 11, 1, '', 'contact_captcha', '', 'no,yes,yes_no_reg', 'no', 11.3, 0, 0, 1),
(111, 2, 0, 0, 2, 1, '', 'register_user_priv', '', 'dinamic', '0', 5, 0, 0, 1),
(112, 2, 0, 0, 2, 1, '', 'register_user_role', '', 'dinamic', '0', 6, 0, 0, 1),
(110, 2, 0, 0, 11, 4, '', 'contact_attachment_email', '', '', '0', 12.8, 0, 0, 1),
(114, 100, 0, 0, 6, 4, '', 'show_print_link', '', '', '1', 2, 0, 0, 1),
(115, 100, 0, 0, 3, 2, 'style="width: 100%"', 'entry_prefix_pattern', '', '', '', 16, 0, 0, 1),
(116, 100, 0, 0, 3, 2, 'size="10"', 'entry_id_padding', '', '', '', 15.8, 0, 0, 1),
(44, 134, 0, 0, 2, 4, '', 'smtp_auth', '', '', '1', 8, 0, 0, 1),
(118, 200, 0, 0, 3, 1, '', 'entry_sort_order', '', 'filename,name,sort_order,added_desc,added_asc,updated_desc,updated_asc,hits_desc,hits_asc', 'sort_order', 1, 0, 0, 1),
(119, 2, 0, 0, 11, 2, '', 'contact_attachment_ext', '', '', '', 12.5, 0, 0, 1),
(120, 100, 0, 0, 6, 4, '', 'show_entry_block', '', '', '1', 1.2, 0, 0, 1),
(149, 1, 1, 0, 3, 1, '', 'num_entries_per_page_admin', '', '10,20,40', '10', 2, 0, 0, 1),
(122, 1, 1, 0, 3, 2, 'size="10"', 'app_width', '', '', '980px', 1, 1, 0, 1),
(123, 1, 0, 0, 2, 2, 'size="10"', 'auth_expired', '', '', '60', 1, 0, 0, 1),
(130, 2, 0, 0, 1, 7, '', 'nav_extra', '', '', '', 11, 0, 1, 1),
(126, 2, 0, 0, 1, 1, '', 'mod_rewrite', '', '1,2,3,9', '1', 25, 0, 0, 1),
(127, 1, 0, 0, 2, 1, '', 'auth_captcha', '', 'no,yes', 'no', 3, 0, 0, 1),
(128, 1, 0, 0, 5, 2, 'style="width: 100%"', 'html_editor_upload_dir', '', '', '[document_root]/kb_upload/', 1, 1, 1, 1),
(131, 2, 0, 0, 2, 1, '', 'login_policy', '', '1,2,9', '1', 11, 0, 0, 1),
(132, 2, 0, 0, 8, 4, '', 'view_header', '', '', '1', 7, 0, 0, 1),
(133, 2, 0, 0, 1, 3, 'rows="2" style="width: 100%"', 'footer_info', '', '', '', 9, 0, 1, 0),
(134, 150, 0, 0, 1, 2, '', 'license_key', '', '', '', 1, 1, 1, 1),
(135, 2, 0, 0, 8, 1, '', 'view_menu_type', '', '1', 'tree', 5, 0, 0, 1),
(136, 1, 0, 0, 6, 2, 'style="width: 100%"', 'cache_dir', '', '', '[document_root_parent]/kb_cache/', 2, 1, 1, 0),
(137, 100, 0, 0, 3, 1, '', 'nav_prev_next', '', 'yes,yes_no_others,no', 'yes', 8.4, 0, 0, 1),
(138, 134, 0, 0, 1, 2, '', 'noreply_email', '', '', '[noreply_email]', 4, 1, 0, 1),
(139, 150, 0, 0, 1, 2, '', 'license_key2', '', '', '', 2, 0, 0, 1),
(140, 150, 0, 0, 1, 2, '', 'license_key3', '', '', '', 3, 0, 0, 1),
(141, 1, 0, 0, 11, 2, '', 'file_extract_pdf', '', '', 'off', 18, 0, 1, 1),
(142, 100, 0, 0, 6, 4, '', 'show_private_block', '', '', '1', 1.3, 0, 0, 1),
(143, 100, 0, 0, 10, 1, '', 'num_comments_per_page', '', '10,20,30,40,50', '50', 13.3, 0, 0, 1),
(144, 100, 0, 0, 10, 4, '', 'comments_entry_page', '', '', '0', 13.4, 0, 0, 1),
(146, 1, 1, 0, 3, 1, '', 'article_sort_order', '', 'name,added_desc,added_asc,updated_desc,updated_asc', 'updated_desc', 5, 0, 0, 1),
(147, 1, 1, 0, 3, 1, '', 'file_sort_order', '', 'filename,added_desc,added_asc,updated_desc,updated_asc', 'updated_desc', 7, 0, 0, 1),
(150, 0, 1, 0, 3, 1, '', 'home_page', '', '1,2,4', '1', 2, 0, 0, 0),
(148, 100, 0, 0, 14, 4, '', 'allow_rating_comment', '', '', '1', 2, 0, 0, 1),
(151, 2, 0, 0, 14, 4, '', 'show_news_link', '', '', '0', 2, 0, 0, 1),
(152, 150, 0, 0, 1, 2, '', 'license_key4', '', '', '', 3, 0, 0, 1),
(153, 2, 0, 0, 14, 1, '', 'num_news_entries', '', '0,1,2,3,5', '1', 5, 0, 0, 1),
(154, 2, 0, 0, 14, 4, '', 'module_news', '', '', '1', 1, 0, 0, 1),
(155, 134, 0, 0, 2, 1, '', 'smtp_secure', '', 'none,ssl,tls', 'none', 6.8, 0, 0, 1),
(156, 1, 0, 0, 11, 2, '', 'file_extract_doc', '', '', 'off', 19, 0, 1, 1),
(160, 134, 0, 0, 1, 2, '', 'admin_email', '', '', '', 7, 1, 1, 1),
(157, 100, 0, 0, 6, 4, '', 'show_pdf_link', '', '', '0', 3, 0, 0, 1),
(158, 2, 0, 0, 15, 1, '', 'allow_subscribe_news', '', '0,2,3', '2', 1, 0, 0, 1),
(162, 0, 0, 0, 5, 4, '', 'hpb_article', '', '', '1', 2, 0, 1, 1),
(161, 100, 0, 0, 6, 2, '', 'show_author_format', '', '', '[last_name] [short_first_name].', 1.6, 0, 0, 1),
(163, 0, 0, 0, 5, 4, '', 'hpb_file', '', '', '1', 2, 0, 1, 1),
(164, 0, 0, 0, 5, 4, '', 'hpb_user', '', '', '1', 2, 0, 1, 1),
(165, 0, 0, 0, 5, 4, '', 'hpb_rating', '', '', '1', 2, 0, 1, 1),
(166, 0, 0, 0, 5, 4, '', 'hpb_comment', '', '', '1', 2, 0, 1, 1),
(167, 2, 0, 0, 15, 1, '', 'allow_subscribe_entry', '', '0,2,3', '2', 2, 0, 0, 1),
(168, 1, 0, 0, 8, 4, '', 'cron_mail_critical', '', '', '1', 1, 0, 0, 1),
(169, 200, 0, 0, 5, 4, '', 'preview_show_hits', '', '', '1', 4, 0, 0, 1),
(170, 134, 0, 0, 3, 2, '', 'mass_mail_send_per_hour', '', '', '250', 1, 0, 0, 1),
(171, 100, 0, 0, 3, 1, '', 'entry_published', '', '0,1', '1', 8.1, 0, 0, 1),
(172, 100, 0, 0, 13, 4, '', 'show_send_link_article', '', '', '0', 2, 0, 0, 1),
(173, 2, 0, 0, 16, 1, '', 'search_default', '', 'all,article,file', 'all', 1, 0, 0, 1),
(174, 2, 0, 0, 16, 4, '', 'search_disable_all', '', '', '0', 3, 0, 0, 0),
(175, 1, 0, 0, 10, 2, '', 'entry_history_max', '', '', 'all', 1, 0, 0, 1),
(176, 100, 0, 0, 12, 1, '', 'rating_type', '', '1,2', '1', 3, 0, 0, 1),
(177, 100, 0, 0, 10, 1, '', 'allow_subscribe_comment', '', '0,2', '2', 13.1, 0, 0, 1),
(179, 140, 0, 0, 1, 2, '', 'plugin_export_key', '', '', 'demo', 1, 0, 1, 1),
(180, 140, 0, 0, 3, 2, '', 'plugin_htmldoc_path', '', '', 'off', 2, 0, 0, 1),
(181, 140, 0, 0, 1, 1, '', 'show_pdf_category_link', '', '0,1,2,3', '0', 3, 0, 0, 1),
(182, 100, 0, 0, 6, 1, '', 'article_block_position', '', 'right,bottom', 'bottom', 1, 0, 0, 1),
(183, 1, 1, 0, 10, 2, '', 'entry_autosave', '', '', '3', 3, 0, 0, 1),
(184, 1, 0, 0, 11, 1, '', 'directory_missed_file_policy', '', 'dinamic', 'none', 25, 0, 0, 1),
(185, 1, 0, 0, 2, 4, '', 'account_password_old', '', '', '1', 7, 0, 0, 1),
(186, 1, 0, 0, 11, 2, '', 'file_param_pdf', '', '', '', 18.1, 0, 1, 0),
(187, 1, 0, 0, 11, 2, '', 'file_param_doc', '', '', '', 19.1, 0, 1, 0),
(188, 100, 0, 0, 6, 4, '', 'show_pdf_link_entry_info', '', '', '1', 3.1, 0, 0, 1),
(189, 140, 0, 0, 1, 2, '', 'htmldoc_fontsize', '', '', '10', 5, 0, 0, 1),
(190, 140, 0, 0, 1, 1, '', 'htmldoc_bodyfont', '', 'Arial,Courier,Helvetica,Monospace,Sans Mono,Sans,Serif,Times', 'Sans', 4, 0, 0, 1),
(191, 100, 0, 0, 10, 2, '', 'comments_author_format', '', '', '[username]', 13.4, 0, 0, 1),
(192, 2, 0, 0, 1, 7, '', 'menu_extra', '', '', '', 13, 0, 0, 1),
(193, 2, 0, 0, 3, 4, '', 'module_trouble', '', '', '1', 20, 0, 0, 0),
(194, 2, 0, 0, 16, 4, '', 'search_suggest', '', '', '1', 5, 0, 0, 1),
(195, 2, 0, 0, 11, 4, '', 'contact_quick_responce', '', '', '1', 11.3, 0, 0, 1),
(196, 140, 0, 0, 1, 2, '', 'htmldoc_params', '', '', '', 5, 0, 0, 0),
(204, 160, 0, 0, 2, 4, '', 'ldap_use_v3', '', '', '1', 8, 0, 0, 1),
(203, 160, 0, 0, 2, 4, '', 'ldap_use_tls', '', '', '0', 7, 0, 0, 1),
(202, 160, 0, 0, 2, 4, '', 'ldap_use_ssl', '', '', '0', 6, 0, 0, 1),
(201, 160, 0, 0, 2, 5, '', 'ldap_connect_password', '', '', '', 5, 0, 0, 1),
(200, 160, 0, 0, 2, 2, '', 'ldap_connect_dn', '', '', '', 4, 0, 0, 1),
(199, 160, 0, 0, 2, 2, '', 'ldap_base_dn', '', '', '', 3, 1, 0, 1),
(198, 160, 0, 0, 2, 2, '', 'ldap_port', '', '', '389', 2, 1, 0, 1),
(197, 160, 0, 0, 2, 2, '', 'ldap_host', '', '', '', 1, 1, 0, 1),
(206, 160, 0, 0, 3, 1, '', 'remote_auth_type', '', '1', '1', 1, 0, 0, 0),
(207, 160, 0, 0, 6, 1, '', 'remote_auth_auto', '', '0,1,2', '0', 1, 0, 0, 0),
(208, 160, 0, 0, 3, 1, '', 'remote_auth_area', '', '1,2', '2', 3, 0, 0, 0),
(209, 160, 0, 0, 3, 1, '', 'remote_auth_local', '', '0,1,2', '0', 4, 0, 0, 1),
(210, 160, 0, 0, 3, 2, '', 'remote_auth_local_ip', '', '', '', 5, 0, 0, 1),
(211, 160, 0, 0, 3, 2, '', 'remote_auth_refresh_time', '', '', '1', 6, 0, 0, 1),
(212, 160, 0, 0, 3, 2, '', 'remote_auth_restore_password_link', '', '', '', 7, 0, 0, 1),
(213, 0, 0, 0, 5, 4, '', 'report_chart', '', '', 'line', 1, 0, 0, 1),
(214, 500, 0, 0, 2, 1, '', 'num_most_viewed_entries', '', '0,3,5,10,15', '5', 3, 0, 0, 1),
(215, 500, 0, 0, 2, 1, '', 'num_recently_posted_entries', '', '0,3,5,10,15', '5', 2, 0, 0, 1),
(216, 500, 0, 0, 2, 1, '', 'num_entries_per_page', '', '10,15,20', '10', 1, 0, 0, 1),
(217, 500, 0, 0, 2, 1, '', 'category_sort_order', '', 'name,sort_order', 'sort_order', 4, 0, 0, 0),
(218, 500, 0, 0, 2, 1, '', 'show_num_entries', '', '0,1', '1', 5, 0, 0, 0),
(219, 500, 0, 0, 2, 1, '', 'num_category_cols', '', '0,1,2,3,4,5', '3', 6, 0, 0, 1),
(220, 500, 0, 0, 2, 1, '', 'entry_sort_order', '', 'name,sort_order,added_desc,added_asc,updated_desc,updated_asc,hits_desc,hits_asc', 'sort_order', 1, 0, 0, 1),
(221, 500, 0, 0, 3, 1, '', 'allow_comments', '', '0,1,2', '1', 12, 0, 0, 1),
(222, 500, 0, 0, 4, 4, '', 'allow_rating', '', '', '1', 1, 0, 0, 1),
(223, 500, 0, 0, 4, 4, '', 'allow_rating_comment', '', '', '1', 2, 0, 0, 1),
(225, 500, 0, 0, 5, 4, '', 'preview_show_hits', '', '', '1', 4, 0, 0, 1),
(227, 500, 0, 0, 5, 4, '', 'preview_show_rating', '', '', '0', 7, 0, 0, 1),
(228, 500, 0, 0, 5, 4, '', 'preview_show_hits', '', '', '0', 10, 0, 0, 1),
(229, 500, 0, 0, 5, 4, '', 'preview_show_date', '', '', '1', 5, 0, 0, 1),
(230, 500, 0, 0, 5, 2, 'size="10"', 'preview_trouble_limit', '', '', '300', 1, 0, 0, 1),
(231, 1, 0, 0, 11, 2, '', 'file_extract_doc2', '', '', 'off', 20, 0, 1, 1),
(232, 160, 0, 0, 1, 4, '', 'remote_auth', '', '', '0', 1, 0, 0, 1),
(233, 163, 0, 0, 1, 4, '', 'remote_auth_script', '', '', '0', 1, 0, 0, 1),
(235, 1, 0, 0, 12, 1, '', 'lang', '', 'dinamic', 'en', 1, 0, 1, 1),
(236, 10, 0, 0, 1, 0, '', 'page_to_load_tmpl', '', '', '', 1, 0, 1, 1),
(237, 1, 0, 0, 10, 1, '', 'entry_default_cat', '', 'dinamic', '', 1, 0, 0, 0),
(238, 2, 0, 0, 19, 4, '', 'show_share_link', '', '', '1', 13, 0, 0, 1),
(239, 2, 0, 0, 19, 7, '', 'item_share_link', '', '', 'Twitter | http://twitter.com/intent/tweet?url=[url] | {client_href}images/icons/socialmediaicons/[size]/twitter.png\r\nFacebook | http://facebook.com/sharer.php?u=[url] | {client_href}images/icons/socialmediaicons/[size]/facebook.png\r\nGoogle Plus | https://plus.google.com/share?url=[url] | {client_href}images/icons/socialmediaicons/[size]/googleplus.png\r\nLinkedIn | https://www.linkedin.com/cws/share?url=[url] | {client_href}images/icons/socialmediaicons/[size]/linkedin.png', 14, 0, 0, 1),
(240, 1, 0, 0, 10, 1, '', 'article_default_category', '', 'dinamic', 'none', 14, 0, 0, 1),
(241, 1, 0, 0, 11, 1, '', 'file_default_category', '', 'dinamic', 'none', 26, 0, 0, 1),
(243, 100, 0, 0, 3, 4, '', 'article_table_content', '', '', '0', 17, 0, 0, 0),
(242, 2, 0, 0, 18, 4, '', 'show_tags', '', '', '1', 2, 0, 0, 1),
(245, 10, 0, 0, 1, 2, '', 'header_background', '', '', '', 1, 0, 1, 1),
(246, 10, 0, 0, 1, 2, '', 'menu_background', '', '', '', 5, 0, 1, 1),
(247, 10, 0, 0, 1, 2, '', 'footer_background', '', '', '', 4, 0, 1, 0),
(248, 10, 0, 0, 1, 2, '', 'menu_item_background', '', '', '', 6, 0, 1, 1),
(249, 10, 0, 0, 1, 2, '', 'menu_item_background_selected', '', '', '', 6.1, 0, 1, 1),
(250, 10, 0, 0, 1, 2, '', 'header_color', '', '', '', 2, 0, 1, 1),
(251, 10, 0, 0, 1, 2, '', 'menu_item_color', '', '', '', 8, 0, 1, 1),
(252, 10, 0, 0, 1, 2, '', 'menu_item_color_selected', '', '', '', 8.1, 0, 1, 1),
(253, 10, 0, 0, 1, 2, '', 'menu_item_bordercolor', '', '', '', 9, 0, 1, 1),
(254, 10, 0, 0, 1, 2, '', 'menu_item_bordercolor_selected', '', '', '', 9.1, 0, 1, 1),
(255, 10, 0, 0, 1, 2, '', 'login_color', '', '', '', 3, 0, 1, 1),
(256, 2, 0, 0, 16, 4, '', 'search_article_id', '', '', '1', 3, 0, 0, 1),
(257, 163, 0, 0, 1, 2, '', 'remote_auth_script_path', '', '', 'default', 3, 0, 0, 1),
(258, 160, 0, 0, 3, 1, '', 'remote_auth_update_account', '', '0,1,2', '2', 8, 0, 0, 1),
(259, 1, 0, 0, 2, 1, '', 'password_captcha', '', 'no,yes', 'yes', 4, 0, 0, 1),
(260, 10, 0, 0, 1, 2, '', 'left_menu_width', '', '', '', 13, 0, 1, 1),
(265, 160, 0, 0, 4, 2, '', 'remote_auth_email_template', '', '', '', 5, 0, 1, 0),
(261, 160, 0, 0, 4, 2, '', 'remote_auth_map_fname', '', '', 'givenName', 1, 1, 0, 1),
(262, 160, 0, 0, 4, 2, '', 'remote_auth_map_lname', '', '', 'sn', 2, 1, 0, 1),
(263, 160, 0, 0, 4, 2, '', 'remote_auth_map_email', '', '', 'mail', 3, 1, 0, 1),
(264, 160, 0, 0, 4, 2, '', 'remote_auth_map_ruid', '', '', 'uid', 0.5, 1, 0, 1),
(266, 160, 0, 0, 5, 2, '', 'ldap_debug_username', '', '', '', 1, 0, 1, 1),
(267, 160, 0, 0, 5, 2, '', 'ldap_debug_password', '', '', '', 2, 0, 1, 1),
(269, 160, 0, 0, 4, 7, 'wrap="off" rows="3"', 'remote_auth_map_role_id', '', '', '', 6, 0, 0, 1),
(268, 160, 0, 0, 4, 7, 'wrap="off" rows="3"', 'remote_auth_map_priv_id', '', '', '', 5, 0, 0, 1),
(270, 160, 0, 0, 6, 2, '', 'remote_auth_auto_script_path', '', '', 'default', 2, 0, 0, 0),
(271, 1, 0, 0, 13, 4, '', 'api_access', '', '', '1', 1, 0, 0, 1),
(272, 10, 0, 0, 1, 2, '', 'menu_item_background_hover', '', '', '', 6.2, 0, 1, 1),
(273, 1, 0, 0, 13, 4, '', 'api_secure', '', '', '1', 1, 0, 0, 1),
(274, 2, 0, 0, 18, 4, '', 'module_tags', '', '', '0', 1, 0, 0, 1),
(275, 0, 0, 0, 5, 4, '', 'home_portlet_order', '', '', '2,3,1|6,7,4,5', 3, 0, 1, 1),
(276, 1, 0, 0, 14, 4, '', 'allow_create_tags', '', '', '1', 1, 0, 0, 1),
(277, 160, 0, 0, 7, 1, '', 'remote_auth_group_type', '', 'static,dynamic', 'dynamic', 1, 0, 0, 1),
(278, 160, 0, 0, 7, 2, '', 'remote_auth_group_attribute', '', '', 'member', 2, 0, 0, 1),
(279, 160, 0, 0, 4, 7, '', 'remote_auth_map_group_to_priv', '', '', '', 5, 0, 0, 1),
(280, 160, 0, 0, 4, 7, '', 'remote_auth_map_group_to_role', '', '', '', 6, 0, 0, 1),
(281, 1, 0, 0, 15, 1, '', 'timezone', '', 'dinamic', 'system', 2, 0, 1, 1),
(287, 10, 0, 0, 2, 2, '', 'menu_color_mobile', '', '', '', 4, 0, 1, 1),
(286, 10, 0, 0, 2, 2, '', 'menu_background_mobile', '', '', '', 3, 0, 1, 1),
(285, 10, 0, 0, 2, 2, '', 'color_mobile', '', '', '', 2, 0, 1, 1),
(284, 10, 0, 0, 2, 2, '', 'background_mobile', '', '', '', 1, 0, 1, 1),
(283, 10, 0, 0, 2, 0, '', 'page_to_load_tmpl_mobile', '', '', '', 1, 0, 1, 1),
(282, 2, 0, 0, 17, 6, '', 'page_to_load_mobile', '', '', 'Default', 1, 0, 0, 1),
(290, 20, 0, 0, 1, 0, '', 'default_sql_automation_article', '', '', 'INSERT INTO {prefix}trigger VALUES\n(NULL,1,2,0,\'outdated_article\',\'\',\'\',2,\'a:2:{i:1;a:2:{s:4:"item";s:6:"status";s:4:"rule";a:2:{i:0;s:2:"is";i:1;s:1:"1";}}i:2;a:2:{s:4:"item";s:12:"date_updated";s:4:"rule";a:3:{i:0;s:4:"more";i:1;s:3:"180";i:2;s:15:"period_old_days";}}}\',\'a:2:{i:1;a:2:{s:4:"item";s:5:"email";s:4:"rule";a:1:{i:0;s:6:"author";}}i:2;a:2:{s:4:"item";s:6:"status";s:4:"rule";a:1:{i:0;s:1:"4";}}}\',\'\',1,0),\n(NULL,1,2,0,\'outdated_article_grouped\',\'\',\'\',2,\'a:2:{i:1;a:2:{s:4:"item";s:6:"status";s:4:"rule";a:2:{i:0;s:2:"is";i:1;s:1:"1";}}i:2;a:2:{s:4:"item";s:12:"date_updated";s:4:"rule";a:3:{i:0;s:4:"more";i:1;s:3:"180";i:2;s:15:"period_old_days";}}}\',\'a:2:{i:1;a:2:{s:4:"item";s:18:"email_user_grouped";s:4:"rule";a:1:{i:0;s:6:"author";}}i:2;a:2:{s:4:"item";s:6:"status";s:4:"rule";a:1:{i:0;s:1:"4";}}}\',\'\',2,0);', 1, 0, 1, 1),
(288, 100, 0, 0, 3, 1, '', 'num_featured_entries', '', '0,1,3,5,10,15', '5', 8, 0, 0, 1),
(289, 2, 0, 0, 8, 8, '', 'header_logo', '', '', '', 8, 0, 1, 1),
(292, 2, 0, 0, 15, 1, 'onchange="toggleSubscriptionTimePicker(this.value, \'news\');"', 'subscribe_news_interval', '', 'hourly,daily', 'daily', 3, 0, 0, 1),
(293, 2, 0, 0, 15, 1, '', 'subscribe_news_time', '', 'dinamic', '00', 4, 0, 0, 1),
(294, 2, 0, 0, 15, 1, 'onchange="toggleSubscriptionTimePicker(this.value, \'entry\');"', 'subscribe_entry_interval', '', 'hourly,daily', 'daily', 5, 0, 0, 1),
(295, 2, 0, 0, 15, 1, '', 'subscribe_entry_time', '', 'dinamic', '00', 6, 0, 0, 1),
(291, 20, 0, 0, 1, 0, '', 'default_sql_workflow_article', '', '', 'INSERT INTO {prefix}trigger VALUES\n(NULL,1,4,0,\'approval_category_admin\',\'\',\'\',2,\'a:1:{i:2;a:2:{s:4:"item";s:15:"privilege_level";s:4:"rule";a:2:{i:0;s:5:"equal";i:1;s:1:"5";}}}\',\'a:1:{i:1;a:3:{s:5:"title";s:0:"";s:4:"item";s:6:"assign";s:4:"rule";a:1:{i:0;s:14:"category_admin";}}}\',\'\',5,1);', 1, 0, 1, 1),
(296, 0, 0, 0, 5, 4, '', 'home_user_portlet_order', '', '', '5,3,4|1,2', 3, 0, 1, 1),
(297, 2, 0, 0, 16, 8, '', 'search_spell_suggest', '', '', '0', 5, 0, 0, 1),
(298, 2, 0, 0, 16, 7, '', 'search_spell_pspell_dic', '', '', '', 6, 0, 0, 1),
(299, 2, 0, 0, 16, 7, '', 'search_spell_custom', '', '', '', 7, 0, 0, 1),
(300, 2, 0, 0, 16, 2, '', 'search_spell_bing_spell_check_key', '', '', '', 8, 0, 1, 1),
(301, 2, 0, 0, 16, 2, '', 'search_spell_bing_spell_check_url', '', '', '', 9, 0, 1, 1),
(302, 2, 0, 0, 16, 2, '', 'search_spell_enchant_provider', '', '', '', 10, 0, 1, 1),
(303, 2, 0, 0, 16, 2, '', 'search_spell_enchant_dic', '', '', '', 11, 0, 1, 1),
(304, 2, 0, 0, 16, 2, '', 'search_spell_bing_autosuggest_key', '', '', '', 11, 0, 1, 1),
(305, 100, 0, 0, 3, 1, '', 'num_featured_entries_cat', '', '0,1,3,5,10,15', '5', 8, 0, 0, 1),
(306, 20, 0, 0, 1, 0, '', 'default_sql_workflow_file', '', '', 'INSERT INTO {prefix}trigger VALUES\n(NULL,2,4,0,\'approval_category_admin\',\'\',\'\',2,\'a:1:{i:2;a:2:{s:4:"item";s:15:"privilege_level";s:4:"rule";a:2:{i:0;s:5:"equal";i:1;s:1:"5";}}}\',\'a:1:{i:1;a:3:{s:5:"title";s:0:"";s:4:"item";s:6:"assign";s:4:"rule";a:1:{i:0;s:14:"category_admin";}}}\',\'\',5,1);', 1, 0, 1, 1),
(307, 600, 0, 0, 2, 1, '', 'num_recently_posted_entries', '', '0,5,10,15,20', '10', 1, 0, 0, 1),
(308, 600, 0, 0, 2, 1, '', 'num_entries_per_page', '', '10,15,20', '10', 2, 0, 0, 1),
(309, 600, 0, 0, 2, 1, '', 'num_comments_per_page', '', '10,20,30,40,50', '50', 3, 0, 0, 1),
(310, 600, 0, 0, 10, 2, '', 'akismet_key', '', '', 'off', 1, 0, 0, 1),
(311, 600, 0, 0, 2, 1, '', 'show_num_entries', '', '0,1', '1', 4, 0, 0, 1),
(312, 600, 0, 0, 5, 1, '', 'allow_subscribe_forum', '', '0,2', '2', 1, 0, 0, 1),
(313, 600, 0, 0, 10, 2, '', 'num_auto_ban', '', '', '2', 2, 0, 0, 1),
(314, 600, 0, 0, 11, 2, '', 'file_max_filesize', '', '', '2048', 3, 0, 0, 1),
(315, 600, 0, 0, 11, 2, '', 'file_allowed_extensions', '', '', 'jpg,jpeg,png,gif,txt,zip,rar,tar.gz,pdf', 2, 0, 0, 1),
(316, 600, 0, 0, 2, 4, '', 'allow_forum_tags', '', '', '1', 5, 0, 0, 1),
(317, 2, 0, 0, 1, 4, '', 'module_forum', '', '', '1', 24, 0, 0, 1),
(318, 100, 0, 0, 6, 4, '', 'show_pool_link', '', '', '1', 2.2, 0, 0, 1),
(319, 140, 0, 0, 2, 2, '', 'plugin_wkhtmltopdf_path', '', '', 'off', 2, 0, 0, 1),
(320, 140, 0, 0, 2, 8, '', 'plugin_export_cover', '', '', '1', 6, 0, 0, 1),
(321, 140, 0, 0, 2, 8, '', 'plugin_export_header', '', '', '0', 7, 0, 0, 1),
(322, 140, 0, 0, 2, 0, '', 'plugin_export_cover_tmpl', '', '', '<div style="text-align:center;"><br /><br /><br /><span style="font-size:20px;">[top_category_title]</span><br /><br /><b>[top_category_description]</b></div>', 1, 0, 0, 1),
(323, 140, 0, 0, 2, 0, '', 'plugin_export_header_tmpl', '', '', '', 1, 0, 0, 1),
(324, 140, 0, 0, 2, 8, '', 'plugin_export_footer', '', '', '1', 8, 0, 0, 1),
(325, 140, 0, 0, 2, 0, '', 'plugin_export_footer_tmpl', '', '', '<hr /><span style="float: left;">[top_category_title]</span><span style="float: right;">[page]</span>', 1, 0, 0, 1),
(327, 2, 0, 0, 17, 8, '', 'header_logo_mobile', '', '', '', 3, 0, 0, 1),
(328, 1, 0, 0, 16, 1, '', 'user_activity_time', '', '1,3,6,12', '6', 1, 0, 0, 1),
(329, 600, 0, 0, 2, 4, '', 'forum_sections', '', '', '1', 6, 0, 0, 1),
(330, 600, 0, 0, 11, 4, '', 'forum_allow_attachment', '', '', '1', 1, 0, 0, 1),
(331, 600, 0, 0, 11, 2, '', 'file_num_per_post', '', '', '5', 4, 0, 0, 1),
(332, 0, 0, 0, 5, 4, '', 'emodule_report', '', '', '1', 1, 0, 1, 1),
(333, 0, 0, 0, 5, 4, '', 'emodule_automation', '', '', '1', 1, 0, 1, 1),
(334, 0, 0, 0, 5, 4, '', 'emodule_workflow', '', '', '1', 1, 0, 1, 1),
(335, 0, 0, 0, 5, 4, '', 'emodule_forum', '', '', '0', 1, 0, 1, 1),
(336, 20, 0, 0, 1, 0, '', 'default_sql_automation_email', '', '', '', 1, 0, 1, 1),
(369, 140, 0, 0, 2, 2, '', 'plugin_wkhtmltopdf_dpi', '', '', '', 3, 0, 0, 1),
(370, 100, 0, 0, 6, 4, '', 'show_comments', '', '', '1', 8, 0, 0, 1),
(371, 162, 0, 0, 4, 2, 'disabled', 'saml_map_remote_id', '', '', 'NameID', 1, 1, 0, 1),
(372, 162, 0, 0, 4, 2, '', 'saml_map_username', '', '', '', 5, 0, 0, 1),
(373, 140, 0, 0, 2, 2, '', 'plugin_wkhtmltopdf_margin_top', '', '', '10', 4, 0, 0, 1),
(337, 141, 0, 0, 1, 4, '', 'sphinx_enabled', '', '', '0', 1, 0, 0, 1),
(338, 141, 0, 0, 2, 2, '', 'sphinx_host', '', '', '127.0.0.1', 1, 1, 0, 1),
(339, 141, 0, 0, 2, 2, '', 'sphinx_port', '', '', '9306', 2, 1, 0, 1),
(340, 141, 0, 0, 2, 2, '', 'sphinx_bin_path', '', '', '', 3, 0, 0, 1),
(341, 141, 0, 0, 2, 2, '', 'sphinx_data_path', '', '', '[cache_dir]/sphinx/', 3, 1, 0, 1),
(342, 141, 0, 0, 1, 4, '', 'sphinx_test_mode', '', '', '0', 2, 0, 0, 1),
(345, 141, 0, 0, 3, 1, 'class="fselect" multiple', 'sphinx_lang', '', 'dinamic', 'en', 3, 1, 0, 1),
(346, 1, 0, 0, 2, 4, '', 'auth_remember', '', '', '60', 2, 0, 0, 1),
(347, 162, 0, 0, 1, 4, '', 'saml_auth', '', '', '0', 1, 0, 0, 1),
(348, 162, 0, 0, 1, 1, '', 'saml_mode', '', '1,2,3', '1', 2, 0, 0, 1),
(349, 162, 0, 0, 2, 2, '', 'saml_name', '', '', '', 1, 1, 0, 1),
(350, 162, 0, 0, 2, 2, '', 'saml_issuer', '', '', '', 2, 1, 0, 1),
(351, 162, 0, 0, 2, 2, '', 'saml_sso_endpoint', '', '', '', 3, 1, 0, 1),
(352, 162, 0, 0, 2, 1, '', 'saml_sso_binding', '', 'redirect,post', 'redirect', 4, 0, 0, 1),
(353, 162, 0, 0, 2, 2, '', 'saml_slo_endpoint', '', '', '', 5, 0, 0, 1),
(354, 162, 0, 0, 2, 1, '', 'saml_slo_binding', '', 'redirect,post', 'redirect', 6, 0, 0, 1),
(355, 162, 0, 0, 2, 7, '', 'saml_idp_certificate', '', '', '', 7, 0, 0, 1),
(356, 162, 0, 0, 3, 2, '', 'saml_refresh_time', '', '', '1', 1, 0, 0, 1),
(357, 162, 0, 0, 3, 1, '', 'saml_update_account', '', '0,1,2', '2', 2, 0, 0, 1),
(358, 162, 0, 0, 4, 2, '', 'saml_map_fname', '', '', 'User.firstName', 2, 1, 0, 1),
(359, 162, 0, 0, 4, 2, '', 'saml_map_lname', '', '', 'User.lastName', 3, 1, 0, 1),
(360, 162, 0, 0, 4, 2, '', 'saml_map_email', '', '', 'User.email', 4, 1, 0, 1),
(361, 162, 0, 0, 4, 7, '', 'saml_map_group_to_priv', '', '', '', 7, 0, 0, 1),
(362, 162, 0, 0, 4, 7, '', 'saml_map_group_to_role', '', '', '', 8, 0, 0, 1),
(363, 162, 0, 0, 5, 9, '', 'saml_metadata', '', '', '', 1, 0, 0, 1),
(364, 162, 0, 0, 5, 7, '', 'saml_sp_certificate', '', '', '', 2, 0, 0, 1),
(365, 162, 0, 0, 5, 8, '', 'saml_sp_private_key', '', '', '', 3, 0, 0, 1),
(366, 1, 0, 0, 2, 1, '', 'password_rotation_freq', '', '0,90,180,365', '0', 5, 0, 0, 1),
(367, 162, 0, 0, 5, 1, '', 'saml_algorithm', '', 'off,rsa-sha1,dsa-sha1,rsa-sha256,rsa-sha384,rsa-sha512', 'off', 4, 0, 0, 1),
(368, 1, 0, 0, 2, 1, '', 'password_rotation_policy', '', '1,2', '1', 6, 0, 0, 1),
(374, 140, 0, 0, 2, 2, '', 'plugin_wkhtmltopdf_margin_bottom', '', '', '10', 5, 0, 0, 1),
(375, 141, 0, 0, 2, 2, '', 'sphinx_prefix', '', '', '', 1, 1, 0, 1),
(376, 141, 0, 0, 2, 2, '', 'sphinx_main_config', '', '', '', 1, 1, 0, 1),
(377, 150, 0, 0, 1, 2, '', 'kbp_version', '', '', '6.0', 1, 1, 1, 1),
(378, 2, 0, 0, 16, 2, '', 'search_spell_bing_autosuggest_url', '', '', '', 12, 0, 1, 1);

--
DROP TABLE IF EXISTS `kbp_user_activity`;
--
CREATE TABLE IF NOT EXISTS `kbp_user_activity` (
  `entry_type` tinyint(3) UNSIGNED NOT NULL,
  `action_type` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `entry_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_id` int(10) UNSIGNED NOT NULL,
  `user_ip` int(10) UNSIGNED DEFAULT NULL,
  `date_action` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_month` int(10) UNSIGNED NOT NULL,
  `extra_data` text,
  KEY `user_id` (`user_id`),
  KEY `date_month` (`date_month`),
  KEY `entry_type` (`entry_type`,`entry_id`)
) ENGINE=MyISAM ;

--
DROP TABLE IF EXISTS `kbp_user_auth_token`;
--
CREATE TABLE IF NOT EXISTS `kbp_user_auth_token` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(150) NOT NULL,
  `remote_token` varchar(32) NOT NULL,
  `date_expired` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  ;

--
TRUNCATE TABLE `kbp_entry_index`;


--
ALTER TABLE `kbp_news` ADD FULLTEXT `title_only` (`title`);
--
ALTER TABLE `kbp_news` CHANGE `date_posted` `date_posted` DATETIME NOT NULL,
                       CHANGE `place_top_date` `place_top_date` DATE NULL DEFAULT NULL;
--
UPDATE `kbp_news` SET date_updated = date_updated, place_top_date = NULL WHERE place_top_date = '0000-00-00';


--
ALTER TABLE `kbp_export` MODIFY `export_option` MEDIUMTEXT NOT NULL;


--
ALTER TABLE `kbp_file_entry` ADD `filename_disk` VARCHAR( 256 ) NOT NULL AFTER `filename`,
                             DROP INDEX `title_only`, ADD FULLTEXT `title_only` (`title`, `filename_index`);
--
ALTER TABLE `kbp_file_entry` CHANGE `date_posted` `date_posted` DATETIME NOT NULL;


--
ALTER TABLE `kbp_entry_draft` CHANGE `entry_obj` `entry_obj` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                              CHANGE `date_posted` `date_posted` DATETIME NOT NULL;
--
ALTER TABLE `kbp_entry_draft` ADD `private` TINYINT UNSIGNED NOT NULL DEFAULT '0' AFTER `entry_obj`,
                              DROP `date_commented`;


--
DELETE FROM `kbp_setting_to_value` WHERE setting_id = 239;


--
UPDATE `kbp_letter_template` SET `skip_tags` = 'name,username,first_name,last_name,middle_name,email' WHERE `id` = 30;


--
INSERT INTO `kbp_priv_rule` VALUES ('2',  '600',  'select,insert,update,status,delete',  '',  '',  '1',  '1'),
                                   ('2',  '220',  'select',  '',  '',  '1',  '1'),
                                   ('2',  '98',  'select,update,delete',  '',  '',  '0',  '1'),
                                   ('2',  '15',  'select,insert,update,status,delete',  '',  '',  '0',  '1');

--
DELETE FROM `kbp_letter_template` WHERE `id` IN(12,13,14,17,18,19);


--
ALTER TABLE `kbp_kb_glossary` ADD `date_updated` TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `definition`;


--
ALTER TABLE `kbp_feedback` CHANGE `date_posted` `date_posted` DATETIME NOT NULL, 
                           CHANGE `date_answered` `date_answered` DATETIME NULL DEFAULT NULL;
--
UPDATE `kbp_feedback` SET date_answered = NULL WHERE date_answered = '0000-00-00 00:00:00';


--
ALTER TABLE `kbp_stuff_data` DROP INDEX `data_key`, ADD INDEX `data_key` (`data_key`(3));


--
UPDATE `kbp_custom_field` SET type_id = 20 WHERE type_id = 11;


--
UPDATE `kbp_user_subscription` SET entry_type = 31 WHERE entry_type = 101;


--
ALTER TABLE `kbp_kb_entry` CHANGE `date_posted` `date_posted` DATETIME NOT NULL,
                           CHANGE `date_commented` `date_commented` TIMESTAMP NULL DEFAULT NULL;
--
UPDATE `kbp_kb_entry` SET date_updated = date_updated, date_commented = NULL WHERE date_commented = '0000-00-00 00:00:00';


--
ALTER TABLE `kbp_user` CHANGE `date_registered` `date_registered` DATETIME NOT NULL;
--
ALTER TABLE `kbp_export_data` CHANGE `date_created` `date_created` DATETIME NOT NULL;
--
ALTER TABLE `kbp_kb_comment` CHANGE `date_posted` `date_posted` DATETIME NOT NULL;
--
ALTER TABLE `kbp_stuff_entry` CHANGE `date_posted` `date_posted` DATETIME NOT NULL;
--
ALTER TABLE `kbp_tag` CHANGE `date_posted` `date_posted` DATETIME NOT NULL;


--
ALTER TABLE `kbp_entry_task` ADD `failed` SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0' AFTER `value2`, 
                             ADD `failed_message` TEXT AFTER `failed`;
--
ALTER TABLE `kbp_entry_task` DROP PRIMARY KEY, ADD PRIMARY KEY (`rule_id`, `entry_id`, `entry_type`);



--
INSERT INTO `kbp_letter_template` VALUES (37, 3, 'registration_confirmed', '', '', '[noreply_email]', '', '[email]', '[first_name] [last_name]', '', '', '', '', NULL, '', NULL, 'from', 'login', '', 0, 2, 1, 1, 7);

--
UPDATE `kbp_letter_template` SET `sort_order` = '8' WHERE `id` = 5;
--
UPDATE `kbp_letter_template` SET `sort_order` = '9' WHERE `id` = 29;


--
ALTER TABLE `kbp_custom_field` ADD `sort_order` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0' AFTER `html_template`,
                               ADD `is_search` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0' AFTER `html_template`;


--
UPDATE `kbp_list_country` SET active = 0 WHERE id = 236;
--
INSERT INTO `kbp_list_country` VALUES (240, 'Serbia', 'RS', 'SRB', 0, 1), (241, 'Montenegro', 'ME', 'MNE', 0, 1), (242, 'South Sudan', 'SS', 'SSD', 0, 1);


--
ALTER TABLE `kbp_trigger` ADD `options` text NOT NULL AFTER `title`,
                          DROP `description`;


--
ALTER TABLE `kbp_user` ADD `lastpass` int(10) UNSIGNED DEFAULT NULL AFTER `lastauth`;


--
ALTER TABLE `kbp_user_role` CHANGE `title` `title` varchar(100) NOT NULL DEFAULT '';


--
DELETE FROM `kbp_setting_to_value` WHERE `setting_id` = 157;


--
ALTER TABLE `kbp_kb_entry_history` ADD `entry_updater_id` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `entry_data`,
                                   ADD `entry_date_updated` TIMESTAMP NULL DEFAULT NULL AFTER `entry_updater_id`;

--
UPDATE `kbp_kb_entry_history` dest, 
    (SELECT entry_id, revision_num+1 AS revision_num, date_posted, user_id  FROM `kbp_kb_entry_history`) src 
    SET dest.entry_updater_id = src.user_id, dest.entry_date_updated = src.date_posted, dest.date_posted = dest.date_posted
    WHERE dest.entry_updater_id = 0 AND dest.entry_id = src.entry_id AND dest.revision_num = src.revision_num;

--
UPDATE `kbp_kb_entry_history` 
    SET entry_updater_id = user_id, entry_date_updated = date_posted, date_posted = date_posted
    WHERE entry_updater_id = 0;
	


--
ALTER TABLE `kbp_kb_category` ADD `active_real` TINYINT(1) NOT NULL DEFAULT '1' AFTER `private`;
--
UPDATE `kbp_kb_category` SET active_real = active;
--
ALTER TABLE `kbp_file_category` ADD `active_real` TINYINT(1) NOT NULL DEFAULT '1' AFTER `private`;
--
UPDATE `kbp_file_category` SET active_real = active;


--
ALTER TABLE `kbp_entry_draft` CHANGE `entry_obj` `entry_obj` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;


--
ALTER TABLE `kbp_news` ADD INDEX (`date_updated`);


--
ALTER TABLE `kbp_email_pool` CHANGE `date_created` `date_created` TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
--
ALTER TABLE `kbp_entry_autosave` CHANGE `date_saved` `date_saved` TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
--
ALTER TABLE `kbp_entry_hits` CHANGE `date_hit` `date_hit` TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
--
ALTER TABLE `kbp_entry_lock` CHANGE `date_locked` `date_locked` TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
--
ALTER TABLE `kbp_file_entry` CHANGE `date_updated` `date_updated` TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
--
ALTER TABLE `kbp_kb_entry` CHANGE `date_updated` `date_updated` TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
--
ALTER TABLE `kbp_kb_entry_history` CHANGE `date_posted` `date_posted` TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
--
ALTER TABLE `kbp_log_cron` CHANGE `date_started` `date_started` TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
--
ALTER TABLE `kbp_log_login` CHANGE `date_login` `date_login` TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
--
ALTER TABLE `kbp_log_search` CHANGE `date_search` `date_search` TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
--
ALTER TABLE `kbp_priv` CHANGE `timestamp` `timestamp` TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
--
ALTER TABLE `kbp_stuff_data` CHANGE `date_posted` `date_posted` TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
--
ALTER TABLE `kbp_stuff_entry` CHANGE `date_updated` `date_updated` TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
--
ALTER TABLE `kbp_kb_rating_feedback` CHANGE `date_posted` `date_posted` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;


--
ALTER TABLE `kbp_news` CHANGE `title` `title` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
--
ALTER TABLE `kbp_news` CHANGE `body` `body` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                       CHANGE `body_index` `body_index` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

