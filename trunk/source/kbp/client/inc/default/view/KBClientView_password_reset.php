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

require_once 'core/app/AppView.php';
require_once APP_MODULE_DIR . 'user/user/inc/UserView_form.php';

        
class KBClientView_password_reset extends KBClientView_common
{
    
    var $code_confirmed = false;
    

    function &execute(&$manager) {
        
        $this->addMsg('user_msg.ini');
        
        $this->home_link = true;
        $this->parse_form = false;
        $this->meta_title = $this->msg['reset_password_msg'];
        $this->nav_title = $this->msg['reset_password_msg'];
        
        $data = $this->getForm($manager);
        
        return $data;
    }
    
    
    function getForm($manager) {
        
        $tpl = new tplTemplatez($this->getTemplate('password_reset_form.html'));
        
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
        
        if($this->getErrors()) { 
            $tpl->tplAssign('error_msg', $this->getErrors()); 
        }
        
        $tpl->tplAssign('password_hint', $this->msg['enter_password_msg']);
        
        if($this->useCaptcha($manager, 'password')) {
            $tpl->tplSetNeeded('/captcha');
            $tpl->tplAssign('captcha_src', $this->getCaptchaSrc());
        }
        
        if(!isset($_POST['reset_code'])) {
            $code = $this->stripVars($_GET['rc'], array(), 'asdasdasda');
            $tpl->tplAssign('reset_code', $code);
        }        
        
        $tpl->tplAssign('action_link', $this->getLink('all'));
        $tpl->tplAssign('cancel_link', $this->getLink());                                                              
        
        $tpl->tplParse(array_merge($this->msg, $this->getFormData()));
        return $tpl->tplPrint(1);
    }
    
    
    function validate($values, $manager) {
        
        require_once 'core/app/AppAjax.php';
        require_once 'eleontev/Validator.php';
        require_once 'eleontev/Util/PasswordUtil.php';
        
        $required = array('password', 'password_2');
                
        $v = new Validator($values, false);

        // check for required first, return errors
        $v->required('required_msg', $required);
        
        if(PasswordUtil::isWeakPassword($values['password'])) {
            $v->setError('pass_weak_msg', 'password');
        }

        $v->compare('pass_diff_msg', 'password', 'password_2');
        
        if($this->useCaptcha($manager, 'password', true)) {
            $unset = !AppAjax::isAjaxRequest();
            if(!$this->isCaptchaValid($values['captcha'], false, $unset)) {
                $v->setError('captcha_text_msg', 'captcha', 'captcha');
            }
        }

        
        return $v->getErrors();        
    }    
}
?>