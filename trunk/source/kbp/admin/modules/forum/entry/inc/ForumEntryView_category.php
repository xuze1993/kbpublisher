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

require_once 'core/app/AppAjax.php';
require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryView_category.php';


class ForumEntryView_category extends KBEntryView_category 
{
    
    var $template = 'form_category.html';
 
	var $module = 'forum';
	var $category_page = 'forum_category';
    
    var $load_own_ini = true;

}
?>