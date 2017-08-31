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

class Setting extends AppObj
{
     
    var $properties = array();
    var $hidden = array();
    
    
    function set($data, $value = false, $strip_vars = 'none') {
        foreach($data as $k => $v) {
            $this->properties[$k] = $v;
        }
    }
    

    function validate($values, $manager) {
        
        require_once 'eleontev/Validator.php';
        
        $is_file = false;
        $file[] = APP_MODULE_DIR . $manager->module_name . '/SettingValidator.php';
        $file[] = APP_EXTRA_MODULE_DIR . $manager->module_name . '/SettingValidator.php';
        $file[] = APP_MODULE_DIR . 'setting/' . $manager->module_name . '/SettingValidator.php';
        foreach($file as $v) {
            if(file_exists($v)) {
                require_once $v;
                $is_file = true;
                break;
            }
        }
        
        if(!$is_file) {
            return false;
        }
        
        
        $v = new SettingValidator();
        $this->errors = $v->validate($values);
                
        if($this->errors) {
            return true;
        }
    }
	
	
	function prepareValues($values, $manager) {
        $parser = &$manager->getParser();
        $values_obj = $manager->formDataToObj($values);
        
        $values = $parser->parseReplacementsArray($values_obj);
        $values = $parser->parseInArray($values);
        $values = $parser->parseInArrayCloud($values);	
	
		return $values;
	}
}
?>