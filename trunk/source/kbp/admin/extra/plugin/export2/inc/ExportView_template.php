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

require_once APP_MODULE_DIR . 'setting/setting/inc/SettingView_form.php';

class ExportView_template extends AppView
{
    
    
    function execute(&$obj, &$manager) {
        $this->addMsg('common_msg.ini', 'export_setting');
        
        $tpl = new tplTemplatez($this->template_dir . 'form_template.html');
        
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        $type = $_GET['type'];
        $tpl->tplAssign('type', $type);
        
        $title = $this->msg[$type . '_template_msg'];
        $tpl->tplAssign('title', $title);
        
        $tpl->tplAssign('ckeditor', $this->getEditor('', 'export', 'body'));
        
        if(!empty($_GET['saved']) || !empty($_GET['disabled'])) {
            $tpl->tplSetNeeded('/close_window');
            $tpl->tplSetNeeded('/set_status');
            
            $status = (!empty($_GET['saved'])) ? 'true' : 'false';            
            $tpl->tplAssign('status', $status);            
        }
        
        if (in_array($type, array('header', 'footer'))) {
            $tpl->tplSetNeeded('/margin_setting');
            
            if ($type == 'header') {
                $tpl->tplAssign('margin_type', 'top');
            }
                    
            if ($type == 'footer') {
                $tpl->tplAssign('margin_type', 'bottom');
            }
            
            
            $margin_setting = ($type == 'header') ? 'top' : 'bottom';
            $margin_setting = 'plugin_wkhtmltopdf_margin_' . $margin_setting;
            
            $margin_msg = AppMsg::getMsgs('setting_msg.ini', 'export_setting', $margin_setting);
            
            $tpl->tplAssign('margin_title', $margin_msg['title']);
            
            $tpl->tplAssign('margin_setting', $margin_setting);
        }
        
        $vars = $this->setCommonFormVars($obj);
        
        $tpl->tplAssign($vars);
        $tpl->tplParse($this->msg);
        
        return $tpl->tplPrint(1);
    }
}
?>