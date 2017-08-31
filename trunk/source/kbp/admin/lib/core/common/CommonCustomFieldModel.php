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

require_once 'eleontev/SQL/MultiInsert.php';


class CommonCustomFieldModel extends BaseModel
{
    
    var $tbl_pref_custom = 'custom_';
    var $tables = array(
        'table'=>'field', 'field',
        'field_to_category', 'field_range_value');
    
    var $etype;
    var $etable;

	static $numeric_inputs = array(2,5,7);
	static $multiple_inputs = array(3,6);
	static $text_inputs = array(1,8);

    
    function __construct($manager = false) {
        parent::__construct();
        
        if ($manager) {
            $this->etype = $manager->entry_type;
            $this->etable = $manager->tbl->custom_data;
        }
    }


    function getCustomDataById($id) {
        $sql = "SELECT field_id, data FROM {$this->etable} WHERE entry_id = '{$id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->getAssoc();
    }
    
    
    function getCustomDataByIds($ids) {
        $ids = implode(',', $ids);
        $sql = "SELECT * FROM {$this->etable} WHERE entry_id IN ({$ids})";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $rows = $result->getArray();
        
        $data = array();
        foreach ($rows as $row) {
            $data[$row['entry_id']][$row['field_id']] = $row['data'];
        }
        
        return $data;
    }


    function getCustomDataCurrent($ids, $field_id) {
        $sql = "SELECT entry_id, field_id, data FROM {$this->etable} 
        WHERE entry_id IN ({$ids}) AND field_id = '{$field_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));

        $data = array();
        while($row = $result->FetchRow()) {
            $data[$row['entry_id']][$row['field_id']] = explode(',', $row['data']);
        }

        return $data;
    }


    function getCustomFieldByIds($ids) {
        $sql = "SELECT * FROM {$this->tbl->field} WHERE id IN ({$ids}) ORDER BY sort_order";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->getAssoc();
    }
       

    function getCustomFieldByEntryType($entry_type = false, $searchable_only = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->etype;
        $sql = "SELECT * FROM {$this->tbl->field}
        WHERE type_id = '{$entry_type}' AND active = 1";
        
        if ($searchable_only) {
            $sql .= ' AND is_search = 1';
        }
        
        $result = $this->db->Execute($sql) or die(db_error($sql)); 
        return $result->GetAssoc();
    }    
    

