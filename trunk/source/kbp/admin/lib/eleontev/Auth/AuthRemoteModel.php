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


class AuthRemoteModel extends AppModel
{
    
    var $tables = array(
        'user', 'user_role', 'priv_name'
    );
        
    
    function isUserByRemoteId($remote_user_id) {
        $sql = "SELECT id, lastauth, date_registered FROM {$this->tbl->user} WHERE imported_user_id = '%s'";
        $sql = sprintf($sql, $remote_user_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    
    
    function isUserByUsername($username, $user_id = false) {
        $param = ($user_id) ? sprintf('id != %d', $user_id) : 1;
        $sql = "SELECT id FROM {$this->tbl->user} WHERE username = '%s' AND {$param}";
        $sql = sprintf($sql, $username);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('id');
    }
    
    
    function isUserByEmail($email, $user_id = false) {
        $param = ($user_id) ? sprintf('id != %d', $user_id) : 1;
        $sql = "SELECT id, date_registered FROM {$this->tbl->user} WHERE email = '%s' AND {$param}";
        $sql = sprintf($sql, $email);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    
    
    function isUserImported($user_id) {
        $sql = "SELECT id FROM {$this->tbl->user} WHERE id = '%d' AND imported_user_id IS NOT NULL";
        $sql = sprintf($sql, $user_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('id');
    }
    
    
    function getUserByRemoteId($remote_user_id) {
        $sql = "SELECT username, password FROM {$this->tbl->user} WHERE imported_user_id = '%s'";
        $sql = sprintf($sql, $remote_user_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    
    
    function getUserById($user_id) {
        $sql = "SELECT username, password FROM {$this->tbl->user} WHERE id = %d";
        $sql = sprintf($sql, $user_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    
    
    /**
    * getRoles -- get all available roles
    *
    * @return   array
    * @access   public
    */    
    function getRoles() {
    
        $sql = "
        SELECT 
            r.id,
            r.id AS role_id,
            r.title AS role
        FROM 
            {$this->tbl->user_role} r";
        
        $sql = sprintf($sql, $user_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    /**
    * getPrivileges -- get all available privileges
    *
    * @return   array
    * @access   public
    */        
    function getPrivileges() {
    
        $sql = "
        SELECT 
            n.name AS priv_name,
            n.id AS priv_id
        FROM 
            {$this->tbl->priv_name} n
        WHERE 1
            #AND n.active = 1
            ";
        
        $sql = sprintf($sql, $user_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function saveUser($user) {

        require_once 'core/base/BaseObj.php';
        require_once 'core/app/AppObj.php';
        
        $dir = APP_MODULE_DIR . 'user/user/inc/';
        require_once $dir . 'User.php';
        require_once $dir . 'UserModel.php';
        
        
        $manager = new UserModel();
        
        $manager->use_priv = true;
        if(isset($user['priv_id']) && strtolower($user['priv_id']) == 'off') {
            $manager->use_priv = false;
        }
        
        $manager->use_role = true;
        if(isset($user['role_id']) && strtolower($user['role_id']) == 'off') {
            $manager->use_role = false;
        }
        
        $obj = new User();
        $obj->set($user);
        $obj->setPassword();
        $obj->set('imported_user_id', $user['remote_user_id']);
        $obj->set('active', 1);
        
        if(!empty($user['priv_id'])) {
            $obj->setPriv($user['priv_id']);
        }
        
        if(!empty($user['role_id'])) {
            if(!is_array($user['role_id'])) {
                $user['role_id'] = explode(',', $user['role_id']);
            }
            $obj->setRole($user['role_id']);
        }
        
        //echo "<pre>"; print_r($obj); echo "</pre>";
        //exit;
            
        return $manager->save($obj);
    }
    
}