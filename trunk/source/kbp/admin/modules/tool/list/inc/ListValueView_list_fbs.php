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


class ListValueView_list_fbs extends ListValueView_list
{
    
    var $tmpl = 'list_value_fbs.html';

    
    function execute(&$obj, &$manager) {

        $this->addMsg('setting_msg.ini');
        $this->addMsg('user_msg.ini');
    
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        // header generate
        $tpl->tplAssign('header', $this->titleHeaderList(false, $obj->group_title));
        
        // sort generate
        $sort = &$this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
        
        $supervisor_id = false;
        if (!empty($_GET['filter']['supervisor_id'])) {
            $supervisor_id = $_GET['filter']['supervisor_id'];
        }
        
        // get records
        $rows = ($supervisor_id) ? $manager->getRecordsBySupervisor($supervisor_id) : $manager->getRecords();
        $rows = $this->stripVars($rows);
        $rows_msg = ParseListMsg::getValueMsg($obj->group_key);
        //echo "<pre>"; print_r($rows); echo "</pre>";
        
        // supervisor
        $ids = $manager->getValuesString($rows, 'list_value'); 
        $supervisor = ($ids) ? $manager->getAdminUserById($ids, 'id_list') : array();
        //echo "<pre>"; print_r($supervisor); echo "</pre>";
        
        // list records
        foreach($rows as $row) {
            
            $obj->set($row);
            $this->setTitle($obj, $rows_msg);
            
            // supervisor
            $admin_user = '';
            if(isset($supervisor[$row['list_value']])) {
                $admin_user = implode('<br />', $supervisor[$row['list_value']]);
            }            
            
            $tpl->tplAssign('admin_user', $admin_user);
            $tpl->tplAssign($this->getViewListVarsJsCustom($obj->get('id'), $obj->get('active'), $obj));
            $tpl->tplParse(array_merge($obj->get(), $this->msg), 'row');
        }
        
        // user
        if($obj->get('list_id') == 4) {
            $this->msg['list_entry_status_msg'] = $this->msg['list_user_status_msg'];
        }                
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
    
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }    
}
?>