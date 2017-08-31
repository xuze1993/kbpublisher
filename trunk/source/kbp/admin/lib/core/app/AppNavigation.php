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


class AppNavigation extends Navigation
{
    
    var $tbl_pref_custom;
    var $tbl_pref;
    var $tables = array('priv_module', 'priv_module_lang');
    var $page;
    var $msg = array();
    
    
    function __construct() {
        
        $reg =& Registry::instance();
        $this->db         =& $reg->getEntry('db');
        $this->tbl_pref =& $reg->getEntry('tbl_pref');
        
        $this->tbl = (object) AppModel::_setTableNames($this->tables, 
                                                       $this->tbl_pref, 
                                                       $this->tbl_pref_custom);
                                                       
        $this->setPage($_SERVER['PHP_SELF']);
    }
    
    
    function setMenuMsg($msgs, $append_msg_keys = array(), 
                            $append_string = " <span style='color: red;'>[new]</span>") {
        $this->msg = $msgs;
        foreach($append_msg_keys as $msg_key) {
            if(isset($this->msg[$msg_key])) {
                $this->msg[$msg_key] .= $append_string; 
            }
        }
    }
    
    
    function setPage($val) {
        $this->page = $val;
    }
    
    
    function getSql($module_name) {
    
        $parent_id = 'p2.parent_id';
        $use_in_sub_menu_sub = 'p2.use_in_sub_menu';

        $sql = "SELECT 
        p1.use_in_sub_menu AS use_in_sub_menu_top,
        p1.menu_name AS top_menu_name, 
        p1.module_name AS top_module_name,
        p2.by_default AS by_default,
        {$use_in_sub_menu_sub} AS use_in_sub_menu_sub, 
        p2.menu_name AS sub_menu_name,
        p2.module_name AS sub_module_name,
        p2.check_priv
        
        FROM {$this->tbl->priv_module} p1, {$this->tbl->priv_module} p2 
        
        WHERE p1.id = {$parent_id}
        AND p1.module_name = '$module_name'
        AND p2.active = 1
        ORDER BY p2.sort_order";
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        return $sql;
    }
    
    
    // select from table priv_module
    function setMenu($module_name, $sub_menu_attrib = false) {
    
        $menu = false;
        
        $sql = $this->getSql($module_name);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        while($row = $result->FetchRow()){
            $menu = true;
            
            if($row['sub_module_name'] == 'spacer') {
                $this->setMenuItem('spacer', $row['sub_menu_name']);
                continue;
            }            
            
            // hide not authorized module, page, ...
            if(!isset($_SESSION['priv_']['all']) && $row['check_priv']) {
                if(!isset($_SESSION['priv_'][$row['sub_module_name']])) { continue; }
            }
                        
            // miss top
            if($row['sub_module_name'] == 'all') { continue; }            
                        
            $params = array();
            
            // generate different link go to page by default
            if($row['by_default']) {
                
                //change by default menu link
                // if(isset($_SESSION['priv_'][$row['sub_module_name']]['by_default'])) {
                //     $row['by_default'] = $_SESSION['priv_'][$row['sub_module_name']]['by_default'];
                // }
                
                // 27 Feb, 2015, eleontev changes to be able not to change link
                // if access to normal by_default is allowed, example module=tool&page=tool
                if(!isset($_SESSION['priv_'][$row['by_default']])) {
                    if(isset($_SESSION['priv_'][$row['sub_module_name']]['by_default'])) {    
                        $row['by_default'] = $_SESSION['priv_'][$row['sub_module_name']]['by_default'];
                    }
                }
                
                if(strpos($row['by_default'], '/')) {
                    $i = 0;
                    $param = explode('/', $row['by_default']);
                    foreach($param as $k => $v) {
                        $params[] = $this->sub_equal[$i++]['value'] . '=' . $v;
                    }
                    
                } else {
                    $key_ = 0;
                    $params = array($this->sub_equal[$key_]['value'] .'='. $row['by_default']);
                }
            
            // generate different link to go to sub page
            } elseif($row['use_in_sub_menu_sub'] == 'YES_DEFAULT') {
                $params = array($this->sub_equal[0]['value'] .'='. $row['sub_module_name']);
            }


            $params = implode('&', array_merge($this->get_params, $params)) . '&';
            
            // add menu to sub from top
            if($row['use_in_sub_menu_top'] != 'NO' 
                && $row['use_in_sub_menu_top'] != '' 
                && !isset($use_in_sub_menu_top)) {
                
                $use_in_sub_menu_top = 1;
                $page = $this->page . '?' . $params . $this->equal_value . '=' . $row['top_module_name'];                
                
                if(isset($this->msg[$row['top_module_name']])) { 
                    $menu_name = $this->msg[$row['top_module_name']]; 
                } else { 
                    $menu_name = $row['top_menu_name']; 
                }
                
                $this->setMenuItem($menu_name, $page);
            }
            
            
            $page = $this->page . '?' . $params . $this->equal_value . '=' . $row['sub_module_name'];
            
            if(isset($this->msg[$row['sub_module_name']])) { 
                $menu_name = $this->msg[$row['sub_module_name']]; 
            } else { 
                $menu_name = $row['sub_menu_name']; 
            }
            
            $this->setMenuItem($menu_name, $page);
            $page = '';
        }
        
        
        return $menu;
    }


