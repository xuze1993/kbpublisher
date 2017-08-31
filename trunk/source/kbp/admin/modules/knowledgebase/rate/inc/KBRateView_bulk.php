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


class KBRateView_bulk extends AppView
{
    
    var $tmpl = 'form_bulk.html';
    
    
    function execute(&$obj, &$manager, $view) {
    
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        $select = new FormSelect();
        $select->select_tag = false;
        
        
        // status
        $range = ListValueModel::getListSelectRange('rate_status', true, false);
        $select->setRange($range);
        $tpl->tplAssign('status_select', $select->select());   
        
        
        // actions js 
        $actions_allowed = $manager->bulk_manager->getActionsAllowed();
        $tpl->tplAssign('bulk_actions', "'" . implode("','",($actions_allowed)) . "'");
        
        // action
        @$val = $values['action'];
        $range = $manager->bulk_manager->getActionsMsg('bulk_kbentry');
        $select->setRange($range, array('none' => $this->msg['with_checked_msg']));
        $tpl->tplAssign('action_select', $select->select($val));
        
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>