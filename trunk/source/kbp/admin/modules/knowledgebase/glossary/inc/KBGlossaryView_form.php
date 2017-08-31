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


class KBGlossaryView_form extends AppView
{
    
    var $tmpl = 'form.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');
        
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
                
        // body
        $tpl->tplAssign('ckeditor', $this->getEditor($obj->get('definition'), 'glossary', 'definition'));
        
        $tpl->tplAssign('ch_display_once', $this->getChecked($obj->get('display_once')));
        $tpl->tplAssign('preview_link', $this->getActionLink('preview'));
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateForm'));
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>
