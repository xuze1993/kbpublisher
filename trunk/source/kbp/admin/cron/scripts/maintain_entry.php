<?php

include_once 'inc/MaintainEntryModel.php';
include_once 'utf8/utils/validation.php';
include_once 'utf8/utils/bad.php';



// if tags updated in Tag module, 
// we should update keywords in meta_keywords
function syncTagKeywords($force_update = false) {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new MaintainEntryModel;

    $entry_types = array(1,2,3); // article, files, news
    $rule_id = 3; // sync_meta_keywords
    $updated = 0;
    
    // need to test if you need this
    // if($force_update) {
    //     return syncTagKeywordsForce($cron, $model, $rule_id, $force_update);
    // }

    $result =& $model->getEntryTasksResult($rule_id);
    if ($result === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    while($row = $result->FetchRow()) {
        $tag_id = $row['entry_id'];
        $tag_action = $row['value1'];
        // echo 'Task: ', print_r($row, 1);
        // exit;
        
        foreach($entry_types as $entry_type) {
        
            $result2 = $model->getEntryIdToTag($tag_id, $entry_type);
            if ($result2 === false) {
                $exitcode = 0;
                continue;
            }
            
            while($row2 = $result2->FetchRow()) {
                $entry_id = $row2['entry_id'];
                // echo 'entry_id: ', $entry_id, "\n";
                // exit;
                
                $tags = $model->getTagsToEntry($entry_id, $entry_type);
                if ($tags === false) {
                    $exitcode = 0;
                    continue;
                }
                
                // echo 'Tags to entry: ', print_r($tags, 1), "\n";
                // exit;
                
                $delim = TagModel::getKeywordDelimeter();
                $keywords = ($tags) ? addslashes(implode($delim, $tags)) : '';
                // echo 'keywords: ', $keywords, "\n";
                // exit;
                
                $ret = $model->setMetaKeyword($entry_id, $entry_type, $keywords);
                if($ret === false) {
                    $exitcode = 0;
                    continue;
                }
                                
                $updated += $ret;

            } // -> while
                        
        } // --> entry_type
        
        
        if($exitcode == 1) { // all is ok
            $ret = $model->statusEntryTask(0, $rule_id, $tag_id);
            if ($ret === false) {
                $exitcode = 0;
            }    
        }
        
    } // --> tasks


    $ret = $model->deleteEmptyTagToEntry($rule_id);
    if ($ret === false) {
        $exitcode = 0;
    } else {
        $ret = $model->removeEntryTasks($rule_id);
        if ($ret === false) {
            $exitcode = 0;
        }        
    }

    $cron->logNotify('%d record(s) updated.', $updated);

    return $exitcode;
}


// get all values from tag_to_entry, and update meta_keywords
function syncTagKeywordsForce($cron, $model, $rule_id, $entry_type) {
    $exitcode = 1;
    $updated = 0;
        
    $result = $model->getEntryIdToTagAll($entry_type);
    if ($result === false) {
        $exitcode = 0;
        return;
    }
    
    while($row = $result->FetchRow()) {
        $entry_id = $row['entry_id'];
        
        $tags = $model->getTagsToEntry($entry_id, $entry_type);
        if ($tags === false) {
            $exitcode = 0;
            continue;
        }
                
        $delim = TagModel::getKeywordDelimeter();
        $keywords = ($tags) ? addslashes(implode($delim, $tags)) : '';
        
        $ret = $model->setMetaKeyword($entry_id, $entry_type, $keywords);
        if($ret === false) {
            $exitcode = 0;
            continue;
        }
                        
        $updated += $ret;

    } // -> while


    $cron->logNotify('%d record(s) updated.', $updated);

    return $exitcode;
}


// convert meta_keywords to tags
// get tags from entry_task table, it is populated on upgrade to v5.0
// and in import articles ...
function updateTagKeywords() {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new MaintainEntryModel;

    $entry_type = 1; // array(1,2,3); // article, files, news
    $rule_id = 2; // update_meta_keywoeds, create tags from keywords
    $tag_visible = 0;
    
    $added = 0;
    $updated = 0;

    $result =& $model->getEntryTasksResult($rule_id);
    if ($result === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    while($row = $result->FetchRow()) {
        
        $entry_id = $row['entry_id'];
        $meta_keywords = $row['value1'];
        
        if(_strpos($meta_keywords, ',') !== false || _strpos($meta_keywords, ';') !== false) {
            $entry_keywords = preg_split('/[,;]/', $meta_keywords, -1, PREG_SPLIT_NO_EMPTY);
        } else {
            $entry_keywords = preg_split('/[\s]+/', $meta_keywords, -1, PREG_SPLIT_NO_EMPTY);
        }
        
        $entry_keywords = array_map(array('TagModel', 'parseTagOnAdding'), $entry_keywords);
        
        // echo $meta_keywords, "\n";
        // echo print_r($entry_keywords, 1);
        // echo "\n=============\n";
        // continue;
        
        foreach($entry_keywords as $keyword) {
            
            if(empty($keyword)) {
                continue;
            }
            
            $escaped_keyword = addslashes($keyword);
            $tag_id = $model->getTagIdByTitle($escaped_keyword);
            if ($tag_id === false) {
                $exitcode = 0;
                break;
            }

            if(!$tag_id) {
                $tag_id = $model->addTag($escaped_keyword, $tag_visible);
                if ($tag_id === false) {
                    $exitcode = 0;
                    break;
                }
                $added++;
            }
            
            $tags_to_entry = array($tag_id, $entry_id, $entry_type);
            $ret = $model->addTagsToEntry($tags_to_entry); 
            if ($ret === false) {
               $exitcode = 0;
               break;
            }
            
        }
        
        if($exitcode == 1) { // all is ok

            $tags = $model->getTagsToEntry($entry_id, $entry_type);
            if ($tags === false) {
               $exitcode = 0;
               continue;
            }

            $delim = TagModel::getKeywordDelimeter();
            $keywords = ($tags) ? addslashes(implode($delim, $tags)) : '';
            // echo 'keywords: ', $keywords, "\n";
            // exit;

            $ret = $model->setMetaKeyword($entry_id, $entry_type, $keywords);
            if($ret === false) {
               $exitcode = 0;
               continue;
            }

            $updated += $ret;

            $ret = $model->statusEntryTask(0, $rule_id, $entry_id);
            if ($ret === false) {
               $exitcode = 0;
            }
        }
    
    }

    $ret = $model->removeEntryTasks($rule_id);
    if ($ret === false) {
        $exitcode = 0;
    }

    $cron->logNotify('%d tag(s) added.', $added);
    $cron->logNotify('%d record(s) updated.', $updated);

    return $exitcode;
}


// $force_update  = 1 // update all articles 
// $force_update  = 3 // update all news
function updateBodyIndex($force_update = false) {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new MaintainEntryModel;

    $rule_id = 1; // update body_index    
    $updated = 0;

    if($force_update) {
        return updateBodyIndexForce($cron, $manager, $model, $rule_id, $force_update);
    }
     
    $result =& $model->getEntryTasksResult($rule_id);
    if ($result === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    while($row = $result->FetchRow()) {
        
        $body = $model->getBody($row['entry_id'], $row['entry_type']);
        if ($body === false) {
            $exitcode = 0;
        
        } elseif($body) {
            
            if(!utf8_compliant($body)) {
                $body = utf8_bad_replace($body, '');
            }
            
            $body_index = RequestDataUtil::getIndexText($body);
            $body_index = RequestDataUtil::addslashes($body_index);
            $ret = $model->updateBodyIndex($row['entry_id'], $row['entry_type'], $body_index);
            if ($ret === false) {
                $entry_type_msg = $manager->record_type[$row['entry_type']];
                $cron->logCritical('Cannot update index for %s id: %d.', $entry_type_msg, $row['entry_id']);
                $exitcode = 0;
            } else {
                $updated++;
                
                $ret = $model->statusEntryTask(0, $rule_id, $row['entry_id'], $row['entry_type']);
                if ($ret === false) {
                    $exitcode = 0;
                }
            }
            
        }
    }
  
    $ret =& $model->removeEntryTasks($rule_id);
    if ($ret === false) {
        $exitcode = 0;
    }    
  
    $cron->logNotify('%d index(s) updated.', $updated);

    return $exitcode;    
}


function updateBodyIndexForce($cron, $manager, $model, $rule_id, $entry_type) {
    $exitcode = 1;    
    $updated = 0;

    $result =& $model->getEntryBodyIndex($entry_type);
    if ($result === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    while($row = $result->FetchRow()) {
        
        if(!utf8_compliant($row['body'])) {
            $row['body'] = utf8_bad_replace($row['body'], '');
        }
        
        $body_index = RequestDataUtil::getIndexText($row['body']);
        $body_index = RequestDataUtil::addslashes($body_index);
        $ret = $model->updateBodyIndex($row['entry_id'], $row['entry_type'], $body_index);
        if ($ret === false) {
            $entry_type_msg = $manager->record_type[$row['entry_type']];
            $cron->logCritical('Cannot update index for %s id: %d.', $entry_type_msg, $row['entry_id']);
            $exitcode = 0;
        } else {
            $updated++;
        }
    }
  
    $cron->logNotify('%d index(s) updated.', $updated);

    return $exitcode;    
}


/*
function freshEntryHistory() {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new MaintainEntryModel;

    $entry_history_max = SettingModel::getQuickCron(1, 'entry_history_max');
    if ($entry_history_max === false) {
        $exitcode = 0;
        return $exitcode;
    }
    // echo 'entry_history_max: ', print_r($entry_history_max, 1), "\n";
    
    
    $deleted_empty = 0;
    $deleted_old = 0;
    
    $ret = $model->deleteHistoryEntryNoArticle();
    if ($ret === false) {
         $exitcode = 0;
    } else {
        $deleted_empty = $ret;
    }
    
    if(strtolower($entry_history_max) == 'all') {
        $cron->logNotify('Entry history is set to keep all revisions.');
        return $exitcode;        
    }

    if($entry_history_max == 0) {
        $cron->logNotify('Entry history is disabled.');
        return $exitcode;
    }

    if(!is_numeric($entry_history_max)) {
        $cron->logCritical('Incorect value for "Maximum number of revisions: %s"', $entry_history_max);
        $exitcode = 0;
        return $exitcode;
    }
    

    $result =& $model->getHistoryEntries($entry_history_max); 
    if ($result === false) {
        $exitcode = 0;
    
    } else {
        while ($row = $result->FetchRow()) {

            $rows_to_delete = (int) $row['num_revisions'] - $entry_history_max;

            // echo 'Entries: ', print_r($row, 1), "\n";
            // echo 'rows_to_delete: ', $rows_to_delete, "\n";
            // echo "\n============\n";
            
            if($rows_to_delete < 1) {
                $str = 'Cannot get paramenters to fresh history, $rows_to_delete: %d';
                $cron->logCritical($str, $rows_to_delete);
                $exitcode = 0;
                break;
            }
            
            $ret = $model->deleteHistoryEntryLimit($row['entry_id'], $rows_to_delete);
            if ($ret === false) {
                 $exitcode = 0;
                 break;
            }
            
            $deleted_old += $rows_to_delete;
        }
    }

    $cron->logNotify('%d records(s) deleted not assotiated with an article.', $deleted_empty);
    $cron->logNotify('%d old records(s) deleted.', $deleted_old);

    return $exitcode;
}
*/


function deleteHistoryEntryNoArticle() {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new MaintainEntryModel;

    $deleted = 0;
    
    $result = $model->deleteHistoryEntryNoArticle();
    if ($ret === false) {
         $exitcode = 0;
    } else {
        $deleted = $result;
    }

    $cron->logNotify('%d missed records(s) deleted.', $deleted);

    return $exitcode;
}


function freshEntryAutosave($seconds) {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new MaintainEntryModel;

    $result = $model->freshEntryAutosave($seconds);
    if ($result === false) {
        $exitcode = 0;
    }
    
    return $exitcode;
}


function unlockEntries($seconds) {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new MaintainEntryModel;

    $result = $model->unlockEntries($seconds);
    if (!$result) {
        $exitcode = 0;
    }
    
    return $exitcode;
}


function deleteDraftNoEntry() {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new MaintainEntryModel;

    $entry_types = array(1, 2); // article, files
    $deleted = 0;
       
    foreach($entry_types as $entry_type) {

        $result = $model->deleteDraftNoEntry($entry_type);
        if ($result === false) {
            $exitcode = 0;
            break;
        }
        
        $deleted += $result;
    }
    
    $cron->logNotify('%d missed records(s) deleted.', $deleted);
    
    return $exitcode;
}


function deleteWorkflowHistoryNoEntry() {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new MaintainEntryModel;

    $entry_types = array(1,2); // article, files
    $deleted = 0;
       
    foreach($entry_types as $entry_type) {

        $result = $model->deleteWorkflowHistoryNoEntry($entry_type);
        if ($result === false) {
            $exitcode = 0;
            break;
        }
        
        $deleted += $result;
    }
    
    $cron->logNotify('%d missed records(s) deleted.', $deleted);
    
    return $exitcode;
}


function deleteFeaturedNoEntry() {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new MaintainEntryModel;

    $entry_types = array(1,2); // article, files
    $deleted = 0;
       
    foreach($entry_types as $entry_type) {

        $result = $model->deleteFeaturedNoEntry($entry_type);
        if ($result === false) {
            $exitcode = 0;
            break;
        }
        
        $deleted += $result;
    }
    
    $cron->logNotify('%d missed records(s) deleted.', $deleted);
    
    return $exitcode;
}


function deleteExpiredForumAttachments() {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $model = new MaintainEntryModel;
    
    $result = $model->deleteExpiredForumAttachments();
    if ($result === false) {
        $exitcode = 0;
        return $exitcode;
    }

    $cron->logNotify('%d attachment(s) have been deleted.', $result);

    return $exitcode;
}

?>