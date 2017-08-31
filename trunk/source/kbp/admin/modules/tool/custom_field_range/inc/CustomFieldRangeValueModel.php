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


class CustomFieldRangeValueModel extends AppModel
{

    var $tbl_pref_custom = 'custom_';
    var $tables = array('table'=>'field_range_value', 'custom_field'=>'field', 'field');
    var $custom_tables =  array('kb_custom_data', 'file_custom_data', 
                                'news_custom_data', 'feedback_custom_data');
    
    
    function isRangeValueInUse($record_id) {
        
        // get range_id
        $sql = "SELECT range_id FROM {$this->tbl->table} WHERE id = %d";
        $sql = sprintf($sql, $record_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $range_id = $result->Fields('range_id');
        
        // get fields where range used
        $sql = "SELECT id, type_id FROM {$this->tbl->field} WHERE range_id = '{$range_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $fields = $result->GetAssoc();
                
        // not in use
        if(!$fields) {
            return false;
        }
        
        $entry_types = array_unique($fields);
        $fields_ids = implode(',', array_keys($fields));
        $range_value_pattern = '(^|,)(' . $record_id . ')(,|$)';
        
        $cmanager = new CustomFieldModel();
                    
        $str = "SELECT COUNT(*) AS num FROM %s
        WHERE field_id IN (%s) AND data REGEXP '%s'";

        foreach($entry_types as $entry_type) {
            $table = $cmanager->record_type_to_custom_table[$entry_type];
            $table = $cmanager->tbl->$table;    
            
            $sql = sprintf($str, $table, $fields_ids, $range_value_pattern);
            $result = $this->db->Execute($sql) or die(db_error($sql));
            if($ret = $result->Fields('num')) {
                return $ret;
            }
        }

        return false;       
    }



/*
    function isRangeValueInUse($record_id) {
    
        $types = implode(',', CommonCustomFieldModel::getFieldTypesWithRange());
        $pattern = '(^|,)(' . $record_id . ')(,|$)';
        
        $sql = "SELECT COUNT(cd.entry_id) AS num 
        FROM {$this->tbl->field} c, %s cd
        WHERE c.id = cd.field_id
        AND c.input_id IN (%s)
        AND cd.data REGEXP '%s'";

        foreach($this->custom_tables as $v) {
            $sql = sprintf($sql, $this->tbl->$v, $types, $pattern);
            
            echo '<pre>', print_r($sql, 1), '</pre>';
            $result = $this->db->Execute($sql) or die(db_error($sql));
            if($ret = $result->Fields('num')) {
                return $ret;
            }
        }

        return false;       
    }
*/
    
    
    function getMaxSortOrder($range_id) {
        $sql = "SELECT MAX(sort_order) as max_sort_order
        FROM {$this->tbl->table} 
        WHERE range_id = '{$range_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('max_sort_order');
    }

}
?>