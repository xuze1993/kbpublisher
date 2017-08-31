<?php

class ScheduledEntryModel extends AppModel
{
    
    var $tbl_pref_custom = '';
    var $tables = array('kb_entry', 'file_entry', 'news', 'log_login', 'entry_schedule');
    

    function &getScheduledRecordsResult() {
        $sql = "SELECT * FROM {$this->tbl->entry_schedule} 
            WHERE date_scheduled <= NOW() AND active = 1";
        // $sql = sprintf($sql, $this->getNow());
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        
        return $result;
    }
    
    
    /**
     * @param array $rec entry_schedule record
     */
    function removeScheduledRecord($rec) {
        $sql = "DELETE FROM {$this->tbl->entry_schedule}
            WHERE entry_id = %d AND entry_type = %d AND num = %d";
        $sql = sprintf($sql, $rec['entry_id'], $rec['entry_type'], $rec['num']);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        
        return $result;
    }
    
    
    /**
     * Update next record for this entry, set num to 1 
     * @param array $rec entry_schedule record
     */
    function updateNextScheduledRecord($rec) {
        $sql = "UPDATE {$this->tbl->entry_schedule} SET
            date_scheduled = date_scheduled, 
            num = 1
            WHERE entry_id = %d AND entry_type = %d AND num = %d";
        $sql = sprintf($sql, $rec['entry_id'], $rec['entry_type'], 2);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        
        return $result;
    }
    
    
    /**
     * @param array $rec entry_schedule record
     */
    function updateScheduledEntry($rec) {

        $et_tables = $this->record_type_to_table;
        
        if (!isset($et_tables[$rec['entry_type']])) {
            $str = "Unknown entry_type in scheduled record: %s";
            $str = sprintf($str, print_r($rec, 1));
            trigger_error($str);
            return false;
        }
        
        $entry_type = $et_tables[$rec['entry_type']];
        $table = $this->tbl->$entry_type;

        $sql = "UPDATE %s SET active = %d WHERE id = %d";
        $sql = sprintf($sql, $table, $rec['value'], $rec['entry_id']);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        
        return $result;
    }
    
    
    function sendScheduledEntryNotification(&$sender, $data) {
        
        if (!$data['notify']) {
            return true; // do not send notification
        }
        
        $vars = array(
            'entry_id' => $data['entry_id'],
            'entry_type' => $data['entry_type'],
            'note' => $data['note'],
            'status' => $data['value']);
        
        if(!$sender) {
            $sender = new AppMailSender();
        }
        
        return $sender->sendScheduledEntryNotification($vars);
    }
}
?>