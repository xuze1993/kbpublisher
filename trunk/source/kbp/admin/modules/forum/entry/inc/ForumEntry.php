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

class ForumEntry extends AppObj
{
	
	var $properties = array('id'		 		=> NULL,
							'category_id'		=> 0,
							'author_id' 		=> 0,
							'updater_id'		=> 0,
                            'first_post_id' 	=> 0,
							'last_post_id' 		=> 0,
							'title'	 			=> '',
							'url_title' 		=> '',
                            'meta_keywords'     => '',
							'date_posted'		=> '',
                            //'date_updated'       => '', // actually used for latest comment?
							'posts'				=> 0,
							'hits'				=> 0,
							'private'			=> 0,
							'active'			=> 1
							);
	
	
	var $hidden = array('id', 'author_id', 'updater_id', 'date_posted', 'hits', 'posts', 'last_post_id');
	var $category = array();
	var $sort_values = array();
	var $attachment = array();
	var $role_read = array();
    var $role_write = array();
	var $author = array();
	var $updater = array();
	var $schedule = array();
    var $tag = array();
    var $first_message = '';
    var $sticky = false;
    var $sticky_date = false;	
	
	
	function setCategory($val) {
		$val = ($val) ? $val : array();
		$this->category = &$val;
		$this->set('category_id', (is_array($val)) ? current($val) : $val);
	}
	
	function &getCategory() {
		return $this->category;
	}
    
    function getRoleRead() {
        return $this->role_read;
    }
    
    function setRoleRead($role) {
        $this->role_read = $role;
    }
        
    function getRoleWrite() {
        return $this->role_write;
    }
    
    function setRoleWrite($role) {
        $this->role_write = $role;
    }
	
	function setAttachment($val) {
		$this->attachment = &$val;
	}
	
	function &getAttachment() {
		return $this->attachment;
	}	
	
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
  
	function setSchedule($key, $val) {
		$this->schedule[$key] = $val;
	}
	
	function getSchedule() {
		return $this->schedule;
	}
    
    function setTag($val) {
        $this->tag = &$val;
    }
    
    function getTag() {
        return $this->tag;
    }
    
    function setFirstMessage($val) {
        $this->first_message = &$val;
    }
    
    function &getFirstMessage() {
        return $this->first_message;
    }
    
    function setSticky($val) {
        $this->sticky = &$val;
    }
    
    function &getSticky() {
        return $this->sticky;
    }
    
    function setStickyDate($val) {
        $this->sticky_date = &$val;
    }
    
    function &getStickyDate() {
        return $this->sticky_date;
    }
  
	function _callBack($property, $val) {
		if($property == 'date_posted' && !$val) {
			$val = date('Y-m-d H:i:s');
		
		} elseif($property == 'author_id' && !$val) {
			$val = AuthPriv::getUserId();
		
		} elseif($property == 'updater_id' && !$val) { // who's posted the last comment 
			$val = AuthPriv::getUserId();
		
		// get private value from array
		} elseif($property == 'private' && is_array($val)) {
			$val = PrivateEntry::getPrivateValue($val);
		}   
		
		return $val;
	}
	
	
	function validate($values, $message_required = false) {
		
		require_once 'eleontev/Validator.php';
		
		$required = array('category', 'title');
        if ($message_required) {
            $required[] = 'message';
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
        $ret['options'] = array($values);
        return $ret;
    }	
}
?>