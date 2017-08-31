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


class KBDraftView_list extends AppView
{
        
    var $template = 'list.html';
    var $template_popup = 'list_popup.html';
    
    
    function execute(&$obj, &$manager, $emanager) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');
        
        $tmpl = ($this->controller->getMoreParam('popup')) ? $this->template_popup : $this->template;
        
        $tpl = new tplTemplatez($this->template_dir . $tmpl);
        
        $show_msg2 = $this->getShowMsg2();
        $tpl->tplAssign('msg', $show_msg2);
        
        // draft message
        if ($this->setting['entry_autosave']) {
            if($manager->isAutosaved(0, '2011-01-01')) {
                $tpl->tplAssign('msg', KBEntryView_common::getDraftsMessage($this, 'kb_draft_autosave'));
            }
        }
        
        // bulk
        $manager->bulk_manager = new KBDraftModelBulk;
        if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv)) {
            $tpl->tplSetNeededGlobal('bulk');
            $tpl->tplAssign('footer', $this->controller->getView($obj, $manager, 'KBDraftView_bulk', $this));
        }
        
        // all assignees in drafts
        $assignee_all = array();
        $assignee_all_ids = $manager->getAssigneeList();
        if(!empty($assignee_all_ids)) {
            $assignee_all = $manager->getUser(implode(',', $assignee_all_ids), false);
        }
        
        // filter sql
        $params = $this->getFilterSql($manager, $emanager);
        $manager->setSqlParams($params['where']);
        $manager->setSqlParamsSelect($params['select']);
        $manager->setSqlParamsFrom($params['from']);
        
        // $manager->setSqlParams('AND d.entry_type = ' . $emanager->entry_type);
        $manager->setSqlParams('AND d.entry_type = ' . $manager->from_entry_type);
        
        // header generate
        $count = (isset($params['count'])) ? $params['count'] : $manager->getCountRecords();
        $bp = &$this->pageByPage($manager->limit, $count);
        
        $add_button = ($this->controller->getMoreParam('popup') == 'text') ? false : true;
        $tpl->tplAssign('header', 
            $this->commonHeaderList($bp->nav, $this->getFilter($manager, $assignee_all), $add_button));
            
        // sort generate
        $sort = &$this->getSort();
        $psort = (isset($params['sort'])) ? $params['sort'] : $sort->getSql();
        $manager->setSqlParamsOrder($psort);
        
        // get records
        $offset = (isset($params['offset'])) ? $params['offset'] : $bp->offset;
        $rows = $manager->getRecords($bp->limit, $offset);
        $rows = $this->stripVars($rows, array('entry_obj', 'workflow_action'));
        
        $step_ids = $manager->getValuesArray($rows, 'last_event_id');
        $step_ids = array_filter($step_ids);
        $entry_assignees = ($step_ids) ? $manager->getAssigneeByStepIds($step_ids) : array();
        $entry_assignees = $this->stripVars($entry_assignees);
        
        $status = $manager->getDraftStatusData();
        $tooltip_str = 'class="_tooltip" title="%s"';
        
        $tpl->tplAssign('type', 'Article');

        foreach($rows as $row) {
            // echo '<pre>', print_r($rows, 1), '</pre>';
            $obj->set($row);
            
            $formated_date_posted = $this->getFormatedDate($row['date_posted'], 'datetime');
            $tpl->tplAssign('formated_date_posted', $formated_date_posted);

            $interval_date_posted = $this->getTimeInterval($row['date_posted']);
            $tpl->tplAssign('interval_date_posted', $interval_date_posted);
            
            $formated_date_updated = '--';
            $interval_date_updated = '';
            $ddiff = strtotime($row['date_updated']) - strtotime($row['date_posted']);
            if($ddiff > 60) {
                $formated_date_updated = $this->getFormatedDate($row['date_updated'], 'datetime');
                $interval_date_updated = $this->getTimeInterval($row['date_updated']);        
            }
            
            $tpl->tplAssign('formated_date_updated', $formated_date_updated);
            $tpl->tplAssign('interval_date_updated', $interval_date_updated);
            
            // author 
            $row['author'] = '--';
            if (!empty($row['first_name'])) {
                $name = PersonHelper::getShortName($row);
                if($row['author_id'] != AuthPriv::getUserId()) {
                    $more = array('id' => $row['author_id']);
                    $link = $this->getLink('users', 'user', false, 'detail', $more);
                    $row['author'] = sprintf('<a href="%s">%s</a>', $link, $name);   
                } else {
                    $row['author'] = $name;   
                }
            }
            
            // title
            $title = $obj->get('title');
            $tpl->tplAssign('short_title', $this->getSubstringStrip($title, 30));
            
            // article id
            $row['entry_id'] = ($row['entry_id']) ? $row['entry_id'] : '--';
            
            // actions/links
            $links = array();
            $links['approval_link'] = $this->getActionLink('approval', $row['id']);
            
            // preview 
            $link = $this->getActionLink('preview', $obj->get('id'));    
            $link = sprintf("javascript:PopupManager.create('%s', 'r', 'r', 2);", $link);
            $links['preview_link'] = $link;
            
            
            $assignees = array();
            $row['assignee_tip'] = '';
            $row['step'] = '--';
            $status_id = 1;
            $being_approved = $manager->isBeingApprovedByRow($row);
            if (!empty($row['last_event_id'])) { // being approved
                $status_id = $row['last_event_action'] + 1;
                
                // tooltip
                $tooltip = array();
                if (!empty($entry_assignees[$row['last_event_id']])) {
                    $assignees = $entry_assignees[$row['last_event_id']];
                    
                    foreach($assignees as $assignee_id) {
                        if(isset($assignee_all[$assignee_id])) {
                            $tooltip[] = PersonHelper::getEasyName($assignee_all[$assignee_id]); 
                        }
                    }
                    
                    $row['assignee_tip'] = sprintf($tooltip_str, $this->stripVars(implode('<br/>', $tooltip)));
                }
                
                if ($being_approved) {
                    $actions = unserialize($row['workflow_action']);
                    $row['step'] = sprintf('%d / %d', $row['step_num']-1, count($actions));
                }
            }
            
            $tpl->tplAssign('status', $status[$status_id]['title']);
            $tpl->tplAssign('status_color', $status[$status_id]['color']);
            $tpl->tplAssign('assignee_num', ($assignees) ? count($assignees) : '--');
            
            
            $actions = $this->getListActions($obj, $links, $manager, $being_approved, $assignees);
            $tpl->tplAssign($this->getViewListVarsJsCustom($obj->get(), $actions, $manager));
            
            $tpl->tplParse($row, 'row');
        }
        
        if ($this->controller->getMoreParam('popup')) {
            $menu_msg = AppMsg::getMenuMsgs('knowledgebase');
            $tpl->tplAssign('popup_title', $menu_msg['kb_draft']);
        }
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
        $tpl->tplAssign($this->parseTitle());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function parseTitle() {
        $values = array();
        $values['step_stat_msg'] = $this->shortenTitle($this->msg['step_stat_msg'], 1);
        return $values;
    }
    
    
    function getViewListVarsJsCustom($entry, $actions, $manager) {
        
        $own_record = ($entry['author_id'] == $manager->user_id);     
        $row = $this->getViewListVarsJs($entry['id'], 1, $own_record, $actions);

        $row['preview_link'] = $actions['preview']['link'];
        
        // double click link
        if(isset($actions['approve'])) {
            $row['update_link'] = $actions['approve']['link'];
        }

        return $row;
    }
    
    
    function getListActions($obj, $links, $manager, $being_approved, $assignee) {
        
        $record_id = $obj->get('id');
        $own_record = ($obj->get('author_id') == $manager->user_id);
        
        $actions = array(
            'detail', 'update', 'delete',
            'preview' => array(
                'msg'  => $this->msg['preview_msg'],
                'link'  => $links['preview_link']
            )
        );
        
        // being approve
        if($being_approved) {
            if ($manager->isUserAllowedToApprove($assignee)) {
                $actions['approve'] = array(
                    'msg'  => $this->msg['review_msg'],
                    'link'  => $links['approval_link']);
            } else {
                unset($actions[array_search('update', $actions)]);
                unset($actions[array_search('delete', $actions)]);
            }    
        }
                 
        return $actions;
    }
    
    
    function getApproveActions() {
        
    }
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        $sort->setCustomDefaultOrder('date_posted', 1);
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        
        $sort->setSortItem('posted_msg', 'date_posted', 'd.date_posted', $this->msg['posted_msg']);
        $sort->setSortItem('updated_msg', 'date_updated', 'd.date_updated', $this->msg['updated_msg'], 2);
        $sort->setSortItem('entry_title_msg', 'title', 'd.title', $this->msg['entry_title_msg']);
        
        return $sort;
    }
    
    
    function getFilter($manager, $assignees) {

        @$values = $_GET['filter'];
        if(isset($values['q'])) {
            $values['q'] = RequestDataUtil::stripVars($values['q'], array(), true);
            $values['q'] = trim($values['q']);
        }
        
        $template_dir = APP_MODULE_DIR . 'knowledgebase/draft/template/';
        $tpl = new tplTemplatez($template_dir . 'form_filter.html');
        
        // status
        $select = new FormSelect();
        $select->select_tag = false;
        
        $range = $manager->getDraftStatusSelectRange();
        $select->setRange($range, array('all'=>'__'));
        @$status = $values['s'];
        $tpl->tplAssign('status_select', $select->select($status));
        
        
        // assignee
        $_assignees = array(
            0 => $this->msg['unassigned_msg']
        );
        
        foreach ($assignees as $id => $v) {
            $_assignees[$id] = PersonHelper::getEasyName($v);
        }
        
        $js_hash = array();
        $str = '{label: "%s", value: "%s"}';
        foreach(array_keys($_assignees) as $k) {
            $js_hash[] = sprintf($str, addslashes($_assignees[$k]), $k);
        }
   
        $js_hash = implode(",\n", $js_hash);         
        $tpl->tplAssign('assignees', $js_hash);
        
        if(isset($values['t']) && ($values['t'] !== '')) {
            $assignee_id = (int) $values['t'];
            if (!empty($_assignees[$assignee_id])) {
                $assignee_name = $this->stripVars($_assignees[$assignee_id]);
            
                $tpl->tplAssign('assignee_id', $assignee_id);
                $tpl->tplAssign('assignee_name', $assignee_name);
            }
        }
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
                
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql($manager, $emanager) {
        
        // filter
        $mysql = array();
        $sphinx = array();
        @$values = $_GET['filter'];
        
        
        // category roles
        $mysql['where'][] = 'AND ' . $manager->getDraftCategoryRolesSql($emanager);
        
        // status
        @$v = $values['s'];
        if($v != 'all' && isset($values['s'])) {
            switch ($v) {
                case 1:
                	$mysql['where'][] = 'AND dw1.id IS NULL';
                    $sphinx['where'][] = 'AND active = 0';
                	break;
                    
                case 2:
                	$mysql['where'][] = 'AND dw1.active = 1';
                    $sphinx['where'][] = 'AND active = 1';
                	break;
                    
                case 3:
                	$mysql['where'][] = 'AND dw1.active = 2';
                    $sphinx['where'][] = 'AND active = 2';
                	break;
            }
        }
        
        // assignee
        @$v = (!isset($values['t'])) ? 'all' : $values['t'];
        if($v != 'all') {
            if (is_numeric($v)) {
                if ($v == 0) {
                    $mysql['where'][] = 'AND (da.assignee_id IS NULL)';
                    
                    $sphinx['select'][] = 'LENGTH(assignee) as _assignee';
                    $sphinx['where'][] = 'AND _assignee = 0';
                    
                } else {
                    $mysql['where'][] = 'AND da.assignee_id = ' . $v;
                    $sphinx['where'][] = 'AND assignee = ' . $v; 
                }
            }
        }
        
        // search str
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);
            
            if($ret = $this->isSpecialSearchStr($v)) {
                 
                if($sql = $this->getSpecialSearchSqlCustom($manager, $emanager, $ret, $v)) {
                    $mysql['where'][] = $sql['where'];
                    if(isset($sql['from'])) {
                        $mysql['from'][] = $sql['from'];
                    }
                    
                } elseif ($ret['rule'] == 'fname') {
                    $fname = addslashes(stripslashes($ret['val']));
                    $fname = str_replace('*', '%', $fname);
                    $mysql['where'][] = sprintf("AND d.title LIKE '%s'", $fname);
                }
                
            } else {
                $v = addslashes(stripslashes($v));
                $mysql['where'][]  = "AND d.title LIKE '%{$v}%'";
                
                $sphinx['match'][] = $v;
            }
        }
        
        $index = ($manager->entry_type == 7) ? 'articleDraft' : 'fileDraft';
        $options = array('index' => $index, 'entry_private' => 1, 'id_field' => 'd.id', 'cat_private' => 'all');
        $arr = $this->parseFilterSql($manager, $v, $mysql, $sphinx, $options);
        // echo '<pre>', print_r($arr, 1), '</pre>';
        
        return $arr;
    }
    
    
    function isSpecialSearchStr($str) {
        
        if($ret = parent::isSpecialSearchStr($str)) {
            return $ret;
        }

        $search = array();
        $search['entry_id'] = "#^entry_id:(\s?[\d,\s?]+)$#";        
        $search['author'] = "#^author(?:_id)?:(\d+)$#";
        $search['approver'] = "#^approver:(\d+)$#";
        $search['fname'] = "#^fname:(.*?)$#";
        
        return $this->parseSpecialSearchStr($str, $search);
    }
    
    
    function getSpecialSearchSqlCustom($manager, $emanager, $ret, $string) {
        
        $arr = array();
        
        if($ret['rule'] == 'id') {
            $arr['where'] = sprintf("AND d.id = '%d'", $ret['val']);
        
        } elseif($ret['rule'] == 'ids') {
            $arr['where'] = sprintf("AND d.id IN(%s)", $ret['val']);
        
        } elseif($ret['rule'] == 'entry_id') {
            $arr['where'] = sprintf("AND d.entry_id = '%d'", $ret['val']);
        
        } elseif($ret['rule'] == 'author') {
            $arr['where'] = sprintf("AND d.author_id IN(%s)", $ret['val']);
        
        } elseif($ret['rule'] == 'updater') {
            $arr['where'] = sprintf("AND d.updater_id IN(%s)", $ret['val']);
        
        } elseif ($ret['rule'] == 'approver') {
            
            $rows = $manager->getAwaitingDrafts($emanager->entry_type, $ret['val'], false);
            
            if (!empty($rows)) {
                $ids = array();
                foreach ($rows as $row) {
                    $ids[] = $row['draft_id'];
                }
                
                $arr['where'] = sprintf("AND d.id IN(%s)", implode(',', $ids));
                
            } else {
                $arr['where'] = 'AND 0';
            }
        }
                   
        return $arr;
    }
    
    
    function getShowMsg2() {
        @$key = $_GET['show_msg2'];
        if($key == 'note_remove_draft_bulk') {
            $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
            $msgs = AppMsg::parseMsgsMultiIni($file);
            $msg['title'] = $msgs['title_remove_drafts_bulk'];
            $msg['body'] = $msgs['note_remove_draft_bulk'];
            return BoxMsg::factory('error', $msg); 
        
        }
    }
    
}
?>