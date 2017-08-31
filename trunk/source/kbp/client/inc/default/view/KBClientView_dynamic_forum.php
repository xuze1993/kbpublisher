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


class KBClientView_dynamic_forum extends KBClientView_forum
{
    
    var $dynamic_limit = 5;
    var $dynamic_reload_limit = 5;
    var $dynamic_sname = 'kb_dynamic_loaded_%s_';
    
    
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
            case 'forum_recent':
                $title = $this->msg['latest_discussions_msg'];
                $rows =& $this->getEntryListRecent($manager, $limit);
                break;
        }
        
        $this->home_link = true;
        $this->nav_title = $title;
        
        $this->meta_title = $title;
        $this->meta_keywords = $manager->getSetting('site_keywords');
        $this->meta_description = $manager->getSetting('site_description');
        
        $data = $this->parseEntryList($manager, $this->stripVars($rows, array('first_message')), $title);
        return $data;
    }
    
    
    function getEntryListRecent(&$manager, $limit, $offset = 0) {
        //$manager->setSqlParams('AND ' . $manager->getPrivateSql(false));
        //$manager->setSqlParams('AND ' . $manager->getCategoryRolesSql(false));
        //$this->setRecentlyPostedSqlParams($manager);
        
        return $manager->getEntryList($limit, $offset, 'index', 'FORCE INDEX (date_updated)');
    }
    
    
    function &getBlockListOption(&$tmpl, $manager, $options = array()) {
        $a = '';
        return $a;
    }
    
}
?>