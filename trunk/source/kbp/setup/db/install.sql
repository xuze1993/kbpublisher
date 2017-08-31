SET sql_mode = '';
--
CREATE TABLE IF NOT EXISTS `kbp_article_template` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `entry_type` tinyint(4) NOT NULL DEFAULT '0',
  `tmpl_key` varchar(30) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `body` text NOT NULL,
  `description` text NOT NULL,
  `is_widget` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT '1',
  `private` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `tmpl_key` (`tmpl_key`(3))
) ENGINE=MyISAM  ;

--
INSERT INTO `kbp_article_template` (`id`, `entry_type`, `tmpl_key`, `title`, `body`, `description`, `is_widget`, `sort_order`, `private`, `active`) VALUES
(1, 1, '', 'Page Content 1', '<h3>Sub title 1 here</h3>\r\n<h3>Sub title 2 here<br />\r\n</h3>\r\n<ol>\r\n    <li>item 1</li>\r\n    <li>item 2</li>\r\n    <li>item3</li>\r\n</ol>\r\n<h3>&nbsp;</h3>', 'Example of article format', 0, 1, 0, 1),
(2, 1, '', 'Info Box', '<div class="box yellowBox">type here</div>\r\n', 'Yellow box with borders', 1, 1, 0, 1),
(3, 1, '', 'Info Box 2', '<div class="box greyBox">type here</div>', 'Grey box with borders', 1, 1, 0, 1);

--
CREATE TABLE IF NOT EXISTS `kbp_custom_field` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `input_id` tinyint(4) NOT NULL DEFAULT '0',
  `type_id` tinyint(4) NOT NULL DEFAULT '0',
  `range_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `tooltip` text NOT NULL,
  `caption` varchar(255) NOT NULL DEFAULT '',
  `default_value` varchar(255) NOT NULL DEFAULT '',
  `is_required` tinyint(4) NOT NULL DEFAULT '0',
  `error_message` text NOT NULL,
  `valid_regexp` varchar(255) NOT NULL DEFAULT '',
  `position` tinyint(4) NOT NULL DEFAULT '0',
  `display` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `html_template` text NOT NULL,
  `is_search` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `active` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `range_id` (`range_id`)
) ENGINE=MyISAM  ;

--
CREATE TABLE IF NOT EXISTS `kbp_custom_field_range` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  ;

--
CREATE TABLE IF NOT EXISTS `kbp_custom_field_range_value` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `range_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `sort_order` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  ;

