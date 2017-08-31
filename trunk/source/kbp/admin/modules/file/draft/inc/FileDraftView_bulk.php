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


class FileDraftView_bulk extends AppView
{
    
    var $tmpl = 'form_bulk.html';
    
    
    function execute(&$obj, &$manager, $view) {
        
        $this->addMsg('bulk_msg.ini', false, 'bulk_common');
        $this->addMsg('knowledgebase/common_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('bulk_actions', "'" . implode("','",($manager->bulk_manager->getActionsAllowed())) . "'");
        
        $select = new FormSelect();
        $select->select_tag = false;
        
        $msg['title'] = '';
        $msg['body'] = $this->msg['assignee_bulk_note_msg'];
        $status_range = $manager->getDraftStatusSelectRange();
        $vars['wait_status'] = $status_range[2];
        $vars['reject_status'] = $status_range[3];
        $tpl->tplAssign('assignee_note_msg', BoxMsg::factory('error', $msg, $vars));


        // actions js 
        $actions_allowed = $manager->bulk_manager->getActionsAllowed();
        $tpl->tplAssign('bulk_actions', "'" . implode("','",($actions_allowed)) . "'");
            
        // action
        $range = $manager->bulk_manager->getActionsMsg('bulk_kbentry');
        $select->setRange($range, array('none' => $this->msg['with_checked_msg']));
        $tpl->tplAssign('action_select', $select->select());
        
        $user_popup_link = $this->getLink('user', 'user');
        $tpl->tplAssign('user_popup_link', $user_popup_link);
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>