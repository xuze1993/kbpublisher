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

class CustomFieldRangeGroupView_form extends AppView
{
    
    var $tmpl = 'form_group.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('custom_field_msg.ini');
        
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        if($this->controller->action == 'update') {
            $link = $this->getLink('this', 'this', 'this', false, array('range_id' => $obj->get('id')));
            $tpl->tplAssign('value_link', $link);
            $tpl->tplSetNeeded('/range_values_btn');            
        }
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateForm'));        
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        //$tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>