<?php

require_once 'eleontev/Assorted.inc.php';
require_once 'eleontev/Dir/MyDir.php';
require_once 'eleontev/Dir/mime_content_type.php'; 
require_once 'inc/FileDirectoryModel.php';
require_once APP_MODULE_DIR . 'file/entry/inc/FileEntry.php';
require_once APP_MODULE_DIR . 'file/draft/inc/FileDraft.php'; 
require_once APP_MODULE_DIR . 'file/draft/inc/FileDraftAction.php';


function spyDirectoryFiles($rule_id = false) {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    
    $model = new FileDirectoryModel;
    $model->fe_model = new FileEntryModel_dir;
    $model->fe_model->error_die = false;
    
    $model->fd_model = new FileDraftModel;
    $model->fd_model->error_die = false;
    $model->fd_model->mail_use_pool = true;
    
    $rq = array();
    $rp = array('step_comment' => '');
    $draft_action = new FileDraftAction($rq, $rp);
    
    $entry_type = $model->fe_model->entry_type;
    $rules = $model->getRules();

    if ($rules === false) {
        $exitcode = 0;
        return $exitcode;
    }

    if (empty($rules)) {
        $cron->logNotify('There are no directory rules');
        return $exitcode;
    }

    if($rule_id) {
        if(!isset($rules[$rule_id])) {
            $cron->logNotify('There is no directory rule with ID: %d', $rule_id);
            return $exitcode;            
        }
        
        $rules_[$rule_id] = $rules[$rule_id];
        $rules = $rules_;
    }


    // settings
    $setting = SettingModel::getQuickCron(1);
    if ($setting === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    $setting = $model->fe_model->setFileSetting($setting);
    $model->setting = &$model->fe_model->setting;
    
    $cat_records = $model->fe_model->getCategoryRecords();
    
    $added = 0;
    $updated = 0;
    $deleted = 0;
    $statused = 0;
    $skipped = 0;
    $submitted = 0;

    // get files for directory rules
    foreach ($rules as $rule_id => $rule) {
    
        if(!is_readable($rule['directory'])) {
            $cron->logCritical('Directory (%s) does not exist or it is not readable', $rule['directory']);
            $exitcode = 0;
            continue;
        }
        
        // files in db for add/update
        $files_db = $model->getFiles($rule['directory'], $rule['parse_child']);
        if($files_db === false) {
            $exitcode = 0;
            continue;
        }
        
        // checking categories
        $obj = unserialize($rule['entry_obj']);
        foreach($obj->getCategory() as $category_id) {
            if (empty($cat_records[$category_id])) {
                $cron->logCritical('Category is missing (Rule ID: %s, Category ID: %s). Skipping to the next rule...', $rule_id, $category_id);
                $exitcode = 0;
                continue 2;
            }
        }
        
        
        if ($rule['is_draft']) {
            $drafts_db = $model->getDraftFiles($rule['directory'], $rule['parse_child']);
        }
        
        
        $one_level = (empty($rule['parse_child'])) ? true : false;
        $files_dir = &$model->fe_model->readDirectory($rule['directory'], $one_level);
        $files_dir = ExtFunc::multiArrayToOne($files_dir); 

        $sign = ($one_level) ? '' : '*';
        $cron->logNotify('%s file(s) found in a directory %s', count($files_dir), $rule['directory'] . $sign);
        
        $controller = new AppController();
        $controller->setWorkingDir();

        // echo 'files_db: ', print_r($files_db, 1);
        // echo 'files_dir: ', print_r($files_dir, 1);
        //exit;

        foreach(array_keys($files_dir) as $k) {
            
            $file = $files_dir[$k];
            $action = false;
            $file_id = null;
            $draft_id = null;
            $update_values = array();
            
                                     
            // file exists in db
            if (isset($files_db[$file])) {
                $match_file = $files_db[$file];

                // compare by md5hash
                if (!empty($match_file['md5hash'])) {
                    $hash = md5_file($file);
                    if ($hash != $match_file['md5hash']) {
                        $action = 'update';
                    }

                // compare by date_updated
                } else {
                    $date_modified = filemtime($file);      
                    $date_update = strtotime($match_file['date_updated']);
                    if ($date_modified > $date_update) {
                        $action = 'update';
                    }
                }
 
                if($action) {
                    $file_id = $match_file['id'];
                    $keep_keys = array('date_posted', 'author_id', 'downloads');
                    foreach($keep_keys as $v) {
                        $update_values[$v] = $match_file[$v];
                    }
                }
                
            } elseif ($rule['is_draft'] && isset($drafts_db[$file])) {
                
                $match_file = $drafts_db[$file];

                // compare by md5hash
                if (!empty($match_file['md5hash'])) {
                    $hash = md5_file($file);
                    if ($hash != $match_file['md5hash']) {
                        $action = 'update';
                    }

                // compare by date_updated
                } else {
                    $date_modified = filemtime($file);      
                    $date_update = strtotime($match_file['date_updated']);
                    if ($date_modified > $date_update) {
                        $action = 'update';
                    }
                }
 
                if($action) {
                    $draft_id = $match_file['draft_id'];
                    $keep_keys = array('date_posted', 'author_id');
                    foreach($keep_keys as $v) {
                        $update_values[$v] = $match_file[$v];
                    }
                }
                
            } else {
                $action = 'insert';
            }

            if($action) {
                $data = $model->getData($file);
                
                $obj = unserialize($rule['entry_obj']);
                $updater_id = $obj->get('author_id');            
    
                $obj->set(array_merge($data, $update_values));
                $obj->set('id', $file_id);
                $obj->properties['updater_id'] = $updater_id;
                
                // as it is new field it could miss in $rule['entry_obj'] 20.09.2012
                $obj->set('filename_index', $data['filename_index']);
                $obj->set('filename_disk', $data['filename_disk']); // 23.07.2015
                
                // echo '<pre>', print_r($rule, 1), '</pre>';
                // echo '<pre>', print_r($obj, 1), '</pre>';
                // echo '<pre>', print_r($obj->get(), 1), '</pre>';
                // echo '<pre>', print_r(unserialize($rule['entry_obj']), 1), '</pre>';
                // echo '<pre>', print_r(array_merge($data, $update_values), 1), '</pre>';
                // continue;
                
                if ($rule['is_draft'] && empty($file_id)) {
                    
                    $draft_obj = new FileDraft;
                    $draft_obj->populate($rp->vars, $obj, $model->fe_model);
                    $draft_obj->set('id', $draft_id);
                    $draft_obj->set('updater_id', $updater_id);
                    
                    $ret = $model->addDraft($draft_obj, $action);
                    
                    if ($ret && $action == 'insert') {
                        $options = array(
                            'source' => 'dir_rule', 
                            'user_id' => $updater_id
                            );
                        
                        $workflow = $model->fd_model->getAppliedWorkflow($options);
                        
                        // echo '<pre>', print_r($workflow, 1), '</pre>';
                        if ($workflow) {
                            $draft_obj->set('id', $ret);
                            $step_comment = 'Automatically submitted via directory rules.';
                            
                            $draft_action->submitForApproval($obj, $model->fe_model, $draft_obj, $model->fd_model, $controller, $workflow, $step_comment);
                            $submitted ++;
                        }
                    }
                    
                } else {
                    $ret = $model->addFile($obj, $action);
                }
                
                // on error we do not die, mark as skipped
                if ($ret === false) {
                    $cron->logCritical('Error adding file (%s), skipped.', $file);
                    $exitcode = 0;
                    $skipped ++;
                    
                } else {
                    
                    if ($action == 'insert') { 
                        $added ++;
                    } else {
                        $updated ++;
                    }
                }
            }
            
        } // --> foreach(array_keys($files_dir) as $k) {
        

        // removed, all files added, updated already for the rule 
        $files_db_key = array();
        foreach(array_keys($files_db) as $k) {
            $files_db_key[$files_db[$k]['id']] = $k;
        }
                    
        $files_missed = array_diff($files_db_key, $files_dir);
        
        if($files_missed) {

            $sign = ($one_level) ? '' : '*';
            $cron->logInform('Found links to %d file(s) in database but files are not present in directory %s', 
                                count($files_missed), $rule['directory'] . $sign);
            $cron->logInform("Missing file(s) ids: %s", implode(",", array_keys($files_missed)));
            
            $file_ids = array_keys($files_missed);
            $missed_action = $setting['directory_missed_file_policy'];

            if(strpos($missed_action, 'status') !== false) {
                $status = (int) preg_replace('#[^\d]#', '', $missed_action);                    
                $ret = $model->setFileStatus($file_ids, $status);
                if($ret) {
                    $statused += count($files_missed);
                } else {
                    $exitcode = 0;
                }

            } elseif($missed_action == 'delete') {
                $ret = $model->deleteFile($file_ids);
                if($ret) {
                    $deleted += count($files_missed);
                } else {
                    $exitcode = 0;
                }
            }
        }


        if(!$model->updateExecution($rule_id)) {
            $exitcode = 0;
        }
        
    } // -> foreach ($rules as $rule_id => $rule) {


    $cron->logNotify('%d file(s) added.', $added);
    $cron->logNotify('%d file(s) updated.', $updated);
    $cron->logNotify('%d file(s) skipped.', $skipped);
    $cron->logNotify('%d file(s) changed status.', $statused);
    
    if ($submitted) {
        $cron->logNotify('%d file(s) submitted for approval.', $submitted);
    }
    
    // if($skipped) {
        // $cron->logInform('%d file(s) skipped. Error on adding to database.', $skipped); // send by email
    // }
    
    if(!$deleted) {
        $cron->logNotify('%d file(s) deleted.', $deleted);
    } else {
        $cron->logInform('%d file(s) deleted.', $deleted); // send by email
    }
    
    return $exitcode;
}

?>