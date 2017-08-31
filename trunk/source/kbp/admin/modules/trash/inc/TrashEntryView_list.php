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

class TrashEntryView_list extends AppView
{
        
    var $tmpl = 'list.html';
    
    
    function execute(&$obj, &$manager) {
    
        $this->addMsg('user_msg.ini');
    
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        // header generate
        $bp =& $this->pageByPage($manager->limit, $manager->getRecordsSql());
        
        // filter sql
        $manager->setSqlParams($this->getFilterSql($manager));
        
        
        // sort generate
        $sort = &$this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
        
        // get records
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        
        $entry_type = $manager->getEntryTypeSelectRange();
        
        $user_ids = $manager->getValuesString($rows, 'user_id');
        if (!empty($user_ids)) {
            $users = $manager->getUserByIds($user_ids);
        }
        
        
        // empty button
        $button = array();
        if($rows && $this->priv->isPriv('delete')) {
            $button[$this->msg['empty_trash_msg']] = 'javascript:emptyTrash();';
        }
        
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager), $button, false));
        $tpl->tplAssign('empty_link', $this->controller->_replaceArgSeparator($this->getActionLink('empty')));
        
        
        foreach($rows as $row) {
            $row['formatted_date'] = $this->getFormatedDate($row['date_deleted'], 'datetime');
            $row['entry_type'] = $entry_type[$row['entry_type']];
            
            if ($row['user_id'] != 0 && isset($users[$row['user_id']])) {
                $username = $users[$row['user_id']];
                if($row['user_id'] == AuthPriv::getUserId()) {
                    $link = $this->getLink('account', 'account_user');
                    
                } else {
                    $more = array('id' => $row['user_id']);
                    $link = $this->getLink('users', 'user', false, 'update', $more);
                }
                
                $row['user'] = sprintf('<a href="%s">%s</a>', $link, $username);
                
            } else {
                $row['user'] = '--';
            }
            
            
            // actions/links
            $links = array();
            $link = $this->getActionLink('preview', $row['id']);
            $links['preview_link'] = sprintf("javascript:PopupManager.create('%s', 'r', 'r', 2);", $link);
            $row['preview_link'] = $links['preview_link'];
            $links['restore_link'] = $this->getActionLink('restore', $row['id']);
            
            $actions = $this->getListActions($links);
            $tpl->tplAssign($this->getViewListVarsJs($row['id'], false, true, $actions));
            
            $tpl->tplParse(array_merge($row, $this->msg), 'row');
        }
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getListActions($links) {
        
        $actions = array();
        // $actions = array('delete');
        
        $actions['preview'] = array(
            'msg'  => $this->msg['preview_msg'], 
            'link' => $links['preview_link']);
            
        if($this->priv->isPriv('update')) {
            $actions['restore'] = array(
                'msg'  => $this->msg['put_back_msg'],
                'confirm_msg'  => $this->msg['sure_common_msg'],
                'link' => $links['restore_link']);
        }
        
        return $actions;
    }
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        
        $sort->setSortItem('date_deleted_msg', 'date_deleted', 'date_deleted', $this->msg['date_deleted_msg'], 2);
        
        return $sort;
    }
    
    
    function getFilter($manager) {

        @$values = $_GET['filter'];
        
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
    
        $select = new FormSelect();
        $select->select_tag = false;
        
        // entry type
        $real_range = $manager->getEntryTypesInTrash();
        $range = $manager->getEntryTypeSelectRange();
        $range = array_intersect_key($range, $real_range);
        
        if(count($range) < 3) {
            return '';
        }

        $select->setRange($range, array('all'=> '__'));
        $type_id = (isset($values['entry_type'])) ? $values['entry_type'] : 'all';
        $tpl->tplAssign('entry_type_select', $select->select($type_id));

        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse(@$_GET['filter']);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql(&$manager) {
        
        // filter
        $arr = array();
        @$values = $_GET['filter'];
        
        // entry type
        @$v = $values['entry_type'];
        if($v != 'all' && !empty($v)) {
            $v = (int) $v;
            $arr[] = "AND entry_type = '$v'";
        }
        
        return implode(" \n", $arr);
    }
}
?>