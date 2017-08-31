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

class CustomFieldRangeValue extends AppObj
{
    
    var $properties = array('id'             => NULL,
                            'range_id'         => '',
                            'title'            => '',
                            'sort_order'      => 1
                            );
    
    
    var $hidden = array('id', 'range_id');    
    
    
    function validate($values) {
        
        require_once 'eleontev/Validator.php';
        
        $required = array('title');
        
        $v = new Validator($values, false);

        // check for required first, return errors
        $v->required('required_msg', $required);
        
        $this->errors = &$v->getErrors();
        return $this->errors;
    }
    
}
?>