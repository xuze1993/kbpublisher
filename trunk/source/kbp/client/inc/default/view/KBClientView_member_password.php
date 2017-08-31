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
require_once APP_MODULE_DIR . 'user/user/inc/UserView_password.php';


class KBClientView_member_password extends KBClientView_member
{
    
	function &execute(&$manager) {
		
		$this->home_link = true;
		$this->parse_form = false;
		$this->meta_title = $this->msg['my_account_msg'];
		
		$this->addMsg('user_msg.ini');
		
		$link = $this->controller->getLink('member');
		$this->nav_title = array($link => $this->msg['my_account_msg'], 
		                         $this->msg['update_password_msg']);
		
		$data = &$this->getForm($manager, $this->msg['update_password_msg']);

		return $data;		
	}	
	
	
	function &getForm($manager, $title) {
		
		$tpl = new tplTemplatez($this->getTemplate('member_password.html'));
		$tpl->tplAssign('member_menu', $this->getMemberMenu($manager));
        
        if (AuthPriv::getPassExpired()) {
            $tpl->tplAssign('hint', UserView_password::getChangePasswordHint($this->msg, $manager->setting));
        } else {
            $tpl->tplSetNeeded('/cancel_btn');
        }	
		
		if($this->manager_2->use_old_pass) {
		    $this->msg['password_msg'] = $this->msg['password_new_msg'];
		    $tpl->tplSetNeeded('/old_password');
		}
		
		$tpl->tplAssign('id', $manager->user_id);
		$tpl->tplAssign('title', $title);
		$tpl->tplAssign('action_link', $this->getLink('all'));
		$tpl->tplAssign('cancel_link', $this->getLink('member_account'));															  
		$tpl->tplAssign('error_msg', $this->getErrors());
        
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
		$tpl->tplAssign($this->getFormData());
		
		$tpl->tplParse();
		return $tpl->tplPrint(1);
	}
    
    
    function validate($values, $manager, $manager_2, $obj) {
        $obj->validatePassword($values, $manager_2);
        if ($obj->errors) {
            return $obj->errors;
        }
    }
    
    
    function getValidate($values) {
        $ret = array();
        $ret['func'] = array($this, 'validate');
        
        $manager_2 = new UserModel;
        $manager_2->is_admin = false; // always use as not admin, hide some fileds, api chaeckbox etc.
        $manager_2->use_old_pass = SettingModel::getQuick(1, 'account_password_old');
        
        $obj_2 = new User;
        
        $ret['options'] = array($values, 'manager', $manager_2, $obj_2);
        return $ret;
    }
}
?>