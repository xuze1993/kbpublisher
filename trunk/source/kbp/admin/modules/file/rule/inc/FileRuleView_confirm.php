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

require_once APP_MODULE_DIR . 'file/draft/inc/FileDraftModel.php';


class FileRuleView_confirm extends AppView
{
    
    var $template = 'form_confirm.html';
    
    
    function execute(&$obj, &$manager, $emanager) {
        
        $eobj = $obj->get('entry_obj');
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        $files_admissible = $emanager->readDirectory($obj->get('directory'), !$obj->get('parse_child'), false);
        $files_admissible = ExtFunc::multiArrayToOne($files_admissible);
        
        $files_total = $manager->readDirectory($obj->get('directory'), !$obj->get('parse_child'));
        $files_total = ExtFunc::multiArrayToOne($files_total);
         
        $status_title = ListValueModel::getListTitle('file_status', $eobj->get('active'));
        
        $d = $obj->get('directory');
        $directory = ($obj->get('parse_child')) ? $d.'*' : $d;
        
        
        // msg
        $workflow_title = '';
        $key = 'file_rule_confirmation';
        if ($obj->get('is_draft')) {
            $draft_manager = new FileDraftModel;
            $key = 'file_rule_confirmation_draft';
                        
            $options = array(
                'source' => 'dir_rule', 
                'user_id' => $eobj->get('author_id')
                );
            
            $workflow = $draft_manager->getAppliedWorkflow($options);
            if ($workflow) {
                $key = 'file_rule_confirmation_draft_workflow';
                $workflow_title = $workflow['title'];  
            }
        }
        
        $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
        $msgs = AppMsg::parseMsgsMultiIni($file);
        $msg = BoxMsg::factory('hint');
        
        $msg->setMsg('title', $this->msg['rule_summary_msg']);
        $msg->setMsg('body', $msgs[$key]);        
        $msg->assignVars('files_num1', count($files_total));
        $msg->assignVars('files_num2', count($files_admissible));
        $msg->assignVars('directory', $directory);
        $msg->assignVars('workflow', $workflow_title);        
        $msg->assignVars('status', $status_title);        
        
        $tpl->tplAssign('hint_msg', $msg->get());
        
        
        unset($obj->properties['entry_obj']);
        if (!$obj->get('parse_child')) {
            unset($obj->properties['parse_child']);
        }
        
        $rule_fields = http_build_hidden($obj->get());
        
        $file_fields = array();
        $file_fields['category'] = $eobj->getCategory();
        $file_fields['author_id'] = $eobj->get('author_id');
        $file_fields['file_active'] = $eobj->get('active');
        $file_fields['custom'] = $eobj->getCustom();
        $file_fields['private'] = $eobj->get('private');
        $file_fields['role_read'] = $eobj->getRoleRead();
        $file_fields['role_write'] = $eobj->getRoleWrite();
        
        $schedule = $eobj->getSchedule();
        foreach ($schedule as $k => $v) {
            $file_fields['schedule_on'][$k] = 1;
            $file_fields['schedule'][$k] = $v;
        }
        
        $file_fields = http_build_hidden($file_fields);
        
        
        $vars = $this->setCommonFormVars($obj);
        $vars['action_link'] = str_replace('&amp;back=1', '', $vars['action_link']);
        $vars['action_link'] .= '&back=1';
        $vars['hidden_fields'] = $rule_fields . $file_fields;  
        $tpl->tplAssign($vars);
       
        
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>