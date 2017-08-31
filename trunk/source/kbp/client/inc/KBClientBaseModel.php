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

require_once 'eleontev/Util/TreeHelper.php';


class KBClientBaseModel extends BaseModel
{
    
    // entry status
    var $entry_published_status = array();
    
    
    static function getSettings($module_id, $setting_key = false) {
        $s = new SettingModel();
        return $s->getSettings($module_id, $setting_key);
    }
    
    
    function getSetting($setting_key) {
        return @$this->setting[$setting_key];
    }
    
    
    function getEntryPublishedStatusRaw($list_id) {
        
        $sql = "
        SELECT 
            l.list_value as lk,
            l.list_value as lk1
        FROM 
            {$this->tbl->list_value} l
        WHERE 1
            AND l.list_id = %d
            AND l.custom_3 = 1";
        
        $sql = sprintf($sql, $list_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function getEntryPublishedStatus($list_id) {
        $status = $this->getEntryPublishedStatusRaw($list_id);
        return ($status) ? implode(',', $status) : '987654321';
    }    
    
    
    function setEntryPublishedStatus() {
        $this->entry_published_status = $this->getEntryPublishedStatus($this->entry_list_id);
    }    
}
?>