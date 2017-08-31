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


class ListGroupView_list extends AppView
{
    
    var $tmpl = 'list_group.html';
    
    
    function execute(&$obj, &$manager) {
    
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        // sort generate
        $manager->setSqlParamsOrder('ORDER BY sort_order');
        
        // get records
        $rows = $this->stripVars($manager->getRecords());
        $rows_msg = ParseListMsg::getGroupMsg();
        
        // sorting
        $titles = array();
        foreach($rows as $k => $row) {
            if ($row['list_key'] == 'forum_status' && !BaseModel::isModule('forum')) {
                continue;
            }
            
            $row = $rows[$k];
            $obj->set($row);
            
            $title = (!empty($row['title'])) ? $row['title'] : $rows_msg[$row['list_key']];
            $obj->set('title', $title);
                    
            $tpl->tplAssign($this->getViewListVarsJsCustom($obj->get('id'), $obj->get('active'), $obj->get('list_key')));
            $tpl->tplParse(array_merge($obj->get(), $this->msg), 'row');            
        }
        
        $tpl->tplAssign($this->msg);
    
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getViewListVarsJsCustom($record_id = false, $active = false, $key = false) {
        
        $actions = array();
        
        $actions['update'] = array(
            'link' => $this->getActionLink('update_group', $record_id),
            );
        
        $actions['load'] = array(
            'link' => $this->controller->getLink('this', 'this', $key),
            'msg'  => $this->msg['view_items_msg']
            );
        
        $row = $this->getViewListVarsJs($record_id, $active, 1, $actions);
        $row['load_link'] = $actions['load']['link'];
        
        return $row;
    }
}
?>