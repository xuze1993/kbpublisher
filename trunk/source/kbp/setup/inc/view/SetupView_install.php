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


class SetupView_install extends SetupView
{

    function &execute($manager) {
        
        $data = $this->getContent($manager);
        return $data;
    }
    
    
    function getContent($manager) {
        
        $this->msg['next_msg'] = ($manager->isUpgrade()) ? $this->msg['upgrade_msg'] 
                                                         : $this->msg['install_msg'];
        
        $tpl = new tplTemplatez($this->template_dir . 'install.html');
        $tpl->tplAssign('user_msg', $this->getErrors());
        
        if($this->getErrors()) {
            $r['button_msg'] = ($manager->isUpgrade()) ? $this->msg['upgrade_msg'] : $this->msg['install_msg'];
            $r['file'] = '[kb_installation_directory]/setup/' . $manager->getSetupData('sql_file');
            $msg['title'] = false;        
            $msg['body'] = $this->getPhraseMsg('db_error_description', $r);
            $tpl->tplAssign('user_do_msg', BoxMsg::factory('error', $msg));
        }
        
        if(!$this->getErrors() && $manager->isUpgrade()) {
            $msg['title'] = $this->msg['attention_msg'];
            $msg['body'] = $this->getPhraseMsg('db_backup_attention');
            $tpl->tplAssign('user_do_msg', BoxMsg::factory('error', $msg));        
        }
        
        
        // setup data
        //echo "<pre>"; print_r($manager->getSetupData()); echo "</pre>";
        $skip = array(
            'setup', 'setup_type', 'password', 'password_2', 
            'setup_upgrade', 'sql_file',
            'old_config_file', 'old_config_file_skip', 'config_file_check');
        $space = array('first_name', 'db_driver', 'client_home_dir');
        $install_info = array();
        foreach($manager->getSetupData() as $k => $v) {
            if(in_array($k, $skip)) {
                continue;
            }
            
            if(in_array($k, $space)) {
                $install_info[] = '---------------------';
            }            
            
            if($k == 'ssl_admin' || $k == 'ssl_client') {
                $what = ($k == 'ssl_admin') ? $this->msg['backend_msg'] : $this->msg['frontend_msg'];
                $v = SetConfiguration::parseSetting($v);
                $install_info[] = sprintf('%s %s: %s', $this->msg['secure_http_msg'], $what, $v);
                continue;
            }
            
            $install_info[] = sprintf('%s: %s', $this->msg[$k . '_msg'], $v);
        }
        
        
        $tpl->tplAssign('install_info', implode("\n", $install_info));
        
        $msg = ($manager->isUpgrade()) ? $this->getPhraseMsg('upgrade') : $this->getPhraseMsg('install');
        $tpl->tplAssign('phrase_msg', $msg);
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }        
}
?>