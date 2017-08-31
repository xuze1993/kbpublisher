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


class KBEntryHistoryView_diff extends AppView
{
    
    var $template = 'form_history_diff.html';
    
    
    function execute(&$obj, &$manager, $data) {
    
        list($revisions, $entry, $eobj, $emanager) = $data;
        $left = $revisions['left'];
        $right = $revisions['right'];
        
    
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        // tabs
        $tpl->tplAssign('menu_block', KBEntryView_common::getEntryMenu($eobj, $emanager, $this));
        
        // diff
        list($diff_new, $diff_old) = Diff2::diff_html($left['body'], $right['body'], false);
        $tpl->tplAssign('diff_old', $diff_old);
        $tpl->tplAssign('diff_new', $diff_new);
        
        $tpl->tplAssign('left_comment', $left['comment']);
        $tpl->tplAssign('right_comment', $right['comment']);
        
        // updated by
        $updated_str = '%s %s %s';
        
        $user = $manager->getUserById($left['entry_updater_id']);
        $updater = PersonHelper::getShortName($user);
        $date = $this->getFormatedDate($left['entry_date_updated'], 'datetime');
        $updated = sprintf($updated_str, $date, $this->msg['by_user_msg'], $updater);
        $tpl->tplAssign('left_updated_by', $updated);
        
        $user = $manager->getUserById($right['entry_updater_id']);
        $updater = PersonHelper::getShortName($user);
        $date = $this->getFormatedDate($right['entry_date_updated'], 'datetime');
        $updated = sprintf($updated_str, $date, $this->msg['by_user_msg'], $updater);
        $tpl->tplAssign('right_updated_by', $updated);
        
        // revision forms
        $revision_url_params = array(
            'module' => 'knowledgebase',
            'page' => 'kb_entry',
            'action' => 'diff',
            'id' => $eobj->get('id')
            );
        
        $params = $revision_url_params;
        $params['vnum'] = $right['revision_num'];
        $tpl->tplAssign('left_hidden_fields', http_build_hidden($params, true));
        
        $params = $revision_url_params;
        $params['vnum2'] = $left['revision_num'];
        $tpl->tplAssign('right_hidden_fields', http_build_hidden($params, true));        
        
        $manager->setSqlParams(sprintf("AND entry_id = %d", $eobj->get('id')), null, true);
        $manager->setSqlParamsOrder('ORDER BY date_posted');
        $rows = $manager->getRecords();
        
        $select = new FormSelect();
        $select->select_tag = false;
        
        $range = array();
        foreach ($rows as $row) {
            $num = $row['revision_num'];
            $date = $this->getFormatedDate($row['entry_date_updated'], 'datetime');
            $range[$num] = sprintf('%s # %d (%s)', $this->msg['revision_msg'], $num, $date);
        }
        
        $date = $this->getFormatedDate($eobj->get('date_updated'), 'datetime');
        $range['live'] = sprintf('%s (%s)', $this->msg['live_revision_msg'], $date);
        
        $select->setRange($range);
        $tpl->tplAssign('left_select', $select->select($left['revision_num']));
        
        unset($range['live']);
        $select->setRange($range);
        $tpl->tplAssign('right_select', $select->select($right['revision_num']));
                        

        CommonEntryView::parseInfoBlock($tpl, $eobj, $this);
		$tpl->tplAssign('entry_id', $eobj->get('id'));
        $publish_status_ids = $emanager->getEntryStatusPublished('article_status');
        
        $a = array();
        $client_controller = &$this->controller->getClientController();
        if(in_array($eobj->get('active'), $publish_status_ids)) {
            $link = $client_controller->getLink('entry', $eobj->get('category_id'), $eobj->get('id'));
            $tpl->tplAssign('entry_link', $link);
            $tpl->tplSetNeeded('/entry_link');
        }
        

        // form vars
        $vars = $this->setCommonFormVars($eobj);
        $vars['action_link'] = str_replace('diff', 'rollback', $vars['action_link']);
        
        $more = array('action'=>'history','id'=>$eobj->get('id'));
        $vars['cancel_link'] = $vars['cancel_link'] . '&' . http_build_query($more);        


        // bredcrumb
        $top_menu_msg = AppMsg::getMenuMsgs('top');
        $link = $this->controller->getCommonLink();
        
        $nav = array();
        $nav[2]['item'] = sprintf('%s', $this->msg['history_msg']);
        $nav[2]['link'] = $vars['cancel_link'];
        $nav[3]['item'] = $this->msg['viewdiff_msg'];
        
        $tpl->tplAssign('nav', $this->getBreadCrumbNavigation($nav));


        // revision_update_to_msg
        $more = array('vnum' => $right['revision_num']);
        $download_link = $this->getActionLink('file', $eobj->get('id'), $more);
        $tpl->tplAssign('download_link', $download_link);
        
        $preview_link = $this->getActionLink('hpreview', $eobj->get('id'), $more);
        $tpl->tplAssign('preview_link', $preview_link); 
        
        $more = array('id' => $eobj->get('id'), 'vnum' => $right['revision_num']);
        $update_link = $this->getLink('knowledgebase', 'kb_entry', false, 'update', $more);
        $tpl->tplAssign('update_link', $update_link); 
        
        $msg_ = str_replace('{num}', $right['revision_num'], $this->msg['preview_revision_msg']);
        $this->msg['preview_revision_msg'] =  $msg_;
        
        $msg_ = str_replace('{num}', $right['revision_num'], $this->msg['rollback_to_msg']);
        $this->msg['rollback_to_msg'] =  $msg_;

        $msg_ = str_replace('{num}', $right['revision_num'], $this->msg['revision_update_to_msg']);
        $this->msg['revision_update_to_msg'] =  $msg_;


        $tpl->tplAssign($vars);        
        // $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
                
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
}
?>