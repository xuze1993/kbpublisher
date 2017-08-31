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

require_once 'core/app/AppAjax.php';
require_once 'eleontev/HTML/DatePicker.php';


class TriggerParser
{
    
    var $time_format = '%H:%M';
    
    var $error = false;
    var $_error = false;
    
    
    static function pack($data) {
        
        foreach(array_keys($data) as $k) {
            $v = $data[$k];
            
            // datetime to unixtimestamp
            if($v['item'] == 'datetime') {
                $data[$k]['rule'][1] = DatePicker::unixDate(1, $v['rule']);
            }
        }
        
        return serialize($data);
    }
    
    
    static function unpack($data) {
        return unserialize($data);
    }    
    
    
    function setMsg() {
        $this->msg = AppMsg::getMsg('trigger_msg.ini');
    }
    
    
    function setManager(&$model) {
        $this->model = &$model;
    }
    
    
    function setItem($item, $rule) {
        $this->items[$this->counter]['item'] = $item;
        $this->items[$this->counter]['rule'] = $rule;
        $this->counter++;
    }
    
    
    function getItem($key) {
        return $this->items[$key];
    }


    function setItems($arr) {
        foreach($arr as $v) {
            $rule = (isset($v['rule'])) ? $v['rule'] : '';
             $this->setItem($v['item'], $rule);
        }
    }
    
    
    function setDefaultItem() {
        $this->setItem($this->default_item, $this->default_rule);
    }
    
    
    function getItems() {
        return $this->items;
    }
    
    
    function getSelect($range, $selected = false) {
        $select = new FormSelect();
        $select->select_tag = false;
        $select->setRange($range);
        return $select->select($selected);
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
    
    
    function _getItemSelect($items, $msg, $selected = false) {
        
        $range = array();
        foreach(array_keys($items) as $k) {
            $item = $items[$k];
            // echo '<pre>', print_r($item, 1), '</pre>';
            
            if(is_integer($item)) {
                $range[] = '-------------------------';
            } else {
                $text = (empty($msg[$item])) ? $item : $msg[$item];
                $range[$item] = $text;
            }
        }
        
        $select = new FormSelect();
        $select->select_tag = false;
        $select->setRange($range);
        
        foreach(array_keys($range) as $k) {
            if(is_numeric($k)) {
                $select->setOptionParam($k, 'disabled');                
            }
        }

        return $select->select($selected);
    }
    
    
    function _getUserFunction($func) {
        if($func == 'this') {
            $func = $this;
        } elseif($func == 'this->model') {
            $func = $this->model;
        }
        
        return $func;        
    }
    
    
    function getRuleHtml() {
        return $this->parseMultiIni(APP_MODULE_DIR . 'tool/trigger/template/form.ini');
    }
    
    
    function setRuleHtml() {
        $this->rules_html = $this->getRuleHtml();
    }
        
    
    function getRule($item, $rule, $counter) {
        
        $html = array();
        $form_data = $this->getRuleHtml();
        $rule_data = &$this->getRuleOption($item);
        $msg = &$this->getRulemsg();
        
        $replacer = new Replacer();
        
        // echo '<pre>', print_r($item, 1), '</pre>';
        // echo '<pre>', print_r($rule, 1), '</pre>';
        // echo '<pre>', print_r($rule_data, 1), '</pre>';
        // echo '<pre>', print_r("================", 1), '</pre>';
        
        $i = 0;
        foreach(array_keys($rule_data) as $k) {
            foreach($rule_data[$k] as $type => $v) {
        
                $a['num'] = $counter;
                $a['style'] = (isset($v['style'])) ? $v['style'] : '';
                $a['options'] = (isset($v['options'])) ? $v['options'] : '';
                $a['placeholder'] = (isset($v['placeholder'])) ? $msg[$v['placeholder']] : '';
                $a['condition_name'] = $this->condition_name;
                $a['id'] = sprintf('option_%d_%d', $counter, $k);
                $a['label_text'] = (isset($v['label_text'])) ? $msg[$v['label_text']] : '';
                $a['label_title'] = (isset($v['label_title'])) ? $msg[$v['label_title']] : '';
                
                $a['tooltip'] = '';
                if (isset($v['tooltip'])) {
                    $file = AppMsg::getCommonMsgFile('tooltip_msg.ini');
                    $tooltip = AppMsg::parseMsgsMultiIni($file, $v['tooltip']);
                    $a['tooltip'] = sprintf('<img src="../client/images/icons/help.svg" style="cursor: help;width: 16px;height: 16px;" class="_tooltip triggerTooltip" title="%s">', $tooltip);
                }
                
                $form_field = $form_data[$type];
            
                $value = (isset($v['value'])) ? $v['value'] : false;
                $value = (isset($rule[$i])) ? $rule[$i] : $value;
                
                if ($this->etype == 'article_automation') {
                    $a['popup_link'] = 'index.php?module=tool&page=automation&sub_page=am_article&action=category';
                }
                
                if ($this->etype == 'file_automation') {
                    $a['popup_link'] = 'index.php?module=tool&page=automation&sub_page=am_file&action=category';
                }
                
                if ($this->etype == 'email_automation') {
                    $a['popup_link'] = 'index.php?module=tool&page=automation&sub_page=am_email&action=entry&item=' . $item;
                    
                    if (!empty($_GET['id'])) {
                        $a['popup_link'] .= '&id=' . $_GET['id']; 
                    }
                }
                
                if ($type == 'text_popup' && $value) {
                    $etype = substr($this->etype, 0, -11);
                    
                    $range = $this->model->getCategorySelectRange($etype);
                    
                    $model_name = ($etype == 'article') ? 'KB' : 'File';
                    $emanager = TriggerModel::instance($model_name . 'EntryModel');
                    $range = $emanager->getCategorySelectRange(false, 0, '');
                    
                    if (!empty($range[$value])) {
                        $a['text_value'] = htmlentities($range[$value]);
                    }
                }
                
                if ($type == 'button' && $value) {
                    $a['style'] .= 'font-weight: bold;';
                }
                
                if ($type == 'image' && !$value) {
                    $value = '../client/images/icons/no_icon.png';
                }
                
                $a['checked'] = ($value) ? 'checked' : '';
                
                $a['error'] = false;
                if (isset($v['validate_func']) && $value) {
                    $func = $this->_getUserFunction($v['validate_func'][0]);
                    $args = (isset($v['args'])) ? $v['args'] : array();
                    $args[] = $value;
                    $is_valid = call_user_func_array(array($func, $v['validate_func'][1]), $args);
                    
                    if (!$is_valid) {
                        $this->_error = true;
                    }
                }
            
                // custom
                if($type == 'custom') {
                    $func = $this->_getUserFunction($v['func'][0]);
                    $args = array();
                    $args[] = $counter; // should be always first arg in custom func
                    $args[] = $value;
                    $html[] = call_user_func_array(array($func, $v['func'][1]), $args);
            
                // select             
                } elseif($type == 'select') {
                
                    if(isset($v['func'])) {
                        $func = $this->_getUserFunction($v['func'][0]);
                        $args = (isset($v['args'])) ? $v['args'] : array();
                        $args[] = $value;
                        $range = call_user_func_array(array($func, $v['func'][1]), $args);
                    } else {
                        $range = array();
                        foreach($v['option'] as $k2 => $v2) {
                            $range[$v2] = $msg[$v2];
                        }
                    }
                    
                    if (count($rule_data) != count($rule)) { // hide this select list
                        if (!empty($rule) && !$value) {
                            $a['style'] = 'display: none;';
                            $a['options'] = 'disabled';
                        }
                    }
                    
                    $a['value'] = $this->getSelect($range, $value);
                    
                    $html[] = $replacer->parse($form_field, $a);
    
                // checkbox
                } elseif($type == 'checkbox') {
                    $html[] = $replacer->parse($form_field, $a);
                    
                } elseif($type == 'email_body' || $type == 'email_subject') {
                    $a['value'] = $value;
                    $a['templates_link_title'] = $this->msg['show_template_tags_msg'];
                    $a['hide_templates_link_title'] = $this->msg['hide_template_tags_msg'];
                    $a['populate_link_title'] = $this->msg['populate_template_msg'];
                    
                    $msg2 = AppMsg::getMsgs('common_msg.ini');
                    $html[] = $replacer->parse($form_field, array_merge($a, $msg2));
                        
                // msg
                } elseif($type == 'msg') {
                    $i--; // rewrite back for value counter
                    $value = $v['value'];
                    $a['value'] = ($value) ? $msg[$value] : '';
                    $html[] = $replacer->parse($form_field, $a);
            
                } elseif($type == 'tag') {
                    
                    $a['templates_link_title'] = $this->msg['show_template_tags_msg'];
                    
                    require_once 'core/common/CommonEntryView.php';
                    $a['tag_block'] = CommonEntryView::getTagBlock(array(), '');

                    $html[] = $replacer->parse($form_field, $a);
            
                // empty
                } elseif($type == 'empty') {
    
                // other
                } else {
                    $a['value'] = $value;
                    $html[] = $replacer->parse($form_field, $a);
                }
            
                $i++;
            }
        }

        $html = '&nbsp;&nbsp;' . implode('&nbsp;&nbsp;', $html);
        return $html;
    }
    
    
    function getRuleText($item, $rule, $counter) {
        
        $html = array();
        $rule_data = &$this->getRuleOption($item);
        $msg = &$this->getRuleMsg();

        $i = 0;
        foreach(array_keys($rule_data) as $k) {
            foreach($rule_data[$k] as $type => $v) {            
                $value = (isset($v['value'])) ? $v['value'] : false;
                $value = (isset($rule[$i])) ? $rule[$i] : $value;
            
                // custom
                if($type == 'select') {
                
                    if(isset($v['func'])) {
                        $func = $this->_getUserFunction($v['func'][0]);
                        $args = (isset($v['args'])) ? $v['args'] : array();
                        $range = call_user_func_array(array($func, $v['func'][1]), $args);
                        $text = $range[$value];
                        
                    } else {
                        $text = $msg[$value];
                        
                        $range = array();
                        foreach($v['option'] as $k2 => $v2) {
                            $range[$v2] = $msg[$v2];
                        }                    
                    }
                    
                    $html[] = $text;
                    
                // other
                } else {
                    $html[] = $value;
                }
            
                $i++;
            }
        }

        $html = ' ' . implode(' ', $html);
        return $html;
    }
    
    
    function ajaxPopulate($item, $div_id) {
        
        $objResponse = new xajaxResponse();
        //$objResponse->addAlert($item);
        //$objResponse->addAlert($div_id);    
        
        //$html = '<textarea name="rule[{num}][]" id="{id}" style="width: 300px;" {options}>{value}</textarea>'; 
        $counter = preg_replace("#[^\d]#", '', $div_id);        
        $html =  $this->getRule($item, array(), $counter);
        $objResponse->addAssign("$div_id", 'innerHTML', $html);
        
        $objResponse->call('populatePostAction', $div_id);
    
        return $objResponse;    
    }
    
    
    function getMatchSelectRange() {
        $range = array();
        $range[1] = $this->msg['match_any_msg'];
        $range[2] = $this->msg['match_all_msg'];
        return $range;
    }
    
    
    function getMatchSelect($selected) {
        $select = new FormSelect();
        $select->select_tag = true;
        $select->select_name = 'cond_match';
        $select->setRange($this->getMatchSelectRange());
        return $select->select($selected);
    }
    
    
    function &parseItems($item_name, $ajax_func, $clone_js) {
        
        $div = $this->getRuleHtml();
        $div = $div['rule_div'];
        
        $items = $this->getItems();
        $replacer = new Replacer();
        
        $html = array();
        foreach(array_keys($items) as $counter) {
            $item = $items[$counter];
        
            $a = array();
            $a['item_name'] = $item_name;
            $a['ajax_func'] = $ajax_func;
            $a['clone_js'] = $clone_js;
            $a['condition_name'] = $this->condition_name;
            
            if ($counter == 1 && count($items) == 1) {
                $a['disabled'] = 'disabled';
                
            } else {
                $a['disabled'] = '';
            }

            $a['num'] = $counter;
            $a['display'] = 'block';

            $a['condition_id']          = $this->id_pref . $counter;
            $a['condition_rule_id']     = $this->id_pref_populate . $counter;
            $a['condition_item_select'] = $this->getItemSelect($item['item']);
            $a['condition_rule']        = $this->getRule($item['item'], $item['rule'], $counter);
            
            if ($this->_error) {
                $this->error = true;
                $this->_error = false;
                
                $a['style'] = 'background: #ffcccc;';
            }
            
            $html[] = $replacer->parse($div, $a);
        }
    
        $html = implode("\n", $html);
        return $html;
    }
    
    
    function &parseItemsToText() {
        
        $items = $this->getItems();
        
        $html = array();
        foreach(array_keys($items) as $counter) {
            $item = $items[$counter];
            
            $line = '';
            $line .= $this->msg['trigger_item'][$item['item']];
            $line .= $this->getRuleText($item['item'], $item['rule'], $counter);
        
            $html[] = ucfirst(strtolower($line));
        }
    
        $html = implode('<br />', $html);
        return $html;
    }
    
    
    function parseDefaultItem($item_name, $ajax_func, $clone_js) {
        
        $div = $this->getRuleHtml();
        $div = $div['rule_div'];
        
        $items = $this->getItems();
        $replacer = new Replacer();
        
        $a = array();
        $a['item_name'] = $item_name;
        $a['ajax_func'] = $ajax_func;
        $a['clone_js'] = $clone_js;
        $a['condition_name'] = $this->condition_name;
        
        $a['display'] = 'none';
        
        $a['condition_id'] = $this->id_readroot;
        $a['condition_item_select'] = $this->getItemSelect($this->default_item);
        $a['condition_rule'] =  $this->getRule($this->default_item, $this->default_rule, $this->counter);
        
        $html = $replacer->parse($div, $a);
    
        return $html;        
    }        
}

?>