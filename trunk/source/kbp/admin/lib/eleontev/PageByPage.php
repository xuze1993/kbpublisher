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

/**
 * PageByPage is a class used to create page by page navigation.
 * 
 * @since 20/05/2003
 * @author Evgeny Leontev <eleontev@gmail.com>
 * @access public
 */


class PageByPage {
    
    var $form_action; 
    var $query = array();    // values to be in URL
    
    var $get_name = 'bp';    // $_GET name to be in query    
    var $get_delim = '|';
    var $action_page = '';
    
    var $offset = 0;         // mysql offset
    var $limit = 10;         // mysql limit
    var $num_records;        // num records
    var $num_pages;          // num pages
    var $cur_record = 1;     // number of record
    var $cur_page = 1;       // number of page
    var $nav;
    
    var $page_msg = 'Page';
    var $record_msg = 'Records'; 
    var $record_from_msg = 'from';
    
    var $prev_msg = 'Prev';
    var $next_msg = 'Next';
    
    var $per_page_msg = 'Records per page';
    var $per_page_name = 'bpr';
    var $per_page_range = array(10, 20, 40);
    
    var $multiple;
    var $offsets = array();
    var $offsets_sname = 'bp_offsets_';
    
    
    
    function __construct($limit = false, $hidden = false, $action_page = false) {
        
        if($limit instanceof PageByPage) {
            foreach (get_object_vars($limit) as $k => $v) {
                $this->$k = $v;
            }
            
            return;
        }
        
        if($limit) { 
            $this->limit = $limit; 
        }
        
        if($hidden) { 
            unset($hidden[$this->get_name], $hidden['submit']);
            $this->query = $hidden;
        }
        
        if($action_page) {
            $this->action_page = $action_page;
        } else {
            $this->action_page = $_SERVER['PHP_SELF'];
        }        
        
        $this->setGetVars();
    }
    
    
    function setGetVars() {
        
        // overwrite limit 
        if(isset($_GET[$this->per_page_name])) {
            $this->limit = (int) $_GET[$this->per_page_name];
        }
        
        if(isset($_GET[$this->get_name])) {
           $this->cur_page = (int) $_GET[$this->get_name];
           $this->cur_record = (int) ceil(($this->cur_page-1) * $this->limit+1);
           $this->offset     = (int) ceil(($this->cur_page-1) * $this->limit);
        }        
    }
    
    
    // function setOffset() {
        // $this->offset = (int) ceil(($this->cur_page-1) * $this->limit);
    // }    
    
    
    static function factory($class, $limit = false, $hidden = false, $action_page = false) {
        
        $class = 'PageByPage_' . $class;
        $m = new $class($limit, $hidden, $action_page);
        
        return $m;
    }
          

    // using COUNT() for count, use it in $sql
    function countAll($sql, $db_obj = false){
        
        if(is_numeric($sql)) {
            $this->num_records = $sql; // num records
                
        } else {
                
            $sql = preg_replace("#SELECT\s{1,}(.+?)\s{1,}FROM# is", 'SELECT COUNT(*) FROM', $sql);
            //echo "<pre>"; print_r($sql); echo "</pre>";
            
            if(!$db_obj) {
                $reg = &Registry::instance();
                $db_obj = &$reg->getEntry('db');
            }
            
            $val = $db_obj->GetCol($sql) or die (db_error($sql));
            $this->num_records = $val[0];
        }
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        // return $this->_prepare();
        
        $this->num_records = (int) $this->num_records;
        $this->num_pages = (int) ceil($this->num_records/$this->limit);
        
        $sql_limit['offset'] = $this->offset;
        $sql_limit['limit'] = $this->limit;
        
        return $sql_limit;
    }
    
    
/*
    // initialize, read session
    function setMultiple($num) {
        
        $this->multiple = $num;
        
        for ($i=1; $i <= $num; $i++) {
            $this->offsets[$i] = 0;
        }
      
        // reset offsets session
        if($this->cur_page <= 1) {
            $_SESSION[$this->offsets_sname] = array();
        }
        
        // set offsets
        if(isset($_SESSION[$this->offsets_sname][$this->cur_page])) {
            foreach($_SESSION[$this->offsets_sname][$this->cur_page] as $k => $v) {
                $this->offsets[$k] = (int) $v;
            }
        }
    }
    
    
    // set session
    function setMultipleResult($offsets) {
        if(!$this->multiple) {
            return;
        }
        
        foreach($offsets as $k => $v) {
            $this->offsets[$k] += $v;
        }
        
        foreach($this->offsets as $k => $v) {
            $_SESSION[$this->offsets_sname][$this->cur_page+1][$k] = $v;
        }
    }*/

    
        
