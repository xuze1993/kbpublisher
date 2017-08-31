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


class TagView_bulk extends AppView
{
    
    var $tmpl = 'form_bulk.html';
    
    
    function execute(&$obj, &$manager, $view) {
        
        $this->addMsg('bulk_msg.ini', false, 'bulk_common');
        
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('bulk_actions', "'" . implode("','",($manager->bulk_manager->getActionsAllowed())) . "'");
        
        $select = new FormSelect();
        $select->select_tag = false;
        
        // status
        $status_range = array();
        if($manager->bulk_manager->isActionAllowed('status')) {
            $range = array(
                1 => $this->msg['status_visible_msg'],
                0 => $this->msg['status_hidden_msg']
            );
            $status_range = &$range;
            $select->setRange($range);
            $tpl->tplAssign('status_select', $select->select());
        }        


        // actions js 
        $actions_allowed = $manager->bulk_manager->getActionsAllowed();
        $tpl->tplAssign('bulk_actions', "'" . implode("','",($actions_allowed)) . "'");
            
        // action
        $range = $manager->bulk_manager->getActionsMsg('bulk_kbentry');
        $select->setRange($range, array('none' => $this->msg['with_checked_msg']));
        $tpl->tplAssign('action_select', $select->select());
        
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>