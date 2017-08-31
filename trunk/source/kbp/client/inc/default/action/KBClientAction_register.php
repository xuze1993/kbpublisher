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

class KBClientAction_register extends KBClientAction_common
{

    function &execute($controller, $manager) {


        // check if registration is not allowed
        if(!$manager->getSetting('register_policy')) { 
            $controller->go();
        }        
        
        // check if registered
        if($manager->is_registered) { 
            $controller->go();
        }
        
        
        $view = &$controller->getView('register');
        
        $view->update = false;
        
        $obj = new User;
        $manager_2 = new UserModel;
        $manager_2->use_priv = true;
        $manager_2->use_role = true;
        $manager_2->use_old_pass = false;
        
        
        if(isset($this->rp->submit)) {

            $errors = $view->validate($this->rp->vars, $manager, $manager_2);
            
            if($errors) {
                $this->rp->stripVars(true);
                $view->setErrors($errors);
                $view->setFormData($this->rp->vars);
            
            } else {
                
                $sent = $manager->sendConfirmRegistration($this->rp->vars, $view);
            
                if($sent) {
        
                    $this->rp->stripVars();
                    $obj->set($this->rp->vars);
                    $obj->set('id', NULL);
                    $obj->set('imported_user_id', NULL);
                    $obj->set('active', 3); // unconfirmed
                    $obj->setPassword();

                    // remove default news subscription
                    if (empty($this->rp->vars['subsc_news'])) {
                        $obj->setSubscription(NULL);
                    }
                    
                    $priv = $manager->getSetting('register_user_priv');
                    if($priv) {
                        $obj->setPriv($priv);
                    }
                    
                    $role = $manager->getSetting('register_user_role');
                    if($role) {
                        $obj->setRole($role);
                    }
                                
                    $manager_2->save($obj);
                    $controller->go('success_go', false, false, 'confirmation_sent');
                
                } else {
                    $this->rp->stripVars(true);
                    $view->setFormData($this->rp->vars);
                    $view->msg_id = 'confirmation_not_sent';
                }
            }
        }
        
        return $view;
    }
    
}
?>