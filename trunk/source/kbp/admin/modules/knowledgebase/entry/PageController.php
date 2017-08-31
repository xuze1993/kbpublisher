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


$controller->loadClass('KBEntry');
$controller->loadClass('KBEntryAction');
$controller->loadClass('KBEntryModel');
$controller->loadClass('KBEntryHistory');
$controller->loadClass('KBEntryHistoryModel');
$controller->loadClass('KBEntryView_common');

require_once 'eleontev/Item/PersonHelper.php';
require_once 'eleontev/HTML/DatePicker.php';
require_once 'eleontev/Diff.php';
require_once 'eleontev/Util/TimeUtil.php';
require_once 'core/common/CommonEntryView.php';
require_once 'core/common/CommonCustomFieldView.php';



// initialize objects
$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);
$rp->setHtmlValues('body'); // to skip $_GET['body'] not strip html
$rp->setCurlyBracesValues('body');
$rp->setSkipKeys(array('schedule', 'schedule_on'));
$controller->rp = &$rp;

$obj = new KBEntry;

$action = new KBEntryAction($rq, $rp);

$manager =& $obj->setManager( new KBEntryModel() );
$manager->checkPriv($priv, $controller->action, @$rq->id, $controller->getMoreParam('popup'), @$rp->bulk_action);

$controller->setMoreParams('show_msg2');

// include 'inc/populate.php';

