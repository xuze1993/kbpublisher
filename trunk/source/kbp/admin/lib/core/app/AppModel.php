<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2007 Evgeny Leontev                                    |
// +----------------------------------------------------------------------+
// | This source file is free software; you can redistribute it and/or    |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This source file is distributed in the hope that it will be useful,  |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// +----------------------------------------------------------------------+

require_once 'eleontev/SQL/ModifySql.php'; 


class AppModel extends BaseModel
{
          
    var $delete_mode = 1; // or 2 (1- delete where id IN (1), 2- delete where id IN (1,2,3)) 
    var $id_field = 'id';
    var $error_die = true;
    
        
    function checkPriv(&$priv, $action) {
        $priv->check($action);
    }
    
    
    // could be used in PHP5 for to interact with obj classes
    //function __call($method, $args){
    //    return call_user_func_array(array($this->obj, $method), $args);
    //}
    
    
    // COMMON DB ACTIONS & SQL // ---------------------
    
    function getById($record_id) {
        
        $sql = "SELECT * FROM {$this->tbl->table} WHERE {$this->sql_params} AND {$this->id_field} = %d";
        $sql = sprintf($sql, $record_id);
        $result = $this->db->Execute($sql);
        //echo $this->getExplainQuery($this->db, $result->sql);
        
        if(!$result) {
            return $this->db_error2($sql);
        }
        
        return $result->FetchRow();
    }
    
    
    function getRecords() {
        
        // php 5.4 fix, Strict Standards
        $args = func_get_args();
        $limit = (isset($args[0])) ? $args[0] : -1;
        $offset = (isset($args[1])) ? $args[1] : -1;
        
        $result =& $this->getRecordsResult($limit, $offset);
        return $result->GetArray();
    }
    
    
    function &getRecordsResult($limit = -1, $offset = -1) {
        $sql = $this->getRecordsSql();
                
        if($limit == -1) {
            $result = $this->db->Execute($sql);
        } else {
            $result = $this->db->SelectLimit($sql, $limit, $offset);
        }
        
        if(!$result) {
            return $this->db_error2($sql);
        }
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        //echo "<pre>"; print_r($result); echo "</pre>";
        //echo "<pre>"; print_r($result->GetArray()); echo "</pre>";
        // echo $this->getExplainQuery($this->db, $result->sql);
        
        return $result;
    }
    
    
    function getRecordsSql() {
        $sql = "SELECT * FROM {$this->tbl->table} WHERE {$this->sql_params} {$this->sql_params_order}";
        return $sql;
    }
    
    
    function getCountRecordsSql() {
        return $this->getRecordsSql();
    }
    

    function getCountRecords() {        
        
        $sql = $this->getCountRecordsSql();
        $data =  $this->db->GetCol($sql);
        
        if($data === false) {
            return $this->db_error2($sql);
        }
        
        return $data[0];
    }    
    
    function add($obj) {
        $sql = ModifySql::getSql('INSERT', $this->tbl->table, $obj->get());
        
        // echo "<pre>"; print_r($sql); echo "</pre>";
        // exit();
        
        $result = $this->db->Execute($sql);

        if(!$result) {
            return $this->db_error2($sql);
        }

        return $this->db->Insert_ID(); 
    }
        
    
    function update($obj) {
        $sql = ModifySql::getSql('UPDATE', $this->tbl->table, $obj->get(), false, $this->id_field);
        
        // echo "<pre>"; print_r($sql); echo "</pre>";
        // exit();
        
        $result = $this->db->Execute($sql);

        if(!$result) {
            return $this->db_error2($sql);
        }
        
        return $this->db->Insert_ID(); // not correct for update
    }
    
    
    function save($obj) {
        $sql = ModifySql::getSql('REPLACE', $this->tbl->table, $obj->get());
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        //exit();
        
        $result = $this->db->Execute($sql);

        if(!$result) {
            return $this->db_error2($sql);
        }
        
        return $this->db->Insert_ID(); // not correct for replace
    }
    
    
    function saveAddUpdate($obj) {
        if($obj->get('id')) {
            return $this->update($obj);
        } else {
            return $this->add($obj);
        }
    }
    
    
    function status($value, $id, $field = 'active') {
        $sql = "UPDATE {$this->tbl->table} SET $field='%d' WHERE {$this->id_field} IN (%s)";
        $sql = sprintf($sql, $value, $this->idToString($id));
        
        $result = $this->db->Execute($sql);
        
        if(!$result) {
            return $this->db_error2($sql);
        }
        
        return $result;
    }
    
    
    function delete($record_id) {
        
        // convert to string 1,2,3... to use in IN()
        $record_id = $this->idToString($record_id);
        
        $sql = "DELETE FROM {$this->tbl->table} WHERE {$this->id_field} IN (%s)";
        $sql = sprintf($sql, $record_id);
        
        $result = $this->db->Execute($sql);

        if(!$result) {
            return $this->db_error2($sql);
        }
        
        return $result;
    }
    
    
    function deleteOnTrash($record_id) {
        return $this->delete($record_id);
    }
    
    
    function trash($record_id, $obj = false) {
        $this->putToTrash($obj);
        $this->deleteOnTrash($record_id);
    }
    
    
    function putToTrash($obj) {
        
        $data = array();
        $data['entry_id'] = $obj->get('id');
        $data['entry_type'] = $this->entry_type;
        $data['entry_obj'] = addslashes(serialize($obj));
        $data['user_id'] = AuthPriv::getUserId();
        
        $sql = ModifySql::getSql('REPLACE', $this->tbl->entry_trash, $data);
        $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function getTables($skip = array()) {
        $tables = array();
        foreach($this->tbl as $k => $v) {
            if(in_array($k, $skip)) {
                continue;
            }
            
            $tables[] = $v;
        }
        
        return array_unique($tables);
    }
    
    
    function lockTables($tables) {
        $tables = implode(' READ, ', array_unique($tables)) . ' READ';
        $sql = "LOCK TABLES {$tables}";
        $this->db->Execute($sql) or die(db_error($sql));        
    }    
    
    
    function unlockTables() {
        $sql = "UNLOCK TABLES";
        $this->db->Execute($sql) or die(db_error($sql));            
    }
    
    
    // UTIL TO GENERATE SQL // ---------------------
    
    // convert to string 1,2,3... to use in IN()
    function idToString($val) {
        
        if(is_array($val)) { 
            
            if($this->delete_mode == 2) {
                $val = implode(',', $val); 
            } else {
                exit('WRONG UTIL SQL');
            }
            
        } else { 
            $val = (int) $val; 
        }
        
        return $val;
    }
    
    
    // when we need die in some cases and not die in othres 
    // in cron for example 
    function db_error2($sql) {
        
        if($this->error_die) {
            die(db_error($sql));
        }
        
        $str = DBUtil::getErrorShortString($this->db->ErrorMsg(), $this->db->ErrorNo());
        trigger_error($str);
        return false;        
    }
    
}
?>