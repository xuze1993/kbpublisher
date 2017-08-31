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

class KBClientLoader
{
    
    static function &getManager(&$setting, $controller, $force_view = false, $user = array()) {
        
        require_once $controller->working_dir . 'KBClientModel_common.php';
        
        $file_views = array('files', 'file', 'download');
        $news_views = array('news', 'print-news');
        $trouble_views = array('troubles', 'trouble', 'print-trouble', 'print-step');
        $forum_views = array('forums', 'topic', 'print-topic', 'forum_recent',
                                'member_topic', 'member_topic_message', 'forum_preview');
        
        $view_id = $controller->view_id;
        if($force_view !== false) {
            $view_id = $force_view;
        }
        
        // files
        if(in_array($view_id, $file_views)) {
                            
            require_once  $controller->common_dir . 'KBClientFileModel.php';
            
            $setting = array_merge($setting, KBClientModel::getSettings(200));
            $manager = new KBClientFileModel;
        
        // news
        } elseif(in_array($view_id, $news_views)) {
            
            require_once  $controller->common_dir . 'KBClientNewsModel.php';
            
            $manager = new KBClientNewsModel;

        // trouble
        } elseif(in_array($view_id, $trouble_views)) {
            
            require_once  $controller->common_dir . 'KBClientTroubleModel.php';
            
            $setting = array_merge($setting, KBClientModel::getSettings(500));
            $manager = new KBClientTroubleModel;
            
        // forum
        } elseif(in_array($view_id, $forum_views)) {
            
            if(!BaseModel::isModule('forum')) {
                $controller->goStatusHeader('404');
            }
            
            require_once  $controller->common_dir . 'KBClientForumModel.php';
            
            $setting = array_merge($setting, KBClientModel::getSettings(600));
            $manager = new KBClientForumModel;
            
        // articles
        } else {
        
            $manager = new KBClientModel_common;
        }
        
        $manager->setting = &$setting;
        $manager->setCustomSettings($controller);
        
        // sort order, removed from settings
        $manager->setting['category_sort_order'] = 'sort_order';
        
        // disable module
        if(!BaseModel::isModule('forum')) {
            $manager->setting['module_forum'] = false;
        }
        

        if(isset($user['user_id'])) {
            $manager->is_registered = (!empty($user['user_id'])) ? true : false;
        } else {
            $manager->is_registered = AuthPriv::isAuthStatic($setting['auth_check_ip']);
        }
        
        $manager->user_id =      (isset($user['user_id'])) ? $user['user_id'] : AuthPriv::getUserId();
        $manager->user_priv_id = (isset($user['priv_id'])) ? $user['priv_id'] : AuthPriv::getPrivId();
        $manager->user_role_id = (isset($user['role_id'])) ? $user['role_id'] : AuthPriv::getRoleId();        
        
        $manager->setCategories();
        $manager->setEntryRolesSql();
        $manager->setEntryPublishedStatus();
                
        //echo ($manager->is_registered) ? 'Registered' : 'Not Registered';
        //echo "<pre>"; print_r($manager->setting); echo "</pre>";
        // echo "<pre>"; print_r($manager->categories); echo "</pre>";
        // echo "<pre>"; print_r($manager); echo "</pre>";
        //exit;
                
        return $manager;
    }
    
    
    static function &getView(&$controller, &$manager) {
                
        require_once $controller->working_dir . 'KBClientView_common.php';
        require_once $controller->working_dir . 'KBClientAction_common.php';
        
        $actions = array(
            'index', 'entry', 'glossary', 'tags', 'comment', 'contact', 'send', 'search', 'rate',
            'recent', 'popular', 'featured', 
            'register', 'confirm', 'login', 'logout', 'password', 'password_rotation', 
            'member', 'member_account', 'member_subsc', 
            'success_go', 'print', 'afile', 'news', 'rssfeed',
            'file', 'download', 'entry_add',
            'subscribe', 'unsubscribe', 'pdf', 'preview', 'trouble',
            'forums', 'topic', 'forum_recent', 'forum_preview', 'search_category'
            );
        
        $suffics = (in_array($controller->view_id, $actions)) ?  $controller->view_id : 'default';
        if(strpos($controller->view_id, 'print') !== false) {
            $suffics = 'print';
        } elseif(strpos($controller->view_id, 'pdf') !== false) {
            $suffics = 'pdf';
        }
        
        $action_class = 'KBClientAction_' . $suffics;
        $action_file  = 'KBClientAction_' . $suffics . '.php';
        
        $file = $controller->working_dir . 'action/' . $action_file;
        if(!file_exists($file)) {
            $file = $controller->default_working_dir . 'action/' . $action_file;
        }
        
        require_once($file);
        
        $action = new $action_class;
        $action->setVars($controller);
        $action->setCategoryId($controller, $manager);
        $action->checkPrivate($controller, $manager);
        return $action->execute($controller, $manager);
    }
        
    
    function _getViewCommon() {
        
    }
    
    
    function _getViewFile() {
        
    }
}
?>