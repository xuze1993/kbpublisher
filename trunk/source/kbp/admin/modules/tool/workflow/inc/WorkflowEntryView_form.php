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

class WorkflowEntryView_form extends AppView
{
    
    var $tmpl = 'form.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('trigger_msg.ini');
        $this->addMsg('random_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        $tr_manager = new WorkflowModel;
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateForm'));
        
        // conditions
        $tpl->tplSetNeeded('/condition');
        
        $cond = WorkflowParserCondition::factory('workflow');
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
        $act = WorkflowParserAction::factory('workflow');
        $act->encoding = $this->encoding;
        $act->time_format = $this->conf['lang']['time_format'];
        $act->setMsg();
        $act->setManager($tr_manager);
        
        $this->act = $act;
        
        $actions = $obj->get('action');
        if($actions) {
            if ($obj->get('trigger_key_temp')) {
                $actions = $manager->setPredefinedStepTitles($actions, $obj->get('trigger_key_temp'));
            }
            
            $act->setItems($actions);
        } else {
            $act->setDefaultItem();
        }        
        
        $step_numbers =($obj->get('id')) ? $manager->getCurrentStepNumbers($obj->get('id')) : array();
        $tpl->tplAssign('action_html', $act->parseItems('action', 'populateAction', 'sa', $step_numbers));
        
        $xajax->registerFunction(array('populateAction', $act, 'ajaxPopulate'));


        // action js
        $tpl->tplAssign('action_readroot', $act->id_readroot);
        $tpl->tplAssign('action_writeroot', $act->id_writeroot);
        $tpl->tplAssign('action_id_pref', $act->id_pref);
        $tpl->tplAssign('action_id_pref_populate', $act->id_pref_populate);
        $tpl->tplAssign('action_counter', $act->counter);
        $tpl->tplAssign('action_html_default', $act->parseDefaultItem('action', 'populateAction', 'sa'));
        
        
        $more = array('filter[priv]' => 'any', 'filter[s]' => 1);
        $link = $this->getLink('users', 'user', false, false, $more);
        $tpl->tplAssign('user_popup_link', $this->controller->_replaceArgSeparator($link));
        
        if($this->controller->action == 'update' && 
            (!empty($step_numbers))) {
                
            $msg = AppMsg::getMsgs('after_action_msg.ini', false, 'workflow_in_use');
            $tpl->tplAssign('error_msg', BoxMsg::factory('hint', $msg));
            
        } else {
            //$tpl->tplSetNeeded('/submit_button');
        }
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
}
?>