--
CREATE TABLE IF NOT EXISTS `kbp_custom_field_to_category` (
  `field_id` int(11) UNSIGNED NOT NULL,
  `category_id` int(11) UNSIGNED NOT NULL DEFAULT '0',
  KEY `field_id` (`field_id`,`category_id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_data_to_user_value` (
  `rule_id` int(11) NOT NULL DEFAULT '0',
  `data_value` int(11) NOT NULL DEFAULT '0',
  `user_value` int(11) NOT NULL DEFAULT '0',
  KEY `rule_id` (`rule_id`),
  KEY `data_value` (`data_value`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_data_to_user_value_string` (
  `rule_id` int(11) NOT NULL DEFAULT '0',
  `data_value` int(11) NOT NULL DEFAULT '0',
  `user_value` varchar(255) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rule_id`,`data_value`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_email_pool` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `letter_type` tinyint(4) NOT NULL DEFAULT '0',
  `message` text NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_sent` timestamp NULL DEFAULT NULL,
  `failed` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `failed_message` text,
  `status` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_entry_autosave` (
  `id_key` varchar(32) NOT NULL,
  `entry_id` int(10) UNSIGNED NOT NULL,
  `entry_type` tinyint(4) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `entry_obj` longtext NOT NULL,
  `date_saved` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  KEY `entry_id` (`entry_id`,`entry_type`),
  KEY `id_key` (`id_key`(3))
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_entry_draft` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `entry_type` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `author_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `updater_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `title` text NOT NULL,
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_posted` datetime NOT NULL,
  `entry_obj` mediumtext NOT NULL,
  `private` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`,`entry_type`)
) ENGINE=MyISAM  ;

--
CREATE TABLE IF NOT EXISTS `kbp_entry_draft_to_category` (
  `draft_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `category_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  KEY `entry_id` (`draft_id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_entry_draft_workflow` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `draft_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `workflow_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_id` int(10) UNSIGNED NOT NULL,
  `date_posted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `step_num` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `step_title` varchar(255) NOT NULL DEFAULT '',
  `comment` text,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `draft_id` (`draft_id`)
) ENGINE=MyISAM  ;

--
CREATE TABLE IF NOT EXISTS `kbp_entry_draft_workflow_history` (
  `draft_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `entry_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `entry_type` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `user_id` int(10) UNSIGNED NOT NULL,
  `date_posted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `step_num` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `step_title` varchar(255) NOT NULL DEFAULT '',
  `comment` text,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  KEY `draft_id` (`draft_id`),
  KEY `entry_id` (`entry_id`,`entry_type`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_entry_draft_workflow_to_assignee` (
  `draft_id` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `draft_workflow_id` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `assignee_id` int(11) UNSIGNED NOT NULL DEFAULT '0',
  KEY `draft_id` (`draft_id`),
  KEY `draft_workflow_id` (`draft_workflow_id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_entry_featured` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `entry_type` tinyint(3) UNSIGNED NOT NULL,
  `entry_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `entry_type` (`entry_type`,`category_id`)
) ENGINE=MyISAM  ;

--
CREATE TABLE IF NOT EXISTS `kbp_entry_hits` (
  `entry_id` int(10) UNSIGNED NOT NULL,
  `entry_type` tinyint(4) NOT NULL,
  `hits` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_hit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`entry_id`,`entry_type`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_entry_lock` (
  `entry_id` int(10) UNSIGNED NOT NULL,
  `entry_type` tinyint(4) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `date_locked` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `reason_locked` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`entry_id`,`entry_type`)
) ENGINE=MyISAM  COMMENT='locked records, mostly opened by editing or by some other re';

--
CREATE TABLE IF NOT EXISTS `kbp_entry_rule` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `entry_type` tinyint(3) UNSIGNED NOT NULL,
  `directory` varchar(255) NOT NULL DEFAULT '',
  `parse_child` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `is_draft` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `description` text NOT NULL,
  `date_executed` datetime DEFAULT NULL,
  `entry_obj` text NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `entry_type` (`entry_type`)
) ENGINE=MyISAM  ;

--
CREATE TABLE IF NOT EXISTS `kbp_entry_schedule` (
  `entry_id` int(10) UNSIGNED NOT NULL,
  `entry_type` tinyint(4) NOT NULL,
  `num` tinyint(4) NOT NULL DEFAULT '1',
  `date_scheduled` datetime NOT NULL,
  `value` tinyint(3) UNSIGNED NOT NULL,
  `note` text,
  `notify` varchar(255) NOT NULL DEFAULT '1',
  `active` tinyint(4) NOT NULL DEFAULT '1',
  KEY `entry_id` (`entry_id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_entry_task` (
  `rule_id` tinyint(4) NOT NULL,
  `entry_id` int(10) UNSIGNED NOT NULL,
  `entry_type` tinyint(4) NOT NULL DEFAULT '0',
  `value1` mediumtext,
  `value2` mediumtext,
  `failed` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `failed_message` text,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rule_id`,`entry_id`,`entry_type`)
) ENGINE=MyISAM  COMMENT='keep sheduled task for entries';

--
CREATE TABLE IF NOT EXISTS `kbp_entry_trash` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) UNSIGNED NOT NULL,
  `entry_type` tinyint(4) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `entry_obj` longtext NOT NULL,
  `date_deleted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_export` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `export_type` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `filetype` varchar(100) NOT NULL,
  `export_option` mediumtext NOT NULL,
  `active` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_export_data` (
  `export_id` int(10) NOT NULL DEFAULT '0',
  `export_type` tinyint(4) UNSIGNED NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL,
  `export_data` longblob NOT NULL,
  `export_result` text NOT NULL,
  `content_type` varchar(100) NOT NULL,
  KEY `export_id` (`export_id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_feedback` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `admin_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `subject_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `question` text NOT NULL,
  `attachment` text,
  `answer` text,
  `answer_attachment` text,
  `date_posted` datetime NOT NULL,
  `date_answered` datetime DEFAULT NULL,
  `answered` tinyint(1) NOT NULL DEFAULT '0',
  `placed` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `user_id` (`user_id`),
  KEY `subject_id` (`subject_id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_feedback_custom_data` (
  `entry_id` int(10) NOT NULL DEFAULT '0',
  `field_id` int(10) NOT NULL DEFAULT '0',
  `data` text NOT NULL,
  PRIMARY KEY (`entry_id`,`field_id`),
  KEY `field_id` (`field_id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_file_category` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `attachable` tinyint(1) DEFAULT '1',
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `num_entry` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `private` tinyint(1) NOT NULL DEFAULT '0',
  `active_real` tinyint(1) NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `sort_order` (`sort_order`)
) ENGINE=MyISAM  ;

--
CREATE TABLE IF NOT EXISTS `kbp_file_custom_data` (
  `entry_id` int(10) NOT NULL DEFAULT '0',
  `field_id` int(10) NOT NULL DEFAULT '0',
  `data` text NOT NULL,
  PRIMARY KEY (`entry_id`,`field_id`),
  KEY `field_id` (`field_id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_file_entry` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `author_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `updater_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `directory` varchar(255) NOT NULL DEFAULT '',
  `sub_directory` varchar(255) NOT NULL DEFAULT '',
  `filename` varchar(255) NOT NULL DEFAULT '',
  `filename_disk` varchar(256) NOT NULL,
  `filename_index` varchar(256) NOT NULL,
  `meta_keywords` text NOT NULL,
  `filesize` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `filetype` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `description_full` text NOT NULL,
  `comment` text NOT NULL,
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_posted` datetime NOT NULL,
  `downloads` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `filetext` mediumtext NOT NULL,
  `md5hash` varchar(32) NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT '1',
  `private` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  KEY `filename` (`filename`(4)),
  KEY `updater_id` (`updater_id`),
  KEY `category_id` (`category_id`),
  KEY `downloads` (`downloads`),
  KEY `date_updated` (`date_updated`)
) ENGINE=MyISAM   COMMENT='images per item';

--
CREATE TABLE IF NOT EXISTS `kbp_file_entry_to_category` (
  `entry_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `category_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `is_main` tinyint(4) NOT NULL DEFAULT '1',
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT '1',
  PRIMARY KEY (`entry_id`,`category_id`),
  KEY `category_id` (`category_id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_kb_attachment_to_entry` (
  `entry_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `attachment_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `attachment_type` tinyint(1) NOT NULL DEFAULT '0',
  KEY `entry_id` (`entry_id`),
  KEY `attachment_id` (`attachment_id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_kb_category` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `num_entry` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `commentable` tinyint(1) NOT NULL DEFAULT '1',
  `ratingable` tinyint(1) NOT NULL DEFAULT '1',
  `category_type` tinyint(1) NOT NULL DEFAULT '1',
  `private` tinyint(1) NOT NULL DEFAULT '0',
  `active_real` tinyint(1) NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `sort_order` (`sort_order`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_kb_comment` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(50) NOT NULL DEFAULT '',
  `comment` text NOT NULL,
  `date_posted` datetime NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`),
  KEY `NewIndex` (`user_id`)
) ENGINE=MyISAM  ;

--
CREATE TABLE IF NOT EXISTS `kbp_kb_custom_data` (
  `entry_id` int(10) NOT NULL DEFAULT '0',
  `field_id` int(10) NOT NULL DEFAULT '0',
  `data` text NOT NULL,
  PRIMARY KEY (`entry_id`,`field_id`),
  KEY `field_id` (`field_id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_kb_entry` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `author_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `updater_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `title` text NOT NULL,
  `body` mediumtext NOT NULL,
  `body_index` mediumtext NOT NULL,
  `url_title` varchar(255) NOT NULL DEFAULT '',
  `meta_keywords` text NOT NULL,
  `meta_description` text NOT NULL,
  `entry_type` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `external_link` text NOT NULL,
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_posted` datetime NOT NULL,
  `date_commented` timestamp NULL DEFAULT NULL,
  `history_comment` text,
  `hits` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT '1',
  `private` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `updater_id` (`updater_id`),
  KEY `author_id` (`author_id`),
  KEY `entry_type` (`entry_type`),
  KEY `category_id` (`category_id`),
  KEY `hits` (`hits`),
  KEY `date_updated` (`date_updated`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_kb_entry_history` (
  `entry_id` int(10) UNSIGNED NOT NULL,
  `revision_num` tinyint(3) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `date_posted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `comment` text NOT NULL,
  `entry_data` mediumtext NOT NULL,
  `entry_updater_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `entry_date_updated` timestamp NULL DEFAULT NULL,
  KEY `entry_id` (`entry_id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_kb_entry_to_category` (
  `entry_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `category_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `is_main` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT '1',
  PRIMARY KEY (`entry_id`,`category_id`),
  KEY `category_id` (`category_id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_kb_glossary` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `phrase` varchar(100) NOT NULL DEFAULT '',
  `definition` text NOT NULL,
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `display_once` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  ;

--
CREATE TABLE IF NOT EXISTS `kbp_kb_rating` (
  `entry_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `votes` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `rate` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`entry_id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_kb_rating_feedback` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `date_posted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `rating` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `comment` text NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`)
) ENGINE=MyISAM  ;

--
CREATE TABLE IF NOT EXISTS `kbp_kb_related_to_entry` (
  `entry_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `related_entry_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `related_type` tinyint(1) NOT NULL DEFAULT '0',
  `related_ref` tinyint(1) NOT NULL DEFAULT '1',
  KEY `entry_id` (`entry_id`),
  KEY `related_entry_id` (`related_entry_id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_letter_template` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_id` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `letter_key` varchar(50) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `from_email` varchar(255) NOT NULL DEFAULT '',
  `from_name` varchar(255) NOT NULL DEFAULT '',
  `to_email` varchar(255) NOT NULL DEFAULT '',
  `to_name` varchar(255) NOT NULL DEFAULT '',
  `to_cc_email` varchar(255) NOT NULL DEFAULT '',
  `to_cc_name` varchar(255) NOT NULL DEFAULT '',
  `to_bcc_email` varchar(255) NOT NULL DEFAULT '',
  `to_bcc_name` varchar(255) NOT NULL DEFAULT '',
  `to_special` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL DEFAULT '',
  `body` text,
  `skip_field` varchar(255) NOT NULL DEFAULT '',
  `extra_tags` varchar(255) NOT NULL,
  `skip_tags` varchar(255) NOT NULL,
  `is_html` tinyint(1) NOT NULL DEFAULT '0',
  `in_out` tinyint(4) NOT NULL DEFAULT '1',
  `predifined` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  ;

--
INSERT INTO `kbp_letter_template` (`id`, `group_id`, `letter_key`, `title`, `description`, `from_email`, `from_name`, `to_email`, `to_name`, `to_cc_email`, `to_cc_name`, `to_bcc_email`, `to_bcc_name`, `to_special`, `subject`, `body`, `skip_field`, `extra_tags`, `skip_tags`, `is_html`, `in_out`, `predifined`, `active`, `sort_order`) VALUES
(1, 4, 'send_to_friend', '', '', '[noreply_email]', '[name]', '[email]', '', '', '', '', '', NULL, '', NULL, 'from,to', 'message,entry_title,sender_email', '', 0, 2, 1, 1, 100),
(2, 4, 'answer_to_user', '', '', '[support_email]', '[support_name]', '[email]', '[name]', '', '', '', '', NULL, '', NULL, '', 'subject,title,question,answer', 'link', 0, 2, 1, 1, 41),
(3, 4, 'contact', '', '', '[noreply_email]', '[name]', '[support_email]', '', '', '', '', '', 'feedback_admin', '', NULL, 'from', 'subject,title,message,attachment,custom', '', 0, 1, 1, 1, 40),
(4, 3, 'confirm_registration', '', '', '[noreply_email]', '', '[email]', '', '', '', '', '', '', '', NULL, 'from,to', '', '', 0, 2, 1, 1, 6),
(5, 3, 'generated_password', '', '', '[noreply_email]', '', '[email]', '', '', '', '', '', NULL, '', NULL, 'from', 'login,password', '', 0, 2, 1, 0, 8),
(6, 1, 'comment_approve_to_admin', '', '', '[noreply_email]', '', '[support_email]', '', '', '', '', '', 'category_admin', '', NULL, 'from', 'message', '', 0, 1, 1, 1, 30),
(7, 3, 'user_approve_to_admin', '', '', '[noreply_email]', '', '[support_email]', '', '', '', '', '', NULL, '', NULL, 'from', 'user_details', '', 0, 1, 1, 1, 1),
(8, 3, 'user_approve_to_user', '', '', '[noreply_email]', '', '[email]', '[first_name] [last_name]', '', '', '', '', NULL, '', NULL, 'from', '', 'link', 0, 2, 1, 1, 2),
(9, 3, 'user_approved', '', '', '[noreply_email]', '', '[email]', '[first_name] [last_name]', '', '', '', '', NULL, '', NULL, 'from', 'login,password', '', 0, 2, 1, 1, 3),
(10, 3, 'user_added', '', '', '[noreply_email]', '', '[email]', '', '', '', '', '', NULL, '', NULL, 'from', 'login,password', '', 0, 2, 1, 1, 4),
(11, 3, 'user_updated', '', '', '[noreply_email]', '', '[email]', '', '', '', '', '', '', '', NULL, 'from', 'login,password', '', 0, 2, 1, 1, 5),
(15, 1, 'article_added', '', '', '[noreply_email]', '', '[email]', '', '', '', '', '', NULL, '', NULL, 'from', '', '', 0, 2, 1, 0, 14),
(16, 1, 'article_updated', '', '', '[noreply_email]', '', '[email]', '', '', '', '', '', NULL, '', NULL, 'from', '', '', 0, 2, 1, 0, 15),
(20, 2, 'file_added', '', '', '[noreply_email]', '', '[email]', '', '', '', '', '', NULL, '', NULL, 'from', '', '', 0, 2, 1, 0, 24),
(21, 2, 'file_updated', '', '', '[noreply_email]', '', '[email]', '', '', '', '', '', NULL, '', NULL, 'from', '', '', 0, 2, 1, 0, 25),
(23, 1, 'rating_comment_added', '', '', '[noreply_email]', '[name]', '[author_email],[updater_email]', '', '[support_email]', '', '', '', 'category_admin', '', NULL, 'from', 'author_email,updater_email,title,rating,message', '', 0, 1, 1, 1, 50),
(24, 4, 'scheduled_entry', '', '', '[noreply_email]', '', '[author_email], [updater_email]', '', '', '', '', '', 'category_admin', '', NULL, 'from', 'author_email,updater_email,note,id,title,status,type', 'name,username,first_name,last_name,middle_name,email', 0, 1, 1, 1, 109),
(26, 6, 'subscription_news', '', '', '[noreply_email]', '', '[email]', '[first_name] [last_name]', '', '', '', '', NULL, '', NULL, 'from', 'content,unsubscribe_link,account_link', 'link', 1, 2, 1, 1, 1),
(27, 6, 'subscription_entry', '', '', '[noreply_email]', '', '[email]', '[first_name] [last_name]', '', '', '', '', NULL, '', NULL, 'from', 'new_article,updated_article,commented_article,new_file,updated_file,unsubscribe_link,account_link', 'link', 1, 2, 1, 1, 2),
(28, 6, 'subscription_comment', '', '', '[noreply_email]', '', '[email]', '[first_name] [last_name]', '', '', '', '', NULL, '', NULL, 'from', 'content,unsubscribe_link,account_link', 'link', 1, 2, 1, 1, 3),
(29, 3, 'reset_password', '', '', '[noreply_email]', '', '[email]', '', '', '', '', '', NULL, '', NULL, 'from,to', 'code', '', 0, 2, 1, 1, 9),
(30, 5, 'draft_approval_request', '', '', '[noreply_email]', '', '[email]', '', '', '', '', '', NULL, '', NULL, 'from,to', 'entry_type,title,comment', 'name,username,first_name,last_name,middle_name,email', 0, 1, 1, 1, 1),
(31, 5, 'draft_rejection', '', '', '[noreply_email]', '', '[email]', '', '', '', '', '', NULL, '', NULL, 'from,to', 'entry_type,title,comment', '', 0, 1, 1, 1, 2),
(32, 5, 'draft_publication', '', '', '[noreply_email]', '', '[email]', '', '', '', '', '', '', '', NULL, 'from,to', 'entry_type,title,comment', '', 0, 1, 1, 1, 5),
(33, 5, 'draft_rejection_to_approver', '', '', '[noreply_email]', '', '[email]', '', '', '', '', '', NULL, '', NULL, 'from,to', 'entry_type,title,comment', '', 0, 1, 1, 1, 3),
(34, 6, 'subscription_topic', '', '', '[noreply_email]', '', '[email]', '[first_name] [last_name]', '', '', '', '', NULL, '', NULL, 'from', 'content,unsubscribe_link,account_link', 'link', 1, 2, 1, 1, 7),
(35, 6, 'subscription_forum', '', '', '[noreply_email]', '', '[email]', '[first_name] [last_name]', '', '', '', '', NULL, '', NULL, 'from', 'new_topic,updated_topic,unsubscribe_link,account_link', 'link', 1, 2, 1, 1, 6),
(37, 3, 'registration_confirmed', '', '', '[noreply_email]', '', '[email]', '[first_name] [last_name]', '', '', '', '', NULL, '', NULL, 'from', 'login', '', 0, 2, 1, 1, 7);

--
CREATE TABLE IF NOT EXISTS `kbp_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `list_key` varchar(50) NOT NULL DEFAULT '',
  `title` varchar(50) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `predifined` tinyint(4) NOT NULL DEFAULT '0',
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `list_key` (`list_key`)
) ENGINE=MyISAM  ;

--
INSERT INTO `kbp_list` (`id`, `list_key`, `title`, `description`, `predifined`, `sort_order`, `active`) VALUES
(1, 'article_status', '', '', 1, 3, 1),
(2, 'file_status', '', '', 1, 2, 1),
(3, 'article_type', '', '', 1, 4, 1),
(4, 'user_status', '', '', 1, 1, 1),
(5, 'feedback_subj', '', '', 1, 10, 1),
(6, 'rate_status', '', '', 1, 6, 1),
(7, 'trouble_status', '', '', 1, 5, 0),
(8, 'forum_status', '', '', 1, 7, 1);

--
CREATE TABLE IF NOT EXISTS `kbp_list_country` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(30) NOT NULL DEFAULT '',
  `iso2` varchar(2) NOT NULL DEFAULT '',
  `iso3` varchar(3) NOT NULL DEFAULT '',
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  ;

--
INSERT INTO `kbp_list_country` (`id`, `title`, `iso2`, `iso3`, `sort_order`, `active`) VALUES
(1, 'Afghanistan', 'AF', 'AFG', 0, 1),
(2, 'Albania', 'AL', 'ALB', 0, 1),
(3, 'Algeria', 'DZ', 'DZA', 0, 1),
(4, 'American Samoa', 'AS', 'ASM', 0, 1),
(5, 'Andorra', 'AD', 'AND', 0, 1),
(6, 'Angola', 'AO', 'AGO', 0, 1),
(7, 'Anguilla', 'AI', 'AIA', 0, 1),
(8, 'Antarctica', 'AQ', 'ATA', 0, 1),
(9, 'Antigua and Barbuda', 'AG', 'ATG', 0, 1),
(10, 'Argentina', 'AR', 'ARG', 0, 1),
(11, 'Armenia', 'AM', 'ARM', 0, 1),
(12, 'Aruba', 'AW', 'ABW', 0, 1),
(13, 'Australia', 'AU', 'AUS', 0, 1),
(14, 'Austria', 'AT', 'AUT', 0, 1),
(15, 'Azerbaijan', 'AZ', 'AZE', 0, 1),
(16, 'Bahamas', 'BS', 'BHS', 0, 1),
(17, 'Bahrain', 'BH', 'BHR', 0, 1),
(18, 'Bangladesh', 'BD', 'BGD', 0, 1),
(19, 'Barbados', 'BB', 'BRB', 0, 1),
(20, 'Belarus', 'BY', 'BLR', 0, 1),
(21, 'Belgium', 'BE', 'BEL', 0, 1),
(22, 'Belize', 'BZ', 'BLZ', 0, 1),
(23, 'Benin', 'BJ', 'BEN', 0, 1),
(24, 'Bermuda', 'BM', 'BMU', 0, 1),
(25, 'Bhutan', 'BT', 'BTN', 0, 1),
(26, 'Bolivia', 'BO', 'BOL', 0, 1),
(27, 'Bosnia and Herzegowina', 'BA', 'BIH', 0, 1),
(28, 'Botswana', 'BW', 'BWA', 0, 1),
(29, 'Bouvet Island', 'BV', 'BVT', 0, 1),
(30, 'Brazil', 'BR', 'BRA', 0, 1),
(31, 'British Indian Ocean Territory', 'IO', 'IOT', 0, 1),
(32, 'Brunei Darussalam', 'BN', 'BRN', 0, 1),
(33, 'Bulgaria', 'BG', 'BGR', 0, 1),
(34, 'Burkina Faso', 'BF', 'BFA', 0, 1),
(35, 'Burundi', 'BI', 'BDI', 0, 1),
(36, 'Cambodia', 'KH', 'KHM', 0, 1),
(37, 'Cameroon', 'CM', 'CMR', 0, 1),
(38, 'Canada', 'CA', 'CAN', 0, 1),
(39, 'Cape Verde', 'CV', 'CPV', 0, 1),
(40, 'Cayman Islands', 'KY', 'CYM', 0, 1),
(41, 'Central African Republic', 'CF', 'CAF', 0, 1),
(42, 'Chad', 'TD', 'TCD', 0, 1),
(43, 'Chile', 'CL', 'CHL', 0, 1),
(44, 'China', 'CN', 'CHN', 0, 1),
(45, 'Christmas Island', 'CX', 'CXR', 0, 1),
(46, 'Cocos (Keeling) Islands', 'CC', 'CCK', 0, 1),
(47, 'Colombia', 'CO', 'COL', 0, 1),
(48, 'Comoros', 'KM', 'COM', 0, 1),
(49, 'Congo', 'CG', 'COG', 0, 1),
(50, 'Cook Islands', 'CK', 'COK', 0, 1),
(51, 'Costa Rica', 'CR', 'CRI', 0, 1),
(52, 'Cote D\'Ivoire', 'CI', 'CIV', 0, 1),
(53, 'Croatia', 'HR', 'HRV', 0, 1),
(54, 'Cuba', 'CU', 'CUB', 0, 1),
(55, 'Cyprus', 'CY', 'CYP', 0, 1),
(56, 'Czech Republic', 'CZ', 'CZE', 0, 1),
(57, 'Denmark', 'DK', 'DNK', 0, 1),
(58, 'Djibouti', 'DJ', 'DJI', 0, 1),
(59, 'Dominica', 'DM', 'DMA', 0, 1),
(60, 'Dominican Republic', 'DO', 'DOM', 0, 1),
(61, 'East Timor', 'TP', 'TMP', 0, 1),
(62, 'Ecuador', 'EC', 'ECU', 0, 1),
(63, 'Egypt', 'EG', 'EGY', 0, 1),
(64, 'El Salvador', 'SV', 'SLV', 0, 1),
(65, 'Equatorial Guinea', 'GQ', 'GNQ', 0, 1),
(66, 'Eritrea', 'ER', 'ERI', 0, 1),
(67, 'Estonia', 'EE', 'EST', 0, 1),
(68, 'Ethiopia', 'ET', 'ETH', 0, 1),
(69, 'Falkland Islands (Malvinas)', 'FK', 'FLK', 0, 1),
(70, 'Faroe Islands', 'FO', 'FRO', 0, 1),
(71, 'Fiji', 'FJ', 'FJI', 0, 1),
(72, 'Finland', 'FI', 'FIN', 0, 1),
(73, 'France', 'FR', 'FRA', 0, 1),
(74, 'France, Metropolitan', 'FX', 'FXX', 0, 1),
(75, 'French Guiana', 'GF', 'GUF', 0, 1),
(76, 'French Polynesia', 'PF', 'PYF', 0, 1),
(77, 'French Southern Territories', 'TF', 'ATF', 0, 1),
(78, 'Gabon', 'GA', 'GAB', 0, 1),
(79, 'Gambia', 'GM', 'GMB', 0, 1),
(80, 'Georgia', 'GE', 'GEO', 0, 1),
(81, 'Germany', 'DE', 'DEU', 0, 1),
(82, 'Ghana', 'GH', 'GHA', 0, 1),
(83, 'Gibraltar', 'GI', 'GIB', 0, 1),
(84, 'Greece', 'GR', 'GRC', 0, 1),
(85, 'Greenland', 'GL', 'GRL', 0, 1),
(86, 'Grenada', 'GD', 'GRD', 0, 1),
(87, 'Guadeloupe', 'GP', 'GLP', 0, 1),
(88, 'Guam', 'GU', 'GUM', 0, 1),
(89, 'Guatemala', 'GT', 'GTM', 0, 1),
(90, 'Guinea', 'GN', 'GIN', 0, 1),
(91, 'Guinea-bissau', 'GW', 'GNB', 0, 1),
(92, 'Guyana', 'GY', 'GUY', 0, 1),
(93, 'Haiti', 'HT', 'HTI', 0, 1),
(94, 'Heard and Mc Donald Islands', 'HM', 'HMD', 0, 1),
(95, 'Honduras', 'HN', 'HND', 0, 1),
(96, 'Hong Kong', 'HK', 'HKG', 0, 1),
(97, 'Hungary', 'HU', 'HUN', 0, 1),
(98, 'Iceland', 'IS', 'ISL', 0, 1),
(99, 'India', 'IN', 'IND', 0, 1),
(100, 'Indonesia', 'ID', 'IDN', 0, 1),
(101, 'Iran (Islamic Republic of)', 'IR', 'IRN', 0, 1),
(102, 'Iraq', 'IQ', 'IRQ', 0, 1),
(103, 'Ireland', 'IE', 'IRL', 0, 1),
(104, 'Israel', 'IL', 'ISR', 0, 1),
(105, 'Italy', 'IT', 'ITA', 0, 1),
(106, 'Jamaica', 'JM', 'JAM', 0, 1),
(107, 'Japan', 'JP', 'JPN', 0, 1),
(108, 'Jordan', 'JO', 'JOR', 0, 1),
(109, 'Kazakhstan', 'KZ', 'KAZ', 0, 1),
(110, 'Kenya', 'KE', 'KEN', 0, 1),
(111, 'Kiribati', 'KI', 'KIR', 0, 1),
(112, 'Korea, Democratic People\'s Rep', 'KP', 'PRK', 0, 1),
(113, 'Korea, Republic of', 'KR', 'KOR', 0, 1),
(114, 'Kuwait', 'KW', 'KWT', 0, 1),
(115, 'Kyrgyzstan', 'KG', 'KGZ', 0, 1),
(116, 'Lao People\'s Democratic Republ', 'LA', 'LAO', 0, 1),
(117, 'Latvia', 'LV', 'LVA', 0, 1),
(118, 'Lebanon', 'LB', 'LBN', 0, 1),
(119, 'Lesotho', 'LS', 'LSO', 0, 1),
(120, 'Liberia', 'LR', 'LBR', 0, 1),
(121, 'Libyan Arab Jamahiriya', 'LY', 'LBY', 0, 1),
(122, 'Liechtenstein', 'LI', 'LIE', 0, 1),
(123, 'Lithuania', 'LT', 'LTU', 0, 1),
(124, 'Luxembourg', 'LU', 'LUX', 0, 1),
(125, 'Macau', 'MO', 'MAC', 0, 1),
(126, 'Macedonia, The Former Yugoslav', 'MK', 'MKD', 0, 1),
(127, 'Madagascar', 'MG', 'MDG', 0, 1),
(128, 'Malawi', 'MW', 'MWI', 0, 1),
(129, 'Malaysia', 'MY', 'MYS', 0, 1),
(130, 'Maldives', 'MV', 'MDV', 0, 1),
(131, 'Mali', 'ML', 'MLI', 0, 1),
(132, 'Malta', 'MT', 'MLT', 0, 1),
(133, 'Marshall Islands', 'MH', 'MHL', 0, 1),
(134, 'Martinique', 'MQ', 'MTQ', 0, 1),
(135, 'Mauritania', 'MR', 'MRT', 0, 1),
(136, 'Mauritius', 'MU', 'MUS', 0, 1),
(137, 'Mayotte', 'YT', 'MYT', 0, 1),
(138, 'Mexico', 'MX', 'MEX', 0, 1),
(139, 'Micronesia, Federated States o', 'FM', 'FSM', 0, 1),
(140, 'Moldova, Republic of', 'MD', 'MDA', 0, 1),
(141, 'Monaco', 'MC', 'MCO', 0, 1),
(142, 'Mongolia', 'MN', 'MNG', 0, 1),
(143, 'Montserrat', 'MS', 'MSR', 0, 1),
(144, 'Morocco', 'MA', 'MAR', 0, 1),
(145, 'Mozambique', 'MZ', 'MOZ', 0, 1),
(146, 'Myanmar', 'MM', 'MMR', 0, 1),
(147, 'Namibia', 'NA', 'NAM', 0, 1),
(148, 'Nauru', 'NR', 'NRU', 0, 1),
(149, 'Nepal', 'NP', 'NPL', 0, 1),
(150, 'Netherlands', 'NL', 'NLD', 0, 1),
(151, 'Netherlands Antilles', 'AN', 'ANT', 0, 1),
(152, 'New Caledonia', 'NC', 'NCL', 0, 1),
(153, 'New Zealand', 'NZ', 'NZL', 0, 1),
(154, 'Nicaragua', 'NI', 'NIC', 0, 1),
(155, 'Niger', 'NE', 'NER', 0, 1),
(156, 'Nigeria', 'NG', 'NGA', 0, 1),
(157, 'Niue', 'NU', 'NIU', 0, 1),
(158, 'Norfolk Island', 'NF', 'NFK', 0, 1),
(159, 'Northern Mariana Islands', 'MP', 'MNP', 0, 1),
(160, 'Norway', 'NO', 'NOR', 0, 1),
(161, 'Oman', 'OM', 'OMN', 0, 1),
(162, 'Pakistan', 'PK', 'PAK', 0, 1),
(163, 'Palau', 'PW', 'PLW', 0, 1),
(164, 'Panama', 'PA', 'PAN', 0, 1),
(165, 'Papua New Guinea', 'PG', 'PNG', 0, 1),
(166, 'Paraguay', 'PY', 'PRY', 0, 1),
(167, 'Peru', 'PE', 'PER', 0, 1),
(168, 'Philippines', 'PH', 'PHL', 0, 1),
(169, 'Pitcairn', 'PN', 'PCN', 0, 1),
(170, 'Poland', 'PL', 'POL', 0, 1),
(171, 'Portugal', 'PT', 'PRT', 0, 1),
(172, 'Puerto Rico', 'PR', 'PRI', 0, 1),
(173, 'Qatar', 'QA', 'QAT', 0, 1),
(174, 'Reunion', 'RE', 'REU', 0, 1),
(175, 'Romania', 'RO', 'ROM', 0, 1),
(176, 'Russian Federation', 'RU', 'RUS', 0, 1),
(177, 'Rwanda', 'RW', 'RWA', 0, 1),
(178, 'Saint Kitts and Nevis', 'KN', 'KNA', 0, 1),
(179, 'Saint Lucia', 'LC', 'LCA', 0, 1),
(180, 'Saint Vincent and the Grenadin', 'VC', 'VCT', 0, 1),
(181, 'Samoa', 'WS', 'WSM', 0, 1),
(182, 'San Marino', 'SM', 'SMR', 0, 1),
(183, 'Sao Tome and Principe', 'ST', 'STP', 0, 1),
(184, 'Saudi Arabia', 'SA', 'SAU', 0, 1),
(185, 'Senegal', 'SN', 'SEN', 0, 1),
(186, 'Seychelles', 'SC', 'SYC', 0, 1),
(187, 'Sierra Leone', 'SL', 'SLE', 0, 1),
(188, 'Singapore', 'SG', 'SGP', 0, 1),
(189, 'Slovakia (Slovak Republic)', 'SK', 'SVK', 0, 1),
(190, 'Slovenia', 'SI', 'SVN', 0, 1),
(191, 'Solomon Islands', 'SB', 'SLB', 0, 1),
(192, 'Somalia', 'SO', 'SOM', 0, 1),
(193, 'South Africa', 'ZA', 'ZAF', 0, 1),
(194, 'South Georgia and the South Sa', 'GS', 'SGS', 0, 1),
(195, 'Spain', 'ES', 'ESP', 0, 1),
(196, 'Sri Lanka', 'LK', 'LKA', 0, 1),
(197, 'St. Helena', 'SH', 'SHN', 0, 1),
(198, 'St. Pierre and Miquelon', 'PM', 'SPM', 0, 1),
(199, 'Sudan', 'SD', 'SDN', 0, 1),
(200, 'Suriname', 'SR', 'SUR', 0, 1),
(201, 'Svalbard and Jan Mayen Islands', 'SJ', 'SJM', 0, 1),
(202, 'Swaziland', 'SZ', 'SWZ', 0, 1),
(203, 'Sweden', 'SE', 'SWE', 0, 1),
(204, 'Switzerland', 'CH', 'CHE', 0, 1),
(205, 'Syrian Arab Republic', 'SY', 'SYR', 0, 1),
(206, 'Taiwan', 'TW', 'TWN', 0, 1),
(207, 'Tajikistan', 'TJ', 'TJK', 0, 1),
(208, 'Tanzania, United Republic of', 'TZ', 'TZA', 0, 1),
(209, 'Thailand', 'TH', 'THA', 0, 1),
(210, 'Togo', 'TG', 'TGO', 0, 1),
(211, 'Tokelau', 'TK', 'TKL', 0, 1),
(212, 'Tonga', 'TO', 'TON', 0, 1),
(213, 'Trinidad and Tobago', 'TT', 'TTO', 0, 1),
(214, 'Tunisia', 'TN', 'TUN', 0, 1),
(215, 'Turkey', 'TR', 'TUR', 0, 1),
(216, 'Turkmenistan', 'TM', 'TKM', 0, 1),
(217, 'Turks and Caicos Islands', 'TC', 'TCA', 0, 1),
(218, 'Tuvalu', 'TV', 'TUV', 0, 1),
(219, 'Uganda', 'UG', 'UGA', 0, 1),
(220, 'Ukraine', 'UA', 'UKR', 0, 1),
(221, 'United Arab Emirates', 'AE', 'ARE', 0, 1),
(222, 'United Kingdom', 'GB', 'GBR', 0, 1),
(223, 'United States', 'US', 'USA', 0, 1),
(224, 'United States Minor Outlying I', 'UM', 'UMI', 0, 1),
(225, 'Uruguay', 'UY', 'URY', 0, 1),
(226, 'Uzbekistan', 'UZ', 'UZB', 0, 1),
(227, 'Vanuatu', 'VU', 'VUT', 0, 1),
(228, 'Vatican City State (Holy See)', 'VA', 'VAT', 0, 1),
(229, 'Venezuela', 'VE', 'VEN', 0, 1),
(230, 'Viet Nam', 'VN', 'VNM', 0, 1),
(231, 'Virgin Islands (British)', 'VG', 'VGB', 0, 1),
(232, 'Virgin Islands (U.S.)', 'VI', 'VIR', 0, 1),
(233, 'Wallis and Futuna Islands', 'WF', 'WLF', 0, 1),
(234, 'Western Sahara', 'EH', 'ESH', 0, 1),
(235, 'Yemen', 'YE', 'YEM', 0, 1),
(236, 'Yugoslavia', 'YU', 'YUG', 0, 1),
(237, 'Zaire', 'ZR', 'ZAR', 0, 1),
(238, 'Zambia', 'ZM', 'ZMB', 0, 1),
(239, 'Zimbabwe', 'ZW', 'ZWE', 0, 1);

--
CREATE TABLE IF NOT EXISTS `kbp_list_value` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `list_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `list_key` varchar(50) NOT NULL DEFAULT '',
  `list_value` tinyint(4) NOT NULL DEFAULT '0',
  `title` varchar(50) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `predifined` tinyint(4) NOT NULL DEFAULT '0',
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `custom_1` text NOT NULL,
  `custom_2` text NOT NULL,
  `custom_3` int(11) NOT NULL DEFAULT '0',
  `custom_4` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `list_id` (`list_id`)
) ENGINE=MyISAM  ;

--
INSERT INTO `kbp_list_value` (`id`, `list_id`, `list_key`, `list_value`, `title`, `description`, `predifined`, `sort_order`, `active`, `custom_1`, `custom_2`, `custom_3`, `custom_4`) VALUES
(1, 1, 'not_published', 0, '', '', 1, 2, 1, '#C0C0C0', '', 0, 0),
(2, 1, 'published', 1, '', '', 1, 1, 1, '#7898C2', '', 1, 1),
(5, 2, 'not_published', 0, '', '', 1, 4, 1, '#C0C0C0', '', 0, 0),
(6, 2, 'published', 1, '', '', 1, 1, 1, '#7898C2', '', 1, 1),
(9, 3, 'bug', 1, '', '', 0, 1, 1, '<p><strong>Bug:</strong><br /> <br /> <br /> <strong>How to repeat:</strong><br /> <br /> <br /> <strong>More details:</strong></p>', '', 0, 0),
(10, 3, 'errdoc', 2, '', '', 0, 2, 1, '<p><strong>Bug:</strong><br /> <br /> <br /> <strong>How to repeat:</strong><br /> <br /> <br /> <strong>More details:</strong></p>', '', 0, 0),
(38, 8, 'not_published', 0, '', '', 1, 3, 1, '#C0C0C0', '', 0, 0),
(11, 3, 'errmsg', 3, '', '', 0, 3, 1, '', '', 0, 0),
(12, 3, 'faq', 4, '', '', 0, 4, 1, '', '', 0, 0),
(13, 3, 'fix', 5, '', '', 0, 5, 1, '', '', 0, 0),
(14, 3, 'hotfix', 6, '', '', 0, 6, 1, '', '', 0, 0),
(15, 3, 'howto', 7, '', '', 0, 7, 1, '', '', 0, 0),
(16, 3, 'info', 8, '', '', 0, 8, 1, '', '', 0, 0),
(17, 3, 'prb', 9, '', '', 0, 9, 1, '', '', 0, 0),
(20, 4, 'not_active', 0, '', '', 1, 3, 1, '#C0C0C0', '', 0, 0),
(21, 4, 'active', 1, '', '', 1, 1, 1, '#7898C2', '', 1, 1),
(24, 5, 'default', 1, '', '', 1, 1, 1, '#000000', '', 0, 1),
(22, 4, 'approve', 2, '', '', 1, 2, 1, '#FF0000', '', 0, 0),
(23, 4, 'draft', 3, '', '', 1, 4, 0, '#808080', '', 0, 0),
(29, 6, 'new', 1, '', '', 1, 1, 0, '#FF0000', '', 0, 0),
(30, 6, 'ignore', 2, '', '', 1, 2, 1, '#C0C0C0', '', 0, 0),
(31, 6, 'progress', 3, '', '', 1, 3, 1, '#FFFF00', '', 0, 0),
(32, 6, 'processed', 4, '', '', 1, 4, 1, '#7898C2', '', 0, 0),
(34, 7, 'not_published', 0, '', '', 1, 4, 1, '#C0C0C0', '', 0, 0),
(35, 7, 'published', 1, '', '', 1, 1, 1, '#7898C2', '', 1, 1),
(36, 7, 'approve', 2, '', '', 1, 2, 1, '#FF0000', '', 0, 0),
(37, 7, 'draft', 3, '', '', 1, 3, 1, '#808080', '', 0, 0),
(51, 2, 'outdated', 4, '', '', 1, 5, 0, '#FFFF00', '', 1, 0),
(50, 1, 'outdated', 4, '', '', 1, 3, 0, '#FFFF00', '', 1, 0),
(39, 8, 'published', 1, '', '', 1, 1, 1, '#7898C2', '', 1, 0),
(40, 8, 'closed', 2, '', '', 1, 2, 1, '#000000', '', 1, 0);

--
CREATE TABLE IF NOT EXISTS `kbp_log_cron` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date_started` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_finished` timestamp NULL DEFAULT NULL,
  `magic` tinyint(3) UNSIGNED NOT NULL,
  `output` text,
  `exitcode` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `magic` (`magic`)
) ENGINE=MyISAM  ;

--
CREATE TABLE IF NOT EXISTS `kbp_log_login` (
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_login` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `login_type` tinyint(4) NOT NULL DEFAULT '0',
  `user_ip` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `username` varchar(50) NOT NULL,
  `output` text NOT NULL,
  `exitcode` tinyint(4) NOT NULL DEFAULT '0',
  `active` tinyint(4) NOT NULL DEFAULT '1',
  KEY `user_id` (`user_id`),
  KEY `user_ip` (`user_ip`),
  KEY `username` (`username`(3))
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_log_search` (
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_search` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `search_type` tinyint(4) NOT NULL DEFAULT '0',
  `search_option` text NOT NULL,
  `search_string` varchar(255) NOT NULL,
  `user_ip` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `exitcode` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=MyISAM ;

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
CREATE TABLE IF NOT EXISTS `kbp_log_trigger` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `trigger_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `trigger_type` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `entry_type` tinyint(3) NOT NULL DEFAULT '0',
  `date_executed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `output` text NOT NULL,
  `exitcode` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  ;

--
CREATE TABLE IF NOT EXISTS `kbp_news` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `author_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `updater_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_posted` datetime NOT NULL,
  `title` text NOT NULL,
  `body` mediumtext NOT NULL,
  `body_index` mediumtext NOT NULL,
  `meta_keywords` text NOT NULL,
  `hits` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `private` tinyint(4) NOT NULL DEFAULT '0',
  `place_top_date` date DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `date_posted` (`date_posted`),
  KEY `date_updated` (`date_updated`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_news_custom_data` (
  `entry_id` int(10) NOT NULL DEFAULT '0',
  `field_id` int(10) NOT NULL DEFAULT '0',
  `data` text NOT NULL,
  PRIMARY KEY (`entry_id`,`field_id`),
  KEY `field_id` (`field_id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_priv` (
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `priv_name_id` smallint(6) NOT NULL DEFAULT '0',
  `grantor` int(11) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`priv_name_id`),
  KEY `name_priv_id` (`priv_name_id`)
) ENGINE=MyISAM ;

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
CREATE TABLE IF NOT EXISTS `kbp_priv_name` (
  `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `description` text,
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  ;

--
INSERT INTO `kbp_priv_name` (`id`, `name`, `description`, `editable`, `sort_order`, `active`) VALUES
(1, '', NULL, 0, 1, 1),
(2, '', NULL, 1, 2, 1),
(3, '', NULL, 1, 3, 1),
(4, '', NULL, 1, 4, 1),
(5, '', NULL, 1, 5, 1);

--
CREATE TABLE IF NOT EXISTS `kbp_priv_rule` (
  `priv_name_id` smallint(6) NOT NULL DEFAULT '0',
  `priv_module_id` smallint(6) NOT NULL DEFAULT '0',
  `what_priv` text NOT NULL,
  `status_priv` text NOT NULL,
  `optional_priv` varchar(256) NOT NULL,
  `apply_to_child` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`priv_name_id`,`priv_module_id`),
  KEY `priv_name_id` (`priv_name_id`)
) ENGINE=MyISAM ;

--
INSERT INTO `kbp_priv_rule` (`priv_name_id`, `priv_module_id`, `what_priv`, `status_priv`, `optional_priv`, `apply_to_child`, `active`) VALUES
(1, 0, 'select,insert,update,status,delete', '', '', 0, 1),
(3, 301, 'select,insert,update,status,delete', '', '', 0, 1),
(3, 101, 'select,insert,update,status,delete', '', '', 0, 1),
(4, 201, 'self_select,insert,self_update,self_status,self_delete', '', '', 0, 1),
(3, 108, 'select,insert,update,status,delete', '', '', 0, 1),
(3, 105, 'select,insert,update,status,delete', '', '', 0, 1),
(3, 103, 'select,insert,update,status,delete', '', '', 0, 1),
(3, 104, 'select,insert,update,status,delete', '', '', 0, 1),
(3, 203, 'select,insert,update,status,delete', '', '', 0, 1),
(4, 103, 'self_select,insert,self_update,self_status,self_delete', '', '', 0, 1),
(4, 104, 'self_select,insert,self_update,self_status,self_delete', '', '', 0, 1),
(2, 2, 'select', '', '', 1, 1),
(2, 134, 'select', '', '', 0, 1),
(2, 130, 'select', '', '', 0, 1),
(2, 131, 'select', '', '', 0, 1),
(2, 220, 'select', '', '', 1, 1),
(23, 200, 'select,insert', '', '', 1, 1),
(2, 6, 'select', '', '', 1, 1),
(3, 201, 'select,insert,update,status,delete', '', '', 0, 1),
(4, 108, 'self_select,insert,self_update,self_status,self_delete', '', '', 0, 1),
(5, 201, 'self_select,insert,self_update', '', 'a:2:{s:6:"insert";a:1:{i:0;s:5:"draft";}s:6:"update";a:1:{i:0;s:5:"draft";}}', 0, 1),
(4, 101, 'self_select,insert,self_update,self_status,self_delete', '', '', 0, 1),
(5, 203, 'self_select,insert,self_update,self_status,self_delete', '', '', 0, 1),
(4, 203, 'self_select,insert,self_update,self_status,self_delete', '', '', 0, 1),
(5, 103, 'self_select,insert,self_update,self_status,self_delete', '', '', 0, 1),
(3, 8, 'select,insert,update,status,delete', '', '', 1, 1),
(3, 10, 'select', '', '', 0, 1),
(3, 11, 'select', '', '', 0, 1),
(2, 400, 'select', '', '', 1, 1),
(23, 201, 'select', '', 'a:0:{}', 0, 1),
(23, 103, 'select,insert', '', '', 0, 1),
(23, 100, 'select,insert', '', '', 1, 1),
(22, 203, 'self_select,insert,self_update,self_delete', '', '', 0, 1),
(22, 205, 'select,insert', '', 'a:1:{s:6:"insert";a:1:{i:0;s:5:"draft";}}', 0, 1),
(22, 2207, 'select', '', '', 0, 1),
(5, 101, 'self_select,insert,self_update', '', 'a:2:{s:6:"insert";a:1:{i:0;s:5:"draft";}s:6:"update";a:1:{i:0;s:5:"draft";}}', 0, 1),
(22, 201, 'select,update', '', 'a:1:{s:6:"update";a:1:{i:0;s:5:"draft";}}', 0, 1),
(22, 103, 'self_select,insert,self_update,self_delete', '', '', 0, 1),
(22, 101, 'select,insert,update,status,delete', '', '', 0, 1),
(22, 100, 'select,insert', '', '', 1, 1),
(2, 15, 'select,insert,update,status,delete', '', '', 0, 1),
(2, 12, 'select', '', '', 0, 1),
(2, 11, 'select,insert,update,status,delete', '', '', 0, 1),
(2, 14, 'select', '', '', 0, 1),
(2, 8, 'select,insert,update,status,delete', '', '', 1, 1),
(2, 10, 'select,insert,self_update,self_status,self_delete', '', '', 0, 1),
(2, 600, 'select,insert,update,status,delete', '', '', 1, 1),
(2, 100, 'select,insert,update,status,delete', '', '', 1, 1),
(2, 200, 'select,insert,update,status,delete', '', '', 1, 1),
(2, 300, 'select,insert,update,status,delete', '', '', 1, 1),
(2, 9, 'select', '', '', 1, 1),
(2, 98, 'select,update,delete', '', '', 1, 1);

--
CREATE TABLE IF NOT EXISTS `kbp_report_entry` (
  `report_id` int(10) UNSIGNED NOT NULL,
  `entry_id` int(10) UNSIGNED NOT NULL,
  `date_day` date NOT NULL,
  `date_week` int(10) UNSIGNED NOT NULL,
  `date_month` int(10) UNSIGNED NOT NULL,
  `date_year` year(4) NOT NULL,
  `value_int` int(11) UNSIGNED NOT NULL,
  `prev_int` int(10) UNSIGNED NOT NULL,
  KEY `date_day` (`report_id`,`date_day`),
  KEY `date_week` (`report_id`,`date_week`),
  KEY `date_month` (`report_id`,`date_month`),
  KEY `date_year` (`report_id`,`date_year`),
  KEY `entry_id` (`entry_id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_report_search` (
  `search_string` varchar(255) NOT NULL DEFAULT '',
  `search_num` int(10) UNSIGNED NOT NULL DEFAULT '0',
  KEY `search_string` (`search_string`(3))
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_report_summary` (
  `report_id` int(10) UNSIGNED NOT NULL,
  `date_day` date NOT NULL,
  `date_year` year(4) NOT NULL,
  `date_month` int(10) UNSIGNED NOT NULL,
  `value_int` int(11) UNSIGNED NOT NULL,
  `prev_int` int(10) UNSIGNED NOT NULL,
  KEY `report_id` (`report_id`),
  KEY `date_year` (`report_id`,`date_year`),
  KEY `date_month` (`report_id`,`date_month`),
  KEY `date_day` (`report_id`,`date_day`)
) ENGINE=MyISAM ;

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
CREATE TABLE IF NOT EXISTS `kbp_setting_to_value` (
  `setting_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `setting_value` text NOT NULL,
  PRIMARY KEY (`setting_id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_setting_to_value_user` (
  `setting_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_id` int(10) UNSIGNED NOT NULL,
  `setting_value` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`setting_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_stuff_category` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_stuff_data` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `data_key` varchar(30) NOT NULL DEFAULT '',
  `date_posted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `data_string` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `data_key` (`data_key`(3))
) ENGINE=MyISAM  ;

--
CREATE TABLE IF NOT EXISTS `kbp_stuff_entry` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `author_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `updater_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `filedata` longblob NOT NULL,
  `filename` varchar(255) NOT NULL DEFAULT '',
  `filesize` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `filetype` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_posted` datetime NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  KEY `filename` (`filename`(4)),
  KEY `category_id` (`category_id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_tag` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date_posted` datetime NOT NULL,
  `title` varchar(100) NOT NULL DEFAULT '',
  `description` text,
  `active` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `title` (`title`(3))
) ENGINE=MyISAM  ;

--
CREATE TABLE IF NOT EXISTS `kbp_tag_to_entry` (
  `entry_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `entry_type` tinyint(4) UNSIGNED NOT NULL DEFAULT '0',
  `tag_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`entry_id`,`entry_type`,`tag_id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_trigger` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `entry_type` tinyint(4) NOT NULL,
  `trigger_type` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `user_id` int(11) NOT NULL,
  `trigger_key` varchar(50) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL,
  `options` text NOT NULL,
  `cond_match` tinyint(4) NOT NULL,
  `cond` text NOT NULL,
  `action` text NOT NULL,
  `schedule` varchar(255) NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT '0',
  `active` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `entry_type` (`entry_type`)
) ENGINE=MyISAM  ;

--
CREATE TABLE IF NOT EXISTS `kbp_user` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `grantor_id` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `imported_user_id` varchar(50) DEFAULT NULL,
  `username` varchar(50) NOT NULL DEFAULT '',
  `password` varchar(150) NOT NULL DEFAULT '',
  `company_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `date_registered` datetime NOT NULL,
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `first_name` varchar(50) NOT NULL DEFAULT '',
  `middle_name` varchar(50) NOT NULL DEFAULT '',
  `last_name` varchar(100) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `phone` varchar(20) NOT NULL DEFAULT '',
  `phone_ext` varchar(10) NOT NULL DEFAULT '',
  `user_comment` text,
  `admin_comment` text,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `editable` tinyint(1) NOT NULL DEFAULT '0',
  `lastauth` int(10) UNSIGNED DEFAULT NULL,
  `lastpass` int(10) UNSIGNED DEFAULT NULL,
  `import_data` varchar(255) DEFAULT NULL,
  `address` varchar(255) NOT NULL DEFAULT '',
  `address2` varchar(255) NOT NULL DEFAULT '',
  `city` varchar(50) NOT NULL DEFAULT '',
  `state` varchar(2) NOT NULL DEFAULT '',
  `zip` varchar(20) NOT NULL DEFAULT '',
  `country` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`username`),
  UNIQUE KEY `imported_user_id` (`imported_user_id`),
  KEY `pass` (`password`(2)),
  KEY `company_id` (`company_id`),
  KEY `email` (`email`(3)),
  KEY `grantor_id` (`grantor_id`)
) ENGINE=MyISAM  ;

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
CREATE TABLE IF NOT EXISTS `kbp_user_auth_token` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(150) NOT NULL,
  `remote_token` varchar(32) NOT NULL,
  `date_expired` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  ;

--
CREATE TABLE IF NOT EXISTS `kbp_user_company` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `phone` varchar(30) NOT NULL DEFAULT '',
  `phone2` varchar(30) NOT NULL DEFAULT '',
  `fax` varchar(30) NOT NULL DEFAULT '',
  `address` varchar(255) NOT NULL DEFAULT '',
  `address2` varchar(255) NOT NULL DEFAULT '',
  `city` varchar(30) NOT NULL DEFAULT '',
  `state` varchar(2) NOT NULL DEFAULT '',
  `zip` varchar(11) NOT NULL DEFAULT '',
  `country` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `url` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `custom` longtext,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  ;

--
CREATE TABLE IF NOT EXISTS `kbp_user_extra` (
  `rule_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `value1` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `value2` text,
  `value3` text,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rule_id`,`user_id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_user_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `title` varchar(100) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`parent_id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=MyISAM  ;

--
INSERT INTO `kbp_user_role` (`id`, `parent_id`, `title`, `description`, `sort_order`, `active`) VALUES
(1, 0, 'Customers', '', 1, 1),
(2, 0, 'Employees', '', 2, 1),
(3, 0, 'Contractors', '', 3, 1),
(4, 7, 'Programmer', '', 1, 1),
(5, 7, 'Tech Writer', '', 2, 1),
(6, 7, 'Beta Tester', '', 3, 1),
(7, 3, 'Manager', '', 1, 1),
(8, 0, 'Partners', '', 4, 1);

--
CREATE TABLE IF NOT EXISTS `kbp_user_subscription` (
  `entry_id` int(10) UNSIGNED NOT NULL,
  `entry_type` tinyint(4) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `date_subscribed` datetime NOT NULL,
  `date_lastsent` datetime DEFAULT NULL,
  PRIMARY KEY (`entry_id`,`entry_type`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_user_temp` (
  `rule_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `user_ip` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `value_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `value1` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `value2` text,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rule_id`,`user_id`)
) ENGINE=MyISAM ;

--
CREATE TABLE IF NOT EXISTS `kbp_user_to_role` (
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `role_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM ;

--
ALTER TABLE `kbp_feedback` ADD FULLTEXT KEY `title` (`title`,`question`,`answer`);
--
ALTER TABLE `kbp_file_entry` ADD FULLTEXT KEY `title` (`title`,`filename_index`,`meta_keywords`,`description`,`filetext`);
--
ALTER TABLE `kbp_file_entry` ADD FULLTEXT KEY `title_only` (`title`,`filename_index`);
--
ALTER TABLE `kbp_kb_comment` ADD FULLTEXT KEY `comment` (`comment`);
--
ALTER TABLE `kbp_kb_entry` ADD FULLTEXT KEY `title_only` (`title`);
--
ALTER TABLE `kbp_kb_entry` ADD FULLTEXT KEY `meta_keywords` (`meta_keywords`);
--
ALTER TABLE `kbp_kb_entry` ADD FULLTEXT KEY `title` (`title`,`body_index`,`meta_keywords`,`meta_description`);
--
ALTER TABLE `kbp_kb_rating_feedback` ADD FULLTEXT KEY `comment` (`comment`);
--
ALTER TABLE `kbp_news` ADD FULLTEXT KEY `title` (`title`,`body_index`,`meta_keywords`);
--
ALTER TABLE `kbp_news` ADD FULLTEXT KEY `title_only` (`title`);
--
ALTER TABLE `kbp_stuff_entry` ADD FULLTEXT KEY `title` (`title`,`description`);
