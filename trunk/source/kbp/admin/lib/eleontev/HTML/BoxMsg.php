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
//
// $Id: BoxMsg.php,v 1.0 2004/10/15 16:57:44 root Exp $

/**
 * BoxMsg is a set of classes to display messages.
 *
 * @since 15/10/2004
 * @author Evgeny Leontiev <eleontev@gmail.com>
 * @access public

 * EXAMPLE:
 * echo HintMsg::quickGet('Test');
 * 
 * $hint = new SuccessMsg();
 * $hint->setMsg('title','SUCCESS {url}');
 * $hint->setMsg('body','If your browser does not support automatic redirection 
 *                       click this link <a h ref="{url}">{url}</a>');
 * $hint->assignVars('url', '555555');
 * echo $hint->get();
 */

require_once 'eleontev/Util/Replacer.php';
require_once 'eleontev/Util/GetMsg.php';


class BoxMsg
{     

    var $error_pref = 'BoxMsg';
    var $vars = array();
    var $img_dir = '';
    var $img;    
    var $div_id;
    var $div_class = 'boxMsgDiv';
    
    var $msg   = array('title'    =>'',
                       'body'     =>'',
                       'header'   =>'');     
    
    var $color = array('title'    =>'#ffffff',
                       'body'     =>'#000000'); 
    
    var $bgcolor = array('border' =>'#8592A2', 
                         'body'   =>'#FFFFE1', 
                         'title'  =>'#ffffff');
                         
    var $options = array();
    

    function __construct () {
        $this->replacer = new Replacer();
    }    
    
    
    static function factory($class, $msgs = array(), $vars = array(), $options = array()) {
        
        $class = ucfirst($class) . 'Msg';
        $m = new $class;
        $m->setOptions($options);
        $m->div_id = $m->getOption('div_id');
        
        if($msgs) {
            if(!is_array($msgs)) {
                $args = func_get_args();
                unset($args[0]);
                $msgs = $m->getMsgsArrayFromArgs($args);
            } 
            
            $m->assignVars($vars);
            $m->setMsgs($msgs);
            $m = $m->get();
        }
        
        return $m;
    }
    
    
    function setMsg($msg_key, $msg) {
        $this->msg[$msg_key] = $msg;
    }    
        
    
    function setMsgs($msgs) {
        
        if(!is_array($msgs)) {     
            $msgs = $this->getMsgsArrayFromArgs(func_get_args());
        } 
        
        foreach($msgs as $k => $v) {
            $this->setMsg($k, $v);
        }
    }
    
    
    function getMsgsArrayFromArgs($arr) {
        $msg_keys = array_keys($this->msg);
        foreach($arr as $k => $v) {
            $ar[current($msg_keys)] = $v;
            next($msg_keys);
        }

        return $ar;
    }
    
    
    
