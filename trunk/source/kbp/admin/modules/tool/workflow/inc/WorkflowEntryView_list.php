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

require_once APP_MODULE_DIR . 'tool/trigger/inc/TriggerEntryView_list.php';


class WorkflowEntryView_list extends TriggerEntryView_list
{
    
    function getFilterSql(&$manager) {
        
        // filter
        $arr = array();
        $arr_select = array();
        @$values = $_GET['filter'];

        
        @$v = $values['approver_id'];
        if ($v) {
            $user_id = (int) $v;
            
            $serialized_str = WorkflowEntryModel::$user_search_str['cond'];
            $str = sprintf($serialized_str, strlen($user_id), $user_id);
            $sql = sprintf("AND (cond LIKE '%%%s%%'", $str);

            $serialized_str = WorkflowEntryModel::$user_search_str['action'];
            $str = sprintf($serialized_str, strlen($user_id), $user_id);
            $sql .= sprintf(" OR action LIKE '%%%s%%')", $str);
                        
            $arr[] = $sql;
        }
        
        
        $arr['where'] = implode(" \n", $arr);
        $arr['select'] = implode(" \n", $arr_select);
        
        return $arr;
    }
}
?>