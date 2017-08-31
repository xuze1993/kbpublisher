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

class CommonImportView
{    

    static function getImportFormBlock($manager, $skip, $required, $recommended) {
        
        $tpl = new tplTemplatez(APP_MODULE_DIR . 'import/article_import/template/block_import_form.html');
        
       
        // table fields
        $fields = $manager->getFields();
        $index = $manager->getIndex();
       
        //echo '<pre>', print_r($fields, 1), '</pre>';
        //echo '<pre>', print_r($index, 1), '</pre>';

        $generated = (!empty($_POST['generated'])) ? $_POST['generated'] : array_merge($required, $recommended);
    
        $i = 1; $a = array(); 
        foreach($fields as $k => $v) {
            if(in_array($k, array_merge($skip, $generated))) { 
                continue; 
            }
            
            $req = (in_array($k, $required)) ? '* ' : '';
            // $default = (!empty($fields[$v]['Default'])) ? ', DEFAULT ' . $fields[$v]['Default'] : '';

            $default = '';
            if(isset($v['Default']) && $v['Default'] != '') {
                $default = ', DEFAULT ' . $v['Default'];
            }
            
            
            $key = '';
            if(isset($index[$k])) {
                if($index[$k]['Key_name'] == 'PRIMARY') {
                    $_key = $index[$k]['Key_name'];
                } elseif($index[$k]['Index_type'] == 'FULLTEXT') {
                    $_key = $index[$k]['Index_type'];
                } elseif($index[$k]['Non_unique'] == 0) {
                    $_key = 'UNIQUE';
                } else {
                    $_key = 'INDEX';
                }
            
                $str = ', <span style="color:#dc143c;">%s</span>';
                $key = sprintf($str, $_key);
            }


            $a['num'] = $i++;
            $a['field_value'] = $k;
            $a['field_title'] = $req . $k . ' - ' . $v['Type'] . $default . $key;
            
            $tpl->tplParse($a, 'fields1');
        }
        
        $tpl->tplAssign('num_drop_rows', count($fields) - count($skip));
        
        $i = 1; $a = array(); 
        foreach($generated as $k => $v) {
            
            $req = (in_array($v, $required)) ? '* ' : '';
            // $default = (!empty($fields[$v]['Default'])) ? ', DEFAULT ' . $fields[$v]['Default'] : '';

            $default = '';
            if(isset($fields[$v]['Default']) && $fields[$v]['Default'] != '') {
                $default = ', DEFAULT ' . $fields[$v]['Default'];
            }
            
            $key = '';
            if(isset($index[$v])) {
                if($index[$v]['Key_name'] == 'PRIMARY') {
                    $_key = $index[$v]['Key_name'];
                } elseif($index[$v]['Index_type'] == 'FULLTEXT') {
                    $_key = $index[$v]['Index_type'];    
                } elseif($index[$v]['Non_unique'] == 0) {
                    $_key = 'UNIQUE';
                } else {
                    $_key = 'INDEX';
                }
            
                $str = ', <span style="color:#dc143c;">%s</span>';
                $key = sprintf($str, $_key);
            }                
            
            $a['num'] = $i++;
            $a['field_value'] = $v;
            $a['field_title'] = $req . $v . ' - ' . $fields[$v]['Type'] . $default . $key;
            
            $tpl->tplParse($a, 'fields2');
        }
        

        $select = new FormSelect();
        $select->setSelectWidth(250);        
        
        // load command
        $select->select_tag = false;
        $select->setSelectName('load_command');
        $select->setRange(array(1 => 'LOAD DATA LOCAL INFILE',
                                2 => 'LOAD DATA INFILE'));
        $tpl->tplAssign('loaddatasql_select', $select->select(@$_POST['load_command']));        
        
        
        // fields
        $v = (isset($_POST['fields_terminated'])) ? $_POST['fields_terminated'] : ',';
        $tpl->tplAssign('fields_terminated', $v);
        
        $v = (isset($_POST['optionally_enclosed'])) ? $_POST['optionally_enclosed'] : '&quot;';
        $tpl->tplAssign('optionally_enclosed', $v);
         
        $v = (substr(PHP_OS, 0, 3) == 'WIN') ? '\r\n' : '\n';
        if(isset($_POST['lines_terminated'])) {
            $v = $_POST['lines_terminated'];
        }
        
        $tpl->tplAssign('lines_terminated', $v);
        
        $v = (isset($_POST['file_2'])) ? $_POST['file_2'] : '';
        $tpl->tplAssign('file_2', $v);

        $tpl->tplParse();
        return $tpl->tplPrint(1);        
    }

}
?>