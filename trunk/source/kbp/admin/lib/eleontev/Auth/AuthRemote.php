<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2005 Evgeny Leontev                                    |
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

/**
 * AuthRemote is a class used to replace/extend KBPublisher authentification
 * with you own implementation.
 *
 * @version 1.0
 * @since 19/12/2008
 * @author Evgeny Leontiev <eleontev@gmail.com>
 * @access public
 */


require_once 'eleontev/Auth/Auth.php';
require_once 'eleontev/Auth/AuthLdap.php';
require_once 'eleontev/Auth/AuthRemoteModel.php';
require_once 'eleontev/Util/IPUtil.php';


class AuthRemote extends AuthPriv
{

    var $auth_type = KB_AUTH_TYPE;
    var $auth_refresh_time = KB_AUTH_REFRESH_TIME;
    var $auth_area = KB_AUTH_AREA;
    var $auth_local = KB_AUTH_LOCAL;
    var $auth_local_ip = KB_AUTH_LOCAL_IP;

    var $log;
    var $use_script = KB_AUTH_SCRIPT; // whether with remoteDoAuth or nor (with settings)

    // to use for remember auth (keep logged-in)
    // to validate if user data changes on LDAP since we remembered him
    var $user_ldap_token;


    function __construct() {
        $this->umanager = new AuthRemoteModel;
        parent::__construct();
    }


