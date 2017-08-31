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


$controller->loadClass('FileEntry');
$controller->loadClass('FileEntryAction');
$controller->loadClass('FileEntryModel');
$controller->loadClass('FileEntryModel_dir');
$controller->loadClass('FileEntryModelBulk');
$controller->loadClass('FileEntryDownload_dir');
$controller->loadClass('FileEntryView_common');
//$controller->loadClass('FileEntryModel_db');
//$controller->loadClass('FileEntryDownload_db');
require_once 'eleontev/HTML/DatePicker.php';
require_once 'eleontev/Dir/Uploader.php';
require_once 'eleontev/Util/TimeUtil.php';


// initialize objects
$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);
$rp->setSkipKeys(array('schedule', 'schedule_on'));
$controller->rp = &$rp;


$obj = new FileEntry;
$action = new FileEntryAction($rq, $rp);
$manager =& $obj->setManager( new FileEntryModel_dir );

// settings
$setting = SettingModel::getQuick(1);
$setting = $manager->setFileSetting($setting);
$manager->checkPriv($priv, $controller->action, @$rq->id, $controller->getMoreParam('popup'), @$rp->bulk_action);


switch ($controller->action) {
case 'delete': // ------------------------------

    // inline
    $as_related = $manager->getEntryToAttachment($rq->id, '2,3'); // inline and attached
    if($as_related) {
        $more = array('id'=>$rq->id, 'rtype'=>'remove');
        $more = array_merge($controller->getFullPageParams(), $more);
        $controller->goPage('file', 'file_entry', false, 'ref_remove', $more);
    }


    // attached only, could be safely removed from table attachment_to_entry
    if(!isset($rq->ignore_reference)) {
        $as_related = $manager->getEntryToAttachment($rq->id, '1'); // attached only
        if($as_related) {
            $more = array('id'=>$rq->id, 'rtype'=>'notice');
            $more = array_merge($controller->getFullPageParams(), $more);
            $controller->goPage('file', 'file_entry', false, 'ref_notice', $more);
        }
    }
    
    if (isset($rp->submit_yes) || isset($rp->submit_no)) {
        $from_disk = (isset($rp->submit_yes));
        $manager->delete($rq->id, $from_disk);
        
        $controller->go();
        
    } 
    
    if (!isset($rp->submit)) {
        $draft_id = $manager->isEntryDrafted($rq->id);
        if($draft_id) {
            $more = array('id' => $rq->id);
            $controller->goPage('file', 'file_entry', false, 'draft_remove', $more);
        }
    }
    
    $data = $manager->getById($rq->id);
    $file_dir = $manager->getSetting('file_dir');
    
    if(!FileEntryDownload_dir::getFileDir($data, $file_dir)) { // missing
        $obj->is_missing = true;
    }
    
    $controller->removeMoreParams('show_msg2');
    $rp->stripVarsValues($data);
    $obj->set($data);

    $view = $controller->getView($obj, $manager, 'FileEntryView_delete');

    break;


case 'ref_notice': // ------------------------------
case 'ref_remove': // ------------------------------

    $data = $manager->getById($rq->id);
    $rp->stripVarsValues($data);
    $obj->set($data);

    $view = $controller->getView($obj, $manager, 'FileEntryView_reference', $controller->action);

    break;
    
    
case 'draft_remove': // ------------------------------

    $controller->loadClass('KBEntryAction', 'knowledgebase/entry');
    $action = new KBEntryAction($rq, $rp);
    $view = $action->draftRemove($obj, $manager, $controller, $priv);
    break;
    
    
case 'move_to_draft': // ------------------------------

    // inline
    $as_related = $manager->getEntryToAttachment($rq->id, '2,3'); // inline and attached
    if($as_related) {
        $more = array('id' => $rq->id, 'rtype' => 'remove_draft');
        $more = array_merge($controller->getFullPageParams(), $more);
        $controller->goPage('file', 'file_entry', false, 'ref_remove', $more);
    }


    // attached only, could be safely removed from table attachment_to_entry
    if(!isset($rq->ignore_reference)) {
        $as_related = $manager->getEntryToAttachment($rq->id, '1'); // attached only
        if($as_related) {
            $more = array('id'=>$rq->id, 'rtype'=>'notice');
            $more = array_merge($controller->getFullPageParams(), $more);
            $controller->goPage('file', 'file_entry', false, 'ref_notice', $more);
        }
    }
    
    $controller->loadClass('KBEntryAction', 'knowledgebase/entry');
    $action = new KBEntryAction($rq, $rp);
    $draft_id = $action->createDraftFromEntry($obj, $manager, $controller, $rq->id);
    
    $manager->delete($rq->id);
    
    $more = array('id' => $draft_id);
    $return = $controller->getLink('file', 'file_draft', false, 'update', $more);
    $controller->setCustomPageToReturn($return, false);
    $controller->go();
    
    break;
    

case 'status': // ------------------------------

    $manager->status($rq->status, $rq->id);
    $controller->go();

    break;


case 'category': // ------------------------------

    $view = $controller->getView($obj, $manager, 'FileEntryView_category');

    break;


case 'role': // ------------------------------

    $controller->loadClass('UserView_role', 'user/user');
    $view = new UserView_role_private();
    $view = $view->execute($obj, $manager);

    break;


case 'file': // ------------------------------

    $view = $action->sendFile($obj, $manager, $controller, true);
    break;
    
    
case 'fopen': // ------------------------------

    $view = $action->sendFile($obj, $manager, $controller, false);
    break;
    

case 'text': // ------------------------------

    $view = $action->fileText($obj, $manager, $controller);
    break;
    
    
case 'tags': // ------------------------------

    $controller->loadClass('KBEntryView_tags', 'knowledgebase/entry');
    $view = new KBEntryView_tags;
    $view = $view->execute($obj, $manager);

    break;
    
    
case 'approval_log':

    $controller->loadClass('KBEntryView_approval_log', 'knowledgebase/entry');
    
	$obj->set('id', $rq->id);
    $data = $manager->getById($rq->id);
    $rp->stripVarsValues($data);
    $obj->set($data);
    
    $view = new KBEntryView_approval_log;
    $view = $view->execute($obj, $manager);
    
    break;
    
    
case 'bulk': // ------------------------------

    if(isset($rp->submit) && !empty($rp->id)) {

        $rp->stripVars();

        $ids = $rp->id;
        $action = $rp->bulk_action;

        $bulk_manager = new FileEntryModelBulk();
        $bulk_manager->setManager($manager);
        
        $drafted_entries = $manager->getDraftedEntries($manager->idToString($ids)); 
        if(!empty($drafted_entries)) {
            $ids = array_diff($ids, $drafted_entries);
        }
        
        if (!empty($ids)) {
            
            switch ($action) {
            case 'delete': // ------------------------------
    
                break;
    
            case 'remove': // ------------------------------
                $bulk_manager->delete($ids);
                break;
    
            case 'status': // ------------------------------
                $bulk_manager->status($rp->value['status'], $ids);
                break;
    
            case 'category_move': // -----------------------------
                $bulk_manager->setCategoryMove($rp->value['category'], $ids);
                break;
    
            case 'category_add': // -------------------------
                $bulk_manager->setCategoryAdd($rp->value['category'], $ids);
                break;
    
            case 'private': // ------------------------------
                $pr = (isset($rp->value['private'])) ? $rp->value['private'] : 0;
                $bulk_manager->setPrivate($rp->value, $pr, $ids);
    
                break;
    
            case 'public': // ------------------------------
                $bulk_manager->setPublic($ids);
                break;
    
            case 'schedule': // ------------------------------
                if($rp->value['schedule_action'] == 'set') {
                    $bulk_manager->setSchedule($rp->schedule_on, $rp->schedule, $ids);
                } else {
                    $bulk_manager->removeSchedule($ids);
                }
    
                break;
    
            case 'sort_order': // ------------------------------
                $bulk_manager->setSortOrder($rp->value['sort_order'], $ids);
                break;
    
            case 'parse': // ------------------------------
                $bulk_manager->parse($rp->value['parse'], $ids);
                $bulk_manager->addSphinxRebuildTask($manager->entry_type);
                break;
    
            case 'tag': // ------------------------------
                $bulk_manager->setTags($rp->tag, $ids, $rp->value['tag_action']);
                break;
    
            case 'custom': // ------------------------------
                $bulk_manager->setCustomData($rp->value['custom'], $ids, $rp->value);
                break;
            }
        }
        
        if (!empty($drafted_entries)) {
            $f = implode(',', $drafted_entries);
            $more = array('filter[q]' => $f, 'show_msg2' => 'note_drafted_entries_bulk');
            $controller->goPage('file', 'file_entry', false, false, $more);
        }

        $controller->go();
    }

    $controller->goPage('main');

    break;


case 'detail': // ------------------------------

    $data = $manager->getById($rq->id);
    $rp->stripVarsValues($data);
    $obj->collect($rq->id, $data, $manager, $controller->action);

    $view = $controller->getView($obj, $manager, 'FileEntryView_detail');
    
    break;
    

case 'clone': // ------------------------------
case 'update': // ------------------------------
case 'insert': // ------------------------------

    if(isset($rp->submit) || isset($rp->submit_attach)) {

        $is_error = $obj->validate($rp->vars, $controller->action, $manager);

        if($is_error) {
            $rp->stripVars(true);
            $obj->populate($rp->vars, $manager, true);

        } else {

            $rp->stripVars();
            $obj->populate($rp->vars, $manager);

            $files = array();
            foreach($_FILES as $file) {
                if (!empty($file['name'])) {
                    $files[] = $file;
                }
            }

            if (!empty($files)) {
                $errors = array();

                foreach ($files as $f) {

                    // when updating and upload file with the same name we need replace old file 
                    // if new name we need delete old file from disk
                    $rename_file = true;
                    if($controller->action == 'update') {
                        $old_file = FileEntryDownload_dir::getFileDir($obj->get(), $manager->getSetting('file_dir'));
                        $fname = ($obj->get('filename_disk')) ? $obj->get('filename_disk') : $obj->get('filename');
                        if($f['name'] == $fname) {
                            $rename_file = false;
                        }
                    }
                    

                    $upload = $manager->upload($rename_file, $f);

                    if(!empty($upload['error_msg'])) {
                        $rp->stripVars('stripslashes_display');
                        $obj->set($rp->vars);
                        $errors = array_merge_recursive($errors, $upload['error_msg']);

                    } else {

                        $content = $manager->getFileContent($upload['good'][1]['to_read']);

                        if($content) {

                            $file_id = $manager->saveFileData($content, $obj->get('id'));

                            $obj->populateFile($upload['good'][1], $manager);

                            $entry_id = $manager->save($obj, $controller->action, true);
                            // $obj->set('id', $entry_id); // commented 12 June 2017 as we have duplicate id error if multiple files

                            // uploaded file
                            $obj->success_files[] = $obj->get('filename');

                            // when updating need to delete old file
                            if($controller->action == 'update' && $rename_file) {
                                unlink($old_file);
                            }


                            // referer
                            if(!empty($rq->referer)) {
                                $controller->setCustomPageToReturn($rq->referer);
                                if(strpos($rq->referer, 'client') !== false) {
                                    $link = $controller->getClientLink(array('files', $obj->get('category_id')));
                                    $controller->setCustomPageToReturn($link, false);
                                }
                            }

                        } else { // --> if($content)
                            $obj->errors['key'][] = array('msg'=>'not_uploaded');
                        }

                    }

                }

                // all files were uploaded
                if (empty($errors)) {

                    if ($controller->getMoreParam('popup') == 1 && isset($rp->submit_attach)) {
                        $_GET['attach_id'] = $entry_id;
                        $controller->setMoreParams('attach_id');
                    }

                    $controller->go('success');

                // some files were not uploaded
                } else {

                    // get error box for all files
                    $obj->error = Uploader::errorBox($errors);

                    if ($controller->action == 'insert') {
                        $_SESSION['formobj_'] = serialize($obj);
                        $more = array('formobj' => 1);
                        
                        if (!empty($rq->popup)) {
                            $more['popup'] = $rq->popup; 
                        }
                        
                        $controller->goPage('this', 'this', false, 'this', $more);
                    }
                }


            } else { // no file - only if update possible

                //if not to change date updated
                //$data = $manager->getById($rq->id);
                //$obj->set('date_updated', $data['date_updated']);

                $entry_id = $manager->save($obj, $controller->action, false);
                $obj->set('id', $entry_id);
                
                // referer
                if(!empty($rq->referer)) {
                    $controller->setCustomPageToReturn($rq->referer);
                    if(strpos($rq->referer, 'client') !== false) {
                        $link = $controller->getClientLink(array('files', $obj->get('category_id')));
                        $controller->setCustomPageToReturn($link, false);
                    }
                }

                $controller->go('success');
            }
        }

    } elseif(in_array($controller->action, array('update', 'clone'))) {

        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data);
        $obj->collect($rq->id, $data, $manager, $controller->action);
        

    } elseif($controller->action == 'insert') {

        $status = ListValueModel::getListDefaultEntry('file_status');
        $status = ($status !== null) ? $status : $obj->get('active');
        $obj->set('active', $status);

        if(!empty($rq->filter['c']) && intval($rq->filter['c']) && $rq->filter['c'] != 'all') {
            $gfc = array($rq->filter['c']);
            if(!$manager->isCategoryNotInUserRole($gfc)) {
                $obj->setCategory($gfc);
            }
        }
    }


    // if redirected after upload error
    if (isset($rq->formobj) && !isset($rp->submit) && !empty($_SESSION['formobj_'])) {
        $obj = unserialize($_SESSION['formobj_']);
    }

    // in case post size exseeded
    if(isset($_SERVER['CONTENT_LENGTH'])) {
        if($post_max_size = Uploader::getIniValue('post_max_size')) {
            if($_SERVER['CONTENT_LENGTH'] > $post_max_size) {
                $msgs = AppMsg::getMsgs('error_msg.ini');
                $msg['title'] = $msgs['error_title_msg'];
                $msg['body'] = $msgs['post_max_size_msg'];
                $obj->error = BoxMsg::factory('error', $msg);
            }
        }
    }
    
    
    // drafts
    $actions = array('update');
    if(in_array($controller->action, $actions)  && !isset($rq->ajax)) {
        if(!isset($rp->submit) && !isset($rq->skip_draft)) {
            
            // draft
            if($draft_id = $manager->isEntryDrafted($rq->id)) {
                $rlink = $controller->getCommonLink();
                $referer = WebUtil::serialize_url($rlink);
                $more = array('id' => $draft_id, 'referer' => $referer);

                $controller->goPage('this', 'file_draft', false, 'entry_update', $more);
            }

        }
    }

    $view = $controller->getView($obj, $manager, 'FileEntryView_form');

    break;


default: // ------------------------------------

    // sort order
    if(isset($rp->submit)) {
        $category_id = $rq->filter['c'];
        foreach ($rp->sort_id as $sort_value => $entry_id) {
            $manager->saveSortOrder($entry_id, $category_id, $sort_value);
        }
    }

    if(isset($_SESSION['formobj_'])) {
        $_SESSION['formobj_'] = array();
        unset($_SESSION['formobj_']);
    }

    $view = $controller->getView($obj, $manager, 'FileEntryView_list');
}
?>