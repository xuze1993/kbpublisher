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

require_once 'core/app/AppMailParser.php';


class TriggerEntryView_form extends AppView
{
    
    var $tmpl = 'form.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('trigger_msg.ini');
        $this->addMsg('random_msg.ini');
        
        $this->template_dir = APP_MODULE_DIR . 'tool/trigger/template/';
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        $this->_parseEmailBox($tpl, $obj, $manager);
        
        $tr_manager = new TriggerModel();
        
        $entry_type = $manager->record_type[$obj->get('entry_type')];
        $trigger_type = array_search($obj->get('trigger_type'), $manager->trigger_types);
        $class_name = sprintf('%s_%s', $entry_type, $trigger_type);
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateForm'));
        
        // conditions
        $cond = TriggerParserCondition::factory($class_name);
        $cond->encoding = $this->encoding;
        $cond->time_format = $this->conf['lang']['time_format'];
        $cond->setMsg();
        $cond->setManager($tr_manager);
        
        if($obj->get('cond')) {
            $cond->setItems($obj->get('cond'));
        } else {
            $cond->setDefaultItem();
        }

        $tpl->tplAssign('condition_html', $cond->parseItems('condition', 'populateCondition', 'sc'));
        
        if($cond->error) {
            $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
            $msgs = AppMsg::parseMsgsMultiIni($file);
            $msg['title'] = '';
            $msg['body'] = $msgs['note_automation_incomplete'];
            $tpl->tplAssign('msg', BoxMsg::factory('error', $msg));
        }
        
        
        // condition js 
        $tpl->tplAssign('condition_readroot', $cond->id_readroot);
        $tpl->tplAssign('condition_writeroot', $cond->id_writeroot);
        $tpl->tplAssign('condition_id_pref', $cond->id_pref);
        $tpl->tplAssign('condition_id_pref_populate', $cond->id_pref_populate);
        $tpl->tplAssign('condition_counter', $cond->counter);
        $tpl->tplAssign('condition_html_default', $cond->parseDefaultItem('condition', 'populateCondition', 'sc'));
        
        $tpl->tplAssign('match_select', $cond->getMatchSelect($obj->get('cond_match')));
        
        $xajax->registerFunction(array('populateCondition', $cond, 'ajaxPopulate'));
        
        
        // actions
        $act = TriggerParserAction::factory($class_name);
        $act->encoding = $this->encoding;
        $act->time_format = $this->conf['lang']['time_format'];
        $act->setMsg();
        $act->setManager($tr_manager);
        
        $this->act = $act;
        
        $actions = $obj->get('action');
        if($actions) {
            if ($obj->get('trigger_key_temp')) {
                $predefined = $manager->getpredefinedValues($obj->get('trigger_key_temp'));
                
                foreach (array_keys($actions) as $key) {
                    $email_actions = array('email', 'email_department', 'email_user_grouped');
                    
                    if (in_array($actions[$key]['item'], $email_actions)) { // load a default template
                        if (empty($actions[$key]['rule'][1])) {
                            $actions[$key]['rule'][1] = $predefined['subject'];
                        }
                        
                        if (empty($actions[$key]['rule'][2])) {
                            $p = new AppMailParser;
                            $email_tmpl = $p->getTemplate($obj->get('trigger_key_temp'));
                            $actions[$key]['rule'][2] = $email_tmpl;    
                        }
                    }
                }
            }
            
            $act->setItems($actions);
        } else {
            $act->setDefaultItem();
        }
        
        $tpl->tplAssign('action_html', $act->parseItems('action', 'populateAction', 'sa'));
        
        $xajax->registerFunction(array('populateAction', $act, 'ajaxPopulate'));
        $xajax->registerFunction(array('showTemplateTags', $this, 'ajaxShowTemplateTags'));
        $xajax->registerFunction(array('populateTemplate', $this, 'ajaxPopulateTemplate'));
        

        // action js
        $tpl->tplAssign('action_readroot', $act->id_readroot);
        $tpl->tplAssign('action_writeroot', $act->id_writeroot);
        $tpl->tplAssign('action_id_pref', $act->id_pref);
        $tpl->tplAssign('action_id_pref_populate', $act->id_pref_populate);
        $tpl->tplAssign('action_counter', $act->counter);
        $tpl->tplAssign('action_html_default', $act->parseDefaultItem('action', 'populateAction', 'sa'));
        
