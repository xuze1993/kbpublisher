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

require_once 'eleontev/Auth/AuthRemoteModel.php';


class AuthSaml extends AuthPriv
{
    
    var $log;
    
    var $algorithms = array(
        'rsa-sha1' => 'http://www.w3.org/2000/09/xmldsig#rsa-sha1',
        'dsa-sha1' => 'http://www.w3.org/2000/09/xmldsig#dsa-sha1',
        'rsa-sha256' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
        'rsa-sha384' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha384',
        'rsa-sha512' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha512'
    );
    
        
    public function __construct($options) {
        $this->validateOptions($options);
        $this->options = $options;
        
        if (empty($this->options['saml_map_remote_id'])) {
            $this->options['saml_map_remote_id'] = 'NameID';
        }
        
        $this->umanager = new AuthRemoteModel;
        parent::__construct();
    }
     
    
    static function getOneLogin($setings, $binding) {
        
        $auth = new AuthSaml($setings);
        $saml_settings = $auth->getSettings();
        
        $class = ($binding == 'redirect') ? 'OneLogin_Saml2_Auth' : 'OneLogin_Saml2_Auth_Post';
        
        // initiating a request
        return new $class($saml_settings);
    }
        
    
    static function validateOptions($options) {
        $required = array(
            'saml_sso_endpoint', 'saml_map_fname',
            'saml_map_lname', 'saml_map_email'
        );
        
        foreach($required as $option) {
            if(empty($options[$option])) {
                throw new Exception("Option {$option} can't be empty.");
            }    
        }
    }
    
    
    // by email
    function doAuthSaml2($user) {
        
        $kb_user = $this->umanager->isUserByEmail($user['email']);
        
        if($kb_user) {
            $msg = 'Remote User exists within KBPublisher database (identified by email), kbp_user_id: %d, email: %s';
            $this->putLog(sprintf($msg, $kb_user['id'], $user['email']));
        
            // in case we have 2 or more users with the same email
            if($this->umanager->isUserByEmail($user['email'], $kb_user['id'])) {
                echo $this->parseEmailExistsMsg($user);
                exit;
            }
        
            $user['id'] = $kb_user['id'];
            $spent = time() - $kb_user['lastauth'];
        
            // rewrite user
            if(!empty($this->options['saml_refresh_time'])) {
                if($spent > intval($this->options['saml_refresh_time'])) {
                    
                    $user['password'] = WebUtil::generatePassword(3,2);
                    
                    // renaming if already in use
                    if($this->umanager->isUserByUsername($user['username'], $user['id'])) {
                        $user['username'] = 'kb_' . $user['username'];
                    }

                    $user['date_registered'] = $kb_user['date_registered'];                    
                
                    $this->umanager->saveUser($user);
                    $this->putLog('User was successfully re-written (synchronized)');
                }
            }
        
            // get kb user and auth
            $user = $this->umanager->getUserById($user['id']);
            $auth = parent::doAuth($user['username'], $user['password'], false);
    
    
        // save first time
        } else {
                        
            $user['password'] = WebUtil::generatePassword(3,2);
            
            // renaming if already in use
            if($this->umanager->isUserByUsername($user['username'])) {
                $user['username'] = 'kb_' . $user['username'];
            }
           
            $user['date_registered'] = NULL;
                
            $user_msg = 'Remote User does not exist within KBPublisher database, synchronizing...';
            $add_msg = 'User was successfully added to KBPublisher database';
            
            $this->putLog($user_msg);
            $this->umanager->saveUser($user);
            $this->putLog($add_msg);
        
            $auth = AuthPriv::doAuth($user['username'], $user['password']);
        }
        
        if($auth) {
            $_SESSION[$this->as_name]['saml'] = 1;
        }
        
        return $auth;
    }
    
    
    function doAuthSaml($user) {
        
        $kb_user = $this->umanager->isUserByRemoteId($user['remote_user_id']);
        
        if($kb_user) {
            $msg = 'Remote User exists within KBPublisher database, kbp_user_id: %d, remote_user_id: %s';
            $this->putLog(sprintf($msg, $kb_user['id'], $user['remote_user_id']));
        
            $user['id'] = $kb_user['id'];
            $spent = time() - $kb_user['lastauth'];
        
            // rewrite user
            if(!empty($this->options['saml_refresh_time'])) {
                if($spent > intval($this->options['saml_refresh_time'])) {
                    
                    $user['password'] = WebUtil::generatePassword(3,2);
                    
                    // renaming if already in use
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
            
            if($kb_user = $this->umanager->isUserByEmail($user['email'])) {
                $exit = true;
                
                // so we assume it is the same user but never logged from remote auth
                if(!$this->umanager->isUserImported($kb_user['id'])) {
                    $exit = false;                        
                }

                if($exit) {
                    echo $this->parseEmailExistsMsg($user);
                    exit;
                }
            }
                        
            $user['password'] = WebUtil::generatePassword(3,2);
            
            $kb_user_id = ($kb_user) ? $kb_user['id'] : false;
            // renaming if already in use
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
        
            $auth = AuthPriv::doAuth($user['username'], $user['password']);
        }
        
        if($auth) {
            $_SESSION[$this->as_name]['saml'] = 1;
        }
        
        return $auth;
    }
    
    
    function parseEmailExistsMsg($user) {
        $msg = AppMsg::afterActionMsg('wrong_remote_email_exists');
        $this->putLog(implode(' - ', $msg));
        $this->putLog('Returned values: ' . print_r($user, 1));
        
        $exitcode = 3;
        $username = (isset($user['username'])) ? addslashes($user['username']) : '';
        $this->putLog(sprintf('Exit with the code: %d', $exitcode));                
        $this->addLog(0, $username, 8, $exitcode);
        
        // we need this message to avoid many questions ...
        return AppMsg::afterActionBox('wrong_remote_email_exists');        
    }
    
    
    public function getSettings() {
        $settings = array(
            'strict' => false,
            //'debug' => true,
            'sp' => self::getSPSettings(),
            'idp' => array (
                'entityId' => $this->options['saml_issuer'],
                'singleSignOnService' => array (
                    'url' => $this->options['saml_sso_endpoint'],
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ),
                'x509cert' => $this->options['saml_idp_certificate']
            ),
            'security' => array(
                'wantNameId' => false
            )
        );
        
        if ($this->options['saml_algorithm'] != 'off') {
            $settings['security']['authnRequestsSigned'] = true;
            $settings['security']['logoutRequestSigned'] = true;
            $settings['security']['logoutResponseSigned'] = true;
            $settings['security']['signatureAlgorithm'] = $this->algorithms[$this->options['saml_algorithm']];
        }
        
        
        if (!empty($this->options['saml_slo_endpoint'])) {
            $settings['idp']['singleLogoutService'] = array (
                'url' => $this->options['saml_slo_endpoint'],
                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            );
        }
        
        if (!empty($this->options['saml_sp_certificate'])) {
            $settings['sp']['x509cert'] = $this->options['saml_sp_certificate']; 
            $settings['sp']['privateKey'] = $this->options['saml_sp_private_key'];
        }
        
        return $settings;
    }
    
    
    static function getSPSettings() {
        
        $reg = &Registry::instance();
        $conf = &$reg->getEntry('conf');
        
        $kb_path = APP_CLIENT_PATH;
        
        $ssl_client_custom = KBClientController::getCustomSsl($conf);
        if($ssl_client_custom) {
            $kb_path = str_replace('http://', 'https://', $kb_path);
        }
        
        $settings = array(
            'entityId' => $kb_path . 'endpoint.php',
            'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
            'x509cert' => '',
            'privateKey' => '',
            'assertionConsumerService' => array(
                'url' => $kb_path . 'endpoint.php?type=acs',
                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST'
            ),
            'singleLogoutService' => array(
                'url' => $kb_path . 'endpoint.php?type=sls',
                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect'
            )
        );
        
        return $settings;
    }
    
    
    public function getUserMapped($name_id, $attributes) {
        
        $user = array();
        
        $required_attrs = array(
            'first_name' => $this->options['saml_map_fname'],
            'last_name' => $this->options['saml_map_lname'],
            'email' => $this->options['saml_map_email']
        );
        
		// username
        if (empty($this->options['saml_map_username']) || $this->options['saml_map_username'] == 'NameID') {
            $user['username'] = $name_id;
        } else {
            $required_attrs['username'] = $this->options['saml_map_username'];
        }
		
		// remote_id
        if (empty($this->options['saml_map_remote_id']) || $this->options['saml_map_remote_id'] == 'NameID') {
            $user['remote_user_id'] = $name_id;
        } else {
            $required_attrs['remote_user_id'] = $this->options['saml_map_remote_id'];
        }
        
        $_attributes = array_change_key_case($attributes, CASE_LOWER);
        foreach ($required_attrs as $k => $v) {
            $_v = strtolower($v);
			
			$search = false;
			if(strpos($v, '|') !== false) { // regexp should be applied
				list($attr, $search) = explode('|', $v);
				$_v = strtolower($attr);
			}
			
            if (empty($_attributes[$_v]) || empty($_attributes[$_v][0])) {
                $msg = "Unable to fetch user's %s - it cannot be empty, given attribute '%s' is empty or does not exist.";
                throw new Exception(sprintf($msg, $k, $v));
            
			} elseif($search) {
                if (!preg_match($search, $_attributes[$_v][0], $matches)) {
                    throw new Exception("Unable split the user's $k name from the attribute $_v.");
                }
				
				$user[$k] = $matches[1];
			   
            } else {
                $user[$k] = $_attributes[$_v][0];
            }
        }
        
        // group mapping
        $credentials = array('priv', 'role');
        
        foreach ($credentials as $key) {
            
            $kbp_field = sprintf('%s_id', $key);
            $user[$kbp_field] = 'off';
            
            $group_rules_key = sprintf('saml_map_group_to_%s', $key);
            
            if (!empty($this->options[$group_rules_key])) {
                $user[$kbp_field] = 0;
                                
                $rules = explode("\n", $this->options[$group_rules_key]);
                foreach ($rules as $rule) {
                    $r = explode('|', trim($rule));
                    if (count($r) != 3) {
                        throw new Exception("Bad format for the '$key' field.");
                    }
                    
                    $saml_attr_name = $r[0];
                    $saml_attr_value = $r[1];
                    $kbp_param = $r[2];
                    
                    if (!empty($attributes[$saml_attr_name]) && in_array($saml_attr_value, $attributes[$saml_attr_name])) {
                        $user[$kbp_field] = $kbp_param;
                        break;
                    }
                }
                
            }
        }
        
        return $user;
    }
    
    
    static function isAccountUpdateable($settings) {
        
        if ($settings['saml_update_account'] == 2) { // automatic
            $ret = ($settings['saml_mode'] == 1); // 1 = allow form
            
        } else {
            $ret = ($settings['saml_update_account']);
        }
        
        return $ret;
    }
    
    
    
    // LOGS // -----------------
    
    static function removeNewline($string) {
        return preg_replace(array("#[\n\r\t]#", "#\s{2,}#"), '', $string);
    }
    
    
    function putLog($msg, $prefix = 'SAML') {
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