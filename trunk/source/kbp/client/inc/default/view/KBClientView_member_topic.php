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

require_once 'KBClientView_member.php';
require_once 'KBClientView_forum.php';


class KBClientView_member_topic extends KBClientView_member
{	

	function &execute(&$manager) {
	    
        if(!$manager->is_registered) {
            $this->controller->go();
        }
		
		$this->home_link = true;
		$this->parse_form = false;
		$this->meta_title = $this->msg['my_account_msg'];
		
		$this->addMsg('user_msg.ini');
		
		$link = $this->controller->getLink('member');
		$this->nav_title = array($link => $this->msg['my_account_msg'], 
		                         $this->msg['member_topics_msg']);
		
		$data = &$this->getForm($manager);

		return $data;		
	}	
	
	
	function &getForm($manager) {
		
        $tpl = new tplTemplatez($this->getTemplate('member_tmpl.html'));
        
        $view = new KBClientView_forum_topic;
        
        $tpl->tplAssign('member_menu', $this->getMemberMenu($manager));
        $tpl->tplAssign('content_tmpl', $view->getTopicList($manager));
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
	}
}

    
class KBClientView_forum_topic extends KBClientView_forum
{
    
	function getTopicList(&$manager) {
		
		$manager->setSqlParams('AND ' . $manager->getPrivateSql(false));
		$manager->setSqlParams('AND ' . $manager->getCategoryRolesSql(false));
		
        $manager->setSqlParams('AND author_id = ' . $manager->user_id);
        
		$manager->setSqlParamsOrder('ORDER BY e.date_updated DESC');
        
        $num = $manager->getSetting('num_entries_per_page');
        $bp = $this->pageByPage($num, $manager->getEntryCount());
        
		$rows = &$manager->getEntryList($bp->limit, $bp->offset, 'index', false, 'FORCE INDEX (date_updated)');
        
        if (empty($rows)) {
            $str = '';
            return $str;
        }
        
        $page_by_page = '';
        if ($bp->num_pages > 1) {
            $page_by_page = $bp->navigate();
        }
        
		return $this->parseEntryList($manager, 
			                            $this->stripVars($rows), 
			                            $this->msg['member_topics_msg'],
										$page_by_page,
										'member_topic');
	}
	
}
?>