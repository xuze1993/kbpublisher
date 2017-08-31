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

class KBApiLoader
{	
	
    static function &getManager(&$setting, $controller, $force_view = false, $user = array()) {
        
        $files_view = array('fileCategories');
        if(in_array($force_view, $files_view)) {
            $force_view = 'files';
        }
        
        $forum_view = array('topicForums', 'topicMessages');
        if(in_array($force_view, $forum_view)) {
            $force_view = 'forums';
        }
        
        $manager = &KBClientLoader::getManager($setting, $controller, $force_view, $user);
        
        return $manager; 
    }
	

    static function &getApi(&$controller, &$manager) { 
            
        $suffix = $controller->call_map[$controller->call];
        $dir = $controller->api_dir . 'modules/' . $suffix . '/';
        
        $suffix = str_replace('_', ' ', $suffix);
        $suffix = ucwords($suffix);
        $suffix = str_replace(' ', '', $suffix);
        
        $class = 'KBApi' . ucwords($suffix);
        $file  = $class . '.php';
        require_once $dir . $file;
        
        $api = KBApiCommon::factory($controller, $controller->request_method, $class, $dir);
        $api->setVars($controller);
		
        // no need to admin actions, create, update, delete
        // if($controller->request_method == 'get') {
            $api->setCategoryId($controller, $manager);
        // }
        
        $api->checkPriv($controller, $manager);
        $api->validate($controller, $manager);
        
        return $api;
     }

}


function __autoload($className) { 
	
    if(stripos($className, 'search') !== false) {
        require_once APP_CLIENT_DIR . 'client/api/modules/search/' . $className . '.php'; 
        
    } elseif(stripos($className, 'file') !== false) {
        require_once APP_CLIENT_DIR . 'client/api/modules/file/' . $className . '.php'; 
    
    } elseif(stripos($className, 'news') !== false) {
        require_once APP_CLIENT_DIR . 'client/api/modules/news/' . $className . '.php'; 
    
    } elseif(stripos($className, 'articleCategory') !== false) {
        require_once APP_CLIENT_DIR . 'client/api/modules/article_category/' . $className . '.php';
    
    } elseif(stripos($className, 'fileCategory') !== false) {
        require_once APP_CLIENT_DIR . 'client/api/modules/file_category/' . $className . '.php';
        
    } elseif(stripos($className, 'topic') !== false) {
        require_once APP_CLIENT_DIR . 'client/api/modules/topic/' . $className . '.php';
        
    } else {
        require_once APP_CLIENT_DIR . 'client/api/modules/article/' . $className . '.php'; 
    }
}
?>