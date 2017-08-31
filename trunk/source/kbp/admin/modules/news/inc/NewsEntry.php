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

class NewsEntry extends AppObj
{
    
    var $properties = array('id'                => NULL,
                            'author_id'         => 0,
                            'updater_id'        => 0,    
                            'title'             => '',
                            'body'              => '',
                            // 'body_index'        => '',
                            'meta_keywords'     => '',
                            'date_posted'       => '',
                            // 'date_updated'      => '',
                            'hits'              => 0,
                            'private'           => 0,
                            'place_top_date'    => NULL,
                            'active'            => 1
                            );
    
    
    var $hidden = array('id', 'author_id', 'updater_id', 'hits');
    var $reset_on_clone = array('id', 'title', 'author_id', 'hits');
    
    var $role_read = array();
    var $role_write = array();
    var $author = array();
    var $updater = array();    
    var $schedule = array();
    var $tag = array();	
    var $custom = array();

    
    function setRoleRead($role) {
        $this->role_read = $role;
    }
    
    function getRoleRead() {
        return $this->role_read;
    }    
    
    function getRoleWrite() {
        return $this->role_write;
    }
    
    function setRoleWrite($role) {
        $this->role_write = $role;
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
	    
    function setCustom($val) {
        $this->custom = &$val;
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
    
    function getCustom() {
        return $this->custom;
    }
    
    function _callBack($property, $val) {
        if($property == 'author_id' && !$val) {
            $val = AuthPriv::getUserId();
        
        } elseif($property == 'updater_id') {
            $val = AuthPriv::getUserId();
        
        // get private value from array
        } elseif($property == 'private' && is_array($val)) {
            $val = PrivateEntry::getPrivateValue($val);
        }
        
        return $val;
    }
    
    
    function validate($values, $manager) {
        
        require_once 'eleontev/Validator.php';
        
        $required = array('title', 'body');
        
        if (isset($values['udate_posted'])) {
            $required[] = 'udate_posted';
        }
        
        $v = new Validator($values, false);
        
        $v->required('required_msg', $required);
        
        
        // date posted
        if (!strtotime($values['date_posted'])) {
            $v->setError('invalid_date_msg', 'date_posted');
        }
        
        if (!empty($values['udate_posted'])) { // ajax
             if(strlen($values['udate_posted']) < 5  || !strtotime($values['udate_posted'])) {
                 $v->setError('invalid_date_msg', 'udate_posted');
             }
        }
        
        // custom
        $fields = $manager->cf_manager->getCustomField();
        $error = $manager->cf_manager->validate($fields, $values);
        if($error) {
            $v->setError($error[0], $error[1], $error[2], $error[3]);
            $this->errors =& $v->getErrors();                    
            return true;
        }
        
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
    }
    
    
    function restore($manager) {

        foreach($this->getSchedule() as $num => $v) {
            $v['date'] = strtotime($v['date']);
            $this->setSchedule($num, $v);
        }

        $tag = $this->getTag();
        if(!empty($tag)) {
            $ids = implode(',', $tag);
            $this->setTag($manager->tag_manager->getTagByIds($ids));
        }
    }
    
    
    function populate($data, $manager, $to_form = false) {
        
        $this->set($data);
        
        // roles
        if(!empty($data['role_read'])) {
            $this->setRoleRead($data['role_read']);
        }

        if(!empty($data['role_write'])) {
            $this->setRoleWrite($data['role_write']);
        }
        
        // schedule
        if(!empty($data['schedule_on'])) {
            foreach($data['schedule_on'] as $num => $v) {
                if($to_form) {
                    $data['schedule'][$num]['date'] = strtotime($data['schedule'][$num]['date']);
                    
                } else {
                    $data['schedule'][$num]['date'] = date('YmdHi00', strtotime($data['schedule'][$num]['date']));
                }
                
                $this->setSchedule($num, $data['schedule'][$num]);
            }
        }
        
        // tags
        if(!empty($data['tag'])) {
            $ids = implode(',', $data['tag']);
            
            if($to_form) {
                $ids = implode(',', $data['tag']);
                $this->setTag($manager->tag_manager->getTagByIds($ids));
                
            } else {
                $keywords = $manager->tag_manager->getKeywordsStringByIds($ids);
                $keywords = RequestDataUtil::addslashes($keywords);
                $this->set('meta_keywords', $keywords);
                
                $this->setTag($data['tag']);
            }
            
        } else {
            $this->setTag(array());
        }
        
        // custom
        if(!empty($data['custom'])) {
            $this->setCustom($data['custom']);
        }
    }
}
?>