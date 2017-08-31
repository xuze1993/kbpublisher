<?php

/**
 * For development purposes only.
 * Do NOT add this to CRON.
 * Run manually.
 */


$_SERVER['HTTP_HOST']     = 'localhost';


require_once 'cron_common.php';

$cron = new Cron('_test_');

// $cron->add('scripts/push_mail.php', 'periodicMail', 'freq');
// $cron->add('scripts/push_mail.php', 'periodicMail', 'hourly');
// $cron->add('scripts/push_mail.php', 'periodicMail', 'daily');
// $cron->add('scripts/push_mail.php', 'periodicMail', 'weekly');
// $cron->add('scripts/push_mail.php', 'periodicMail', 'monthly');
// 
// $cron->add('scripts/push_mail.php', 'dbMail', 5); // 5 is frequency (every 5 minutes)
// $cron->add('scripts/push_mail.php', 'freshDbMail', array(1, 15));    // sent
// $cron->add('scripts/push_mail.php', 'freshDbMail', array('0,2', 30));    // failed
// 
// $cron->add('scripts/db_tech_functions.php', 'freshCronLog', array(30, array('freq', 'hourly', 'daily')));
// $cron->add('scripts/db_tech_functions.php', 'freshCronLog', array(90, array('weekly', 'monthly')));
// $cron->add('scripts/db_tech_functions.php', 'freshLoginLog', 90);
// $cron->add('scripts/db_tech_functions.php', 'freshUserTemp');
// $cron->add('scripts/db_tech_functions.php', 'optimizeTables', 10);
// $cron->add('scripts/db_tech_functions.php', 'repairTables', 1);
// 
// $cron->add('scripts/maintain_entry.php', 'unlockEntries', 24*60*60);
// $cron->add('scripts/maintain_entry.php', 'freshEntryAutosave', 60*60*48);
// $cron->add('scripts/maintain_entry.php', 'updateBodyIndex');
// $cron->add('scripts/maintain_entry.php', 'updateTagKeywords');
// $cron->add('scripts/maintain_entry.php', 'syncTagKeywords');
// $cron->add('scripts/maintain_entry.php', 'deleteHistoryEntryNoArticle');
// $cron->add('scripts/maintain_entry.php', 'deleteDraftNoEntry');
// $cron->add('scripts/maintain_entry.php', 'deleteWorkflowHistoryNoEntry');
// $cron->add('scripts/maintain_entry.php', 'deleteFeaturedNoEntry');
// 
// $cron->add('scripts/kbp_tech_functions.php', 'setupTest', array(array('freq', 'daily', 'weekly'), 1));
// $cron->add('scripts/kbp_tech_functions.php', 'setupValidate');
$cron->add('scripts/kbp_tech_functions.php', 'cleanCacheDirectory');
// 
// $cron->add('scripts/reports.php', 'updateReportEntry'); // should be added before syncHits
// $cron->add('scripts/reports.php', 'syncHits');
// $cron->add('scripts/reports.php', 'updateReportSummary');
// $cron->add('scripts/reports.php', 'updateSearchReport');
// 
// $cron->add('scripts/scheduled_entry.php', 'processScheduledRecords');
// 
// $cron->add('scripts/subscription.php', 'processNewsSubscription');
//$cron->add('scripts/subscription.php', 'processEntriesSubscription');
// $cron->add('scripts/subscription.php', 'processCommentSubscription');

// $cron->add('scripts/subscription.php', 'normalizeCategorySubscription');
// $cron->add('scripts/subscription.php', 'processTopicSubscription');
// $cron->add('scripts/subscription.php', 'processForumSubscription');
// 
// $cron->add('scripts/directory.php', 'spyDirectoryFiles');
// 
// $cron->add('scripts/consistency.php', 'inheritCategoryPrivateAttributes');
// $cron->add('scripts/consistency.php', 'inheritCategoryNotActiveStatus');

// $cron->add('scripts/indexes.php', 'indexRecords');
// $cron->add('scripts/indexes.php', 'deleteIndexesNoEntry');
// 
// $cron->add('scripts/automations.php', 'executeAutomations');
// $cron->add('scripts/automations.php', 'executeEmailAutomations');

// $cron->add('scripts/reports.php', 'syncUserActivityReport');
// $cron->add('scripts/reports.php', 'freshUserActivityReport');

// $cron->add('scripts/indexes.php', 'sphinxTasks');
// $cron->add('scripts/indexes.php', 'sphinxIndex');
// $cron->add('scripts/indexes.php', 'sphinxIndex', true);

$cron->run();
?>