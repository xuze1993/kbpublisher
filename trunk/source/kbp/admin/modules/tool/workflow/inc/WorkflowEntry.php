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


class WorkflowEntry extends AppObj
{
    
    var $properties = array('id'             => NULL,
                            'entry_type'     => 1,
                            'trigger_type'   => 0,
                            'user_id'        => 0,
                            'trigger_key'    => '',
                            'title'          => '',
                            'options'        => '',
                            'cond_match'     => 2,
                            'cond'           => '',
                            'action'         => '',
                            'schedule'       => '',
                            'active'         => 1
                            );
    
    
    var $hidden = array('id', 'entry_type', 'trigger_type', 'user_id', 'trigger_key');
    var $reset_on_clone = array('id', 'title', 'user_id', 'trigger_key', 'active');
    
    var $condition = array();
    var $action = array();
    

    function _callBack($property, $val) {
        if($property == 'user_id' && !$val) {
            $val = AuthPriv::getUserId();
            
        } elseif($property == 'active' && $val === NULL) { // for reset_on_clone
            $val = 1;
        }
        
        return $val;
    }    
    
    
    function validate($values) {
        
        require_once 'eleontev/Validator.php';
        
        $required = array('title', 'action');
        
        $v = new Validator($values, false);

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }

    }
    
}
?>