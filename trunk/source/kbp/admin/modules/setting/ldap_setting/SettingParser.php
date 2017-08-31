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


class SettingParser extends SettingParserCommon
{
    
    function parseInputOptions($key, $value) {
        $ret = false;
        
        if($key == 'remote_auth' && $this->isAuthRemoteDisabled()) {
            $ret = ' disabled';
        }
        
        return $ret;
    }
    
    
    function parseSubmit($template_dir, $msg) {
        
        $tpl = new tplTemplatez($template_dir . 'form_submit_auth.html');
        $tpl->tplSetNeededGlobal('ldap');
        
        $tpl->tplAssign('type', 'ldap');
        $tpl->tplAssign('params', 'false');
        
        $tpl->tplParse($msg);
        return $tpl->tplPrint(1);
    }


    // rearange soting by groups
    function parseGroupId($group_id) {
        $sort = array(6 => '3.1', 7 => '3.2');
        return (isset($sort[$group_id])) ? $sort[$group_id] : $group_id;
    }
    
    
    // when display
    function skipSettingDisplay($key, $value = false) {
        $ret = false;
        
        if($key == 'remote_auth_map_priv_id' || $key == 'remote_auth_map_role_id') {
            $ret = true;
        }
                
        return $ret;
    }
    
    
    // parse description
    function parseDescription($key, $value) {
        if($key == 'remote_auth' && BaseModel::isCloud()) {
            $value = '';
        }
    
        return $value;
    }
}
?>