   /**
    * doAuth
    *
    * @param    string    $username
    * @param    string    $password
    * @param              $remember, $md5 for compability with AuthPriv::doAuth
    *
    * @return   bool      true or false
    * @access   public
    */
    function doAuth($username, $password, $md5 = true) {

        $this->putLog('Initializing REMOTE authentication');

        if($this->use_script) {
            if(!function_exists('remoteDoAuth')) {
                $this->putLog('Function remoteDoAuth does not exist, proceed with LOCAL authentication...');
                return parent::doAuth($username, $password);
            }
        }

        // local auth
        if($this->auth_local == 1) {
            $this->putLog('Trying LOCAL authentication (before remote authentication)');
            $auth_local = true;
            if($this->auth_local_ip) {
                if(!IPUtil::isValidQuick($this->auth_local_ip)) {
                    $msg = 'User IP %s does not match rules. Valid IP(s): %s, proceed with REMOTE authentication...';
                    $this->putLog(sprintf($msg, IPUtil::getIP(), $this->auth_local_ip));
                    $auth_local = false;
                }
            }

            if($auth_local) {
                if(parent::doAuth($username, $password)) {
                    return true;
                }

                $this->putLog('LOCAL authentication failed, proceed with REMOTE authentication...');
            }
        }

        $auth = false;

        if($this->use_script) {
            $user = remoteDoAuth($username, $password);
        } else {
            $user = $this->remoteDoAuthSetting($username, $password);
        }

        if($user) {
            $user = &RequestDataUtil::stripslashes($user, array('username'));
            $user = &RequestDataUtil::addslashes($user, array('username'));
        }

        //echo '<pre>', print_r($user, 1);

        if(!$user) {
            $msg = 'REMOTE authentication (remoteDoAuth) does not authenticate user (no any data returned)';
            $this->putLog($msg);
        }

        // remoteDoAuth may return error
        if(!empty($user['error_msg'])) {
            $this->putLog($user['error_msg']);
        }

        // add, rewrite user on request
        // login remote only
        if($this->auth_type == 1 && $user) {
            $this->putLog('Auth type: 1, (add/rewrite user on login)');

            if($this->validateRemoteReturn($user)) {
                echo $this->parseWrongParamsMsg($user);
                exit;
            }

            $kb_user = $this->umanager->isUserByRemoteId($user['remote_user_id']);
            if($kb_user) {
                $msg = 'Remote User exists within KBPublisher database, kbp_user_id: %d, remote_user_id: %s';
                $this->putLog(sprintf($msg, $kb_user['id'], $user['remote_user_id']));

                $user['id'] = $kb_user['id'];
                $spent = time() - $kb_user['lastauth'];

                // rewrite user
                if(!empty($this->auth_refresh_time)) {
                    if($spent > intval($this->auth_refresh_time)) {

                        if($this->umanager->isUserByUsername($user['username'], $user['id'])) {
                            $user['username'] = 'kb_' . $user['username'];
                        }

                        $user['date_registered'] = $kb_user['date_registered'];
                        // if(!$user['date_registered']) {
                            // $user['date_registered'] = NULL;
                        // }

                        if($this->umanager->isUserByEmail($user['email'], $user['id'])) {
                            echo $this->parseEmailExistsMsg($user);
                            exit;
                        }

                        $this->umanager->saveUser($user);
                        $this->putLog('User was successfully re-written (synchronized)');
                    }
                }

                // get kb user and auth
                $user = $this->umanager->getUserById($user['id']);
                $auth = parent::doAuth($user['username'], $user['password'], false);


            // save first time
            } else {

                $check_user_imported = true;
                if(defined('KB_AUTH_REWRITE_USER_BY_EMAIL')) {
                    $check_user_imported = 'KB_AUTH_REWRITE_USER_BY_EMAIL';
                }

                if($kb_user = $this->umanager->isUserByEmail($user['email'])) {
                    $exit = true;

                    // so we assume it is the same user but never logged from remote auth
                    if($check_user_imported && !$this->umanager->isUserImported($kb_user['id'])) {
                        $exit = false;
                    }

                    if($exit) {
                        echo $this->parseEmailExistsMsg($user);
                        exit;
                    }
                }

                $kb_user_id = ($kb_user) ? $kb_user['id'] : false;
                if($this->umanager->isUserByUsername($user['username'], $kb_user_id)) {
                    $user['username'] = 'kb_' . $user['username'];
                }

                if($kb_user) {

                    $user['id'] = $kb_user['id'];
                    $user['date_registered'] = $kb_user['date_registered'];
                    // if(!$user['date_registered']) {
                        // $user['date_registered'] = NULL;
                    // }

                    $user_msg = 'Remote User exists within KBPublisher database (identified by email), kbp_user_id: %d';
                    $user_msg = sprintf($user_msg, $kb_user['id']);
                    $add_msg = 'User was successfully re-written (synchronized)';

                } else {

                    $user['date_registered'] = NULL;

                    $user_msg = 'Remote User does not exist within KBPublisher database, synchronizing...';
                    $add_msg = 'User was successfully added to KBPublisher database';
                }

                $this->putLog($user_msg);
                $this->umanager->saveUser($user);
                $this->putLog($add_msg);

                $auth = parent::doAuth($user['username'], $user['password']);
            }


        // login as existing kb user (previously defined)
        // $user is the user_id (id field in kb_user table)
        } elseif($this->auth_type == 2 && $user) {
            $this->putLog('Auth type: 2, (login as existing KBP user, previously defined)');

            $error = false;
            if(is_array($user) && !isset($user['user_id'])) {
                $error = true;
            } elseif(!is_array($user) && !is_numeric($user)) {
                $error = true;
            }

            if($error) {
                echo $this->parseWrongParamsMsg($user);
                exit;
            }


            $user_id = $user;
            if(is_array($user) && isset($user['user_id'])) {
                $user_id = $user['user_id'];
            }

            $kb_user = $this->umanager->getUserById($user_id);
            if($kb_user) {
                $this->putLog(sprintf('User ID: %d, log in...', $user_id));
                $auth = parent::doAuth($kb_user['username'], $kb_user['password'], false);

                if($auth) {
                    $_SESSION[$this->as_name]['remote'] = 1;
                    if(is_array($user) && isset($user['username'])) {
                        $_SESSION[$this->as_name]['remote_username'] = $user['username'];
                    }
                }
            } else {
                $msg = 'User ID: %d, does not exist within KBPublisher database';
                $this->putLog(sprintf($msg, $user_id));
            }


        // custom
        } //elseif($this->remote_auth == 3) {
        //    $auth = $user;
        //}


        //local auth
        if($this->auth_local == 2 && !$auth) {
            $this->putLog('Trying LOCAL authentication (after remote authentication failed)');
            $auth_local = true;
            if($this->auth_local_ip) {
                if(!IPUtil::isValidQuick($this->auth_local_ip)) {
                    $msg = 'User IP %s does not match rules. Valid IP(s): %s, exit LOCAL authentication';
                    $this->putLog(sprintf($msg, IPUtil::getIP(), $this->auth_local_ip));
                    $auth_local = false;
                }
            }

            if($auth_local) {
                $auth = parent::doAuth($username, $password);
            }
        }

        //exit;
        return $auth;
    }


    function parseWrongParamsMsg($user) {
        $msg = AppMsg::afterActionMsg('wrong_remote_auth_params');
        $this->putLog(implode(' - ', $msg));
        $this->putLog('Returned values: ' . print_r($user, 1));

        $exitcode = 3;
        $username = (isset($user['username'])) ? addslashes($user['username']) : '';
        $this->putLog(sprintf('Exit with the code: %d', $exitcode));
        $this->addLog(0, $username, 2, $exitcode);

        // we need this message to avoid many questions ...
        return AppMsg::afterActionBox('wrong_remote_auth_params');
    }


