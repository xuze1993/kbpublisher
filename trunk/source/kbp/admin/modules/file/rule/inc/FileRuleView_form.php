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


class FileRuleView_form extends AppView
{
    
    var $template = 'form.html';
    
    
    function execute(&$obj, &$manager, $obj_view) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsgPrepend('common_msg.ini', 'knowledgebase');
        
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors, $this->controller->module));
        
        
        // read link
        $link = $this->getLink('file', 'file_rule', false, 'dir');
        $tpl->tplAssign('read_link', html_entity_decode($link));
        
        $tpl->tplAssign('parse_child_checked', $this->getChecked($obj->get('parse_child')));
        $tpl->tplAssign('is_draft_checked', $this->getChecked($obj->get('is_draft')));
        
        // file object
        $tpl->tplAssign('block_obj_tmpl', $obj_view);
        
        
        $debug_link = $this->controller->getCurrentLink();
        $debug_link = $this->controller->_replaceArgSeparator($debug_link);
        $tpl->tplAssign('debug_link', $debug_link);
        
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateFormRule'));
        
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function ajaxValidateFormRule($values, $options = array()) {
        $objResponse = $this->ajaxValidateForm($values, $options);
        
        if (!$this->obj->errors) {
            $this->obj = new FileEntry;
            $this->controller->action = 'rule';
            $this->manager = new FileEntryModel_dir;
            
            $objResponse = $this->ajaxValidateForm($values, $options);            
        }
        
        return $objResponse;
    }
        
}
?>