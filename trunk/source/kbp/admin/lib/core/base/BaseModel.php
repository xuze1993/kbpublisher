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

class BaseModel extends BaseApp
{
    
    var $tbl_pref_custom;
    var $tbl_pref;    
    var $tables = array();
    var $custom_tables = array();
    var $db;
    var $limit;
    var $tbl;
    
    var $sql_params = '1';                       // will be added to select rows
    var $sql_params_ar = array();
    var $sql_params_default = '1';
    
    var $sql_params_select = '1';        
    var $sql_params_select_ar = array();
    
    var $sql_params_from = '';               
    var $sql_params_from_ar = array();
    
    var $sql_params_join = '';               
    var $sql_params_join_ar = array();
    
    var $sql_params_order = ''; 
    
    var $record_type = array(
        1 => 'article', 2 => 'file', 3 => 'news', 4 => 'topic',
        7 => 'article_draft', 8 => 'file_draft', 
        10 => 'user', 20 => 'feedback', 21 => 'glossary',
        22 => 'tag', 30 => 'email', 31 => 'comment',
        32 => 'rating_feedback');

    var $record_type_to_table = array(
        1 => 'kb_entry', 2 => 'file_entry', 3 => 'news', 4 => 'forum_entry', 
        10 => 'user', 20 => 'feedback');

    var $category_type_to_table = array(
        11 => 'kb_category', 12 => 'file_category', 14 => 'forum_category');
    
    var $category_type_to_etoc_table = array(
        11 => 'kb_entry_to_category', 12 => 'file_entry_to_category', 
        14 => 'forum_entry_to_category');

    var $no_private_priv = array(1); // for this priv private entry does not matter

    // nums in private field in tables
    // 1 = read/write, 2 = write, 3 = read
    var $private_rule = array(
        'read'  => array(1,3), 
        'write' => array(1,2));

    var $entry_type_to_url = array(
        1 => array('knowledgebase', 'kb_entry'), 
        2 => array('file', 'file_entry'), 
        3 => array('news', 'news_entry'), 
        4 => array('forum', 'forum_entry'),
        7 => array('knowledgebase', 'kb_draft'),
        8 => array('file', 'file_draft'), 
        10 => array('users', 'user'), 
        20 => array('feedback', 'feedback')
    );
    
    var $entry_task_rules = array(
        1 => 'update_index_body',
        2 => 'update_meta_keywords', // on upgrade to 5.0, import 
        3 => 'sync_meta_keywords',    // when updated in tags, sync in meta_keywords
        4 => 'sphinx_restart',
        5 => 'sphinx_index',
        6 => 'sphinx_stop',
        7 => 'sphinx_files'
    );
    
    

