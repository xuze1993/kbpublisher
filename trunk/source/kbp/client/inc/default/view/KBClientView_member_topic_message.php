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

require_once APP_CLIENT_DIR . 'client/inc/DocumentParserForum.php';
require_once 'KBClientView_member.php';
require_once 'KBClientView_forum.php';


class KBClientView_member_topic_message extends KBClientView_member
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
		                         $this->msg['member_posts_msg']);
		
		$data = &$this->getForm($manager);

		return $data;		
	}	
	
	
	function &getForm($manager) {
		
        $tpl = new tplTemplatez($this->getTemplate('member_tmpl.html'));
        
        $view = new KBClientView_forum_topic_message;
        
        $tpl->tplAssign('member_menu', $this->getMemberMenu($manager));
        $tpl->tplAssign('content_tmpl', $view->getMessageList($manager));
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
	}
}



class KBClientView_forum_topic_message extends KBClientView_forum
{	
	
	function getMessageList(&$manager) {
        
        $num = $manager->getSetting('num_comments_per_page');
        $bp = $this->pageByPage($num, $manager->getUserPostsCount());
        
		$rows = $manager->getUserPosts($bp->limit, $bp->offset);
        $rows = $this->stripVars($rows);
        
        if (empty($rows)) {
            $str = '';
            return $str;
        }

        $tpl = new tplTemplatez($this->getTemplate('member_forum_list_message.html'));

        if ($bp->num_pages > 1) {
            $tpl->tplSetNeeded('/by_page_bottom');
            $tpl->tplAssign('page_by_page', $bp->navigate());
        }

        $tpl->tplAssign('list_title', $this->msg['member_posts_msg']);

        //$tpl->tplAssign('block_list_option_tmpl', $this->getBlockListOption($tpl, $manager, $entry, array('rss', 'subscribe', 'print')));

        foreach(array_keys($rows) as $k) {
            $row = $rows[$k];

			$row['anchor'] = 'post-' . $row['id'];
            $row['formated_date'] = $this->getFormatedDate($row['date_posted'], 'datetime');
            $row['interval_date_posted'] = $this->getTimeInterval($row['date_posted'], true);
            
            
            // icons
            //             $icon = 'topic';
            //             if ($row['active'] == 2) { $icon .= '_closed'; } // closed
            //             if (@$row['is_sticky']) { $icon .= '_pinned'; }
            // 
            // $private = $this->isPrivateEntry($row['private'], $row['category_private']);
            //             $row['item_img'] = $this->_getItemImg($manager->is_registered, $private, $icon);
            
            
            $more = array('message_id' => $row['id']);
            $row['topic_link'] = $this->controller->getLink('topic', false, $row['entry_id'], false, $more);

            if ($row['date_updated'] != $row['date_posted']) {
                $tpl->tplSetNeeded('row/updater');
                $row['interval_date_updated'] = $this->getTimeInterval($row['date_updated'], true);

                $user = $manager->getUserInfo($row['updater_id']);
                $row['updater_first_name'] = $user['first_name'];
                $row['updater_last_name'] = $user['last_name'];
            }

            $tpl->tplParse($row, 'row');
        }

        if(DocumentParser::isCode2($tpl->parsed['row'])) {
            DocumentParser::parseCode2($tpl->parsed['row'], $this->controller);
        }

        $tpl->tplParse();
        return $tpl->tplPrint(1);
	}
	
}
?>