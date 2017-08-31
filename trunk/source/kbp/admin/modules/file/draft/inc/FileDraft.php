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

require_once APP_MODULE_DIR . 'knowledgebase/draft/inc/KBDraft.php';


class FileDraft extends KBDraft
{
    
    var $title_name = 'filename';
 
 
    function validate($values) {
        
        require_once 'eleontev/Validator.php';
        
        // php 5.4 fix, Strict Standards
        $args = func_get_args();
        $action = (isset($args[1])) ? $args[1] : 'insert';
        $entry_id = (isset($args[2])) ? $args[2] : 0;
        
        
        $required = array();
        
        if($action == 'insert' && !$entry_id) {
            $required[] = 'file';
            
            foreach ($_FILES as $file) {
                if ($file['name']) {
                    $values['file'] = $file['name']; 
                }
            }
        }
        
        $v = new Validator($values, false);

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }   
    }
    
    
    function getValidate($values) {
        $ret = array();
        $ret['func'] = array($this, 'validate');
        
        $entry_id = (!empty($_GET['entry_id'])) ? $_GET['entry_id'] : false;
        $ret['options'] = array($values, 'action', $entry_id);
        return $ret;
    }
    
    
    function validateFile($values, $manager) {
				
        $upload = new Uploader;
        $upload->setAllowedExtension($manager->setting['file_allowed_extensions']);
        $upload->setDeniedExtension($manager->setting['file_denied_extensions']);
        $upload->setMaxSize($manager->setting['file_max_filesize']);
        
        $errors = $upload->validate($values);
        if(!empty($errors)) {
			$v = new Validator($values, true);
			$error_msg = Uploader::getErrorText($errors);
            foreach($error_msg as $msg) {
                $v->setError($msg, 'file', 'file', 'custom');
            }
            
			$this->errors = $v->getErrors();
            return true;
        }
    }
    
    
    function getValidateFile($values) {
        $ret = array();
        $ret['func'] = array($this, 'validateFile');
        $ret['options'] = array($values, 'manager');
        return $ret;
    }

}
?>