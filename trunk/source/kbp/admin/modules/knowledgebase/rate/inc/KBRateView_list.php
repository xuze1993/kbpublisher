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


class KBRateView_list extends AppView
{
    
    var $tmpl = 'list.html';

    
    function execute(&$obj, &$manager) {

        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');
        $this->escapeMsg(array('sure_delete_entry_comment_msg'));
        

        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        // bulk
        $manager->bulk_manager = new KBRateModelBulk();
        if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv)) {
            $tpl->tplSetNeededGlobal('bulk');
            $tpl->tplAssign('footer', $this->controller->getView($obj, $manager, 'KBRateView_bulk', $this));
        }
    
        // filter sql        
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params['where']);
        $manager->setSqlParamsSelect($params['select']);    
        
        // header generate
        $count = (isset($params['count'])) ? $params['count'] : $manager->getRecordsSql();
        $bp = &$this->pageByPage($manager->limit, $count);
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager), false));
        
        // sort generate
        $sort = &$this->getSort();
        $psort = (isset($params['sort'])) ? $params['sort'] : $sort->getSql();
        $manager->setSqlParamsOrder($psort);
        
        // get records
        $offset = (isset($params['offset'])) ? $params['offset'] : $bp->offset;
        $rows = $this->stripVars($manager->getRecords($bp->limit, $offset));
        
        // comments num
        if($rows) {
            $entry_ids = $manager->getValuesString($rows, 'entry_id');
            $num_comment = $manager->getCountCommentsPerEntry($entry_ids);
        }
        
        // status_msg
        $status = $manager->getEntryStatusData();        
        
        $client_controller = &$this->controller->getClientController();
                        
        
        // list records
        foreach($rows as $row) {
            
            $obj->set($row);
            $obj->set('comment', nl2br($obj->get('comment')));
            $tpl->tplAssign('username', $row['username']);        
            $tpl->tplAssign('r_email', $row['r_email']);
            
            $tpl->tplAssign('title', $row['title']);
            $tpl->tplAssign('short_title', $this->getSubstringStrip($row['title'], 50));
            $tpl->tplAssign('num_comment', $num_comment[$row['entry_id']]);
            $tpl->tplAssign('status', $status[$row['active']]['title']);
            $tpl->tplAssign('color', $status[$row['active']]['color']);
            
            $formatted_date = $this->getFormatedDate($row['date_posted']);
            $tpl->tplAssign('formatted_date', $formatted_date);

            $interval_date = $this->getTimeInterval($row['date_posted'], true);
            $tpl->tplAssign('interval_date', $interval_date);
            
            
            // filter link
            $more = array('entry_id' => $obj->get('entry_id'));
            $tpl->tplAssign('filter_link', $this->getActionLink('entry', false, $more));
            
            
            // actions/links
            $more = array(
                'id'=>$obj->get('entry_id'),
                'referer' => WebUtil::serialize_url($this->controller->getCommonLink()));
            $links['entry_detail_link'] = $this->controller->getLink('knowledgebase', 'kb_entry', false, 'detail', $more);
            
            $actions = $this->getListActions($obj, $links);
            
            $tpl->tplAssign($this->getViewListVarsJs($obj->get('id'), $obj->get('active'), 1, $actions));
            
            $tpl->tplParse(array_merge($obj->get(), $this->msg), 'row');
        }
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function getListActions($obj, $links) {
        $actions = array('update', 'delete');
        
        if($this->priv->isPriv('select', 'kb_entry')) {
            $actions['detail'] = array(
                'msg'  => $this->msg['detail_msg'], 
                'link' => $links['entry_detail_link'], 
                'img'  => '');
        }
        
        return $actions;
    }

    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(2);
        $sort->setDefaultSortItem('date', 2);        
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        $sort->setSortItem('date_posted_msg', 'date', 'c_date_posted', $this->msg['date_posted_msg']);
        $sort->setSortItem('status_msg','status', 'active', $this->msg['status_msg']);
        
        // search
        if(!empty($_GET['filter']['q']) && empty($_GET['sort'])) {
            $f = $_GET['filter']['q'];
            if(!$this->isSpecialSearchStr($f)) {
                $sort->resetDefaultSortItem();
                $sort->setSortItem('search', 'search', 'score', '', 2);
            }
        }

        return $sort;
    }        
    
    
    function getFilter($manager) {

        @$values = $_GET['filter'];
        if(isset($values['q'])) {
            $values['q'] = RequestDataUtil::stripVars($values['q'], array(), true);
            $values['q'] = trim($values['q']);
        }

        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
    
        $select = new FormSelect();
        $select->select_tag = false;
        
        // status
        $v = (!empty($values['s'])) ? $values['s'] : 'all';
        $extra_range = array('all'=>'__');
        $select->setRange($manager->getListSelectRange(false), $extra_range);
        $tpl->tplAssign('status_select', $select->select($v));        
        
        
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
        
        @$v = $values['s'];
        if($v != 'all' && isset($values['s'])) {
            $status = (int) $v;
            $mysql['where'][] = "AND c.active = '$status'";
            $sphinx['where'][] = "AND active = $status";
        }
        
        // search str
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);

            if($ret = $this->isSpecialSearchStr($v)) {
                
                if($ret['rule'] == 'id') {
                    $mysql['where'][] = sprintf("AND c.id = '%d'", $ret['val']);
                
                } elseif($ret['rule'] == 'ids') {
                    $mysql['where'][] = sprintf("AND c.id IN(%s)", $ret['val']);
                }
            
            } else {
                $v = addslashes(stripslashes($v));
                $mysql['select'][] = "MATCH (c.comment) AGAINST ('$v') AS score";
                $mysql['where'][]  = "AND MATCH (c.comment) AGAINST ('$v' IN BOOLEAN MODE)";
                
                $sphinx['match'][] = $v;
            }
        }        
        
        
        $options = array('index' => 'ratingFeedback', 'id_field' => 'c.id');
        $arr = $this->parseFilterSql($manager, $values['q'], $mysql, $sphinx, $options);
        // echo '<pre>', print_r($arr, 1), '</pre>';
        
        return $arr;
    }    
}
?>