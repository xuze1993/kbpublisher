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


class KBClientView_member_api extends KBClientView_member
{
	
	function &execute(&$manager) {
		
		$this->home_link = true;
		$this->parse_form = false;
		$this->meta_title = $this->msg['my_account_msg'];
		
		$this->addMsg('user_msg.ini');
		$this->escapeMsg(array('api_reset_key_sure2_msg'));
		
		$link = $this->controller->getLink('member');
		$this->nav_title = array($link => $this->msg['my_account_msg'], 
		                         $this->msg['api_update_msg']);
		
		$data = &$this->getForm($manager, $this->msg['api_update_msg']);

		return $data;		
	}	
	
	
	function &getForm($manager, $title) {
		
		$tpl = new tplTemplatez($this->getTemplate('member_api.html'));
		$tpl->tplAssign('member_menu', $this->getMemberMenu($manager));	
		$tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        $tpl->tplAssign('api_note_msg', AppMsg::hintBoxCommon('note_api_account'));
        	
        //xajax
        // $ajax = &$this->getAjax($obj, $manager);
        // $xajax = &$ajax->getAjax();
        // $xajax->registerFunction(array('generateApiKey', $this, 'ajaxGenerateApiKey'));        

        //xajax
        $ajax = &$this->getAjax('entry');
        $xajax = &$ajax->getAjax($manager);
        $xajax->registerFunction(array('generateApiKey', 'UserView_api', 'ajaxGenerateApiKey'));

        // extra
        $api_rule_id = $this->manager_2->extra_rules['api']; 
        $api_data = $this->obj_2->getExtraValues($api_rule_id);
        $api_access = $api_data['api_access'];
        $tpl->tplAssign($api_data);
        $tpl->tplAssign('api_rule_id', $api_rule_id);
        $tpl->tplAssign('api_access', (!empty($api_access)) ? 1 : 0);

        $http = ($manager->getSetting('api_secure')) ? 'https://' : 'http://';
        $api_url = $http . $this->conf['api_path'];
        $tpl->tplAssign('api_url', $api_url);
		
		$tpl->tplAssign('id', $manager->user_id);
		$tpl->tplAssign('title', $title);
		$tpl->tplAssign('action_link', $this->getLink('all'));
		$tpl->tplAssign('cancel_link', $this->getLink('member_account'));															  
		$tpl->tplAssign('error_msg', $this->getErrors());
				        
		$tpl->tplAssign($this->msg);
		$tpl->tplAssign($this->getFormData());
		
		$tpl->tplParse();
		return $tpl->tplPrint(1);
	}
}
?>