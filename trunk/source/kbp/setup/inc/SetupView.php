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

class SetupView extends BaseView
{    

    var $form_data = array();
    var $msg = array();
    var $passed = true;
    
    var $back_button = true;
    var $refresh_button = false;
    var $cancel_button = true;
    
    var $msg_vars = array('product_name' => 'KBPublisher');
    
    
    function __construct() {

        $reg = &Registry::instance();
        $this->conf = &$reg->getEntry('conf');
        $this->controller = &$reg->getEntry('controller');
        $this->encoding = $this->conf['lang']['meta_charset'];
        $this->lang = $this->conf['lang']['meta_content'];
        $this->db_encoding = 'UTF-8'; 
        if(!empty($this->conf['lang']['db_charset'])) {
            $this->db_encoding = $this->conf['lang']['db_charset'];
        }
                           
        $this->setMsg();
        $this->setUrlVars();
        $this->setStepMsg();
        $this->setTemplateDir();
    }
    
    
    function setTemplateDir() {
        $this->template_dir = $this->controller->working_dir . 'template/';
    }    
    
    
    function setUrlVars() {
        $this->view_id = $this->controller->view_id;
        $this->msg_id = $this->controller->msg_id;
    }    
    
    
    function getMsgFile($file, $module = true) {
        require_once 'core/app/AppMsg.php';
        return ($module) ? AppMsg::getModuleMsgFile($module, $file) 
                         : AppMsg::getCommonMsgFile($file);
    }
    
    
    function setMsg() {
        $file = $this->getMsgFile('common_msg.ini', 'setup');
        $this->msg = AppMsg::parseMsgs($file, false, false);
    }
    
    
    function addMsg($file, $module, $key = false, $sections = false) {
        $file = $this->getMsgFile($file, $module);
        $this->msg = array_merge($this->msg, AppMsg::parseMsgs($file, $key, $sections));
    }
    
    
    function setStepMsg() {
        $step = $this->controller->getStepKey($this->view_id, 'index');
        $file = $this->getMsgFile('step_msg.ini', 'setup');
        $msg = AppMsg::parseMsgs($file, $step);
        $msg['title_msg'] = Replacer::doParse($msg['title_msg'], $this->msg_vars);
        $msg['desc_msg'] = Replacer::doParse($msg['desc_msg'], $this->msg_vars);
        
        $this->msg = array_merge($this->msg, $msg);
    }
    
    
    function getPhraseMsg($key, $vars = array()) {
        $file = AppMsg::getModuleMsgFile('setup', 'phrase_msg.ini');
        $msg = AppMsg::parseMsgsMultiIni($file, $key);
        $msg = Replacer::doParse($msg, array_merge($this->msg_vars, $vars));
        return $msg;
    }
    
    
    function getFormatedDate($timestamp, $format = null) {
        $format = ($format) ? $format : $this->conf['lang']['date_format'];
        return $this->_getFormatedDate($timestamp, $format);
    }
        
    
    function getLink($view = false, $msg_key = false) {
        return $this->controller->getLink($view, $msg_key);
    }
    
    
    function getErrors($module = false) {
        require_once 'core/app/AppMsg.php';
        return AppMsg::errorBox($this->errors, 'setup');
    }
}
?>