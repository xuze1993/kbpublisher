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

require_once APP_CLIENT_DIR . 'client/inc/KBClientSearchModel_mysql.php';


class KBClientSearchEngine_mysql
{   
    
    function __construct($manager, $values, $entry_type) {
        // if ($entry_type != 'all') {
            $this->manager = $this->getManager($manager, $values, $entry_type);
        // }
    }
    
    
    function getManager($manager, $values, $entry_type) {
        
        $smanager = new KBClientSearchModel_mysql($values, $manager);
        
        $smanager->user_id = $manager->user_id;
        $smanager->user_role_id = $manager->user_role_id;
        $smanager->entry_published_status = $manager->entry_published_status;
        $smanager->entry_type = $manager->entry_type;

        $smanager->entry_role_sql_from = $manager->entry_role_sql_from;
        $smanager->entry_role_sql_where = $manager->entry_role_sql_where;
        $smanager->entry_role_sql_group = $manager->entry_role_sql_group;
        
        $search_type = 1; //$manager->getSetting('search)type'); // 1 = fulltext
        $smanager->setFullTextParams($entry_type, $search_type);
        
        $smanager->setDateParams($entry_type);

        // category
        if(in_array($entry_type, array('article', 'file', 'trouble', 'forum'))) {
            $smanager->setCategoryParams($manager->categories);
            
            if(!empty($smanager->values['c'])) {
                $smanager->select_type = 'category';
            }
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
        
        $smanager->setSqlParams('AND ' . $manager->getPrivateSql(false), 'category');
        $smanager->setSqlParams('AND ' . $manager->getCategoryRolesSql(false));
        
        $smanager->setOrderParams($values, $entry_type);
        $smanager->sort_by_score = KBClientSearchHelper::isOrderByScore($values);
        
        return $smanager;
    }
    
    
    function getAllSearchData($manager, $controller, $values, $limit, $offset) {
        
        $rows = array();
        $count = array();
        $managers = array();
        
        $article_setting = $manager->setting;
        
        $smanager = $this->getManager($manager, $values, 'article');
        $this->manager = $smanager;
         
        $rows['article'] = $smanager->getArticleList($limit, $offset);
        $count['article'] = $smanager->getArticleCount();
        $managers['article'] = $manager;
        
        if($manager->getSetting('module_file')) {
            $values['in'] = 'file';
            $manager = &KBClientLoader::getManager($manager->setting, $controller, 'files');
            $smanager = $this->getManager($manager, $values, 'file');
            
            $rows['file'] = $smanager->getFileList($limit, $offset, $manager);
            $count['file'] = $smanager->getFileCount($manager);
            $managers['file'] = $manager;
        }
        
        if($manager->getSetting('module_news')) {
            $values['in'] = 'news';
            $manager = &KBClientLoader::getManager($article_setting, $controller, 'news');
            $smanager = $this->getManager($manager, $values, 'news');
            
            $rows['news'] = $smanager->getNewsList($limit, $offset, $manager);
            $count['news'] = $smanager->getNewsCount($manager);
            $managers['news'] = $manager;
        }
        
        if($manager->getSetting('module_forum')) {
            $values['in'] = 'forum';
            $manager = &KBClientLoader::getManager($manager->setting, $controller, 'forums');
            $smanager = $this->getManager($manager, $values, 'forum');
            
            $rows['forum'] = $smanager->getForumEntryList($limit, $offset, $manager);
            $count['forum'] = $smanager->getForumMessageCount($manager);
            $managers['forum'] = $manager;
        }
        
        return array($count, $rows, $managers);
    }
    
    
    // function getAllSearchDataOld($manager, $controller, $values, $bp) {
    //
    //     $rows = array();
    //     $count = array();
    //     $managers = array();
    //
    //     $article_setting = $manager->setting;
    //
    //     $smanager = $this->getManager($manager, $values, 'article');
    //     $this->manager = $smanager;
    //
    //     $rows['article'] = $smanager->getArticleList($bp->limit, $bp->offsets[1]);
    //     $count['article'] = $smanager->getArticleCount();
    //     $managers['article'] = $manager;
    //
    //     if($manager->getSetting('module_file')) {
    //         $values['in'] = 'file';
    //         $manager = &KBClientLoader::getManager($manager->setting, $controller, 'files');
    //         $smanager = $this->getManager($manager, $values, 'file');
    //
    //         $rows['file'] = $smanager->getFileList($bp->limit, $bp->offsets[2], $manager);
    //         $count['file'] = $smanager->getFileCount($manager);
    //         $managers['file'] = $manager;
    //     }
    //
    //     if($manager->getSetting('module_news')) {
    //         $values['in'] = 'news';
    //         $manager = &KBClientLoader::getManager($article_setting, $controller, 'news');
    //         $smanager = $this->getManager($manager, $values, 'news');
    //
    //         $rows['news'] = $smanager->getNewsList($bp->limit, $bp->offsets[3], $manager);
    //         $count['news'] = $smanager->getNewsCount($manager);
    //         $managers['news'] = $manager;
    //     }
    //
    //     if($manager->getSetting('module_forum')) {
    //         $values['in'] = 'forum';
    //         $manager = &KBClientLoader::getManager($manager->setting, $controller, 'forums');
    //         $smanager = $this->getManager($manager, $values, 'forum');
    //
    //         $rows['forum'] = $smanager->getForumEntryList($bp->limit, $bp->offsets[4], $manager);
    //         $count['forum'] = $smanager->getForumMessageCount($manager);
    //         $managers['forum'] = $manager;
    //     }
    //
    //     return array($count, $rows, $managers);
    // }
}
?>