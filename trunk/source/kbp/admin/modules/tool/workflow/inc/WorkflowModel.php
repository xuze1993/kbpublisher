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

require_once APP_MODULE_DIR . 'tool/trigger/inc/TriggerModel.php';


class WorkflowModel extends TriggerModel
{

    var $models = array();    
    var $user_limit = 3;
    
    
    function getPrivSelectRange() {
        $m = &WorkflowModel::instance('UserModel');
        
        $sql = "SELECT n.id, n.name
        
            FROM {$m->tbl->priv_name} n, {$m->tbl->priv_rule} r

            WHERE n.id = r.priv_name_id
                AND (n.editable = 0
                    OR (r.priv_module_id = 103 AND r.what_priv LIKE '%,update%')
                    OR (r.priv_module_id = 100 AND r.what_priv LIKE '%update%'))
                AND n.active = 1
            
            ORDER BY n.sort_order";
        
        $result = $m->db->SelectLimit($sql, $this->user_limit + 1) or die(db_error($sql));
        $rows = $result->GetAssoc();
        
        $priv_lang = AppMsg::getMsgs('privileges_msg.ini');
        
        $data = array();
        foreach ($rows as $id => $name) {
            $_name = ($name) ? $name : $priv_lang[$id]['name'];
            $data['priv_' . $id] = sprintf('[privilege: %s]', $_name);
        }
        
        return $data;
    }       
}
?>