    // set msg, we should create 5 msg
    // for example: setMsg(Pages, Records, from)
    function setMsg($msg) {
        
        if(!is_array($msg)) { 
            $msg = func_get_args(); 
        }
        
        $this->page_msg = $msg[0];
        $this->record_msg = $msg[1]; 
        $this->record_from_msg = $msg[2];
        
        if(!empty($msg[3])) {
            $this->prev_msg = $msg[3];
            $this->next_msg = $msg[4];
        }
    }
    
    
    function setPerPageMsg($msg) {
        $this->per_page_msg = $msg;
    }
    
    
    function setPerPageRange($arr) {
        $this->per_page_range = array();
        foreach($arr as $v) {
            $this->per_page_range[$v] = $v;
        }
    }
    
        
    // Private // -- 
        
    function _prepare() {
        
        // moved to  constructor 2016-08-24       
        // if(isset($_GET[$this->get_name])) {
           // $this->cur_record = (int) ceil(($this->cur_page-1)*$this->limit+1);
           // $this->offset     = (int) ceil(($this->cur_page-1)*$this->limit);
        // }


        // moved to countAll() 
        // $this->num_records = (int) $this->num_records;
        // $this->num_pages = (int) ceil($this->num_records/$this->limit);
        //
        // $sql_limit['offset'] = $this->offset;
        // $sql_limit['limit'] = $this->limit;
        //
        // return $sql_limit;
    }    
    
    
    // get title and links for pages navigation
    function _getPages($show_pages = 10){
        
        $half = $show_pages/2;
        
        $start = ($this->cur_page > $half+1) ? $this->cur_page - $half : 1;
        $end = ($this->cur_page > $half) ?  $this->cur_page + $half-1 : $show_pages;
        
        if ($end > $this->num_pages) {
            $diff = $end - $this->num_pages;
            if ($start > 1) {
                $start -= $diff;
            }
        }
        
        $pages = array();
        
        // add to start
        if($start > 1) {
            $pages[] = array('...', $this->_getLink($start-1));
        }
        
        for ($i = $start; $i <= $end; $i++) {
            if($i > $this->num_pages) {
                continue;
            }
            $pages[] = array($i, $this->_getLink($i));
        }

        // add to end 
        if($end > $show_pages && $end < $this->num_pages) {
            $pages[] = array('...', $this->_getLink($end+1));
        }        

        // echo '<pre>Start: ', print_r($start, 1), '</pre>';
        // echo '<pre>End: ', print_r($end, 1), '</pre>';
        // echo '<pre>Cur Page: ', print_r($this->cur_page, 1), '</pre>';
        // echo '<pre>', print_r($pages, 1), '</pre>';
        
        return $pages;
    }    
                
    
    // get link for previous or next page
    function _getLink($num_page, $back = false) {
        
        $sign = (strpos($this->action_page, '?') !== false) ? '&' : '?';
        
        // back link to the first page
        if($back && $num_page == 1) {
            $query_arr = $this->query;
            $sign = ($query_arr) ? $sign : '';
            
        } else {
            $query_arr = array_merge($this->query, array($this->get_name => $num_page));
        }
        
        return $this->action_page . $sign . http_build_query($query_arr);
    }
    
    
    function _getNextPrevLinks($type = 'standart') {
           
        $ret = array();
        $ret['prev'] = false;
        $ret['next'] = false;
        
        // print previos arows (<<)     
        if($this->num_pages > 1 && $this->cur_page != 1) {
            $num_page = $this->_getBpValuesPrev();
            $ret['prev'] = $this->_getLink($num_page, true);
        }

        // print next arrows (>>) 
        if($this->num_pages > $this->cur_page){
            $num_page = $this->_getBpValuesNext();
            $ret['next'] = $this->_getLink($num_page);
        }            
        
        return $ret;
    }
    
    
    function _getBpValuesPrev() {
        return $this->cur_page - 1;
    }    
    
    
    function _getBpValuesNext() {
        return $this->cur_page + 1;
    }   
}


