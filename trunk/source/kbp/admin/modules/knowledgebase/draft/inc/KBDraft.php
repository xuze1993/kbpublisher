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

class KBDraft extends AppObj
{
    
    var $properties = array('id'                 => NULL,
                            // 'entry_type'         => 0,
                            'entry_id'           => 0,
                            'author_id'          => 0,
                            'updater_id'         => 0,
                            'title'              => '',
                            'date_posted'        => '',
                            // 'date_updated'       => '',
                            'private'            => 0,
                            'active'             => 1
                            );
    
    
    var $hidden = array('id', 'author_id', 'updater_id', 'date_posted');
    
    var $category = array();
    var $role_read = array();
    var $role_write = array();
    var $author = array();
    var $updater = array();
    
    var $sent_to_approval = false;
    var $last_event = false;
    var $approver_rule = false;
    
    var $title_name = 'title';
    
    
    function setCategory($val) {
        $val = ($val) ? $val : array();
        $this->category = &$val;
    }
    
    function &getCategory() {
        return $this->category;
    }
    
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
    
    
    function validate($values) {
        
        require_once 'eleontev/Validator.php';
        
        $required = array('title');
        
        $v = new Validator($values, false);

        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
    }
    
    
    function populate($vars, $eobj, $emanager) {
        
        $this->set('entry_type', $emanager->entry_type);
        
        if (!empty($vars['draft']['id'])) {
            $this->set('id', $vars['draft']['id']);
        }

        if (!empty($vars['id'])) {
            $this->set('entry_id', $vars['id']);
        }
        
        // new in v6.0, we need write roles to apply private atrributes
        $this->set('private', $eobj->get('private'));
        $this->role_write = $eobj->role_write;
        $this->category = $eobj->category;
        
        
        $entry_obj = clone $eobj; // need this not to modify $eobj
        $entry_obj = RequestDataUtil::stripslashesObj($entry_obj);
        $entry_obj = addslashes(serialize($entry_obj));
        $this->set('entry_obj', $entry_obj);
        
        $title = $eobj->get($this->title_name);
        $this->set('title', $title);
        
        $author_id = $eobj->get('author_id');
        $date_posted = $eobj->get('date_posted');
        
        if ($eobj->get('id')) { // entry id
            $author_id = $eobj->get('updater_id');
            
            // if (!$draft_id) { // creation
            if (empty($vars['draft']['id'])) { // creation
                $date_posted = date('Y-m-d H:i:s');
                
            } else {
                $date_posted = $vars['draft']['date_posted'];
            }
        }
        
        $this->set('author_id', $author_id);
        $this->set('updater_id', AuthPriv::getUserId());
        $this->set('date_posted', $date_posted);
    }
    
    
/*
    function populateFromEntry($entry_obj, $draft_id) {
        
        $author_id = $entry_obj->get('author_id');
        $date_posted = $entry_obj->get('date_posted');
        
        if ($entry_obj->get('id')) { // entry id
            $author_id = $entry_obj->get('updater_id');
            
            if (!$draft_id) { // creation
                $date_posted = date('Y-m-d H:i:s');
            }
        }
        
        $this->set('author_id', $author_id);
        $this->set('updater_id', AuthPriv::getUserId());
        $this->set('title', $entry_obj->get($this->title_name));
        $this->set('date_posted', $date_posted);
    }*/


}
?>