switch ($controller->action) {
case 'delete': // ------------------------------

    $as_related = $manager->getEntryToRelated($rq->id, '2,3');
    if($as_related) {
        $f = sprintf('related-inline:%d', $rq->id);
        $controller->goPage('knowledgebase', 'kb_entry', false, false,
                            array('filter[q]'=>$f, 'show_msg2'=>'note_remove_reference'));
    }

    // draft
    if($draft_id = $manager->isEntryDrafted($rq->id)) {
        $rlink = $controller->getCommonLink();
        $referer = WebUtil::serialize_url($rlink);
        $more = array('id' => $rq->id, 'referer' => $referer);

        $controller->goPage('this', 'this', false, 'draft_remove', $more);
    }
    
    
    $data = $manager->getById($rq->id);
    $obj->collect($rq->id, $data, $manager, 'save');
    
    $manager->trash($rq->id, $obj);
    
    // referer
    if(!empty($rq->referer)) {
        $controller->setCustomPageToReturn($rq->referer);
        if(strpos($rq->referer, 'client') !== false) {
            $link = $controller->getClientLink(array('index', $rq->category_id));
            $controller->setCustomPageToReturn($link, false);
        }
    }
    
    $controller->go('success', false, false, 'trash');
    break;
    
    
case 'draft_remove': // ------------------------------

    $view = $action->draftRemove($obj, $manager, $controller, $priv);
    break;
        
    
case 'move_to_draft': // ------------------------------

    $as_related = $manager->getEntryToRelated($rq->id, '2,3');
    if($as_related) {
        $f = sprintf('related-inline:%d', $rq->id);
        $controller->goPage('knowledgebase', 'kb_entry', false, false,
                            array('filter[q]'=>$f, 'show_msg2'=>'note_remove_reference'));
    }
    
    $draft_id = $action->createDraftFromEntry($obj, $manager, $controller, $rq->id);
    
    $manager->deleteOnTrash($rq->id);
    
    $more = array('id' => $draft_id);
    $return = $controller->getLink('knowledgebase', 'kb_draft', false, 'update', $more);
    $controller->setCustomPageToReturn($return, false);
    $controller->go();
    
    break;
    

case 'preview': // ------------------------------

    if(!empty($rq->id)) {

        $data = $manager->getById($rq->id);
        if($data) {
            $rp->stripVarsValues($data);
            $obj->set($data);
            $obj->setCustom($manager->cf_manager->getCustomDataById($rq->id));
        }
    }
    
    $view = $controller->getView($obj, $manager, 'KBEntryView_preview');
    break;


case 'category': // ------------------------------

    $view = $controller->getView($obj, $manager, 'KBEntryView_category');
    break;


// called from public emode 
case 'category2': // ------------------------------
    
    if (!empty($rq->main_category_id)) {
        $obj->set('category_id', $rq->main_category_id);
    }
    $view = $controller->getView($obj, $manager, 'KBEntryView_category2');
    break;
    

case 'template': // ------------------------------

    $view = $controller->getView($obj, $manager, 'KBEntryView_template');
    break;


case 'role': // ------------------------------

    $controller->loadClass('UserView_role', 'user/user');
    $view = new UserView_role_private();
    $view = $view->execute($obj, $manager);
    break;
    
    
case 'tags': // ------------------------------

    $view = $controller->getView($obj, $manager, 'KBEntryView_tags');
    break;


case 'lock': // ------------------------------

    $view = $action->lock($obj, $manager, $controller);
    break;
    

case 'autosave': // ------------------------------

    $view = $action->autosave($obj, $manager, $controller);
    break;


case 'advanced': // ------------------------------ // popup in public area
    
    $obj->populate($rp->vars, $manager, true);
    
    $view = $controller->getView($obj, $manager, 'KBEntryView_advanced');
    break;
    
    
case 'custom_field': // ------------------------------ // popup in public area
    
    $obj->populate($rp->vars, $manager, true);
    
    $view = $controller->getView($obj, $manager, 'KBEntryView_custom_field');
    break;
    

case 'history': // ------------------------------

    $data = $manager->getById($rq->id);
    $rp->stripVarsValues($data);
    $obj->set($data);
    
    $obj2 = new KBEntryHistory;
    $manager2 = new KBEntryHistoryModel();
    
    $view = $controller->getView($obj2, $manager2, 'KBEntryHistoryView_list', array($data, $obj, $manager));

    break;


case 'diff': // ------------------------------

    $live_data = $manager->getById($rq->id);
    $rp->stripVarsValues($live_data);
    $obj->set($live_data);
    $obj->set('date_updated', $live_data['date_updated']);
    $obj->setAuthor($manager->getUser($live_data['author_id']));
    $obj->setUpdater($manager->getUser($live_data['updater_id']));
    $obj->set('history_comment', $live_data['history_comment']);

    $obj2 = new KBEntryHistory;
    $manager2 = new KBEntryHistoryModel();


    // left revision
    if (empty($rq->vnum2) || $rq->vnum2 == 'live') { // left revision is live
        $left_rev = array();
        $left_rev['revision_num'] = 'live';
        $left_rev['comment'] = $obj->get('history_comment');
        $left_rev['entry_updater_id'] = $obj->get('updater_id');
        $left_rev['entry_date_updated'] = $obj->get('date_updated');
        $left_rev['body'] = $obj->get('body');

    } else {
        $left_rev = $manager2->getHistoryById($rq->id, $rq->vnum2);
        $entry_data = unserialize($left_rev['entry_data']);
        $left_rev['body'] = $entry_data['body'];
        $rp->stripVarsValues($left_rev);
    }


    $right_rev = $manager2->getHistoryById($rq->id, $rq->vnum);
    $entry_data = unserialize($right_rev['entry_data']);
    $right_rev['body'] = $entry_data['body'];
    $rp->stripVarsValues($right_rev);

    // left-vnum2, right-vnum
    $revisions = array('left' => $left_rev, 'right' => $right_rev);
    
    $view = $controller->getView($obj2, $manager2, 'KBEntryHistoryView_diff', 
                                        array($revisions, $live_data, $obj, $manager));

    break;


case 'hpreview': // ------------------------------

    $data = $manager->getById($rq->id);

    $obj = new KBEntryHistory;
    $manager = new KBEntryHistoryModel();

    $history_data = &$manager->getVersionData($rq->id, $rq->vnum);
    if(!$history_data) {
        $extra = array('id'=>$rq->id, 'show_msg'=>'error_get_history_version');
        $controller->goPage('this', 'this', false, 'history', $extra);
    }

    $data = $history_data + $data;
    $rp->stripVarsValues($data);

    $data2 = $manager->getHistoryById($rq->id, $rq->vnum);
    $rp->stripVarsValues($data2);
    $obj->set($data2);

    $view = $controller->getView($obj, $manager, 'KBEntryHistoryView_preview', $data);

    break;


case 'rollback': // ------------------------------

    // lock
    if($manager->isEntryLocked($rq->id)) {
        $extra = array('id'=>$rq->id, 'back'=>$controller->action);
        if(!empty($rq->referer)) {
            $extra['referer'] = $rq->referer;
        }

        $controller->goPage('this', 'this', false, 'lock', $extra);
    }
    
    // draft
    if($draft_id = $manager->isEntryDrafted($rq->id)) {
        $rlink = $controller->getLink('knowledgebase', 'kb_entry', false, 'history', array('id' => $rq->id));
        $referer = WebUtil::serialize_url($rlink);
        $more = array('id' => $draft_id, 'referer' => $referer, 'vnum' => $rq->vnum);

        $controller->goPage('this', 'kb_draft', false, 'entry_update', $more);
    }


    $h_manager = new KBEntryHistoryModel();

    $history_data = &$h_manager->getVersionData($rq->id, $rq->vnum);
    if(!$history_data) {
        $extra = array('id'=>$rq->id, 'show_msg'=>'error_get_history_version');
        $controller->goPage('this', 'this', false, 'history', $extra);
    }

    // save history
    $current_data = $manager->getById($rq->id);
    if($allowed_rev = KBEntryHistoryModel::getHistoryAllowedRevisions()) {
        $version_data = &$h_manager->parseVersionData($current_data);
        $hdata['entry_updater_id'] = $current_data['updater_id'];
        $hdata['entry_date_updated'] = $current_data['date_updated'];
        $hdata['comment'] = RequestDataUtil::stripVars($current_data['history_comment'], array(), 'addslashes');
        $h_manager->addRevision($rq->id, $version_data, $manager->user_id, $hdata);
        $h_manager->removeExtraRevisions($rq->id, $allowed_rev);
    }

    // rollback
    $current_data = $history_data + $current_data;
    $rp->stripVarsValues($current_data, 'addslashes');
    $obj->set($current_data);
    $obj->set('date_updated', NULL);
    $obj->set('updater_id', $manager->user_id);
    $obj->set('body_index', RequestDataUtil::getIndexText($current_data['body']));

    $msg = AppMsg::getMsg('common_msg.ini', 'knowledgebase');
    $comment = str_replace('{num}', $rq->vnum, $msg['rollback_comment_msg']);
    $obj->set('history_comment', $comment);

    $manager->update($obj);

    $controller->go();
    break;


case 'hdelete': // ------------------------------

    $h_manager = new KBEntryHistoryModel();
    $h_manager->deleteRevisionAll($rq->id);
	
	$return = $controller->getLink('this', 'this', false, 'history', array('id'=>$rq->id));
    $controller->setCustomPageToReturn($return, false);
    $controller->go();
    break;


case 'file': // ------------------------------

    $h_manager = new KBEntryHistoryModel();

    if (isset($rq->html)) {
        if (isset($rq->vnum)) {
            $data = $h_manager->getHistoryById($rq->id, $rq->vnum);
            $entry_data = unserialize($data['entry_data']);

            $data['id'] = $data['entry_id'];
            $data['body'] = $entry_data['body'];
        } else {
            $data = $manager->getById($rq->id);
            $data['revision_num'] = $h_manager->getEntryMaxVersion($rq->id) + 1;
        }

        $h_manager->sendFileDownloadHtml($data);
        exit;
    }

    if (extension_loaded('zip')) {
        $max_revision = $h_manager->getEntryMaxVersion($rq->id) + 1;
        $live_rev = $manager->getById($rq->id);
        $selected_rev = $h_manager->getHistoryById($rq->id, $rq->vnum);

        $h_manager->sendFileDownload($live_rev, $selected_rev, $max_revision);
        exit;

    } else {
        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data);
        $obj->set($data);

        $manager2 = new KBEntryHistoryModel();

        $data2 = &$manager2->getHistoryById($rq->id, $rq->vnum);
        $rp->stripVarsValues($data2);

        $view = $controller->getView($obj, $manager, 'KBEntryHistoryView_download', $data2);
    }

    break;
        
    
