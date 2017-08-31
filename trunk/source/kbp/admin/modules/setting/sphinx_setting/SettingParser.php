<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KBPublisher package                              |
// | KPublisher - web based knowledgebase publishing tool                      |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2010 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+


class SettingParser extends SettingParserCommon
{
    
    function getCustomFormHeader($obj) {
		
        $header = '';
        $this->error = false;
        
        if ($obj->get('sphinx_data_path')) {
            $config_path = $obj->get('sphinx_data_path') . 'sphinx.conf';
            $original_config_path = $obj->get('sphinx_data_path') . 'sphinx_original.conf';
            if (file_exists($config_path) && file_exists($original_config_path)) {
                if (md5_file($config_path) != md5_file($original_config_path)) {
                    $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
                    $msgs = AppMsg::parseMsgsMultiIni($file);
                    $msg['title'] = '';
                    $msg['body'] = $msgs['sphinx_config_changes'];
                    $header .= BoxMsg::factory('hint', $msg);
                }
            }
        }
        
        $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
        $msgs = AppMsg::parseMsgsMultiIni($file);
        
        $msg['title'] = '';
        
        if ($obj->get('sphinx_enabled') == 2) {
            $tasks = array('restart', 'files', 'index');
            
            $key = 'sphinx_restart_task';
            $class = 'hint';
            $vars = array();
                
            foreach ($tasks as $v) {
                $task = $this->manager->getSphinxTask($v);
                
                if (!empty($task['failed_message'])) {
                    $this->error = true;
                
                    $key = 'sphinx_restart_failed';
                    $class = 'error';
                    
                    $message = $task['failed_message'];
                    $lines_num = substr_count($message, "\n");
                    if ($lines_num > 10) { // too big
                        $lines = explode("\n", $message, 10);
                        array_pop($lines);
                        
                        $lines[] = '';
                        $link = 'index.php?module=log&page=sphinx_log&filter[s]=0';
                        $lines[] = sprintf('... <a href="%s" style="float: right;">Details</a>', $link);
                        
                        $message = implode('<br />', $lines);
                    }
                    
                    $vars = array('failed_message' => $message);
                    break;
                }
            }
            
            $msg['body'] = $msgs[$key];
            $header .= BoxMsg::factory($class, $msg, $vars);
            
        } else {
            $stop_task = $this->manager->getSphinxTask('stop', true);
            if ($stop_task) {
                $msg['body'] = $msgs['sphinx_stop_task'];
                $header .= BoxMsg::factory('hint', $msg);
            }
        }
        
        return $header;
    }
    
    
    function parseIn($key, $value, &$values = array()) {
        
        $dirs = array('sphinx_bin_path', 'sphinx_data_path');
        
        if(in_array($key, $dirs) && !empty($value)) {
            $value = $this->parseDirectoryValue($value);
        }
        
        if ($key == 'sphinx_host' && $value == 'localhost') {
            $value = '127.0.0.1';
        }
        
        return $value;
    }
    
    
    // options parse
    function parseSelectOptions($key, $values, $range = array()) {
        
        if($key == 'sphinx_lang') {
            
            require_once APP_MSG_DIR . 'CompareLang.php';
            $values = CompareLang::getLangSelectRange(APP_MSG_DIR);
        }
        
        return $values;
    }


    function skipSettingDisplay($key, $value = false) {
        $ret = false;

        if($key == 'sphinx_prefix' || $key == 'sphinx_main_config') {
            $ret = true;
        }

        return $ret;
    }
    
	
    function parseSubmit($template_dir, $msg) {
        
        $tpl = new tplTemplatez($template_dir . 'form_submit_sphinx.html');
        
        $restart_task = $this->manager->getSphinxTask('restart', true);
        $stop_task = $this->manager->getSphinxTask('stop', true);
        
        $tpl->tplAssign('class', 'button');
        
        if (!$this->error && (!empty($restart_task) || !empty($stop_task))) {
            $tpl->tplAssign('class', 'button buttonDisabled');
            $tpl->tplAssign('disabled', 'disabled');
        }
        
        
        $msg2 = AppMsg::getMsgs('setting_msg.ini', 'sphinx_setting');
        $tpl->tplAssign('restart', $msg2['sphinx_other']['restart']);
        $tpl->tplAssign('restart_desc', $msg2['sphinx_other']['restart_desc']);
        
		$tpl->tplAssign('port_validate_func', 'false');
		if(!BaseModel::isCloud()) {
			$tpl->tplSetNeeded('/sphinx_restart');
			$tpl->tplAssign('port_validate_func', "'portValidateCallback'");
		} 
		
        /*if(!$this->manager->getSettings(141, 'sphinx_enabled')) {
            $tpl->tplAssign('class2', 'buttonDisabled');
            $tpl->tplAssign('disabled2', 'disabled');
        }*/
        
        $tpl->tplParse($msg);
        return $tpl->tplPrint(1);
    }
}
    
?>