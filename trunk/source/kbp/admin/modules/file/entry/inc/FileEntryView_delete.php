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


class FileEntryView_delete extends AppView
{
    
    var $template = 'form_delete.html';
    
    function execute(&$obj, &$manager) {
        
        $template_dir = APP_MODULE_DIR . 'file/entry/template/';
        $tpl = new tplTemplatez($template_dir . $this->template);
        
        $msg_key = ($obj->is_missing) ? 'inaccessible_file' : 'delete_file_from_disk';
        $tpl->tplAssign('error_msg', AppMsg::afterActionBox($msg_key));
        
        $filename = ($obj->is_missing) ? $this->setting['file_dir'] . $obj->get('filename') : $manager->getFileDir($obj->get());
        $tpl->tplAssign('filename', $filename);
        
        
        if ($obj->is_missing) {
            $delete_button_value = $this->msg['delete_from_db_msg'];
            
            if ($this->priv->isPriv('delete')) {
                $tpl->tplSetNeeded('/from_db_button');
            }
            
            if ($this->priv->isPriv('update')) {
                $tpl->tplSetNeeded('/update_button');
                $link = $this->getActionLink('update', $obj->get('id'));
                $tpl->tplAssign('update_link', $link);
            }
            
        } else {
            $tpl->tplSetNeeded('/from_db_button');
            $tpl->tplSetNeeded('/from_disk_button');
            $delete_button_value = $this->msg['no_from_disk_msg'];
        }
        
        $tpl->tplAssign('delete_button_value', $delete_button_value);
        
        $vars = $this->setCommonFormVars($obj);
        $vars['action_link'] = str_replace(array('action=file', 'action=preview'), 'action=delete', $vars['action_link']);
        
        $tpl->tplAssign($vars);
        //$tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        //$tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>