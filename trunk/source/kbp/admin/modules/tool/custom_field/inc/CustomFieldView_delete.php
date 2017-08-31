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


class CustomFieldView_delete extends AppView
{
    
    var $template = 'form_delete.html';
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('custom_field_msg.ini'); 
        
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        $msg = array('delete_field_msg' => $this->msg['delete_field_msg']);
        $tpl->tplAssign('error_msg', AppMsg::afterActionBox('delete_custom_field', 'error', false, $msg));
        
        $entry_type = $obj->get('type_id');
        
        $more = array('filter[q]'=>'custom_id:' . $obj->get('id'));
        $module = $manager->entry_type_to_url[$entry_type][0];
        $page = $manager->entry_type_to_url[$entry_type][1];
        
        $entry_link = $this->getLink($module, $page, false, false, $more);
        $tpl->tplAssign('entry_link', $entry_link);    
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        //$tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        //$tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>