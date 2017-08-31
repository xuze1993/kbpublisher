<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2005 Evgeny Leontev                                    |
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

require_once 'eleontev/URL/RequestDataUtil.php';


class RequestData
{
    
    var $int_keys = array();
    var $skip_keys = array();
    var $html_keys = array();
    var $html_values = array();
    var $curly_braces = array();
    
    
    // array: key is that we can access it, value is custom real param
    // for example: real $_GET['custom'] = 20; we can access it like $rq->action, 
    // so on page we always can write $rq->action
    // access any var we want just change value for $predefined array for that key
    //var $predefined = array('action'=>'_a');
    var $predefined = array();
    var $vars = array();
    
    var $action; // to have it on page
    
    
    function __construct(&$var, $int_keys = array()) {
        $this->setIntKeys($int_keys);
        $this->setVars($var);
    }
    
    function setIntKeys($key) {
        $key = (is_array($key)) ? $key : array($key);
        foreach($key as $k) {
            $this->int_keys[] = $k;
        }
    }    
    
    function setSkipKeys($key) {
        $key = (is_array($key)) ? $key : array($key);
        foreach($key as $k) {
            $this->skip_keys[] = $k;
        }
    }

    function setHtmlKeys($key) {
        $key = (is_array($key)) ? $key : array($key);
        foreach($key as $k) {        
            $this->html_keys[] = $k;
        }
    }
    
    // the same as setHtmlKeys 
    function setHtmlValues($key) {
        $this->setHtmlKeys($key);
    }
    
    function getHtmlValues() {
        $arr = array();
        foreach($this->html_keys as $v) {
            $arr[$v] = &$this->vars[$v];
        }

        return $arr;
    }
    
    function setCurlyBracesValues($key) {
        $this->curly_braces[$key] = $key;
    }
    
    function getCurlyBracesValues() {
        $arr = array();
        foreach($this->curly_braces as $v) {
            $arr[$v] = &$this->vars[$v];
        }

        return $arr;
    }
        
    
    function &setVars(&$var) {
        $this->setVarsPHP4($var);
        $this->vars =& $var;
        $this->toInt();
        
        return $this->vars;
    }
    
    
    // I do not like it at all
    function setVarsPHP4(&$arr) {
        foreach($arr as $k => $v){
            $this->$k =& $arr[$k];
        }
    }
        
     
    function toInt() {
        return RequestDataUtil::toInt($this->vars, $this->int_keys);
    }
    
    
    // $param true, false (for server check) or real values
    function stripVars($server_check = false) {
        
        $skip = array_merge($this->skip_keys, $this->html_keys);
        $html_values = $this->getHtmlValues();
        $cb_values = $this->getCurlyBracesValues();
        
        RequestDataUtil::stripVars($this->vars, $skip, $server_check);
        RequestDataUtil::stripVarsHtml($html_values, array(), $server_check);
        RequestDataUtil::stripVarsCurlyBraces($cb_values, $server_check);
    }
    
    
    // $param true, false (for server check) or real values
    // function &stripVarsValues(&$values, $server_check = 'display') {
    function stripVarsValues(&$values, $server_check = 'display') {
        $this->vars = &$values;
        $this->stripVars($server_check);
        
        return $this->vars;
    }
}
?>