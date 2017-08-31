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

require_once 'core/app/BanModel.php';
require_once APP_MODULE_DIR . 'log/login/inc/LoginLogModel.php';
require_once APP_MODULE_DIR . 'user/user/inc/UserActivityLog.php';


class KBClientView_login extends KBClientView_common
{
    
    var $login_msg = array(
        'comment'  => 'need_to_login_comment',
        'contact'  => 'need_to_login_contact',
        'enter'    => 'need_to_login_enter',
        'send'     => 'need_to_login_send',

        'afile'    => 'need_to_login_file',
        'file'     => 'need_to_login_file',
        'download' => 'need_to_login_file',
        'getfile'  => 'need_to_login_file',
    
        'entry'    => 'need_to_login_entry',
        'news'     => 'need_to_login_entry',

        'category' => 'need_to_login_category',
        'index'    => 'need_to_login_category',
        'files'    => 'need_to_login_category',
        'troubles' => 'need_to_login_category',

        'authtime' => 'need_to_login_authtime',
        'subscribe' => 'need_to_login_subscribe',
       
        'password_reset_success' => 'password_reset_success',
        'registration_confirmed' => 'registration_confirmed',
        
        'forums'         => 'need_to_login_forum',
        'forum'          => 'need_to_login_forum',
        'topic_add'      => 'need_to_login_topic',
        'topic_message'  => 'need_to_login_message',
        
        'sso_error'      => 'sso_error'
    );
    
    
    function &execute(&$manager) {
        
        $this->addMsg('user_msg.ini');
        
        $this->home_link = true;
        $this->parse_form = false;
        $this->meta_title = $this->msg['login_msg'];
        $this->nav_title = $this->msg['login_msg'];
        $this->category_nav_generate = false; // not to generate categories in navigation line
        
        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
        
        $saml_only = (AuthProvider::isSamlAuth() && AuthProvider::isSamlOnly());
        $data = ($saml_only) ? $this->getSamlForm($manager) : $this->getForm($manager);
        
        return $data;
    }
    

