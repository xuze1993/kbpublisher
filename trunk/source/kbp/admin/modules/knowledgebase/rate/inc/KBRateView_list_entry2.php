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

require_once 'KBRateView_list.php';


class KBRateView_list_entry2 extends KBRateView_list
{
    
    var $tmpl = 'list_entry2.html';
    
    var $limit = 10;

    
    function execute(&$obj, &$manager) {

        $this->addMsg('user_msg.ini');
        $this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
    
        $entry_id = $obj->get('entry_id');    
        $client_controller = &$this->controller->getClientController();
        
        if ($this->controller->page == 'kb_rate') {
            $tpl->tplSetNeededGlobal('comment_view');
            
            // update article link
            $more = array('id'=>$obj->get('entry_id'), 'referer' => WebUtil::serialize_url($this->controller->getCommonLink()));
            $link = $this->controller->getLink('knowledgebase', 'kb_entry', false, 'update', $more);
            $tpl->tplAssign('entry_link_update', $link);
            
            // back link
            $link = $this->controller->getLink('knowledgebase', 'kb_rate');
            $tpl->tplAssign('back_link', $link);
        
        } else {
            $tpl->tplSetNeededGlobal('entry_view');
        }
        
        // public article link
        $link = $client_controller->getLink('entry', false, $entry_id);
        $tpl->tplAssign('entry_link', $link);
        $tpl->tplAssign('entry_id', $entry_id);
    
        // delete all link
        $more = array('entry_id' => $entry_id);
        $tpl->tplAssign('delete_entry_link', $this->getActionLink('delete_entry', false, $more));
        
    
        // filter sql    
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params['where']);
        $manager->setSqlParamsSelect($params['select']);        
        $manager->setSqlParams(sprintf("AND e.id = %d", $entry_id));
        
        
        // status, all, for list
        $status_range_list = $manager->getListSelectRange(false);
        
        // status, active only, for new comment
        $status_range = $manager->getListSelectRange(true);

        $select = new FormSelect();
        $select->setSelectWidth(250);
        $select->setSelectName('active');
        $select->setRange($status_range);            
        $tpl->tplAssign('status_select', $select->select($obj->get('active')));
        
    
        // header generate
        // $bp =& $this->pageByPage($this->limit, $manager->getRecordsSql(), true, array(), 'page', 'bpt');
        $bp_options = array('class'=>'page', 'get_name'=>'bpt');
        $bp =& $this->pageByPage($this->limit, $manager->getRecordsSql(), $bp_options);
        $this->num_records = $bp->num_records;

        if($bp->num_pages > 1) {
            $tpl->tplAssign('page_by_page_bottom', $this->commonHeaderList($bp->nav, false, false));
        }
        $tpl->tplAssign('num_records', $this->num_records);

        // sort generate
        $sort = &$this->getSort();        
        $manager->setSqlParamsOrder($sort->getSql());
        
        // get records
        $rows = $manager->getRecords($bp->limit, $bp->offset);
        
        if($this->priv->isPriv('update', 'kb_rate')) {
            $tpl->tplSetNeeded('/update');
        }
        
        
        // list records
        foreach($rows as $row) {
            
            $tpl->tplAssign('raw_comment', $row['comment']);
            
            $row = $this->stripVars($row);
            $obj->set($row);
            $obj->set('comment', nl2br($obj->get('comment')));
            
            $tpl->tplAssign('username', ($row['username']) ? $row['username'] : '--');         
            $tpl->tplAssign('r_email', $row['r_email']);
            
            $formatted_date = $this->getFormatedDate($row['date_posted'], 'datetime');
            $tpl->tplAssign('formatted_date', $formatted_date);

            $interval_date = $this->getTimeInterval($row['date_posted'], true);
            $tpl->tplAssign('interval_date', $interval_date);            
            
            // status
            foreach ($status_range_list as $k => $v) {
                $a['name'] = $v;
                $a['comment_id'] = $row['id'];
                $a['status_id'] = $k;
                $a['status_display'] = ($k == $row['active']) ? 'none' : 'block';
                
                $tpl->tplParse($a, 'row/status_row');
            }
            
            $tpl->tplSetNested('row/status_row');
            $tpl->tplAssign('status', $status_range_list[$row['active']]);
            //$tpl->tplAssign('color', $status[$row['active']]['color']);
            
            if($this->priv->isPriv('update', 'kb_rate')) {
                $tpl->tplSetNeeded('row/update');
            }
            
            if($this->priv->isPriv('delete', 'kb_rate')) {
                $tpl->tplSetNeeded('row/delete');
            }
            
            if($this->priv->isPriv('status', 'kb_rate')) {
                $tpl->tplSetNeeded('row/update_status');
            }
            
            $tpl->tplAssign($this->getViewListVarsJs($obj->get('id'), $obj->get('active')));
            
            $tpl->tplParse(array_merge($obj->get(), $this->msg), 'row');
        }
        
        if($rows) {
            $tpl->tplAssign('title', $row['title']);
            $tpl->tplAssign('short_title', $this->getSubstringStrip($row['title'], 100));
        }
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        if ($this->controller->page == 'kb_rate') {
            $more = array('entry_id' => $this->controller->getMoreParam('entry_id'));
            $xajax->setRequestURI($this->controller->getAjaxLink('all', false, false, false, $more));
        }
        
