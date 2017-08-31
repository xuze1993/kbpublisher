<?php

function processScheduledRecords() {
    
    include_once 'inc/ScheduledEntryModel.php';    
    
    $exitcode = 1; // well

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new ScheduledEntryModel();
    $sender = new AppMailSender();

    $processed = 0;
    $result =& $model->getScheduledRecordsResult();    
    if ($result) {
        while ($row = $result->FetchRow()) {
            
            if ($model->updateScheduledEntry($row)) {
                $ret = $model->sendScheduledEntryNotification($sender, $row);
                    
                if (!$ret) {
                    $exitcode = 0;
                    $cron->logCritical('Cannot add scheduled notification into pool: %s.', print_r($row, 1));
                
                } elseif($ret === 'no_user_to_send') {
                    $cron->logNotify('No user to send scheduled notification, skip adding into pool: %s.', print_r($row, 1));
                }
                
                // go to proceess, notification is not important thing here
                if ($model->removeScheduledRecord($row)) {
                    $model->updateNextScheduledRecord($row);
                    $processed += 1;
                } else {
                    $exitcode = 0;
                }
                
            } else {
                $exitcode = 0;
            }
        }
    
    } else {
        $cron->logNotify('Cannot get entries.');
        $exitcode = 0;
    }

    $cron->logNotify('(%d) entries processed.', $processed);

    return $exitcode;
}
?>