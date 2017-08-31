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

class KBClientController
{
    var $query  = array(
        'view'           =>'View',
        'category_id'    =>'CategoryID',
        'entry_id'       =>'EntryID',
        'entry_title'    =>'EntryTitle',
        'category_title' =>'CategoryTitle',
        'msg'            =>'Msg',
        'bp'             =>'bp',
        'message_id'     =>'message_id'
        );
    
    var $request;
    
    var $working_dir;
    var $working_path;    
    var $kb_path;
    var $client_path;
    var $setting = array();
    
    var $mod_rewrite = false;
    var $url_replace_rule = array();
    var $encoding;    
    var $ssl_client_custom = false;    
    
    var $extra_params = array();
    var $dirs = array();
    var $arg_separator = '&amp;';
    var $auth_ended = false;
    
    
    function __construct() {
        
        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
        
        $this->debug = $conf['debug_info'];
        $this->encoding = $conf['lang']['meta_charset'];
        if(isset($conf['lang']['replace'])) {
            $this->url_replace_rule = $conf['lang']['replace'];
        }
        
        $this->setCustomSsl($conf);
        $this->setUrlVars();
    }
    
    
    function setDirVars(&$settings) {
    
        $this->kb_path   = APP_CLIENT_PATH;
        $this->link_path = APP_CLIENT_PATH;
        $this->setting   = &$settings;
        
        // ssl views
        if($this->isCustomSslView($this->view_id)) {
            $this->kb_path = str_replace('http://', 'https://', $this->kb_path);
        }
        
                
        $this->kb_dir      = APP_CLIENT_DIR;
        $this->client_path = $this->kb_path . 'client/'; 
        $this->skin_dir    = APP_CLIENT_DIR . 'client/skin/';
        $this->skin_path   = $this->kb_path . 'client/skin/';
        
        $client_dir = APP_CLIENT_DIR . 'client/inc/';
        $this->common_dir          = $client_dir;
        $this->default_working_dir = $client_dir . 'default/';
        $this->working_dir         = $client_dir . $settings['view_format'] . '/';
    }
    
    
    function setUrlVars() {

        $this->category_id = (int) $this->getRequestVar('category_id');
        $this->category_title = urldecode($this->getRequestVar('category_title'));

        $this->entry_id = (int) $this->getRequestVar('entry_id');
        $this->entry_title = urldecode($this->getRequestVar('entry_title'));
        
        $this->view_key = $this->getRequestKey('view');
        $view_id = $this->getRequestVar('view');
        $this->view_id = ($view_id) ? $view_id : 'index';
        
        $this->msg_id = $this->getRequestVar('msg');
    }
    
    
    function setSettings(&$settings) {
        $this->setting = &$settings;
    }
    
    
    function getSetting($setting_key) {
        return @$this->setting[$setting_key];
    }
    
    
    function setModRewrite($var) {
        if($var == 2 || $var == 3) {
            $this->mod_rewrite = $var;
            
        // automatic
        } elseif($var == 1) {
            $this->mod_rewrite = (!empty($_SERVER['KBP_REWRITE'])) ? 2 : false;
        
        } else {
            $this->mod_rewrite = false;
        }
    }
    
    
    function setCustomSsl($var) {
         $this->ssl_client_custom = self::getCustomSsl($var);
    }


