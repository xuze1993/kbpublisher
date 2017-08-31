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


class Export extends AppObj
{
  
    var $properties = array('id'            => NULL,
                            'export_type'   => 3,
                            'user_id'       => 0,             
                            'title'         => '',
                            'description'   => '',
                            'export_option' => '',
                            'filetype'      => '',              
                            'active'        => 1
                            );
                            
    var $category = array();
    var $role = array();
    var $user_mode = 0;
    
    var $columns = array();
    
                            
    function setCategory($val) {
        $this->category = &$val;
    }
    
    function &getCategory() {
        return $this->category;
    }
    
    function setRole($values) {
        $this->role = $values;
    }
    
    function &getRole() {
        return $this->role;
    }  
      
    function setUserMode($val) {
        $this->user_mode = &$val;
    }
    
    function &getUserMode() {
        return $this->user_mode;
    }
    
    function setColumns($val) {
        $this->columns = &$val;
    }
    
    function &getColumns() {
        return $this->columns;
    }
    
    
    function validate($values, $manager) {
        
        require_once 'eleontev/Validator.php';
        
        $required = array('title', 'category', 'generated');
        
        $v = new Validator($values);

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
        
        if(!isset($values['do'])) {
            $v->setError('select_export_type_msg', 'type', 'type');
            $this->errors =& $v->getErrors();
            return true;
        }
            
    }
    
}
?>