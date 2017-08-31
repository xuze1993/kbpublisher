<?php

$entries_num = 5;
$days_num = 5;
$current_timestamp = time();

$_sql = "INSERT INTO `s_user_activity` VALUES (%d,%d,%d,%s,'%s','%s','%s',null)";

set_time_limit(0);

for($i = 0; $i < $days_num; $i ++) { // day
    
    foreach (UserActivityLog::$entry as $entry_type_key => $entry_type_id) { // entry type
    
        foreach ($manager->getUserActionSelectRange($entry_type_key) as $action_type_id => $action_key) { // action
        
            for($j = 1; $j <= $entries_num; $j ++) { // entry
                $entry_id = $j;
                $users_num = mt_rand(1, 30);
                
                for($k = 0; $k < $users_num; $k ++) { // user
                    $user_id = mt_rand(1, 20);
                    
                    $hour = mt_rand(1, 23);
                    // $timestamp = $current_timestamp - (3600 * $hour) - ($i * 86400);
                    $timestamp = $current_timestamp - (86400*31*$i);
                    $date = date('Y-m-d H:i:s', $timestamp);
                    $date_month = date('Ym', $timestamp);
                    
                    $sql = sprintf($_sql, $entry_type_id, $action_type_id, $user_id, 'INET_ATON("127.0.0.1")', $entry_id, $date, $date_month);
                    $manager->db->Execute($sql) or die(db_error($sql));
                }
            }
        
        }
        
    }    
    
}

?>