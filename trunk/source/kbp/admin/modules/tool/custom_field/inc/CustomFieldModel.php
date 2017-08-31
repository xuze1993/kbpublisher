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


class CustomFieldModel extends AppModel
{

    var $tbl_pref_custom = 'custom_';
    var $tables = array('table'=>'field', 'field_range', 'field_to_category');
    var $custom_tables = array('kb_entry', 'file_entry', 'news', 'trouble_entry','user', 'feedback',
                                   'kb_entry_to_category', 'file_entry_to_category', 'trouble_entry_to_category',
                                'kb_custom_data', 'file_custom_data', 'news_custom_data',
                                   'trouble_custom_data', 'user_custom_data', 'feedback_custom_data');

    var $record_type_to_custom_table = array(
        1 => 'kb_custom_data', 
        2 => 'file_custom_data', 
        3 => 'news_custom_data', 
        4 => 'trouble_custom_data', 
        10 => 'user_custom_data', 
        20 => 'feedback_custom_data');
    
    var $recort_type_to_category_type = array(1 => 11, 2 => 12, 4 => 14);
                                      
    var $field_type = array(
        'select' => 2, 'select_multiply' => 3,
        'checkbox' => 5, 'checkbox_group' => 6, 'radio' => 7,  /*'date' => 9,*/
        'text' => 1,  /*'password' => 4,*/ 'textarea' => 8
        );
    
    var $entry_type = array(
        'article', 'file', // 'trouble', 
        'news', 
        // 'user', 
        'feedback');

    var $display_option = array('top' => 1, 'bottom' => 2, 'block' => 3, 'hidden' => 4);
    var $entry_type_with_category = array(1,2,4);

    var $tabs = array(
        'field_type' => 0,
        'categories' => 2,
        'options' => 3,
        'range' => 4, 
        'validation' => 5,
        'display_options' => 6
    );
        
    var $error_fields_to_tab = array(
        'title' => 3,
        'range_id' => 4,
        'valid_regexp' => 5,
        'error_message' => 5
    );
    
        
    function getCountRecordsSql() {
        $sql = "SELECT COUNT(c.id) FROM {$this->tbl->table} c WHERE {$this->sql_params}";
        return $sql;
    }
    
    
    function getRecordsByStatus() {
        $sql = "SELECT cf.*, cfc.category_id
            FROM {$this->tbl->table} cf
            
            LEFT JOIN {$this->tbl->field_to_category} cfc 
            ON cf.id = cfc.field_id
            
            WHERE {$this->sql_params}
            
            -- GROUP BY cf.id,cfc.category_id -- mysql 5.7
            GROUP BY cf.id
            
            {$this->sql_params_order}";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $rows = $result->GetArray();
        
        $data = array(1 => array(), 0 => array());
        foreach ($rows as $row) {
            $data[$row['active']][$row['display']][] = $row;
        }
        
        ksort($data[1]);
        ksort($data[0]);
        
        return $data;
    }
    
    
    function &getCategoryById($record_id) {
        $sql = "SELECT category_id FROM {$this->tbl->field_to_category} 
        WHERE field_id IN ($record_id)";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        $data = array();
        while($row = $result->FetchRow()) {
            $data[] = $row['category_id'];
        }
        
        return $data;
    }
    
    
    function &getCategoryByIds($record_ids) {
        $sql = "SELECT * FROM {$this->tbl->field_to_category} 
        WHERE field_id IN ($record_ids)";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        $data = array();
        while($row = $result->FetchRow()) {
            $data[$row['field_id']][] = $row['category_id'];
        }
        
        return $data;
    }
    

    function getEntryTypeById($record_id) {
        $sql = "SELECT type_id FROM {$this->tbl->table} WHERE id = '{$record_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('type_id');
    }

    
    function &getCategoryRecords($entry_type, $method = 'getRecords') {
        switch ($entry_type) {
            case 1:
                require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryModel.php';
                $manager = new KBEntryModel();
                break;
                
            case 2:
                require_once APP_MODULE_DIR . 'file/entry/inc/FileEntryModel.php';
                $manager = new FileEntryModel();
                break;
                
            case 4:
                require_once APP_MODULE_DIR . 'trouble/entry/inc/TroubleEntryModel.php';
                $manager = new TroubleEntryModel();
                break;
                
            default:
                return array();
                break;
        }
        
        $categories = &$manager->cat_manager->$method();
        
        return $categories;
    }
    
    
    function getTabsRange($msg, $skip_tabs = array()) {
        $range = array();
        foreach($this->tabs as $k => $v) {
            if (!in_array($k, $skip_tabs)) {
                $range[$v] = $msg['custom_field_tab'][$k];    
            }
        }
        
        return $range;
    }
    
    
    function getFieldTypeSelectRange($msg) {
        $data = array();
        foreach ($this->field_type as $field_type => $id) {
            $data[$id]['title'] = $msg[$field_type]['title']; 
            $data[$id]['descr'] = $msg[$field_type]['descr'];           
        }
        
        return $data;
    }
    
    
    function getEntryTypeSelectRange() {
        $data = array();
        $msg = AppMsg::getMsg('ranges_msg.ini', false, 'record_type');
        foreach ($this->entry_type as $type) {
            $k = array_search($type, $this->record_type);
            $data[$k] = $msg[$type];
        }
        
        return $data;
    }
    

