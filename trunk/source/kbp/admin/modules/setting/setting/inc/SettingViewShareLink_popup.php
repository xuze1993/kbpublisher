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

require_once APP_MODULE_DIR . 'tool/trigger/inc/TriggerParser.php';
require_once APP_MODULE_DIR . 'tool/trigger/inc/TriggerParserCondition.php';
require_once APP_MODULE_DIR . 'tool/trigger/inc/TriggerModel.php';
require_once APP_MODULE_DIR . 'setting/setting/inc/SettingView_form.php';


class SettingViewShareLink_popup extends SettingView_form
{
    
    var $sites = array(
        'Twitter' => array(
            'url' => 'http://twitter.com/intent/tweet?url=[url]',
            'icon' => '{client_href}images/icons/socialmediaicons/[size]/twitter.png'
         ),
        'Facebook' => array(
            'url' => 'http://facebook.com/sharer.php?u=[url]&title=[title]',
            'icon' => '{client_href}images/icons/socialmediaicons/[size]/facebook.png'
         ),
        'Google Plus' => array(
            'url' => 'https://plus.google.com/share?url=[url]',
            'icon' => '{client_href}images/icons/socialmediaicons/[size]/googleplus.png'
         ),
        'LinkedIn' => array(
            'url' => 'https://www.linkedin.com/cws/share?url=[url]&title=[title]',
            'icon' => '{client_href}images/icons/socialmediaicons/[size]/linkedin.png'
        ),
        'Reddit' => array(
            'url' => 'http://www.reddit.com/submit?url=[url]&title=[title]',
            'icon' => '{client_href}images/icons/socialmediaicons/[size]/reddit.png'
        ),
        'Digg' => array(
            'url' => 'https://digg.com/submit?url=[url]&title=[title]',
            'icon' => '{client_href}images/icons/socialmediaicons/[size]/digg.png'
        ),
        'Delicious' => array(
            'url' => 'https://delicious.com/save?v=5&noui&jump=close&url=[url]&title=[title]',
            'icon' => '{client_href}images/icons/socialmediaicons/[size]/delicious.png'
        ),
        'StumpleUpon' => array(
            'url' => 'http://www.stumbleupon.com/submit?url=[url]&title=[title]',
            'icon' => '{client_href}images/icons/socialmediaicons/[size]/stumbleupon.png'
        ),
    );
    
    
    function execute(&$obj, &$manager) {
        $tpl = new tplTemplatez($this->template_dir . 'form_share_link.html');
        
        $msg = AppMsg::getMsg('setting_msg.ini', 'public_setting', 'item_share_link');
        $tpl->tplAssign('popup_title', $msg['title']);
        
        $items = array();
        
        $lines = explode("\n", $obj->get('item_share_link'));
        foreach ($lines as $line) {
            $line = explode('|', $line);
            $line = array_map('trim', $line);
            list($title, $url, $icon) = $line;
            
            $icon = str_replace('[size]', '24x24', $icon);
            $icon = str_replace('{client_href}', '../client/', $icon);
            
            if (in_array($title, array_keys($this->sites))) {
                $items[] = array(
                    'item' => $title,
                    'rule' => array($icon)
                );
                
            } else {
                
                if ($icon) {
                    $is_remote = (strpos($icon, 'http://') !== false || strpos($icon, 'https://') !== false);
                    $is_root = ($icon[0] == '/');
                    $_icon = ($is_remote || $is_root) ? $icon : '../' . $icon;
                    
                } else {
                    $_icon = '';
                }
                
                $items[] = array(
                    'item' => 'custom',
                    'rule' => array($_icon, $title, $url, $icon)
                );
            }
        }
        
        $cond = TriggerParserCondition::factory('setting');
        $cond->view = $this;
        $cond->encoding = $this->encoding;
        $cond->time_format = $this->conf['lang']['time_format'];
        $cond->setMsg();
        
        $tr_manager = new TriggerModel;
        $cond->setManager($tr_manager);
        
        $cond->setItems($items);

        $tpl->tplAssign('condition_html', $cond->parseItems('condition', 'populateCondition', 'sc'));
        
        
        $tpl->tplAssign('condition_readroot', $cond->id_readroot);
        $tpl->tplAssign('condition_writeroot', $cond->id_writeroot);
        $tpl->tplAssign('condition_id_pref', $cond->id_pref);
        $tpl->tplAssign('condition_id_pref_populate', $cond->id_pref_populate);
        $tpl->tplAssign('condition_counter', $cond->counter);
        $tpl->tplAssign('condition_html_default', $cond->parseDefaultItem('condition', 'populateCondition', 'sc'));
        
        
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->setRequestURI($this->controller->getAjaxLink('setting', 'public_setting', 'kba_setting', false, array('popup' => 'item_share_link')));
        
        $xajax->registerFunction(array('populateCondition', $cond, 'ajaxPopulate'));
        $xajax->registerFunction(array('saveLinks', $this, 'ajaxSaveLinks'));
        $xajax->registerFunction(array('setDefaults', $this, 'ajaxSetDefaults'));
        
        
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        
        return $tpl->tplPrint(1);
    }
    
    
    function ajaxSaveLinks($data) {
        
        $objResponse = new xajaxResponse();
        
        $params = array();
        parse_str($data, $params);
        
        $lines = array();
        $error_msg_key = false;
        foreach($params['cond'] as $k => $v) {
            //if (in_array($v['title'], array_keys($this->sites))) {
            if (!empty($v['rule'])) {
                
                if (strpos($v['rule'][1], '[url]') === false) {
                    $error_msg_key = 'share_url_format_msg';
                }
                
                $icon = $v['rule'][2];
                if ($icon) {
                    $is_remote = (strpos($icon, 'http://') !== false || strpos($icon, 'https://') !== false);
                    $is_root = ($icon[0] == '/');
                    
                    if ($is_root) {
                        $icon = $_SERVER['DOCUMENT_ROOT'] . $icon;
                        
                    } elseif (!$is_remote) {
                        chdir(APP_CLIENT_DIR);
                        $icon = realpath($icon);
                    }
                    
                    if (!$icon || !@getimagesize($icon)) {
                        $error_msg_key = 'icon_not_found_msg';
                    }
                }
                
                if (empty($v['rule'][0])) {
                    $error_msg_key = 'share_title_msg';
                }
                
                if ($error_msg_key) {
                    $msgs = AppMsg::getMsgs('error_msg.ini');
                    $msg_vars = array('body' => $msgs[$error_msg_key]);
                    $objResponse->assign('error_block', 'innerHTML', BoxMsg::factory('error', $msg_vars)); 
                    
                    $objResponse->assign('more_condition_' . $k, 'style.background', '#ffcccc');
                     
                    return $objResponse;
                }
                
                $lines[] = implode(' | ', $v['rule']);
                
            } else {
                $lines[] = sprintf('%s | %s | %s', $v['item'], $this->sites[$v['item']]['url'], $this->sites[$v['item']]['icon']);
            }
        }
        
        $lines = array_unique($lines);
        $data = implode("\n", $lines);
        
        $setting_key = 'item_share_link';
        $setting_id = $this->manager->getSettingIdByKey($setting_key);
        $this->manager->setSettings(array($setting_id => addslashes($data)));
        
        $num = (strlen($data) > 0) ? count($lines) : 0;
        $objResponse->assign('custom_counter', 'innerHTML', ($num) ? sprintf('(%d)', $num) : '');
        
        $objResponse->script('LeaveScreenMsg.skipCheck();location.reload();');
        
        return $objResponse;    
    }
    
    
    function ajaxSetDefaults() {
        
        $objResponse = new xajaxResponse();
        
        $setting_id = $this->manager->getSettingIdByKey('item_share_link');
        $this->manager->setDefaultValues($setting_id);
        
        $objResponse->script('LeaveScreenMsg.skipCheck();location.reload();');
        
        return $objResponse;    
    }
}
?>