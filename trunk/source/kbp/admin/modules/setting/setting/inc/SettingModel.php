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

require_once APP_MODULE_DIR . 'setting/setting/inc/SettingParserCommon.php';
require_once APP_MODULE_DIR . 'setting/setting/inc/SettingParserModelCommon.php';


class SettingModel extends AppModel
{

    var $tables = array('table'=>'setting', 'setting', 
                        'setting_to_value',
                        'setting_to_value_user',
                        'priv', 'priv_module');
    
    var $module_id;
    var $module_name;
    var $array_delim = ',';
    var $parser;
    var $user_id = 0;
    var $separate_form = false;
    
    var $setting_input = array(
        0 => false,
        1 => 'select',
        2 => 'text',
        3 => 'textarea',
        4 => 'checkbox',
        5 => 'password',
        6 => 'text_btn',
        7 => 'hidden_btn',
        8 => 'checkbox_btn',
        9 => 'info'
    );
    
    
    static function getQuick($module_id, $setting_key = false, $ignore_parser = false) {
        $m = new SettingModel();
        return $m->getSettings($module_id, $setting_key, $ignore_parser);
    }
    
    
    static function getQuickCron($module_id, $setting_key = false, $ignore_parser = false) {
        $m = new SettingModel();
        $m->error_die = false;
        return $m->getSettings($module_id, $setting_key, $ignore_parser);
    }
    
    
    static function &getQuickUser($user_id, $module_id, $setting_key = false, $ignore_parser = false) {
        $m = new SettingModel();
        $setting = &$m->getSettings($module_id, $setting_key, $ignore_parser);
        $user_setting = &$m->getSettingsUser($user_id, $module_id, $setting_key, $ignore_parser);
        
        if($setting_key) {
            if($user_setting) {
                $setting = $user_setting;
            }
        } else {
            $setting = array_merge($setting, $user_setting);
        }
        
        return $setting;
    }        
    
    
    function &getSettingsUser($user_id, $module_id, $setting_key, $ignore_parser = false) {
        
        $key_param = ($setting_key) ? "s.setting_key = '$setting_key'" : '1';
        
        $sql = "
        SELECT 
            s.setting_key,
            s.input_id,
            sv.setting_value AS value
        FROM 
            ({$this->tbl->setting} s,
            {$this->tbl->setting_to_value_user} sv)
            
        WHERE 1
            AND $key_param
            AND s.id = sv.setting_id 
            AND sv.user_id = %d
            AND s.active = 1 
            AND s.user_module_id IN(%s)";
            
        $data = array();
        $module_id = ($module_id !== false) ? $module_id : $this->module_id;
        $module_id = (is_array($module_id)) ? implode(',', $module_id) : $module_id;
        
        $sql = sprintf($sql, $user_id, $module_id);
        //echo '<pre>', print_r($this->getExplainQuery($this->db, $sql), 1), '</pre>';
        
        $result = $this->db->Execute($sql) or die(db_error($sql));        
        $rows = $result->GetArray();
        $parser = $this->getParser($ignore_parser);

        foreach($rows as $k => $v) {
            
            if($v['input_id'] == 1 && strpos($v['value'], $this->array_delim)) {
                $data[$v['setting_key']] = $this->_valueToArray($v['value']);
            } else {
                $data[$v['setting_key']] = $v['value'];
            }
            
            $data[$v['setting_key']] = $parser->parseReplacements($v['value']);
            unset($rows[$k]);
        }    
        
        $data = ($setting_key && isset($data[$setting_key])) ? $data[$setting_key] : $data;
        return $data;
    }
    
    
    function getSettingsSql($setting_key) {

        $key_param = ($setting_key) ? "s.setting_key = '$setting_key'" : '1';
        
        $sql = "
        SELECT 
            s.setting_key,
            s.input_id,
            IFNULL(sv.setting_value, s.default_value) AS value
        FROM 
            {$this->tbl->setting} s
        LEFT JOIN 
            {$this->tbl->setting_to_value} sv ON s.id = sv.setting_id
            
        WHERE 1
            AND $key_param
            AND s.active = 1 
            AND s.module_id IN(%s)";
            
        return $sql;
    }
    
    
    function &getSettings($module_id = false, $setting_key = false, $ignore_parser = false) {
        
        $data = array();
        $module_id = ($module_id !== false) ? $module_id : $this->module_id;
        $module_id = (is_array($module_id)) ? implode(',', $module_id) : $module_id;
        
        $sql = $this->getSettingsSql($setting_key);
        $sql = sprintf($sql, $module_id);
        //echo '<pre>', print_r($this->getExplainQuery($this->db, $sql), 1), '</pre>';
        
        $result = $this->db->Execute($sql);
        if(!$result) {
            return $this->db_error2($sql);
        }
        
        $rows = $result->GetArray();
        $parser = $this->getParser($ignore_parser);
        //echo '<pre>', print_r($parser, 1);

        foreach($rows as $k => $v) {
            
            if($v['input_id'] == 1 && strpos($v['value'], $this->array_delim)) {
                $data[$v['setting_key']] = $this->_valueToArray($v['value']);
            } else {
                $data[$v['setting_key']] = $v['value'];
            }
            
            $data[$v['setting_key']] = $parser->parseReplacements($v['value']);
            unset($rows[$k]);
        }    
        
        $data = ($setting_key) ? $data[$setting_key] : $data;
        return $data;
    }
    
    
    function getRecordsSql() {
        
        $sql = "
        SELECT 
            s.id AS name,
            s.setting_key,    
            s.messure,
            s.options,
            s.range,
            s.group_id,
            s.required,
            s.default_value AS value,
            s.input_id
        FROM 
            {$this->tbl->setting} s
                
        WHERE s.active = 1 
        AND s.module_id = '%d'
        ORDER BY s.module_id, s.group_id, s.sort_order";
        
        $sql = sprintf($sql, $this->module_id);
        //echo "<pre>"; print_r($sql); echo "</pre>";
        
        return $sql;
    }    
    
    
    function &getRecords($limit = -1, $offset = -1) {
        
        $data = array();
        $sql = $this->getRecordsSql();
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $parser = $this->getParser();
        
        while($row = $result->FetchRow()){
            
            // here we can miss some fields
            if($parser->skipSettingDisplay($row['setting_key'])) {
                continue;
            }
            
            if($parser->skipSettingDisplayCloud($row['setting_key'])) {
                continue;
            }
            
            $row['input'] = $this->setting_input[$row['input_id']];
            
            if($row['input'] == 'select') {
                $row['value'] = $this->_valueToArray($row['value']);
            }
                        
            if($row['range'] !== '' && $row['range'] != 'dinamic') {
                $row['range'] = $this->_valueToArray($row['range']);
            }    
            
            $group_id = $parser->parseGroupId($row['group_id']);
            $data[$group_id][$row['setting_key']] = $row;
        }
        
        ksort($data);
        return $data;
    }
    
    
    function getSettingInputTypes() {

        $sql = "SELECT s.id, s.input_id
        FROM {$this->tbl->setting} s
        WHERE s.active = 1 
        AND s.module_id = '%d'";
                
        $data = array();
        $sql = sprintf($sql, $this->module_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        while($row = $result->FetchRow()) {
            $data[$row['id']] = $this->setting_input[$row['input_id']];
        }

        return  $data;        
    }
    
    
    function getSettingKeys() {
        $sql = "SELECT s.id, s.setting_key
        FROM {$this->tbl->setting} s
        WHERE s.active = 1 
        AND s.module_id = '%d'"; 
		
        $sql = sprintf($sql, $this->module_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return  $result->GetAssoc();
    }
    
    
    function getSettingIdByKey($key) {
        $keys = (is_array($key)) ? $key : array($key);
        
        foreach (array_keys($keys) as $k) {
            $keys[$k] = sprintf('"%s"', $keys[$k]);
        }
        
        $sql = "SELECT setting_key, id 
        FROM {$this->tbl->setting} 
        WHERE setting_key IN (%s)";
        $sql = sprintf($sql, implode(',', $keys));
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return (is_array($key)) ? $result->GetAssoc() : $result->Fields('id');
    }
        
    
    function formDataToObj($values) {
        $values1 = array();
        $keys = $this->getSettingKeys();
        foreach($values as $k => $v) {
            $values1[$keys[$k]] = $v;
        }
        
        return $values1;
    }
    
    
    function saveQuery($data) {
        require_once 'eleontev/SQL/MultiInsert.php';
        $ins = new MultiInsert;
        $ins->setFields(array('setting_id', 'setting_value'));
        $ins->setValues($data);
        $sql = $ins->getSql($this->tbl->setting_to_value, 'REPLACE');
        
        // echo '<pre>', print_r($data, 1), '</pre>';
        // echo '<pre>', print_r($sql, 1), '</pre>';
        // exit;
        
        $this->db->Execute($sql) or die(db_error($sql));        
    }
    
    
    function save($data) {
        
        $input = $this->getSettingInputTypes();
        $keys  = array_flip($this->getSettingKeys());
        
        // handle checkboxex, set 0 value for not checked (not in data)
        foreach($keys as $setting_key => $setting_id) {
            if($input[$setting_id] == 'checkbox' && !isset($data[$setting_key])) {
                $data[$setting_key] = 0;
            }
        }
        
        // handle data
        $data1 = array();
        foreach($data as $setting_id => $v) {
            if($input[$keys[$setting_id]] == 'select') {
                $v = $this->_valueToString($v);
            }
                       
            $data1[$keys[$setting_id]] = array($keys[$setting_id], $v);
        }         
                
        $this->saveQuery($data1);
    }
    
    
    function setSettings($data) {
        
        $data1 = array();
        foreach($data as $setting_id => $setting_value) {
            $data1[] = array($setting_id, $setting_value);
        }
        
        if($data1) {
            $this->saveQuery($data1);            
        }
    }
        
    
    function getDefaultValuesSql() {
        $sql = "SELECT id AS setting_id, default_value AS setting_value 
        FROM {$this->tbl->setting} WHERE module_id = %d 
        AND active = 1 AND skip_default = 0";
        
        return $sql;        
    }
    
    
    function setDefaultValues($setting_id = false) {
        $sql = $this->getDefaultValuesSql();
        $sql = sprintf($sql, $this->module_id);
        
        if ($setting_id) {
            $sql .= ' AND id = ' . $setting_id;
        }
        
        $result = $this->db->Execute($sql) or die(db_error($sql));      
        
        // skip in cloud 
        $skip = array();
        if(BaseModel::isCloud()) {
            $keys = BaseModel::getCloudSkipDeafaults();
            $skip = $this->getSettingIdByKey($keys);
        }
        
        $parser = $this->getParser();
        $data = array();
        foreach($result->GetArray() as $k => $v) {
            
            // skip cloud values
            if(in_array($v['setting_id'], $skip)) {
                continue;
            }
            
            $data[$k]['setting_id'] = $v['setting_id'];
            $data[$k]['setting_value'] = $parser->parseReplacements($v['setting_value']);
        }
        
        $this->saveQuery($data);
    }
    
    
    function setModuleId($module_name) {
        $sql = "SELECT id FROM {$this->tbl->priv_module} WHERE module_name = '%s'";
        $sql = sprintf($sql, $module_name);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        $this->module_id = $result->Fields('id');
        $this->module_name = $module_name;
    }
    
    
    // set default_value
    function updateDefaultValue($id, $value) {
        $sql = "UPDATE {$this->tbl->setting} 
        SET default_value = '%s' WHERE id = '%d'";
        $sql = sprintf($sql, $value, $id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    // HELPERS // ----------------------------    
    
    function _valueToArray(&$val) {
        
        $val = explode($this->array_delim, $val);
        $new_ar = array();
        
        foreach($val as $v) {
            $new_ar[trim($v)] = trim($v);
        }
        return $new_ar;
    }
    
    
    function _valueToString($val) {
        if(is_array($val)) {
            $val = implode($this->array_delim, $val);
        }
        
        return $val; 
    }
    
    
    function _valueToOtherArray(&$val) {
    
        $val = explode($this->array_delim, $val);
        
        foreach($val as $k => $v) {
            $val[trim($k)] = explode('-', $v);
        }
        
        foreach($val as $k => $v) {
            $arr1[$val[$k][0]] = $val[$k][1];
        }
        
        return $arr1;
    }

    
    function &getParser($ignore_concrete = false) {
        if(!$this->parser) {
            $this->loadParser($ignore_concrete);
        }
        
        return $this->parser;
    }    
    
    
    function loadParser($ignore_concrete = false) {
        
        $is_parser = false;
        $is_manager = false;
        
        if(!$ignore_concrete) {
        
            $file[] = APP_MODULE_DIR . $this->module_name . '/';
            $file[] = APP_EXTRA_MODULE_DIR . $this->module_name . '/';
            $file[] = APP_MODULE_DIR . 'setting/' . $this->module_name . '/';
            
            foreach($file as $v) {
                $f = $v . 'SettingParser.php';
                if(file_exists($f)) {
                    $is_parser = true;
                    require_once $f;
                    
                    $f = $v . 'SettingParserModel.php';
                    if(file_exists($f)) {
                        $is_manager = true;
                        require_once $f;
                    }
                    
                    break;
                }
            }
        }
        
        $this->parser = ($is_parser) ? new SettingParser($this) : 
                                       new SettingParserCommon($this);
        
        $this->parser->manager = ($is_manager) ? new SettingParserModel() : 
                                                 new SettingParserModelCommon();
    }
    
    
    function upload() {
            
        require_once 'eleontev/Dir/Uploader.php';
    
        $upload = new Uploader;
        $upload->store_in_db = false;
        
        $upload->setMaxSize(48); // due to the text datatype limit of 64kb
        $upload->setAllowedExtension('jpg', 'png', 'gif');
        $upload->setUploadedDir(APP_CACHE_DIR);
        
        $f = $upload->upload($_FILES);

        if(isset($f['bad'])) {
            $f['error_msg'] = $upload->errorBox($f['bad']);
            
        } else{
            $f['filename'] = APP_CACHE_DIR . $f['good'][1]['name'];
        } 
                                   
        return $f;
    }
}
?>