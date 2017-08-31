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

require_once APP_MODULE_DIR . 'user/subscription/inc/SubscriptionView_list_articles_cat.php';


class SubscriptionView_list_forums extends SubscriptionView_list_articles_cat
{

    var $tmpl = 'articles_list_cat.html';
    var $table_name = 'forums';
    var $entry_type = 14;
    var $client_view_str = 'forums';


    function getCategoryManager($manager) {
        return $manager->getForumManager();
    }
}
?>