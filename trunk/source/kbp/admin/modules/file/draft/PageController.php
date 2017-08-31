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


$controller->loadClass('FileDraft');
$controller->loadClass('FileDraftAction');
$controller->loadClass('FileDraftModel');
$controller->loadClass('FileDraftModelBulk');
$controller->loadClass('FileDraftView_common');

$controller->loadClass('KBDraftView_approval_history', 'knowledgebase/draft');
// $controller->loadClass('KBDraftView_bulk', 'knowledgebase/draft');

$controller->loadClass('FileEntry', 'file/entry');
$controller->loadClass('FileEntryAction', 'file/entry');
$controller->loadClass('FileEntryView_common', 'file/entry');
$controller->loadClass('FileEntryModel', 'file/entry');
$controller->loadClass('FileEntryDownload_dir', 'file/entry');
$controller->loadClass('FileEntryModel_dir', 'file/entry');

require_once 'core/common/CommonEntryView.php';
require_once 'core/common/CommonCustomFieldView.php';
require_once 'eleontev/Item/PersonHelper.php';
require_once APP_MODULE_DIR . 'user/user/inc/UserActivityLog.php';

// initialize objects
$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);
$rp->setSkipKeys(array('entry_obj'));
$controller->rp = &$rp;


$obj = new FileDraft;
$manager = new FileDraftModel();

$eobj = new FileEntry;
$emanager = new FileEntryModel_dir;

$action = new FileDraftAction($rq, $rp);
$eaction = new FileEntryAction($rq, $rp);

$manager->checkPriv($priv, $controller->action, @$rq->id, @$rp->bulk_action, $controller, $emanager);

$setting = SettingModel::getQuick(1);
$setting = $emanager->setFileSetting($setting);

$file_dir = $emanager->getSetting('file_dir');
$draft_dir = $file_dir . 'draft/';


