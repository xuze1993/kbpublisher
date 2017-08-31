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

require_once 'eleontev/Util/HashPassword.php';


class Auth extends AppModel
{

    var $sql;
    var $cookie_expire = '2 week';
    var $check_ip = true;
    var $user_status = 1; // if false we do not check any status
    
    
    static function factory($auth) {
        
        $class = 'Auth' . $auth;
        $file  = 'Auth' . $auth . '.php';
        
        require_once 'eleontev/Auth/' . $file;
        return new $class;
    }
    
    
    function setTable($table) {
        $this->tbl->table = $table;
    }    
    
    
    function setSql($sql) {
        $this->sql = $sql;
    }

    
    function setCheckIp($check_ip) {
        $this->check_ip = $check_ip;
    }    
    
    
    function setUserStatus($status) {
        $this->user_status = $status;
    }
    
    
    function setLastAuth($user_id, $date) {
        $sql = "UPDATE {$this->tbl->user} SET lastauth = '%d', 
        date_updated=date_updated WHERE id = '%d'";
        $sql = sprintf($sql, $date, $user_id);
        $this->db->Execute($sql) or die(db_error($sql));    
    }
    
    
    function isPasswordExpiered($user_id, $days) {
        $sql = "SELECT lastpass FROM {$this->tbl->user} 
        WHERE id = %d 
        AND DATEDIFF(NOW(), FROM_UNIXTIME(lastpass)) > %d";
        $sql = sprintf($sql, $user_id, $days);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('lastpass');
    }
    
    
    function _getAuth_old($username, $password, $md5 = true) {
        
        $auth = false;
        
        if (!empty($username) && !empty($password)) {

            $sql = "
            SELECT 
                u.id AS user_id, 
                u.username
            FROM 
                {$this->tbl->user} u
            WHERE 1
                AND u.username = '%s'
                AND u.password = '%s'
                AND u.active = 1";
            
            // addslashes added when registered, md5(addslashes($password)); 
            $password = addslashes(stripslashes($password)); 
            $password = ($md5) ? MD5($password) : $password;
            $username = addslashes(stripslashes($username));

            $sql = sprintf($sql, $username, $password);
            $result = $this->db->Execute($sql) or die(db_error($sql));
            
            if($result->RecordCount() == 1) { 
                $auth = $result->FetchRow();
            }
        }

        return $auth;
    }
    
    
    function _getAuth($username, $password, $md5 = true) {

        $auth = false;

        if (!empty($username) && !empty($password)) {

            $sql = "
            SELECT 
                u.id AS user_id, 
                u.password AS hashed_password,
                u.username
            FROM 
                {$this->tbl->user} u
            WHERE 1
                AND u.username = '%s'
                AND u.active = 1";

            // this come from different sources and from database
            // so we need addslashes here, it always should work 
            // the problem coul be if \ (backslash) used in username
            $username_escaped = addslashes(stripslashes($username));

            // need it here because "hashed_password" was generated with slashes
            // the problem coul be if \ (backslash) used in password
            $password_escaped = addslashes(stripslashes($password));

            $sql = sprintf($sql, $username_escaped);
            $result = $this->db->Execute($sql) or die(db_error($sql));

            if($result->RecordCount() == 1) {
                $row = $result->FetchRow();

                // migrate password
                if(strlen($row['hashed_password']) == 32) {
                    $auth = $this->_getAuth_old($username, $password, $md5);
                    if($auth) {
                        $hash = HashPassword::getHash($password_escaped);
                        $this->updateUserPassword($row['user_id'], $hash);
                    }
                    
                    return $auth;
                }
                
                // need to encode, in prev implementaion it was md5
                if($md5) {
                    $ret = HashPassword::validate($password_escaped, $row['hashed_password']);
                
                // goes from db, encoded
                } else {
                    $ret = ($password_escaped == $row['hashed_password']);
                }
                
                if($ret) {
                    $auth = $row;
                }
            }
        }

        return $auth;
    }    
    
    
    static function _isAuth($as_name, $check_ip = true) {
        $ret = true;
        $ip = ($check_ip) ? Auth::getIP() : '';
        
        if(@$_SESSION[$as_name]['auth'] != md5(@$_SESSION[$as_name]['thua'] .
                                               @$_SESSION[$as_name]['user_id'] . 
                                               @$_SESSION[$as_name]['username'] .
                                               $ip) 
           || @!$_SESSION[$as_name]['auth'] || @!$_SESSION[$as_name]['thua']){
        
            $ret = false;
        }
        
        return $ret;
    }
    
    
    function authToSession($name, $user_id, $username, $hash = false) {
        $ip = ($this->check_ip) ? Auth::getIP() : '';
        // $session_id = ($hash === false) ? session_id() : $hash; 
        
        if($hash === false) {
            session_regenerate_id(true);
            $hash = session_id(); 
        }
        
        $_SESSION[$name]['auth'] = md5($hash . $user_id . $username . $ip);
        $_SESSION[$name]['thua'] = $hash;
        $_SESSION[$name]['user_id'] = $user_id;
        $_SESSION[$name]['username'] = $username;
        $_SESSION[$name]['time_flag'] = time();
        
        // rotation
        require_once APP_MODULE_DIR . 'user/user/inc/UserModel.php';
        $manager = new UserModel;
        
        $user = $manager->getById($user_id);
        $_SESSION[$name]['pass_updated'] = $user['lastpass'];
    }
    
    
    function authToSessionApi($user_id, $username, $hash) {
        $this->authToSession($this->as_name, $user_id, $username, $hash);
    }
    
    
    // TOKEN AUTH (remember) // 
    
