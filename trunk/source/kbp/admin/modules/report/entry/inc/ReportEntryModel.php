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


class ReportEntryModel extends AppModel
{

    var $tables = array('table' => 'report_entry', 'kb_entry', 'file_entry', 'news');
    
    var $having_params = '';
    
    var $report_type = array(
        1 => array(
            'key' => 'article_hit',
            'table' => 'kb_entry'),
        2 => array(
            'key' => 'file_hit',
            'table' => 'file_entry'),
        11 => array(
            'key' => 'news_hit',
            'table' => 'news'));
    
    var $report_period = array(
        'all_period',
        'previous_day',
        'this_week',
        'previous_week',
        'this_month',
        'previous_month',
        'this_year',
        'previous_year',
        'custom_period');
        
    var $report_range  = array(
        'daily' => 'date_day',
        'weekly' => 'date_week',
        'monthly' => 'date_month',
        'yearly' => 'date_year');
    
    
    function getRecordsSql() {
        $sql = "SELECT re.entry_id, SUM(re.value_int) as 'value'
                FROM
                    ({$this->tbl->table} re
                    {$this->sql_params_from})
                WHERE {$this->sql_params}
                GROUP BY re.entry_id
                HAVING 1
                    {$this->having_params}
                {$this->sql_params_order}";
        return $sql;
    }
    
    
    function getCountRecordsSql() {
        $sql = "SELECT count(*) FROM (
            SELECT SUM(re.value_int) as 'value'
            FROM ({$this->tbl->table} re
                {$this->sql_params_from})
            WHERE {$this->sql_params}
            GROUP BY re.entry_id
            HAVING 1
                {$this->having_params}) c";
 
        return $sql;        
    }
    
    
    function getTotalHits() {
        $sql = "SELECT SUM(re.value_int) as total
                FROM
                    ({$this->tbl->table} re
                    {$this->sql_params_from})
                WHERE {$this->sql_params}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('total');
    }
    
    
    function getTitlesByIds($report_type, $ids, $field = 'title') {
        $table = $this->report_type[$report_type]['table'];
        $sql = "SELECT id, {$field} FROM {$this->tbl->$table} WHERE id IN ({$ids})";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function getReportedEntryIds($report_type, $ids) {
        $table = $this->report_type[$report_type]['table'];
        $sql = "SELECT DISTINCT entry_id
                FROM {$this->tbl->table}
                WHERE entry_id IN ({$ids})
                    AND report_id = '{$report_type}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $rows = $result->GetArray();
        
        $data = array();
        foreach ($rows as $row) {
            $data[] = $row['entry_id']; 
        }
        
        return $data;
    }
    
    
    function getEarliestReportDate() {
        $sql = "SELECT UNIX_TIMESTAMP(MIN(date_day)) as num FROM {$this->tbl->table}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');
    }
    
    
    function getReportTypeSelectRange($msg) {
        $data = array();
        foreach ($this->report_type as $k => $v) {
            $data[$k] = $msg['report_type'][$v['key']];            
        }
        
        return $data;
    }
    
    
    function getRangeSelectRange($msg) {
        $data = array();
        foreach($this->report_range as $k => $v) {
            $data[$k] = $msg[$k];
        }
        
        return $data;
    }
    
    
    function getReportPeriodSelectRange($msg) {
        $data = array();
        foreach ($this->report_period as $k) {
            $data[$k] = $msg['period_range'][$k];            
        }
        
        return $data;
    }
    
    
    // entries
    function getEntryRecords($force_index, $week_start, $limit = false, $offset = false) {
        $sql = "SELECT 
            *,
            SUM(value_int) as 'value'
        FROM {$this->tbl->table} FORCE INDEX ({$force_index})
        WHERE 1
            AND {$this->sql_params}
        GROUP BY {$this->sql_params_group}";
        
        // HAVING value > 1000
        // ORDER BY {$this->sql_params_group}
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        // echo $this->getExplainQuery($this->db, $result->sql);
        
        $data = array();
        while($row = $result->FetchRow()) {
            $data[$row[$force_index]][$row['entry_id']] = $row['value'];
        }
        
        //ksort($data);
        return $data;
    }
    
    
    function getRecordsTotal() {
        $sql = "SELECT 
            report_id,
            SUM(value_int) as 'value'
        FROM {$this->tbl->table}
        WHERE 1
            AND {$this->sql_params}
        GROUP BY report_id";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        //echo $this->getExplainQuery($this->db, $result->sql);
        
        return $result->GetAssoc();        
    }
      
}
?>