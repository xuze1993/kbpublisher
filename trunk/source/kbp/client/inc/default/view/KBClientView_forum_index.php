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


class KBClientView_forum_index extends KBClientView_forum
{	

	var $num_subcategories = 0;

	
	function &execute(&$manager) {
		
        if(!$manager->categories) {
            $msg = $this->getActionMsg('success', 'no_forums'); 
            return $msg; 
        }
        
		$title = $this->msg['forum_title_msg'];
		$this->meta_title = $title;

		$data = array();
        $data[] = $this->getEntryList($manager);
		$data[] = $this->getCategoryList($title, $manager);
		$data = implode('', $data);
		
		return $data;
	}
	
	
	// topics
	function getEntryList(&$manager) {
		
		$manager->setSqlParams('AND ' . $manager->getPrivateSql(false));
		$manager->setSqlParams('AND ' . $manager->getCategoryRolesSql(false));
		
		$recent_num = $manager->getSetting('num_recently_posted_entries');
		return $this->_getRecentlyPosted($manager, $recent_num);
	}
	
	
	function _getRecentlyPosted($manager, $num) {
        
		$manager->setSqlParamsOrder('ORDER BY e.date_updated DESC');		
		$rows = &$manager->getEntryList($num + 1, 0, 'index', false, 'FORCE INDEX (date_updated)');
        
        if (empty($rows)) {
            $str = '';
            return $str;
        }
        
        $more_link = false;
        if(count($rows) > $num) {
            $more_link = $this->getMoreLink('forum_recent');
            unset($rows[$num]);
        }
        
		return $this->parseEntryList($manager,
                                        $this->stripVars($rows, array('first_message')), 
                                        $this->msg['latest_discussions_msg'],
                                        '',
                                        'recent',
                                        false,
                                        $more_link);
	}
}
?>