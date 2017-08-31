<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KBPublisher package                              |
// | KPublisher - web based knowledgebase publishing tool                      |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2008 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+


$controller->loadClass('KBDraft');
$controller->loadClass('KBDraftAction');
$controller->loadClass('KBDraftModel');
$controller->loadClass('KBDraftModelBulk');
$controller->loadClass('KBDraftView_approval_history');
$controller->loadClass('KBDraftView_common');
$controller->loadClass('KBEntryView_preview', 'knowledgebase/entry');

$controller->loadClass('KBEntry', 'knowledgebase/entry');
$controller->loadClass('KBEntryAction', 'knowledgebase/entry');
$controller->loadClass('KBEntryModel', 'knowledgebase/entry');
$controller->loadClass('KBEntryView_common', 'knowledgebase/entry');
$controller->loadClass('KBEntryHistoryModel', 'knowledgebase/entry');

require_once 'core/common/CommonEntryView.php';
require_once 'core/common/CommonCustomFieldView.php';
require_once 'eleontev/Item/PersonHelper.php';
require_once APP_MODULE_DIR . 'user/user/inc/UserActivityLog.php';

// initialize objects
$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);
$rp->setSkipKeys(array('entry_obj'));
$controller->rp = &$rp;


$obj = new KBDraft;
$manager = new KBDraftModel();

$eobj = new KBEntry;
$emanager = new KBEntryModel;

$action = new KBDraftAction($rq, $rp);
$eaction = new KBEntryAction($rq, $rp);

$manager->checkPriv($priv, $controller->action, @$rq->id, @$rp->bulk_action, $controller, $emanager);


