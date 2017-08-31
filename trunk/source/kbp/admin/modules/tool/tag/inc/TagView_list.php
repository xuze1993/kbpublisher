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

class TagView_list extends AppView
{
    
    var $template = 'list.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('random_msg.ini');
    
        $tpl = new tplTemplatez($this->template_dir . $this->template);
		
        $tpl->tplAssign('msg', $this->getShowMsg2($manager));
				
        // bulk
        $manager->bulk_manager = new TagModelBulk();
        if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv)) {
            $tpl->tplSetNeededGlobal('bulk');
            $tpl->tplAssign('footer', $this->controller->getView($obj, $manager, 'TagView_bulk', $this));
        }
                   
        // filter sql
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params['where']);
        $manager->setSqlParamsSelect($params['select']);
        $manager->setSqlParamsFrom($params['from']);

        // header generate
        $count = (isset($params['count'])) ? $params['count'] : $manager->getRecordsSql();
        $bp = &$this->pageByPage($manager->limit, $count);
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager)));
        
        // sort generate
        $sort = &$this->getSort();
        $psort = (isset($params['sort'])) ? $params['sort'] : $sort->getSql();
        $manager->setSqlParamsOrder($psort);
        
        // get records
        $offset = (isset($params['offset'])) ? $params['offset'] : $bp->offset;
        $rows = $this->stripVars($manager->getRecords($bp->limit, $offset));
        $ids = $manager->getValuesString($rows, 'id');
        
        // attached to entries
        $entries_num = ($ids) ? $manager->getReferencedEntriesNum($ids) : array();
        $entry_type_msg = AppMsg::getMsg('ranges_msg.ini', false, 'record_type');
        
        // list records
        foreach($rows as $row) {
            $obj->set($row);
            
            $row['entries_num'] = '--';
            $row['entries_num_tip'] = '';
            $tip_str = '<b>%s</b>: <a href=\'%s\'>%d</a>';
            
            $in_use = isset($entries_num[$row['id']]);
            
            if($in_use) {
                
                $tpl->tplSetNeeded('row/entry_num_tip');
                $entries_num_tip = array();
                foreach ($entries_num[$row['id']] as $entry_type => $entry_num) {
                    
                    $msg_key = $manager->record_type[$entry_type];
                    $more = array('filter[q]'=>'tag:'.$row['title']);
                    
                    $url_params = $manager->entry_type_to_url[$entry_type];
                    $link = $this->getLink($url_params[0], $url_params[1], false, false, $more);
                    
                    $entries_num_tip[] = sprintf($tip_str, $entry_type_msg[$msg_key], $link, $entry_num);  
                }
                
                $row['entries_num'] = array_sum($entries_num[$row['id']]);
                $row['entries_num_tip'] = implode('<br/>', $entries_num_tip);
            }

            $row['date_posted_formatted'] = $this->getFormatedDate($row['date_posted']);
            $row['date_posted_formatted_full'] = $this->getFormatedDate($row['date_posted'], 'datetime');

            $tpl->tplAssign($this->getViewListVarsJs($obj->get('id'), $obj->get('active')));
            
            $tpl->tplParse($row, 'row');
        }
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
        
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);    
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);    
        
        $sort->setSortItem('tag_msg',  'title', 'title',   $this->msg['tag_msg']);
        $sort->setSortItem('date_added_msg',  'dp', 'date_posted',   $this->msg['date_added_msg'], 2);
        
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
        
        //status
        @$status = $values['s'];
        $range = array(
            'all'=> '__',
               1 => $this->msg['status_visible_msg'],
               0 => $this->msg['status_hidden_msg']
              );
        
        $select->setRange($range);
        $tpl->tplAssign('status_select', $select->select($status));
                
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
                
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql(&$manager) {
        
        // filter
        $mysql = array();
        $sphinx = array();
        @$values = $_GET['filter'];
        
        
        // status
        @$v = $values['s'];
        if($v != 'all' && isset($values['s'])) {
            $v = (int) $v;
            $mysql['where'][] = "AND active = '$v'";
            $sphinx['where'][] = "AND active = $v";
        }        
        
        // search str
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);
            
            if($ret = $this->isSpecialSearchStr($v)) {
                 
                if($sql = $this->getSpecialSearchSql($manager, $ret, $v, 'id')) {
                    $mysql['where'][] = $sql['where'];
                    if(isset($sql['from'])) {
                        $mysql['from'][] = $sql['from'];
                    }    
                }
                
            } else {
                $v = addslashes(stripslashes($v));
                $mysql['where'][]  = "AND (title LIKE '%{$v}%')";
                
                $sphinx['match'][] = $v;
            }        
        }
        
        $options = array('index' => 'tag', 'id_field' => 'id');
        $arr = $this->parseFilterSql($manager, $values['q'], $mysql, $sphinx, $options);
        // echo '<pre>', print_r($arr, 1), '</pre>';
        
        return $arr;
    }
	
	
	function getShowMsg2($manager) {
	    
        @$key = $_GET['show_msg2'];
        if ($key == 'note_remove_tag_bulk') {
            $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
            $msgs = AppMsg::parseMsgsMultiIni($file);
            $msg['title'] = $msgs['title_remove_tags_bulk'];
            $msg['body'] = $msgs['note_remove_tag_bulk'];
            return BoxMsg::factory('error', $msg);            
        
        } 
        
        // elseif($manager->isTagUpdateTask()) {
        //     
        //     $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
        //     $msgs = AppMsg::parseMsgsMultiIni($file);
        //     // $msg['title'] = $msgs['title_remove_tags_bulk'];
        //     $msg['body'] = $msgs['note_remove_tag_bulk'];
        //     return BoxMsg::factory('hint', $msg);                        
        // }
    }
 
}
?>