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

class CustomField extends AppObj
{
    
    var $properties = array('id'              => NULL,
                            'input_id'        => 1,
                            'type_id'         => 0,
                            'range_id'        => 0,
                            'title'           => '',                                                        
                            'tooltip'         => '',
                            'caption'         => '',
                            'default_value'   => '',
                            'is_required'     => 0,
                            'error_message'   => '',
                            'valid_regexp'    => '',
                            'position'        => 0,
                            'display'         => 0,
                            'html_template'   => '&#123;title&#125;: &#123;value&#125;',
                            'is_search'       => 0,                                                    
                            'active'          => 1
                            );
    
    var $category = array();    
    var $hidden = array('id', 'input_id', 'type_id', 'active');
    
        
    function setCategory($val) {
        $val = ($val) ? $val : array();
        $this->category = &$val;
    }
    
    function &getCategory() {
        return $this->category;
    }    
    
    function validate($values, $manager) {
        
        require_once 'eleontev/Validator.php';
        
        $v = new Validator($values, false);
        
        $required = array('title');        

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
        
        if ($manager->isFieldTypeWithRange($values['input_id'])) {
            if ($values['range_id'] == 0) {
                $v->setError('required_msg', 'range_id', 'required');
                $this->errors =& $v->getErrors();
                return true;     
            }
        }
        
/*
        // radio
        if ($values['input_id'] == 7 && empty($values['dv'])) {
            $v->setError('required_msg', 'range_id', 'required');
            $this->errors =& $v->getErrors();
            return true;     
        }
*/

        if (!empty($values['valid_regexp'])) {
            
            // check if is regexp valid
            if (@preg_match($values['valid_regexp'], '') === false) {
                $v->setError('regexp_valid_msg', 'valid_regexp');
                $this->errors =& $v->getErrors();
                return true;
            }
            
            // check for error msg
            if (empty($values['error_message'])) {
                $v->setError('required_msg', 'error_message', 'required');
                $this->errors =& $v->getErrors();
                return true; 
            }
        }
    }
    
    
    function getValidate($values) {
        $ret = array();
        $ret['func'] = array($this, 'validate');
        $ret['options'] = array($values, new CommonCustomFieldModel);
        return $ret;
    }
    
    
    function validateApply($values, $manager) {
        require_once 'eleontev/Validator.php';
        
        $v = new Validator($values, true);
        
        $data = $manager->getById($_GET['id']);
        
        if ($data['valid_regexp']) {
            if (!preg_match($data['valid_regexp'], $values['value'])) {
                $v->setError($data['error_message'], 'value', 'custom_fields', 'custom');
                
                $this->errors =& $v->getErrors();
                return true;
            }
        }
    }
    
    
    function getValidateApply($values) {
        $ret = array();
        $ret['func'] = array($this, 'validateApply');
        $ret['options'] = array($values, 'manager');
        return $ret;
    }
    

    function getFormView($entry_type, $controller) {
        $class = 'CustomFieldView_form_' . $entry_type;
        
        if(!$controller->isClass($class)) {
            $class = 'CustomFieldView_form';
        }
            
        return $class;    
    }    
}
?>