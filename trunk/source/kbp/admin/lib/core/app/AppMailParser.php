<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2007 Evgeny Leontev                                    |
// +----------------------------------------------------------------------+
// | This source file is free software; you can redistribute it and/or    |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This source file is distributed in the hope that it will be useful,  |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// +----------------------------------------------------------------------+

require_once 'eleontev/Util/Replacer.php';
require_once 'eleontev/HTML/tplTemplatez.php';
require_once 'core/app/AppMsg.php';
require_once 'core/app/AppMailModel.php';


class AppMailParser
{

    var $template_dir;
    var $templates = array('send_to_friend'     => 'kb_friend.txt',
                           'answer_to_user'     => 'kb_answer.txt',
                           'contact'            => 'kb_contact.txt'
                           );
    
    
    var $vars      = array('support_name', 
                           'support_email', 
                           'support_mailer',
                           'noreply_email',
                           'admin_email',
                           'name',
                           'username',
                           'first_name',
                           'last_name',
                           'middle_name',
                           'email',
                           'link'
                           //'admin_username',
                           //'admin_first_name',
                           //'admin_last_name',
                           //'admin_middle_name',
                           //'admin_email'
                           
                           );

    
    
    function __construct() {
        $this->template_dir = APP_EMAI_TMPL_DIR;
        $this->setReplaser();
    }

    
    function setReplaser() {
        $this->replacer = new Replacer;
        $this->replacer->s_var_tag = "[";
        $this->replacer->e_var_tag = "]";
        $this->replacer->strip_var = false;
    }
    
    
    function setSettingVars($setting) {
        $this->assign('support_name', $setting['from_name']);
        $this->assign('support_email', $setting['from_email']);
        $this->assign('support_mailer', $setting['from_mailer']);
        $this->assign('noreply_email', $setting['noreply_email']);
        $this->assign('admin_email', $setting['admin_email']);
    }
    
    
    function assignUser($user) {
        $this->assign($user);
        $this->assign('name', $user['first_name'] . ' ' . $user['last_name']);
        $this->assign('login', $user['username']);
    }    
    
    
    function getVars() {
        return $this->vars;
    }
    
    
    function getValue($key) {
        if(isset($this->replacer->vars[$key])) {
            return $this->replacer->vars[$key];
        }
    }
    
    
    function assign($var, $value = false) {
        $this->replacer->assign($var, $value);
    }
    
    
    function parse($str) {
        return $this->replacer->parse($str);
    }
    
    
    function setTemplate($key, $value) {
        $this->templates[$key] = $value;
    }
    
    
    static function getTemplateMsg($letter_key = false) {
        $msg = AppMsg::getMsg('template_msg.ini', 'email_setting', 'common');
        return array_merge(AppMsg::getMsg('template_msg.ini', 'email_setting', $letter_key), $msg);
    }
    
    
    function getTemplate($letter_key) {
        
        $msg = $this->getTemplateMsg($letter_key);
        
        $template = $this->template_dir . $letter_key. '.txt';
        if(isset($this->templates[$letter_key])) {
            $template = $this->template_dir . $this->templates[$letter_key];
        }
        
        $extended_templates = AppMsg::parseMsgs(APP_EMAI_TMPL_DIR . '_extended_templates.ini', $letter_key);
        if (!empty($extended_templates)) {
            
            $lang_template = false;
            if ($extended_templates['template_lang']) {
                $file = AppMsg::getModuleMsgFileSingle('email_setting', $extended_templates['template_lang']);
                $tpl = new tplTemplatez($file);
                
                $tpl->clean_html = false;
                $tpl->strip_vars = false;
                
                //$tpl->tplParse($msg);
                $lang_template = $tpl->tplPrint(1);    
            }
            
            if (!$extended_templates['template_file']) { // we got just a lang file
                return $lang_template;
                
            } else { // we got another one in the template_email folder
                $msg['template'] = $lang_template;
                $template = $this->template_dir . $extended_templates['template_file'];
            }
        }
        
        $tpl = new tplTemplatez($template);
        $tpl->clean_html = false;
        //$tpl->strip_vars = false;
        
        $tpl->tplParse($msg);
        return $tpl->tplPrint(1);
    }
    
    
    function parseHtmlTemplate($template, $vars) {
        
        $tpl = new tplTemplatez($this->template_dir . 'page_html.html');
        $tpl->clean_html = false;
        $tpl->strip_vars = true;
        
        $tpl->tplAssign('content', $template);
        $tpl->tplAssign($vars);
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function &parseSubscriptionRow($data, $view) {
        
        $tpl = new tplTemplatez($this->template_dir . 'subscription_row.html');
        $tpl->clean_html = false;
        $tpl->strip_vars = false;
        
        $cc = &AppController::getClientController();
        
        foreach(array_keys($data) as $entry_id) {
            $a['title'] = $data[$entry_id]['title'];
            $a['link'] = $cc->getFolowLink($view, false, $entry_id);
            @$a['date'] = $data[$entry_id]['date'];
            
            $tpl->tplParse($a, 'row');
        }
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }    
}
?>