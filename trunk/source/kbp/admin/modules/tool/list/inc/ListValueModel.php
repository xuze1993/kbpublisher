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

require_once APP_MODULE_DIR . 'tool/list/inc/ParseListMsg.php';


class ListValueModel extends AppModel
{

    var $tables = array('table'=>'list_value', 'list_value', 'list', 'user');
    
    // new user val will be greater than 20
    // this applied after release so some user list values could be in this range 
    var $reserved_list_value = 20;
    
    
    function getGroupList($list_key) {
        $sql = "SELECT * FROM {$this->tbl->list} WHERE list_key = '{$list_key}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    
    
    // 2014-05-27, ORDER BY sort_order' removed to avoid temporary sql etc, using  uasort instead 
    function &getListValuesByKey($list_key, $list_value = false, $order_by = true) {
        
        $param = ($list_value != false) ? "lv.list_value = '{$list_value}'" : '1';
        // $sort_order = ($order_by) ? 'ORDER BY sort_order' : '';
        
        $sql = "SELECT lv.*
        FROM 
            {$this->tbl->list} l,
            {$this->tbl->list_value} lv 
        WHERE 1
            AND l.id = lv.list_id
            AND l.list_key = '{$list_key}'
            AND $param";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        // echo $this->getExplainQuery($this->db, $result->sql);
        $rows = $result->GetAssoc();                
    
        if($order_by) {
            uasort($rows, array($this, 'sortByOrder'));
        }
    
        $data = array();
        $list_msg = ParseListMsg::getValueMsg($list_key);
        foreach($rows as $k => $v) {
            $data[$v['list_value']] = $v;
            if(empty($v['title'])) {
                $data[$v['list_value']]['title'] = $list_msg[$v['list_key']];
            }
        }
        
        return $data;
    }
    
    
    function sortByOrder($a, $b) {
        return $a['sort_order'] > $b['sort_order'];
    }
    
    
    function getMaxListValue($list_id) {
        $sql = "SELECT MAX(list_value) AS num 
        FROM {$this->tbl->list_value} WHERE list_id = '{$list_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        $val = $result->Fields('num');
        if($val <= $this->reserved_list_value) {
            $val = $this->reserved_list_value;
        }
        
        return $val;
    }
    
    
    function getMaxListOrder($list_id) {
        $sql = "SELECT MAX(sort_order) AS num 
        FROM {$this->tbl->list_value} WHERE list_id = '{$list_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');
    }
    
    
    function resetDefaults($list_id) {
        $sql = "UPDATE {$this->tbl->list_value} SET custom_4 = 0 WHERE list_id = '{$list_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));        
    }
    
    
    function inUse($id) {
        $row = $this->getById($id);
        $list_value = $row['list_value'];
        
        $sql = "SELECT 1 AS field 
        FROM {$this->tbl->reftable} 
        WHERE active = '{$list_value}' 
        LIMIT 1";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('field');        
    }    
    
    
    // ADMIN USER //-----------------
    
    function saveAdminUserToCategory($users, $record_id) {
        return true;
    }
    
    function deleteAdminUserToCategory($record_id) {
        return true;
    }
    
    function getAdminUserByIds($ids) {
        return array();
    }

    function getAdminUserById($record_id, $list_view = false) {
        return array();
    }    
    
    
    // WITH OBJECT INITIALIZE
    
    // $not_skip_val is used in form update to have current entry value
    static function getListSelectRange($list_key, $active_only = true, $not_skip_val = false) {
        $m = new ListValueModel();
        $data = array();
        foreach($m->getListValuesByKey($list_key) as $list_value =>  $v) {
            
            if($active_only && !$v['active']) {
                if(false === $not_skip_val) {
                    continue;
                }
                
                if($v['list_value'] != $not_skip_val) {
                    continue;
                }
            }
            
            $data[$v['list_value']] = $v['title'];
        }
        
        return $data;
    }
    
    
    // the same as getListSelectRange but without sord order in sql 
    // $m->getListValuesByKey($list_key, false, false)
    static function getListRange($list_key, $active_only = true, $not_skip_val = false) {
        $m = new ListValueModel();
        $data = array();
        foreach($m->getListValuesByKey($list_key, false, false) as $list_value =>  $v) {
            
            if($active_only && !$v['active']) {
                if(false === $not_skip_val) {
                    continue;
                }
                
                if($v['list_value'] != $not_skip_val) {
                    continue;
                }
            }
            
            $data[$v['list_value']] = $v['title'];
        }
        
        return $data;
    }    
    
    
    static function getListData($list_key, $list_value = false) {
        $m = new ListValueModel();
        $data = $m->getListValuesByKey($list_key, $list_value);
        
        if($list_value) {
            return $data[$list_value];
        } else {
            return $data;
        }
    }
    
    
    static function getListTitle($list_key, $list_value) {
        $m = new ListValueModel();
        $data = $m->getListValuesByKey($list_key, $list_value, false);
        return $data[$list_value]['title'];
    }
    
    
    static function getListDefaultEntry($list_key) {
        $m = new ListValueModel();
        $sql = "SELECT lv.list_value
        FROM {$m->tbl->list} l, {$m->tbl->list_value} lv 
        WHERE l.id = lv.list_id AND l.list_key = '{$list_key}' AND custom_4 = 1";
        $result = $m->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('list_value');
    }    
}
?>
