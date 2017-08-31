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
        
        if($key == 'saml_auth' && $this->isAuthRemoteDisabled()) {
            $ret = ' disabled';
        }
        
        return $ret;
    }
	
    // rearange soting by groups
    function parseGroupId($group_id) {
        $sort = array(5=>'2.1', 3=>'4.1');
        return (isset($sort[$group_id])) ? $sort[$group_id] : $group_id;
    }
    
    
    function parseSubmit($template_dir, $msg) {
        
        $tpl = new tplTemplatez($template_dir . 'form_submit_auth.html');
        
        $tpl->tplAssign('type', 'saml');
        $tpl->tplAssign('params', 'true, true');
        
        $tpl->tplParse($msg);
        return $tpl->tplPrint(1);
    }
    
}
?>