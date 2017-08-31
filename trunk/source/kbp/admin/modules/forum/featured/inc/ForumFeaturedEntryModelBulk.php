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


class ForumFeaturedEntryModelBulk extends BulkModel
{

    var $actions = array('sort_order', 'remove');


    function setActionsAllowed($manager, $priv, $allowed = array()) {

        $actions = $this->getActionAllowedCommon($manager, $priv, $allowed);

        if(!$manager->show_bulk_sort) {
            unset($actions['sort_order']);
            unset($actions['remove_from']);
        }


        $this->actions_allowed = array_keys($actions);
        return $this->actions_allowed;
    }


    function setSortOrder($val_sort, $ids) {

        foreach($ids as $id) {
            $sort = $val_sort[$id];

            $sql = "UPDATE {$this->model->tbl->table}
                SET sort_order = '{$sort}'
                WHERE id = '{$id}'";
                $this->model->db->Execute($sql) or die(db_error($sql));
        }
    }
    
}
?>