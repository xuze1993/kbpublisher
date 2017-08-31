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

class PasswordUtil
{

    var $table = 'user';
    var $id_field = 'id';
    var $pass_field = 'password';
    var $login_field = 'username';
    var $email_field = 'email';
    var $ext_sql;
    var $temp_table = 'user_temp';
    var $temp_pass_field = false;
    var $db;


    function setExtSql($sql) {
        $this->ext_sql = $sql;
    }

    function getAllInfo($id) {
        $sql = "SELECT * FROM {$this->table}
        WHERE {$this->id_field} = '{$id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }

    function getUsername($id) {
        $sql = "SELECT {$this->login_field} FROM {$this->table}
        WHERE {$this->id_field} = '{$id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields($this->login_field);
    }

    function getEmail($id) {
        $sql = "SELECT {$this->email_field} FROM {$this->table}
        WHERE {$this->id_field} = '{$id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields($this->email_field);
    }

    // set new password, temporary by default
    function setPassword($id, $password, $username = false, $temp_password = false) {

        $temp_sql_arr = array();
        if($username) {
            $temp_sql_arr[] = "{$this->login_field} = '{$username}'";
        }
        if($this->temp_pass_field) {
            $temp_sql_arr[] = "{$this->temp_pass_field} = '{$temp_password}'";
        }

        $temp_sql = ($temp_sql_arr) ? implode(',', $temp_sql_arr) . ',' : '';

        $sql = "UPDATE {$this->table} SET
        {$temp_sql}
        {$this->pass_field} = '{$password}'
        WHERE {$this->id_field} = '{$id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $password;
    }


	function generatePassword($num_sign = 3, $num_int = 2) {
        return WebUtil::generatePassword($num_sign, $num_int);
	}


    // return id if password exists false otherwise
    function isPasswordExists($pass) {
        $sql = "SELECT {$this->id_field} FROM {$this->table}
        WHERE {$this->pass_field} = '{$pass}' {$this->ext_sql}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields($this->id_field);
    }


    // return id if email exists false otherwise
    function isEmailExists($email) {
        $sql = "SELECT {$this->id_field} FROM {$this->table}
        WHERE {$this->email_field} = '{$email}' {$this->ext_sql}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields($this->id_field);
    }


    // return id if username exists false otherwise
    function isUsernameExists($login) {
        $sql = "SELECT {$this->id_field} FROM {$this->table}
        WHERE {$this->login_field} = '{$login}' {$this->ext_sql}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields($this->id_field);
    }


    // return true if password is temporary false otherwise
    function isTempPassword($id) {
        $sql = "SELECT is_temp_password FROM {$this->table}
        WHERE {$this->id_field} = '{$id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('is_temp_password');
    }


    function setUserResetPassword($user_id, $reset_code, $user_ip = false) {

        if($user_ip === false) {
            $user_ip = WebUtil::getIP();
            $user_ip = ($user_ip == 'UNKNOWN') ? 0 :  $user_ip;
        }

        $sql = "REPLACE {$this->temp_table}
        SET rule_id = 1,
            user_id = '{$user_id}',
            user_ip = IFNULL(INET_ATON('{$user_ip}'), 0),
            value2 = '{$reset_code}',
            active = 1";
        $result = $this->db->Execute($sql) or die(db_error($sql));
    }


    function unsetUserResetPassword($user_id) {
        $sql = "UPDATE {$this->temp_table} SET active = 0
        WHERE rule_id = 1 AND user_id = '{$user_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
    }


    function getUserByResetPasswordCode($code, $min_interval) {
        $sql = "SELECT ut.user_id AS 'user_id'
        FROM {$this->temp_table} ut
        WHERE ut.rule_id = 1
            AND ut.value_timestamp > DATE_SUB(NOW(), INTERVAL {$min_interval} MINUTE)
            AND ut.value2 = '{$code}'
            AND ut.active = 1";

        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('user_id');
    }


    static function isWeakPassword($password) {
        
        $ret = false;
        
        $length = 8;
        $upper = '#[A-Z]#';  //Uppercase
        $lower = '#[a-z]#';  //lowercase
        $number = '#[0-9]#';  //numbers
        $special = '#~`!@\#%^&*()-_+={}[]|\;:<>,./?#';  // whatever you mean by 'special char'
        
        if(!preg_match($upper, $password)) {
            $ret = true;
            
        } elseif(!preg_match($lower, $password)) {
            $ret = true;
        
        } elseif(!preg_match($number, $password)) {
            $ret = true;

        // } elseif(!preg_match($special, $password)) {
            // $ret = true;

        } elseif(strlen($password) < $length) {
            $ret = true;
        }

        return $ret;
    }
}
?>