    function parseEmailExistsMsg($user) {
        $msg = AppMsg::afterActionMsg('wrong_remote_email_exists');
        $this->putLog(implode(' - ', $msg));
        $this->putLog('Returned values: ' . print_r($user, 1));

        $exitcode = 3;
        $username = (isset($user['username'])) ? addslashes($user['username']) : '';
        $this->putLog(sprintf('Exit with the code: %d', $exitcode));
        $this->addLog(0, $username, 2, $exitcode);

        // we need this message to avoid many questions ...
        return AppMsg::afterActionBox('wrong_remote_email_exists');
    }


    // validate
    function validateRemoteReturn($values) {

        require_once 'eleontev/Validator.php';

        $required = array(
            'first_name', 'last_name', 'email',
            'username', 'password', 'remote_user_id'
        );

        $v = new Validator($values, false);
        $v->required('remote_auth_required_msg', $required);
        return $v->getErrors();
    }


   /**
    * authToSessionRemote -- save user authentication to session
    *
    * @param    int       $user_id
    * @param    string    $username
    *
    * @access   public
    */
    function authToSessionRemote($user_id, $username) {
        $this->authToSession($this->as_name, $user_id, $username);
    }


   /**
    * roleToSessionRemote -- save user roles to session
    *
    * @param    int      $role_id
    *
    * @access   public
    */
    function roleToSessionRemote($role_id) {
        $role_id = (is_array($role_id)) ? $role_id : array($role_id);
        $this->roleToSession($this->as_name, $role_id);
    }


   /**
    * privToSessionRemote -- save user priv to session
    *
    * @param    int       $priv_id      privilege id
    *
    * @access   public
    */
    function privToSessionRemote($priv_id) {
        $this->privToSession($priv_id);
    }


    // ACCOUNT & PASSWORD // ------------

    static function getPasswordLinkParams($link) {

        $ret['block'] = false;
        $ret['link'] = KB_AUTH_RESTORE_PASSWORD_LINK;

        if(KB_AUTH_RESTORE_PASSWORD_LINK === false
                || _strtolower(KB_AUTH_RESTORE_PASSWORD_LINK) === 'off') {
            $ret['block'] = false;

        } else {

            // check if local auth used
            if(KB_AUTH_LOCAL) {
                $ret['block'] = true;
                $ret['link'] = $link;

                // we use local but IP dos not match = no local
                if(KB_AUTH_LOCAL_IP && !IPUtil::isValidQuick(KB_AUTH_LOCAL_IP)) {
                    $ret['block'] = false;
                }

            // no local and has link
            } elseif(KB_AUTH_RESTORE_PASSWORD_LINK) {
                $ret['block'] = true;
            }

        }

        return $ret;
    }


    static function isAccountUpdateable() {

        $ret = false;

        $kb_auth_update_account = 2; // automatic by default
        if(defined(KB_AUTH_UPDATE_ACCOUNT)) {
            $kb_auth_update_account = KB_AUTH_UPDATE_ACCOUNT;
        }

        if($kb_auth_update_account == 0) {
            $ret = false;

        } elseif($kb_auth_update_account == 1) {
            $ret = true;

        // automatic
        } elseif($kb_auth_update_account == 2) {

            // check if local auth used
            if(KB_AUTH_LOCAL) {
                $ret = true;

                // we use local but IP dos not match = no local
                if(KB_AUTH_LOCAL_IP && !IPUtil::isValidQuick(KB_AUTH_LOCAL_IP)) {
                    $ret = false;
                }
            }
        }

        return $ret;
    }


    // SCRIPT OR SETTINGS // ----------

