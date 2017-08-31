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


class KBClientView_password extends KBClientView_common
{
    

    function &execute(&$manager) {
        
        $this->addMsg('user_msg.ini');
        
        $this->home_link = true;
        $this->parse_form = false;
        $this->meta_title = $this->msg['reset_password_msg'];
        $this->nav_title = $this->msg['reset_password_msg'];
        
        
        if($this->msg_id == 'password_reset_sent') {
            $data = '';
            
        } else {
            $data = $this->getForm($manager);
        }
        
        return $data;
    }
    
    
    function getForm($manager) {
                
        $tpl = new tplTemplatez($this->getTemplate('password_form.html'));
        
        if($this->getErrors()) { 
            $tpl->tplAssign('error_msg', $this->getErrors()); 
        }
        
        $tpl->tplAssign('password_hint', $this->msg['restore_password2_msg']);
                
        if($this->useCaptcha($manager, 'password')) {
            $tpl->tplSetNeeded('/captcha');
            $tpl->tplAssign('captcha_src', $this->getCaptchaSrc());
        }
        
        $ajax = &$this->getAjax('validate');
        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('validate', $ajax, 'ajaxValidateForm'));
        
        $tpl->tplAssign('action_link', $this->getLink('password'));
        $tpl->tplAssign('cancel_link', $this->getLink());
        
        $tpl->tplParse(array_merge($this->msg, $this->getFormData()));
        return $tpl->tplPrint(1);
    }
    
    
    function validate($values, $manager) {
        require_once 'core/app/AppAjax.php';
        
        $required = array('email');
        
        $v = new Validator($values, false);
        $v->required('required_msg', $required);
        $v->regex('email_msg', 'email', 'email');
    
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