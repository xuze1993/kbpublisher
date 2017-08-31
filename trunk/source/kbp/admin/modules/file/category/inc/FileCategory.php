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

class FileCategory extends AppObj
{
    
    var $properties = array('id'             => NULL,
                            'parent_id'      => 0,
                            'name'           => '',
                            'description'    => '',
                            'attachable'     => 1,
                            //'browseable'    => '',
                            'sort_order'     => 'sort_end',
                            'private'        => 0,
                            // 'active_real' => 1,
                            'active'         => 1
                            );
    
    
    var $hidden = array('id');
    var $admin_user = array();
    var $role_read = array();
    var $role_write = array();
    
    
    function getAdminUser() {
        if(is_array($this->admin_user)) {
            return array_unique($this->admin_user);
        }
        
        return $this->admin_user;
    }
    
    function setAdminUser($user) {
        $this->admin_user = $user;
    }
    
    function getRoleRead() {
        return $this->role_read;
    }
    
    function setRoleRead($role) {
        $this->role_read = $role;
    }
    
    function getRoleWrite() {
        return $this->role_write;
    }
    
    function setRoleWrite($role) {
        $this->role_write = $role;
    }        
    
    function _callBack($property, $val) {
        
        // get private value from array
        if($property == 'private' && is_array($val)) {
            $val = PrivateEntry::getPrivateValue($val);
        }   
        
        return $val;
    }    
    
    function validate($values) {
        
        require_once 'eleontev/Validator.php';
        
        $required[] = 'name';
        
        // when user select wrong category
        if(!isset($values['parent_id'])) {
            $required[] = 'parent_id';
        
        // not to generate error if top category selected 
        } elseif($values['parent_id'] !== '0') {
            $required[] = 'parent_id';
        }
        
        $v = new Validator($values, false);

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
    }
    
}
?>