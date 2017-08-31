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

require_once APP_CLIENT_DIR . 'client/inc/KBClientSearchModel_sphinx.php';


class KBClientSearchEngine_sphinx
{
    
    
    function __construct($manager, $values, $entry_type) {
        // if ($entry_type != 'all') {
            $this->manager = $this->getManager($manager, $values, $entry_type);
        // }
    }
    
    
    function getManager($manager, $values, $entry_type) {
        
        $smanager = new KBClientSearchModel_sphinx($values, $manager);
        
        $smanager->setEntryRolesParams($manager);
        
        $smanager->setPrivateParams($manager);
        $smanager->setCategoryRolesParams($manager);
        
        if ($entry_type != 'all') {
            $smanager->smanager->setIndexParams($entry_type);
        }
        
        $smanager->setCategoryStatusParams($manager);
        $smanager->setStatusParams($manager);
        $smanager->setFullTextParams($entry_type);
        
        $smanager->setDateParams($entry_type);
        
        // category
        if(in_array($entry_type, array('article', 'file', 'trouble', 'forum'))) {
            $smanager->setCategoryParams($manager->categories);
        }

        // type // $entry_type == 'article
        if(in_array($entry_type, array('article'))) {
            if(!empty($smanager->values['et'])) {
                $smanager->setEntryTypeParams();
            }
        }
        
        // custom
        if(!empty($smanager->values['custom'])) {
            $smanager->setCustomFieldParams($manager);
        }
        
        $smanager->setOrderParams($values, $entry_type);
        
        return $smanager;
    }
    
    
    function getAllSearchData($manager, $controller, $values, $limit, $offset) {
        
        $rows = array();
        $count = array();
        $managers = array(
            'article' => $manager
        );
        $source_id = array(1);
        
        $article_setting = $manager->setting;
        
        $smanager = new KBClientSearchModel_sphinx($values, $manager);
        $this->manager = $smanager;
        
        $smanager->setFullTextParams(false);
        
        $smanager->setDateParams();
        
        if($manager->getSetting('module_file')) {
            $source_id[] = 2;
            $managers['file'] = &KBClientLoader::getManager($manager->setting, $controller, 'files');
        }
        
        if($manager->getSetting('module_news')) {
            $source_id[] = 3;
            $managers['news'] = &KBClientLoader::getManager($article_setting, $controller, 'news');
        }
        
        if($manager->getSetting('module_forum')) {
            $source_id[] = 4;
            $managers['forum'] = &KBClientLoader::getManager($manager->setting, $controller, 'forums');
        }
        
        $smanager->smanager->setSourceParams($source_id);
        $smanager->setCategoryStatusParams($managers);
        $smanager->setStatusParams($managers);
        
        $smanager->setPrivateParams($manager);
        $smanager->setEntryRolesParams($manager);
        $smanager->setCategoryRolesParams($managers);
        
        list($count, $rows) = $smanager->getAllSearchData($limit, $offset, $managers);
        
        return array($count, $rows, $managers);
    }
}
?>