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

class SettingView_form extends AppView
{
    
    var $tmpl = 'form.html';
    var $tmpl_2 = 'form2.html';
    
    
    function execute(&$obj, &$manager) {

        $form_data = $this->parseMultiIni($this->template_dir . 'form.ini');
        
        $parser = &$manager->getParser();
        $setting_msg = $parser->getSettingMsg($manager->module_name);
        $popup_link = $this->getLink('all');
        
        $tmpl = ($manager->separate_form) ? $this->tmpl_2 : $this->tmpl;
    
        $tpl = new tplTemplatez($this->template_dir . $tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors, $manager->module_name));
        $tpl->tplAssign('js_error', $this->getErrorJs($obj->errors));
        
        $r = new Replacer();

        $select = new FormSelect();
        $select->select_tag = false;
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateForm2'));
        $xajax->registerFunction(array('checkPort', $this, 'ajaxCheckPort'));
        
        $rows = &$manager->getRecords();
        //echo '<pre>', print_r($rows, 1), '</pre>';
        
        $t_id = 0;
        $i = 1;
        foreach($rows as $group_id => $group) {
            if(!empty($setting_msg['group_title'])) {
                $tpl->tplSetNeeded('row/group');
                $tpl->tplAssign('group_id', $i);
                $tpl->tplAssign('delim_id', $i);
                $tpl->tplSetNeeded('row/group_delim');
                $i++;
            }
            
            $key_last = end($group);
            $key_last = $key_last['setting_key'];

            foreach($group as $setting_key => $v) {

                if ($setting_key == $key_last) {
                    $tpl->tplAssign('end', '</div>');
                    $tpl->tplSetNeeded('row/submit');
                } else {
                    $tpl->tplAssign('end', '');  
                }

                $setting_key = trim($setting_key);
                $v['group_title_msg'] = $setting_msg['group_title'][$v['group_id']];
                $v['required_sign'] = ($v['required']) ? '<span class="requiredSign">*</span>' : '';
                $v['id'] = $setting_key;
                if($ioptions = $parser->parseInputOptions($setting_key, @$v['value'])) {
                    $v['options'] .= ' ' . $ioptions;
                }
                
                if($v['input'] == 'checkbox') {
                    $v['checked'] = @($obj->get($setting_key)) ? 'checked' : '';
                    $v['value'] = @($obj->get($setting_key)) ? 1 : 0;
                
                } elseif ($v['input'] == 'select') {
                    
                    $lang_range = $setting_msg[$v['setting_key']];
                    unset($lang_range['title'], $lang_range['descr']);
                    
                    // we have options in lang file
                    if(isset($lang_range['option_1'])) {
                        
                        if($v['range'] == 'dinamic') {
                            $v['range'] = $parser->parseSelectOptions($v['setting_key'], $lang_range);
                        } else {
                           
                            $options = $parser->parseSelectOptions($v['setting_key'], $lang_range);
                            foreach($v['range'] as $k1 => $v1) {
                                $value = (current($options)) ? trim(current($options)) : $v1;
                                $v['range'][trim($k1)] = $value;
                                next($options);
                            }    
                        }
            
                    // full dinamic generate
                    } else {
                        $v['range'] = $parser->parseSelectOptions($v['setting_key'], $v['range']);
                    }

                    
                    //if multiple
                    if(strpos($v['options'], 'multiple') !== false) {
                        $v['array_sign'] = '[]';
                    }
                    
                    $select->setRange($v['range']);
                    $v['value'] = $select->select($obj->get($setting_key));
                    unset($v['range']);
                
                } else {
                    
                    $v['value'] = @$obj->get($setting_key);
                }             
                
                
                // here we can change some values
                $v['value'] = $parser->parseOut($setting_key, $v['value']);
                if($v['input'] == 'checkbox') {
                    $v['checked'] = ($v['value']) ? 'checked' : '';
                }
                
                $v['popup_link'] = $this->getLink('all');
                
                
                if($v['input'] == 'hidden_btn') {
                    
                    // ldap/saml group mapping
                    $group_mapping_keys = array(
                        'remote_auth_map_group_to_priv' => array(
                            'custom_setting' => 'remote_auth_map_priv_id'
                        ),
                        'remote_auth_map_group_to_role' => array(
                            'custom_setting' => 'remote_auth_map_role_id'
                        ),
                        'saml_map_group_to_priv' => array(),
                        'saml_map_group_to_role' => array()
                    );
                                                     
                    if (isset($group_mapping_keys[$setting_key])) {
                        $rules_count = 0;
                        if ($v['value']) {
                            $rules_count += count(explode("\n", trim($v['value'])));
                        }
                        
                        $custom_setting_key = @$group_mapping_keys[$setting_key]['custom_setting'];
                        if ($custom_setting_key) { // ldap
                            $custom_setting = $manager->getSettings('160', $custom_setting_key);
                            if ($custom_setting) {
                                $rules_count += count(explode("\n", trim($custom_setting)));
                            }
                        }
                        
                        $v['text'] = sprintf('%s: <span id="%s_count">%s</span>', $this->msg['rules_added_msg'], $v['id'], $rules_count);                        
                    }
                    
                    // saml certificates
                    $cert_keys = array(
                        'saml_idp_certificate',
                        'saml_sp_certificate'
                    );
                    
                    if (in_array($setting_key, $cert_keys)) {
                        if ($v['value']) {
                            $cert = openssl_x509_parse($v['value']);
                            if ($cert) {
                                $v['text'] = 'CN=' . $cert['subject']['CN'];
                            }
                            
                        } else {
                            //$v['text'] = '--';
                        }
                    }
                    
                    
                    // extra items
                    $extra_items_keys = array(
                        'menu_extra', 'nav_extra'
                    );
                    
                    if (in_array($setting_key, $extra_items_keys)) {
                        $rules_count = 0;
                        if ($v['value']) {
                            $rules_count += count(explode('||', trim($v['value'])));
                        }
                        
                        $v['text'] = sprintf('%s: <span id="%s_count">%s</span>', $this->msg['items_added_msg'], $v['id'], $rules_count);
                    }
                }
                
                if($v['input'] == 'checkbox_btn') {
                    $v['checked'] = ($v['value']) ? 'checked' : '';
                    $v['url'] = $this->controller->getCurrentLink();
                }
                
                if($v['input'] == 'info') {
                    $v['url'] = $this->controller->getCurrentLink();
                }
                
                if($parser->parseForm($setting_key, 'check')) {
                    $field = $parser->parseForm($setting_key, 
                                               $v, 
                                               $r->parse($form_data[$v['input']], $v), 
                                               $setting_msg);
                } else {                
                    $field = $r->parse($form_data[$v['input']], $v);
                }
                
                $msg_key = $parser->parseMsgKey($v['setting_key']);
                
                $title = $parser->parseTitle($msg_key, $setting_msg[$msg_key]['title']);
                $tpl->tplAssign('title_msg', $title);

                $desc = $parser->parseDescription($msg_key, $setting_msg[$msg_key]['descr']);
                $tpl->tplAssign('description_msg', $desc);

                $tpl->tplAssign('form_input', $field);
                $tpl->tplAssign('id', $setting_key);
                $t_id ++;

                $tpl->tplParse(array_merge($v, $this->msg), 'row'); 
            }
        }
        
