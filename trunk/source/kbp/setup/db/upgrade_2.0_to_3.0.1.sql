ALTER TABLE `kbp_priv` DROP INDEX `user`; 
                     
--
ALTER TABLE `kbp_priv`DROP `id`, ADD PRIMARY KEY (`user_id` , `priv_name_id`);
 

--
ALTER TABLE `kbp_user` CHANGE `id` `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                     CHANGE `active` `active` TINYINT(1)  DEFAULT "1" NOT NULL,
					 CHANGE `username` `username` VARCHAR( 50 ) NOT NULL,
					 ADD `grantor_id` INT(10) UNSIGNED DEFAULT "1" NOT NULL AFTER `id`,
					 ADD `imported_user_id` INT(10) UNSIGNED DEFAULT NULL,
					 ADD `company_id` INT(10)  UNSIGNED DEFAULT "0" NOT NULL AFTER `password`,			 
					 ADD `admin_comment` TEXT AFTER `phone`,
					 ADD `user_comment` TEXT,
					 ADD `date_registered` DATETIME DEFAULT "0000-00-00 00:00:00" NOT NULL,
					 ADD `lastauth` INT(10),
					 ADD `import_data` VARCHAR(255) DEFAULT NULL,
					 ADD `phone_ext` VARCHAR( 10 ) NOT NULL AFTER `phone`,
					 ADD `address` varchar(255) NOT NULL DEFAULT '',
					 ADD `address2` varchar(255) NOT NULL DEFAULT '',
					 ADD `city` varchar(50) NOT NULL DEFAULT '',
					 ADD `state` varchar(2) NOT NULL DEFAULT '',
					 ADD `zip` varchar(20) NOT NULL DEFAULT '',
					 ADD `country` tinyint(3) UNSIGNED NOT NULL DEFAULT '0';
					 
--
ALTER TABLE `kbp_user` ADD INDEX grantor_id (grantor_id),
					 ADD INDEX company_id (company_id), 
					 ADD UNIQUE `imported_user_id` ( `imported_user_id` ),
					 ADD INDEX (`email`(3));

--
ALTER TABLE `kbp_feedback` CHANGE `attachment` `attachment` TEXT, 
						 CHANGE `answer` `answer` TEXT,
						 CHANGE `category_id` `subject_id` INT(10)  UNSIGNED DEFAULT "0" NOT NULL,
						 ADD `title` VARCHAR(255) NOT NULL AFTER `email`;					 

--
UPDATE `kbp_feedback` SET subject_id = 1;					 
						 
--
ALTER TABLE `kbp_feedback` DROP INDEX `NewIndex` ,
						 ADD INDEX `user_id` ( `user_id` ),
						 ADD INDEX ( `subject_id` );	 
						 
--
ALTER TABLE `kbp_kb_category` CHANGE `num_entry` `num_entry` SMALLINT(5) UNSIGNED DEFAULT "0" NOT NULL, 
							ADD `category_type` TINYINT(1)  DEFAULT "1" NOT NULL AFTER `ratingable`;
							

--
ALTER TABLE `kbp_file_category` ADD `attachable` TINYINT(1)  DEFAULT "1" AFTER `description`;


--
ALTER TABLE `kbp_kb_entry` DROP INDEX `url_title`,
						 DROP INDEX `meta_keywords`,
						 ADD FULLTEXT (`meta_keywords`),
						 ADD `entry_type` TINYINT(3) UNSIGNED DEFAULT '0' NOT NULL AFTER `meta_description`,
						 ADD INDEX (`entry_type`),
						 ADD `category_id` INT( 10 ) UNSIGNED DEFAULT '0' NOT NULL AFTER `id`,
						 ADD INDEX ( `category_id` ),
						 ADD `external_link` TEXT NOT NULL AFTER `entry_type`;
						 
						 
--
ALTER TABLE `kbp_file_entry` ADD `category_id` INT( 10 ) UNSIGNED DEFAULT '0' NOT NULL AFTER `id`,
						   ADD INDEX ( `category_id` );

--
ALTER TABLE `kbp_kb_entry_to_category` DROP INDEX `entry_id`,
									 ADD INDEX ( `category_id` ),
									 ADD `sort_order` SMALLINT UNSIGNED DEFAULT '1' NOT NULL ;

--
ALTER TABLE `kbp_file_entry_to_category` ADD INDEX ( `category_id` ),
									   ADD `sort_order` SMALLINT UNSIGNED DEFAULT '1' NOT NULL ;
									   
--
ALTER TABLE `kbp_kb_related_to_entry` DROP INDEX `attachment_id`,
									ADD INDEX `related_entry_id` ( `related_entry_id` );
									
									
--
DELETE FROM kbp_setting_to_value WHERE setting_id IN(7,103);


--
DELETE FROM kbp_setting_to_value WHERE setting_id=103;


--
DELETE FROM kbp_setting_to_value WHERE setting_id = 131;


--
ALTER TABLE `kbp_user` CHANGE `user_comment` `user_comment` TEXT NULL,
                     CHANGE `admin_comment` `admin_comment` TEXT NULL;					 					 

								 
--
ALTER TABLE `kbp_file_entry` ADD FULLTEXT `title_only` (`title`);


--
DROP TABLE IF EXISTS `kbp_article_template`;
--
CREATE TABLE `kbp_article_template` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `tmpl_key` varchar(30) NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  `body` text NOT NULL,
  `description` text NOT NULL,
  `sort_order` smallint(5) unsigned NOT NULL default '1',
  `private` tinyint(1) NOT NULL default '0',
  `active` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  KEY `tmpl_key` (`tmpl_key`(3))
) TYPE=MyISAM   ;

--
INSERT INTO `kbp_article_template` (`id`, `tmpl_key`, `title`, `body`, `description`, `sort_order`, `private`, `active`) VALUES
(1, '', 'Page Content 1', '<h3>Sub title 1 here</h3>\r\n<h3>Sub title 2 here<br />\r\n</h3>\r\n<ol>\r\n    <li>item 1</li>\r\n    <li>item 2</li>\r\n    <li>item3</li>\r\n</ol>\r\n<h3>&nbsp;</h3>', 'Example of article format', 1, 0, 1),
(2, '', 'Info Box', '<div class="box yellowBox">type here</div>', 'Yellow box with borders', 1, 0, 1),
(3, '', 'Info Box 2', '<div class="box greyBox">type here</div>', 'Grey box with borders', 1, 0, 1);

--
DROP TABLE IF EXISTS `kbp_data_to_user_rule`;
--
CREATE TABLE `kbp_data_to_user_rule` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `title` varchar(40) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM   ;

--
INSERT INTO `kbp_data_to_user_rule` (`id`, `title`) VALUES
(104, 'file_entry_to_user_admin'),
(5, 'kb_category_to_role_write'),
(1, 'kb_category_to_role_read'),
(6, 'file_category_to_role_write'),
(2, 'file_category_to_role_read'),
(3, 'kb_category_to_user_admin'),
(4, 'file_category_to_user_admin'),
(101, 'kb_entry_to_role_read'),
(103, 'kb_entry_to_user_admin'),
(102, 'file_entry_to_role_read'),
(10, 'feedback_user_admin');

--
DROP TABLE IF EXISTS `kbp_letter_template`;
--
CREATE TABLE `kbp_letter_template` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `letter_key` varchar(50) NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `from_email` varchar(255) NOT NULL default '',
  `from_name` varchar(255) NOT NULL default '',
  `to_email` varchar(255) NOT NULL default '',
  `to_name` varchar(255) NOT NULL default '',
  `to_cc_email` varchar(255) NOT NULL default '',
  `to_cc_name` varchar(255) NOT NULL default '',
  `to_bcc_email` varchar(255) NOT NULL default '',
  `to_bcc_name` varchar(255) NOT NULL default '',
  `to_special` varchar(255) default NULL,
  `subject` varchar(255) NOT NULL default '',
  `body` text,
  `skip_field` varchar(255) NOT NULL default '',
  `is_html` tinyint(1) NOT NULL default '0',
  `in_out` tinyint(4) NOT NULL default '1',
  `predifined` tinyint(1) NOT NULL default '0',
  `active` tinyint(1) NOT NULL default '0',
  `sort_order` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM   ;

--
INSERT INTO `kbp_letter_template` (`id`, `letter_key`, `title`, `description`, `from_email`, `from_name`, `to_email`, `to_name`, `to_cc_email`, `to_cc_name`, `to_bcc_email`, `to_bcc_name`, `to_special`, `subject`, `body`, `skip_field`, `is_html`, `in_out`, `predifined`, `active`, `sort_order`) VALUES
(1, 'send_to_friend', '', '', '', '', '', '', '', '', '', '', NULL, '', NULL, 'to,from', 0, 2, 1, 1, 100),
(2, 'answer_to_user', '', '', '[support_email]', '[support_name]', '[email]', '[name]', '', '', '', '', NULL, '', NULL, '', 0, 2, 1, 1, 41),
(3, 'contact', '', '', '[email]', '[name]', '[support_email]', '[support_name]', '', '', '', '', 'feedback_admin', '', NULL, '', 0, 1, 1, 1, 40),
(4, 'confirm_registration', '', '', '[noreply_email]', '', '[email]', '', '', '', '', '', NULL, '', NULL, '', 0, 2, 1, 1, 6),
(5, 'generated_password', '', '', '[noreply_email]', '', '[email]', '', '', '', '', '', NULL, '', NULL, '', 0, 2, 1, 1, 7),
(6, 'comment_approve_to_admin', '', '', '[noreply_email]', '', '[support_email]', '[support_name]', '', '', '', '', 'category_admin', '', NULL, '', 0, 1, 1, 1, 30),
(7, 'user_approve_to_admin', '', '', '[noreply_email]', '', '[support_email]', '[support_name]', '', '', '', '', NULL, '', NULL, '', 0, 1, 1, 1, 1),
(8, 'user_approve_to_user', '', '', '[noreply_email]', '', '[email]', '[first_name] [last_name]', '', '', '', '', NULL, '', NULL, 'to_cc,to_bcc', 0, 2, 1, 1, 2),
(9, 'user_approved', '', '', '[support_email]', '[support_name]', '[email]', '[first_name] [last_name]', '', '', '', '', NULL, '', NULL, '', 0, 2, 1, 1, 3),
(10, 'user_added', '', '', '[noreply_email]', '', '[email]', '', '', '', '', '', NULL, '', NULL, '', 0, 2, 1, 1, 4),
(11, 'user_updated', '', '', '[noreply_email]', '', '[email]', '', '', '', '', '', NULL, '', NULL, '', 0, 2, 1, 1, 5),
(12, 'article_approve_to_admin', '', '', '[noreply_email]', '', '[support_email]', '[support_name]', '', '', '', '', 'category_admin', '', NULL, '', 0, 1, 1, 1, 10),
(13, 'article_approve_to_user', '', '', '[noreply_email]', '', '[email]', '[first_name] [last_name]', '', '', '', '', NULL, '', NULL, 'to_cc,to_bcc', 0, 2, 1, 0, 11),
(14, 'article_approved', '', '', '[support_email]', '[support_name]', '[email]', '[first_name] [last_name]', '', '', '', '', NULL, '', NULL, '', 0, 2, 1, 1, 13),
(15, 'article_added', '', '', '[noreply_email]', '', '[email]', '', '', '', '', '', NULL, '', NULL, '', 0, 2, 1, 0, 14),
(16, 'article_updated', '', '', '[noreply_email]', '', '[email]', '', '', '', '', '', NULL, '', NULL, '', 0, 2, 1, 0, 15),
(17, 'file_approve_to_admin', '', '', '[noreply_email]', '', '[support_email]', '[support_namel]', '', '', '', '', NULL, '', NULL, '', 0, 1, 1, 1, 20),
(18, 'file_approve_to_user', '', '', '[noreply_email]', '', '[email]', '[first_name] [last_name]', '', '', '', '', NULL, '', NULL, 'to_cc,to_bcc', 0, 2, 1, 0, 21),
(19, 'file_approved', '', '', '[support_email]', '[support_name]', '[email]', '[first_name] [last_name]', '', '', '', '', NULL, '', NULL, '', 0, 2, 1, 1, 23),
(20, 'file_added', '', '', '[noreply_email]', '', '[email]', '', '', '', '', '', NULL, '', NULL, '', 0, 2, 1, 0, 24),
(21, 'file_updated', '', '', '[noreply_email]', '', '[email]', '', '', '', '', '', NULL, '', NULL, '', 0, 2, 1, 0, 25);

--
DROP TABLE IF EXISTS `kbp_list`;
--
CREATE TABLE `kbp_list` (
  `id` int(11) NOT NULL auto_increment,
  `list_key` varchar(50) NOT NULL default '',
  `title` varchar(50) NOT NULL default '',
  `description` text NOT NULL,
  `predifined` tinyint(4) NOT NULL default '0',
  `sort_order` tinyint(3) unsigned NOT NULL default '0',
  `active` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `list_key` (`list_key`)
) TYPE=MyISAM   ;

--
INSERT INTO `kbp_list` (`id`, `list_key`, `title`, `description`, `predifined`, `sort_order`, `active`) VALUES
(1, 'article_status', '', '', 1, 3, 1),
(2, 'file_status', '', '', 1, 2, 1),
(3, 'article_type', '', '', 1, 4, 1),
(4, 'user_status', '', '', 1, 1, 1),
(5, 'feedback_subj', '', '', 1, 10, 1);

--
DROP TABLE IF EXISTS `kbp_list_country`;
--
CREATE TABLE `kbp_list_country` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(30) NOT NULL default '',
  `iso2` varchar(2) NOT NULL default '',
  `iso3` varchar(3) NOT NULL default '',
  `sort_order` tinyint(3) unsigned NOT NULL default '0',
  `active` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM   ;

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

--
DROP TABLE IF EXISTS `kbp_list_value`;
--
CREATE TABLE `kbp_list_value` (
  `id` int(11) NOT NULL auto_increment,
  `list_id` int(10) unsigned NOT NULL default '0',
  `list_key` varchar(50) NOT NULL default '',
  `list_value` tinyint(4) NOT NULL default '0',
  `title` varchar(50) NOT NULL default '',
  `description` text NOT NULL,
  `predifined` tinyint(4) NOT NULL default '0',
  `sort_order` tinyint(3) unsigned NOT NULL default '0',
  `active` tinyint(1) NOT NULL default '0',
  `custom_1` text NOT NULL,
  `custom_2` text NOT NULL,
  `custom_3` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `list_id` (`list_id`)
) TYPE=MyISAM   ;

--
INSERT INTO `kbp_list_value` (`id`, `list_id`, `list_key`, `list_value`, `title`, `description`, `predifined`, `sort_order`, `active`, `custom_1`, `custom_2`, `custom_3`) VALUES
(1, 1, 'not_published', 0, '', '', 1, 4, 1, '#C0C0C0', '', 0),
(2, 1, 'published', 1, '', '', 1, 1, 1, '#7898C2', '', 1),
(3, 1, 'approve', 2, '', '', 1, 2, 1, '#FF0000', '', 0),
(4, 1, 'draft', 3, '', '', 1, 3, 1, '#808080', '', 0),
(5, 2, 'not_published', 0, '', '', 1, 4, 1, '#C0C0C0', '', 0),
(6, 2, 'published', 1, '', '', 1, 1, 1, '#7898C2', '', 1),
(7, 2, 'approve', 2, '', '', 1, 2, 1, '#FF0000', '', 0),
(8, 2, 'draft', 3, '', '', 1, 3, 1, '#808080', '', 0),
(9, 3, 'bug', 1, '', '', 0, 1, 1, '<strong>Bug:</strong><br />\r\n<br />\r\n<br />\r\n<strong>How to repeat:</strong><br />\r\n<br />\r\n<br />\r\n<strong>More details:</strong>', '', 0),
(10, 3, 'errdoc', 2, '', '', 0, 2, 1, '<font face="Arial"><strong>SYMPTOMS:<br />\r\n<br />\r\n<br />\r\n<br />\r\n</strong></font><font face="Arial"><strong>CAUSE:<br />\r\n<br />\r\n<br />\r\n<br />\r\n</strong></font><font face="Arial"><strong>MORE INFORMATION:</strong></font>', '', 0),
(11, 3, 'errmsg', 3, '', '', 0, 3, 1, '', '', 0),
(12, 3, 'faq', 4, '', '', 0, 4, 1, '', '', 0),
(13, 3, 'fix', 5, '', '', 0, 5, 1, '', '', 0),
(14, 3, 'hotfix', 6, '', '', 0, 6, 1, '', '', 0),
(15, 3, 'howto', 7, '', '', 0, 7, 1, '', '', 0),
(16, 3, 'info', 8, '', '', 0, 8, 1, '', '', 0),
(17, 3, 'prb', 9, '', '', 0, 9, 1, '', '', 0),
(20, 4, 'not_active', 0, '', '', 1, 3, 1, '#C0C0C0', '', 0),
(21, 4, 'active', 1, '', '', 1, 1, 1, '#7898C2', '', 1),
(24, 5, 'default', 1, '', '', 1, 1, 1, '#000000', '', 0),
(22, 4, 'approve', 2, '', '', 1, 2, 1, '#FF0000', '', 0),
(23, 4, 'draft', 3, '', '', 1, 4, 0, '#808080', '', 0);

--
DROP TABLE IF EXISTS `kbp_priv_module`;
--
CREATE TABLE `kbp_priv_module` (
  `id` smallint(6) NOT NULL default '0',
  `parent_id` smallint(6) NOT NULL default '0',
  `parent_setting_id` tinyint(1) NOT NULL default '0',
  `module_name` varchar(30) NOT NULL default '0',
  `menu_name` varchar(50) NOT NULL default '',
  `use_in_sub_menu` enum('NO','YES_DEFAULT','YES_NOT_DEFAULT') default NULL,
  `as_sub_menu` tinyint(1) NOT NULL default '0',
  `by_default` varchar(30) NOT NULL default '',
  `own_priv` tinyint(1) NOT NULL default '0',
  `check_priv` tinyint(1) NOT NULL default '1',
  `status_priv` tinyint(1) NOT NULL default '0',
  `what_priv` varchar(50) default NULL,
  `sort_order` smallint(5) unsigned NOT NULL default '0',
  `active` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
INSERT INTO `kbp_priv_module` (`id`, `parent_id`, `parent_setting_id`, `module_name`, `menu_name`, `use_in_sub_menu`, `as_sub_menu`, `by_default`, `own_priv`, `check_priv`, `status_priv`, `what_priv`, `sort_order`, `active`) VALUES
(0, 0, 0, 'all', '', 'NO', 0, '', 0, 1, 0, NULL, 0, 1),
(1, 0, 0, 'users', 'Users', 'NO', 0, 'user', 0, 1, 0, NULL, 20, 1),
(3, 0, 0, 'setting', 'Settings', 'NO', 0, 'kb_setting', 0, 1, 0, NULL, 200, 1),
(100, 0, 3, 'knowledgebase', 'KnowledgeBase', 'NO', 0, 'kb_entry', 0, 1, 0, NULL, 5, 1),
(12, 1, 0, 'priv', 'Privileges', 'NO', 0, '', 0, 1, 0, NULL, 4, 1),
(101, 100, 0, 'kb_entry', 'Questions', 'NO', 0, '', 1, 1, 2, NULL, 1, 1),
(102, 100, 0, 'kb_category', 'Categories', 'NO', 0, '', 0, 1, 0, NULL, 15, 1),
(104, 100, 0, 'kb_comment', 'Comments', 'NO', 0, '', 0, 1, 0, NULL, 3, 1),
(105, 100, 0, 'kb_glossary', 'Glossary', 'NO', 0, '', 0, 1, 0, NULL, 4, 1),
(131, 3, 3, 'admin_setting', 'Admin', 'NO', 0, '', 0, 1, 0, 'select,update', 1, 1),
(10, 1, 0, 'user', 'Users', 'NO', 0, '', 1, 1, 0, NULL, 1, 1),
(8, 0, 0, 'feedback', 'Feedback', 'NO', 0, 'feedback', 0, 1, 0, NULL, 9, 1),
(130, 3, 0, 'kb_setting', 'KnowledgeBase', 'NO', 0, '', 0, 1, 0, 'select,update', 2, 1),
(108, 100, 0, 'kb_attachment', 'Attachments', 'NO', 0, '', 0, 1, 0, NULL, 20, 0),
(200, 0, 3, 'file', 'Files', 'NO', 0, 'file_entry', 0, 1, 0, NULL, 6, 1),
(202, 200, 0, 'file_category', 'Categories', 'NO', 0, '', 0, 1, 0, NULL, 2, 1),
(109, 100, 0, 'kb_client', 'Client View', 'NO', 0, '', 0, 0, 0, NULL, 23, 1),
(132, 3, 0, 'letter_template', 'Letter Template', 'NO', 0, '', 0, 1, 0, 'select,update', 12, 1),
(133, 3, 0, 'file_setting', 'Files', 'NO', 0, '', 0, 1, 0, 'select,update', 5, 1),
(14, 1, 0, 'role', 'Roles', 'NO', 0, '', 0, 1, 0, NULL, 3, 1),
(300, 0, 0, 'ticket', 'Tickets', 'NO', 0, '', 0, 1, 0, NULL, 7, 0),
(201, 200, 0, 'file_entry', 'Files', 'NO', 0, '', 1, 1, 2, NULL, 1, 1),
(134, 3, 0, 'email_setting', 'Email', 'NO', 0, '', 0, 1, 0, 'select,update', 11, 1),
(4, 0, 0, 'help', 'Help', 'NO', 0, 'help', 0, 0, 0, NULL, 220, 1),
(61, 6, 0, 'php_info', 'PHP info', 'NO', 0, 'php_info', 0, 1, 0, NULL, 1, 0),
(62, 6, 0, 'db_info', 'DB info', 'NO', 0, 'db_info', 0, 1, 0, NULL, 2, 0),
(135, 3, 0, 'cron', 'Sheduled Tasks', 'NO', 0, '', 0, 1, 0, 'select,update', 19, 0),
(80, 8, 0, 'feedback', 'Feedback', 'NO', 0, '', 0, 1, 0, NULL, 1, 1),
(203, 200, 0, 'file_bulk', 'Bulk Actions', 'NO', 0, '', 0, 1, 0, NULL, 3, 1),
(43, 4, 0, 'help_about', 'About', 'NO', 0, '', 0, 0, 0, NULL, 10, 0),
(44, 4, 0, 'help_licence', 'Licence', 'NO', 0, '', 0, 0, 0, 'select', 11, 0),
(41, 4, 0, 'help', 'Help', 'NO', 0, '', 0, 0, 0, NULL, 1, 1),
(42, 4, 0, 'help_faq', 'FAQ', 'NO', 0, '', 0, 0, 0, NULL, 2, 0),
(136, 3, 0, 'backup', 'Backups', 'NO', 0, '', 0, 1, 0, 'select,update', 20, 0),
(137, 3, 0, 'role', 'Roles', 'NO', 0, '', 0, 2, 0, NULL, 21, 1),
(138, 3, 0, 'list_setting', 'Lists', 'NO', 0, '', 0, 1, 0, NULL, 15, 1),
(11, 1, 0, 'company', 'Companies', 'NO', 0, '', 0, 1, 0, NULL, 2, 1),
(139, 3, 0, 'priv', 'Priviledges', 'NO', 0, '', 0, 2, 0, NULL, 22, 1),
(107, 100, 0, 'article_template', 'Article Template', 'NO', 0, '', 0, 1, 0, NULL, 16, 1),
(7, 0, 0, 'imex', 'Import/Export', 'NO', 0, 'import_user', 0, 1, 0, NULL, 210, 1),
(72, 7, 0, 'import_article', 'Import Articles', 'NO', 0, '', 0, 1, 0, 'insert', 2, 1),
(71, 7, 0, 'import_user', 'Import Users', 'NO', 0, '', 0, 1, 0, 'insert', 1, 1),
(74, 7, 0, 'kb_entry', 'Articles', 'NO', 0, '', 0, 2, 0, NULL, 5, 1),
(141, 3, 0, 'template', 'Template', 'NO', 0, 'email_template', 0, 1, 0, NULL, 0, 0),
(73, 7, 0, 'user', 'User', 'NO', 0, '', 0, 2, 0, NULL, 4, 1),
(79, 7, 0, 'spacer', '7', 'NO', 0, '', 0, 0, 0, NULL, 3, 1),
(9, 0, 0, 'home', 'Home', 'NO', 0, 'home', 0, 0, 0, NULL, 1, 1),
(90, 9, 0, 'home', 'Home', 'NO', 0, '', 0, 0, 0, NULL, 1, 1),
(150, 3, 0, 'licence_setting', 'Licence', 'NO', 0, '', 0, 1, 0, 'select,insert,update', 0, 1),
(45, 4, 0, 'help_request', 'Support Request', 'NO', 0, '', 0, 0, 0, NULL, 5, 0);

--
DROP TABLE IF EXISTS `kbp_priv_name`;
--
CREATE TABLE `kbp_priv_name` (
  `id` smallint(5) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` text,
  `editable` tinyint(1) NOT NULL default '1',
  `sort_order` smallint(5) unsigned NOT NULL default '1',
  `active` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM   ;

