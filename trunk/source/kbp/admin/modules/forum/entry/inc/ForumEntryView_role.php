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


class ForumEntryView_role extends AppView 
{
    
    var $template = 'form_role.html';
 

    function execute(&$obj, &$manager) {
		$this->controller->loadClass('UserView_role', 'user/user');

		$view = new UserView_role();
		return $view->execute($obj, $manager);
    }
}
?>