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

require_once 'core/app/BulkModel.php';
require_once APP_MODULE_DIR . 'knowledgebase/category/inc/KBCategoryModelBulk.php';


class ForumCategoryModelBulk extends KBCategoryModelBulk
{

    var $actions = array('private', 'public',
                         'forum_admin',
                        //'delete'
                         'status', 'sort_order');

    var $apply_child = true;


    function setActionsAllowed($manager, $priv, $allowed = array()) {

        $actions = $this->getActionAllowedCommon($manager, $priv, $allowed);

        if(!$manager->show_bulk_sort) {
            unset($actions['sort_order']);
        }


        $this->actions_allowed = array_keys($actions);
        return $this->actions_allowed;
    }

}
?>