    static function loadEnviroment($setting = array()) {

        $error = false;

        if(!isset($setting['remote_auth_script'])) {
            $setting = SettingModel::getQuick(array(160,163));
        }
        

        define('KB_AUTH_SCRIPT', $setting['remote_auth_script']);

        if($setting['remote_auth_script']) {

            $file = AuthRemote::getScriptPath($setting, 'remote_auth_script_path');
            if(!is_file($file) || !is_readable($file)) {

                $error = sprintf('File (%s) does not exists or it is not readable,
                skipping REMOTE authentication, proceed with LOCAL authentication...' , $file);
                $error = self::removeNewline($error);

            } else {
                require_once $file;
            }

        } else {

            // for all areas, disabled in UI
            // define('KB_AUTH_AREA', $setting['remote_auth_area']);
            define('KB_AUTH_AREA', 2);

            define('KB_AUTH_LOCAL', $setting['remote_auth_local']);
            define('KB_AUTH_LOCAL_IP', $setting['remote_auth_local_ip']);
            define('KB_AUTH_TYPE', 1);
            // define('KB_AUTH_TYPE', $setting['remote_auth_type']);
            define('KB_AUTH_REFRESH_TIME', $setting['remote_auth_refresh_time']);
            define('KB_AUTH_RESTORE_PASSWORD_LINK', $setting['remote_auth_restore_password_link']);
            define('KB_AUTH_UPDATE_ACCOUNT', $setting['remote_auth_update_account']);

            if(!empty($setting['remote_auth_auto'])) {

                $file = AuthRemote::getScriptPath($setting, 'remote_auth_auto_script_path');
                if(!is_file($file) || !is_readable($file)) {

                    $error = sprintf('File (%s) does not exists or it is not readable,
                    skipping AUTO authentication, proceed with REMOTE authentication...' , $file);
                    $error = self::removeNewline($error);

                } else {

                    define('KB_AUTH_AUTO', $setting['remote_auth_auto']);
                    require_once $file;
                }
            }
        }

        return $error;
    }


    static function getScriptPath($setting, $key) {

        $default_paths = array(
            'remote_auth_script_path'      => APP_LIB_DIR . 'custom/remote_auth.php',
            'remote_auth_auto_script_path' => APP_LIB_DIR . 'custom/remote_auth_auto.php',
        );

        $path = trim($setting[$key]);
        if(empty($path) || _strtolower($path) == 'default') {
            $path = $default_paths[$key];
        }

        return $path;
    }


    // data from database
    function remoteDoAuthSetting($username, $password) {

        $user = false;
        if(empty($username) || empty($password)) {
            return $user;
        }

        $setting = SettingModel::getQuick(array(160));

        try {

            $ldap = new AuthLdap($setting);

            $ldap->connect();
            $ldap->bind($setting['ldap_connect_dn'], $setting['ldap_connect_password']);

            $ldap_user = $ldap->searchUser($username);

            // if found
            if(!empty($ldap_user)) {
                $ldap->bind($ldap_user['dn'], $password);

                $this->user_ldap_token = $ldap->getUserToken($ldap_user);

                $user = array();
                if($this->auth_type == 1) {
                    $user = $ldap->getUserMapped($ldap_user);
                    $user['password'] = $password;
                    $user['username'] = $username;
                }
            }

        } catch (Exception $e) {
            $exitcode = 3;
            $this->putLog('LDAP error: ' . $e->getMessage());
            return $user;
        }

        return $user;
    }


    // AUTO AUTH // --------------------

    function autoAuth() {

        $user = false;

        $suffix = 'Auto';
        $this->putLog('Initializing AUTO authentication', $suffix);

        if(!function_exists('remoteAutoAuth')) {
            $msg = 'Function remoteAutoAuth does not exist, skipping AUTO authentication,
            proceed with REMOTE authentication...';
            $msg = $this->removeNewline($msg);
            $this->putLog($msg, $suffix);
            return false;
        }

        // do not check in debug
        if(KB_AUTH_AUTO == 1 && AuthRemote::isRemoteAutoAuth()) {
            $msg = 'AUTO authentication is locked, one "wrong" authentication try is allowed per session,
                    skipping AUTO authentication, proceed with REMOTE authentication...';
            $msg = $this->removeNewline($msg);
            $this->putLog($msg, $suffix);

        } else {

            $user = remoteAutoAuth();

            if($user === false) {
                $msg = 'AUTO authentication (remoteAutoAuth) failed (false returned)';

            } elseif(!is_array($user)) {
                $msg = 'AUTO authentication (remoteAutoAuth) failed, wrong parameters returned';
                $msg2 = 1;

            } elseif(is_array($user) && !isset($user['username']) || !isset($user['password'])) {
                $msg = 'AUTO authentication (remoteAutoAuth) failed, wrong parameters returned';
                $msg2 = 1;

            } else {
                $msg = 'Trying AUTO authentication';
            }

            $msg = preg_replace("#[\n\r\t]#", '', $msg);
            $this->putLog($msg, $suffix);

            if(!empty($msg2)) {
                $this->putLog('Returned values: ' . print_r($user, 1), $suffix);
            }

            AuthRemote::setRemoteAutoAuth(KB_AUTH_AREA);
        }

        return $user;
    }


    // LOGS // -----------------

    static function removeNewline($string) {
        return preg_replace(array("#[\n\r\t]#", "#\s{2,}#"), '', $string);
    }


    function putLog($msg, $prefix = 'Remote') {
        if(!empty($this->log)) {
            $this->log->putLogin($msg, $prefix);
        }
    }


    function addLog($user_id, $username, $type, $exitcode) {
        if(!empty($this->log)) {
            $this->log->AddLogin($user_id, $username, $type, $exitcode);
        }
    }

}
?>