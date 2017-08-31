<?php
define('KB_AUTH_AREA', 2);
define('KB_AUTH_LOCAL', 0);
define('KB_AUTH_LOCAL_IP', NULL);
define('KB_AUTH_TYPE', 1);
define('KB_AUTH_REFRESH_TIME', 1); //3600*24*30
define('KB_AUTH_RESTORE_PASSWORD_LINK', '');


// require adLDAP
require_once 'custom/adLDAP/adLDAP.php';


function remoteDoAuth($username, $password) {
    
    $user = false;
    if(empty($username) || empty($password)) {
        return $user;
    }
    
    $options = array(
        'domain_controllers' => array('192.168.1.1')
    );

    try {
        $adldap = new adLDAP($options);

    } catch (adLDAPException $e) {
        // echo print_r($e, 1);
        echo 'Error connecting to LDAP!';
        exit();   
    }    
    
    
    // request for user
    $auth = $adldap->authenticate($username, $password);
    if(!$auth) {
        // echo sprintf("Unable to authenticate user: %s,  with password: %s \n", $username, $password);
        // exit();
        return $user;
    }
    
    
    $user_info = array('*');  // get all data
    // $user_info = array('uid', 'givenname', 'sn', 'mail'); // get specified data
    $ldap_user = $adldap->user_info($username, $user_info);
    
    // if found
    if(!empty($ldap_user[0])) { 
        $ldap_user = $ldap_user[0];
        
        $user = array();
        
        if(!empty($ldap_user['givenname'][0])) {
            $user['first_name'] = $ldap_user['givenname'][0];
        }
        
        if(!empty($ldap_user['sn'][0])) {
            $user['last_name'] = $ldap_user['sn'][0];
        }
        
        if(!empty($ldap_user['mail'][0])) {
            $user['email'] = $ldap_user['mail'][0];
        }
        
        if(!empty($ldap_user['uid'][0])) {
            $user['remote_user_id'] = $ldap_user['uid'][0];
        }
        
        $user['username'] = $username;
        $user['password'] = $password;
                
        
        // assign a priv to user (optional)
        // it is fully up to you how to determine who is authenticated and what priv to assign
        // $user['priv_id'] = 3;
        
        // assign a role to user (optional)
        // it is fully up to you how to determine who is authenticated and what role to assign
        // $user['role_id'] = 1;
    }
    
    // to debug, uncomment
    // echo '<pre>ldap_user: ', print_r($ldap_user, 1), '</pre>'; 
    // echo '<pre>user: ', print_r($user, 1), '</pre>'; 
    // exit;
    
    return $user;
}
?>