        $more  = array('filter[priv]' => 'any', 'filter[s]' => 1);
        $link = $this->getLink('users', 'user', false, false, $more);
        $tpl->tplAssign('user_popup_link', $this->controller->_replaceArgSeparator($link));
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function ajaxShowTemplateTags($num, $action_name) {
        $objResponse = new xajaxResponse();
        
        $tpl = new tplTemplatez($this->template_dir . 'placeholder_list.html');
        
        $file = AppMsg::getCommonMsgFile('placeholder_msg.ini');
        $msg = AppMsg::parseMsgsMultiIni($file);
        
        $placeholder_group = (in_array($action_name, array('email_user_grouped', 'email_group_grouped'))) ? 'grouped' : 'non-grouped';
        $tags = $this->act->placeholders[$placeholder_group];
        
        foreach ($tags as $tag) {
            $v['title'] = sprintf('[%s]', $tag);
            $msg_tag = str_replace('.', '_', $tag);
            $v['desc'] = (!empty($msg[$msg_tag])) ? $msg[$msg_tag] : '';
            $tpl->tplParse($v, 'row');
        }
        
        if ($placeholder_group == 'grouped') {
            $tpl->tplSetNeeded('/loop_hint');
            
            $tags = $this->act->placeholders['non-grouped'];
            
            foreach ($tags as $tag) {
                $v['title'] = sprintf('[%s]', $tag);
                $msg_tag = str_replace('.', '_', $tag);
                $v['desc'] = (!empty($msg[$msg_tag])) ? $msg[$msg_tag] : '';
                $tpl->tplParse($v, 'row2');
            }
        }
        
        $tpl->tplAssign($this->msg);
        $tpl->tplParse();
        
        $objResponse->assign('template_tags_' . $num, 'innerHTML', $tpl->tplPrint(1));
        $objResponse->assign('template_tags_' . $num, 'style.display', 'block');
        $objResponse->assign('template_tags_button_' . $num, 'style.display', 'none');
        $objResponse->assign('template_tags_hide_button_' . $num, 'style.display', 'inline');
        
        return $objResponse;
    }
    
    
    function ajaxPopulateTemplate($email_body, $action_name) {
        $objResponse = new xajaxResponse();
        
        require_once 'core/app/AppMailSender.php';
        require_once APP_ADMIN_DIR . 'cron/inc/AutomationModel.php';
        require_once APP_ADMIN_DIR . 'cron/inc/DataConsistencyModel.php';
        require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryModel.php';
        
        $entry_type = substr($this->controller->sub_page, 3);
        
        $sender = new AppMailSender;
        if ((in_array($action_name, array('email_user_grouped', 'email_group_grouped')))) {
            $sender->parser->replacer->s_loop_tag = '<row>';
            $sender->parser->replacer->e_loop_tag = '</row>';
        }
        
        $a_manager = new AutomationModel;
        $a_manager->etype = $entry_type;
        $a_manager->emanager = DataConsistencyModel::getEntryManager('admin', array_search($entry_type, $a_manager->record_type));
        
        
        // test data
        $entries = array();
        
        $user = new UserModel;
        $user = $user->getById(AuthPriv::getPrivId());
        
        $status_range = $this->act->getStatusSelectRange();
        
        
        $custom_fields = $a_manager->emanager->cf_manager->getCustomFieldByEntryType();
        $custom_data = array();
        foreach ($custom_fields as $custom_id => $v) {
            $custom_data[$custom_id]['title'] = $v['title'];
            $custom_data[$custom_id]['value'] = 1;
        }
        
        for ($i = 1; $i <= 10; $i ++) {
            $entry = array();
            
            $entry['id'] = $i;
            $entry['title'] = sprintf('Test %s #%d', ucwords($entry_type), $i);
            
            $entry['filename'] = 'Test.docx';
            
            $entry['author'] = $user;
            $entry['updater'] = $user;
            
            $entry['active'] = key($status_range);
            
            if ($entry_type == 'article') {
                $type_range = $this->act->getTypeSelectRange();
                end($type_range);
                $entry['entry_type'] = key($type_range);
            }
            
            $entry['custom'] = $custom_data;
            
            $entries[$i] = $entry;
        }
        
        if (in_array($action_name, array('email_user_grouped', 'email_group_grouped'))) {
            $vars = $a_manager->_getEmailVarsGrouped($entries);
            
        } else {
            $vars = $a_manager->_getEmailVars($entries[1]);
        }
        
        $vars['email'] = 'test@example.com';
        
        $sender->parser->assign($vars);
        
        $parsed_email_body = nl2br($sender->parser->parse($email_body));
        $objResponse->call('showPopulatedTemplate', $parsed_email_body);
    
        return $objResponse;    
    }
    
    
    public function __call($name, $arguments) {
        
    }
}
?>