class PageByPage_page extends PageByPage
{

    function navigate() {
        $html = array();
        $html[] = '<div>';
        $html[] = '<div>%s</div>';
        $html[] = '<div style="margin-top: 17px;">%s</div>';
        $html[] = '</div>';
        
        $html = implode('', $html);
        return sprintf($html, $this->_records(), $this->_pages());
    }


    function _records() {
        
        $html = array();
        $prevnext = $this->_getNextPrevLinks();

        $str_link_p = '<span class="bpPrevNext">&#x2190; <a href="%s">%s</a></span>';
        $str_link_n = '<span class="bpPrevNext"><a href="%s">%s</a> &#x2192;</span>';
        $str_current_p = '<span class="bpPrevNextCurrent">&#x2190; %s</span>';
        $str_current_n = '<span class="bpPrevNextCurrent">%s  &#x2192;</span>';
        $str_delim = '<span class="bpPrevNextDelim">&nbsp;</span>';

        // print previos arows (<<)     
        if($prevnext['prev']){
            $html[] = sprintf($str_link_p, $prevnext['prev'], $this->prev_msg);
        } else {
            $html[] = sprintf($str_current_p, $this->prev_msg);                
        }

        // print next arrows (>>)
        if($prevnext['next']){
            $html[] = sprintf($str_link_n, $prevnext['next'], $this->next_msg);
        } else {
            $html[] = sprintf($str_current_n, $this->next_msg);                
        }
        
        // echo '<pre>', print_r(implode($str_delim, $html), 1), '</pre>';
        return implode($str_delim, $html);
    }


    function _pages() {
        
        $nav = '<span class="bpPageNav"><a href="%s">%s</a></span>';
        $nav_current = '<span class="bpPageNavCurrent">%s</span>';
        $pages = $this->_getPages();
    
        $html = array();
        foreach($pages as $v) {
            if($this->cur_page == $v[0]) {
                $html[] = sprintf($nav_current, $v[0]);
            } else {
                $html[] = sprintf($nav, $v[1], $v[0]);
            }
        }
        
        return implode("\n", $html);
    }


}



class PageByPage_form extends PageByPage
{
    
