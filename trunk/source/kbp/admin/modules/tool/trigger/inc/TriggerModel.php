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


class TriggerModel
{
    
    var $user_limit = 3;


    static function &instance($model) {
        static $registry;
        
        if(empty($registry[$model])) {
            $registry[$model] = TriggerModel::factory($model);
        }
        
        return $registry[$model];
    }


    static function factory($model) {
        if($model == 'KBEntryModel') {
            require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryModel.php';
            return new KBEntryModel;
        
        } elseif($model == 'FileEntryModel') {
            require_once APP_MODULE_DIR . 'file/entry/inc/FileEntryModel.php';
            return new FileEntryModel;

        } elseif($model == 'TicketEntryModel') {
            require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryModel.php';
            return new KBEntryModel;

        } elseif($model == 'UserModel') {
            require_once APP_MODULE_DIR . 'user/user/inc/UserModel.php';
            return new UserModel;
        } 
    }
    
    
    function &getCategorySelectRange($type) {
        if($type == 'article') {
            $m = &TriggerModel::instance('KBEntryModel');
            $range = $m->getCategorySelectRange();
    
        } elseif($type == 'file') {
            $m = &TriggerModel::instance('FileEntryModel');
            $range = $m->getCategorySelectRange();            
        }
        
        return $range;
    }
    
    
    function getCategoryById($type, $id) {
        if($type == 'article') {
            $m = &TriggerModel::instance('KBEntryModel');
    
        } elseif($type == 'file') {
            $m = &TriggerModel::instance('FileEntryModel');     
        }
        
        $category = $m->cat_manager->getById($id);
        return $category;
    }
    
    
    function getUserSelectRange($extra_id, $placeholders = array(), $empty_allowed = true) {

        $m = &TriggerModel::instance('UserModel');
        
        $sql = "SELECT u.id, u.first_name, u.last_name 
            FROM {$m->tbl->user} u, {$m->tbl->priv} p
            WHERE u.id = p.user_id
                AND u.id != {$m->user_id}
                AND u.active = 1
            ORDER BY u.last_name ASC";
            
        $result = $m->db->SelectLimit($sql, $this->user_limit + 1) or die(db_error($sql));
        $rows = $result->GetArray();
        
        $data = array();
        
        if ($empty_allowed) {
            $data[0] = '__';
        }
                
        if (!empty($placeholders)) {
            foreach ($placeholders as $k => $v) {
                $data[$k] = $v;
            }
        }
        
        $user = $m->getById($m->user_id);
        $data[$m->user_id] = $this->parseUserName($user);
        
        if (!empty($extra_id) && is_numeric($extra_id)) {
            $user = $m->getById($extra_id);
            $data[$extra_id] = $this->parseUserName($user);
        }
        
        $more_option = false;
        if(count($rows) > $this->user_limit) {
            $more_option = true;
            array_pop($rows);
        }
        
        foreach ($rows as $row) {
            $data[$row['id']] = $this->parseUserName($row);
        }
        
        if ($more_option) {
            $data['more'] = '...';
        }
        
        return $data;
    }
    
    
    function parseUserName($user) {
        return sprintf('%s %s', $user['first_name'], $user['last_name']);
    }
           
}
?>