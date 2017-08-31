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


class TrashEntryModel extends AppModel
{

    var $tbl_pref_custom = '';
    var $tables = array('table' => 'entry_trash', 'entry_trash', 'user');
        
    var $entry_type = array(
        'article',
        'file', 
        'news'
        );
        
    
    function getEntryTypeSelectRange() {
        $data = array();    
        $msg = AppMsg::getMsg('ranges_msg.ini', false, 'record_type');
        foreach ($this->entry_type as $type) {
            $k = array_search($type, $this->record_type);
            $data[$k] = $msg[$type];
        }
                
        return $data;
    }
    
    
    function getEntryTypesInTrash() {
        $sql = "SELECT DISTINCT(entry_type), entry_type AS et FROM {$this->tbl->table}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }    
    
    
    function getUserByIds($ids) {
        $sql = "SELECT id, username FROM {$this->tbl->user} WHERE id IN ({$ids})";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function getEntryType($id) {
        $sql = "SELECT entry_type FROM {$this->tbl->entry_trash} WHERE id = '{$id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('entry_type');
    }
    
    
    function getEntryTypes() {
        $sql = "SELECT DISTINCT(entry_type) AS et, entry_type   
        FROM {$this->tbl->entry_trash}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function truncate() {
        $sql = "TRUNCATE {$this->tbl->table}";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
}
?>