    function navigate($more_fields = false){
        
        if($more_fields !== false) {
            
            require_once ('HTML/FormSelect.php');
            
            $this->setPerPageRange($this->per_page_range);
            
            $select = new FormSelect();
            $select->setFormMethod($_GET);
            $select->setFormName('per_page_form');
            $select->setSelectWidth(50);
            $select->setOnChangeSubmit();
            
            $select->setSelectName($this->per_page_name);
            $select->setRange($this->per_page_range);
            
            $sel_html = '<form action="" style="margin: 0px;">';

            $vars = $_GET;
            
            // reset bp by some reasons it will rewrite $_GET variable if not use unset
            unset($vars[$this->get_name]);
            $vars[$this->get_name] = 1; 
            
            unset($vars[$this->per_page_name]);
            $sel_html .= http_build_hidden($vars, true);

            $limit = (isset($_GET[$this->per_page_name])) ? $_GET[$this->per_page_name] : $this->limit;
            $sel_html .= $select->select($limit);
            
            $sel_html .= '</form>';
            
            $sel_html = '<td style="white-space: nowrap;">&nbsp;' . $this->per_page_msg . ':</td>
                         <td>'.$sel_html.'</td>';
            
        } else {
            $sel_html = '';
        }        

        $html = '<table cellpadding="4" cellspacing="0" border="0" width="100%">
        <tr style="background-color: #e7e7e7;">
            
            <td width="100%" style="padding: 4px;">'.$this->_records().'</td>
            <td nowrap style="padding-right: 15px; text-align: right;">'.$this->_pages().'</td>
            <td style="background-color: #ffffff;padding: 1px;"></td>
            
            ' . $sel_html . '
            
        </tr>
        </table>';
        
        return $html;
    }
    
    
    function _pages(){
        
        $str = '<form action="%s" name="by_page_form" id="by_page_form" style="margin : 0px 0px;">';
        $a = sprintf($str, $this->action_page);
        $a .= http_build_hidden($this->query, true);
        $a .= $this->page_msg . ": ";
        
        
        if($this->num_pages > 1){
            
            $str = '<select name="%s" style="padding: 0px; font: 10px;" onchange="this.form.submit();">';
            $a .=  sprintf($str, $this->get_name);
            
            $display_num = 5;
            
            $start = $this->cur_page - $display_num;
            $end = $this->cur_page + $display_num;
            
            if ($start < 0) {
                $end += abs($start);
            }
            
            if ($end > $this->num_pages) {
                $start -= ($end - $this->num_pages);
            }
            
            $interval = range($start, $end);
            if (!in_array(1, $interval)) {

                if (in_array(2, $interval)) {
                    array_unshift($interval, 1);
                } else {
                    $interval['first'] = 1;
                }
            }
     
            if (!in_array($this->num_pages, $interval)) {
                
                if (in_array($this->num_pages - 1, $interval)) {
                    $interval[] = $this->num_pages;
                } else {
                    $interval['last'] = $this->num_pages;
                }
            }                

            for($i=1;$i<=$this->num_pages;$i++) {
                
                if($this->cur_page == $i) {
                    $a .= '<option value="" selected="selected">'.$i.'</option>';
                } elseif (in_array($i, $interval)) {
                    
                    $index = array_search($i, $interval);
                    
                    // last page
                    if ($index === 'last') {
                        $a .= '<option value="" disabled>...</option>';  
                    }
                    
                    $a .= '<option value="'.$i.'">'.$i.'</option>';
          
                    // first page
                    if ($index === 'first') {
                        $a .= '<option value="" disabled>...</option>';
                    }
                }
            }
            $a .= '</select> ';
            $a .=  $this->record_from_msg . ' ' . $this->num_pages;
        
        } else {
            $a .= 1;
        }
        
        
        $prevnext = $this->_getNextPrevLinks();
        
        // print previos arows (<<)     
        if($prevnext['prev']){
            $str = '&nbsp;&nbsp;<a href="%s" title="%s"><b>&laquo;&laquo;</b></a>';
            $a .= sprintf($str, $prevnext['prev'], $this->prev_msg);
        }
            
        // print next arrows (>>)
        if($prevnext['next']){
            $str = '&nbsp;&nbsp;<a href="%s" title="%s"><b>&raquo;&raquo;</b></a>';
            $a .= sprintf($str, $prevnext['next'], $this->next_msg);
        }
        
        $a .= '</form>';    
        return $a;
    }
    
    
    function _records(){
        
        $n = $this->limit*$this->cur_page;
        if($n > $this->num_records){
            $b =  ($n - $this->num_records);
            $n = ($n - $b);
        }

        $str = '%s: %s - %s %s %s';
        return sprintf($str, $this->record_msg, $this->offset+1, $n, 
                               $this->record_from_msg, $this->num_records);
    }
    
}


class PageByPage_short extends PageByPage
{

    function &_pages() {    
    
        $a = '';
        $prevnext = $this->_getNextPrevLinks();
        
        // print previos arows (<<)     
        if($prevnext['prev']){
            $str = '&nbsp;&nbsp;<a href="%s" title="%s"><b>&laquo;&laquo;</b></a>';
            $a .= sprintf($str, $prevnext['prev'], $this->prev_msg);
        }
            
        // print next arrows (>>)
        if($prevnext['next']){
            $str = '&nbsp;&nbsp;<a href="%s" title="%s"><b>&raquo;&raquo;</b></a>';
            $a .= sprintf($str, $prevnext['next'], $this->next_msg);
        }
    
        return $a;    
    }
     
     
    function _records(){
        
        $n = $this->limit*$this->cur_page;
        if($n > $this->num_records){
            $b =  ($n - $this->num_records);
            $n = ($n - $b);
        }
        
        $str = '%s: %s - %s %s %s';
        return sprintf($str, $this->record_msg, $this->offset+1, $n, 
                               $this->record_from_msg, $this->num_records);
    }
           
    
    function navigate() {
        return sprintf('%s&nbsp;&nbsp;%s', $this->_records(), $this->_pages());
    }

    
    function info() {
        
        $n = $this->limit*$this->cur_page;
        if($n > $this->num_records){
            $b =  ($n - $this->num_records);
            $n = ($n - $b);
        }
        
        if($this->num_records >= 1) {
            $showing = sprintf('%s: %d - %d', $this->record_msg, $this->offset+1, $n); 
            return sprintf('%s: %d | %s', $this->found_msg, $this->num_records, $showing);   
        }
    }
    
}


