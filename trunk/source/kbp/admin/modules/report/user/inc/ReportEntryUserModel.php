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

require_once APP_MODULE_DIR . 'user/user/inc/UserActivityLog.php';


class ReportEntryUserModel extends AppModel
{

    var $tables = array(
        'table' => 'user_activity',
        'kb_entry', 'file_entry',
        'news', 'user', 'forum_entry',
        'entry_draft');
    
    
    var $action_order = array(
        'view',
        'create',
        'update',
        'bulk_update',
        'delete',
        'trash',
        'login',
        'publish'
    );
    
    var $action = array(
        'create' => 2,
        'update' => 3,
        'delete' => 4,
        'bulk_update' => 8
    );
    
    var $extra_action = array(
        'article' => array(
            'view'   => 1,
            'trash'  => 9
        ),
        'file' => array(
            'view'   => 1
        ),
        'user' => array(
            'login' => 5
        ),
        'news' => array(
            'view'   => 1
        ),
        'forum_topic' => array(
            'view'   => 1
        ),
        'article_draft' => array(
            'publish' => 7
        ),
        'file_draft' => array(
            'publish' => 7
        )
    );
    
    var $entry_type_to_table = array(
        1 => 'kb_entry',
        2 => 'file_entry',
        3 => 'user',
        4 => 'news',
        5 => 'forum_entry',
        6 => 'entry_draft',
        7 => 'entry_draft'
    );
    
    var $extra_title_field = array(
        2 => 'filename',
        3 => 'username'
    );
    
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
    
    
    function getRecordsSql() {
        $sql = "SELECT *, INET_NTOA(user_ip) AS user_ip_formatted
            FROM {$this->tbl->table}
            WHERE {$this->sql_params}
            {$this->sql_params_order}";
            
        // echo '<pre>', print_r($sql, 1), '</pre>';
        return $sql;
    }
    
    
    function checkAllTimeActivities($user_id) {
        $sql = "SELECT 1 FROM {$this->tbl->table} WHERE user_id = %d";
        $sql = sprintf($sql, $user_id);
        $result = $this->db->SelectLimit($sql, 1, 0) or die(db_error($sql));
        return (bool) ($result->Fields(1));
    }
    
    
    function getReportPeriodSelectRange($msg) {
        $data = array();
        foreach ($this->report_period as $k) {
            $data[$k] = $msg['period_range'][$k];            
        }
        
        return $data;
    }
    
    
    function getUserActionSelectRange($entry_type = false) {

        $range = AppMsg::getMsg('ranges_msg.ini', false, 'user_action');

        $data = array();
        foreach ($this->action as $k => $v) {
            $data[$k] = $v;
        }
        
        if ($entry_type) {
            if ($entry_type == 'all') {
                foreach ($this->extra_action as $k => $v) {
                    foreach ($v as $k1 => $v1) {
                        $data[$k1] = $v1;
                    }
                }
                
            } else {
                $entry_type_key = array_search($entry_type, UserActivityLog::$entry);
                if (!empty($this->extra_action[$entry_type_key])) { // extra
                    foreach ($this->extra_action[$entry_type_key] as $k => $v) {
                        $data[$k] = $v;
                    }
                }
            }
        }
        
        $data2 = array();
        foreach($this->action_order as $v) {
            if(isset($data[$v])) {
                $data2[$data[$v]] = $range[$v];
            }
        }
        
        // echo '<pre>', print_r($data2, 1), '</pre>';
        return $data2;
    }
    
    
    function getEntrySelectRange() {
        $msg = AppMsg::getMsg('ranges_msg.ini', false, 'record_type');
		
		$range = UserActivityLog::$entry;
    	if(!BaseModel::isModule('forum')) {
    		unset($range['forum_topic']);
    	}
		
		$data = array();
        foreach ($range as $k => $v) {
            $data[$v] = $msg[$k];
        }
        
        return $data;
    }
    
    
    function getUserByIds($ids) {
        $sql = "SELECT * FROM {$this->tbl->user} WHERE id IN ({$ids})";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function getUnrelatedUsers($limit = -1, $offset = -1) {
        $sql = "SELECT *
            FROM {$this->tbl->user} u
            
            LEFT JOIN {$this->tbl->table} ua
            ON ua.user_id = u.id
            AND {$this->sql_params}
            
            WHERE ua.user_id IS NULL
            {$this->sql_params_order}";
        
        if($limit == -1) {
            $result = $this->db->Execute($sql) or die(db_error($sql));
            
        } else {
            $result = $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));
        }
        
        return $result->GetArray();
    }
    
    
    function getEntryTitles($data) {
        $_data = array();
        foreach ($data as $entry_type_id => $ids) {
            $sql = "SELECT id, %s FROM %s WHERE id IN (%s)";
            $table = $this->entry_type_to_table[$entry_type_id];
            $title_field = (empty($this->extra_title_field[$entry_type_id])) ? 'title' : $this->extra_title_field[$entry_type_id];
            $sql = sprintf($sql, $title_field, $this->tbl->$table, implode(',', $ids));
            $result = $this->db->Execute($sql) or die(db_error($sql));
            $_data[$entry_type_id] = $result->GetAssoc();
        }
        
        return $_data;
    }
      
}
?>