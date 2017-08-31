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


class KBClientView_member extends KBClientView_common
{
    
    function &execute(&$manager) {
        
        $this->home_link = true;
        $this->parse_form = false;
        $this->meta_title = $this->msg['my_account_msg'];
        $this->nav_title = $this->msg['my_account_msg'];
        
        $data = $this->getList($manager, $this->msg['my_account_msg']);

        return $data;        
    }
    
    
    function getList($manager) {
        
        $tpl = new tplTemplatez($this->getTemplate('member_home.html'));
        $tpl->tplAssign('member_menu', $this->getMemberMenu($manager));
        
        foreach ($this->items as $k => $v) {
            if($k == 'member') {
                continue;
            }
            
            $v['link'] = $this->getLink($k);
            
            $tpl->tplParse($v, 'row');
        }        

        $tpl->tplParse();
        return $tpl->tplPrint(1);    
    }
    
    
    function getMemberMenu($manager) {
    
        require_once 'eleontev/Navigation.php';
    
        $nav = new Navigation;
        $nav->setEqualAttrib('GET', 'View');
        $nav->setTemplate($this->getTemplate('block_tab_menu.html'));
        $nav->setDefault('member');

        $this->items = array(
            'member' => array(
				'title' => $this->msg['member_home_msg']),  
				
            'member_account' => array(
				'title' => $this->msg['member_account_msg'], 
				'desc'  => $this->msg['member_account_desc_msg']),
            
			'member_subsc' => array(
				'title' => $this->msg['member_subsc_msg'],
				'desc'  => $this->msg['member_subsc_desc_msg']),
            
			'member_topic' => array(
				'title' => $this->msg['member_topics_msg'],
				'desc'  => $this->msg['member_topics_desc_msg']),
            
			'member_topic_message' => array(
				'title' => $this->msg['member_posts_msg'],
				'desc'  => $this->msg['member_posts_desc_msg'])
            );


        if(!$manager->isSubscribtionAllowed('entry') && !$manager->isSubscribtionAllowed('news')) {
            unset($this->items['member_subsc']);
        }

		if(!BaseModel::isModule('forum')) {
			unset($this->items['member_topic']);
			unset($this->items['member_topic_message']);
		}

        $order = 0;
        foreach ($this->items as $k => $v) {
            $nav->setMenuItem($v['title'], $this->getLink($k));
            $nav->auxilary[$nav->menu_name][$order++] = $k;
        }
        
        return $nav->generate();    
    }    
}
?>