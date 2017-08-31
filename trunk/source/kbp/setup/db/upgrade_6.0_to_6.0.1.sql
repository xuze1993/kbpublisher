UPDATE kbp_setting SET setting_key = 'search_spell_bing_autosuggest_key' WHERE setting_key = 'search_spell_bing_autocomplete';
--
UPDATE kbp_setting SET setting_key = 'search_spell_bing_spell_check_key' WHERE setting_key = 'search_spell_bing_key';
--
UPDATE kbp_setting SET setting_key = 'search_spell_bing_spell_check_url' WHERE setting_key = 'search_spell_bing_url';
--
INSERT kbp_setting VALUES (378, 2, 0, 0, 16, 2, '', 'search_spell_bing_autosuggest_url', '', '', '', 12, 0, 1, 1);


--
ALTER TABLE `kbp_news` CHANGE `title` `title` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
--
ALTER TABLE `kbp_news` CHANGE `body` `body` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                       CHANGE `body_index` `body_index` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
