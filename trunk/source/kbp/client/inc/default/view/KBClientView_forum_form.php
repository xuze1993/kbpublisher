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


class KBClientView_forum_form extends KBClientView_forum
{

	function &execute(&$manager) {
		             
		$row = &$manager->getEntryById($this->entry_id, $this->category_id);
		$row = $this->stripVars($row);
		if(!$row) { $a = ''; return $a; }
	
		
		$this->meta_robots = false;
		$this->meta_title = $this->form_title;
		$this->parse_form = false;

		$title = $this->getSubstring($row['title'], 50, '...');
		
		$entry_id = $this->controller->getEntryLinkParams($row['id'], $row['title'], $row['url_title']);
		$link = $this->getLink('topic', false, $entry_id);
		$this->nav_title = array($link => $title, $this->form_title);
		
		
		$data = &$this->getForm($manager, $this->meta_title, $row);
		
		return $data;		
	}
}
?>