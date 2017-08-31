UPDATE `kbp_setting` SET `setting_key` = 'article_sort_order' WHERE `id` = 146;


--
INSERT INTO `kbp_setting` VALUES (276,1,0,0,14,4,'','allow_create_tags','','',1,1,0,0,1);


--
UPDATE `kbp_setting` SET `sort_order` = '0.5' WHERE `id` = 264;
--
UPDATE `kbp_setting` SET `input_id` = 7 WHERE `id` IN (268, 269);
--
INSERT INTO `kbp_setting` VALUES (277,160,0,0,7,1,'','remote_auth_group_type','','static,dynamic','dynamic',1,0,0,1),
                                 (278,160,0,0,7,2,'','remote_auth_group_attribute','','','member',2,0,0,1),
                                 (279,160,0,0,4,7,'','remote_auth_map_group_to_priv','','','',5,0,0,1),
                                 (280,160,0,0,4,7,'','remote_auth_map_group_to_role','','','',6,0,0,1);