    function saveRememberAuth($id, $user_id, $token, $remote_token, $date_expired) {
        $sql = "REPLACE {$this->tbl->user_auth_token} SET 
            id = {$id},
            user_id = '{$user_id}',
            token = '{$token}',
            remote_token = '{$remote_token}',
            date_expired = '{$date_expired}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $this->db->Insert_ID();
    }
    
    
    function getRememberAuth($selector) {
        $sql = "SELECT a.*, u.username, u.imported_user_id as 'ruid'
            FROM {$this->tbl->user_auth_token} a, {$this->tbl->user} u 
            WHERE  a.id = '{$selector}'
            AND a.date_expired >= CURDATE()
            AND a.user_id = u.id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }

    
    function deleteRememberAuth($selector) {
        $sql = "DELETE FROM {$this->tbl->user_auth_token} WHERE  id = '{$selector}'";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function isValidRememberAuth($selector, $validator) {
        
        $ret = false;
        $row = $this->getRememberAuth($selector);
        
        if($row && HashPassword::validate($validator, $row['token'])) {
            $ret = $row;
        }
    
        return $ret;
    }
    
    
    function setRememberAuth($user_id, $remote_token, $auth_id = false) {
        
        $selector = WebUtil::generatePassword(5, 2); // 12 length
        $token = HashPassword::getHash($selector);

        $timestamp = strtotime('+ 14 days');
        $expired = date('Y-m-d', $timestamp);

        $auth_id = ($auth_id) ? $auth_id : 'NULL';
        $id = $this->saveRememberAuth($auth_id, $user_id, $token, $remote_token, $expired);
        $cookie = sprintf('%s:%s',$id, $selector);

        $this->authToCookie($timestamp, $cookie);
    }
    
    
    // COOKIE //-------------------------
    
    static function authToCookieByName($name, $period = false, $data = false, $path = '/') {
        //setcookie ( string name [, string value [, int expire [, string path [, string domain [, int secure]]]]] )
        setcookie($name, $data, self::getCookieTime($period), $path);
    }
        
    
    static function removeCookieByName($name) {
        self::authToCookieByName($name);
        unset($_COOKIE[$name]);
    }    
    
    
    // period - 1 hour, 5 days, 8 months, 2 years ...
    static function getCookieTime($period = false, $sign = '+') {
        // return ($period) ? strtotime($sign . $period) : time();
        return ($period) ? (is_numeric($period)) ? $period : strtotime($sign . $period) : time();
    }

    
    function setCookieExpire($period = false) {
        $this->cookie_expire = self::getCookieTime($period);
    }    
    
    
    function _getUserByValue($values) {
        $where_sql = ModifySql::_getWhereSql($values, array_keys($values));
        $sql = "SELECT * FROM {$this->tbl->user} WHERE {$where_sql}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        if($result->RecordCount() == 1) {
            return $result->FetchRow();
        }
        
        return false;
    }
    
    
    function doAuthByValue($values, $md5 = false) {
        $user = $this->_getUserByValue($values);
        if($user) {
            return $this->doAuth($user['username'], $user['password'], $md5);
        }    
        
        return false;
    }
    
    
    function getAuthByValue($values, $md5 = false) {
        $user = $this->_getUserByValue($values);
        if($user) {
            return $this->_getAuth($user['username'], $user['password'], $md5);
        }    
        
        return false;        
    }
    
    
    function updateUserPassword($user_id, $password) {
        $sql = "UPDATE {$this->tbl->user} SET password = '%s', date_updated = date_updated WHERE id = '%d'";
        $sql = sprintf($sql, $password, $user_id);
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    // ROLES //-------------------------
    
    function _getUserRole($user_id) {
        $sql = "SELECT role_id FROM {$this->tbl->user_to_role} WHERE user_id = '%d'";
        $sql = sprintf($sql, $user_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetArray();
    }
    
    
    function roleToSession($name, $data) {
        foreach($data as $k => $v) {
            $_SESSION[$name]['role_id'][] = $v['role_id'];
        } 
    }    
    
    
    // OTHER // ------------------------------
    
    static function getIP() {
        return WebUtil::getIP();
    }
    
}
?>