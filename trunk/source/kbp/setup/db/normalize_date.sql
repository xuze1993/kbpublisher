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