    function __construct() {

        $reg =& Registry::instance();
        $this->db         =& $reg->getEntry('db');
        $this->tbl_pref   =& $reg->getEntry('tbl_pref');
        $this->limit      =& $reg->getEntry('limit');
        $this->extra      =& $reg->getEntry('extra');
        $this->priv       =& $reg->getEntry('priv');
        
        $this->tbl = $this->setTableNames();
    }
    
    
    function setTableNames() {
        $t = array();
        $t1 = $this->_setTableNames($this->tables, $this->tbl_pref, $this->tbl_pref_custom);
        $t2 = $this->_setTableNames($this->custom_tables, $this->tbl_pref);
        
        return (object) array_merge($t1, $t2);
    }
    
    
    static function _setTableNames($tables, $tbl_pref = '', $tbl_pref_custom = '') {
        $tbl =  array(); 
        foreach($tables as $k => $v) {
            $t = (!is_int($k)) ? $k : $v;
            $tbl[$t] = $tbl_pref . $tbl_pref_custom . $v;
        }
        
        return $tbl;
    }
    
    
    // set diffrent sql params used when list records
    function setSqlParams($params, $key = null, $empty = false) {
        static $i = 0;
        $key = ($key) ? $key : $i++;
        if($empty) { $this->sql_params_ar = array(); }
        
        $this->sql_params_ar['default'] = $this->sql_params_default;
        $this->sql_params_ar[$key] = $params;
        $this->sql_params = implode(' ', $this->sql_params_ar);
    }
    
    
    // set diffrent sql params used when list records
    function setSqlParamsSelect($params, $key = null, $empty = false) {
        static $i = 0;
        $key = ($key) ? $key : $i++;
        if($empty) { $this->sql_params_select_ar = array(); }
        
        $this->sql_params_select_ar['default'] = 1;
        if($params) {
            $this->sql_params_select_ar[$key] = $params;
        }
        
        $this->sql_params_select = implode(', ', $this->sql_params_select_ar);
    }
    
    
    // set diffrent sql params used when list records
    function setSqlParamsFrom($params, $key = null, $empty = false) {
        static $i = 0;
        $key = ($key) ? $key : $i++;
        if($empty) { $this->sql_params_from_ar = array(); }
        
        //$this->sql_params_from_ar['default'] = '';
        $this->sql_params_from_ar[$key] = $params;
        $this->sql_params_from = implode(', ', $this->sql_params_from_ar);
    }    
    
    
    // set diffrent sql params used when list records
    function setSqlParamsJoin($params, $key = null) {
        static $i = 0;
        $key = ($key) ? $key : $i++;
        
        //$this->sql_params_from_ar['default'] = '';
        $this->sql_params_join_ar[$key] = $params;
        $this->sql_params_join = implode("\n", $this->sql_params_join_ar);
    }    
    
    
    function setSqlParamsOrder($val) {
        $this->sql_params_order = $val;
    }
    
        
    function getValuesArray($data, $id_field = 'id', $unique = false) {
        $ids = array();
        foreach($data as $k => $v) {
            $ids[] = $v[$id_field];
        }
        
        return ($unique) ? array_unique($ids) : $ids;
    }
    
    
    function getValuesString($data, $id_field = 'id', $unique = false) {
        return implode(',', $this->getValuesArray($data, $id_field, $unique));
    }

    
    function isExtra($module) {
        return (!empty($this->extra[$module]));
    }
    
    
    function getExplainQuery($db, $sql) {
        require_once 'adodb/tohtml.inc.php';
        $sql = 'EXPLAIN ' . $sql;
        $result = $db->Execute($sql);
        
        $ret = "<pre>" . print_r($sql, 1) . "</pre>";    
        $ret .= rs2html($result,'border=2 cellpadding=3'); 
        
        return $ret;
    }
    
    
    function getNow() {
        return date('Y-m-d H:i:s');
    }
    
    
    function getCurdate() {
        return date('Y-m-d');
    }
    
    
    function getCurtime() {
        return date('H:i:s');
    }
    
    
    static function getPlugingSetting($setting) {
        if(!isset($setting['plugin_export_key'])) {
            $setting = new SettingModel();
            $setting = &$setting->getSettings(array(140));
        }
    
        return $setting;
    }
    
    
    // htmldoc
    static function isPluginExport($setting = array()) {
        $ret = false;
        $setting = BaseModel::getPlugingSetting($setting);

        if(strtolower($setting['plugin_htmldoc_path']) != 'off') {
            $key = $setting['plugin_export_key'];
            $ret = true;
            // if(empty($key) || strtolower($key) == 'demo') {
            if(strtolower($key) == 'demo') {
                $ret = 'demo';
            }
        }
            
        return $ret;
    }
    
    
    // WKHTMLTOPDF
    static function isPluginExport2($setting = array()) {
        $ret = true;
        $setting = BaseModel::getPlugingSetting($setting);
        
        $key = $setting['plugin_export_key'];
        // if(empty($key) || strtolower($key) == 'demo') {
        if(strtolower($key) == 'demo') {
            $ret = 'demo';
        }
        
        return $ret;
    }
    
    
    static function isPluginExport2Pdf($setting = array()) {
        $setting = BaseModel::getPlugingSetting($setting);
        $ret = BaseModel::isPluginExport2($setting);
        if(strtolower($setting['plugin_wkhtmltopdf_path']) == 'off') {
            $ret = false;
        }
        
        return $ret;
    }
    
    
    // return demo or true if enabled, false otherwise 
    static function isPluginPdf($setting = array()) {
        
        $setting = BaseModel::getPlugingSetting($setting);
        
        // wkhtmltopdf
        $ret = BaseModel::isPluginExport2Pdf($setting);
        
        // htmldoc
        if(!$ret) {
            $ret = BaseModel::isPluginExport($setting);
        }
        
        return $ret;        
    }
    
    
    // return tool name if enabled, false otherwise 
    static function getPluginPdf($setting = array()) {
        $ret = false;
        $setting = BaseModel::getPlugingSetting($setting);
        
        // wkhtmltopdf
        if(BaseModel::isPluginExport2($setting)) {
            if(strtolower($setting['plugin_wkhtmltopdf_path']) != 'off') {
                $ret = 'wkhtmltopdf';
            }
        }
        
        // htmldoc
        if(!$ret) {
            if(BaseModel::isPluginExport($setting)) {
                $ret = 'htmldoc';
            }
        }
        
        return $ret;        
    }
    
    
    
