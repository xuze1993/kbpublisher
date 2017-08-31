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

require_once 'core/app/BulkModel.php';


class KBDraftModelBulk extends BulkModel
{

    var $actions = array('assignee', 'delete');
	
    
    function setActionsAllowed($manager, $priv, $allowed = array()) {
    
        $actions = $this->getActionAllowedCommon($manager, $priv, $allowed);
        
        if (!AuthPriv::isAdmin()) {
            unset($actions['assignee']);
        }
        
        $this->actions_allowed = array_keys($actions);
        return $this->actions_allowed;        
    }
    
    
    function setAssignee($ids, $assignee, $controller) {
        $ids = implode(',', $ids);
        $this->model->setSqlParams("AND d.id IN ({$ids})");
        $rows = $this->model->getRecords();
        
        $assignee_str = implode(',', $assignee);
        
        foreach ($rows as $row) {
            if ($this->model->isBeingApprovedByRow($row)) {
                $this->model->deleteAssignees($row['id']);
                $this->model->saveAssignees($row['id'], $row['last_event_id'], $assignee);
                
                $obj = new KBDraft;
                $obj->set($row);
                $sent = $this->model->sendDraftReview($obj, $controller, $row['comment'], $assignee_str);
                
                $assignee_mva = sprintf('(%s)', $assignee_str);
                AppSphinxModel::updateAttributes('assignee', $assignee_mva, $ids, $this->model->entry_type);
            }
        }
    }
    
    
    function delete($ids) {
        $ids_remove = array();
        
        // remove not allowed, which in approve 
        if($ids) {
            $ids_str = $this->model->idToString($ids);
            $this->model->setSqlParams("AND d.id IN ($ids_str)");
            $rows = $this->model->getRecords();
            
            foreach($rows as $row) {
                
                // being approved 
                if($this->model->isBeingApprovedByRow($row)) {
                    $last_event = $this->model->getLastApprovalEvent($row['id']);
                    $assignees = $this->model->getAssigneeByStepIds($last_event['id']);
                    $assignees = (!empty($assignees[$row['id']])) ? $assignees[$row['id']] : array();
                    
                    $ret = $this->model->isUserAllowedToApprove($assignees);
                    
                    if(!$ret) {
                        $ids_remove[] = $row['id'];
                    }
                }
            }
            
            // remove not allowed
            $ids = array_diff($ids, $ids_remove);
        }
        
        if($ids) {
            $this->model->delete($ids);
        }
        
        return $ids_remove;
    }
        
}
?>