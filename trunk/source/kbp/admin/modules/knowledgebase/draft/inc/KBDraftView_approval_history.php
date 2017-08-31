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


class KBDraftView_approval_history extends AppView 
{
    
    var $template = 'list_approval.html';
    

    function execute(&$obj, &$manager, $data = false) {
        
        $menu_block = false;
        if ($data) {
            list($menu_block, $emanager) = $data;
        }
        
        $this->addMsg('user_msg.ini');
        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');
        
        $approval_history = $manager->getApprovalLog($obj->get('id'));
        if (empty($approval_history)) {
            return '';
        }
        
        $user_ids = $manager->getValuesString($approval_history, 'user_id', true);
        $users = $manager->getUser($user_ids, false);
        
        $template_dir = APP_MODULE_DIR . 'knowledgebase/draft/template/';
        $tpl = new tplTemplatez($template_dir . $this->template);
        
        if ($menu_block) {
            $prefix = ($this->controller->module == 'knowledgebase') ? 'KB' : 'File';
            $class = $prefix . 'DraftView_common';
            $tpl->tplAssign('menu_block', $class::getEntryMenu($obj, $manager, $this, $emanager));
        } else {
            $tpl->tplAssign('title', $this->msg['workflow_log_msg']);
            $tpl->tplSetNeeded('/title');
        }
        
        foreach ($approval_history as $event) {
            
            $event = array_merge($event, $this->getEventVars($event['active'], $event['step_num'], $this->msg));
            $event['formatted_date'] = $this->getFormatedDate($event['date_posted'], 'datetime');
            if(isset($users[$event['user_id']])) {
                $event += $users[$event['user_id']];
            }
            
            if ($event['step_title']) {
                $tpl->tplSetNeeded('row/step_title');
            }

            if ($event['comment']) {
                $tpl->tplSetNeeded('row/comment');
            }
            
            $event['comment'] = nl2br($event['comment']);
            
            $tpl->tplParse($event, 'row');
        }
        
        $tpl->tplAssign($this->msg);
  
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getEventVars($active, $step_num, $msg) {
        
        $event = array();
        $event['by_user_msg'] = $msg['by_user_msg'];
               
                
        switch ($active) {
            case 1:
            	$event['name'] = $msg['approved_msg'];
                $event['color'] = 'green';
            
                if ($step_num == 1) {
                    $event['name'] = $msg['submitted_msg'];
                    $event['color'] = 'black';
                }
                
            	break;
            
            case 2:
            	$event['name'] = $msg['rejected_msg'];
                $event['color'] = 'red';
                break;
                
            case 3:
            	$event['name'] = $msg['published_msg'];
                $event['color'] = 'green';
            	break;
        }
        
        return $event;
    }
}
?>