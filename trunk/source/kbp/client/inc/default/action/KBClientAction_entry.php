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

require_once APP_MODULE_DIR . 'user/user/inc/UserActivityLog.php';
require_once 'core/base/BaseObj.php';
require_once 'core/app/AppObj.php';
require_once 'core/common/PrivateEntry.php';
require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntry.php';
require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryHistoryModel.php';
            

class KBClientAction_entry extends KBClientAction_common
{

    function &execute($controller, $manager) {
        
		if(empty($_GET['em'])) {
	        if($manager->isUserViewed($this->entry_id) === false) {
	            $manager->addView($this->entry_id);
	            $manager->setUserViewed($this->entry_id);
	        }
            
			// do not count after update, cnacel update, etc. not implemented
			// if(empty($_GET['ae'])) {
	        	UserActivityLog::add('article', 'view', $this->entry_id);
			// }
		}
		
                
        $view = &$controller->getView('entry');
        
        // to keep correct sort order on the left menu
        if($manager->getCategoryType($this->category_id) == 'book') {
            $view->controller->setting['entry_sort_order'] = 'sort_order';
        }
        
        
        $emode = false;
        if((!empty($_COOKIE['kb_emode_']) || !empty($_GET['em'])) && !$view->mobile_view) {
            
            $entry = $manager->getEntryById($this->entry_id, $this->category_id);
            if($manager->isEntryUpdatableByUser($this->entry_id, $this->category_id, 
                                                    $entry['private'], $entry['category_private'], $entry['active'])) {
                
                $emode = true;
            }
        }       
        
        
        // emode, quick update 
        if($emode) {
        
            $this->rp->setHtmlValues('body');
            $this->rp->setCurlyBracesValues('body');
            $this->rp->setSkipKeys(array('schedule', 'schedule_on'));
        
            $setting = SettingModel::getQuick(1);
            
            $controller->loadClass('entry');
            $view = &$controller->getView('entry_emode');
            $view->entry =& $entry; 
                
            $emanager = new KBEntryModel;
            $view->emanager = $emanager;
            
            $obj = new KBEntry;
        
            if(isset($this->rp->category)) {
            
                $errors = $obj->validate($this->rp->vars, $emanager);
            
                if($errors) {
                    $this->rp->stripVars(true);
                    $view->setErrors($errors);
                
                } else {
                    $this->rp->stripVars();
                
                    $obj->set($obj->get());
                    $obj->populate($this->rp->vars, $emanager);
                    $obj->set('body_index', RequestDataUtil::getIndexText($this->rp->vars['body']));
            
                    $history_data = false;            
                    if($allowed_rev = KBEntryHistoryModel::getHistoryAllowedRevisions($setting['entry_history_max'])) {
                        $history = new KBEntryHistoryModel();
                        $new_data = RequestDataUtil::stripslashes($obj->get());
                        $old_data = $emanager->getById($this->entry_id);
                        $history_data = $history->compare($new_data, $old_data);

                        // add history comment if any
                        if($history_data && isset($this->rp->history_comment)) {
                            $obj->set('history_comment', $this->rp->history_comment);
                        }
                    }
            
                    $emanager->save($obj);
            
                    if($history_data) {
                        $hdata['entry_updater_id'] = $old_data['updater_id'];
                        $hdata['entry_date_updated'] = $old_data['date_updated'];
                        $hdata['comment'] = RequestDataUtil::stripVars($old_data['history_comment'], array(), 'addslashes');
                        $history->addRevision($this->entry_id, $history_data, $manager->user_id, $hdata);
                        $history->removeExtraRevisions($this->entry_id, $allowed_rev);
                    }
                
                    $emanager->setEntryReleased($this->entry_id);
                    $emanager->deleteAutosave($this->entry_id);
                
                    UserActivityLog::add('article', 'update', $this->entry_id);
            
                    if(!in_array($obj->get('active'), explode(',', $manager->entry_published_status))) { // not visible anymore
                        $controller->go('index', $this->category_id, false, 'entry_updated', 0, 1);
                    }
                    
                    $controller->go('success_go', $this->category_id, $this->entry_id, 'entry_updated');
                }
                
            
            } elseif (!isset($this->rq->ajax)) {
                
                // locked
                if($emanager->isEntryLocked($this->entry_id)) {
                    $view->entry_locked = true;
                    
                } else {
                    $emanager->setEntryLocked($this->entry_id);
                }

                // autosaved
                if ($setting['entry_autosave']) {

                    $is_autosaved = $emanager->isAutosaved($this->entry_id, $entry['date_updated']);
                    if ($is_autosaved) {
                        $view->entry_autosaved = true;     
                    }
                }
                
                // draft
                if($draft_id = $emanager->isEntryDrafted($this->entry_id)) {
                    $more = array('id'=>$draft_id, 'referer'=>'emode');
                    $link = $controller->getAdminRefLink('knowledgebase', 'kb_draft', false, 'entry_update', $more, false);
                    $controller->goUrl($link);
                }
            }
            
            $view->eobj = $obj;
            
            $data = $emanager->getById($this->entry_id);
            if (!empty($data)) {
                $this->rp->stripVarsValues($data);
                $obj->collect($this->entry_id, $data, $emanager, 'update');
                $obj->set('date_updated', $data['date_updated']);
            }
            
        }
        
        return $view;
    }
    
}
?>