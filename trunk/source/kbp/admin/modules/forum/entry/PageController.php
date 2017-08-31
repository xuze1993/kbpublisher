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

$controller->loadClass('ForumEntry');
$controller->loadClass('ForumEntryModel');
$controller->loadClass('ForumMessage');
$controller->loadClass('ForumEntryView_common');

require_once 'eleontev/HTML/DatePicker.php';
require_once 'eleontev/Util/TimeUtil.php';


// initialize objects
$rq = new RequestData($_GET);
$rp = new RequestData($_POST);
$rp->setHtmlValues('body'); // to skip $_GET['body'] not strip html
$rp->setCurlyBracesValues('body');
//$rp->setCustomWordrap('external_link', 300);
$rp->setSkipKeys(array('schedule', 'schedule_on'));
$controller->rp = &$rp;

$obj = new ForumEntry;

$manager =& $obj->setManager(new ForumEntryModel());
$priv->setCustomAction('file', 'select');
$priv->setCustomAction('tags', 'select');
$manager->checkPriv($priv, $controller->action, @$rq->id, $controller->getMoreParam('popup'), @$rp->bulk_action);


if(isset($_GET['show_msg2'])) {
	$controller->setMoreParams('show_msg2');
	$controller->setCommonLink();
}

// include 'inc/populate.php';

