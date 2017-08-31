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

require_once APP_MODULE_DIR . 'user/subscription/inc/SubscriptionView_form_articles_cat.php';


class SubscriptionView_form_forums extends SubscriptionView_form_articles_cat
{

    var $tmpl = 'form.html';
    var $admin_view = true;


    function getCategoryManager($manager) {
        return $manager->getForumManager();
    }

    function getClientActionLink() {
        return APP_CLIENT_PATH . 'index.php?View=member_subsc&action=insert&type=14';
    }

    function getClientCancelLink() {
        return APP_CLIENT_PATH . 'index.php?View=member_subsc&type=14';
    }
}
?>