<?php

class DataConsistencyModel extends AppModel
{
 
    static function &getEntryManager($user, $type) {
        
        // admin user
        if($user === 'admin') {
            $user = array(
                'user_id' => 0, 
                'priv_id' => 1, 
                'role_id' => array()
                );
        }
        
        if($type == 1 || $type == 11) {
            require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryModel.php';
            $emanager = new KBEntryModel($user, 'read');
        
        } elseif ($type == 3) {            
            require_once APP_MODULE_DIR . 'news/inc/NewsEntryModel.php';
            $emanager = new NewsEntryModel;
            
        } else {            
            require_once APP_MODULE_DIR . 'file/entry/inc/FileEntryModel.php';
            $emanager = new FileEntryModel($user, 'read');
        }
    
        return $emanager;
    }
 
    
    static function &getCategoryManager($user, $type) {
        
        // admin user
        if($user === 'admin') {
            $user = array(
                'user_id' => 0, 
                'priv_id' => 1, 
                'role_id' => array()
                );
        }
        
        if($type == 11) {
            require_once APP_MODULE_DIR . 'knowledgebase/category/inc/KBCategoryModel.php';
            $manager = new KBCategoryModel($user);
        
        } elseif ($type == 12) {            
            require_once APP_MODULE_DIR . 'file/category/inc/FileCategoryModel.php';
            $manager = new FileCategoryModel($user);
            
        } else {            
            require_once APP_MODULE_DIR . 'forum/category/inc/ForumCategoryModel.php';
            $manager = new ForumCategoryModel($user);
        }
    
        return $manager;
    }
}
?>