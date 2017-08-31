-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 23, 2017 at 08:00 PM
-- Server version: 5.5.55-0ubuntu0.14.04.1
-- PHP Version: 5.5.9-1ubuntu4.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `kbp`
--

-- --------------------------------------------------------

--
-- Table structure for table `kbp_article_template`
--

CREATE TABLE IF NOT EXISTS `kbp_article_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_type` tinyint(4) NOT NULL DEFAULT '0',
  `tmpl_key` varchar(30) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `body` text NOT NULL,
  `description` text NOT NULL,
  `is_widget` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT '1',
  `private` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `tmpl_key` (`tmpl_key`(3))
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `kbp_article_template`
--

INSERT INTO `kbp_article_template` (`id`, `entry_type`, `tmpl_key`, `title`, `body`, `description`, `is_widget`, `sort_order`, `private`, `active`) VALUES
(1, 1, '', 'Page Content 1', '<h3>Sub title 1 here</h3>\r\n<h3>Sub title 2 here<br />\r\n</h3>\r\n<ol>\r\n    <li>item 1</li>\r\n    <li>item 2</li>\r\n    <li>item3</li>\r\n</ol>\r\n<h3>&nbsp;</h3>', 'Example of article format', 0, 1, 0, 1),
(2, 1, '', 'Info Box', '<div class="box yellowBox">type here</div>\r\n', 'Yellow box with borders', 1, 1, 0, 1),
(3, 1, '', 'Info Box 2', '<div class="box greyBox">type here</div>', 'Grey box with borders', 1, 1, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `kbp_custom_field`
--

CREATE TABLE IF NOT EXISTS `kbp_custom_field` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `input_id` tinyint(4) NOT NULL DEFAULT '0',
  `type_id` tinyint(4) NOT NULL DEFAULT '0',
  `range_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `tooltip` text NOT NULL,
  `caption` varchar(255) NOT NULL DEFAULT '',
  `default_value` varchar(255) NOT NULL DEFAULT '',
  `is_required` tinyint(4) NOT NULL DEFAULT '0',
  `error_message` text NOT NULL,
  `valid_regexp` varchar(255) NOT NULL DEFAULT '',
  `position` tinyint(4) NOT NULL DEFAULT '0',
  `display` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `html_template` text NOT NULL,
  `is_search` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `active` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `range_id` (`range_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_custom_field_range`
--

CREATE TABLE IF NOT EXISTS `kbp_custom_field_range` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_custom_field_range_value`
--

CREATE TABLE IF NOT EXISTS `kbp_custom_field_range_value` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `range_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `sort_order` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_custom_field_to_category`
--

CREATE TABLE IF NOT EXISTS `kbp_custom_field_to_category` (
  `field_id` int(11) unsigned NOT NULL,
  `category_id` int(11) unsigned NOT NULL DEFAULT '0',
  KEY `field_id` (`field_id`,`category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_data_to_user_value`
--

CREATE TABLE IF NOT EXISTS `kbp_data_to_user_value` (
  `rule_id` int(11) NOT NULL DEFAULT '0',
  `data_value` int(11) NOT NULL DEFAULT '0',
  `user_value` int(11) NOT NULL DEFAULT '0',
  KEY `rule_id` (`rule_id`),
  KEY `data_value` (`data_value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_data_to_user_value_string`
--

CREATE TABLE IF NOT EXISTS `kbp_data_to_user_value_string` (
  `rule_id` int(11) NOT NULL DEFAULT '0',
  `data_value` int(11) NOT NULL DEFAULT '0',
  `user_value` varchar(255) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rule_id`,`data_value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_email_pool`
--

CREATE TABLE IF NOT EXISTS `kbp_email_pool` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `letter_type` tinyint(4) NOT NULL DEFAULT '0',
  `message` text NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_sent` timestamp NULL DEFAULT NULL,
  `failed` smallint(5) unsigned NOT NULL DEFAULT '0',
  `failed_message` text,
  `status` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_entry_autosave`
--

CREATE TABLE IF NOT EXISTS `kbp_entry_autosave` (
  `id_key` varchar(32) NOT NULL,
  `entry_id` int(10) unsigned NOT NULL,
  `entry_type` tinyint(4) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `entry_obj` longtext NOT NULL,
  `date_saved` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  KEY `entry_id` (`entry_id`,`entry_type`),
  KEY `id_key` (`id_key`(3))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_entry_draft`
--

CREATE TABLE IF NOT EXISTS `kbp_entry_draft` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned NOT NULL DEFAULT '0',
  `entry_type` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `author_id` int(10) unsigned NOT NULL DEFAULT '0',
  `updater_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` text NOT NULL,
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_posted` datetime NOT NULL,
  `entry_obj` mediumtext NOT NULL,
  `private` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`,`entry_type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_entry_draft_to_category`
--

CREATE TABLE IF NOT EXISTS `kbp_entry_draft_to_category` (
  `draft_id` int(10) unsigned NOT NULL DEFAULT '0',
  `category_id` int(10) unsigned NOT NULL DEFAULT '0',
  KEY `entry_id` (`draft_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_entry_draft_workflow`
--

CREATE TABLE IF NOT EXISTS `kbp_entry_draft_workflow` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `draft_id` int(10) unsigned NOT NULL DEFAULT '0',
  `workflow_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL,
  `date_posted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `step_num` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `step_title` varchar(255) NOT NULL DEFAULT '',
  `comment` text,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `draft_id` (`draft_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_entry_draft_workflow_history`
--

CREATE TABLE IF NOT EXISTS `kbp_entry_draft_workflow_history` (
  `draft_id` int(10) unsigned NOT NULL DEFAULT '0',
  `entry_id` int(10) unsigned NOT NULL DEFAULT '0',
  `entry_type` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL,
  `date_posted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `step_num` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `step_title` varchar(255) NOT NULL DEFAULT '',
  `comment` text,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  KEY `draft_id` (`draft_id`),
  KEY `entry_id` (`entry_id`,`entry_type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_entry_draft_workflow_to_assignee`
--

CREATE TABLE IF NOT EXISTS `kbp_entry_draft_workflow_to_assignee` (
  `draft_id` int(11) unsigned NOT NULL DEFAULT '0',
  `draft_workflow_id` int(11) unsigned NOT NULL DEFAULT '0',
  `assignee_id` int(11) unsigned NOT NULL DEFAULT '0',
  KEY `draft_id` (`draft_id`) USING BTREE,
  KEY `draft_workflow_id` (`draft_workflow_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_entry_featured`
--

CREATE TABLE IF NOT EXISTS `kbp_entry_featured` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_type` tinyint(3) unsigned NOT NULL,
  `entry_id` int(10) unsigned NOT NULL,
  `category_id` int(10) unsigned NOT NULL DEFAULT '0',
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `entry_type` (`entry_type`,`category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_entry_hits`
--

CREATE TABLE IF NOT EXISTS `kbp_entry_hits` (
  `entry_id` int(10) unsigned NOT NULL,
  `entry_type` tinyint(4) NOT NULL,
  `hits` int(10) unsigned NOT NULL DEFAULT '0',
  `date_hit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`entry_id`,`entry_type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_entry_lock`
--

CREATE TABLE IF NOT EXISTS `kbp_entry_lock` (
  `entry_id` int(10) unsigned NOT NULL,
  `entry_type` tinyint(4) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `date_locked` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `reason_locked` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`entry_id`,`entry_type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='locked records, mostly opened by editing or by some other re';

-- --------------------------------------------------------

--
-- Table structure for table `kbp_entry_rule`
--

CREATE TABLE IF NOT EXISTS `kbp_entry_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_type` tinyint(3) unsigned NOT NULL,
  `directory` varchar(255) NOT NULL DEFAULT '',
  `parse_child` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `is_draft` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `description` text NOT NULL,
  `date_executed` datetime DEFAULT NULL,
  `entry_obj` text NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `entry_type` (`entry_type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_entry_schedule`
--

CREATE TABLE IF NOT EXISTS `kbp_entry_schedule` (
  `entry_id` int(10) unsigned NOT NULL,
  `entry_type` tinyint(4) NOT NULL,
  `num` tinyint(4) NOT NULL DEFAULT '1',
  `date_scheduled` datetime NOT NULL,
  `value` tinyint(3) unsigned NOT NULL,
  `note` text,
  `notify` varchar(255) NOT NULL DEFAULT '1',
  `active` tinyint(4) NOT NULL DEFAULT '1',
  KEY `entry_id` (`entry_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_entry_task`
--

CREATE TABLE IF NOT EXISTS `kbp_entry_task` (
  `rule_id` tinyint(4) NOT NULL,
  `entry_id` int(10) unsigned NOT NULL,
  `entry_type` tinyint(4) NOT NULL DEFAULT '0',
  `value1` mediumtext,
  `value2` mediumtext,
  `failed` smallint(5) unsigned NOT NULL DEFAULT '0',
  `failed_message` text,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rule_id`,`entry_id`,`entry_type`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='keep sheduled task for entries';

-- --------------------------------------------------------

--
-- Table structure for table `kbp_entry_trash`
--

CREATE TABLE IF NOT EXISTS `kbp_entry_trash` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned NOT NULL,
  `entry_type` tinyint(4) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `entry_obj` longtext NOT NULL,
  `date_deleted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_export`
--

CREATE TABLE IF NOT EXISTS `kbp_export` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `export_type` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `filetype` varchar(100) NOT NULL,
  `export_option` mediumtext NOT NULL,
  `active` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_export_data`
--

CREATE TABLE IF NOT EXISTS `kbp_export_data` (
  `export_id` int(10) NOT NULL DEFAULT '0',
  `export_type` tinyint(4) unsigned NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL,
  `export_data` longblob NOT NULL,
  `export_result` text NOT NULL,
  `content_type` varchar(100) NOT NULL,
  KEY `export_id` (`export_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_feedback`
--

CREATE TABLE IF NOT EXISTS `kbp_feedback` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0',
  `subject_id` int(10) unsigned NOT NULL DEFAULT '0',
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
  KEY `subject_id` (`subject_id`),
  FULLTEXT KEY `title` (`title`,`question`,`answer`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_feedback_custom_data`
--

CREATE TABLE IF NOT EXISTS `kbp_feedback_custom_data` (
  `entry_id` int(10) NOT NULL DEFAULT '0',
  `field_id` int(10) NOT NULL DEFAULT '0',
  `data` text NOT NULL,
  PRIMARY KEY (`entry_id`,`field_id`),
  KEY `field_id` (`field_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_file_category`
--

CREATE TABLE IF NOT EXISTS `kbp_file_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `attachable` tinyint(1) DEFAULT '1',
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `num_entry` smallint(5) unsigned NOT NULL DEFAULT '0',
  `private` tinyint(1) NOT NULL DEFAULT '0',
  `active_real` tinyint(1) NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `sort_order` (`sort_order`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_file_custom_data`
--

CREATE TABLE IF NOT EXISTS `kbp_file_custom_data` (
  `entry_id` int(10) NOT NULL DEFAULT '0',
  `field_id` int(10) NOT NULL DEFAULT '0',
  `data` text NOT NULL,
  PRIMARY KEY (`entry_id`,`field_id`),
  KEY `field_id` (`field_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_file_entry`
--

CREATE TABLE IF NOT EXISTS `kbp_file_entry` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL DEFAULT '0',
  `author_id` int(10) unsigned NOT NULL DEFAULT '0',
  `updater_id` int(10) unsigned NOT NULL DEFAULT '0',
  `directory` varchar(255) NOT NULL DEFAULT '',
  `sub_directory` varchar(255) NOT NULL DEFAULT '',
  `filename` varchar(255) NOT NULL DEFAULT '',
  `filename_disk` varchar(256) NOT NULL,
  `filename_index` varchar(256) NOT NULL,
  `meta_keywords` text NOT NULL,
  `filesize` int(10) unsigned NOT NULL DEFAULT '0',
  `filetype` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `description_full` text NOT NULL,
  `comment` text NOT NULL,
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_posted` datetime NOT NULL,
  `downloads` int(10) unsigned NOT NULL DEFAULT '0',
  `filetext` mediumtext NOT NULL,
  `md5hash` varchar(32) NOT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT '1',
  `private` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  KEY `filename` (`filename`(4)),
  KEY `updater_id` (`updater_id`),
  KEY `category_id` (`category_id`),
  KEY `downloads` (`downloads`),
  KEY `date_updated` (`date_updated`),
  FULLTEXT KEY `title` (`title`,`filename_index`,`meta_keywords`,`description`,`filetext`),
  FULLTEXT KEY `title_only` (`title`,`filename_index`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='images per item' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_file_entry_to_category`
--

CREATE TABLE IF NOT EXISTS `kbp_file_entry_to_category` (
  `entry_id` int(10) unsigned NOT NULL DEFAULT '0',
  `category_id` int(10) unsigned NOT NULL DEFAULT '0',
  `is_main` tinyint(4) NOT NULL DEFAULT '1',
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`entry_id`,`category_id`),
  KEY `category_id` (`category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_kb_attachment_to_entry`
--

CREATE TABLE IF NOT EXISTS `kbp_kb_attachment_to_entry` (
  `entry_id` int(10) unsigned NOT NULL DEFAULT '0',
  `attachment_id` int(10) unsigned NOT NULL DEFAULT '0',
  `attachment_type` tinyint(1) NOT NULL DEFAULT '0',
  KEY `entry_id` (`entry_id`),
  KEY `attachment_id` (`attachment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_kb_category`
--

CREATE TABLE IF NOT EXISTS `kbp_kb_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `num_entry` smallint(5) unsigned NOT NULL DEFAULT '0',
  `commentable` tinyint(1) NOT NULL DEFAULT '1',
  `ratingable` tinyint(1) NOT NULL DEFAULT '1',
  `category_type` tinyint(1) NOT NULL DEFAULT '1',
  `private` tinyint(1) NOT NULL DEFAULT '0',
  `active_real` tinyint(1) NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `sort_order` (`sort_order`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `kbp_kb_category`
--

INSERT INTO `kbp_kb_category` (`id`, `parent_id`, `name`, `description`, `sort_order`, `num_entry`, `commentable`, `ratingable`, `category_type`, `private`, `active_real`, `active`) VALUES
(1, 0, 'KBPublisher Introduction', '', 0, 0, 1, 1, 1, 0, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `kbp_kb_comment`
--

CREATE TABLE IF NOT EXISTS `kbp_kb_comment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(50) NOT NULL DEFAULT '',
  `comment` text NOT NULL,
  `date_posted` datetime NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`),
  KEY `NewIndex` (`user_id`),
  FULLTEXT KEY `comment` (`comment`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_kb_custom_data`
--

CREATE TABLE IF NOT EXISTS `kbp_kb_custom_data` (
  `entry_id` int(10) NOT NULL DEFAULT '0',
  `field_id` int(10) NOT NULL DEFAULT '0',
  `data` text NOT NULL,
  PRIMARY KEY (`entry_id`,`field_id`),
  KEY `field_id` (`field_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_kb_entry`
--

CREATE TABLE IF NOT EXISTS `kbp_kb_entry` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL DEFAULT '0',
  `author_id` int(10) unsigned NOT NULL DEFAULT '0',
  `updater_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` text NOT NULL,
  `body` mediumtext NOT NULL,
  `body_index` mediumtext NOT NULL,
  `url_title` varchar(255) NOT NULL DEFAULT '',
  `meta_keywords` text NOT NULL,
  `meta_description` text NOT NULL,
  `entry_type` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `external_link` text NOT NULL,
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_posted` datetime NOT NULL,
  `date_commented` timestamp NULL DEFAULT NULL,
  `history_comment` text,
  `hits` int(10) unsigned NOT NULL DEFAULT '0',
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT '1',
  `private` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `updater_id` (`updater_id`),
  KEY `author_id` (`author_id`),
  KEY `entry_type` (`entry_type`),
  KEY `category_id` (`category_id`),
  KEY `hits` (`hits`),
  KEY `date_updated` (`date_updated`),
  FULLTEXT KEY `title_only` (`title`),
  FULLTEXT KEY `meta_keywords` (`meta_keywords`),
  FULLTEXT KEY `title` (`title`,`body_index`,`meta_keywords`,`meta_description`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `kbp_kb_entry`
--

INSERT INTO `kbp_kb_entry` (`id`, `category_id`, `author_id`, `updater_id`, `title`, `body`, `body_index`, `url_title`, `meta_keywords`, `meta_description`, `entry_type`, `external_link`, `date_updated`, `date_posted`, `date_commented`, `history_comment`, `hits`, `sort_order`, `private`, `active`) VALUES
(1, 1, 1, 1, 'Getting Started', '<p>This article explains how you can create an article, news, user in KBPublisher.</p>\r\n\r\n<p>If you not signed in yet,&nbsp;Click&nbsp;<strong>Sign in</strong>&nbsp;at&nbsp;the top right corner, fill the fields "Username" and "Password" and click&nbsp;<strong>OK.</strong><br />\r\n&nbsp;</p>\r\n\r\n<h3 class="lineTitle">Create an Article</h3>\r\n\r\n<ol>\r\n	<li>Click <svg class="tmIcon" fill="#999" viewbox="0 0 24 24" width="18" xmlns="http://www.w3.org/2000/svg"><path d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm0-2c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm6 13h-5v5h-2v-5h-5v-2h5v-5h2v5h5v2z"></path></svg>&nbsp;at&nbsp;the top right corner&nbsp;and choose&nbsp;<strong>Add Article Here</strong> from the popup&nbsp;menu.</li>\r\n	<li>Fill "Title",&nbsp;"Category" and "Article" fields and click <strong>Save.</strong>&nbsp;</li>\r\n	<li>See&nbsp;<a href="http://www.kbpublisher.com/kb/the-article-input-screen-explained_40.html">The Article Input Screen Explained</a>&nbsp;article for details.</li>\r\n</ol>\r\n\r\n<h3 class="lineTitle">Create a News</h3>\r\n\r\n<ol>\r\n	<li>Click <svg class="tmIcon" fill="#999" viewbox="0 0 24 24" width="18" xmlns="http://www.w3.org/2000/svg"><path d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm0-2c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm6 13h-5v5h-2v-5h-5v-2h5v-5h2v5h5v2z"></path></svg>&nbsp;at&nbsp;the top right corner&nbsp;and choose <strong>Add News</strong> from the popup&nbsp;menu.</li>\r\n	<li>Fill "Date", "Title" and "Body" fields and click&nbsp;<strong>Save.</strong></li>\r\n	<li>See&nbsp;<a href="http://www.kbpublisher.com/kb/the-news-input-screen-explained_242.html">The News Input Screen Explained</a>&nbsp;article for details.</li>\r\n</ol>\r\n\r\n<h3 class="lineTitle">Add a File</h3>\r\n\r\n<ol>\r\n	<li>Click <svg class="tmIcon" fill="#999" viewbox="0 0 24 24" width="18" xmlns="http://www.w3.org/2000/svg"><path d="M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm0-2c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm6 13h-5v5h-2v-5h-5v-2h5v-5h2v5h5v2z"></path></svg>&nbsp;at&nbsp;the top right corner&nbsp;and choose <strong>Add File</strong> from the popup&nbsp;menu.</li>\r\n	<li>Fill "File" (choose&nbsp;file from disk),&nbsp;"Category" and click&nbsp;<strong>Save</strong></li>\r\n	<li>See&nbsp;<a href="http://www.kbpublisher.com/kb/add-a-file_65.html">Add a File</a>&nbsp;article for details</li>\r\n</ol>\r\n\r\n<h3 class="lineTitle">Add a User</h3>\r\n\r\n<ol>\r\n	<li>Click <svg class="tmIcon" fill="#999" viewbox="0 0 24 24" width="18" xmlns="http://www.w3.org/2000/svg"><path d="M24 14.187v-4.374c-2.148-.766-2.726-.802-3.027-1.529-.303-.729.083-1.169 1.059-3.223l-3.093-3.093c-2.026.963-2.488 1.364-3.224 1.059-.727-.302-.768-.889-1.527-3.027h-4.375c-.764 2.144-.8 2.725-1.529 3.027-.752.313-1.203-.1-3.223-1.059l-3.093 3.093c.977 2.055 1.362 2.493 1.059 3.224-.302.727-.881.764-3.027 1.528v4.375c2.139.76 2.725.8 3.027 1.528.304.734-.081 1.167-1.059 3.223l3.093 3.093c1.999-.95 2.47-1.373 3.223-1.059.728.302.764.88 1.529 3.027h4.374c.758-2.131.799-2.723 1.537-3.031.745-.308 1.186.099 3.215 1.062l3.093-3.093c-.975-2.05-1.362-2.492-1.059-3.223.3-.726.88-.763 3.027-1.528zm-4.875.764c-.577 1.394-.068 2.458.488 3.578l-1.084 1.084c-1.093-.543-2.161-1.076-3.573-.49-1.396.581-1.79 1.693-2.188 2.877h-1.534c-.398-1.185-.791-2.297-2.183-2.875-1.419-.588-2.507-.045-3.579.488l-1.083-1.084c.557-1.118 1.066-2.18.487-3.58-.579-1.391-1.691-1.784-2.876-2.182v-1.533c1.185-.398 2.297-.791 2.875-2.184.578-1.394.068-2.459-.488-3.579l1.084-1.084c1.082.538 2.162 1.077 3.58.488 1.392-.577 1.785-1.69 2.183-2.875h1.534c.398 1.185.792 2.297 2.184 2.875 1.419.588 2.506.045 3.579-.488l1.084 1.084c-.556 1.121-1.065 2.187-.488 3.58.577 1.391 1.689 1.784 2.875 2.183v1.534c-1.188.398-2.302.791-2.877 2.183zm-7.125-5.951c1.654 0 3 1.346 3 3s-1.346 3-3 3-3-1.346-3-3 1.346-3 3-3zm0-2c-2.762 0-5 2.238-5 5s2.238 5 5 5 5-2.238 5-5-2.238-5-5-5z"></path></svg> at&nbsp;the top right corner.</li>\r\n	<li>Go to&nbsp;<strong>Users</strong>&nbsp;and then click&nbsp;<strong>Add New</strong></li>\r\n	<li>Fill required fields (marked with *) and click&nbsp;<strong>Save.</strong>&nbsp;</li>\r\n	<li>See&nbsp;<a href="http://www.kbpublisher.com/kb/the-user-input-screen-explained_73.html">The User Input Screen Explained</a>&nbsp;article for details.</li>\r\n</ol>\r\n', 'This article explains how you can create an article, news, user in KBPublisher. If you not signed in yet, Click Sign in at the top right corner, fill the fields "Username" and "Password" and click OK. Create an Article Click at the top right corner and choose Add Article Here from the popup menu. Fill "Title", "Category" and "Article" fields and click Save. See The Article Input Screen Explained article for details. Create a News Click at the top right corner and choose Add News from the popup menu. Fill "Date", "Title" and "Body" fields and click Save. See The News Input Screen Explained article for details. Add a File Click at the top right corner and choose Add File from the popup menu. Fill "File" (choose file from disk), "Category" and click Save See Add a File article for details Add a User Click at the top right corner. Go to Users and then click Add New Fill required fields (marked with *) and click Save. See The User Input Screen Explained article for details.', '', '', '', 0, '', '2017-08-22 14:30:54', '2017-08-22 20:00:54', '0000-00-00 00:00:00', '', 0, 1, 0, 1);
INSERT INTO `kbp_kb_entry` (`id`, `category_id`, `author_id`, `updater_id`, `title`, `body`, `body_index`, `url_title`, `meta_keywords`, `meta_description`, `entry_type`, `external_link`, `date_updated`, `date_posted`, `date_commented`, `history_comment`, `hits`, `sort_order`, `private`, `active`) VALUES
(2, 1, 1, 1, 'Set up a KBPublisher view', '<p>There are 3 different views for KBPublisher, Left Menu, Browsable and Intranet.<br />\r\nLeft Menu is set by default. See below.&nbsp;</p>\r\n\r\n<h3 class="lineTitle">Left Menu</h3>\r\n\r\n<p><img alt="" src="data:image/gif;base64,R0lGODlhWAJ2AdUAAGJgYmOUxdPS1bCxsm1glouNkZBuXrjJ2pCy0yFwvNDZ7LOzzpaFeMW5rpBqsY+YqNrp9hIQG9PJv+naz/Tp2ezs+M201f/46uz4/GEvk5mns6eYjrGYyrGmm6qGvSksQfnu6VZa9kgqJE8MezE/8Q8K8kBHV+De4be59JGp/JeExVJHQn6K+vfv9zcAe6Wd9Xh8fzo7O+/v7/f39+fn5///9vb////39/f3///3/+fn8Ozs7Orq6vT09EWEwf///yH/C1hNUCBEYXRhWE1QPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4gPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS4zLWMwMTEgNjYuMTQ1NjYxLCAyMDEyLzAyLzA2LTE0OjU2OjI3ICAgICAgICAiPiA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPiA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIiB4bWxuczpzdFJlZj0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3NUeXBlL1Jlc291cmNlUmVmIyIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ1M2IChNYWNpbnRvc2gpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOkVBRDMxREY5MTdBMDExRTdBQjlGQTc2MTA5OUZFRjg1IiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOkVBRDMxREZBMTdBMDExRTdBQjlGQTc2MTA5OUZFRjg1Ij4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6RUFEMzFERjcxN0EwMTFFN0FCOUZBNzYxMDk5RkVGODUiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5k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" style="height: 296px; width: 400px;" /></p>\r\n\r\n<p></p>\r\n\r\n<h3 class="lineTitle">Intranet</h3>\r\n\r\n<p><img alt="" src="data:image/gif;base64,R0lGODlhWAJ8AdUAAKil2Pn8/e3u7WSCo1aNzbCws8fb8HVUorXH3ZxvWm9ucYqOkt7e3o9ssilyuZWFecm4qtvo9E1NUdXHt8e02MzNzfHo2o6XrBcUGezbzqaYjuz5/ZKovLOmmmAsk//666uNweju+1hh909XZSotQEcvKm9lWWRXUPru6UAAfFplcMnN3ACN5szW2y8/7ujb8NrOxREN8lBEOzk/SN3WydvH4F9cYvnu+e7452+673p+grq/wdjY2EWEwfX19f///yH/C1hNUCBEYXRhWE1QPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4gPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS4zLWMwMTEgNjYuMTQ1NjYxLCAyMDEyLzAyLzA2LTE0OjU2OjI3ICAgICAgICAiPiA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPiA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIiB4bWxuczpzdFJlZj0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3NUeXBlL1Jlc291cmNlUmVmIyIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ1M2IChNYWNpbnRvc2gpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOkZCNTE1ODUwMTdBMDExRTdBQjlGQTc2MTA5OUZFRjg1IiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOkZCNTE1ODUxMTdBMDExRTdBQjlGQTc2MTA5OUZFRjg1Ij4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6RUFEMzFERkYxN0EwMTFFN0FCOUZBNzYxMDk5RkVGODUiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5k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" style="width: 400px; height: 296px;" /><br />\r\n&nbsp;</p>\r\n\r\n<h3 class="lineTitle">Browsable</h3>\r\n\r\n<p><img alt="" src="data:image/gif;base64,R0lGODlhWAJ5AdUAANvZ3K6j2Pv/+mqCpMjc766wtHVQo1SMzYyPkrrG5W5vc45qsYhyZsiz2Me3q/Pn2BUUF46XrVBOT/n38dbJvdrp9yhwt+vb0mIvk5WGeaWZj7Gmm5Woue/4/evv+SkvPqqMwfrv6U9XaEctJ2hWS3FkWWFo+OXc60oFf1pmdQUA8TpAS1FCOTE+9Pjv+WJeYTAAejR7wFxfW3p/hO/v7+fn5vDv5ufv7ufn8fHn5/Hn8d3n5rzCxerq6kWEwf///yH/C1hNUCBEYXRhWE1QPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4gPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS4zLWMwMTEgNjYuMTQ1NjYxLCAyMDEyLzAyLzA2LTE0OjU2OjI3ICAgICAgICAiPiA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPiA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIiB4bWxuczpzdFJlZj0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3NUeXBlL1Jlc291cmNlUmVmIyIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ1M2IChNYWNpbnRvc2gpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOkVBRDMxREZEMTdBMDExRTdBQjlGQTc2MTA5OUZFRjg1IiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOkVBRDMxREZFMTdBMDExRTdBQjlGQTc2MTA5OUZFRjg1Ij4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6RUFEMzFERkIxN0EwMTFFN0FCOUZBNzYxMDk5RkVGODUiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5k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" style="height: 296px; width: 400px;" /><br />\r\n&nbsp;</p>\r\n\r\n<h3 class="lineTitle">To change view</h3>\r\n\r\n<ul>\r\n	<li>Sign in to the knowledgebase</li>\r\n	<li>Go the&nbsp;<strong>Admin Area</strong></li>\r\n	<li>Click on the&nbsp;<strong>Settings</strong>&nbsp;menu and choose the&nbsp;<strong>Public Area&nbsp;-&gt; Common</strong>&nbsp;tab</li>\r\n	<li>Select required view in the&nbsp;<strong>View format</strong>&nbsp;field</li>\r\n	<li>For Left Menu and Intranet views you can specify which menu type to use, set&nbsp;<strong>Left Menu Type</strong></li>\r\n	<li>Click&nbsp;<strong>Save&nbsp;</strong>to save the changes</li>\r\n	<li>Go to&nbsp;<strong>Public Area</strong>&nbsp;to see/test your new settings<br />\r\n	&nbsp;</li>\r\n</ul>\r\n\r\n<h3 class="lineTitle">To customize view</h3>\r\n\r\n<p>See below articles for details</p>\r\n\r\n<ul>\r\n	<li><a href="http://www.kbpublisher.com/kb/change-kbpublisher-look-and-feel-(design-integration)_217.html">Change KBPublisher Look and Feel</a></li>\r\n	<li><a href="http://www.kbpublisher.com/kb/customizing-article-display-options_101.html">Customizing article display options</a></li>\r\n	<li><a href="http://www.kbpublisher.com/kb/customizing-public-area_95.html">Customizing Public Area</a></li>\r\n</ul>\r\n', 'There are 3 different views for KBPublisher, Left Menu, Browsable and Intranet. Left Menu is set by default. See below. Left Menu Intranet Browsable To change view Sign in to the knowledgebase Go the Admin Area Click on the Settings menu and choose the Public Area -> Common tab Select required view in the View format field For Left Menu and Intranet views you can specify which menu type to use, set Left Menu Type Click Save to save the changes Go to Public Area to see/test your new settings To customize view See below articles for details Change KBPublisher Look and Feel Customizing article display options Customizing Public Area', '', '', '', 0, '', '2017-08-22 14:30:54', '2017-08-22 20:00:54', '0000-00-00 00:00:00', '', 0, 1, 0, 1);
INSERT INTO `kbp_kb_entry` (`id`, `category_id`, `author_id`, `updater_id`, `title`, `body`, `body_index`, `url_title`, `meta_keywords`, `meta_description`, `entry_type`, `external_link`, `date_updated`, `date_posted`, `date_commented`, `history_comment`, `hits`, `sort_order`, `private`, `active`) VALUES
(3, 1, 1, 1, 'Manuals, FAQ, HowTo', '<ul>\r\n	<li><a href="http://www.kbpublisher.com/kb/1/">User Manual</a></li>\r\n	<li><a href="http://www.kbpublisher.com/kb/50/">Developer Manual</a></li>\r\n	<li><a href="http://www.kbpublisher.com/kb/2/">FAQ</a></li>\r\n	<li><a href="http://www.kbpublisher.com/kb/55/">HowTo, Tips &amp; Tricks</a></li>\r\n</ul>\r\n', 'User Manual Developer Manual FAQ HowTo, Tips & Tricks', '', '', '', 0, '', '2017-08-22 14:30:54', '2017-08-22 20:00:54', '0000-00-00 00:00:00', NULL, 0, 1, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `kbp_kb_entry_history`
--

CREATE TABLE IF NOT EXISTS `kbp_kb_entry_history` (
  `entry_id` int(10) unsigned NOT NULL,
  `revision_num` tinyint(3) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `date_posted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `comment` text NOT NULL,
  `entry_data` mediumtext NOT NULL,
  `entry_updater_id` int(10) unsigned NOT NULL DEFAULT '0',
  `entry_date_updated` timestamp NULL DEFAULT NULL,
  KEY `entry_id` (`entry_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_kb_entry_to_category`
--

CREATE TABLE IF NOT EXISTS `kbp_kb_entry_to_category` (
  `entry_id` int(10) unsigned NOT NULL DEFAULT '0',
  `category_id` int(10) unsigned NOT NULL DEFAULT '0',
  `is_main` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`entry_id`,`category_id`),
  KEY `category_id` (`category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `kbp_kb_entry_to_category`
--

INSERT INTO `kbp_kb_entry_to_category` (`entry_id`, `category_id`, `is_main`, `sort_order`) VALUES
(1, 1, 1, 1),
(2, 1, 1, 2),
(3, 1, 1, 3);

-- --------------------------------------------------------

--
-- Table structure for table `kbp_kb_glossary`
--

CREATE TABLE IF NOT EXISTS `kbp_kb_glossary` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `phrase` varchar(100) NOT NULL DEFAULT '',
  `definition` text NOT NULL,
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `display_once` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_kb_rating`
--

CREATE TABLE IF NOT EXISTS `kbp_kb_rating` (
  `entry_id` int(10) unsigned NOT NULL DEFAULT '0',
  `votes` int(10) unsigned NOT NULL DEFAULT '0',
  `rate` bigint(20) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`entry_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_kb_rating_feedback`
--

CREATE TABLE IF NOT EXISTS `kbp_kb_rating_feedback` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `date_posted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `rating` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `comment` text NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`),
  FULLTEXT KEY `comment` (`comment`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_kb_related_to_entry`
--

CREATE TABLE IF NOT EXISTS `kbp_kb_related_to_entry` (
  `entry_id` int(10) unsigned NOT NULL DEFAULT '0',
  `related_entry_id` int(10) unsigned NOT NULL DEFAULT '0',
  `related_type` tinyint(1) NOT NULL DEFAULT '0',
  `related_ref` tinyint(1) NOT NULL DEFAULT '1',
  KEY `entry_id` (`entry_id`),
  KEY `related_entry_id` (`related_entry_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_letter_template`
--

CREATE TABLE IF NOT EXISTS `kbp_letter_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
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
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=38 ;

--
-- Dumping data for table `kbp_letter_template`
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

-- --------------------------------------------------------

--
-- Table structure for table `kbp_list`
--

CREATE TABLE IF NOT EXISTS `kbp_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `list_key` varchar(50) NOT NULL DEFAULT '',
  `title` varchar(50) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `predifined` tinyint(4) NOT NULL DEFAULT '0',
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `list_key` (`list_key`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;

--
-- Dumping data for table `kbp_list`
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

-- --------------------------------------------------------

--
-- Table structure for table `kbp_list_country`
--

CREATE TABLE IF NOT EXISTS `kbp_list_country` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(30) NOT NULL DEFAULT '',
  `iso2` varchar(2) NOT NULL DEFAULT '',
  `iso3` varchar(3) NOT NULL DEFAULT '',
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=240 ;

--
-- Dumping data for table `kbp_list_country`
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
(52, 'Cote D''Ivoire', 'CI', 'CIV', 0, 1),
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
(112, 'Korea, Democratic People''s Rep', 'KP', 'PRK', 0, 1),
(113, 'Korea, Republic of', 'KR', 'KOR', 0, 1),
(114, 'Kuwait', 'KW', 'KWT', 0, 1),
(115, 'Kyrgyzstan', 'KG', 'KGZ', 0, 1),
(116, 'Lao People''s Democratic Republ', 'LA', 'LAO', 0, 1),
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

-- --------------------------------------------------------

--
-- Table structure for table `kbp_list_value`
--

CREATE TABLE IF NOT EXISTS `kbp_list_value` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `list_id` int(10) unsigned NOT NULL DEFAULT '0',
  `list_key` varchar(50) NOT NULL DEFAULT '',
  `list_value` tinyint(4) NOT NULL DEFAULT '0',
  `title` varchar(50) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `predifined` tinyint(4) NOT NULL DEFAULT '0',
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `custom_1` text NOT NULL,
  `custom_2` text NOT NULL,
  `custom_3` int(11) NOT NULL DEFAULT '0',
  `custom_4` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `list_id` (`list_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=52 ;

--
-- Dumping data for table `kbp_list_value`
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

-- --------------------------------------------------------

--
-- Table structure for table `kbp_log_cron`
--

CREATE TABLE IF NOT EXISTS `kbp_log_cron` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date_started` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_finished` timestamp NULL DEFAULT NULL,
  `magic` tinyint(3) unsigned NOT NULL,
  `output` text,
  `exitcode` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `magic` (`magic`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_log_login`
--

CREATE TABLE IF NOT EXISTS `kbp_log_login` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `date_login` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `login_type` tinyint(4) NOT NULL DEFAULT '0',
  `user_ip` int(11) unsigned NOT NULL DEFAULT '0',
  `username` varchar(50) NOT NULL,
  `output` text NOT NULL,
  `exitcode` tinyint(4) NOT NULL DEFAULT '0',
  `active` tinyint(4) NOT NULL DEFAULT '1',
  KEY `user_id` (`user_id`),
  KEY `user_ip` (`user_ip`),
  KEY `username` (`username`(3))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `kbp_log_login`
--

INSERT INTO `kbp_log_login` (`user_id`, `date_login`, `login_type`, `user_ip`, `username`, `output`, `exitcode`, `active`) VALUES
(0, '2017-08-22 14:32:06', 1, 2130706433, 'lakshmi', '[2017-08-22 20:02:06] Initializing...\n[2017-08-22 20:02:06] Login failed! - Wrong username and/or password. (Username: lakshmi)\n[2017-08-22 20:02:06] Exit with the code: 2\n', 2, 1),
(0, '2017-08-22 14:32:16', 1, 2130706433, 'lakshmi', '[2017-08-22 20:02:16] Initializing...\n[2017-08-22 20:02:16] Login failed! - Wrong username and/or password. (Username: lakshmi)\n[2017-08-22 20:02:16] Exit with the code: 2\n', 2, 1),
(1, '2017-08-22 14:32:46', 1, 2130706433, 'lakshmi', '[2017-08-22 20:02:46] Initializing...\n[2017-08-22 20:02:46] Login successful\n[2017-08-22 20:02:46] Exit with the code: 1\n', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `kbp_log_search`
--

CREATE TABLE IF NOT EXISTS `kbp_log_search` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `date_search` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `search_type` tinyint(4) NOT NULL DEFAULT '0',
  `search_option` text NOT NULL,
  `search_string` varchar(255) NOT NULL,
  `user_ip` int(11) unsigned NOT NULL DEFAULT '0',
  `exitcode` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_log_sphinx`
--

CREATE TABLE IF NOT EXISTS `kbp_log_sphinx` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date_executed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `entry_type` tinyint(3) unsigned NOT NULL,
  `action_type` tinyint(3) unsigned NOT NULL,
  `output` text,
  `exitcode` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `action_type` (`action_type`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_log_trigger`
--

CREATE TABLE IF NOT EXISTS `kbp_log_trigger` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `trigger_id` int(10) unsigned NOT NULL DEFAULT '0',
  `trigger_type` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `entry_type` tinyint(3) NOT NULL DEFAULT '0',
  `date_executed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `output` text NOT NULL,
  `exitcode` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_news`
--

CREATE TABLE IF NOT EXISTS `kbp_news` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `author_id` int(10) unsigned NOT NULL DEFAULT '0',
  `updater_id` int(10) unsigned NOT NULL DEFAULT '0',
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_posted` datetime NOT NULL,
  `title` text NOT NULL,
  `body` mediumtext NOT NULL,
  `body_index` mediumtext NOT NULL,
  `meta_keywords` text NOT NULL,
  `hits` int(10) unsigned NOT NULL DEFAULT '0',
  `private` tinyint(4) NOT NULL DEFAULT '0',
  `place_top_date` date DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `date_posted` (`date_posted`),
  KEY `date_updated` (`date_updated`),
  FULLTEXT KEY `title` (`title`,`body_index`,`meta_keywords`),
  FULLTEXT KEY `title_only` (`title`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `kbp_news`
--

INSERT INTO `kbp_news` (`id`, `author_id`, `updater_id`, `date_updated`, `date_posted`, `title`, `body`, `body_index`, `meta_keywords`, `hits`, `private`, `place_top_date`, `active`) VALUES
(1, 1, 1, '2017-08-22 14:30:54', '2017-08-22 20:00:54', 'Welcome to KBPublisher!', '<p>Dear Sir or Madam,</p>\r\n\r\n<p>Thank your for your interest in KBPublisher.<br />\r\nBelow you can find some useful information to better understand KBPublisher.</p>\r\n\r\n<ul>\r\n	<li><a href="http://www.kbpublisher.com/kb/What-is-KBPublisher_118.html">What is&nbsp;KBPublisher</a></li>\r\n	<li><a href="http://www.kbpublisher.com/kb/Summary_121.html">Getting started</a></li>\r\n	<li><a href="http://www.kbpublisher.com/kb/78/">KBPublisher - HowTo, Tips &amp; Tricks</a></li>\r\n	<li><a href="http://www.kbpublisher.com/kb/103/">API</a><br />\r\n	&nbsp;</li>\r\n</ul>\r\n\r\n<p>If you have any questions about KBPublisher or have any<br />\r\ntechnical issues you need help with, please do not hesitate to contact us.</p>\r\n\r\n<p>You may contact us by opening a ticket through our client portal at<br />\r\nhttps://www.kbpublisher.com/client/ or by sending an email to support@kbpublisher.com</p>\r\n\r\n<p><br />\r\nAgain, thank you for your interest in KBPublisher. We look forward to serving you.</p>\r\n\r\n<p>Sincerely,<br />\r\nThe KBPublisher Team</p>\r\n\r\n<p></p>\r\n\r\n<p></p>\r\n\r\n<p></p>\r\n', 'Dear Customer, Thank your for your interest in KBPublisher. Below you can find some useful information to better understand KBPublisher. What is KBPublisher Getting started KBPublisher - HowTo, Tips & Tricks API If you have any questions about KBPublisher or have any technical issues you need help with, please do not hesitate to contact us. You may contact us by opening a ticket through our client portal at https://www.kbpublisher.com/client/ or by sending an email to support@kbpublisher.com Again, thank you for your interest in KBPublisher. We look forward to serving you. Sincerely, The KBPublisher Team', '', 0, 0, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `kbp_news_custom_data`
--

CREATE TABLE IF NOT EXISTS `kbp_news_custom_data` (
  `entry_id` int(10) NOT NULL DEFAULT '0',
  `field_id` int(10) NOT NULL DEFAULT '0',
  `data` text NOT NULL,
  PRIMARY KEY (`entry_id`,`field_id`),
  KEY `field_id` (`field_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_priv`
--

CREATE TABLE IF NOT EXISTS `kbp_priv` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `priv_name_id` smallint(6) NOT NULL DEFAULT '0',
  `grantor` int(11) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`priv_name_id`),
  KEY `name_priv_id` (`priv_name_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `kbp_priv`
--

INSERT INTO `kbp_priv` (`user_id`, `priv_name_id`, `grantor`, `timestamp`) VALUES
(1, 1, 1, '2017-08-22 14:30:54');

-- --------------------------------------------------------

--
-- Table structure for table `kbp_priv_module`
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
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `kbp_priv_module`
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

-- --------------------------------------------------------

--
-- Table structure for table `kbp_priv_name`
--

CREATE TABLE IF NOT EXISTS `kbp_priv_name` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `description` text,
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `kbp_priv_name`
--

INSERT INTO `kbp_priv_name` (`id`, `name`, `description`, `editable`, `sort_order`, `active`) VALUES
(1, '', NULL, 0, 1, 1),
(2, '', NULL, 1, 2, 1),
(3, '', NULL, 1, 3, 1),
(4, '', NULL, 1, 4, 1),
(5, '', NULL, 1, 5, 1);

-- --------------------------------------------------------

--
-- Table structure for table `kbp_priv_rule`
--

CREATE TABLE IF NOT EXISTS `kbp_priv_rule` (
  `priv_name_id` smallint(6) NOT NULL DEFAULT '0',
  `priv_module_id` smallint(6) NOT NULL DEFAULT '0',
  `what_priv` text NOT NULL,
  `status_priv` text NOT NULL,
  `optional_priv` varchar(256) NOT NULL,
  `apply_to_child` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`priv_name_id`,`priv_module_id`),
  KEY `priv_name_id` (`priv_name_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `kbp_priv_rule`
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

-- --------------------------------------------------------

--
-- Table structure for table `kbp_report_entry`
--

CREATE TABLE IF NOT EXISTS `kbp_report_entry` (
  `report_id` int(10) unsigned NOT NULL,
  `entry_id` int(10) unsigned NOT NULL,
  `date_day` date NOT NULL,
  `date_week` int(10) unsigned NOT NULL,
  `date_month` int(10) unsigned NOT NULL,
  `date_year` year(4) NOT NULL,
  `value_int` int(11) unsigned NOT NULL,
  `prev_int` int(10) unsigned NOT NULL,
  KEY `date_day` (`report_id`,`date_day`),
  KEY `date_week` (`report_id`,`date_week`),
  KEY `date_month` (`report_id`,`date_month`),
  KEY `date_year` (`report_id`,`date_year`),
  KEY `entry_id` (`entry_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_report_search`
--

CREATE TABLE IF NOT EXISTS `kbp_report_search` (
  `search_string` varchar(255) NOT NULL DEFAULT '',
  `search_num` int(10) unsigned NOT NULL DEFAULT '0',
  KEY `search_string` (`search_string`(3))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_report_summary`
--

CREATE TABLE IF NOT EXISTS `kbp_report_summary` (
  `report_id` int(10) unsigned NOT NULL,
  `date_day` date NOT NULL,
  `date_year` year(4) NOT NULL,
  `date_month` int(10) unsigned NOT NULL,
  `value_int` int(11) unsigned NOT NULL,
  `prev_int` int(10) unsigned NOT NULL,
  KEY `report_id` (`report_id`),
  KEY `date_year` (`report_id`,`date_year`),
  KEY `date_month` (`report_id`,`date_month`),
  KEY `date_day` (`report_id`,`date_day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_setting`
--

CREATE TABLE IF NOT EXISTS `kbp_setting` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_module_id` int(10) unsigned NOT NULL DEFAULT '0',
  `tab_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `group_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `input_id` tinyint(3) unsigned NOT NULL DEFAULT '0',
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
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=379 ;

--
-- Dumping data for table `kbp_setting`
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
(290, 20, 0, 0, 1, 0, '', 'default_sql_automation_article', '', '', 'INSERT INTO {prefix}trigger VALUES\n(NULL,1,2,0,''outdated_article'','''','''',2,''a:2:{i:1;a:2:{s:4:"item";s:6:"status";s:4:"rule";a:2:{i:0;s:2:"is";i:1;s:1:"1";}}i:2;a:2:{s:4:"item";s:12:"date_updated";s:4:"rule";a:3:{i:0;s:4:"more";i:1;s:3:"180";i:2;s:15:"period_old_days";}}}'',''a:2:{i:1;a:2:{s:4:"item";s:5:"email";s:4:"rule";a:1:{i:0;s:6:"author";}}i:2;a:2:{s:4:"item";s:6:"status";s:4:"rule";a:1:{i:0;s:1:"4";}}}'','''',1,0),\n(NULL,1,2,0,''outdated_article_grouped'','''','''',2,''a:2:{i:1;a:2:{s:4:"item";s:6:"status";s:4:"rule";a:2:{i:0;s:2:"is";i:1;s:1:"1";}}i:2;a:2:{s:4:"item";s:12:"date_updated";s:4:"rule";a:3:{i:0;s:4:"more";i:1;s:3:"180";i:2;s:15:"period_old_days";}}}'',''a:2:{i:1;a:2:{s:4:"item";s:18:"email_user_grouped";s:4:"rule";a:1:{i:0;s:6:"author";}}i:2;a:2:{s:4:"item";s:6:"status";s:4:"rule";a:1:{i:0;s:1:"4";}}}'','''',2,0);', 1, 0, 1, 1),
(288, 100, 0, 0, 3, 1, '', 'num_featured_entries', '', '0,1,3,5,10,15', '5', 8, 0, 0, 1),
(289, 2, 0, 0, 8, 8, '', 'header_logo', '', '', '', 8, 0, 1, 1),
(292, 2, 0, 0, 15, 1, 'onchange="toggleSubscriptionTimePicker(this.value, ''news'');"', 'subscribe_news_interval', '', 'hourly,daily', 'daily', 3, 0, 0, 1),
(293, 2, 0, 0, 15, 1, '', 'subscribe_news_time', '', 'dinamic', '00', 4, 0, 0, 1),
(294, 2, 0, 0, 15, 1, 'onchange="toggleSubscriptionTimePicker(this.value, ''entry'');"', 'subscribe_entry_interval', '', 'hourly,daily', 'daily', 5, 0, 0, 1),
(295, 2, 0, 0, 15, 1, '', 'subscribe_entry_time', '', 'dinamic', '00', 6, 0, 0, 1),
(291, 20, 0, 0, 1, 0, '', 'default_sql_workflow_article', '', '', 'INSERT INTO {prefix}trigger VALUES\n(NULL,1,4,0,''approval_category_admin'','''','''',2,''a:1:{i:2;a:2:{s:4:"item";s:15:"privilege_level";s:4:"rule";a:2:{i:0;s:5:"equal";i:1;s:1:"5";}}}'',''a:1:{i:1;a:3:{s:5:"title";s:0:"";s:4:"item";s:6:"assign";s:4:"rule";a:1:{i:0;s:14:"category_admin";}}}'','''',5,1);', 1, 0, 1, 1),
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
(306, 20, 0, 0, 1, 0, '', 'default_sql_workflow_file', '', '', 'INSERT INTO {prefix}trigger VALUES\n(NULL,2,4,0,''approval_category_admin'','''','''',2,''a:1:{i:2;a:2:{s:4:"item";s:15:"privilege_level";s:4:"rule";a:2:{i:0;s:5:"equal";i:1;s:1:"5";}}}'',''a:1:{i:1;a:3:{s:5:"title";s:0:"";s:4:"item";s:6:"assign";s:4:"rule";a:1:{i:0;s:14:"category_admin";}}}'','''',5,1);', 1, 0, 1, 1),
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

-- --------------------------------------------------------

--
-- Table structure for table `kbp_setting_to_value`
--

CREATE TABLE IF NOT EXISTS `kbp_setting_to_value` (
  `setting_id` int(10) unsigned NOT NULL DEFAULT '0',
  `setting_value` text NOT NULL,
  PRIMARY KEY (`setting_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `kbp_setting_to_value`
--

INSERT INTO `kbp_setting_to_value` (`setting_id`, `setting_value`) VALUES
(41, 'lakshmis@ezeees.com'),
(160, 'lakshmis@ezeees.com'),
(20, '/var/www/html/kb_file/'),
(128, '/var/www/html/kb_upload/'),
(235, 'en');

-- --------------------------------------------------------

--
-- Table structure for table `kbp_setting_to_value_user`
--

CREATE TABLE IF NOT EXISTS `kbp_setting_to_value_user` (
  `setting_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL,
  `setting_value` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`setting_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_stuff_category`
--

CREATE TABLE IF NOT EXISTS `kbp_stuff_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_stuff_data`
--

CREATE TABLE IF NOT EXISTS `kbp_stuff_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `data_key` varchar(30) NOT NULL DEFAULT '',
  `date_posted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `data_string` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `data_key` (`data_key`(3))
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_stuff_entry`
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
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_posted` datetime NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  KEY `filename` (`filename`(4)),
  KEY `category_id` (`category_id`),
  FULLTEXT KEY `title` (`title`,`description`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_tag`
--

CREATE TABLE IF NOT EXISTS `kbp_tag` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date_posted` datetime NOT NULL,
  `title` varchar(100) NOT NULL DEFAULT '',
  `description` text,
  `active` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `title` (`title`(3))
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_tag_to_entry`
--

CREATE TABLE IF NOT EXISTS `kbp_tag_to_entry` (
  `entry_id` int(10) unsigned NOT NULL DEFAULT '0',
  `entry_type` tinyint(4) unsigned NOT NULL DEFAULT '0',
  `tag_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`entry_id`,`entry_type`,`tag_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_trigger`
--

CREATE TABLE IF NOT EXISTS `kbp_trigger` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entry_type` tinyint(4) NOT NULL,
  `trigger_type` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `user_id` int(11) NOT NULL,
  `trigger_key` varchar(50) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL,
  `options` text NOT NULL,
  `cond_match` tinyint(4) NOT NULL,
  `cond` text NOT NULL,
  `action` text NOT NULL,
  `schedule` varchar(255) NOT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT '0',
  `active` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `entry_type` (`entry_type`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `kbp_trigger`
--

INSERT INTO `kbp_trigger` (`id`, `entry_type`, `trigger_type`, `user_id`, `trigger_key`, `title`, `options`, `cond_match`, `cond`, `action`, `schedule`, `sort_order`, `active`) VALUES
(1, 1, 2, 0, 'outdated_article', '', '', 2, 'a:2:{i:1;a:2:{s:4:"item";s:6:"status";s:4:"rule";a:2:{i:0;s:2:"is";i:1;s:1:"1";}}i:2;a:2:{s:4:"item";s:12:"date_updated";s:4:"rule";a:3:{i:0;s:4:"more";i:1;s:3:"180";i:2;s:15:"period_old_days";}}}', 'a:2:{i:1;a:2:{s:4:"item";s:5:"email";s:4:"rule";a:1:{i:0;s:6:"author";}}i:2;a:2:{s:4:"item";s:6:"status";s:4:"rule";a:1:{i:0;s:1:"4";}}}', '', 1, 0),
(2, 1, 2, 0, 'outdated_article_grouped', '', '', 2, 'a:2:{i:1;a:2:{s:4:"item";s:6:"status";s:4:"rule";a:2:{i:0;s:2:"is";i:1;s:1:"1";}}i:2;a:2:{s:4:"item";s:12:"date_updated";s:4:"rule";a:3:{i:0;s:4:"more";i:1;s:3:"180";i:2;s:15:"period_old_days";}}}', 'a:2:{i:1;a:2:{s:4:"item";s:18:"email_user_grouped";s:4:"rule";a:1:{i:0;s:6:"author";}}i:2;a:2:{s:4:"item";s:6:"status";s:4:"rule";a:1:{i:0;s:1:"4";}}}', '', 2, 0),
(3, 1, 4, 0, 'approval_category_admin', '', '', 2, 'a:1:{i:2;a:2:{s:4:"item";s:15:"privilege_level";s:4:"rule";a:2:{i:0;s:5:"equal";i:1;s:1:"5";}}}', 'a:1:{i:1;a:3:{s:5:"title";s:0:"";s:4:"item";s:6:"assign";s:4:"rule";a:1:{i:0;s:14:"category_admin";}}}', '', 5, 1),
(4, 2, 4, 0, 'approval_category_admin', '', '', 2, 'a:1:{i:2;a:2:{s:4:"item";s:15:"privilege_level";s:4:"rule";a:2:{i:0;s:5:"equal";i:1;s:1:"5";}}}', 'a:1:{i:1;a:3:{s:5:"title";s:0:"";s:4:"item";s:6:"assign";s:4:"rule";a:1:{i:0;s:14:"category_admin";}}}', '', 5, 1);

-- --------------------------------------------------------

--
-- Table structure for table `kbp_user`
--

CREATE TABLE IF NOT EXISTS `kbp_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `grantor_id` int(10) unsigned NOT NULL DEFAULT '1',
  `imported_user_id` varchar(50) DEFAULT NULL,
  `username` varchar(50) NOT NULL DEFAULT '',
  `password` varchar(150) NOT NULL DEFAULT '',
  `company_id` int(10) unsigned NOT NULL DEFAULT '0',
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
  `lastauth` int(10) unsigned DEFAULT NULL,
  `lastpass` int(10) unsigned DEFAULT NULL,
  `import_data` varchar(255) DEFAULT NULL,
  `address` varchar(255) NOT NULL DEFAULT '',
  `address2` varchar(255) NOT NULL DEFAULT '',
  `city` varchar(50) NOT NULL DEFAULT '',
  `state` varchar(2) NOT NULL DEFAULT '',
  `zip` varchar(20) NOT NULL DEFAULT '',
  `country` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`username`),
  UNIQUE KEY `imported_user_id` (`imported_user_id`),
  KEY `pass` (`password`(2)),
  KEY `company_id` (`company_id`),
  KEY `email` (`email`(3)),
  KEY `grantor_id` (`grantor_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `kbp_user`
--

INSERT INTO `kbp_user` (`id`, `grantor_id`, `imported_user_id`, `username`, `password`, `company_id`, `date_registered`, `date_updated`, `first_name`, `middle_name`, `last_name`, `email`, `phone`, `phone_ext`, `user_comment`, `admin_comment`, `active`, `editable`, `lastauth`, `lastpass`, `import_data`, `address`, `address2`, `city`, `state`, `zip`, `country`) VALUES
(1, 1, NULL, 'lakshmi', '$2y$07$AAAJtQAAbtkAAV5vAAEIc.p8KJzJwravIgDQ9oflsiULueGygwG/G', 0, '2017-08-22 20:00:54', '2017-08-22 14:30:54', 'Lakshmi', '', 'Suvvada', 'lakshmis@ezeees.com', '', '', NULL, NULL, 1, 0, 1503412366, NULL, NULL, '', '', '', '', '', 0);

-- --------------------------------------------------------

--
-- Table structure for table `kbp_user_activity`
--

CREATE TABLE IF NOT EXISTS `kbp_user_activity` (
  `entry_type` tinyint(3) unsigned NOT NULL,
  `action_type` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `entry_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL,
  `user_ip` int(10) unsigned DEFAULT NULL,
  `date_action` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_month` int(10) unsigned NOT NULL,
  `extra_data` text,
  KEY `user_id` (`user_id`),
  KEY `date_month` (`date_month`),
  KEY `entry_type` (`entry_type`,`entry_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_user_auth_token`
--

CREATE TABLE IF NOT EXISTS `kbp_user_auth_token` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(150) NOT NULL,
  `remote_token` varchar(32) NOT NULL,
  `date_expired` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_user_company`
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
  `country` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `url` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `custom` longtext,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_user_extra`
--

CREATE TABLE IF NOT EXISTS `kbp_user_extra` (
  `rule_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `value1` int(11) unsigned NOT NULL DEFAULT '0',
  `value2` text,
  `value3` text,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rule_id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_user_role`
--

CREATE TABLE IF NOT EXISTS `kbp_user_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `title` varchar(100) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`parent_id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;

--
-- Dumping data for table `kbp_user_role`
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

-- --------------------------------------------------------

--
-- Table structure for table `kbp_user_subscription`
--

CREATE TABLE IF NOT EXISTS `kbp_user_subscription` (
  `entry_id` int(10) unsigned NOT NULL,
  `entry_type` tinyint(4) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `date_subscribed` datetime NOT NULL,
  `date_lastsent` datetime DEFAULT NULL,
  PRIMARY KEY (`entry_id`,`entry_type`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_user_temp`
--

CREATE TABLE IF NOT EXISTS `kbp_user_temp` (
  `rule_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `user_ip` int(10) unsigned NOT NULL DEFAULT '0',
  `value_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `value1` int(11) unsigned NOT NULL DEFAULT '0',
  `value2` text,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`rule_id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `kbp_user_to_role`
--

CREATE TABLE IF NOT EXISTS `kbp_user_to_role` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `role_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
