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

// this is to reset/remind password

require_once 'eleontev/Validator.php';
require_once 'eleontev/Util/PasswordUtil.php';
require_once 'eleontev/Util/HashPassword.php';


class KBClientAction_password extends KBClientAction_common
{

    var $link_lifetime = 30; // mins
    

    function &execute($controller, $manager) {

        // check if login allowed, disable as also used by admin area
        // if($manager->getSetting('login_policy') == 9) {
            // $controller->go();
        // }
        
        if($manager->is_registered) {
            $controller->go();
        }
        
        
        $action = $controller->msg_id;
        
        if($action == 'reset') {            
            $view = $this->reset($controller, $manager);
        
        } else {
            $view = $this->set($controller, $manager);
        
        }
        
        return $view;
    }       
    
    
    function set($controller, $manager) {
        
        $view = &$controller->getView('password');
       
        if(isset($this->rp->submit)) {

            $pass = new PasswordUtil;
            $pass->db =& $manager->db;
            $pass->table = $manager->tbl->user;
            $pass->temp_table = $manager->tbl->user_temp;
            $pass->setExtSql('AND active = 1');
            $email = addslashes(stripslashes($this->rp->vars['email']));

            $errors = $view->validate($this->rp->vars, $manager);

            if($errors) {
                $this->rp->stripVars(true);
                $view->setErrors($errors);
                $view->setFormData($this->rp->vars);

            } elseif(!$user_id = $pass->isEmailExists($email)) {

                $v = new Validator($this->rp->vars, false);
                $v->setError('email_not_exists_msg', '1');  
                $errors = $v->getErrors();

                $this->rp->stripVars(true);
                $view->setErrors($errors);
                $view->setFormData($this->rp->vars);

            } else {

                $reset_code = $pass->generatePassword(4, 4);
                $pass->setUserResetPassword($user_id, $reset_code);

                $more = array('rc' => $reset_code);
                $link = $controller->getFolowLink('password', false, false, 'reset', $more);
                $user = $manager->getUserInfo($user_id);

                $sent = $manager->sendResetPasswordLink($user, $reset_code, $link);

                if($sent) {
                    $controller->go('success_go', false, false, 'password_reset_sent');

                } else {
                    $this->rp->stripVars(true);
                    $view->setFormData($this->rp->vars);
                    $view->msg_id = 'password_reset_not_sent';
                }
            }
        }

        return $view;
    } 
    
    
    function reset($controller, $manager) {
        
        if(empty($this->rq->rc)) {
            $controller->goStatusHeader('404');
        }
        
        $view = &$controller->getView('password_reset');
        
        
        if(isset($this->rp->submit)) {
            
            $pass = new PasswordUtil;
            $pass->db =& $manager->db;
            $pass->table = $manager->tbl->user;
            $pass->temp_table = $manager->tbl->user_temp;

            $errors = $view->validate($this->rp->vars, $manager);
                        
            if($errors) {
                $this->rp->stripVars(true);
                $view->setErrors($errors);
                $view->setFormData($this->rp->vars);
            
            } else {
                
                $reset_code = addslashes($this->rq->rc);
                $reset_min = $this->link_lifetime;
                
                $user_id = $pass->getUserByResetPasswordCode($reset_code, $reset_min);
                
                if(!$user_id) {
                    $controller->go('password', false, false, 'password_reset_error');
                
                } else {

                    $this->rp->stripVars();
                    
                    $password = HashPassword::getHash($this->rp->password);
                    $pass->setPassword($user_id, $password);
                    $pass->unsetUserResetPassword($user_id);

                    $controller->go('success_go', false, false, 'password_reset_success');
                }
            }
        }
        
        return $view;     
    }
}
?>