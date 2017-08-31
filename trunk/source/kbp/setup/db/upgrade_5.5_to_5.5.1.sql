UPDATE `kbp_letter_template` SET `from_email` = '[noreply_email]', `from_name` = '';
--
UPDATE `kbp_letter_template` SET `skip_field` = 'from';
--
UPDATE `kbp_letter_template` SET `skip_field` = 'from,to' WHERE id IN(1,29,4,30,31,32,33);
--
UPDATE `kbp_letter_template` SET `to_email` = '[email]' WHERE `id` = 1;
--
UPDATE `kbp_letter_template` SET `from_email` = '[support_email]',`from_name` = '[support_name]', `skip_field` = '' WHERE `id` = 2;
--
UPDATE `kbp_letter_template` SET `from_name` = '[name]' WHERE `id` IN (1,3,23);


--
UPDATE `kbp_list_value` SET `predifined` = 0 WHERE `id` IN (3,4,7,8);


--
UPDATE `kbp_setting` SET `required` = 1 WHERE `id` = 160; 


--
ALTER TABLE `kbp_entry_draft_workflow` CHANGE `date_posted` `date_posted` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
--
ALTER TABLE `kbp_entry_draft_workflow_history` CHANGE `date_posted` `date_posted` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;


--
UPDATE `kbp_article_template` SET `is_widget` = 1 WHERE `id` IN (2,3);
