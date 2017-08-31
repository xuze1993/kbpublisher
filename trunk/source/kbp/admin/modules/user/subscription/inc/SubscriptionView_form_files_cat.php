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


class SubscriptionView_form_files_cat extends SubscriptionView_form_articles_cat
{
        
    var $tmpl = 'form.html';
    var $admin_view = true;


    function getCategoryManager($manager) {
        return $manager->getFileManager();
    }
    
    function getClientActionLink() {
        return $_SERVER['PHP_SELF'] . '?View=member_subsc&action=insert&type=12';
    }
        
    function getClientCancelLink() {
        return $_SERVER['PHP_SELF'] . '?View=member_subsc&type=12';
    }    
}
?>