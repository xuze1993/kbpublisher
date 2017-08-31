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

require_once APP_MODULE_DIR . 'tool/trigger/inc/TriggerEntryModel.php';


class WorkflowEntryModel extends TriggerEntryModel
{

    var $trigger_type = 4;

    var $entry_types = array(
        'wf_article' => 1,
        'wf_file'    => 2
    );
    
    static $user_search_str = array(
        'cond'   => 's:6:"author";s:4:"rule";a:2:{i:0;s:2:"is";i:1;s:%s:"%s";',
        'action' => 's:4:"rule";a:1:{i:0;s:%s:"%s";}'
    );
    
    
    // DELETE RELATED // ---------------------
    
    
    
    // DUMP // ----------------------------   
    
    
    function getDefaultSql() {
        
        require_once 'eleontev/Util/FileUtil.php';
        
        $this->setSqlParams('AND trigger_key != ""');
        $sql = $this->getRecordsSql();
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $rows = $result->GetArray();
        
        $trigger_sql = "(%s,1,%s,0,'%s','','',2,'%s','%s','',%s,%s)";
        $email_actions = array('email', 'email_group', 'email_user_grouped');
        
        $trigger_types = array(
            4 => 'workflow'
        );
        
        $lines = array(4 => array());
        foreach ($rows as $row) {
        
            $action = unserialize($row['action']);
            if (in_array($action[1]['item'], $email_actions)) {
                unset($action[1]['rule'][1]);
                unset($action[1]['rule'][2]);
            }
            
            // cutting step titles
            foreach (array_keys($action) as $k) {
                $action[$k]['title'] = '';
            }
            
            $lines[$row['trigger_type']][] = sprintf(
                $trigger_sql,
                'NULL',
                $row['trigger_type'],
                $row['trigger_key'],
                $row['cond'],
                serialize($action),
                $row['sort_order'],
                $row['active']);
        }
        
        $default_sql = "INSERT INTO {prefix}trigger VALUES\n%s;";
        
        $data = array();
        foreach ($trigger_types as $type_id => $name) {
            if (!empty($lines[$type_id])) {
                $data[$type_id] = sprintf($default_sql, implode($lines[$type_id], ",\n"));
            }
        }
        
        return $data;
    }
    
    
    function isWorkflowInUse($record_id = false) {
        $entry_type = $this->entry_type;
        
        $sql = "SELECT COUNT(ed.id) AS num 
        FROM ({$this->tbl->entry_draft} ed,
            {$this->tbl->entry_draft_workflow} w) 
        WHERE ed.entry_type = '{$entry_type}' 
            AND ed.id = w.draft_id";
        
        if ($record_id) {
            $sql .= sprintf(' AND w.workflow_id = %d', $record_id);
        }
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');        
    }
    
    
    function getCurrentStepNumbers($record_id) {
        $entry_type = $this->entry_type;
        
        $sql = "SELECT dw.draft_id, step_num
        	FROM ({$this->tbl->entry_draft} ed,
                 {$this->tbl->entry_draft_workflow} dw
                 INNER JOIN (
                       SELECT MAX(dw1.id) as id, dw1.draft_id FROM {$this->tbl->entry_draft_workflow} dw1
                       GROUP BY dw1.draft_id
                 ) dw2 ON dw.id = dw2.id
            )
            
            WHERE ed.entry_type = '{$entry_type}'
                AND ed.id = dw.draft_id
                AND dw.workflow_id = $record_id
                AND step_num != 0";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();        
    }
}
?>