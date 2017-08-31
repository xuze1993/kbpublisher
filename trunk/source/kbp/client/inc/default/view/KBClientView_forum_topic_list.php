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


// list topics 
class KBClientView_forum_topic_list extends KBClientView_forum
{	

	var $num_subcategories = 0;
    var $num_topics = true;
    
	
	function &execute(&$manager) {
		
		// does not matter why no category, deleted, or inactive or private
		// always send 404, SE will exclude it from result, users will not see 404
		if(!isset($manager->categories[$this->category_id])) { 
		    
            // new private policy, check if category exists
            if($manager->is_registered) {
                if($manager->isCategoryExistsAndActive($this->category_id)) {
                    $this->controller->goAccessDenied('forums');
                }
            }
		    
			$this->controller->goStatusHeader('404');
		}
		
        $in_section = $manager->isForumInSection($this->category_id);
        
		$rows = $this->stripVars($manager->getCategoryList($this->category_id));
		$title = ($in_section) ? $this->msg['forum_title_msg'] : $this->msg['subforum_title_msg'];
		$this->meta_title = $this->stripVars($manager->categories[$this->category_id]['name']);
		$this->num_subcategories = count($rows);

        // section, status
        $topic_allowed = $manager->isTopicAddingAllowed($this->category_id);

        // not logged, private, banned 
        if($topic_allowed && !$manager->isTopicAddingAllowedByUser($this->category_id)) { 
            $topic_allowed = false;
        }
        
        $data = array();
        $data[] = $this->getCategoryList($title, $manager, $this->num_topics);
        $data[] = $this->getEntryList($manager, $topic_allowed);
        
		$data = implode('', $data);

		return $data;
	}


	// topics
	function &getEntryList(&$manager, $add_button = true) {
		
		$manager->setSqlParams('AND ' . $manager->getPrivateSql(false));
		$manager->setSqlParams('AND ' . $manager->getCategoryRolesSql(false));
		
		$num = $manager->getSetting('num_entries_per_page');
		return $this->_getCategoryEntries($manager, $num, $add_button);
	}


	function &_getCategoryEntries($manager, $num, $add_button = true) {
			
		$manager->setSqlParams("AND cat.id = '{$this->category_id}'");
		$bp = $this->pageByPage($num, $manager->getEntryCount());
		 
		$sort = $manager->getSortOrder();
		$manager->setSqlParamsOrder('ORDER BY ' . $sort . ' DESC');
		$rows = &$manager->getEntryList($bp->limit, $bp->offset, 'category');
        
        $this->num_topics = count($rows);
		
		if(!$rows && !$this->num_subcategories) {
		    if ($add_button) {
		        $str = ' - <a href="%s">%s</a>';
                $add_link = $this->getLink('forums', $this->category_id, false, 'post');
                $add_link = $vars['link'] = sprintf($str, $add_link, $this->msg['add_entry_msg']);
                
		    } else {
		        $add_link = '';
		    }
             
			$msg = &$this->getActionMsg('success', 'no_category_topic', false, array('link' => $add_link));
			return $msg;
		}
        
        $page_by_page = '';
        if ($bp->num_pages > 1) {
            $page_by_page = $bp->navigate();
        }
        
        $title = $this->meta_title;
		$list = $this->parseEntryList($manager, 
			                          $this->stripVars($rows, array('first_message')), 
			                          $title, 
			                          $page_by_page,
                                      '',
                                      $add_button);
                                      
        return $list;
	}
	
}
?>