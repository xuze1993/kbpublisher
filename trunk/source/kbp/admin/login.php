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

require_once 'config.inc.php';
require_once 'config_more.inc.php';
require_once 'common.inc.php';
require_once 'eleontev/Validator.php';
require_once 'core/app/AppMsg.php';
require_once 'core/app/LoggerModel.php';
require_once 'core/app/BanModel.php';
require_once APP_MODULE_DIR . 'log/login/inc/LoginLogModel.php';
require_once APP_MODULE_DIR . 'user/user/inc/UserActivityLog.php';

session_name($conf['session_name']);
session_start();


$saml = AuthProvider::isSamlAuth();
if($saml) {
    if (AuthProvider::isSamlOnly()) {
        
        $cc = AppController::getClientController();
        
        $more_sso = array('return' => APP_ADMIN_PATH . 'index.php?module=home&page=home');
        $sso_url = $cc->getLink('login', false, false, false, $more_sso);
        
        header('Location: ' . $cc->_replaceArgSeparator($sso_url));
        exit;
    }
}

$auth = false;
$errors = false;
$use_captcha = false;
$setting = SettingModel::getQuick(1);
$password_rotation_freq = $setting['password_rotation_freq'];

if($setting['auth_captcha'] == 'yes') {
    require_once 'eleontev/Util/CaptchaImage.php';
    require_once APP_CLIENT_DIR . 'client/inc/KBClientView.php';
    
    if(CaptchaImage::isRequredLib()) {
        $use_captcha = true;
    }
}


$rvars = AuthProvider::getRemoteAuthVars(true);
$auth_remote = $rvars['auth_remote'];
$auth_auto = $rvars['auth_auto'];
$load_remote_error = $rvars['load_remote_error'];

// normal login
if (isset($_POST['login_username']) && isset($_POST['login_password'])) {

    AuthPriv::logout();
    
    $log = new LoggerModel;
    $log->putLogin('Initializing...');    
    if($load_remote_error) {
        $log->putLogin($load_remote_error);
    }

    $errors = loginValidate($_POST, $conf, $use_captcha, $auth_remote, $log, 
                                false, $password_rotation_freq);
    
    if(!$errors) {
        $auth = true;
    }


// auto login
} elseif ($auth_auto) {        
        
    $r = AuthPriv::isRemoteAutoAuth();
    AuthPriv::logout();
    AuthPriv::setRemoteAutoAuth($r);        
        
    $use_captcha = false;

    $log = new LoggerModel;
    $log->putLogin('Initializing...');
    if($load_remote_error) {
        $log->putLogin($load_remote_error);
    }

    $priv = Auth::factory('Remote');    
    $priv->log = &$log;
    $user = $priv->autoAuth();

    if($user !== false) {

        $values['login_username'] = (isset($user['username'])) ? $user['username'] : '';
        $values['login_password'] = (isset($user['password'])) ? $user['password'] : '';
        $errors2 = loginValidate($values, $conf, $use_captcha, $auth_remote, $log, 
                                    true, $password_rotation_freq);

        if(!$errors2) {
            $auth = true;
        }

    } else {

        // for remote write last login to file to debug 
        $log->writeLogFile();
    }
}


if ($auth == false) {
    sleep(1); // for brute attack    
    echo loginForm($conf, $errors, $use_captcha, $auth_remote, $saml);

} else {
    
    $page = APP_ADMIN_PATH . 'index.php?module=home&page=home';
    if(!empty($_SESSION['ref_'])) {
        $page = 'index.php?' . str_replace('&amp;', '&', WebUtil::unserialize_url($_SESSION['ref_']));
    }
    
    header('Location: ' . $page);
    exit();
}


