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


class KBEntryHistoryView_list extends AppView
{
    
    var $template = 'list_history.html';
    
    
    function execute(&$obj, &$manager, $data) {
        
        $this->addMsg('user_msg.ini');
        $this->escapeMsg(array('sure_delete_entry_revision_msg'));
        
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        // tabs
        list($entry, $eobj, $emanager) = $data;
        $tpl->tplAssign('menu_block', KBEntryView_common::getEntryMenu($eobj, $emanager, $this));
        
        $entry_id = $eobj->get('id');        
        $manager->setSqlParams(sprintf("AND entry_id = '%d'", $entry_id));
        
        // entry update not allowed
        $eown_record = ($eobj->get('author_id') == $emanager->user_id);
        $entry_updateble = false;
        if($this->isEntryUpdateable($entry_id, $eobj->get('active'), $eown_record)) {
            if(!$this->priv->isPrivOptional('update', 'draft', 'kb_drafr')) {
                $entry_updateble = true;
                $tpl->tplSetNeeded('/delete_entry_link');                
            }
        }
        
                
        // header generate
        $count = $manager->getCountRecords();        
        $bp = &$this->pageByPage($manager->limit, $count);
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, false, false));
        
        // sort generate
        $sort = &$this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
        
        // current entry data
        $date = $this->getFormatedDate($entry['date_updated'], 'datetime');
        $tpl->tplAssign('formatted_date_posted_live', $date);
        
        $tpl->tplAssign('comment_live', $entry['history_comment']);
        $tpl->tplAssign('revision_num_live', $manager->getEntryMaxVersion($entry_id)+1);
        
        $user = $manager->getUserById($entry['updater_id']);
        $tpl->tplAssign('updated_by_live', PersonHelper::getShortName($user));
        
        // get records
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        
        // list records
        foreach($rows as $row) {
            
            $date = $this->getFormatedDate($row['date_posted'], 'datetime');
            $tpl->tplAssign('formatted_date_posted', $date);

            $date = $this->getFormatedDate($row['entry_date_updated'], 'datetime');
            $tpl->tplAssign('formatted_entry_date_updated', $date);
            
            $tpl->tplAssign('updated_by', PersonHelper::getShortName($row));
                        
            $actions = array();
            if($entry_updateble) {
                $actions = $this->getListActions($row, array());
            }
            $row_id = $row['entry_id'] . $row['revision_num'];
            $tpl->tplAssign($this->getViewListVarsJs($row_id, 1, 1, $actions));
            
            $row['id'] = $row_id;
            $tpl->tplParse($row, 'row');
        }
        
        
        // update article link
        $referer = WebUtil::serialize_url($this->getActionLink('history', $entry_id));
        $more = array('id'=>$entry_id, 'referer' => $referer);
        $link = $this->controller->getLink('knowledgebase', 'kb_entry', false, 'update', $more);
        $tpl->tplAssign('entry_link_update', $link);
        
        // public article link
        $client_controller = &$this->controller->getClientController();
        $link = $client_controller->getLink('entry', false, $entry_id);
        $tpl->tplAssign('entry_link', $link);
    
        // delete all link
        $more = array('id' => $entry_id);
        $tpl->tplAssign('delete_entry_link', $this->getActionLink('hdelete', false, $more));                
        
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getListActions($row, $links) {
        
        $entry_id = $row['entry_id'];
        $revision_num = $row['revision_num']; 
        
        $actions = array();
        
        $more = array('vnum'=>$revision_num);
                
        $actions['diff'] = array(
            'link' => $this->getActionLink('diff', $entry_id, $more),
            'msg'  => $this->msg['viewdiff_msg']
        );
        
        $actions['download'] = array(
            'link' => $this->getActionLink('file', $entry_id, $more),
            'msg'  => $this->msg['download_msg']
        );
        
        $link = $this->getActionLink('hpreview', $entry_id, $more);
        $link = sprintf("javascript:PopupManager.create('%s', 'r', 'r', 1);", $link);
        $actions['preview'] = array(
            'link' => $link,
            'msg'  => $this->msg['preview_msg']
        );
        
        
        $actions['rollback'] = array(
            'link' => $this->getActionLink('rollback', $entry_id, $more),
            'msg'  => $this->msg['rollback_msg'],
            'confirm_msg'  => $this->msg['sure_common_msg']
        );

        
        $more = array('id' => $entry_id, 'vnum' => $revision_num);
        $actions['update'] = array(
            'link' => $this->getLink('knowledgebase', 'kb_entry', false, 'update', $more),
            'msg'  => $this->msg['revision_update_msg']
        );
        
        return $actions;
    }
    
    
    // reassign 
    function getActionsOrder() {
        $order = array(
            'preview',              'delim',
            'diff', 'download',     'delim',
            'rollback', 'update'
        );
            
        return $order;
    }
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        //$sort->setDefaultSortItem('datep');
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        
        $sort->setSortItem('date_added_msg', 'datep', 'date_posted', $this->msg['date_added_msg'], 2);
                
        return $sort;
    }
}
?>