--
INSERT INTO `kbp_priv_name` (`id`, `name`, `description`, `editable`, `sort_order`, `active`) VALUES
(1, '', NULL, 0, 1, 1),
(2, '', NULL, 1, 2, 1),
(3, '', NULL, 1, 3, 1),
(4, '', NULL, 1, 4, 1),
(5, '', NULL, 1, 5, 1);

--
DROP TABLE IF EXISTS `kbp_priv_rule`;
--
CREATE TABLE `kbp_priv_rule` (
  `priv_name_id` smallint(6) NOT NULL default '0',
  `priv_module_id` smallint(6) NOT NULL default '0',
  `what_priv` set('select','self_select','insert','update','self_update','status','self_status','delete','self_delete') NOT NULL default '',
  `status_priv` text NOT NULL,
  `apply_to_child` tinyint(1) unsigned NOT NULL default '0',
  `active` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`priv_name_id`,`priv_module_id`),
  KEY `priv_name_id` (`priv_name_id`)
) TYPE=MyISAM;

--
INSERT INTO `kbp_priv_rule` (`priv_name_id`, `priv_module_id`, `what_priv`, `status_priv`, `apply_to_child`, `active`) VALUES
(1, 0, 'select,insert,update,status,delete', '', 0, 1),
(3, 10, 'select', '', 0, 1),
(3, 8, 'select,insert,update,status,delete', '', 1, 1),
(3, 201, 'select,insert,update,status,delete', '', 0, 1),
(3, 104, 'select,insert,update,status,delete', '', 0, 1),
(4, 201, 'self_select,insert,self_update,self_status,self_delete', '', 0, 1),
(4, 101, 'self_select,insert,self_update,self_status,self_delete', '', 0, 1),
(5, 101, 'self_select,insert,self_update,self_status,self_delete', 'a:3:{s:6:"update";a:2:{i:0;s:1:"2";i:1;s:1:"3";}s:6:"status";a:2:{i:0;s:1:"2";i:1;s:1:"3";}s:6:"delete";a:2:{i:0;s:1:"2";i:1;s:1:"3";}}', 0, 1),
(2, 11, 'select,insert,update,status,delete', '', 0, 1),
(3, 105, 'select,insert,update,status,delete', '', 0, 1),
(5, 201, 'self_select,insert,self_update,self_status,self_delete', 'a:3:{s:6:"update";a:2:{i:0;s:1:"2";i:1;s:1:"3";}s:6:"status";a:2:{i:0;s:1:"2";i:1;s:1:"3";}s:6:"delete";a:2:{i:0;s:1:"2";i:1;s:1:"3";}}', 0, 1),
(3, 101, 'select,insert,update,status,delete', '', 0, 1),
(2, 100, 'select,insert,update,status,delete', '', 1, 1),
(2, 200, 'select,insert,update,status,delete', '', 1, 1),
(2, 10, 'select,insert,self_update,self_status,self_delete', '', 0, 1),
(2, 8, 'select,insert,update,status,delete', '', 1, 1),
(3, 11, 'select', '', 0, 1),
(2, 14, 'select', '', 0, 1),
(2, 12, 'select', '', 0, 1);