    function parseEPage($page) {

        $ret = false;
        @$content = file_get_contents($page);
        if(!$content) {
            $b = array('e', 'o');
            $s = $b[0] . 'r' . 'r' . $b[1] . 'r';
            $s .= ' o'.'p'.$b[0].'n: '. basename($page);
            echo $s;
            die();
        }

        preg_match('#\/\*(\w{48})\*\/#', $content, $match);
        $code = (!empty($match[1])) ? $match[1] : false;
        
        $f = 'b'.'a'.'s'.'e'.'6'.'4'.'_'.'d'.'ec'.'od'.'e';
        preg_match('#eval\('.$f.'\("\s(.*?)\s"\)\);#s', $content, $match);
        $data = (!empty($match[1])) ? $match[1] : false;

        if($code && $data) {

             // not mixed code, the same as encoded on site
            $dc = str_replace(array("\n", "\r"), '', trim($data));
            $code2 = str_repeat(md5($dc), 2);

            // not mixed code, reverted
            $code3 = str_split($code, 16);
            $code3 = $code3[2] . $code3[0] . $code3[1] . $code3[0];

            if($code2 === $code3) {
                $ret = true;
            }
        }

        return $ret;
    }


    function parseEMenu() {
        $p = array(
            APP_LIB_DIR . 'core/base/BaseApp.php',
            APP_MODULE_DIR . 'setting/license_setting/inc/LicenseSettingModel.php',
            APP_ADMIN_DIR . 'index.php',
            APP_MODULE_DIR . 'user/user/inc/UserModel.php',
            APP_ADMIN_DIR . 'extra/plugin/export/inc/KBExportHtmldoc_pdf.php'
        );
        
        $k = array_rand($p);
        // echo '<pre>', print_r($p[$k], 1), '</pre>';
        
        if(!AppNavigation::parseEPage($p[$k])) {
            $b = array('e', 'o');
            $s = $b[0] . 'r' . 'r' . $b[1] . 'r';
            $s .= ' d'.$b[0].'c'. $b[1] .'d'.'i'.'n'.'g';
            echo $s;
            die();
        }
    }


    function generate($menu_name = 'default') {
        
        if(!isset($this->menu_array[$menu_name])) { 
            return $this->_generateEmpty();
        } else {
            return $this->_generateMenu($menu_name);
        }
    }
    
    
    function _generateMenu($menu_name) {
        
        ksort($this->menu_array[$menu_name]);
        
        $tpl = new tplTemplatez($this->template);
        
        // only if template with row/active and row/nonactive
        if($this->template_type == 'NORMAL') {
            $page_val = $this->_getCheckValue($_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
            
            // to highlight properly
            if(isset($this->highlight_menu_item[$menu_name][$page_val])) {
                $page_val = $this->highlight_menu_item[$menu_name][$page_val];
            }            
            
            $menu_key = $this->_getCurrentMenuItem($page_val, $menu_name);
            
            foreach($this->menu_array[$menu_name] as $k => $v){
                $v['menu_key'] = $this->auxilary[$menu_name][$k];
                
                if($v['menu_item'] == 'spacer') {
                    $tpl->tplSetNeeded('row/spacer'); 
                
                } elseif($k == $menu_key){ 
                    $tpl->tplSetNeeded('row/active'); 
                
                } else { 
                    $tpl->tplSetNeeded('row/nonactive'); 
                }
                
                $tpl->tplParse(array_merge($this->options,$v),'row');
            }
            
        } else {
            foreach($this->menu_array[$menu_name] as $k => $v){
                $tpl->tplParse($v,'row');
            }
        }
        
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);        
    }
    
    
    // just top line
    function _generateEmpty() {
        $tpl = new tplTemplatez(APP_TMPL_DIR . 'sub_menu_empty.html');        
        $tpl->tplParse();
        return $tpl->tplPrint(1);        
    }
    
    
    function getMenu() {
        
    }
    
    
    function getTopMenu() {
        
    }
    
    
    function getShortcutMenu($controller, $priv, $msg) {

        $links = array();
        $links[] = array('knowledgebase', 'kb_entry', 'entry');
        $links[] = array('knowledgebase', 'kb_draft', 'entry_draft');
        $links[] = array('knowledgebase', 'kb_category', 'category');
        $links[] = array('knowledgebase', 'kb_glossary', 'glossary');
        $links[] = array('file', 'file_entry', 'file');
        $links[] = array('file', 'file_draft', 'file_draft');
        $links[] = array('file', 'file_category', 'file_category');
        $links[] = array('news', 'news_entry', 'news');
        $links[] = array('users', 'user', 'user');

        $drafts = array('kb_entry', 'file_entry');

        $range = array();
        $action_str = '<li><a href="%s">%s</a></li>';
        
        foreach($links as $v) {
            if($priv->isPriv('insert', $v[1])) {
                $link = $controller->getLink($v[0], $v[1], false, 'insert');
                $range[$v[1]] = sprintf($action_str, $link, $msg[$v[2]]);            
            
                // only drafts allowed
                if(in_array($v[1], $drafts)) {
                    if($priv->isPrivOptional('insert', 'draft', $v[1])) {
                        unset($range[$v[1]]);
                    }
                }
            }
        }
        
        return implode('<li class="dropdown-divider"></li>', $range);
    }    
}

AppNavigation::parseEMenu();
?>