        if (isset($_POST['q'])) {
            $tpl->tplAssign('filter', $_POST['q']);
        }                                        
        
        if (!empty($obj->errors)) {
            foreach ($obj->errors as $error) {
                $err_key = (is_array($error[0]['field'])) ? implode("','", $error[0]['field']) : $error[0]['field'];
                $tpl->tplAssign('show_errors', "showErrorBlock('$err_key')");
            }
        }        
        
        // debug page
        $debug_link = $this->controller->getCurrentLink();
        $debug_link = $this->controller->_replaceArgSeparator($debug_link);
        $tpl->tplAssign('debug_link', $debug_link);    
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign('custom_text', $parser->getCustomFormHeader($obj));
        
        // fselect
        $tpl->tplAssign('overflow_text', $this->msg['selected_msg'] . ': {{n}}');
        
        if ($this->priv->isPriv('update') || $this->controller->module == 'account') {
            $tpl->tplAssign('submit_buittons', $parser->parseSubmit($this->template_dir, $this->msg));
        }
        
        $tpl->tplParse();
        
        return $tpl->tplPrint(1);
    }
    
    
    // parse multilines ini file
    // it will skip all before defining first [block] 
    function parseMultiIni($file, $key = false) {
        $s_delim = '[%';
        $e_delim = '%]'; 
        
        $str = implode('',file($file));
        if($key && strpos($str, $s_delim . $key . $e_delim) === false) { return; } 
        
        $str = explode($s_delim, $str);
        $num = count($str);
            
        for($i=1;$i<$num;$i++){
            $section = substr($str[$i], 0, strpos($str[$i], $e_delim));
            $arr[$section] = substr($str[$i], strpos($str[$i], $e_delim)+strlen($e_delim));
        }
        
        return ($key) ? @$arr[$key] : $arr;
    }
    
	
	function ajaxValidateForm2($values, $options) {
		$values = $this->obj->prepareValues($values['values'], $this->manager);
		return parent::ajaxValidateForm($values, $options);
	}
    
    
    function ajaxCheckPort($host, $port, $button_name) {
		
        $objResponse = new xajaxResponse();
        
        $old_port = SettingModel::getQuick(141, 'sphinx_port');
            
        if ($old_port != $port) { // changed
            $ret = true;
			$port  = (int) $port;
            
            $fp = @fsockopen($host, $port, $errno, $errstr, 0.1);
            
            if ($fp) {
                fclose($fp);
                
                $msg = AppMsg::getMsgs('setting_msg.ini', 'sphinx_setting');
                $objResponse->script(sprintf("confirmForm('%s', 'submit');", $msg['sphinx_other']['port_in_use']));
            
                return $objResponse;
            }
        }
        
        $script = sprintf('$("input[name=%s]").attr("onClick", "").click();', $button_name);
        $objResponse->script($script);
        
        return $objResponse;
    }
	
}
?>