--
DROP TABLE IF EXISTS `kbp_setting`;
--
CREATE TABLE `kbp_setting` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `module_id` int(10) unsigned NOT NULL default '0',
  `group_id` tinyint(3) unsigned NOT NULL default '0',
  `input_id` tinyint(3) unsigned NOT NULL default '0',
  `options` varchar(100) NOT NULL default '',
  `setting_key` varchar(255) NOT NULL default '',
  `messure` varchar(10) NOT NULL default '',
  `range` varchar(255) NOT NULL default '',
  `default_value` varchar(255) NOT NULL default '',
  `sort_order` float NOT NULL default '0',
  `required` tinyint(1) NOT NULL default '0',
  `skip_default` tinyint(1) NOT NULL default '0',
  `active` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM   ;

--
INSERT INTO `kbp_setting` (`id`, `module_id`, `group_id`, `input_id`, `options`, `setting_key`, `messure`, `range`, `default_value`, `sort_order`, `required`, `skip_default`, `active`) VALUES
(1, 100, 4, 1, '', 'allow_comments', '', '0,1,2', '1', 12, 0, 0, 1),
(2, 100, 4, 4, '', 'allow_rating', '', '', '1', 14, 0, 0, 1),
(3, 100, 4, 1, '', 'allow_post_entry', '', '0,1,2', '1', 11, 0, 0, 0),
(4, 100, 3, 1, '', 'num_most_viewed_entries', '', '0,3,5,10,15', '5', 8, 0, 0, 1),
(5, 100, 3, 1, '', 'num_recently_posted_entries', '', '0,3,5,10,15', '5', 7, 0, 0, 1),
(6, 100, 3, 1, '', 'num_entries_per_page', '', '10,15,20', '10', 6, 0, 0, 1),
(7, 100, 8, 1, 'onchange="populateSelect(myOptions[this.value]);"', 'view_format', '', 'default,left', 'left', 2, 0, 0, 1),
(8, 100, 6, 4, '', 'show_hits', '', '', '1', 4, 0, 0, 1),
(9, 100, 4, 1, '', 'comment_policy', '', '1,2,3', '1', 13, 0, 0, 1),
(10, 100, 1, 2, 'size="50"', 'site_title', '', '', 'Your Company :: Knowledgebase', 1, 0, 1, 1),
(11, 100, 1, 2, 'size="50"', 'support_email', '', '', 'your@email.com', 10, 0, 1, 0),
(12, 100, 3, 4, '', 'show_glossary_link', '', '', '1', 18, 0, 0, 1),
(13, 100, 8, 2, 'size="50"', 'page_to_load', '', '', 'Default', 1, 0, 0, 1),
(14, 100, 3, 1, '', 'category_sort_order', '', 'name,sort_order', 'sort_order', 10, 0, 0, 0),
(15, 100, 6, 4, '', 'show_send_link', '', '', '1', 3, 0, 0, 1),
(16, 100, 3, 1, '', 'show_num_entries', '', '0,1', '1', 10.1, 0, 0, 1),
(17, 100, 1, 4, '', 'show_title_nav', '', '', '1', 12, 0, 0, 1),
(104, 100, 3, 1, '', 'entry_sort_order', '', 'name,sort_order,added_desc,added_asc,updated_desc,updated_asc,hits_desc,hits_asc', 'sort_order', 1, 0, 0, 1),
(19, 100, 1, 2, 'size="50"', 'nav_title', '', '', 'KB Home', 6, 0, 1, 1),
(20, 200, 1, 2, 'size="50"', 'file_dir', '', '', '[document_root_parent]/kb_file/', 10, 0, 1, 1),
(21, 200, 1, 4, '', 'file_extract', '', '', '1', 15, 0, 0, 1),
(22, 200, 1, 2, 'size="50"', 'file_denied_extensions', '', '', 'php,php3,php5,phtml,asp,aspx,ascx,jsp,cfm,cfc,pl,bat,exe,dll,reg,cgi', 13, 0, 0, 1),
(106, 100, 2, 1, '', 'register_captcha', '', 'no,yes', 'yes', 7, 0, 0, 1),
(23, 200, 1, 2, '', 'file_max_filesize', '', '', '2048', 11, 0, 0, 1),
(24, 200, 1, 1, '', 'file_store', '', 'dir,db', 'dir', 0, 0, 0, 0),
(25, 200, 1, 2, 'size="50"', 'file_allowed_extensions', '', '', '', 12, 0, 0, 1),
(26, 200, 1, 1, '', 'file_rename_policy', '', 'date_Ymd-His,date_Ymd,date_Y,suffics_3', 'date_Ymd-His', 10, 0, 0, 0),
(27, 200, 3, 1, '', 'num_most_viewed_entries', '', '0,3,5,10,15', '5', 3, 0, 0, 1),
(28, 200, 3, 1, '', 'num_recently_posted_entries', '', '0,3,5,10,15', '5', 2, 0, 0, 1),
(29, 200, 3, 1, '', 'num_entries_per_page', '', '10,15,20', '10', 1, 0, 0, 1),
(30, 200, 3, 1, '', 'category_sort_order', '', 'name,sort_order', 'sort_order', 4, 0, 0, 0),
(31, 200, 3, 1, '', 'show_num_entries', '', '0,1', '1', 5, 0, 0, 1),
(34, 100, 3, 4, '', 'show_file_link', '', '', '1', 17, 0, 0, 1),
(33, 100, 2, 4, '', 'kb_register_access', '', '', '0', 1, 0, 0, 1),
(35, 100, 2, 1, '', 'private_policy', '', '1,2', '2', 16, 0, 0, 1),
(36, 200, 2, 1, '', 'private_policy', '', '1,2', '2', 0, 0, 0, 1),
(49, 134, 2, 2, 'size="50"', 'smtp_port', '', '', '25', 7, 0, 0, 1),
(38, 100, 2, 4, '', 'register_policy', '', '', '1', 2, 0, 0, 1),
(39, 100, 2, 2, 'size="10"', 'auth_expired', '', '', '60', 14, 0, 0, 1),
(40, 134, 2, 1, '', 'mailer', '', 'mail,smtp,sendmail', 'mail', 3, 1, 0, 1),
(41, 134, 1, 2, 'size="50"', 'from_email', '', '', '', 2, 1, 1, 1),
(42, 134, 1, 2, 'size="50"', 'from_name', '', '', 'Support Team', 3, 0, 0, 1),
(43, 134, 2, 2, 'size="50"', 'sendmail_path', '', '', '/usr/sbin/sendmail', 4, 0, 0, 1),
(109, 100, 4, 2, 'size="10"', 'contact_attachment', '', '', '1', 11.3, 0, 0, 1),
(45, 134, 2, 2, 'size="50"', 'smtp_user', '', '', '', 9, 0, 0, 1),
(46, 134, 2, 5, 'size="50"', 'smtp_pass', '', '', '', 10, 0, 0, 1),
(47, 134, 2, 2, 'size="50"', 'smtp_host', '', '', '', 6, 0, 0, 1),
(50, 100, 5, 2, 'size="10"', 'preview_article_limit', '', '', '300', 3, 0, 0, 1),
(51, 100, 5, 4, '', 'preview_show_comments', '', '', '1', 8, 0, 0, 1),
(52, 100, 5, 4, '', 'preview_show_rating', '', '', '0', 7, 0, 0, 1),
(53, 100, 5, 4, '', 'preview_show_hits', '', '', '0', 10, 0, 0, 1),
(54, 100, 5, 4, '', 'preview_show_date', '', '', '1', 5, 0, 0, 1),
(55, 100, 6, 4, '', 'show_author', '', '', '0', 5, 0, 0, 1),
(56, 134, 1, 2, 'size="50"', 'from_mailer', '', '', 'KBMailer', 1, 0, 0, 1),
(57, 100, 2, 4, '', 'register_confirmation', '', '', '1', 8, 0, 0, 0),
(58, 100, 3, 1, '', 'num_entries_category', '', '0,all,3,5,10,15,20', '5', 8.1, 0, 0, 1),
(59, 100, 7, 2, 'size="50"', 'rss_title', '', '', 'Knowledgebase RSS', 2, 0, 1, 1),
(60, 100, 7, 3, 'rows="2" style="width: 100%"', 'rss_description', '', '', '', 3, 0, 1, 1),
(61, 100, 7, 1, '', 'rss_generate', '', 'none,one,top', 'one', 1, 0, 0, 1),
(62, 100, 1, 3, 'rows="2" style="width: 100%"', 'site_keywords', '', '', '', 2, 0, 1, 1),
(63, 100, 1, 3, 'rows="2" style="width: 100%"', 'site_description', '', '', '', 3, 0, 1, 1),
(64, 100, 3, 1, '', 'num_category_cols', '', '0,1,2,3,4,5', '3', 10.2, 0, 0, 1),
(65, 200, 3, 1, '', 'num_category_cols', '', '0,1,2,3,4,5', '3', 6, 0, 0, 1),
(66, 100, 2, 4, '', 'register_approval', '', '', '0', 3, 0, 0, 1),
(105, 100, 4, 1, '', 'comment_captcha', '', 'no,yes,yes_no_reg', 'yes_no_reg', 12.1, 0, 0, 1),
(101, 100, 1, 2, 'size="50"', 'header_title', '', '', 'Knowledgebase', 5, 0, 1, 1),
(102, 100, 4, 1, '', 'allow_contact', '', '0,1,2', '1', 11.2, 0, 0, 1),
(103, 100, 8, 1, '', 'view_template', '', '1', 'default', 3, 0, 0, 1),
(107, 100, 4, 1, '', 'contact_captcha', '', 'no,yes,yes_no_reg', 'no', 11.7, 0, 0, 1),
(108, 100, 4, 1, '', 'entry_captcha', '', 'no,yes,yes_no_reg', '', 11.1, 0, 0, 0),
(111, 100, 2, 1, '', 'register_user_priv', '', 'dinamic', '0', 5, 0, 0, 1),
(112, 100, 2, 1, '', 'register_user_role', '', 'dinamic', '0', 6, 0, 0, 1),
(110, 100, 4, 4, '', 'contact_attachment_email', '', '', '0', 11.6, 0, 0, 1),
(114, 100, 6, 4, '', 'show_print_link', '', '', '1', 2, 0, 0, 1),
(115, 100, 3, 2, 'style="width: 100%"', 'entry_prefix_pattern', '', '', '', 16, 0, 0, 1),
(116, 100, 3, 2, 'size="10"', 'entry_id_padding', '', '', '', 15.8, 0, 0, 1),
(44, 134, 2, 4, '', 'smtp_auth', '', '', '1', 8, 0, 0, 1),
(117, 100, 2, 1, '', 'auth_captcha', '', 'no,yes', 'no', 9, 0, 0, 0),
(118, 200, 3, 1, '', 'entry_sort_order', '', 'name,sort_order,added_desc,added_asc,updated_desc,updated_asc,hits_desc,hits_asc', 'sort_order', 1, 0, 0, 1),
(119, 100, 4, 2, 'size="50"', 'contact_attachment_ext', '', '', '', 11.4, 0, 0, 1),
(120, 100, 6, 4, '', 'show_entry_block', '', '', '1', 1, 0, 0, 1),
(121, 1, 3, 1, '', 'num_entries_per_page', '', '10,20,40', '10', 2, 0, 0, 1),
(122, 1, 3, 2, 'size="10"', 'app_width', '', '', '980px', 1, 1, 0, 1),
(123, 1, 2, 2, 'size="10"', 'auth_expired', '', '', '60', 0, 0, 0, 1),
(130, 100, 1, 2, 'size="50"', 'nav_extra', '', '', '', 11, 0, 1, 1),
(126, 100, 1, 1, '', 'mod_rewrite', '', '1,2,3,9', '1', 15, 0, 0, 1),
(127, 1, 1, 1, '', 'auth_captcha', '', 'no,yes', 'no', 1, 0, 0, 1),
(128, 1, 5, 2, 'style="width: 100%"', 'html_editor_upload_dir', '', '', '[document_root]/kb_upload/', 1, 1, 1, 1),
(129, 100, 3, 4, '', 'show_map_link', '', '', '1', 19, 0, 0, 0),
(131, 100, 2, 1, '', 'login_policy', '', '1,2,9', '1', 11, 0, 0, 1),
(132, 100, 8, 4, '', 'view_header', '', '', '1', 7, 0, 0, 1),
(133, 100, 1, 3, 'rows="2" style="width: 100%"', 'footer_info', '', '', '', 9, 0, 1, 0),
(134, 150, 1, 2, 'size="50"', 'license_key', '', '', '', 1, 1, 1, 1),
(135, 100, 8, 1, '', 'view_menu_type', '', 'tree,followon', 'followon', 5, 0, 0, 1),
(136, 1, 6, 2, 'style="width: 100%"', 'cache_dir', '', '', '[document_root_parent]/kb_cache/', 2, 1, 1, 0),
(137, 100, 3, 1, '', 'nav_prev_next', '', 'yes,yes_no_others', 'yes', 8.4, 0, 0, 1),
(138, 134, 1, 2, 'size="50"', 'noreply_email', '', '', '[noreply_email]', 4, 1, 0, 1),
(139, 150, 1, 2, 'size="50"', 'license_key2', '', '', '', 2, 0, 0, 1),
(140, 150, 1, 2, 'size="50"', 'license_key3', '', '', '', 3, 0, 0, 1);