    // $cat_ids = should be all assigned + all child           
    function getCustomField($categories = array(), $entry_categories = array()) {
        if(!empty($entry_categories)) {
            
            $cat_ids = array();            
            foreach ($entry_categories as $cat) {
                $arr = TreeHelperUtil::getParentsById($categories, $cat);     
                $cat_ids = array_merge($cat_ids, $arr);
            }
            
            $cat_ids = array_unique($cat_ids);
            $cat_ids = implode(',', $cat_ids);
            
            return $this->getCustomFieldCategory($cat_ids);
            
        } else {
            return $this->getCustomFieldNoCategory();
        }
    }
    
    
    function getCustomFieldCategory($cat_ids) {
        $sql = "SELECT 
            c.*,
            IF(cc.field_id, 1, 0) as has_category 
        FROM 
            {$this->tbl->field} c

        LEFT JOIN {$this->tbl->field_to_category} cc ON c.id = cc.field_id  
        
        WHERE c.type_id = '{$this->etype}'
        AND (cc.category_id IN ({$cat_ids}) OR cc.category_id IS NULL)
        AND c.active = 1
        GROUP BY c.id
        
        ORDER BY display, sort_order";

        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->getAssoc();
    }
    
        
    function getCustomFieldNoCategory() {
        $sql = "SELECT 
            c.*,
            0 as has_category
        FROM 
            {$this->tbl->field} c
        
        LEFT JOIN {$this->tbl->field_to_category} cc ON c.id = cc.field_id   
        
        WHERE c.type_id = '{$this->etype}'
        AND cc.category_id IS NULL
        AND c.active = 1
        
        ORDER BY display, sort_order";
    
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->getAssoc();
    }
    
    
    function getCustomFieldByCategory($ids) {
        $sql = "SELECT DISTINCT c.*, 1 as has_category
        FROM ({$this->tbl->field} c,
            {$this->tbl->field_to_category} cc)     
            
        WHERE c.id = cc.field_id
        AND c.type_id = '{$this->etype}'
        AND cc.category_id IN ({$ids})
        AND c.active = 1
        
        ORDER BY display, sort_order";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    

    function getCustomFieldRange($range_id) {
        $sql = "SELECT id, title
        FROM {$this->tbl->field_range_value}
        WHERE range_id = '{$range_id}'
        ORDER BY sort_order";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }

    
    function getCustomFieldIdsByCategory($ids) {
        $sql = "SELECT field_id
        FROM {$this->tbl->field_to_category}
        WHERE category_id IN ({$ids})";

        $result = $this->db->Execute($sql) or die(db_error($sql)); 
        
        $data = array();
        while($row = $result->FetchRow()) {
            $data[] = $row['field_id'];
        }

        return $data;
    }
    
    
    function getCustomFieldSql($values) {

        $sql = array();
        $sql['where'] = 1;
        $sql['join'] = '';
    
        $join = array();
        $where = array();
        
        $cfields = $this->getCustomFieldByEntryType();
        $table = $this->etable;
        
        foreach($values as $field_id => $v) {
            
            $field_id = (int) $field_id;
            $t = 'cd_'. $field_id;
            $j = "LEFT JOIN {$table} {$t} ON {$t}.entry_id = e.id AND {$t}.field_id = '{$field_id}'";
            
			// multiple inputs should be array, goes from api as string
			if(in_array($cfields[$field_id]['input_id'], self::$multiple_inputs)) {
				if(!is_array($v)) {
					$v = explode(',', $v);
				}
			}
			
            // empty
            if($v == '') {
                
            
            // checkbox
            } elseif(in_array($cfields[$field_id]['input_id'], self::$numeric_inputs)) {
                $join[] = $j;
                $where[] = sprintf('%s.data = %d', $t, (int) $v);
                
            // date
            } elseif($cfields[$field_id]['input_id'] == 9) {
                $join[] = $j;
                $where[] = sprintf("%s.data = '%s'", $t, date('Y-m-d', strtotime($v)));
                
            // multi ranges        
            } elseif (is_array($v)) {
                
                $join[] = $j;
                
                // match all
                if(1) {
                    foreach($v as $rvalue) {
                        $pattern = '(^|,)(' . $rvalue . ')(,|$)';
                        $where[] = sprintf("%s.data REGEXP '%s'", $t, $pattern);
                    }
                
                // match any
                } else {
                    $pattern = '(^|,)(' . implode('|', $v) . ')(,|$)';
                    $where[] = sprintf("%s.data REGEXP '%s'", $t, $pattern);                    
                }
            
            // text
            } else {
                $join[] = $j;
                $where[] = sprintf("%s.data LIKE '%%%s%%'", $t, $v);
            }
        }
        
        if($join) {
            $sql['join'] = implode("\n", $join);
            $sql['where'] = sprintf('(%s)', implode(" AND ", $where));
        }
    
		
        // echo '<pre>', print_r($values, 1), '</pre>';
        // echo '<pre>', print_r($sql, 1), '</pre>';
        return $sql;
    }
    
    
    function getCustomFieldSphinxQL($values) {

        $sql = array();
        $sql['select'] = '';
        $sql['where'] = '';
        $sql['match'] = '';
    
        $select = array();
        $where = array();
        $match = array();
        
        $cfields = $this->getCustomFieldByEntryType();
        
        foreach($values as $field_id => $v) {
            
            $field_id = (int) $field_id;
			
			// multiple inputs should be array, goes from api as string
			if(in_array($cfields[$field_id]['input_id'], self::$multiple_inputs)) {
				if(!is_array($v)) {
					$v = explode(',', $v);
				}
			}
			
			
            // empty
            if($v == '') {
                
            
            // text
            } elseif (in_array($cfields[$field_id]['input_id'], self::$text_inputs)) {
                $match[] = $v;
                
            // numeric
            } elseif(in_array($cfields[$field_id]['input_id'], self::$numeric_inputs)) {
                $where[] = sprintf('custom.%d = %d', $field_id, (int) $v);
                
            // multi ranges        
            } elseif (is_array($v)) {
                
                $in = array();
                foreach($v as $rvalue) {
                    $in[] = sprintf('IN(custom.%d, %s)', $field_id, $rvalue);
                }
                
                $select[] = implode(' + ', $in) . ' as _custom' . $field_id;
                $where[] = sprintf('_custom%d = %d', $field_id, count($v));
            
			} else {
            	$match[] = $v;
            }
        }
        
        $sql['select'] = implode("\n", $select);
        
        if($where) {
            $sql['where'] = sprintf('(%s)', implode(" AND ", $where));
        }
        
        if($match) {
            $sql['match'] = '@custom_text ' . implode(' ', $match);
        }
    
		// echo '<pre>', print_r($values,1), '<pre>';
        // echo '<pre>', print_r($sql, 1), '</pre>';
        return $sql;
    }

    
    static function getFieldTypesWithRange() {
        return array(2,3,6,7);
    }


    static function getFieldTypesWithRangeMultiple() {
        // return array(3,6);
		return self::$multiple_inputs;
    }


    static function getFieldTypesWithValidation() {
        return array(1,4,8);
    }
 

    static function isFieldTypeWithRange($type) {
        return !(in_array($type, CommonCustomFieldModel::getFieldTypesWithRange()) === false);
    }
    
    
    static function isFieldTypeWithValidation($type) {
        return !(in_array($type, CommonCustomFieldModel::getFieldTypesWithValidation()) === false);
    }


    function validate($fields, $values) {
        
        // required
        $missed = array();
        foreach($fields as $id => $val) {
            if ($val['is_required']) {
                if (empty($values['custom'][$id])) {
                    $missed[] = sprintf('custom[%d]', $id);
                }
            }
        }
        
        if($missed) {
            return array('required_msg', $missed, 'custom_fields', 'key');
        }
        
        return $this->validateUserDefined($fields, $values);
    }
    
    
    function validateUserDefined($fields, $values) {
        foreach($fields as $id => $val) {
            if ($val['valid_regexp']) {
                if (!empty($values['custom'][$id])) {
                    if (!preg_match($val['valid_regexp'], $values['custom'][$id])) {
                        return array($val['error_message'], sprintf('custom[%d]', $id), 'custom_fields', 'custom');
                    }
                }
            }
        }
        
        return false;
    }
    
    
    function save($value, $record_id, $more_value = array()) {

        $data = array();
        $record_id = (is_array($record_id)) ? $record_id : array($record_id);
        
        foreach($value as $id => $v) {
            foreach($record_id AS $entry_id) {
                                
                // add existing data in bulk
                if(isset($more_value[$entry_id][$id])) {
                    $v2 = array_unique(array_merge($v, $more_value[$entry_id][$id]));
                } else {
                    $v2 = $v;
                }
                
                $ins = $v2;
                if (is_array($ins)) {
                    $ins = implode(',', $ins);
                }

                if (trim($ins) != '') {
                    $data[] = array($entry_id, $id, $ins);
                }
            }
        }
        
        // echo '<pre>value: ', print_r($value, 1), '</pre>';
        // echo '<pre>more_value: ', print_r($more_value, 1), '</pre>';
        // echo '<pre>data: ', print_r($data, 1), '</pre>';
                       
        if($data) {
            $sql = MultiInsert::get("INSERT IGNORE {$this->etable} (entry_id, field_id, data) 
                                     VALUES ?", $data);        
            return $this->db->Execute($sql) or die(db_error($sql)); 
        }  
    }


     // DELETE RELATED // -------------------

    function delete($entry_id) {
        $sql = "DELETE FROM {$this->etable} WHERE entry_id IN ({$entry_id})";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function deleteByFieldId($id) {
        $sql = "DELETE FROM {$this->etable} WHERE field_id IN ({$id})";
        return $this->db->Execute($sql) or die(db_error($sql));    
    }
    

    // for bulk
    function deleteByEntryIdAndFieldId($entry_id, $field_id) {
        $sql = "DELETE FROM {$this->etable} WHERE entry_id IN ({$entry_id}) AND field_id IN ({$field_id})";
        return $this->db->Execute($sql) or die(db_error($sql));    
    }
    
    
    // when category deleted, remove from  all assignment from field_to_category
    function deleteFieldToCategory($cat_id, $type) {
        $sql = "DELETE fc
        FROM {$this->tbl->field} f, {$this->tbl->field_to_category} fc  
        WHERE f.id = fc.field_id 
        AND f.type_id = '{$type}' 
        AND fc.category_id IN ({$cat_id})";
        
        // echo '<pre>', print_r($sql, 1), '</pre>';
        return $this->db->Execute($sql) or die(db_error($sql));
    }

}
?>