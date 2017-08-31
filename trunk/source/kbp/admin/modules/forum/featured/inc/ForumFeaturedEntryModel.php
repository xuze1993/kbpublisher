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

require_once 'ForumFeaturedEntryModelBulk.php';
require_once APP_MODULE_DIR . 'forum/entry/inc/ForumEntryModel.php';


class ForumFeaturedEntryModel extends AppModel
{

    var $tbl_pref_custom = 'forum_';
    var $tables = array(
        'table' => 'featured', 'entry_featured', 'entry');
    
    var $show_bulk_sort = true;
    
    
    function __construct() {
        parent::__construct();
        $this->emanager = new ForumEntryModel;
    }
    
    
    function getById($record_id) {
        $this->setSqlParams("AND ef.id = '$record_id'");
        $sql = $this->getRecordsSql();
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    
    
    function getRecordsSql() {
        
        $sql = "SELECT ef.*, e.title, e.hits, e.category_id
            FROM 
                ({$this->tbl->table} ef,
                {$this->tbl->entry} e)
            WHERE ef.entry_id = e.id
                AND ef.message_id = 0
                AND {$this->sql_params}
            {$this->sql_params_order}";
        
        // echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }
    
    
    function getCountRecordsSql() {
        $sql = "SELECT COUNT(DISTINCT ef.entry_id)
            FROM ({$this->tbl->table} ef,
                {$this->tbl->entry} e)
            WHERE ef.entry_id = e.id
                AND {$this->sql_params}";
 
        return $sql;        
    }
    
    
    function getEntryIds($record_id) {
        $record_id = $this->idToString($record_id);
        
        $sql = "SELECT entry_id
            FROM {$this->tbl->table}
            WHERE id IN (%s)";
        $sql = sprintf($sql, $record_id);
        $result = $this->db->Execute($sql);
        
        $data = array();
        while($row = $result->FetchRow()) {
            $data[] = $row['entry_id'];
        }
        
        return $data;
    }
    
    
    function getUsedCategories() {
        $sql = "SELECT DISTINCT entry_id
            FROM {$this->tbl->table}
            WHERE entry_id != 0";
            
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        $data = array();
        while($row = $result->FetchRow()) {
            $data[] = $row['entry_id'];
        }
        return $data;
    }
    
    
    function getEntryCount($category_id) {
        $sql = "SELECT COUNT(*) as num
            FROM {$this->tbl->table}
            WHERE entry_id = '{$category_id}'";
            
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');
    }
    
    
    function saveSortOrder($ids, $sort_order) {
        foreach ($ids as $id) {
            $this->updateSortOrder($id, $sort_order);
            $sort_order ++;
        }
    }
    
    
    function updateSortOrder($id, $sort_order) {
        $sql = "UPDATE {$this->tbl->table} SET sort_order = %d WHERE id = %d";
        $sql = sprintf($sql, $sort_order, $id);
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    /*function save($entry_id, $index_page, $categories) {
        require_once 'eleontev/SQL/MultiInsert.php';
        
        if ($index_page) {
            $categories[] = 0;
        }
        
        $ids = implode(',', $categories);
        $this->emanager->increaseFeaturedEntrySortOrder($ids);
        
        $data = array();
        foreach ($categories as $v) {
            $data[] = array($v);
        }
        
        $sql = MultiInsert::get("INSERT {$this->tbl->table} (entry_id, entry_type, entry_id, sort_order)
                                VALUES ?", $data, array($this->entry_type, $entry_id, 1));
                                
        $sql = sprintf($sql, $entry_id);
        $this->db->Execute($sql) or die(db_error($sql));
    }*/
}
?>