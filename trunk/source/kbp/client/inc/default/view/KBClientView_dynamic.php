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


class KBClientView_dynamic extends KBClientView_index
{
    
    var $dynamic_limit = 25;
    var $dynamic_reload_limit = 200;
    var $dynamic_sname = 'kb_dynamic_loaded_%s_';
    var $load_button = true;
    
    
    function &execute(&$manager) {

        $limit = $this->dynamic_limit;
        $sname = sprintf($this->dynamic_sname, $this->dynamic_type);
        if (!empty($_SESSION[$sname])) {
            $limit = $_SESSION[$sname];
            if ($limit > $this->dynamic_reload_limit) {
                $limit = $this->dynamic_reload_limit;
            }
        }
        
        switch ($this->dynamic_type) {
            case 'recent':
                $title = $this->msg['recently_posted_entries_title_msg'];
                $rows = $this->getEntryListRecent($manager, $limit + 1);
                break;
                
            case 'popular':
                $title = $this->msg['most_viewed_entries_title_msg'];
            	$rows = $this->getEntryListMostViewed($manager, $limit + 1);
            	break;
                
            case 'featured':
            	$title = $this->msg['featured_entries_title_msg'];
            	$rows = $this->getEntryListFeatured($manager, $limit + 1);
            	break;
        }
        
        if (count($rows) <= $limit) {
            $this->load_button = false;
            
        } else {
            array_pop($rows);
        }
        
        $this->home_link = true;
        $this->nav_title = $title;
        
        $this->meta_title = $title;
        $this->meta_keywords = $manager->getSetting('site_keywords');
        $this->meta_description = $manager->getSetting('site_description');
         
        $data = $this->parseArticleList($manager, $this->stripVars($rows), $title);
                
        return $data;
    }
    
    
    function getEntryListRecent(&$manager, $limit, $offset = 0) {
        $manager->setSqlParams('AND ' . $manager->getPrivateSql(false));
        $manager->setSqlParams('AND ' . $manager->getCategoryRolesSql(false));
        $this->setRecentlyPostedSqlParams($manager);

        return $manager->getEntryList($limit, $offset, 'index', 'FORCE INDEX (date_updated)');
    }
    
    
    function getEntryListMostViewed(&$manager, $limit, $offset = 0) {
        $manager->setSqlParams('AND ' . $manager->getPrivateSql(false));
        $manager->setSqlParams('AND ' . $manager->getCategoryRolesSql(false));
        $this->setMostViewedSqlParams($manager);
        
        return $manager->getEntryList($limit, $offset, 'index', 'FORCE INDEX (hits)');
    }
    
    
    function getEntryListFeatured(&$manager, $limit, $offset = 0) {
        $manager->setSqlParams('AND ' . $manager->getPrivateSql(false));
        $manager->setSqlParams('AND ' . $manager->getCategoryRolesSql(false));
        
        if ($this->category_id) {
            return $manager->getFeaturedInCategory($limit, $offset, $this->category_id);
        } else {
            $this->setFeaturedSqlParams($manager);
            return $manager->getEntryList($limit, $offset, 'index');
        }
    }
    
    
    function &getBlockListOption(&$tmpl, $manager, $options = array()) {
        $a = '';
        return $a;
    }
    
}
?>