switch ($controller->action) {
    
case 'delete': // ------------------------------

    $view = $action->delete($obj, $manager, $controller);    
    break;
    
case 'entry_update': // ------------------------------

    $view = $action->noteUpdateEntry($obj, $manager, $controller, $priv, $emanager);
    break;
    
    
case 'category': // ------------------------------

    $controller->loadClass('KBEntryView_category', 'knowledgebase/entry');
    $view = new KBEntryView_category;
    $view = $view->execute($obj, $emanager);
    break;
    
    
case 'tags': // ------------------------------

    $controller->loadClass('KBEntryView_tags', 'knowledgebase/entry');
    $view = new KBEntryView_tags;
    $view = $view->execute($obj, $emanager);
    break;
    
    
case 'lock': // ------------------------------

    $view = $eaction->lock($obj, $manager, $controller);
    break;
        

case 'approval_lock': // ------------------------------
    
    $view = $action->noteApprovalLock($obj, $manager, $controller);
    break;
        
    
case 'autosave': // ------------------------------

    $view = $eaction->autosave($obj, $manager, $controller);
    break;
    
    
case 'role': // ------------------------------

    $controller->loadClass('UserView_role', 'user/user');
    $view = new UserView_role_private();
    $view = $view->execute($obj, $emanager);
    
    break;
    
     
case 'preview': // ------------------------------

    if(!empty($rq->id)) {
        $data = $manager->getById($rq->id);
        if($data) {
            $eobj = unserialize($data['entry_obj']);
            // $_data = $eobj->get();
            // RequestDataUtil::stripVars($_data, array(), 'stripslashes');
            // $eobj->set($_data);
        }
    }
    
    $controller->loadClass('KBEntryView_preview', 'knowledgebase/entry');        
    $view = new KBEntryView_preview;    
    $view = $view->execute($eobj, $emanager);

    break;
    
    
case 'detail': // ------------------------------

    $data = $manager->getById($rq->id);
    $eobj = unserialize($data['entry_obj']);
    $eobj->restore($emanager);
    
    $eobj->setAuthor($manager->getUser($eobj->get('author_id')));
    $eobj->setUpdater($manager->getUser($eobj->get('updater_id')));
    $eobj->set('date_updated', $data['date_updated']);
    
    RequestDataUtil::stripVars($data, array('entry_obj'), 'display');
    $obj->set($data);
    
    $controller->loadClass('KBEntryView_detail', 'knowledgebase/entry');
    
    $detail_view = new KBEntryView_detail;
    $detail_view->draft_view = true;
    $detail_view->page = 'kb_draft';
        
    $view = $detail_view->execute($eobj, $emanager, array($obj, $manager));
    break;


case 'bulk': // ------------------------------

    if(isset($rp->submit) && !empty($rp->id)) {

        $rp->stripVars();

        $ids = $rp->id;
        $action = $rp->bulk_action;

        $bulk_manager = new KBDraftModelBulk;
        $bulk_manager->setManager($manager);

        switch ($action) {
            case 'assignee': // ------------------------------
                $bulk_manager->setAssignee($ids, $rp->assignee, $controller);
                
                break;
            
            case 'delete': // ------------------------------
                $not_deleted = $bulk_manager->delete($ids);
                if($not_deleted) {
                    $f = implode(',', $not_deleted);
                    $more = array('filter[q]' => $f, 'show_msg2' => 'note_remove_draft_bulk');
                    $controller->goPage('this', 'this', false, false, $more);
                }
                break;           
        }

        $controller->go();
    }

    $controller->goPage('main');

    break;
    
    
case 'approval_log':

    $data = $manager->getById($rq->id);
    $rp->stripVarsValues($data);
    $obj->set($data);
    
    $view = new KBDraftView_approval_history;
    $view = $view->execute($obj, $manager, array(true, $emanager));

    break;
        

case 'approval':
	
    $rp->stripVars();
    
	$data = $manager->getById($rq->id);
    RequestDataUtil::stripVars($data, array('entry_obj'), 'display');
    $obj->set($data);
    
    $eobj = unserialize($data['entry_obj']);
    $eobj = RequestDataUtil::addslashesObj($eobj);
        
    $last_event = $manager->getLastApprovalEvent($rq->id);
    $assignees = $manager->getAssigneeByStepIds($last_event['id']);
    $workflow = $manager->wf_manager->getById($last_event['workflow_id']);
    $s_event = $manager->getLastSubmissionEvent($rq->id);

    $actions = $manager->wf_manager->unpackActions($workflow); 
    $workflow['action'] = $actions;
    
    if(isset($rp->submit_approve)) {
        
        $next_step_num = $last_event['step_num'] + 1;
        $step_title = addslashes($actions[$last_event['step_num']]['title']);
        $step_comment = stripslashes($rp->step_comment);
        
        // the next step is available, advancing
        if (count($actions) > $last_event['step_num']) {
            
            $assignees = $manager->getAssignees($obj, $eobj, $emanager, $workflow, $next_step_num);
            $step_id = $manager->moveToStep($rq->id, $workflow['id'], $next_step_num, $step_title, $rp->step_comment, 1);
            $manager->saveAssignees($rq->id, $step_id, $assignees);
            
            $sent = $manager->sendDraftReview($obj, $controller, $step_comment, $assignees);
            
            // unlock
            $manager->setEntryReleased($obj->get('id'));
            
            $controller->go('success', false, false, 'skip');
            
        // publishing
        } else {
        
            $entry_id = $action->publish($eobj, $emanager, $manager, $controller, $priv);
            $manager->moveToStep($rq->id, $workflow['id'], $next_step_num, $step_title, $rp->step_comment, 3);

            $manager->addApprovalHistory($rq->id, $entry_id, $emanager->entry_type);
            $manager->deleteApprovalLog($rq->id);
            $manager->deleteAssignees($rq->id);
            
            $sent = $manager->sendDraftPublication($obj, $controller, $step_comment, 
                                                        $s_event['user_id'], $entry_id);
            
            UserActivityLog::add('article', 'create', $eobj->get('id'));
            
            $controller->go('success', false, false, 'publish');
        }
            
                
    // rejecting
    } elseif(isset($rp->submit_reject)) {
        
        $prev_step_num = $last_event['step_num'] - 1;
        if (!empty($rp->step_num) && $rp->step_num == 'start') {
            $prev_step_num = 0;
        }
       
        $step_title = addslashes($actions[$last_event['step_num']]['title']);
        $step_comment = stripslashes($rp->step_rejection_comment);
        
        $assignees = $manager->getAssignees($obj, $eobj, $emanager, $workflow, $prev_step_num);
        $step_id = $manager->moveToStep($rq->id, $last_event['workflow_id'], $prev_step_num, $step_title, $rp->step_rejection_comment, 2);
        $manager->saveAssignees($rq->id, $step_id, $assignees);
        
        if ($prev_step_num == 0) {
            $sent = $manager->sendDraftRejectionToSubmitter($obj, $controller, 
                                                              $step_comment, $s_event['user_id']);

        } else {
            $sent = $manager->sendDraftRejectionToAssignee($obj, $controller, 
                                                              $step_comment, $last_event['user_id']);
        }

        // unlock
        $manager->setEntryReleased($rq->id);
        $controller->go('success', false, false, 'skip');
    }
    
    
    $obj->last_event = $last_event;
    $obj->active_workflow = $workflow;
    $obj->assignee = @$assignees[$last_event['id']];
    $obj->s_event = $s_event;
    
    if(!isset($rq->ajax)) {
        if(!isset($rp->submit_approve) && !isset($rp->submit_reject)) {
            if($manager->isEntryLocked($rq->id, $manager->entry_type)) {
        	    $more = array('id' => $rq->id);
        	    $controller->goPage('this', 'this', false, 'approval_lock', $more);
            
            } else {
                $manager->setEntryLocked($rq->id, $manager->entry_type);
            }
        }
    }    
    
	$view = $controller->getView($obj, $manager, 'KBDraftView_approval', array($eobj, $emanager));
    
    break;
        
    
case 'update': // ------------------------------
case 'insert': // ------------------------------

    $controller->loadClass('KBEntryView_form', 'knowledgebase/entry');
    require_once 'eleontev/HTML/DatePicker.php';
    require_once 'eleontev/Diff.php';
    require_once 'eleontev/Util/TimeUtil.php';

    $rp->setHtmlValues('body');
    $rp->setCurlyBracesValues('body');
    $rp->setSkipKeys(array('schedule', 'schedule_on'));    
    
    // saving as a draft
    if(isset($rp->submit) || isset($rp->submit_draft)) {

        $is_error = $obj->validate($rp->vars);

        if($is_error) {
            $rp->stripVars(true);
            $eobj->populate($rp->vars, $emanager, true);
            
            $obj->set($rp->vars['draft']);
            
            if (!empty($rq->id)) {
                $data = $manager->getById($rq->id);
                $obj->set('date_updated', $data['date_updated']);
            }
            
            if ($obj->get('author_id')) {
                $obj->setAuthor($manager->getUser($obj->get('author_id')));
                $obj->setUpdater($manager->getUser($obj->get('updater_id')));
            }
    
        // no error
        } else {
            $rp->stripVars();
            $eobj->populate($rp->vars, $emanager);
            
            if ($rp->updater_id) {
                $eobj->properties['updater_id'] = $rp->updater_id;
            }

            if(isset($rp->history_comment)) {
                $eobj->set('history_comment', $rp->history_comment);
            }
            
            $obj->populate($rp->vars, $eobj, $emanager);
            
            $draft_id = $manager->save($obj);
            if (!$draft_id) {
                $draft_id = $obj->get('id');
            }
            
            $controller->setRequestVar('id', $draft_id);
            
            // unlock, remove autosave
            $action->release($controller, $manager);

            // remove autosave
            $actions = array('insert');
            if(in_array($controller->action, $actions)) {
                if(!empty($rp->id_key)) {
                    $manager->deleteAutosaveByKey($rp->id_key);
                }
            }
            
            // continue editing
            if(isset($rp->continue_update)) {
                $more = array('id' => $draft_id);
                $controller->goPage('this', 'this', false, 'update', $more);
            }
            
            if(!empty($rq->referer)) {
                $controller->setCustomPageToReturn($rq->referer);
            }
            
            if (!empty($rp->assignee_redirect)) {
                $dialog = (isset($rp->submit_assignee_approve)) ? 'approve' : 'reject';
                $more = array('id' => $rq->id, 'dialog' => $rp->assignee_redirect);
                $link = $controller->getLink('this', 'this', 'this', 'approval', $more);
                $controller->setCustomPageToReturn($link, false);
            }
            
            $controller->go();
        }
    
    
    // publishing
    } elseif(isset($rp->submit_publish) || isset($rp->submit_approve)) {
        
        $is_error = $eobj->validate($rp->vars, $emanager);

        if($is_error) {
            $rp->stripVars(true);
            $eobj->populate($rp->vars, $emanager, true);
            $obj->set($rp->vars['draft']);
            
            if (!empty($rq->id)) {
                $data = $manager->getById($rq->id);
                $obj->set('date_updated', $data['date_updated']);
            }
            
            if ($obj->get('author_id')) {
                $obj->setAuthor($manager->getUser($obj->get('author_id')));
                $obj->setUpdater($manager->getUser($obj->get('updater_id')));
            }
    
        // no error
        } else {
            
            $rp->stripVars();
            $eobj->populate($rp->vars, $emanager);
            
            if (!empty($rp->draft_author)) { // overwriting
                $eobj->set('author_id', $manager->user_id);
            }
            
            if(isset($rp->history_comment)) {
                $eobj->set('history_comment', $rp->history_comment);
            }
            
            
            $workflow = $manager->getAppliedWorkflow();
            if ($workflow) { // submission
                
                $obj->populate($rp->vars, $eobj, $emanager);
                $draft_id = $manager->save($obj);
                if ($draft_id) {
                    $obj->set('id', $draft_id);
                }
                
                // remove autosave
                $actions = array('insert');
                if(in_array($controller->action, $actions)) {
                    if(!empty($rp->id_key)) {
                        $manager->deleteAutosaveByKey($rp->id_key);
                    }
                }
                
                $assignees = $manager->getAssignees($obj, $eobj, $emanager, $workflow, 1);
                $step_id = $manager->moveToStep($obj->get('id'), $workflow['id'], 1, '', $rp->step_comment, 1);
                $manager->saveAssignees($obj->get('id'), $step_id, $assignees);
                
                $step_comment = stripslashes($rp->step_comment);
                $sent = $manager->sendDraftReview($obj, $controller, $step_comment, $assignees);
                
                // unlock, remove autosave
                $action->release($controller, $manager);
            
                $controller->go('success', false, false, 'skip');
                
            } else { // approval isn't needed, free to publish
                
                $options = array('check_private' => 1);
                $action->publish($eobj, $emanager, $manager, $controller, $priv, $options);
                
                // unlock, remove autosave
                $action->release($controller, $manager);
                
                UserActivityLog::add('article', 'create', $eobj->get('id'));
                $controller->go('success', false, false, 'skip');
            }
        }
        
    } elseif($controller->action == 'update') {
        
        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data);
        
        $obj->set($data);
        $obj->setAuthor($manager->getUser($data['author_id']));
        $obj->setUpdater($manager->getUser($data['updater_id']));
        $obj->set('date_updated', $data['date_updated']);
        
        $last_event = $manager->getLastApprovalEvent($rq->id);
        if ($last_event) {
            $assignees = $manager->getAssigneeByStepIds($last_event['id']);
            
            $obj->last_event = $last_event;
            $obj->assignee = @$assignees[$last_event['id']];
        }

        // ongoing approval
        $last_event = $manager->getLastApprovalEvent($rq->id);
        if ($manager->isBeingApproved($last_event)) {
            $obj->sent_to_approval = true;
        }

        $eobj = unserialize($data['entry_obj']);
        $rp->stripVarsValues($eobj->properties);
        $eobj->restore($emanager);
        
        
    } elseif($controller->action == 'insert') {
        
        if(!empty($rq->entry_id)) {
            $entry_draft = $manager->getByEntryId($rq->entry_id, $emanager->entry_type);
            if (!empty($entry_draft)) {
                $extra = array('id' => $entry_draft['id']);
                if(!empty($rq->referer)) {
                    $extra['referer'] = $rq->referer;
                }
        
                $controller->goPage('this', 'this', false, 'entry_update', $extra);
            }
            
            $entry_data = $emanager->getById($rq->entry_id);
            $rp->stripVarsValues($entry_data);
            $eobj->collect($rq->entry_id, $entry_data, $emanager, $controller->action);
            
            $obj->set('entry_id', $rq->entry_id);
        }
        
        if(!empty($rq->filter['c']) && intval($rq->filter['c']) && $rq->filter['c'] != 'all') {
            $gfc = array($rq->filter['c']);
            if(!$emanager->isCategoryNotInUserRole($gfc)) {
                $eobj->setCategory($gfc);
            }
        }
    }
    
    // if locked, if autosaved
    $actions = array('update');
    if(in_array($controller->action, $actions)  && !isset($rq->ajax)) {
        if(!isset($rp->submit) &&
            !isset($rp->submit_draft) &&
            !isset($rp->submit_publish) &&
            !isset($rp->submit_approve)) {
            
            // lock
            if($manager->isEntryLocked($rq->id, $manager->entry_type)) {
                $more = array('id'=>$rq->id);
                if(!empty($rq->referer)) {
                    $more['referer'] = $rq->referer;
                }

                $controller->goPage('this', 'this', false, 'lock', $more);

            } else {
                $manager->setEntryLocked($rq->id, $manager->entry_type);
            }
            
            // if autosaved
            if(!isset($rq->dkey)) {
                if($manager->isAutosaved($rq->id, $data['date_updated'], $manager->entry_type)) {
                    $more = array('id' => $rq->id);
                    if(!empty($rq->referer)) {
                        $more['referer'] = $rq->referer;
                    }

                    $controller->goPage('this', 'this', false, 'autosave', $more);
                }
            }

        }
    }
    
    
    if(isset($rq->dkey) && !isset($rq->ajax)) {
        if(!isset($rp->submit_publish) && !isset($rp->submit_approve)) {

            if($data_draft = $manager->getAutosavedDataByKey($rq->dkey))  {

                $eobj = unserialize($data_draft['entry_obj']);
                $rp->stripVarsValues($eobj->properties);
                $eobj->category = RequestDataUtil::stripVars($eobj->category, array(), 'display');
                $eobj->schedule = RequestDataUtil::stripVars($eobj->schedule, array(), 'display');

                if($controller->action == 'update') {
                    $data = $manager->getById($rq->id);
                    $eobj->set('date_updated', $data['date_updated']);
                    
                } else {
                    $obj->set('entry_id', $eobj->get('id'));
                    if (!$eobj->get('id')) {
                        $eobj->set('id', NULL);
                    }
                }
                
                $eobj->restore($emanager);

            }
        }
    }
    
    
    $view = $controller->getView($obj, $manager, 'KBDraftView_form', array($eobj, $emanager));

    break;
    
    
case 'convert': // ------------------------------
    $view = $eaction->convert($eobj, $emanager, $controller);
    
    break;
    
    
default: // ------------------------------------
    
    $view = $controller->getView($obj, $manager, 'KBDraftView_list', $emanager);
}
?>