        $xajax->registerFunction(array('deleteComment', $this, 'ajaxDeleteComment'));
        $xajax->registerFunction(array('updateComment', $this, 'ajaxUpdateComment'));
        $xajax->registerFunction(array('addComment', $this, 'ajaxAddComment'));
        $xajax->registerFunction(array('updateCommentStatus', $this, 'ajaxUpdateCommentStatus'));
        $xajax->registerFunction(array('deleteAllComments', $this, 'ajaxDeleteAllComments'));
        
        if($this->priv->isPriv('insert', 'kb_rate')) {
            $tpl->tplSetNeeded('/add_new');
        }
        
        if($this->priv->isPriv('delete', 'kb_rate')) {
            $tpl->tplSetNeeded('/delete_all');
        }
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($this->setStatusFormVars(1, false));
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function ajaxDeleteComment($id) {
        $objResponse = new xajaxResponse();
        
        $this->manager->delete($id);
        
        $fade_js = "$('#comment_{$id}').fadeOut(1000);";
        $objResponse->script($fade_js);
        
        $this->_ajaxRefreshHeader($objResponse, $this->manager->limit - 1, $this->num_records - 1);
    
        return $objResponse;
    }
    
    
    function ajaxUpdateComment($id, $comment) {
        $objResponse = new xajaxResponse();
        
        $data = $this->manager->getById($id);
        $this->obj->set($data);
        
        $comment = RequestDataUtil::stripVars($comment, array(), 'addslashes');
        $this->obj->set('comment', $comment);
        
        $this->manager->save($this->obj);
        
        $objResponse->script('$("#comment").val("");');
        
        $comment = $this->obj->get('comment');
        $comment = RequestDataUtil::stripVars($comment, array(), 'stripslashes');
        $objResponse->call('insertUpdatedComment', $id, nl2br($comment), $comment);
    
        return $objResponse;
    }
    
    
    function ajaxAddComment($comment, $status) {
        $objResponse = new xajaxResponse();
        
        $this->obj = new KBRate;
        
        $entry_id = ($this->controller->page == 'kb_rate') ? $_GET['entry_id'] : $_GET['id']; 
        $this->obj->set('entry_id', $entry_id);
        
        $comment = RequestDataUtil::stripVars($comment, array(), 'addslashes');
        $this->obj->set('comment', $comment);
        
        $this->obj->set('date_posted', null);
        $this->obj->set('user_id', AuthPriv::getUserId());
        $this->obj->set('active', $status);
        
        $comment_id = $this->manager->save($this->obj);
        $this->obj->set('id', $comment_id);
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        $row = $this->obj->get();
        $row['raw_comment'] = RequestDataUtil::stripVars($row['comment'], array(), 'stripslashes');
        $row['comment'] = nl2br($row['raw_comment']);
        $row['formatted_date'] = $this->getFormatedDate($this->obj->get('date_posted'), 'datetime');
        $row['interval_date'] = $this->getTimeInterval($this->obj->get('date_posted', true));
        $row['username'] = AuthPriv::getUsername();
        $row['status_checked'] = ($this->obj->get('active')) ? 'checked' : '';
        $row['name'] = '';
        $row['email'] = '';
        
        $status_range = $this->manager->getListSelectRange(true);
        //$row['color'] = $status_range[$status]['color'];
        
        foreach ($status_range as $k => $v) {
            $a['name'] = $v;
            $a['comment_id'] = $row['id'];
            $a['status_id'] = $k;
            $a['status_display'] = ($k == $status) ? 'none' : 'block';
            
            $tpl->tplParse($a, 'row/status_row');
        }
            
        $tpl->tplSetNested('row/status_row');
            
        $row['status'] = $status_range[$status];
        
        if($this->priv->isPriv('update', 'kb_rate')) {
            $tpl->tplSetNeeded('row/update');
        }

        if($this->priv->isPriv('delete', 'kb_rate')) {
            $tpl->tplSetNeeded('row/delete');
        }
        
        if($this->priv->isPriv('status', 'kb_rate')) {
            $tpl->tplSetNeeded('row/update_status');
        }
         
        $tpl->tplParse(array_merge($row, $this->msg), 'row');
        
        $objResponse->call('insertComment', $tpl->parsed['row']);
        $objResponse->script('$("#comment_form").hide();');
        $objResponse->script('$("#comment").val("");');
        
        $this->_ajaxRefreshHeader($objResponse, $this->manager->limit + 1, $this->num_records + 1);
    
        return $objResponse;
    }
    
    
    function _ajaxRefreshHeader($objResponse, $limit, $num) {
        $objResponse->addAssign('comments_num', 'innerHTML', $num);
    }
    
    
    function ajaxUpdateCommentStatus($id, $status) {
        $objResponse = new xajaxResponse();
        
        $this->manager->status($status, $id);
        
        $status_range = $this->manager->getEntryStatusData();
        $status_title = $status_range[$status]['title'];
        
        $objResponse->addAssign('current_status_' . $id, 'innerHTML', $status_title);
        $objResponse->script("$('#status_list_{$id} li').show();");
        $objResponse->script("$('#status_item_{$status}').hide();");
        $objResponse->script("$('#status_item_divider_{$status}').hide();");
    
        return $objResponse;
    }
        
    
    function ajaxDeleteAllComments() {
    
        $objResponse = new xajaxResponse();
        
        $this->manager->deleteByEntryId($this->obj->get('entry_id'));
        
        $objResponse->addAssign('commentsBlock', 'innerHTML', '');
        
        $this->_ajaxRefreshHeader($objResponse, 0, 0);
        
        return $objResponse;    
    }
}
?>