function loginForm($conf, $errors, $use_captcha, $auth_remote, $saml) {

    $tpl = new tplTemplatez(APP_TMPL_DIR . 'login2.html');
    $tpl->strip_vars = true;
    $tpl->tplAssign('meta_charset', $conf['lang']['meta_charset']);
    
    if(APP_DEMO_MODE) {
        $tpl->tplSetNeeded('/demo');
    }    
    
    if($errors) {
        $tpl->tplAssign('error_msg', AppMsg::errorBox($errors));
    
    } elseif(@$_GET['msg'] == 'auth_expired') {
        $msgs = AppMsg::errorServiceBox('auth_expired');
        $tpl->tplAssign('error_msg', $msgs);
    }
    
    
    $cc = AppController::getClientController();

    $forgot_password = true;
    $forgot_password_link = $cc->getLink('password');
    
    if($auth_remote) {
        $fpassword = AuthRemote::getPasswordLinkParams($forgot_password_link);
        $forgot_password = $fpassword['block'];
        $forgot_password_link = $fpassword['link'];
     }       
    
    if($forgot_password) {
        $tpl->tplAssign('forgot_password_link', $forgot_password_link);
        $tpl->tplSetNeeded('/forgot_password');            
    }
    
    if($use_captcha) {
        $tpl->tplSetNeeded('/captcha');
        $tpl->tplAssign('captcha_src', APP_CLIENT_PATH . 'captcha.php');
    }
    
    $msg = AppMsg::getMsgs('user_msg.ini');
    
     // saml
    if ($saml) {
        $auth_setting = AuthProvider::getSettings();
        
        $more_sso = array(
            'return' => APP_ADMIN_PATH . 'index.php?module=home&page=home',
            'sso' => 1
        );
        $sso_url = $cc->getLink('login', false, false, false, $more_sso);
        $tpl->tplAssign('sso_link', $sso_url);
        
        $r = array('name' => $auth_setting['saml_name']);
        $login_via_msg = AppMsg::replaceParse($msg['login_via_msg'], $r);
        $tpl->tplAssign('login_via', $login_via_msg);
        
        $tpl->tplSetNeeded('/sso_link');
    }
    
    
    $msg['login_title_msg'] = $msg['login_title_msg'];
    $msg['cancel_link'] = APP_CLIENT_PATH;
    
    $msg['product_name']    = $conf['product_name'];
    $msg['product_version'] = $conf['product_version'];
    $msg['product_www']     = $conf['product_www'];
    
    $tpl->tplAssign($msg);
    
    $tpl->tplParse($_POST);
    return $tpl->tplPrint(1);
}


function loginValidate($values, $conf, $use_captcha, $auth_remote, $log, 
                            $auto_auth, $password_rotation_freq) {
                                      
    $required = array('login_username', 'login_password');
    
    $v = new Validator($values, false);
    $v->required('required_msg', $required);
    
    if($use_captcha) {
        if(!KBClientView::isCaptchaValid($values['captcha'])) {
            $v->setError('captcha_text_msg', 'captcha', 'captcha');
        }
    }
    
    if($error = $v->getErrors()) {
        $log->putLogin(AppMsg::errorMessageString($error));        
        $exitcode = 3;
    
    } else {
        
        $priv = Auth::factory(($auth_remote) ? 'Remote' : 'Priv');
        $priv->setCheckIp($conf['auth_check_ip']);
        $priv->log = &$log;    
        $auth = $priv->doAuth($values['login_username'], $values['login_password']);
 
        if(!$auth) {
            $err_msg = AppMsg::getMsgs('error_msg.ini', false, 'login_failed', 1);
            $err_msg = implode(' - ', $err_msg);
    
            $user_msg = sprintf(' (Username: %s)', $values['login_username']);
            $log->putLogin($err_msg . $user_msg);
            $exitcode = 2;

            $msg = AppMsg::getMsgs('error_msg.ini', false, 'login_failed', 1); 
            $vars = array(); //array('count' => $msg_num); 
            $v->setError(BoxMsg::factory('error', $msg, $vars), 'auth', 'auth', 'formatted');
        
        } else {
            $log->putLogin('Login successful');
            $exitcode = 1;
            
            UserActivityLog::add('user', 'login');
            
            // password rotation 
            if($password_rotation_freq && !$auth_remote) {
                if($priv->isPasswordExpiered(AuthPriv::getUserId(), $password_rotation_freq)) {
                    AuthPriv::setPassExpired(1);
                    
                    $page = APP_ADMIN_PATH . 'index.php?module=account&page=account_user&action=password';
                    header('Location: ' . $page);
                    exit();
                }
            }
        }
    }
    
    $user_id = (AuthPriv::getUserId()) ? AuthPriv::getUserId() : 0;
    $username = addslashes($values['login_username']);
    $auth_type = ($auth_remote) ? AuthProvider::getAuthType() : 'local';
    
    $log->putLogin(sprintf('Exit with the code: %d', $exitcode));
    $log->addLogin($user_id, $username, $auth_type, $exitcode);
    
    return $v->getErrors();
}
?>