case 'approval_log':

	$obj->set('id', $rq->id);
    $data = $manager->getById($rq->id);
    $rp->stripVarsValues($data);
    $obj->set($data);
            
    $view = $controller->getView($obj, $manager, 'KBEntryView_approval_log');
    
    break;
    
    
case 'bulk': // ------------------------------

    if(isset($rp->submit) && !empty($rp->id)) {

        $rp->stripVars();

        $ids = $rp->id;
        $action = $rp->bulk_action;

        $bulk_manager = new KBEntryModelBulk();
        $bulk_manager->setManager($manager);
        
        
        // not allowed update entries with drafts
        $drafted_entries = $manager->getDraftedEntries($manager->idToString($ids)); 
        if(!empty($drafted_entries)) {
            $ids = array_diff($ids, $drafted_entries);
        }
        
        
        // triggers
        // $controller->loadClass('TriggerBulkSavingHelper', 'tool/trigger');
        // $trigger = new TriggerBulkSavingHelper($manager, $rp);

        
        if (!empty($ids)) {
        
            switch ($action) {
                
            // case 'delete': // ------------------------------
            //     $not_deleted = $bulk_manager->delete($ids);
            //     if($not_deleted) {
            //         $f = implode(',', $not_deleted);
            //         $more = array('filter[q]'=>$f, 'show_msg2'=>'note_remove_reference_bulk');
            //         $controller->goPage('knowledgebase', 'kb_entry', false, false, $more);
            //     }
            // 
            //     break;
                
            case 'trash': // ------------------------------
                $not_deleted = $bulk_manager->trash($ids);
                if($not_deleted) {
                    $f = implode(',', $not_deleted);
                    $more = array('filter[q]'=>$f, 'show_msg2'=>'note_remove_reference_bulk');
                    $controller->goPage('knowledgebase', 'kb_entry', false, false, $more);
                }
    
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
    
            case 'meta_description': // ------------------------------
                $bulk_manager->setMetaDescription($rp->value['meta_description'], $ids);
                break;
    
            case 'external_link': // ------------------------------
                $bulk_manager->setExternalLink($rp->value['external_link'], $ids);
                break;
    
            case 'type': // ------------------------------
                $bulk_manager->setEntryType($rp->value['type'], $ids);
                break;
    
            case 'sort_order': // ------------------------------
                $bulk_manager->setSortOrder($rp->value['sort_order'], $ids);
                break;
    
            case 'rate_reset': // ------------------------------
                $bulk_manager->resetRate($ids);
                break;
    
            case 'tag': // ------------------------------
                $bulk_manager->setTags($rp->tag, $ids, $rp->value['tag_action']);
                break;
    
            case 'custom': // ------------------------------
                $bulk_manager->setCustomData($rp->value['custom'], $ids, $rp->value);
                break;

            case 'author': // ------------------------------
                $bulk_manager->setAuthor($rp->value['author'], $rp->value['updater'], $ids);
                break;
            }
        
        }
        
        // $trigger->save();
        
        if (!empty($drafted_entries)) {
            $f = implode(',', $drafted_entries);
            $more = array('filter[q]' => $f, 'show_msg2' => 'note_drafted_entries_bulk');
            $controller->goPage('this', 'this', false, false, $more);
        }

        $controller->go();
    }

    $controller->goPage('main');

    break;


