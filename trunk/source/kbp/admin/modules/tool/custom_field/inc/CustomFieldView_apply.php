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


class CustomFieldView_apply extends CustomFieldView_form
{
    
    var $tmpl = 'form_apply_value.html';
    
    
    function execute(&$obj, &$manager, &$manager2 = false) {
        
        $this->addMsg('custom_field_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        switch ($obj->get('input_id')) {                    
        case 1:
            $tpl->tplSetNeeded('/text');
            break;

        case 2:
        case 7:                    
            $tpl->tplAssign('range_block', 
                $this->getRangeBlock($obj->get('range_id'), $obj->get('default_value'), true));   
            $tpl->tplSetNeeded('/radio_behavior');
            $tpl->tplSetNeeded('/range');
            break;

        case 3:
        case 6:                    
            $tpl->tplAssign('range_block', 
                $this->getRangeBlock($obj->get('range_id'), $obj->get('default_value'), true));   
            $tpl->tplSetNeeded('/range');
            break;

        case 5:
            $tpl->tplAssign('default_value_ch', $this->getChecked($obj->get('default_value')));
            $tpl->tplSetNeeded('/checkbox');
            break;

        case 8:
            $tpl->tplSetNeeded('/textarea');
            break;

        case 9:
            $tpl->tplSetNeeded('/date');
            break;
        }

        // entry type        
        $entry_type = $manager->getEntryTypeSelectRange();
        $tpl->tplAssign('entry_type_str', $entry_type[$obj->get('type_id')]);

        $field_type = $manager->getFieldTypeSelectRange($this->msg);
        $tpl->tplAssign('field_type_str', $field_type[$obj->get('input_id')]['title']); 


        $msgk = ($this->controller->action == 'apply') ? 'apply_value_note_msg' : 'apply_value_note2_msg';
        $tpl->tplAssign('apply_value_note', $this->msg[$msgk]);

        $msgk = ($this->controller->action == 'apply') ? 'cancel_msg' : 'skip_msg';
        $tpl->tplAssign('cancel_title', $this->msg[$msgk]);
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateFormApply'));

        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($obj->get());        
        $tpl->tplAssign($this->msg);  
                
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function ajaxValidateFormApply($values, $options = array()) {
        $options['func'] = 'getValidateApply';
        $objResponse = $this->ajaxValidateForm($values, $options);
        
        return $objResponse;
    }
}
?>