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

require_once 'core/base/BaseObj.php';
require_once 'core/app/AppObj.php';
require_once 'core/app/AppView.php';

require_once APP_MODULE_DIR . 'user/user/inc/User.php';
require_once APP_MODULE_DIR . 'user/user/inc/UserModel.php';
require_once APP_MODULE_DIR . 'user/user/inc/UserView_detail.php';
require_once APP_MODULE_DIR . 'user/user/inc/UserView_api.php';


class KBClientAction_member_account extends KBClientAction_common
{

    function &execute($controller, $manager) {
        
        $action = $controller->msg_id;
        
        $controller->loadClass('member');
        
        if($action == 'update') {
            $view = &$controller->getView('member_update');
            $view->update = true;
            
        } elseif($action == 'password') {
            $view = &$controller->getView('member_password');
            
        } elseif($action == 'api') {
            $view = &$controller->getView('member_api');
        
        } else {
            $view = &$controller->getView('member_account');     
        }
        
        
        $reg =& Registry::instance();
        $conf = $reg->getEntry('conf');        
        
        $obj_2 = new User;
        $manager_2 = new UserModel;
        $manager_2->is_admin = false; // always use as not admin, hide some fileds, api chaeckbox etc.
        $manager_2->use_old_pass = SettingModel::getQuick(1, 'account_password_old');
        
        if (AuthProvider::isRemoteAuth()) {
            AuthRemote::loadEnviroment();
            $manager_2->account_updateable = AuthRemote::isAccountUpdateable();
        
        } elseif (AuthProvider::isSamlAuth()) {
            AuthProvider::loadSaml();    
            $auth_setting = AuthProvider::getSettings();
            $manager_2->account_updateable = AuthSaml::isAccountUpdateable($auth_setting);
        }
        
        // update
        if(isset($this->rp->submit)) {
            
            $errors = $view->validate($this->rp->vars, $manager, $manager_2);
            
            if($errors) {
                $this->rp->stripVars(true);
                $view->setErrors($errors);
                $view->setFormData($this->rp->vars);
            
            } else {
                
                $this->rp->stripVars();
                $obj_2->set($this->rp->vars);
                $obj_2->set('id', $manager->user_id);
                $obj_2->setPassword(1); // mean not insert in db if not_change_pass = 1
                $obj_2->unsetProperties(array('user_comment', 'admin_comment', 'grantor_id', 'company_id'));
                
                $manager_2->save($obj_2);
                
                $controller->go('success_go', false, false, 'account_updated');
            }

        // password
        } elseif(isset($this->rp->submit_password)) {

            $errors = $view->validate($this->rp->vars, $manager, $manager_2, $obj_2);
            
            if($errors) {
                $this->rp->stripVars(true);
                $view->setErrors($errors);
                $view->setFormData($this->rp->vars);
            
            } else {
                
                $this->rp->stripVars();
                $obj_2->set($this->rp->vars);
                $obj_2->set('id', $manager->user_id);
                $obj_2->setPassword(); // hash it
                                                
                $manager_2->updatePassword($obj_2->get('password'), $obj_2->get('id'), $obj_2->pass_changed);
                
                $controller->go('success_go', false, false, 'password_updated');
            }
            
        // api
        } elseif(isset($this->rp->submit_api)) {

            $obj_2->setExtra($manager_2->getExtraById($manager->user_id));
            
            $api_rule_id = $manager_2->extra_rules['api'];
            $api_data = $obj_2->getExtraValues($api_rule_id);
            $api_access = $api_data['api_access'];

            // no access to update it
            if(!$api_access && !$manager->is_admin) {
                $controller->go('member_account');
            }

            $obj_2->setExtra($this->rp->vars['extra']);
            $api_data = $obj_2->getExtraValues($api_rule_id);
            $errors = $obj_2->validateApiKeys($api_data);
            
            if($errors) {
                $this->rp->stripVars(true);
                $view->setErrors($errors);
                $obj_2->setExtra($this->rp->vars['extra']);
            
            } else {
                
                $this->rp->stripVars();
                $obj_2->setExtra($this->rp->vars['extra']);

                $manager_2->saveExtra($obj_2->getExtra(), $manager->user_id);
                $controller->go('success_go', false, false, 'api_updated');
            }
                    
        } else {
            
            $data = $manager_2->getById($manager->user_id);
            $this->rp->stripVarsValues($data);
            $view->setFormData($data); // for update 
            
            // for detail view
            $obj_2->set($data);
            $obj_2->setPriv($manager_2->getPrivById($manager->user_id));
            $obj_2->setRole($manager_2->getRoleById($manager->user_id));
            
            // for api view 
            $extra = $manager_2->getExtraById($manager->user_id);
            $this->rp->stripVarsValues($extra);
            $obj_2->setExtra($extra);
        }
        
        $view->obj_2 =& $obj_2;
        $view->manager_2 =& $manager_2;
        
        return $view;
    }
    
    
    function validateApiKeys($values) {
        $obj = new User;
        $obj->validateApiKeys($values, $manager_2);
        return $obj->errors;
    }        

}
?>