case 'detail': // ------------------------------

    $data = $manager->getById($rq->id);
    $rp->stripVarsValues($data);
    $obj->collect($rq->id, $data, $manager, $controller->action);

    $view = $controller->getView($obj, $manager, 'KBEntryView_detail');
    break;
    
    
case 'kb_comment': // ------------------------------

    $controller->loadClass('KBComment', 'knowledgebase/comment');
    $controller->loadClass('KBCommentModel', 'knowledgebase/comment');
    $controller->loadClass('KBCommentView_list_entry2', 'knowledgebase/comment');
    
    $data = $manager->getById($rq->id);
    $rp->stripVarsValues($data);
    $obj->collect($rq->id, $data, $manager, $controller->action);
    
    $view = $controller->getView($obj, $manager, 'KBEntryView_comment');
    break;
    
    
case 'kb_rate': // ------------------------------

    $controller->loadClass('KBRate', 'knowledgebase/rate');
    $controller->loadClass('KBRateModel', 'knowledgebase/rate');
    $controller->loadClass('KBRateView_list_entry2', 'knowledgebase/rate');
    
    $data = $manager->getById($rq->id);
    $rp->stripVarsValues($data);
    $obj->collect($rq->id, $data, $manager, $controller->action);
    
    $view = $controller->getView($obj, $manager, 'KBEntryView_rate');
    break;
    
    
