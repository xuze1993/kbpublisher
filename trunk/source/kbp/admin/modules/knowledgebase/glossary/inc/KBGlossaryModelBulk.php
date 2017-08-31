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


class KBGlossaryModelBulk extends BulkModel
{

    var $actions = array('glossary_display', 'status', 'delete');
    

    function updateDisplay($val, $ids) {
        $ids = $this->model->idToString($ids);
        $sql = "UPDATE {$this->model->tbl->table} SET display_once = '{$val}' WHERE id IN ($ids)";
        $this->model->db->Execute($sql) or die(db_error($sql));        
    }

}
?>