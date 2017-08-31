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

require_once 'eleontev/Item/Person.php';


class User extends Person
{
     
    var $properties = array('id'         =>NULL,
                            'grantor_id' => 0,
                            'imported_user_id'  =>NULL,
                            'company_id' => 0,
                            'first_name' => '',
                            'middle_name'=> '', 
                            'last_name'  => '',
                            'email'      => '',
                            'phone'      => '',
                            'username'   => '',
                            'password'   => '',
                            'date_registered' => '',
                            'active'     => 1,
                            
                            'lastauth'      =>NULL,
                            'user_comment'  => '',
                            'admin_comment' => '',
                            
                            'phone_ext'     => '',
                            'address'       => '',
                            'address2'      => '',
                            'city'          => '',
                            'state'         => '',
                            'zip'           => '',
                            'country'       => 0
                           );
                                              
    var $hidden = array('id', 'date_registered', 'lastauth', 'grantor_id', 'imported_user_id');
           
    var $priv = array();
    var $role = array();
    var $subscription = array(3); // news by default
    var $extra_data = array();
    
    // map, data fields to extra table and default values 
    var $extra_data_default = array(
        1 => array(
            'value1' => array('api_access', 0),
            'value2' => array('api_public_key', false),
            'value3' => array('api_private_key', false),
            )
        ) ;
        
    var $more_info = false;
    
    
    // remove password from obj, not to replace in db
    // or set hashed password to keep in db
    function setPassword($skip_password = false) {
        if($skip_password) { 
            unset($this->properties['password']);// mean not insert in db
        } else {
            // password escaped here, with slashes 
            $password = $this->get('password');
            $this->set('password', HashPassword::getHash($password));
        }
    }

    function setPriv($values) {
        $this->priv = $values;
    }
    
    function &getPriv() {
        return $this->priv;
    }
    
    function setRole($values) {
        $this->role = $values;
    }
    
    function &getRole() {
        return $this->role;
    }
        
    function setSubscription($values) {
        $this->subscription = $values;
    }
    
    function getSubscription() {
        return $this->subscription;
    }
    
    function setExtra($values) {
        
        // foreach(array_keys($values) as $rule_id) {
        //     $ret = array_filter($values[$rule_id]);
        //     if(!$ret) {
        //         unset($values[$rule_id]);
        //     }
        // }
        
        $this->extra_data = $values;
    }
    
    function getExtra() {
        return $this->extra_data;
    }
    
    // used in form, to populate fields
    function getExtraValues($rule_id) {
        $values = array();
        $data = $this->getExtra();
        foreach($this->extra_data_default[$rule_id] as $k => $v) {
            $values[$v[0]] = isset($data[$rule_id][$k]) ? $data[$rule_id][$k] :  $v[1];
        }
        
        return $values;
    }
    
    // some formating goes here
    function _callBack($property, $val) {
        
        if($property == 'first_name' || $property == 'last_name' || $property == 'middle_name') {
            $val = ucfirst($val);
        
        } elseif($property == 'date_registered' && !$val) {
            $val = date('Y-m-d H:i:s');
        
        } elseif($property == 'imported_user_id' && !$val) {
            $val = NULL;
        
        } elseif($property == 'lastauth' && !$val) {
            $val = NULL;
        
        } elseif($property == 'grantor_id') {
            $val = (int) $val;
        }
        
        return $val;
    }
    
    
    function setGrantor() {
        if($this->get('grantor_id') == 0) {
            $this->set('grantor_id', AuthPriv::getUserId());
        }
    }
    
    
    function validate($values, $manager, $more_required = array()) {
        
        require_once 'eleontev/Validator.php';
        require_once 'eleontev/Util/PasswordUtil.php';
        
        $required = array_merge(array('first_name', 'last_name', 'email', 'username'), $more_required);
        
        $pass = false;
        if(empty($values['not_change_pass'])) {
            $pass = true;
            $required[] = 'password';            
        }
        
        $v = new Validator($values);

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
        
        
        // user id
        $user_id = (isset($values['id'])) ? intval($values['id']) : false;
        
        // we have such username
        $username = addslashes(stripslashes($values['username']));
        if($manager->isUsernameExists($username,  $user_id)) {
            $v->setError('username_exists_msg', 'username');
        }
        
        // we have such email
        $email = addslashes(stripslashes($values['email']));
        if($manager->isEmailExists($email, $user_id)) {
            $v->setError('email_exists_msg', 'email');
        }        
        
        
        $v->regex('email_msg', 'email', 'email');
        
        // password
        if($pass) {
            if($ret = PasswordUtil::isWeakPassword($values['password'])) {
                $v->setError('pass_weak_msg', 'password');
            }
            
            $v->compare('pass_diff_msg', 'password_2', 'password');
        }

        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
    }
    
    
    function validatePassword($values, $manager) {
        
        require_once 'eleontev/Validator.php';
        require_once 'eleontev/Util/PasswordUtil.php';
        
        $required = array('password');
        
        if($manager->use_old_pass) {
            $required[] = 'password_old';
        }
        
        $v = new Validator($values);

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
        
        // password
        if(PasswordUtil::isWeakPassword($values['password'])) {
            $v->setError('pass_weak_msg', 'password');
        }
        
        $v->compare('pass_diff_msg', 'password', 'password_2');
        
        $hashed_stored_password = $manager->getPassword($values['id']);

        if($manager->use_old_pass) {
            
            $old_password = $values['password_old'];
            
            // pass saved with addslashes
            if(!get_magic_quotes_gpc()) {
                $old_password = addslashes($old_password);
            }
            
            $ret = HashPassword::validate($old_password, $hashed_stored_password);
            
            if(!$ret) {
                $v->setError('old_pass_diff_msg', 'password_old');
            }
        }
        
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
        
        $this->pass_changed = !HashPassword::validate($values['password'], $hashed_stored_password);
        
        if (AuthPriv::getPassExpired() && (AuthPriv::getUserId() == $values['id']) && !$this->pass_changed) {
            $v->setError('pass_match_old_msg', 'password', 'password');
        }
        
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
    }
    
    
    function getValidatePassword($values) {
        $ret = array();
        $ret['func'] = array($this, 'validatePassword');
        $ret['options'] = array($values, 'manager');
        return $ret;
    }
    
    
    function validateApiKeys($values) {
        
        require_once 'eleontev/Validator.php';
        
        $required = array('api_public_key', 'api_private_key'); // 
        
        
        $v = new Validator($values);

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
        
        // public key        
        if(_strlen($values['api_public_key']) < 32) {
            $v->setError('value_short_msg', 'api_public_key');
        }
        
        // private key
        if(_strlen($values['api_private_key']) < 32) {
            $v->setError('value_short_msg', 'api_private_key');
        }
        
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
    }
    
    
    function getValidateApiKeys($values) {
        $ret = array();
        $ret['func'] = array($this, 'validateApiKeys');
        
        $this->setExtra($values['extra']);
        $api_data = $this->getExtraValues(1);
        
        $ret['options'] = array($api_data, 'manager');
        return $ret;
    }
}
?>