switch ($controller->action) {
    
case 'delete': // ------------------------------

    $view = $action->delete($obj, $manager, $controller);
    break;

case 'entry_update': // ------------------------------

    $view = $action->noteUpdateEntry($obj, $manager, $controller, $priv, $emanager);
    break;


case 'category': // ------------------------------

    $view = $controller->getView($obj, $emanager, 'FileDraftView_category');
    break;


case 'tags': // ------------------------------

    $controller->loadClass('KBEntryView_tags', 'knowledgebase/entry');
    $view = new KBEntryView_tags;
    $view = $view->execute($obj, $emanager);
    break;


case 'approval_lock': // ------------------------------

    $view = $action->noteApprovalLock($obj, $manager, $controller);
    break;


case 'role': // ------------------------------

    $controller->loadClass('UserView_role', 'user/user');
    $view = new UserView_role_private();
    $view = $view->execute($obj, $emanager);
    break;


case 'file': // ------------------------------

    $view = $action->sendFile($manager, $emanager, $controller, true);
    break;


case 'fopen': // ------------------------------

    $view = $action->sendFile($manager, $emanager, $controller, false);
    break;

// case 'text': // ------------------------------
//
//     $view = $eaction->fileText($eobj, $emanager, $controller);
//     break;


case 'detail': // ------------------------------

    $data = $manager->getById($rq->id);
    $eobj = unserialize($data['entry_obj']);
    $eobj->restore($emanager);

    $eobj->setAuthor($manager->getUser($eobj->get('author_id')));
    $eobj->setUpdater($manager->getUser($eobj->get('updater_id')));
    $eobj->set('date_updated', $data['date_updated']);

    RequestDataUtil::stripVars($data, array('entry_obj'), 'display');
    $obj->set($data);

    $controller->loadClass('FileEntryView_detail', 'file/entry');

    $detail_view = new FileEntryView_detail;
    $detail_view->draft_view = true;
    $detail_view->page = 'file_draft';

    $view = $detail_view->execute($eobj, $emanager, array($obj, $manager));
    break;


case 'bulk': // ------------------------------

    if(isset($rp->submit) && !empty($rp->id)) {

        $rp->stripVars();

        $ids = $rp->id;
        $action = $rp->bulk_action;

        $bulk_manager = new FileDraftModelBulk;
        $bulk_manager->setManager($manager);

        switch ($action) {
            case 'assignee': // ------------------------------
                $bulk_manager->setAssignee($ids, $rp->assignee, $controller);
                break;
                
            case 'delete': // ------------------------------
                $bulk_manager->delete($ids);
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

            $options = array('file_dir' => $file_dir);
            $entry_id = $action->publish($eobj, $emanager, $manager, $controller, $priv, $options);
            $manager->moveToStep($rq->id, $workflow['id'], $next_step_num, $step_title, $rp->step_comment, 3);

            $manager->addApprovalHistory($rq->id, $entry_id, $emanager->entry_type);
            $manager->deleteApprovalLog($rq->id);
            $manager->deleteAssignees($rq->id);

            $sent = $manager->sendDraftPublication($obj, $controller, $step_comment,
                                                        $s_event['user_id'], $entry_id);
                                                        
            UserActivityLog::add('file', 'create', $entry_id);
            
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

	$view = $controller->getView($obj, $manager, 'FileDraftView_approval', array($eobj, $emanager));

    break;


case 'update': // ------------------------------
case 'insert': // ------------------------------

    $controller->loadClass('FileEntryView_form', 'file/entry');
    require_once 'eleontev/Dir/Uploader.php';
    require_once 'eleontev/Util/TimeUtil.php';

    $rp->setSkipKeys(array('schedule', 'schedule_on'));

    $emanager->num_files_upload = 1;

    if(isset($rp->submit)) { // saving as a draft

        $entry_id = (!empty($rq->entry_id)) ? $rq->entry_id : false;
        $is_error = $obj->validate($rp->vars, $controller->action, $entry_id);

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

			if($_FILES['file_1']['name']) {
			    $dir = (!empty($rq->entry_id) || $eobj->get('id')) ? $draft_dir : $file_dir;

                $action->upload($eobj, $emanager, $obj, $manager, $controller, $dir);

                if (!$obj->error) {
                    $draft_id = $manager->save($obj);
                    if ($draft_id) {
                        $obj->set('id', $draft_id);
                    }
                    
                    $controller->setRequestVar('id', $draft_id);
                    
                    if(!empty($rq->referer)) {
                        $controller->setCustomPageToReturn($rq->referer);
                    }

                    $controller->go();
                }

			} else { // updating, the old file remains

			    if (!empty($rq->entry_id)) {
                    $filename = ($eobj->get('filename_disk')) ? $eobj->get('filename_disk') : $eobj->get('filename');
			        $copy_params = $manager->copyFileToDrafts($filename, $eobj->get('directory'), $draft_dir);

                    if ($copy_params['status']) {
                        $eobj->set('directory', $draft_dir);

                        if ($copy_params['new_filename']) {
                            $eobj->set('filename', $copy_params['new_filename']);
                        }

                    } else {
                        $more['show_msg'] = 'error_copying_draft';
                        $controller->goPage('this', 'this', false, false, $more);
                    }
			    }

                $obj->populate($rp->vars, $eobj, $emanager);

			    $draft_id = $manager->save($obj);

                if(!empty($rq->referer)) {
                    $controller->setCustomPageToReturn($rq->referer);
                }

                $controller->go();
			}

        }

    // publishing
    } elseif(isset($rp->submit_publish) || isset($rp->submit_approve)) {

        $is_error = $eobj->validate($rp->vars, $controller->action, $emanager);

        if($is_error) {
            $rp->stripVars(true);
            $eobj->populate($rp->vars, $manager, true);
            $obj->set($rp->vars['draft']);

            if (!empty($rq->id)) {
                $data = $manager->getById($rq->id);
                $obj->set('date_updated', $data['date_updated']);
            }

            if ($obj->get('author_id')) {
                $obj->setAuthor($manager->getUser($obj->get('author_id')));
                $obj->setUpdater($manager->getUser($eobj->get('updater_id')));
            }

        // no error
        } else {
            $rp->stripVars();
            $eobj->populate($rp->vars, $emanager);

            if (!empty($rp->draft_author)) { // overwriting
                $eobj->set('author_id', $manager->user_id);
            }

            $obj->populate($rp->vars, $eobj, $emanager);

            $workflow = $manager->getAppliedWorkflow();

            if($_FILES['file_1']['name']) { // we have a file

                $action->upload($eobj, $emanager, $obj, $manager, $controller, $file_dir);

                if (empty($obj->error)) { // valid
                    if ($workflow) { // submission
                        $draft_id = $manager->save($obj);
                        if ($draft_id) {
                            $obj->set('id', $draft_id);
                        }

                        $action->submitForApproval($eobj, $emanager, $obj, $manager, $controller, $workflow);
                        $controller->go('success', false, false, 'skip');

                    } else { // approval isn't needed, free to publish
                        $options = array('file_dir' => $file_dir, 'check_private' => 1);
                        $entry_id = $action->publish($eobj, $emanager, $manager, $controller, $priv, $options);
                        
                        UserActivityLog::add('file', 'create', $entry_id);
                        $controller->go('success', false, false, 'skip');
                    }
                }

            } else { // no file
                if(!FileEntryDownload_dir::getFileDir($eobj->get(), $file_dir)) { // missing
                    $eobj->setError('file_not_exist_msg');

                } else {

                    if ($workflow) { // submission

                        $obj->populate($rp->vars, $eobj, $emanager);
			            $draft_id = $manager->save($obj);
                        if ($draft_id) {
                            $obj->set('id', $draft_id);
                        }

                        $action->submitForApproval($eobj, $emanager, $obj, $manager, $controller, $workflow);
                        $controller->go();

                    } else { // publishing
                        $options = array('file_dir' => $file_dir, 'check_private' => 1);
                        $entry_id = $action->publish($eobj, $emanager, $manager, $controller, $priv, $options);
                        
                        UserActivityLog::add('file', 'create', $entry_id);
                        $controller->go('success', false, false, 'publish');
                    }
                }
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


    $view = $controller->getView($obj, $manager, 'FileDraftView_form', array($eobj, $emanager));

    break;



default: // ------------------------------------

    $view = $controller->getView($obj, $manager, 'FileDraftView_list', $emanager);
}
?>