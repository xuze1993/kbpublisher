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

class ForumFeaturedEntry extends AppObj
{
    
	var $properties = array('id'		 	=> NULL,
                            'entry_id'      => 0,
							'message_id' 	=> 0,
							'date_to'		=> NULL,
							'sort_order' 	=> 0,
							'active'		=> 1
							);
    
    
    var $hidden = array('id', 'entry_id', 'sort_order');
    var $title = '';
    
    
    function setTitle($val) {
        $this->title = $val;
    }
    
    function getTitle() {
        return $this->title;
    }
    
}
?>