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


class ReportUsageModel extends BaseModel
{
    
    var $tbl_pref_custom = 'report_';
    var $tables = array('table' => 'summary', 'summary');
    var $custom_tables =  array();
    
    var $sql_params_group = 'report_id';
    
    var $report_type = array(
        1 => 'article_hit',
        2 => 'file_hit',
        11 => 'news_hit',
        3 => 'login',
        4 => 'registration',
        5 => 'comment',
        6 => 'feedback',
        7 => 'article_new',
        8 => 'file_new',
        9 => 'article_updated',
        10 => 'file_updated');
    
    
    // key => table filed to group by
    var $report_range  = array(
        'daily' => 'date_day',
        //'weekly' => 'date_week',
        'monthly' => 'date_month',
        'yearly' => 'date_year');
        
    
    
    function getRecords($force_index, $week_start, $limit = false, $offset = false) {
        $sql = "SELECT 
            *,
            WEEK(date_day, {$week_start}) AS date_week,
            SUM(value_int) as 'value'
        FROM {$this->tbl->summary} FORCE INDEX ({$force_index})
        WHERE 1
            AND {$this->sql_params}
        GROUP BY {$this->sql_params_group}";
        
        // HAVING value > 1000
        // ORDER BY {$this->sql_params_group}        
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        // echo $this->getExplainQuery($this->db, $result->sql);
        
        $data = array();
        while($row = $result->FetchRow()) {
            $data[$row[$force_index]][$row['report_id']] = $row['value'];
        }
        
        //ksort($data);
        return $data;
    }
    
    
    function getRecordsTotal() {
        $sql = "SELECT 
            report_id,
            SUM(value_int) as 'value'
        FROM {$this->tbl->summary}
        WHERE 1
            AND {$this->sql_params}
        GROUP BY report_id";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        //echo $this->getExplainQuery($this->db, $result->sql);
        
        return $result->GetAssoc();        
    }
    
    
    function getReportTypeSelectRange($msg) {
        $data = array();
        foreach ($this->report_type as $k => $v) {
            $data[$k] = $msg['report_type'][$v];            
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
    
    
    function getEarliestReportDate() {
        $sql = "SELECT UNIX_TIMESTAMP(MIN(date_day)) as num FROM {$this->tbl->table}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');
    }


    function checkPriv(&$priv, $action) {
        $priv->setCustomAction('file', 'select');
        $priv->setCustomAction('chart', 'select'); 
        $priv->check($action);
    }
}
?>