    static function getCustomSsl($var) {
        if($var === false) {
            return false;
        } else {
            if($var['ssl_admin'] && empty($var['ssl_client_2'])) {
                return $var['ssl_admin'];
            }
        }
    }
    
    
    function getRequestVar($var) {
        return (isset($_GET[$this->query[$var]])) ? urlencode(urldecode($_GET[$this->query[$var]])) : NULL;
    }
    
    
    function getRequestKey($key) {
        return @$this->query[$key];
    }
    
    
    function goUrl($url) {
        $url = $this->_replaceArgSeparator($url);
        header("Location: " . $url);
        exit();
    }
    
    
    function setCrowlMsg($msg_key) {
        $_SESSION['success_msg_'] = $msg_key;
    }
    
    
    function go($view = false, $category_id = false, $entry_id = false, $msg_key = false, 
                    $more = array(), $growl = false) {
        
        $this->arg_separator = '&';
        ini_set('arg_separator.output', '&');
        
        if(empty($more)) {
            $more = array();
        }
        
        // hash to anchor to correct message in forum and comments
        $hash = '';
        if (!empty($more['message_id'])) {
            $hash = '#c' . $more['message_id'];
            unset($more['message_id']);
        }
        
        
        // growl instead of msg
        if($growl) {
            $_SESSION['success_msg_'] = $msg_key;
            $msg_key = false;
        }
        
        
        // to avoid success_go, use crowl
        // to redirect to correct page and set session success_msg_ ...
        $success_go = false;
        if($view === 'success_go') {
            
            $sgo = $this->getView('success_go');
            $view = $sgo->getViewId($msg_key);
            $msg_id = $sgo->getMsgId($msg_key); // msg in url

            // if we redirect with msg (msg_id) then no need in clrowl, message will be on page
            if(!$msg_id) {
                $_SESSION['success_msg_'] = $sgo->getMsgIdToDisplay($msg_key);
            }

            $msg_key = $msg_id;// msg in URL
            
            //copied from KBClientView_success_go.php, not sure what it did
            // set to 0 correct display msg
            $category_id = ($msg_id && !$category_id) ? 0 : $category_id;
        }

        // for url title
        if($view == 'entry' || $view == 'topic' && $this->mod_rewrite == 3) {
            if($this->entry_title) {
                $entry_id = $this->getEntryLinkParams($entry_id, false, $this->entry_title);
            } else {
                $data = KBClientQuickModel::getEntryTitles($entry_id, $view);
                $entry_id = $this->getEntryLinkParams($entry_id, $data['title'], $data['url_title']);
            }
        }

        // for url category title
        if($view == 'index' || $view == 'files' && $this->mod_rewrite == 3) {
            if($this->category_title) {
                $category_id = $this->getEntryLinkParams($category_id, false, $this->category_title);
            }
        }

        
        $url = $this->getLink($view, $category_id, $entry_id, $msg_key, $more);
        $url .= $hash;
        
        // echo '<pre>', print_r($url, 1), '</pre>';
        // exit;
        
        header("Location: " . $url);
        exit();
    }    
    
    
    function getFullUrl($params, $more_params = array()) {
        
        $query = false;
        if($params || $more_params) {
            $params = array_merge($params, $more_params);
            $query = http_build_query($params);
        }
        
        return ($query) ? $_SERVER['PHP_SELF'] . '?' . $query : $_SERVER['PHP_SELF'];

    }
    
    
    function getLink($view = false, $category_id = false, $entry_id = false, $msg_key = false, 
                        $more = array(), $more_rewrite = false) {
        if($view == 'all') {
        
            if($_GET) {
                $diff = array_diff(array_keys($_GET), $this->query);
                foreach($diff as $v) {
                    $more[$v] = $_GET[$v];
                }
            }
            
            return $this->_getLink($this->view_id, $this->category_id, $this->entry_id, 
                                    $this->msg_id, $more, $more_rewrite);     
        
        } else {
            return $this->_getLink($view, $category_id, $entry_id, $msg_key, $more, $more_rewrite);
        }
    }
    
    
    function getLinkNoRewrite($view = false, $category_id = false, $entry_id = false, $msg_key = false, 
                                $more = array()) {
        
        $rewrite = $this->mod_rewrite;
        $this->mod_rewrite = 0;
        $link = $this->getLink($view, $category_id, $entry_id, $msg_key, $more);
        $this->mod_rewrite = $rewrite;
    
        return $link;
    } 
    
        
    // truing to make compatible with IIS 
    function getAjaxLink($view = false, $category_id = false, $entry_id = false, $msg_key = false, $more = array()) {
        $more['ajax'] = 1;
        $link = $this->getLink($view, $category_id, $entry_id, $msg_key, $more);
        $link = $this->_replaceArgSeparator($link);
        return $link;
    }
    
    
    function getRedirectLink($view = false, $category_id = false, $entry_id = false, $msg_key = false, $more = array()) {
        $link = $this->getLink($view, $category_id, $entry_id, $msg_key, $more);
        $link = $this->_replaceArgSeparator($link);        
        return $link;
    }    
    
    
    function getFolowLink($view = false, $category_id = false, $entry_id = false, $msg_key = false, $more = array()) {
        $link = $this->getLink($view, $category_id, $entry_id, $msg_key, $more);
        $link = $this->_replaceArgSeparator($link);        
        return $link;
    }
        
    
    static function getAdminRefLink($module = false, $page = false, $sub_page = false, $action = false, 
                                $more = array(), $replace_arg = true) {
        require_once 'core/base/Controller.php';
        require_once 'core/app/AppController.php';
        return AppController::getRefLink($module, $page, $sub_page, $action, $more, $replace_arg);
    }
    
    
    // admin_path force to https or http, dpends on ssl settings  
    function getAdminJsLink() {
        
        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
             
        if($conf['ssl_client']) {
            $link = str_replace('http://', 'https://', APP_ADMIN_PATH);    
        } else {
            $link = str_replace('https://', 'http://', APP_ADMIN_PATH);
        }
        
        return $link;
    }
    
    
    private function _getLink($view = 'index', $category_id = false, $entry_id = false, $msg_key = false, 
                                $more = array(), $more_rewrite = false) {
        
        $link = array();
        $kb_path = $this->link_path;
        $with_category_id = array(
            'index', 'files', 'news', 'troubles', 'featured', 'forums',
            'print-cat', 'pdf-cat', 'entry_add');
        $category_id = (in_array($view, $with_category_id)) ? $category_id : false;
        
        if($view == 'news' && $entry_id) { // as the same view for category and entry
            $category_id = false;
        }
        
        
        // msg on index page
        $force_index = ($view == 'index' && !$category_id && !$entry_id && $msg_key);
        
        $view = ($view == 'index' && !$force_index) ? false : $view;
        
        // ssl views
        if($this->isCustomSslView($view)) {
            $kb_path = str_replace('http://', 'https://', $kb_path);
        }    
            
        if(!$this->mod_rewrite) {
        
            if($view)        { $link[1] = sprintf('%s=%s', $this->getRequestKey('view'), $view); }
            if($category_id) { $link[2] = sprintf('%s=%d', $this->getRequestKey('category_id'), $category_id); }
            if($entry_id)    { $link[3] = sprintf('%s=%d', $this->getRequestKey('entry_id'), $entry_id); }
            if($msg_key)     { $link[4] = sprintf('%s=%s', $this->getRequestKey('msg'), $msg_key); }
            if($more)        { $link[5] = http_build_query($more); }
            if($this->extra_params) { $link[6] = http_build_query($this->extra_params); }
            
            if($view == 'form') {
                $link = $kb_path . 'index.php';            
            } else {
                $link = (!$link) ? $kb_path : $kb_path . 'index.php?' . implode($this->arg_separator, $link);
            }
        
        // entry title in url 
        } elseif($this->mod_rewrite == 3 && is_array($entry_id)) {
            $link = $entry_id['title'] . '_' . $entry_id['id'] . '.html';
            $link = ($view != 'entry') ? $view . '/' . $link : $link;
            $link = (@!$link) ? $kb_path : $kb_path . $link;
            // echo '<pre>', print_r($link, 1), '</pre>';
            
            if($more) {
                $link .= '?' . http_build_query($more);
            }
        
        // category title in url 
        } elseif($this->mod_rewrite == 3 && is_array($category_id)) {
            $link = $category_id['title'] . '-' . $category_id['id'].'/';
            $link = ($view && $view != 'entry') ? $view . '/' . $link : $link;
            $link = (@!$link) ? $kb_path : $kb_path . $link;
            // echo '<pre>', print_r($link, 1), '</pre>';
            
            if($more) {
                $link .= '?' . http_build_query($more);
            }
        
        // rewrite = /entry/1
        } else {
        
            if($view)        { $link[1] = sprintf('%s', $view); }
            if($category_id) { $link[2] = sprintf('%d', $category_id); }
            if($entry_id)    { $link[3] = sprintf('%d', $entry_id); }
            if($msg_key)     { $link[4] = sprintf('%s', $msg_key); }            
            
            $link = (@!$link) ? $kb_path : $kb_path . implode('/', $link) . '/';
        
            $link_extra = array();
            if($more) {
                if($more_rewrite) {
                    $link .= implode('/', $more) . '/';
                } else {
                    $link_extra[1] = http_build_query($more);
                }
            }
            
            if($this->extra_params) { 
                $link_extra[2] = http_build_query($this->extra_params); 
            }
        
            if($link_extra) {
                $link .= '?' . implode($this->arg_separator, $link_extra);
            }
        }
        
        return $link;
    }
    
    
    function _replaceArgSeparator($str) {
        return str_replace('&amp;', '&', $str);
    }

        
    function getUrlTitle($str, $maxlen = 100) {
    
        $rule  = array('#\s+#' => '-', 
                       '#[.,!?&<>\'":;/\[\]\|=‘’”"“–…\#]#' => '',
                       '#-{2,}#' => '-');
        
        @$str = html_entity_decode($str, ENT_QUOTES, $this->encoding);
        $str = preg_replace(array_keys($rule), $rule, $str);
        $str = substr($str, 0, $maxlen);
        
        return strtr($str, $this->url_replace_rule);
    }
    
    
    function getEntryLinkParams($entry_id, $entry_title, $url_title = false) {
        if($this->mod_rewrite == 3) {
            $url_title = ($url_title) ? $url_title : $this->getUrlTitle($entry_title);
            $url_title = _strtolower($url_title);
            $entry_id = array('id' => $entry_id, 'title' => $url_title);
        }
        
        return $entry_id;
    }
        
    
    function loadClass($view_id = false, $type = 'View') {
        
        if(!$view_id){
            $view_id = $this->view_id;
        }        
        
        $class = 'KBClientView_'. $view_id;
        
        $files = array();
        $files[]  = $this->working_dir . 'view/' . $class . '.php';
        $files[]  = $this->default_working_dir . 'view/' . $class . '.php';
        
        foreach($files as $file) {
            if(file_exists($file)) {
                require_once $file;
                return $class;
            }            
        }
        
        if($this->debug) {
            exit("KBClientController::loadClass($view_id) - Unable to load file $class");
        } else {
            $this->goStatusHeader('404');
        }
    }
    
    
    function &getView($view_id = false) {
        
        if(!$view_id){
            $view_id = $this->view_id;
        }
        
        $class = $this->loadClass($view_id);
        $class = new $class;
        return $class;
     }
    
    
    //could be called in some action to replace current action
    function getAction($view_id = false) {
        
        if(!$view_id){
            $view_id = $this->view_id;
        }
        
        $class = 'KBClientAction_'. $view_id;
        
        $files = array();
        $files[]  = $this->working_dir . 'action/' . $class . '.php';
        $files[]  = $this->default_working_dir . 'action/' . $class . '.php';
        $files[]  = $this->working_dir . 'action/KBClientAction_default.php';
        
        foreach($files as $file) {
            if(file_exists($file)) {
                require_once $file;
                return new $class;
            }
        }
    }    
    
    
    function getSearchParamsOnLogin($params) {
        
        $search_params = array(
            's', 'q', 'in', 'custom', 
            'et', 'c', 'cp', 'cf',
            'period', 'pv', 'is_from', 'is_to');
        
        $more = array();
        
        foreach($search_params as $v) {
            if(isset($params[$v])) {
                if(is_array($params[$v])) {
                    foreach($params[$v] as $k2 => $v2) {
                        $more[$v][$k2] = $v2;
                    }
                } else {
                    $more[$v] = $params[$v];
                }
            }
        }
        
        return $more;
    }
    
    
    function goStatusHeader($header, $view = 'index', $category_id = false, $entry_id = false, $msg_key = false) {
        if($header == '301') {
            header ('HTTP/1.1 301 Moved Permanently');
            $this->go($view, $category_id, $entry_id, $msg_key);
        
        } elseif($header == '404') {
            header("HTTP/1.1 404 Not Found");
            
            if($this->mod_rewrite) {
                $this->goUrl($this->link_path . '404.html');
            } else {
                $this->go('404');
            }
        }
    }
    
    
    function goAccessDenied($view) {
        $this->go('index', false, false, 'access_denied'); // display meesage on page 
        // $this->go('success_go', false, false, 'access_denied'); // in growl
    }   
    
    
    function checkRedirect() {
        
        // glossary print moved
        if($this->view_id == 'print' && $this->msg_id == 'glossary') {
              $this->goStatusHeader(301, 'print-glossary');
        }
    }
    
    
    function checkAuth($ajax = false) {
        
        // check for inactivity time
        $this->auth_ended = false;
        if(AuthPriv::getUserId()) {
            
            $auth = new AuthPriv;
            $auth->setCheckIp($this->setting['auth_check_ip']);
            
            // $skip_arr = array('entry_update');
            // $auth->setSkipAuthExpired((in_array($this->view_id, $skip_arr) && $_POST));
            
            // if auth_remember then ignore auth_expired
            $auth->setSkipAuthExpired($this->getSetting('auth_remember'));
            $auth->setAuthExpired($this->setting['auth_expired']);
            
            if(!$auth->isAuth()) {
                $auth->logout(false);
                $this->auth_ended = true;
            }
        }
        
        
        // registered only
        if(!AuthPriv::isAuthStatic($this->setting['auth_check_ip']) && $this->setting['kb_register_access']) {
            
            $not_private = array('register', 'login', 'password', 'confirm', 'success_go', 'confirmation');
            if(!in_array($this->view_id, $not_private)) {
                $login_msg = ($this->auth_ended) ?  'authtime_' . $this->view_id : $this->view_id . '_enter';
            
                // set entry_id
                $entry_id = $this->entry_id;
                if(!$this->entry_id && $this->category_id) {
                    $entry_id = $this->category_id;
                }
                
                // search params
                $more = array();
                if($this->view_id == 'search') {
                    $more = $this->getSearchParamsOnLogin($_GET);
                }
                
                // ajax logout
                if(isset($_GET['ajax'])) {
                    require_once $this->controller->kb_dir . 'client/inc/KBClientAjax.php';
                    if(KBClientAjax::isAjaxRequest()) {
                        $str = sprintf('<html>%s</html>', KBClientAjax::getlogout());
                        echo $str;
                        ob_end_flush();
                        exit;
                    }
                }
                
                $this->go('login', $this->category_id, $entry_id, $login_msg, $more);
            }
        }
    }
    
    
    function checkAutoLogin() {
        
        // saml auto
        if(!AuthPriv::getUserId() && $this->view_id != 'login') {
            $saml_auto = (AuthProvider::isSamlAuth() && AuthProvider::isSamlAuto());
        
            if($saml_auto && empty($_SESSION['saml_attempts_'])) {
                $_SESSION['saml_attempts_'] = 1;
                $more = array('sso' => 1, 'return' => $this->getLink('all'));
                $this->go('login', $this->category_id, $this->entry_id, false, $more);
            }    
        }
        
        
        // keep me signed, cookie exists and logged out
        // $auth_cookie removed on saml login so it should work 
        $auth_cookie = AuthPriv::getCookie();
        if(!AuthPriv::getUserId() && $auth_cookie) {
            
            require_once 'core/app/LoggerModel.php';
            
            $remove_auth = true;
            @list($selector,$validator) = explode(':', $auth_cookie);
                
            $log = new LoggerModel;
            $log->putLogin('Initializing...');
            
            $auth_type = 'auto'; // auto
            $exitcode = 3; // error
            $user_id = 0;
            $username = '';
            
            // auto login allowed (https, settings)
            if($this->isAutoLoginAllowed()) {
                
                $auth = new AuthPriv; 
                $data = $auth->isValidRememberAuth($selector, $validator);
                
                if($data) {
                    
                    // validate remote user data, changed or not
                    $user_remote_token = '';
                    $rvalid = true;
                    if(AuthProvider::isRemoteAuth()) {
                        $load_remote_error = AuthRemote::loadEnviroment();
                        if(defined('KB_AUTH_AREA')) {
                            $ldap_setting = SettingModel::getQuick(array(160));
                            $user_remote_token = AuthLdap::getUserTokenByUid($data['ruid'], $ldap_setting);
                            $rvalid = ($data['remote_token'] && ($data['remote_token'] == $user_remote_token));
                            
                            if($rvalid) {
                                $log->putLogin('Remote user was not changed, proceed to auto authentication');
                            } else {
                                $log->putLogin('Remote user changed, proceed to login form');
                            }
                        }           
                    }
                    
                    $user_id = $data['user_id'];
                    $username = addslashes($data['username']);
                    
                    $values = array('id' => $user_id);
                    $logged = ($rvalid) ? $auth->doAuthByValue($values) : false;
                                        
                    if($logged) {
                        $remove_auth = false;
                        $auth->setRememberAuth($user_id, $user_remote_token, $selector);
                        
                        $exitcode = 1; //well
                        $log->putLogin('Login successful');
                        UserActivityLog::add('user', 'login');
                    
                    } else {
                        $exitcode = 2; //failed
                        $log->putLogin('Login failed');
                    }
                       
                } else {
                    
                    $exitcode = 2; //failed
                    $log->putLogin('Unable to get data for automatic authentication');
                    $log->putLogin('Login failed');
                }
            
            } else {
                $exitcode = 3; //error
                $log->putLogin('Auto authentication disabled');
            }
            
        
            $log->putLogin(sprintf('Exit with the code: %d', $exitcode));
            $log->addLogin($user_id, $username, $auth_type, $exitcode);
            
            if($remove_auth) {
                $auth = (!empty($auth)) ? $auth : new AuthPriv;
                $auth->removeCookie();
                $auth->deleteRememberAuth($selector);
            }
        
        }
    }
    
    
    function checkPasswordExpiered() {
        
        if (in_array($this->view_id, array('member_account', 'logout'))) {
            return;
        }
        
        if(!AuthPriv::getUserId()) {
            return;
        }
        
        if(AuthPriv::isAdmin()) {
            return;
        }
        
        if(AuthPriv::getPassExpired()) {
            if($this->getSetting('password_rotation_policy') == 2) {
                $this->go('member_account', false, false, 'password');
            }
        }
    }
    
    
    function isAutoLoginAllowed() {
        
        $ret = false;
        if($this->getSetting('auth_remember')) {
            
            if($this->ssl_client_custom) {
                $ret = true;
            }
        
            if($ret === false) {
                $reg = &Registry::instance();
                $conf = &$reg->getEntry('conf');
                $ret = ($conf['ssl_client']);
            }
        }
        
        // $ret = true;
        return $ret;
    }
    
    
    function checkCustomSslView() {
        if($this->isCustomSslView($this->view_id)) {
            $port = ($this->ssl_client_custom == 1) ? 443 : $this->ssl_client_custom;
            if($_SERVER['SERVER_PORT'] != $port) {
                $this->go($this->view_id, $this->category_id, $this->entry_id, $this->msg_id);                
            }            
        }
    }
    
    
    // check if view for custom ssl views
    function isCustomSslView($view) {
        
        $ret = false;
        if(!$this->ssl_client_custom) {
            return $ret;
        }
        
        $ssl_view = array(
            'login', 'register', 'password',
            'member', 'member_account', 'member_subsc'
            );
        
        if(in_array($view, $ssl_view)) {
            $ret = true;        
        }
        
        return $ret;
    }
    
}
?>