case 'convert': // ------------------------------

    $view = $action->convert($obj, $manager, $controller);
    break;


case 'attachment': // ------------------------------

    $view = $action->attachment($obj, $manager, $controller);
    break;
    

case 'clone': // ------------------------------
case 'question': // ----------------------------
case 'update': // ------------------------------
case 'insert': // ------------------------------

    if(isset($rp->submit)) {

        $is_error = $obj->validate($rp->vars, $manager);

        if($is_error) {
            $rp->stripVars(true);
            $obj->populate($rp->vars, $manager, true);

        // no error
        } else {
/*
            $state_before = null;
            if(isset($rq->id)) {
                $data = $manager->getById($rq->id);
                $s_obj = new KBEntry;
                $s_obj->set($data);
                
                $tags = $manager->tag_manager->getTagByEntryId($rq->id);
                $s_obj->setTag(array_keys($tags));
                
                $state_before = $manager->getTrackedFields($s_obj);
            }*/

            
            $rp->stripVars();
            $obj->populate($rp->vars, $manager);
            $obj->set('body_index', RequestDataUtil::getIndexText($rp->vars['body']));


            // history
            $history_data = false;
            if($controller->action == 'update') {
                if($allowed_rev = KBEntryHistoryModel::getHistoryAllowedRevisions()) {
                    $history = new KBEntryHistoryModel();
                    $new_data = RequestDataUtil::stripslashes($obj->get());
                    $old_data = $manager->getById($rq->id);
                    $history_data = $history->compare($new_data, $old_data);

                    // add history comment if any
                    if($history_data && isset($rp->history_comment)) {
                        $obj->set('history_comment', $rp->history_comment);
                    }
                }
            }

            // echo '<pre>', print_r($old_data, 1), '</pre>';
            // exit;

            // save
            $entry_id = $manager->save($obj);
            $obj->set('id', $entry_id);
            $new_status = $manager->getStatusKey($entry_id, 1);
            
            $controller->setRequestVar('id', $entry_id);
            
            // $state_after = $manager->getTrackedFields($obj);
            // $manager->saveStates($state_before, $state_after);

            // history
            if($history_data) {
                $hdata['entry_updater_id'] = $old_data['updater_id'];
                $hdata['entry_date_updated'] = $old_data['date_updated'];
                $hdata['comment'] = RequestDataUtil::stripVars($old_data['history_comment'], array(), 'addslashes');
                $history->addRevision($entry_id, $history_data, $manager->user_id, $hdata);
                $history->removeExtraRevisions($entry_id, $allowed_rev);
            }

            // unlock, remove autosave
            $actions = array('update');
            if(in_array($controller->action, $actions)) {
                $manager->setEntryReleased($rq->id);
                $manager->deleteAutosave($rq->id);
            }

            // remove autosave
            $actions = array('clone', 'insert');
            if(in_array($controller->action, $actions)) {
                if(!empty($rp->id_key)) {
                    $manager->deleteAutosaveByKey($rp->id_key);
                }
            }

            if($controller->action == 'question') {
                $manager->setUserEntryPlaced($rq->question_id);
            }

            // continue editing
            if(isset($rp->continue_update)) {
                $more = array('id' => $entry_id);
                $controller->goPage('this', 'this', false, 'update', $more);
            }


            // referer
            if(!empty($rq->referer)) {
                $controller->setCustomPageToReturn($rq->referer);
                if(strpos($rq->referer, 'client') !== false) {
                    $link = $controller->getClientLink(array('entry', false, $entry_id));
                    $controller->setCustomPageToReturn($link, false);
                }
            }

            $controller->go('success', false, $need_approve_msg);
        }


    } elseif(in_array($controller->action, array('update', 'clone'))) {

        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data);
        $obj->collect($rq->id, $data, $manager, $controller->action);
        // echo '<pre>', print_r($obj, 1), '</pre>';

        // open revision in update, set body from history
        if(isset($rq->vnum)) {
            $manager2 = new KBEntryHistoryModel();
            $rev_data = $manager2->getHistoryById($rq->id, $rq->vnum);
            $rev_data = unserialize($rev_data['entry_data']);

            $rp->stripVarsValues($rev_data);
            $obj->set('body', $rev_data['body']);
        }


    } elseif($controller->action == 'question') {

        $data = $manager->getMemberQuestionById($rq->question_id);
        $rp->stripVarsValues($data);
        $obj->set($data);
        $obj->set('id', NULL);
        $obj->set('title', $data['title'] . ' - ' . $data['question']);
        $obj->set('body', nl2br($data['answer']));


    } elseif($controller->action == 'insert') {
        
        if (!empty($rq->emode)) {
            $rp->stripVars(true);
            $obj->populate($rp->vars, $manager, true);
        }

        $status = ListValueModel::getListDefaultEntry('article_status');
        $status = ($status !== null) ? $status : $obj->get('active');
        $obj->set('active', $status);

        if(!empty($rq->filter['c']) && intval($rq->filter['c']) && $rq->filter['c'] != 'all') {
            $gfc = array($rq->filter['c']);
            if(!$manager->isCategoryNotInUserRole($gfc)) {
                $obj->setCategory($gfc);
            }
        }
    }

    // if locked, if autosaved, if draft
    $actions = array('update');
    if(in_array($controller->action, $actions)  && !isset($rq->ajax)) {
        if(!isset($rp->submit)) {

            // lock
            if($manager->isEntryLocked($rq->id)) {
                $more = array('id'=>$rq->id);
                if(!empty($rq->referer)) {
                    $more['referer'] = $rq->referer;
                }
                
                if(!empty($rq->popup)) {
                    $more['popup'] = $rq->popup;
                }

                $controller->goPage('this', 'this', false, 'lock', $more);

            } else {
                $manager->setEntryLocked($rq->id);
            }

            // if autosaved
            if(!isset($rq->dkey)) {
                if($manager->isAutosaved($rq->id, $data['date_updated'])) {
                    $more = array('id'=>$rq->id);
                    if(!empty($rq->referer)) {
                        $more['referer'] = $rq->referer;
                    }
                    
                    if (!empty($rq->popup)) {
                        $more['popup'] = $rq->popup;
                    }

                    $controller->goPage('this', 'this', false, 'autosave', $more);
                }
            }
            
            // draft
            if($draft_id = $manager->isEntryDrafted($rq->id)) {
                $rlink = $controller->getCommonLink();
                $referer = WebUtil::serialize_url($rlink);
                $more = array('id' => $draft_id, 'referer' => $referer);
                
                if (!empty($rq->popup)) {
                    $more['popup'] = $rq->popup;
                }

                $controller->goPage('this', 'kb_draft', false, 'entry_update', $more);
            }

        }
    }


    // open autosave in form, in update and in insert
    if(isset($rq->dkey) && !isset($rq->ajax)) {
        if(!isset($rp->submit)) {

            if($data_draft = $manager->getAutosavedDataByKey($rq->dkey))  {

                $obj = unserialize($data_draft['entry_obj']);
                $rp->stripVarsValues($obj->properties);
                $obj->category = RequestDataUtil::stripVars($obj->category, array(), 'display');
                $obj->schedule = RequestDataUtil::stripVars($obj->schedule, array(), 'display');
                // $obj->custom = RequestDataUtil::stripVars($obj->custom, array(), 'display');


                if($controller->action == 'update') {
                    $data = $manager->getById($rq->id);
                    $obj->set('date_updated', $data['date_updated']);
                } else {
                    $obj->set('id', NULL);
                }

                $obj->restore($manager);
            }
        }
    }
    

    $view = $controller->getView($obj, $manager, 'KBEntryView_form');

    break;
    

default: // ------------------------------------

    // sort order
    if(isset($rp->submit)) {
        $category_id = $rq->filter['c'];
        foreach ($rp->sort_id as $sort_value => $entry_id) {
            $manager->saveSortOrder($entry_id, $category_id, $sort_value);
        }
    }

    $view = $controller->getView($obj, $manager, 'KBEntryView_list');

    // timestart('KBEntryView_list2');
    // $view = $controller->getView($obj, $manager, 'KBEntryView_list');
    // timestop('KBEntryView_list2');

}
?>