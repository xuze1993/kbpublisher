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


class KBDraftView_delete extends AppView
{
    
    var $template = 'form_delete.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('common_msg.ini', 'knowledgebase');
                
        $template_dir = APP_MODULE_DIR . 'knowledgebase/draft/template/';
        $tpl = new tplTemplatez($template_dir . $this->template);
        
        $tpl->tplAssign('error_msg', AppMsg::afterActionBox('draft_being_approved'));
        
        // title field
        $file = ($this->controller->module == 'file');
        $field_title = ($file) ?  $this->msg['filename_msg'] : $this->msg['title_msg'];
        $tpl->tplAssign('field_title', $field_title);
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>