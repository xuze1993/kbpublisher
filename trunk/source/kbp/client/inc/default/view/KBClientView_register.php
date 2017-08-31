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
require_once APP_MODULE_DIR . 'user/user/inc/UserView_form.php';


class KBClientView_register extends KBClientView_common
{
    
    var $update = false;
    

    function &execute(&$manager) {
        
        $title = $this->msg['register_msg'];
        
        $this->home_link = true;
        $this->parse_form = false;
        $this->meta_title = '';
        $this->nav_title = $title;
        
        $data = &$this->getForm($manager, $title);
        
        return $data;        
    }
    

    function &getForm($manager, $title) {

        $this->addMsg('user_msg.ini');
        
        $tpl = new tplTemplatez($this->getTemplate('register_form.html'));
        
        if($this->update) {
            @$val = ($_POST) ? $_POST['remember'] : AuthPriv::getCookie();
            $val = ($val) ? 'checked' : '';
            $tpl->tplAssign('remember_option', $val);
        } else {
            $tpl->tplSetNeededGlobal('register');
        }
        
        
        $tpl->tplAssign('id', $manager->user_id);
        $tpl->tplAssign('title', $title);
        $tpl->tplAssign('action_link', $this->getLink('all'));
        $tpl->tplAssign('cancel_link', $this->getLink());
        $tpl->tplAssign('error_msg', $this->getErrors());
        
        
        if($this->useCaptcha($manager, 'register')) {
            $tpl->tplSetNeeded('/captcha');
            $tpl->tplAssign('captcha_src', $this->getCaptchaSrc());
        }
        

        // subscription
        if($subs = $manager->getSetting('allow_subscribe_news')) {    
            // if subs for user with priv and assign priv on registering
            //if($subs == 3 && !$manager->getSetting('register_user_priv')) {
            //    $subs = false;
            //}
        }
        
        if($subs) {
            $subscribe = ($_POST) ? @$_POST['subsc_news'] : 1;
            $tpl->tplAssign('ch_subsc_news', $this->getChecked($subscribe));
            $tpl->tplSetNeeded('/subscription');            
        }
        
        // generate
        $view = new UserView_form;
        $view->template_dir = APP_MODULE_DIR . 'user/user/template/';
        $tpl->tplAssign('generate_pass_block', $view->getGeneratePasswordBlock());
        
        $ajax = &$this->getAjax('entry');
        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('generatePassword', $view, 'ajaxGeneratePassword'));
        
        
        $ajax = &$this->getAjax('validate');
        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('validate', $ajax, 'ajaxValidateForm'));
        
        
        $tpl->tplAssign($this->msg);
        //$tpl->tplAssign($this->convertRequiredMsg(array('username_msg', 'password_msg', 'captcha_msg')));
        $tpl->tplAssign($this->getFormData());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function validate($values, $manager, $manager_2) {
        
        require_once 'core/app/AppAjax.php';
        require_once 'eleontev/Validator.php';
        
        $obj = new User;
        $obj->validate($values, $manager_2);
        if($obj->errors) {
            return $obj->errors;
        }
        
        
        $v = new Validator($values, false);
        
        if($this->useCaptcha($manager, 'register', true)) {
            $unset = !AppAjax::isAjaxRequest();
            if(!$this->isCaptchaValid($values['captcha'], false, $unset)) {
                $v->setError('captcha_text_msg', 'captcha', 'captcha');
            }
        }
        
        if($v->getErrors()) {
            return $v->getErrors();
        }
        
    }
    
    
    function getValidate($values) {
        $ret = array();
        $ret['func'] = array($this, 'validate');
        
        $manager_2 = new UserModel;
        $manager_2->use_priv = true;
        $manager_2->use_role = true;
        $manager_2->use_old_pass = false;
        
        $ret['options'] = array($values, 'manager', $manager_2);
        return $ret;
    }
}
?>