class PageByPage_mobile extends PageByPage
{

    function navigate() {
        $html = array();
        $html[] = '<div>';
        $html[] = '<nav><ul class="pagination">%s</ul></nav>';
        $html[] = '</div>';
        
        $html = implode('', $html);
        return sprintf($html, $this->_pages());
    }
    
    
    function _pages() {
        
        $html = array();
        $prevnext = $this->_getNextPrevLinks();

        $str_link_p = '<li class="page-item"><a class="page-link" href="%s" aria-label="%s"><span aria-hidden="true">&laquo;</span></a></li>';
        $str_link_n = '<li class="page-item"><a class="page-link" href="%s" aria-label="%s"><span aria-hidden="true">&raquo;</span></a></li>';
        $str_current_p = '<li class="page-item disabled"><a class="page-link" href="#" aria-label="%s"><span aria-hidden="true">&laquo;</span></a></li>';
        $str_current_n = '<li class="page-item disabled"><a class="page-link" href="#" aria-label="%s"><span aria-hidden="true">&raquo;</span></a></li>';
        $str_delim = '<span class="bpPrevNextDelim">&nbsp;</span>';

        // print previos arows (<<)     
        if($prevnext['prev']){
            $html[] = sprintf($str_link_p, $prevnext['prev'], $this->prev_msg);
        } else {
            $html[] = sprintf($str_current_p, $this->prev_msg);                
        }
        
        $nav = '<li class="page-item"><a class="page-link" href="%s">%s</a></li>';
        $nav_current = '<li class="page-item active"><span class="page-link">%s</span></li>';
        $pages = $this->_getPages();
    
        foreach($pages as $v) {
            if($this->cur_page == $v[0]) {
                $html[] = sprintf($nav_current, $v[0]);
            } else {
                $html[] = sprintf($nav, $v[1], $v[0]);
            }
        }
        
        // print next arrows (>>)
        if($prevnext['next']){
            $html[] = sprintf($str_link_n, $prevnext['next'], $this->next_msg);
        } else {
            $html[] = sprintf($str_current_n, $this->next_msg);                
        }
        
        return implode("\n", $html);
    }
}


class PageByPage_forum extends PageByPage
{

    function navigate() {
        $html = array();
        
        $prevnext = $this->_getNextPrevLinks();

        $str_link_p = '<span class="bpForumPageNav"><a href="%s">&laquo;</a></span><span class="bpForumPageNav"><a href="%s">&#x2190; %s</a></span>';
        $str_link_n = '<span class="bpForumPageNav"><a href="%s">%s &#x2192;</a></span><span class="bpForumPageNav"><a href="%s">&raquo;</a></span>';

        // print previos arows (<<)     
        if($prevnext['prev']){
            $html[] = sprintf($str_link_p, $this->_getLink(1), $prevnext['prev'], $this->prev_msg);
        }
        
        $nav = '<span class="bpForumPageNav"><a href="%s">%s</a></span>';
        $nav_current = '<span class="bpForumPageNav bpForumPageNavCurrent">%s</span>';
        $pages = $this->_getPages();
    
        foreach($pages as $v) {
            if($this->cur_page == $v[0]) {
                $html[] = sprintf($nav_current, $v[0]);
            } else {
                $html[] = sprintf($nav, $v[1], $v[0]);
            }
        }
        
        if($prevnext['next']){
            $html[] = sprintf($str_link_n, $prevnext['next'], $this->next_msg, $this->_getLink($this->num_pages));
        }
        
        $html[] = sprintf('<span class="bpForumPageCount">%s %d %s %d</span>', $this->page_msg, $this->cur_page, $this->record_from_msg, $this->num_pages);
        
        $html = implode('', $html);
        return sprintf($html, $this->_records(), $this->_pages());
    }


    function _records() {
        
    }


    function _pages() {
    }

}



// Showing 1 to 10 of 177         < Previous              Next >      


// $bp = new PageByPage(10, $_GET);
// $bp->countAll(150);
// 
// echo $bp->shortNavigate();
// echo '<br/>';
// echo $bp->fixedNavigate();
// echo '<br/>';
// echo $bp->navigate();
// 
// echo '<br/>';
// echo $bp->_pages();


// echo "<pre>"; print_r($bp); echo "</pre>";

?>