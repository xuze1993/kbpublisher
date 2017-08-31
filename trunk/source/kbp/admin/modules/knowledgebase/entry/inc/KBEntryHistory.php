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

class KBEntryHistory extends AppObj
{
    
    var $properties = array('revision_num'        => 1,
                            'entry_id'            => 0,
                            'user_id'             => 0,
                            'date_posted'         => '',
                            'comment'             => '',
                            'entry_updater_id'    => 0,
                            'entry_date_updated'  => '',
                            'entry_data'          => ''
                            );
    
    
    var $hidden = array();
    var $author = array();
    var $updater = array();
    
    
    function setAuthor($val) {
        $this->author = &$val;
    }
    
    function &getAuthor() {
        return $this->author;
    }    
    
    function setUpdater($val) {
        $this->updater = &$val;
    }
    
    function &getUpdater() {
        return $this->updater;
    }
        
    
    function _callBack($property, $val) {
        //if($property == 'date_posted' && !$val) {
            // $val = date('Y-m-d H:i:s');
        
        if($property == 'author_id' && !$val) {
            $val = AuthPriv::getUserId();
        }   
        
        return $val;
    }
    
    
    function validate($values) {
        
/*
        require_once 'eleontev/Validator.php';
        
        $required = array('title', 'body', 'category');
        
        $v = new Validator($values, false);

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
*/

    }
}
?>