    function getSelectRange() {
        $sql = "SELECT id, title FROM {$this->tbl->table}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }

    
    function getPositionSelectRange($msg) {
        $range = array();
        foreach($this->position as $k => $v) {
            $range[$v] = $msg['position'][$k];
        }
        
        return $range;
    }
    
    
    function getDisplayOptionSelectRange($options, $msg) {
        $range = array();
        foreach($options as $v) {
            $key = array_search($v, $this->display_option);
            $range[$v] = $msg['display_options'][$key];
        }
        
        return $range;
    }
    
    
    function isEntryRecords($entry_type) {
        $table = $this->record_type_to_table[$entry_type];
        $table = $this->tbl->$table;
        $sql = "SELECT count(*) AS num FROM {$table}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');
    }
    
    
    function applyValue($value, $field_id, $entry_type, $entry_categories) {
        return (empty($entry_categories)) 
            ? $this->applyValueNoCategory($value, $field_id, $entry_type)
            : $this->applyValueCategory($value, $field_id, $entry_type, $entry_categories); 
    }
    
    
    function applyValueNoCategory($value, $field_id, $entry_type) {
    
        $entry_table = $this->record_type_to_table[$entry_type];
        $entry_table = $this->tbl->$entry_table;
        
        $custom_table = $this->record_type_to_custom_table[$entry_type];
        $custom_table = $this->tbl->$custom_table;
        
        $sql = "INSERT IGNORE {$custom_table} (entry_id, field_id, data) 
        SELECT id, '{$field_id}', '{$value}' FROM {$entry_table}";
                    
        $result = $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function applyValueCategory($value, $field_id, $entry_type, $entry_categories) {
    
        $entry_table = $this->record_type_to_table[$entry_type];
        $entry_table = $this->tbl->$entry_table;
        
        $custom_table = $this->record_type_to_custom_table[$entry_type];
        $custom_table = $this->tbl->$custom_table;
        
        $category_type = $this->recort_type_to_category_type[$entry_type];
        
        $etoc_table = $this->category_type_to_etoc_table[$category_type];
        $etoc_table = $this->tbl->$etoc_table;
        
        $categories = &$this->getCategoryRecords($entry_type);
        
        $cat_ids = $entry_categories;
        foreach ($entry_categories as $cat) {
            //$arr = TreeHelperUtil::getParentsById($categories, $cat);
            
            $tree = new TreeHelper();
            
            foreach($categories as $k => $row) {
                $tree->setTreeItem($row['id'], $row['parent_id']);
            }
    
            $arr = $tree->getChildsById($cat);
            $cat_ids = array_merge($cat_ids, $arr);
        }
            
        $cat_ids = implode(',', array_unique($cat_ids));
        
        $sql = "INSERT IGNORE {$custom_table} (entry_id, field_id, data)
        SELECT id, '{$field_id}', '{$value}'
        FROM ({$entry_table} e, {$etoc_table} c)
        WHERE e.id = c.entry_id
        AND c.category_id IN ($cat_ids)";

        $result = $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function save($obj) {
        
        $action = (!$obj->get('id')) ? 'insert' : 'update';
        
        // insert
        if($action == 'insert') {
            
            $id = $this->add($obj);
            $this->saveCustomFieldToCategory($obj->getCategory(), $id);    
        
        // update
        } else {
            
            $id = $obj->get('id');
            
            $this->update($obj);
             
            $this->deleteCustomFieldToCategory($id);
            $this->saveCustomFieldToCategory($obj->getCategory(), $id);
        }
        
        return $id;
    }
    
    
    function saveCustomFieldToCategory($cat, $record_id) {
    
        if(empty($cat)) {
            return;
        }
        
        require_once 'eleontev/SQL/MultiInsert.php';
        
        $data = array();
        $record_id = (is_array($record_id)) ? $record_id : array($record_id);
        $cat = (is_array($cat)) ? $cat : array($cat);
                                             
        foreach($cat as $cat_id) {
            foreach($record_id as $id) {
                $data[] = array($id, $cat_id);
            }
        }        
                                                      
        $sql = MultiInsert::get("INSERT IGNORE {$this->tbl->field_to_category} (field_id, category_id) VALUES ?", $data); 
                                                                                                            
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function saveSortOrder($values) {
        foreach ($values as $display => $ids) {
            $sort_order = 1;
            
            foreach ($ids as $id) {
                $this->updateSortOrder($id, $sort_order, $display);
                $sort_order ++;
            }
        }
    }
    
    
    function updateSortOrder($id, $sort_order, $display) {
        $sql = "UPDATE {$this->tbl->table} SET sort_order = %s, display = %s WHERE id = %s";
        $sql = sprintf($sql, $sort_order, $display, $id);
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    // DELETE
    
    function isFieldInUse($record_id, $entry_type) {
        $table = $this->record_type_to_custom_table[$entry_type];
        $table = $this->tbl->$table;    
    
        $sql = "SELECT COUNT(*) AS num FROM {$table} WHERE field_id = %d";
        $sql = sprintf($sql, $record_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        if($ret = $result->Fields('num')) {
            return $ret;
        }

        return false;
    }
    

    function deteteCustomField($record_id) {
        $sql = "DELETE FROM {$this->tbl->table} WHERE id IN ({$record_id})";
        return $this->db->Execute($sql) or die(db_error($sql));        
    }
   
    
    function deleteCustomFieldToCategory($record_id) {
        $sql = "DELETE FROM {$this->tbl->field_to_category} WHERE field_id IN ({$record_id})";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function deleteCustomData($record_id, $entry_type) {
        $table = $this->record_type_to_custom_table[$entry_type];
        $table = $this->tbl->$table;
        
        $sql = "DELETE FROM {$table} WHERE field_id IN ({$record_id})";
        return $this->db->Execute($sql) or die(db_error($sql));
    }


   function deleteField($record_id, $entry_type) {
        $this->deteteCustomField($record_id);
        $this->deleteCustomFieldToCategory($record_id);
        $this->deleteCustomData($record_id, $entry_type);
    }

}
?>