    function setMsgsIni($file, $msg_file_key) {
        return $this->setMsgs(GetMsg::parseIni($file, $msg_file_key));
    }
    
    
    function get() {
        foreach($this->msg as $k => $v) {
            if(!$v) { continue; }
            $this->msg[$k] = $this->replacer->parse($v);
        }
        
        return $this->getHtml();
    }
    
    
    function assignVars($var, $value = false) {
        $this->replacer->assignVars($var, $value);
    }
    
    
    function setOptions($val) {
        $this->options = $val;
    }
    
    
    function getOption($key) {
        return @$this->options[$key];
    }
    
    
    function & getHtml() {
        
        if (!$this->div_id) { // generating an id
            $this->div_id = md5(time() . $this->msg['body']);
        }
        
        $div_id = ($this->div_id) ? 'id="'.$this->div_id.'"' : '';
        $div_container = '<div %s class="%s" style="border: 1px %s solid;background-color: %s;color: %s;word-wrap: break-word;text-align: left;">';
        
        $html = sprintf($div_container, $div_id, $this->div_class, $this->bgcolor['border'], $this->bgcolor['body'], $this->color['body']) . "\n";
        
        $title_block = '<div class="%sTitle" style="padding: 4px 8px; font-weight: bold; color: %s;background-color: %s;"><div style="float: left;">%s</div>%s<div style="clear: both;"></div></div>';
        if ($this->getOption('close_btn')) {
            $icon = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAACXBIWXMAAAsTAAALEwEAmpwYAAAAIGNIUk0AAHolAACAgwAA+f8AAIDpAAB1MAAA6mAAADqYAAAXb5JfxUYAAACESURBVHjabNBLDoJQDEbh77oLYSBgxBVIXLePiWETvnA5OAFTr3bUtKfJ+SultMMRjd+qcUgpdXDBiAFVgFZ4Trse2gma4QLLAL2wna+rAD9wD1Cd+xS4TsCIG8p5uQjgDMhmXxXFh6z/BNxk4uWkEQO28T25eAzYQ4cz1n8e3uCE/XsAjBEsnVT7rVIAAAAASUVORK5CYII=';
            $str = '<div style="float: right;"><a href="#" onclick="$(\'#%s\').fadeOut(1500); return false;" style="color: white;text-decoration: none;"><img src="%s" /></a></div>';
            $close_block = sprintf($str, $this->div_id, $icon);
            
        } else {
            $close_block = ''; 
        }
        
        $html .= ($this->msg['title']) ? sprintf($title_block, $this->div_class, $this->color['title'], $this->bgcolor['border'], $this->msg['title'], $close_block) : '';
        $html .= '<div style="padding: 8px;">';
        $html .= ($this->img) ? '<img src="'.$this->img_dir . $this->img.'" alt="" style="margin: 0px 20px 10px 0px; float: left;" />' : '';
        $html .= ($this->msg['header']) ?  '<b>'.$this->msg['header'].'</b><br />' : '';
        $html .= $this->msg['body'];
        $html .= ($this->msg['title']) ? '' : $close_block;
        $html .= '</div></div>' . "\n";
        
        if ($this->getOption('effect')) {
            $html .= sprintf('<script type="text/javascript">$(document).ready(function() {$("#%s").%s;});</script>', $this->div_id, $this->getOption('effect'));
        }
        
        return $html;
    }
    
    
    function & getTableHtml() {
        $div_id = ($this->div_id) ? 'id="'.$this->div_id.'"' : '';
        $html = sprintf('<div %s class="%s">', $div_id, $this->div_class) . "\n";
        $html .= '<table width="100%" cellspacing="0" cellpadding="1" style="background-color: '.$this->bgcolor['border'].';">' . "\n";
        $html .= ($this->msg['title']) ?  '<tr><td style="padding: 4px 8px; font-weight: bold; color: '.$this->color['title'].';">'.$this->msg['title'].'</td></tr>' : '';        
        $html .= '<tr><td style="padding: 1px;"><table width="100%" cellspacing="0" cellpadding="8" style="background-color: '.$this->bgcolor['body'].';"><tr><td style="color:'.$this->color['body'].';padding: 8px;">';
        $html .= ($this->img) ? '<img src="'.$this->img_dir . $this->img.'" alt="" style="margin: 0px 20px 10px 0px; float: left;" />' : '';
        $html .= ($this->msg['header']) ?  '<b>'.$this->msg['header'].'</b><br />' : '';
        $html .= $this->msg['body'];
        $html .= '</td></tr></table>' . "\n";
        $html .= '</td></tr></table><br />' . "\n";
        $html .= '</div>' . "\n";
        
        return $html;
    }
}


class HintMsg extends BoxMsg
{

    //var $img = 'images/icons/hint.gif';
    
    var $bgcolor = array('border' =>'#8592A2', 
                         'body'   =>'#FFFFE1', 
                         'title'  =>'#ffffff');
                         
}



class ErrorMsg extends BoxMsg
{
    
    var $bgcolor = array('border' =>'#cc0033', 
                         'body'   =>'#FFFFE1', 
                         'title'  =>'#ffffff');
                         
}



class SuccessMsg extends BoxMsg
{

    var $bgcolor = array('border' =>'#339900', 
                         'body'   =>'#FFFFE1', 
                         'title'  =>'#ffffff');
                         
}



/*
$msg = BoxMsg::factory('hint');
$msg->setMsgs('title', 21321);
echo $msg->get();

$msg = BoxMsg::factory('error');
$msg->setMsgs('title', 21321);
echo $msg->get();

$msg = BoxMsg::factory('success');
$msg->setMsgs('title', 21321);
echo $msg->get();
// or

$msg = new BoxMsg();
$msg->setMsgs('title', 21321);
echo $msg->get();
*/


/*
$hint = new SuccessMsg();
$hint->setMsg('title','SUCCESS {url}');
$hint->setMsg('body','If your browser does not support automatic redirection 
                      click this link <a href="{url}">{url}</a>');
$hint->assignVars('url', '555555');
echo $hint->get();
*/
?>