<?php

class KBDraftAction extends AppAction
{

    function publish($eobj, $emanager, $manager, $controller, $priv, $options = array()) {
		
        // check priv
        $priv_action = ($eobj->get('id')) ? 'update' : 'insert';
        if(!$priv->isPriv($priv_action, 'kb_entry')) {
            echo AuthPriv::errorMsg();
            exit;
        }
        
        // if simple publish, not assignee
        if(!empty($options['check_private'])) {
            $has_private = $manager->isCategoryNotInUserRole($eobj->getCategory());
            if($has_private) {
                echo AuthPriv::errorMsg();
                exit;
            }
        }
        
        
        $eobj->set('body_index', RequestDataUtil::getIndexText($eobj->get('body')));
    
        // setting default status
        $status = ListValueModel::getListDefaultEntry('article_status');
        $status = ($status !== null) ? $status : $eobj->get('active');
        $eobj->set('active', $status);
    
        $history_data = false;
        
        if ($eobj->get('id')) { // entry id
            
            // not to update hits, it could be bigger
            $eobj->unsetProperties('hits');
            
            // history
            $old_data = $emanager->getById($eobj->get('id'));
            
            // order
            $sort_values = $emanager->getSortOrderByEntryId($eobj->get('id'));
            $eobj->setSortValues($sort_values);
            
            if($allowed_rev = KBEntryHistoryModel::getHistoryAllowedRevisions()) {
                $history = new KBEntryHistoryModel;
                $new_data = RequestDataUtil::stripslashes($eobj->get());
                $history_data = $history->compare($new_data, $old_data);
                
                if($history_data && isset($this->rp->history_comment)) {
                    $eobj->set('history_comment', $this->rp->history_comment);
                }
            }
            
        } else {
            $eobj->set('id', null);
            $eobj->set('date_posted', null);
        }
        
        $entry_id = $emanager->save($eobj);
        $eobj->set('id', $entry_id);
        
        if($history_data) {
            $hdata['entry_updater_id'] = $old_data['updater_id'];
            $hdata['entry_date_updated'] = $old_data['date_updated'];
            $hdata['comment'] = RequestDataUtil::stripVars($old_data['history_comment'], array(), 'addslashes');
            $history->addRevision($entry_id, $history_data, $manager->user_id, $hdata);
            $history->removeExtraRevisions($entry_id, $allowed_rev);
        }

        // remove autosave
        $actions = array('insert');
        if(in_array($controller->action, $actions)) {
            if(!empty($this->rp->id_key)) {
                $manager->deleteAutosaveByKey($this->rp->id_key);
            }
        }
        
        if (!empty($this->rq->id)) {
            $manager->deleteDraftEntry($this->rq->id);
        }
        
        return $entry_id;
    }
    
    
    function release($controller, $manager) {
        $actions = array('update');
        if(in_array($controller->action, $actions)) {
            $manager->setEntryReleased($this->rq->id);
            $manager->deleteAutosave($this->rq->id);
        }
    }


    function noteUpdateEntry($obj, $manager, $controller, $priv, $emanager) {
        
        $controller->loadClass('KBDraftView_note_update_entry', 'knowledgebase/draft');
        
        $data = $manager->getById($this->rq->id);
        $this->rp->stripVarsValues($data);
        $obj->set($data);
        $obj->set('date_updated', $data['date_updated']);
        
        // ongoing approval
        $last_event = $manager->getLastApprovalEvent($obj->get('id'));
        if ($manager->isBeingApproved($last_event)) {
            $obj->sent_to_approval = true;
        }
        
        $draft_id = $this->rq->id;

        if(isset($this->rp->submit)) {
            $manager->checkPriv($priv, 'delete', $draft_id, false, $controller, $emanager);
            $manager->delete($draft_id);
            
            if (empty($this->rq->vnum)) {
                $more = array('entry_id' => $obj->get('entry_id'));
                $controller->goPage('this', 'this', false, 'insert', $more);
                
            } else { // rolling it back
                $more = array('id' => $obj->get('entry_id'), 'vnum' => $this->rq->vnum);
                $controller->goPage('knowledgebase', 'kb_entry', false, 'rollback', $more);
            }
        }
        
        $priv->use_exit_screen = false;
        $allowed = $manager->checkPriv($priv, 'update', $draft_id, false, $controller, $emanager);

        $view = new KBDraftView_note_update_entry;
        $view = $view->execute($obj, $manager, $allowed);
        return $view;
    }
    
    
    function noteApprovalLock($obj, $manager, $controller) {
        
        $controller->loadClass('KBDraftView_approval_lock', 'knowledgebase/draft');
        $id = $this->rq->id;
        
        if(isset($this->rp->submit)) {
            $manager->setEntryReleased($id);
            $more = array('id' => $id);
            if(!empty($this->rq->referer)) {
                $more['referer'] = $this->rq->referer;
            }

            $action = (isset($this->rq->back)) ? $this->rq->back : 'approval';
            $controller->goPage('this', 'this', false, $action, $more);
        }

        $data = $manager->getById($id);
        $this->rp->stripVarsValues($data);
        $obj->set($data);
        
        $view = new KBDraftView_approval_lock;
        $view = $view->execute($obj, $manager);
        return $view;   
    }
    
    
    function delete($obj, $manager, $controller) {

        $controller->loadClass('KBDraftView_delete', 'knowledgebase/draft');

        $delete = false;
        if (isset($this->rp->submit)) {
            $delete = true;

        } else {
            $last_event = $manager->getLastApprovalEvent($this->rq->id);
            if (empty($last_event)) { // ~ never added to workflow
                $delete = true;
            }
        }

        if ($delete) {
            $manager->delete($this->rq->id);
            $controller->go();
        }


        $data = $manager->getById($this->rq->id);
        $this->rp->stripVarsValues($data);
        $obj->set($data);

        $view = new KBDraftView_delete;
        $view = $view->execute($obj, $manager);
        return $view;
    }
    
}

?>