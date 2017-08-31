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


class TriggerParserAction extends TriggerParser
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
		$class = 'TriggerParserAction_' . $type;
		$file  = 'TriggerParserAction_' . $type . '.php';
		
		//require_once $file;
		return new $class;
	}
	
	
    function &getRuleOption($item) {
        
        $file = sprintf('trigger_map_%s.php', $this->etype);
        include APP_MODULE_DIR . 'tool/trigger/inc/' . $file;
        
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
        return $this->msg['trigger_action_rule'];
    }
    
    
    function getItemSelect($selected = false) {
        $file = sprintf('trigger_map_%s.php', $this->etype);
        include APP_MODULE_DIR . 'tool/trigger/inc/' . $file;        
        return $this->_getItemSelect(array_keys($actions), $this->msg['trigger_action'], $selected);
    }
}



class TriggerParserAction_article extends TriggerParserAction
{
    
    var $default_item = 'status';
    var $default_rule = array(0);
    var $etype = 'article';
    
    var $placeholders = array(
        'non-grouped' => array(
            'article.id',
            'article.title',
            'article.type',
            'article.status',
            //'article.latest_comment',
            'article.link',
            'article.author.id',
            'article.author.username',
            'article.author.name',
            'article.author.first_name',
            'article.author.middle_name',
            'article.author.last_name',
            'article.updater.id',
            'article.updater.username',
            'article.updater.name',
            'article.updater.first_name',
            'article.updater.middle_name',
            'article.updater.last_name'
        ),
        'grouped' => array(
            'articles.num',
            'articles.link.filtered.outdated'
        )
    );
    
    var $extra_tags = array();
       
    
    function getCategorySelectRange() {
        return $this->model->getCategorySelectRange('article');
    }
    
    
    function getStatusSelectRange() {
        $m = TriggerModel::instance('KBEntryModel');
        return $m->getListSelectRange('article_status', false);
    }
    
    
    function getTypeSelectRange() {
        $m = TriggerModel::instance('KBEntryModel');
        $range = $m->getListSelectRange('article_type', false);
        $extra_range = array(0 => '__');
        
        return $extra_range + $range;
    }
    
    
    function getEmailRecipientSelectRange($value) {
        $placeholders = array(
            'author' => '[author]',
            'updater' => '[updater]');
        return $this->model->getUserSelectRange($value, $placeholders, false);
    }
}


class TriggerParserAction_article_automation extends TriggerParserAction
{
    var $default_item = 'status';
    var $default_rule = array(1);
    var $etype = 'article_automation';
    
    var $placeholders = array(
        'non-grouped' => array(
            'article.id',
            'article.title',
            'article.type',
            'article.status',
            //'article.latest_comment',
            'article.link',
            'article.author.id',
            'article.author.username',
            'article.author.name',
            'article.author.first_name',
            'article.author.middle_name',
            'article.author.last_name',
            'article.updater.id',
            'article.updater.username',
            'article.updater.name',
            'article.updater.first_name',
            'article.updater.middle_name',
            'article.updater.last_name',
            'article.custom.{custom_id}.title',
            'article.custom.{custom_id}.value'
        ),
        'grouped' => array(
            'articles.num',
            'articles.link.filtered.outdated'
        )
    );
    
    
    function getStatusSelectRange() {
        $m = &TriggerModel::instance('KBEntryModel');
        return $m->getListSelectRange('article_status', false);
    }
    
    
    function getTypeSelectRange() {
        $m = TriggerModel::instance('KBEntryModel');
        $range = $m->getListSelectRange('article_type', false);
        $extra_range = array(0 => '__');
        
        return $extra_range + $range;
    }
        
    
    function getEmailRecipientSelectRange($value) {
        $placeholders = array(
            'author' => '[author]',
            'updater' => '[updater]');
        return $this->model->getUserSelectRange($value, $placeholders, false);
    }
    
    
    function getGroupEmailRecipientSelectRange($value) {
        $placeholders = array('supervisors' => '[category supervisors]');
        return $placeholders;
    }
}


class TriggerParserAction_file_automation extends TriggerParserAction
{
    var $default_item = 'status';
    var $default_rule = array(1);
    var $etype = 'file_automation';
    
    var $placeholders = array(
        'non-grouped' => array(
            'file.filename',
            'file.title',
            'file.status',
            'file.link',
            'file.author.id',
            'file.author.username',
            'file.author.name',
            'file.author.first_name',
            'file.author.middle_name',
            'file.author.last_name',
            'file.updater.id',
            'file.updater.username',
            'file.updater.name',
            'file.updater.first_name',
            'file.updater.middle_name',
            'file.updater.last_name'
        ),
        'grouped' => array(
            'files.num',
            'files.link.filtered.outdated'
        )
    );
    
    
    function getStatusSelectRange() {
        $m = &TriggerModel::instance('KBEntryModel');
        return $m->getListSelectRange('file_status', false);
    }
        
    
    function getEmailRecipientSelectRange($value) {
        $placeholders = array(
            'author' => '[author]',
            'updater' => '[updater]');
        return $this->model->getUserSelectRange($value, $placeholders, false);
    }
    
    
    function getGroupEmailRecipientSelectRange($value) {
        $placeholders = array('supervisors' => '[category supervisors]');
        return $placeholders;
    }
}


class TriggerParserAction_email_automation extends TriggerParserAction
{
    var $default_item = 'create_draft';
    var $default_rule = array();
    var $etype = 'email_automation';
    
    
    static $placeholders = array(
        'message.from.email',
        'message.from.name',
        'message.to.email',
        'message.cc.email',
        'message.subject',
        'message.content',
        'message.date.received',
        'date_created');
}

?>