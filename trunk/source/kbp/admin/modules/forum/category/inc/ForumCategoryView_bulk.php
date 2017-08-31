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
   

class ForumCategoryView_bulk extends AppView
{
    
    var $tmpl = 'form_bulk.html';
    
    
    function execute(&$obj, &$manager, $view) {
        
        $this->addMsgPrepend('user_msg.ini');
            
    
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('bulk_actions', "'" . implode("','",($manager->bulk_manager->getActionsAllowed())) . "'");
        
        $select = new FormSelect();
        $select->select_tag = false;
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
            
        // action
        @$val = $values['action'];
        $select->setRange($manager->bulk_manager->getActionsMsg('bulk_kbcategory'), array('none' => $this->msg['with_checked_msg']));
        $tpl->tplAssign('action_select', $select->select($val));
        
        // private
        if($manager->bulk_manager->isActionAllowed('private')) {
            $tpl->tplSetNeeded('/private');
            $tpl->tplAssign('block_private_tmpl', 
                PrivateEntry::getPrivateBulkBlock($obj, $manager, 'knowledgebase', 'kb_category'));
            $xajax->registerFunction(array('loadRoles', $this, 'ajaxLoadRoles'));
        }
        
        // status
        $extra_range = array();
        $msg = AppMsg::getMsg('setting_msg.ini', false, 'list_category_status');
        $range = array(1 => $msg['published'], 0 => $msg['not_published']);
        $select->setRange($range, $extra_range);
        $tpl->tplAssign('status_select', $select->select());
        
        $user_popup_link = $this->getLink('user', 'user');
        $tpl->tplAssign('user_popup_link', $user_popup_link);
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function ajaxLoadRoles($ids) {
        return PrivateEntry::ajaxLoadRoles($ids, $this->manager);
    }
}
?>