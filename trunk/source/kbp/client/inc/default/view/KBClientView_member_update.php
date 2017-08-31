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


class KBClientView_member_update extends KBClientView_member
{
	
	function &execute(&$manager) {
		
		$this->home_link = true;
		$this->parse_form = false;
		$this->meta_title = $this->msg['my_account_msg'];
		
		$this->addMsg('user_msg.ini');
		
		$link = $this->controller->getLink('member');
		$this->nav_title = array($link => $this->msg['my_account_msg'], 
		                         $this->msg['update_profile_msg']);
		
		$data = &$this->getForm($manager, $this->msg['update_profile_msg']);

		return $data;		
	}	
	
	
	function &getForm($manager, $title) {
		
		$tpl = new tplTemplatez($this->getTemplate('register_form.html'));
		$tpl->tplAssign('member_menu', $this->getMemberMenu($manager));
		
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
		$tpl->tplAssign('cancel_link', $this->getLink('member_account'));															  
		$tpl->tplAssign('error_msg', $this->getErrors());
        
        $ajax = &$this->getAjax('validate');
        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('validate', $ajax, 'ajaxValidateForm'));
				        
		$tpl->tplAssign($this->msg);
		$tpl->tplAssign($this->getFormData());
		
		$tpl->tplParse();
		return $tpl->tplPrint(1);
	}
    
    
    function validate($values, $manager, $manager_2) {
        $values['not_change_pass'] = 1;
        
        $obj = new User;
        $obj->validate($values, $manager_2);
        return $obj->errors;
    }
    
    
    function getValidate($values) {
        $ret = array();
        $ret['func'] = array($this, 'validate');
        
        $manager_2 = new UserModel;
        $manager_2->is_admin = false; // always use as not admin, hide some fileds, api chaeckbox etc.
        $manager_2->use_old_pass = SettingModel::getQuick(1, 'account_password_old');
        
        $ret['options'] = array($values, 'manager', $manager_2);
        return $ret;
    }
}
?>