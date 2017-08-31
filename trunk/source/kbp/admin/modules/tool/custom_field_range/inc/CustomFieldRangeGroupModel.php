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


class CustomFieldRangeGroupModel extends AppModel
{

    var $tbl_pref_custom = 'custom_field_';
    var $tables = array('table'=>'range', 'range_value');
    var $custom_tables =  array('custom_field');    
        
    
    function getValuesNum($ids) {
        $sql = "SELECT range_id, COUNT(*) as num
            FROM {$this->tbl->range_value} 
            WHERE range_id IN ($ids) GROUP BY range_id";
        $result = $this->db->Execute($sql) or die(db_error($sql));

        return $result->GetAssoc();
    }
    
    
    function getReferencedFieldsNum($ids) {
        $sql = "SELECT range_id, type_id, COUNT(*) as num
            FROM {$this->tbl->custom_field} 
            WHERE range_id IN ($ids) 
            GROUP BY type_id, range_id";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        $data = array();
        while($row = $result->FetchRow()) {  
            $data[$row['range_id']][$row['type_id']] = $row['num'];
        }

        return $data;
    }
    

    // DELETE RELATED //  -------------------

    function isRangeInUse($record_id) {
        $sql = "SELECT COUNT(*) AS num 
        FROM {$this->tbl->custom_field} 
        WHERE range_id = %d";
        
        $sql = sprintf($sql, $record_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');       
    }    


    function delete($record_id) {
        $this->deleteGroups($record_id);
        $this->deleteValues($record_id);
    }
    
    
    function deleteGroups($record_id) {
        $sql = "DELETE FROM {$this->tbl->table} WHERE id IN ({$record_id})";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function deleteValues($record_id) {
        $sql = "DELETE FROM {$this->tbl->range_value} WHERE range_id IN ({$record_id})";
        return $this->db->Execute($sql) or die(db_error($sql));
    }

}
?>