    // MODULES // ------------------------
    
    static function getExtraModules() {
        
        $emodules = array(
            332 => 'report', 
            333 => 'automation', 
            334 => 'workflow',
            335 => 'forum');
            
        return $emodules;
    }
    
    
    
    // use this to hide some modules, functionality
    static function isModule($module, $setting = array()) {
        
        $ret = false;
        $setting_key = 'emodule_' . $module;
        
        if(isset($setting[$setting_key])) {
            $ret = $setting[$setting_key];
        } else {
            $reg =& Registry::instance();
            $setting = $reg->getEntry('setting');
            
            if(isset($setting[$setting_key])) {
                $ret = $setting[$setting_key];
            } else {
                $ret = SettingModel::getQuick(0, $setting_key, true);
            }
        }
        
        // return true;
        return $ret;
    }
    
    
    // CLOUD // --------------------------
    
    static function isCloud() {
        $ret = false;
        if(defined('KBP_CLOUD')) {
            $ret = (KBP_CLOUD);
        }
        
        // $ret = true;
        return $ret;
    }
    
    
    static function getCloudHideTabs() {
        $hide_tabs_cloud = array(
            'licence_setting',
            'file_bulk',
            'file_rule',
            'rauth_setting',
            'sphinx_setting', // in 6.0 only
        );
        
        return $hide_tabs_cloud;
    }
    
    
    static function getCloudHideSettigs() {
        $keys = array(
            'html_editor_upload_dir', 'cache_dir', 'file_dir', 
            'file_extract_pdf', 'file_extract_doc', 'file_extract_doc2',
            'directory_missed_file_policy',
            'cron_mail_critical',
            'mass_mail_send_per_hour',
            // 'remote_auth_script', 'remote_auth_script_path',
            'remote_auth_auto', 'remote_auth_auto_script_path',
            'plugin_htmldoc_path', 'plugin_wkhtmltopdf_path',
            'sphinx_host', 'sphinx_port', 'sphinx_bin_path', 'sphinx_data_path'
            );
            
        return $keys;
    }
    
    
    static function getCloudPluginKeys() {
        $keys = array(
            'plugin_export_key', 
            'license_key4' // copyright 
            );
            
        return $keys;
    }
    
    
    static function getCloudSkipDeafaults() {
        $keys = BaseModel::getCloudHideSettigs();
        $keys = array_merge($keys, BaseModel::getCloudPluginKeys());
        return $keys;
    }
    
}
?>