--
DROP TABLE IF EXISTS `kbp_setting_input`;
--
CREATE TABLE `kbp_setting_input` (
  `id` tinyint(3) unsigned NOT NULL auto_increment,
  `input` varchar(20) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM   ;

--
INSERT INTO `kbp_setting_input` (`id`, `input`) VALUES
(1, 'select'),
(2, 'text'),
(3, 'textarea'),
(4, 'checkbox'),
(5, 'password');

--
DROP TABLE IF EXISTS `kbp_user_company`;
--
CREATE TABLE `kbp_user_company` (
  `id` int(10) NOT NULL auto_increment,
  `title` varchar(255) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `phone` varchar(30) NOT NULL default '',
  `phone2` varchar(30) NOT NULL default '',
  `fax` varchar(30) NOT NULL default '',
  `address` varchar(255) NOT NULL default '',
  `address2` varchar(255) NOT NULL default '',
  `city` varchar(30) NOT NULL default '',
  `state` varchar(2) NOT NULL default '',
  `zip` varchar(11) NOT NULL default '',
  `country` tinyint(3) unsigned NOT NULL default '0',
  `url` varchar(255) NOT NULL default '',
  `description` text,
  `custom` longtext,
  `active` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM   ;

--
DROP TABLE IF EXISTS `kbp_user_role`;
--
CREATE TABLE `kbp_user_role` (
  `id` int(11) NOT NULL auto_increment,
  `parent_id` int(11) NOT NULL default '0',
  `title` varchar(50) NOT NULL default '',
  `description` text NOT NULL,
  `sort_order` tinyint(3) unsigned NOT NULL default '0',
  `active` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`,`parent_id`),
  KEY `parent_id` (`parent_id`)
) TYPE=MyISAM   ;

--
INSERT INTO `kbp_user_role` (`id`, `parent_id`, `title`, `description`, `sort_order`, `active`) VALUES
(1, 0, 'Customers', '', 2, 1),
(2, 0, 'Employees', '', 1, 1),
(3, 0, 'Contractors', '', 2, 1),
(4, 7, 'Programmer', '', 3, 1),
(5, 7, 'Tech Writer', '', 2, 1),
(6, 7, 'Beta Tester', '', 1, 1),
(7, 3, 'Manager', '', 1, 1),
(8, 0, 'Partners', '', 3, 1);

--
DROP TABLE IF EXISTS `kbp_user_to_role`;
--
CREATE TABLE `kbp_user_to_role` (
  `user_id` int(10) unsigned NOT NULL default '0',
  `role_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`user_id`,`role_id`)
) TYPE=MyISAM;