switch ($controller->action) {
case 'delete': // ------------------------------
	
	$manager->delete($rq->id);
	$controller->go();

	break;
    

case 'category': // ------------------------------

	$view = &$controller->getView($obj, $manager, 'ForumEntryView_category');
	break;
    
    
case 'role': // ------------------------------

	$controller->loadClass('UserView_role', 'user/user');	
	$view = new UserView_role();
	$view = &$view->execute($obj, $manager);
	
	break;
    
    
case 'tags': // ------------------------------

    $controller->loadClass('KBEntryView_tags', 'knowledgebase/entry');
    $view = new KBEntryView_tags;
    $view = $view->execute($obj, $manager);

    break;
    
    
case 'bulk': // ------------------------------
	
	if(isset($rp->submit) && !empty($rp->id)) {
		
		$ids = $rp->id;
		$action = $rp->bulk_action;
		
		$bulk_manager = new ForumEntryModelBulk();
		$bulk_manager->setManager($manager);
		
		switch ($action) {
		case 'delete': // ------------------------------
			//$manager->delete($ids, true); // false to skip sort updating  ???
			$not_deleted = $bulk_manager->delete($ids);
			if($not_deleted) {
				$f = implode(',', $not_deleted);
				$controller->goPage('forum', 'forum_entry', false, false, 
				                     array('filter[q]'=>$f, 'show_msg2'=>'note_remove_reference_bulk'));
			}
			
			break;
		
		case 'status': // ------------------------------
			$bulk_manager->status($rp->value['status'], $ids);
			break;
		
		case 'forum_move': // -----------------------------
			$bulk_manager->setCategoryMove($rp->value['category'], array(), $ids);
			break;
			
		case 'category_add': // -------------------------
			$bulk_manager->setCategoryAdd($rp->value['category'], array(), $ids);
			break;		
			
		case 'private': // ------------------------------
			$pr = (isset($rp->value['private'])) ? $rp->value['private'] : 0;
			$bulk_manager->setPrivate($rp->value['role_read'], $pr, $ids);
			break;
		
		case 'public': // ------------------------------
			$bulk_manager->setPublic($ids);
			break;

		case 'schedule': // ------------------------------
			$rp->schedule = RequestDataUtil::stripVars($rp->schedule);
			$bulk_manager->setSchedule($rp->schedule_on, $rp->schedule, $ids);
			break;
      
		case 'unschedule': // ------------------------------
			$bulk_manager->removeSchedule($ids);
			break;
            
        case 'tag': // ------------------------------
            $bulk_manager->setTags($rp->tag, $ids, $rp->value['tag_action']);
            break;
            
        case 'sticky': // ------------------------------
            $bulk_manager->makeSticky($ids, $rp->value['sticky']);
            break; 	
      
        }	
		
		$controller->go();
	}
	
	$controller->goPage('main');
	
	break;	


case 'detail': // ------------------------------ 
    
    $data = $manager->getById($rq->id);
    $rp->stripVarsValues($data, true);
    $obj->set($data);
    $obj->set('date_updated', $data['date_updated']);
    $obj->setTag($manager->tag_manager->getTagByEntryId($rq->id));
    
    $sticky_date = $manager->getStickyDate($rq->id);
    if ($sticky_date) {
        $obj->setSticky(true);
        
        if (strtotime($sticky_date)) {
            $obj->setStickyDate($sticky_date);
        }
    }
    
    $view = &$controller->getView($obj, $manager, 'ForumEntryView_detail', $data);
    
    break;


case 'list': // ------------------------------ 
    
    $data = $manager->getById($rq->id);
    $rp->stripVarsValues($data, true);
    $obj->set($data);
    $obj->set('date_updated', $data['date_updated']); 
    
    $view = &$controller->getView($obj, $manager, 'ForumMessageView_list', $data);
    
    break;
    

case 'update': // ------------------------------
case 'insert': // ------------------------------
    
    if(isset($rp->submit) || isset($rp->submit_new)) {

        $is_error =& $obj->validate($rp->vars);

        if ($controller->action == 'insert' && empty($rp->message)) {
            $is_error = true;
        }
        
        if($is_error) {
            $rp->stripVars(true);
            
            $obj->set($rp->vars);
            $obj->setCategory(@$rp->vars['category']);
            
            if(isset($rq->id)) {
                $data = $manager->getById($rq->id);
                $obj->setAuthor($manager->getUser($data['author_id']));
                
                $ddiff = $data['tsu'] - $data['ts'];
                if($ddiff > $manager->update_diff) {
                    $obj->setUpdater($manager->getUser($data['updater_id']));                
                    $obj->set('date_updated', $data['date_updated']);
                }
            }
            
            if(!empty($rp->vars['tag'])) {
                $ids = implode(',', $rp->vars['tag']);
                $obj->setTag($manager->tag_manager->getTagByIds($ids));
            }
            
            if(!empty($rp->vars['sticky'])) {
                $obj->setSticky(true);
                
                if (!empty($rp->vars['sticky_to'])) {
                    $obj->setStickyDate($rp->vars['sticky_to']);
                }
            }
            
            /*if(!empty($rp->vars['role_read'])) {
                $obj->setRoleRead($rp->vars['role_read']);
            }
            
            if(!empty($rp->vars['attachment'])) {
                $ids = implode(',', $rp->vars['attachment']);
                $obj->setAttachment($manager->getAttachmentByIds($ids));
            }
            
            if(!empty($rp->vars['schedule_on'])) {
                foreach($rp->vars['schedule_on'] as $num => $v) {
                    $rp->vars['schedule'][$num]['date'] = DatePicker::unixDate2($rp->vars['schedule'][$num]['date']);
                    $obj->setSchedule($num, $rp->vars['schedule'][$num]);
                }
            }
            
            if(!empty($rp->vars['message'])) {
                $obj->setFirstMessage($rp->vars['message']);
            }*/
                
        // no error
        } else {
            $rp->stripVars();         
            
            if ($controller->action == 'update') {
                $data = $manager->getById($rq->id);
                $obj->set($data);
            }
            
            $obj->set($rp->vars);
            $obj->setCategory($rp->vars['category']);
            
            $obj->setSticky($rp->vars['sticky']);
            
            if(!empty($rp->vars['tag'])) {
                $obj->setTag($rp->vars['tag']);
                
                $keywords = $manager->tag_manager->getKeywordsStringByIds(implode(',', $rp->vars['tag']));
                $keywords = RequestDataUtil::addslashes($keywords);
                $obj->set('meta_keywords', $keywords);
            }
            
            if(!empty($rp->vars['sticky'])) {
                $obj->setSticky(true);
                
                if (!empty($rp->vars['sticky_to'])) {
                    $obj->setStickyDate($rp->vars['sticky_to']);
                }
            }
            
            /*if(!empty($rp->vars['attachment'])) {
                $obj->setAttachment($rp->vars['attachment']);
            }         
            
            if(!empty($rp->vars['role_read'])) {
                $obj->setRoleRead($rp->vars['role_read']);
            }
            
            if(!empty($rp->vars['schedule_on'])) {
                foreach($rp->vars['schedule_on'] as $num => $v) {
                    $rp->vars['schedule'][$num]['date'] = DatePicker::sqlDate2($rp->vars['schedule'][$num]['date']);
                    $obj->setSchedule($num, $rp->vars['schedule'][$num]);
                }
            }*/
      
      
            // save as new
            if(isset($rp->submit_new)) {
                $obj->set('id', NULL);
                $obj->set('hits', 0);
                $obj->set('date_posted', NULL);
                $obj->set('author_id', NULL);
                $controller->action = 'insert';
            }
            

            // save
            $entry_id = $manager->save($obj);
            $obj->set('id', $entry_id);
            $new_status = $manager->getStatusKey($entry_id, 1);            
            
            
            // first message
            if ($controller->action == 'insert') {
                $m = new ForumMessage;
                $manager2 = new ForumMessageModel(); 
                
                $m->set($rp->vars);
                $m->set('entry_id', $entry_id);

                $message_id = $manager2->save($m);
                
                $manager2->updateMessageFieldsForEntry($entry_id);
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
        
    
    } elseif($controller->action == 'update') {

        $data = $manager->getById($rq->id);
        $categories = $manager->getCategoryById($rq->id);
        
        $rp->stripVarsValues($data);
        $obj->set($data);
        $obj->setCategory($categories);
        //$obj->setAttachment($manager->getAttachmentById($rq->id));
        $obj->setAuthor($manager->getUser($data['author_id']));
        
        $obj->setTag($manager->tag_manager->getTagByEntryId($rq->id));
        
        $sticky_date = $manager->getStickyDate($rq->id);
        if ($sticky_date) {
            $obj->setSticky(true);
            
            if (strtotime($sticky_date)) {
                $obj->setStickyDate($sticky_date);
            }
        }
        
        foreach($manager->getScheduleByEntryId($rq->id) as $num => $v) {
            $obj->setSchedule($num, $v);
        }
    
        $ddiff = $data['tsu'] - $data['ts'];
        if($ddiff > $manager->update_diff) {
            $obj->setUpdater($manager->getUser($data['updater_id']));
            $obj->set('date_updated', $data['date_updated']);
        }
        
        $obj->setRoleRead($manager->getRoleReadById($rq->id));
    
    
    } elseif(isset($rq->filter['c']) && $rq->filter['c'] != 'all') {
        $obj->setCategory(array($rq->filter['c']));
    }
    
    $view = &$controller->getView($obj, $manager, 'ForumEntryView_form');

    break;

    
case 'message': // ------------------------------

    $message = new ForumMessage;
    $manager2 = new ForumMessageModel(); 
    
    
    $data = $manager->getMessage($rq->id);
    
    if(isset($rp->submit)) {
        $is_error = &$message->validate($rp->vars);
     
        if($is_error) {
            $rp->stripVars(true);
            $message->set($rp->vars);
                
        } else {
            $rp->stripVars();
            $message->set($rp->vars);
            
            if ($message->get('message') != $data['message']) { // the message has been changed
                $message->set('date_updated', null);    
            }
            
            $manager2->save($message);
            
            if (isset($rp->attachment)) {
                $att_delete_ids = implode(',', $rp->attachment);
                $manager2->deleteAttachmentByIds($att_delete_ids);
            }
                  
            $controller->go();      
        }
        
    } else {
        $rp->stripVarsValues($data);
        $message->set($data);
    }

    $view = &$controller->getView($message, $manager, 'ForumMessageView_form', $data);   
    
    break;


default: // ------------------------------------
	
	$view = &$controller->getView($obj, $manager, 'ForumEntryView_list');
}
?>