    function &getForm($manager) {
                                                            
        $tpl = new tplTemplatez($this->getTemplate('login_form.html'));
        
/*
        if ($manager->isUserBlocked()) {
            $errors['key'][]['msg'] = 'user_block_cookie';
            $this->setErrors($errors);
            
            $tpl->tplAssign('disabled', 'disabled'); 
        }
*/
        
        $tpl->tplAssign('error_msg', $this->getErrors());
        $tpl->tplAssign('action_link', $this->getLink('all'));
        

        $forgot_password = true;
        $forgot_password_link = $this->getLink('password');
        
        if($this->auth_remote) {
            $fpassword = AuthRemote::getPasswordLinkParams($forgot_password_link);
            $forgot_password = $fpassword['block'];
            $forgot_password_link = $fpassword['link'];
        }
        
        if($forgot_password) {
            $tpl->tplAssign('forgot_password_link', $forgot_password_link);
            $tpl->tplSetNeeded('/forgot_password');            
        }
        
        if($this->useCaptcha($manager, 'auth')) {
            $tpl->tplSetNeeded('/captcha');
            $tpl->tplAssign('captcha_src', $this->getCaptchaSrc());
        }
        
        if($manager->getSetting('register_policy')) {
            $tpl->tplAssign('register_link', $this->getLink('register'));
            $tpl->tplSetNeeded('/register_link');
        }
        
        // remember
        if($this->controller->isAutoLoginAllowed()) {
            $d = $this->getFormData();
            $tpl->tplAssign('remember_ch', $this->getChecked((isset($d['remember']))));
            $tpl->tplSetNeeded('/remember_me');
        }
        
        if($forgot_password && $manager->getSetting('register_policy')) {
            $tpl->tplSetNeeded('/divider');
        }
        
        if(!$manager->getSetting('kb_register_access')) {
            $block = (!empty($_POST)) ? 'cancel_button2' : 'cancel_button'; 
            $tpl->tplAssign('cancel_link', $this->getLink('index'));
            $tpl->tplSetNeeded('/' . $block);
        }
        
        if(AuthProvider::isSamlAuth()) {
            $auth_setting = AuthProvider::getSettings();
            
            $more = array('sso' => 1, 'return' => $this->sso_return_url);
            $tpl->tplAssign('sso_link', $this->getLink('login', false, false, false, $more));
            
            $r = array('name' => $auth_setting['saml_name']);
            $login_via_msg = AppMsg::replaceParse($this->msg['login_via_msg'], $r);
            $tpl->tplAssign('login_via', $login_via_msg);
            
            $tpl->tplSetNeeded('/sso_link');            
        }
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($this->convertRequiredMsg(array('login_username_msg', 'password_msg', 'captcha_msg')));
        $tpl->tplAssign($this->getFormData());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function &getSamlForm($manager) {
                                                            
        $tpl = new tplTemplatez($this->getTemplate('login_saml_form.html'));
        
        $tpl->tplAssign('error_msg', $this->getErrors());
        
        $more = array('sso' => 1, 'return' => $this->sso_return_url);
        $tpl->tplAssign('sso_link', $this->getLink('login', false, false, false, $more));
        
        $auth_setting = AuthProvider::getSettings();
        $r = array('name' => $auth_setting['saml_name']);
        $login_via_msg = AppMsg::replaceParse($this->msg['login_via_msg'], $r);
        $tpl->tplAssign('login_via', $login_via_msg);
        
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function validate($values, $manager, $log) {
        
        require_once 'eleontev/Validator.php';
                 
        $v = new Validator($values, false);
        $v->required('required_msg', array('username', 'password'));       
        
        if(!$this->auth_auto) {
            if($this->useCaptcha($manager, 'auth', true)) {
                if(!$this->isCaptchaValid($values['captcha'])) {
                    $v->setError('captcha_text_msg', 'captcha', 'captcha');
                }
            }
        }
        
        /* $ban_setting = $this->getBanSetting($manager, $values['username'], true);
         if($ban_setting) {
             $ban = BanModel::factory('login');
             if($date_banned = $ban->isBan(array('username' => $values['username']))) {    
                 $v->setError('captcha_text_msg', 'ban', 'ban');
             }
         }*/
        
                
        if($error = $v->getErrors()) {
            $log->putLogin(AppMsg::errorMessageString($error));
            $exitcode = 3;
        
        } else { 
            
            $auth = Auth::factory(($this->auth_remote) ? 'Remote' : 'Priv');
            $auth->setCheckIp($manager->getSetting('auth_check_ip'));
            $auth->log = &$log;
            
            $ret = $auth->doAuth($values['username'], $values['password']);
            
            $error_key = 'login_failed';
            
            // checking if banned
            /*$ban = BanModel::factory('login'); 
        
            $username = addslashes($values['username']);
            $log_manager = new LoginLogModel;  
            $user_id = ($ui_ = $log_manager->getUserIdByUsername($username)) ? $ui_ : 0;
            $user_ip = WebUtil::getIP();
        
            $ban_params = array('user_id' => $user_id, 'ip' => $user_ip, 'username' => $username);
            $is_ban = $ban->isBan($ban_params);
        
            if ($is_ban) {
                $auth = false;
                $error_key = 'user_banned';             
            }*/
                                    
            if($ret == false) {
                $err_msg = AppMsg::getMsgs('error_msg.ini', false, 'login_failed', 1);
                $err_msg = implode(' - ', $err_msg);
                
                $user_msg = sprintf(' (Username: %s)', $values['username']);
                $log->putLogin($err_msg . $user_msg);
                $exitcode = 2;
                                                  
                $msg = AppMsg::getMsgs('error_msg.ini', false, $error_key, 1); 
                $vars = array(); //array('count' => $msg_num); 
                $v->setError(BoxMsg::factory('error', $msg, $vars), 'auth', 'auth', 'formatted');
    
            } else {
                
                if($this->controller->isAutoLoginAllowed() && isset($values['remember'])) {
                    $auth->setRememberAuth(AuthPriv::getUserId(), $auth->user_ldap_token);
                }
                
                $log->putLogin('Login successful');
                $exitcode = 1;
                
                UserActivityLog::add('user', 'login');
                
                // password rotation 
                $password_rotation_freq = $manager->getSetting('password_rotation_freq');
                if($password_rotation_freq && !$this->auth_remote) {
                    if($auth->isPasswordExpiered(AuthPriv::getUserId(), $password_rotation_freq)) {
                        AuthPriv::setPassExpired(1);
                        
                        $this->controller->go('member_account', false, false, 'password');
                    }
                }
            }            
        }
        
        $user_id = (AuthPriv::getUserId()) ? AuthPriv::getUserId() : 0;
        $username = addslashes($values['username']);
        $auth_type = ($this->auth_remote) ? AuthProvider::getAuthType() : 'local';
                
        $log->putLogin(sprintf('Exit with the code: %d', $exitcode));
        $log->addLogin($user_id, $username, $auth_type, $exitcode);
        
        return $v->getErrors();
    }
    
        
    function getBanSetting($manager, $username, $ip = false) {
        
        if($ip === false) {
            $ip = WebUtil::getIP();
        }
        
        $ip = ($ip == 'UNKNOWN') ? 0 :  $ip;
        
        $s = array();
$manager->setting['login_ban_ip'] = 7;
        if($allowed = (int) $manager->getSetting('login_ban_ip')) {
            $s[1]['ip'] = $allowed;
            $s[2]['ip']['allowed'] = $allowed;
            $s[2]['ip']['value']   = $ip;
        }

$manager->setting['login_ban_username'] = 3;
        if($allowed = (int) $manager->getSetting('login_ban_username')) {
            $s[1]['username'] = $allowed;
            $s[2]['username']['allowed'] = $allowed;
            $s[2]['username']['value']   = $username;
        }

        return $s;
    }
    
}
?>