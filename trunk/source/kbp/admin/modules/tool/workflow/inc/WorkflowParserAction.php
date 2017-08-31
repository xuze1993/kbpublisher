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


class WorkflowParserAction extends WorkflowParser
{
	
	var $id_readroot = 'action_readroot';
	var $id_writeroot = 'action_writeroot';
	var $id_pref = 'more_action_';
	var $id_pref_populate = 'more_action_populate_';
	var $counter = 1;
	var $condition_name = 'action';	
	
	var $items = array();
	var $msg = array();
	
	
	static function factory($type) {
		$class = 'WorkflowParserAction_' . $type;
		$file  = 'WorkflowParserAction_' . $type . '.php';
		
		//require_once $file;
		return new $class;
	}
	
	
    function &getRuleOption($item) {
        
        $file = sprintf('workflow_map_%s.php', $this->etype);
        include APP_MODULE_DIR . 'tool/workflow/inc/' . $file;
        
        $rule = array('empty' => '');
        if(isset($actions[$item])) {
            $item_rule = $actions[$item];
            $rule = $action_rules[$item_rule['r']];            
        }
        
        // mesage if any change from item_to_rule
        if(isset($rule['msg']) && isset($item_rule['msg'])) {
            $rule['msg']['value'] = $item_rule['msg'];
        }
        
        return $rule;
    }
    
    
    function &getRuleMsg() {
        // return $this->msg['trigger_action_rule'];
    	$r = array();
		return $r;
	}
    
    
    function getItemSelect($selected = false) {
        $file = sprintf('workflow_map_%s.php', $this->etype);
        include APP_MODULE_DIR . 'tool/workflow/inc/' . $file;        
        return $this->_getItemSelect(array_keys($actions), $this->msg['trigger_action'], $selected);
    }
}



class WorkflowParserAction_workflow extends WorkflowParserAction
{
    
    var $default_item = 'assign';
    var $default_rule = array('category_admin');
    var $default_title = ''; 
    
    var $etype = 'article';
    var $extra_html = '<span class="extra_html"><span class="step_number">%s.</span>
                       <input type="text" name="%s[%s][title]" value="%s" style="width: 250px;" placeholder="%s" /><div style="height: 5px;"></div>
                       <span class="step_number"></span></span>';
       
    
    function getCategorySelectRange() {
        return $this->model->getCategorySelectRange('article');
    }
    
    
    function getStatusSelectRange() {
        $m = WorkflowModel::instance('KBEntryModel');
        return $m->getListSelectRange('article_status', false);
    }
    
    
    function getApproverSelectRange($value) {
        $placeholders = array(
            'category_admin' => '[category supervisors]',
            'author' => '[author]',
            'selectable' => '[selectable]'
        );
        
        $privileges = $this->model->getPrivSelectRange();
        $data = array_merge($placeholders, $privileges);
        
        $m = &WorkflowModel::instance('UserModel');
        if (!empty($value) && is_numeric($value)) {
            $extra_user = $m->getById($value);
            $data[$value] = sprintf('%s %s', $extra_user['first_name'], $extra_user['last_name']);
        }
        
        $user = $m->getById($m->user_id);
        $data[$m->user_id] = sprintf('[current user: %s %s]', $user['first_name'], $user['last_name']);
        
        $data['more'] = '...';
        
        return $data;
    }
}

?>