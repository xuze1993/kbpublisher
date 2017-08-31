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

require_once 'core/common/CommonCustomFieldView.php';


class FeedbackView_list extends AppView
{
    
    var $template = 'list.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        $this->addMsg('common_msg.ini', 'knowledgebase');
        
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        // bulk
        $manager->bulk_manager = new FeedbackModelBulk();
        if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv)) {
            $tpl->tplSetNeededGlobal('bulk');
            $tpl->tplAssign('footer', $this->controller->getView($obj, $manager, 'FeedbackView_bulk', $this));
        }
        
        // filter sql        
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params['where']);
        $manager->setSqlParamsSelect($params['select']);
        $manager->setSqlParamsFrom($params['from']);
        $manager->setSqlParamsJoin($params['join']);

        // header generate
        $count = (isset($params['count'])) ? $params['count'] : $manager->getCountRecordsSql();
        $bp = &$this->pageByPage($manager->limit, $count);
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager), false));
        
        // sort generate
        $sort = &$this->getSort();
        $psort = (isset($params['sort'])) ? $params['sort'] : $sort->getSql();
        $manager->setSqlParamsOrder($psort);
        
        // get records
        $offset = (isset($params['offset'])) ? $params['offset'] : $bp->offset;
        $rows = $this->stripVars($manager->getRecords($bp->limit, $offset));
        
        // subjects
        $subject = $manager->getSubjectSelectRange();
        
        // list records
        foreach($rows as $row) {
            
            $obj->set($row);
            
            $num = 250;
            $more = (strlen($obj->get('question')) > $num);
            if($more) {
                $obj->set('question', substr($obj->get('question'), 0, $num) . '...');
            }
            
            
            $attach_num = ($obj->get('attachment')) ? count(explode(';', $obj->get('attachment'))) : '-';
            $tpl->tplAssign('attachment_num', $attach_num);
            $tpl->tplAssign('username', $row['username']);
            $tpl->tplAssign('formatted_date', $this->getFormatedDate($row['ts']));
            
            $more = array('filter[q]' => sprintf('user_id:%d', $row['user_id']));
            $tpl->tplAssign('userfilter_link', $this->getLink('this', 'this', null, null, $more));
            
            $interval_date = $this->getTimeInterval($row['ts'], true);
            $tpl->tplAssign('interval_date', $interval_date);
            
            $subj = (isset($subject[$row['subject_id']])) ? $subject[$row['subject_id']] : '';
            $tpl->tplAssign('subject', $subj);
            
            
            // actions/links
            $links = array();
            $links['email_link'] = $this->getActionLink('answer', $obj->get('id'));
            
            $more_param = array('question_id' => $obj->get('id'), 
                            'referer' => WebUtil::serialize_url($this->controller->getCommonLink()));
            $links['update_link'] = $this->controller->getLink('knowledgebase', 'kb_entry', false, 'question', $more_param);
            
            $active_var = ($obj->get('answered') == 0) ? '1' : '0';
            $links['status_answered'] = $this->getActionLink('answer_status', $obj->get('id')) . '&status=' . $active_var;
            
            $active_var = ($obj->get('placed') == 0) ? '1' : '0';
            $links['status_placed'] = $this->getActionLink('place_status', $obj->get('id')) . '&status=' . $active_var;
            
            
            $actions = $this->getListActions($obj, $links);
            $tpl->tplAssign($this->getViewListVarsJsCustom($obj->get(), $links, $actions));
            
            $tpl->tplParse(array_merge($obj->get(), $this->msg), 'row');
        }
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getViewListVarsJsCustom($entry, $links, $actions = array()) {
        
        // another title for uppdate button
        $msg = $this->msg['answer_msg'] . ' / ' . $this->msg['move_to_entries_msg'];
        $this->msg['update_msg'] = $msg;
        
        $this->inactive_style = 'color: #000000;';
        
        $row = $this->getViewListVarsJs($entry['id'], $entry['answered'], true, $actions);
        
        // for dblclick
        // if(!empty($actions['place_to_kb'])) {
            // $row['update_link'] = $actions['place_to_kb']['link'];
        // }
        
        if(!$this->priv->isPriv('status')) {
            
            $statuses = array('answered', 'placed');
            foreach ($statuses as $status) {
                if($entry[$status] == 0) {
                    $row[$status . '_img'] = $this->getImgLink('', 'active_d_0', '');
                } else {
                    $row[$status . '_img'] = $this->getImgLink('', 'active_d_1', '');
                }
            }

        } else {
            $active_img = ($entry['answered'] == 0) ? 'active_0' : 'active_1';
            $row['answered_img'] = $this->getImgLink($links['status_answered'], $active_img, 
                                                $this->msg['set_status_msg'], $this->msg['sure_status_msg']);
            
            $active_img = ($entry['placed'] == 0) ? 'active_0' : 'active_1';
            $row['placed_img'] = $this->getImgLink($links['status_placed'], $active_img, 
                                                $this->msg['set_status_msg'], $this->msg['sure_status_msg']);  
        }

        return $row;
    }
    
    
    function getListActions($obj, $links) {
        
        $actions = array('delete');
        
        if ($obj->get('answered')) {
            $actions[] = 'detail';
        }
        
        if($this->priv->isPriv('insert', 'kb_entry')) {
            $actions['place_to_kb'] = array(
                'msg'  => $this->msg['place_to_kb_msg'], 
                'link' => $links['update_link']
                );
        }
        
        $status_msg_str = '%s (%s)';
        
        if($this->priv->isPriv('status')) {
            
            $actions['status_answered'] = array(
                'link' => $links['status_answered'],
                'msg'  => sprintf($status_msg_str, $this->msg['set_status_msg'], $this->msg['answered_status_msg'])
                );
                
            $actions['status_placed'] = array(
                'link' => $links['status_placed'], 
                'msg'  => sprintf($status_msg_str, $this->msg['set_status_msg'], $this->msg['placed_status_msg'])
                );
        }
        
        if($this->priv->isPriv('update')) {
            $actions['email'] = array(
                'msg'  => $this->msg['answer_msg'], 
                'link' => $links['email_link']
                );  
        }
            
        return $actions;
    }
    
    
    // reassign 
    function getActionsOrder() {
        $order = array(
            'status', 'detail',                 'delim',
            'email', 'place_to_kb',             'delim',
            'status_answered', 'status_placed', 'delim',
            'update', 'delete');
            
        return $order;
    }
    
    
    function &getSort() {

        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(2);
        $sort->setDefaultSortItem('date', 2);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);        
        
        $sort->setSortItem('subject_msg', 'subj', 'subject_id', $this->msg['subject_msg']);
        $sort->setSortItem('email_msg', 'email', 'email', $this->msg['email_msg']);
        //$sort->setSortItem('username_msg', 'username', 'user_id', $this->msg['username_msg']);
        //$sort->setSortItem('attachment_num_msg', 'attachment', 'attachment', array($this->msg['attachment_num_msg'], 6));
        $sort->setSortItem('answered_status_msg','status', 'answered', $this->msg['answered_status_msg']);
        $sort->setSortItem('date_posted_msg', 'date', 'date_posted', $this->msg['date_posted_msg']);
        $sort->setSortItem('placed_status_msg', 'placed', 'placed', $this->msg['placed_status_msg']);
        
        // search
        if(!empty($_GET['filter']['q']) && empty($_GET['sort'])) {
            $f = $_GET['filter']['q'];
            if(!$this->isSpecialSearchStr($f)) {
                $sort->resetDefaultSortItem();
                $sort->setSortItem('search', 'search', 'score', '', 2);
            }
        }        
        
        //$sort->getSql();
        //$sort->toHtml()
        return $sort;
    }    
    
    
    function getFilter($manager) {

        @$values = $_GET['filter'];
        if(isset($values['q'])) {
            $values['q'] = RequestDataUtil::stripVars($values['q'], array(), true);
            $values['q'] = trim($values['q']);
        }

        //xajax
        $xobj = null;
        $ajax = &$this->getAjax($xobj, $manager);
        $xajax = &$ajax->getAjax();


        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
    
        $select = new FormSelect();
        $select->select_tag = false;
        
        // category
        $select->setRange($manager->getSubjectSelectRange(), array('all'=>'__'));
        @$subject_id = $values['c'];
        $tpl->tplAssign('subject_select', $select->select($subject_id));
        
        
        // status
        $range = AppMsg::getMsgs('ranges_msg.ini', false, 'user_questions');
        if(isset($range['all'])) { unset($range['all']); }
        $select->setRange($range, array('all'=>'__'));        
        @$status = $values['s'];
        $tpl->tplAssign('status_select', $select->select($status));        
        
        // custom 
        CommonCustomFieldView::parseAdvancedSearch($tpl, $manager, $values, $this->msg);
        $xajax->registerFunction(array('parseAdvancedSearch', $this, 'ajaxParseAdvancedSearch'));        
        
                
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql($manager) {
        
        // filter
        $mysql = array();
        $sphinx = array();
        @$values = $_GET['filter'];        
        
        @$v = $values['c'];
        if($v != 'all' && !empty($v)) {
            $id = (int) $v;
            $mysql['where'][] = "AND subject_id = '{$id}'";
            $sphinx['where'][] = "AND subject_id = $id";
        }
        
        
        @$v = $values['s'];
        if($v && $v != 'all') {
            
            if($v == 'answered' || $v == 'not_answered') {
                $val = ($v == 'answered') ? 1 : 0;
                $f = 'answered';
                
            } else {
                $val = ($v == 'placed') ? 1 : 0;
                $f = 'placed';
            }
            
            $mysql['where'][] = "AND $f = $val";
            $sphinx['where'][] = "AND $f = $val";
        }
        
        // search str
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);

            if($ret = $this->isSpecialSearchStr($v)) {
                
                // id, ids
                if($sql = $this->getSpecialSearchSql($manager, $ret, $v)) {
                    $mysql['where'][] = $sql['where'];
                    if(isset($sql['from'])) {
                        $mysql['from'][] = $sql['from'];
                    }                
                
                } elseif ($ret['rule'] == 'user_id') {
                    $mysql['where'][] = sprintf("AND e.user_id=%d", $ret['val']);
                    if($ret['val'] == 0) {
                        $mysql['where'][] = sprintf("OR e.user_id IS NULL");
                    }

                } elseif ($ret['rule'] == 'admin_id') {
                    $mysql['where'][] = sprintf("AND e.admin_id=%d", $ret['val']);

                } elseif ($ret['rule'] == 'username') {
                    $mysql['where'][] = sprintf("AND u.username ='%s'", $ret['val']);
                }
            
            } else {
                $v = addslashes(stripslashes($v));
                $mysql['select'][] = "MATCH (e.title, e.question, e.answer) AGAINST ('$v') AS score";
                $mysql['where'][]  = "AND MATCH (e.title, e.question, e.answer) AGAINST ('$v' IN BOOLEAN MODE)";
                
                $sphinx['match'][] = $v;
            }
        }
        
        // custom 
        @$v = $values['custom'];
        if($v) {
            $v = RequestDataUtil::stripVars($v);
            $sql = $manager->cf_manager->getCustomFieldSql($v);
            $mysql['where'][] = 'AND ' . $sql['where'];
            $mysql['join'][] = $sql['join'];
            
            $sql = $manager->cf_manager->getCustomFieldSphinxQL($v);
            if (!empty($sql['where'])) {
                $sphinx['where'][] = 'AND ' . $sql['where'];
            }
            $sphinx['select'][] = $sql['select'];
            $sphinx['match'][] = $sql['match'];
        }
        
        @$v = $values['q'];
        $options = array('index' => 'feedback');
        $arr = $this->parseFilterSql($manager, $values['q'], $mysql, $sphinx, $options);
        // echo '<pre>', print_r($arr, 1), '</pre>';
        
        return $arr;
    }
    
    
    // if some special search used
    function isSpecialSearchStr($str) {
        
        if($ret = parent::isSpecialSearchStr($str)) {
            return $ret;
        }
        
        $search['user_id'] = "#^user(?:_id)?:(\d+)$#";
        $search['admin_id'] = "#^admin(?:_id)?:(\d+)$#";
        $search['username'] = "#^username:(\w+)$#";
        
        return $this->parseSpecialSearchStr($str, $search);
    }    
    
    
    // Filter // -----------

    function ajaxParseAdvancedSearch($show) {
        return CommonCustomFieldView::ajaxParseAdvancedSearch($show, $this->manager, $this->msg);
    }
    
}
?>