<?php

require_once 'cron_common.php';

$cron = new Cron('weekly');
$cron->add('scripts/push_mail.php', 'periodicMail', 'weekly');
$cron->add('scripts/push_mail.php', 'freshDbMail', array(1, 15));    // sent
$cron->add('scripts/push_mail.php', 'freshDbMail', array('0,2', 30));    // failed
$cron->add('scripts/db_tech_functions.php', 'freshCronLog', array(30, array('freq', 'hourly', 'daily')));
$cron->add('scripts/db_tech_functions.php', 'freshCronLog', array(90, array('weekly', 'monthly')));
$cron->add('scripts/db_tech_functions.php', 'freshLoginLog', 90);
$cron->add('scripts/db_tech_functions.php', 'freshSearchLog', 90);
$cron->add('scripts/db_tech_functions.php', 'freshSphinxLog', 90);
$cron->add('scripts/db_tech_functions.php', 'freshUserTemp');
$cron->add('scripts/maintain_entry.php', 'deleteHistoryEntryNoArticle');
$cron->add('scripts/maintain_entry.php', 'deleteDraftNoEntry');
$cron->add('scripts/maintain_entry.php', 'deleteWorkflowHistoryNoEntry');
$cron->add('scripts/maintain_entry.php', 'deleteFeaturedNoEntry');
$cron->add('scripts/consistency.php', 'inheritCategoryNotActiveStatus');
$cron->add('scripts/subscription.php', 'normalizeCategorySubscription');
$cron->add('scripts/kbp_tech_functions.php', 'cleanCacheDirectory');
$cron->add('scripts/kbp_tech_functions.php', 'setupTest', array(array('daily', 'monthly'), 1));
$cron->run();

?>