<?php
require_once 'inc/ParseSqlFile.php';
require_once '../admin/config.inc.php';
require_once '../admin/config_more.inc.php';

require_once 'eleontev/Util/FileUtil.php';


/*
Do not forget letter templares default
/admin/modules/setting/letter_template/PageController.php

To genereate dump.sql
use phpmysdmin export with options
Structure
    Add DROP TABLE
    Add IF NOT EXISTS (less efficient as indexes will be generated during table creation) 5.7
    Enclose table and field names with backquotes

Data
    Complete inserts
    Extended inserts
    Maximal length of created query: = empty
    
=======================================================================
DO NOT FORGET DEFAULTS FOR 
    automations, workflows, letter templates!!!    
=======================================================================
*/


$prefix = array('s_'=> 'kbp_');


// -- INSTALL -----------------------

$install = FileUtil::read('../../_sql/dump.sql');
$install = ParseSqlFile::parseDumpString($install);


$file = 'db/install.sql';

// empty means all
$tables['skip'] = array(
    'trigger_to_run', // for triggers, not using for now
    'kb_attachment',
    'entry_comment',
    'report_entry_test'
);

$tables['parse'] = array();

$tables['data'] = array(
    'priv_module', 'priv_name', 'priv_rule',
    'letter_template', 'article_template',
    'user_role',
    'list_value', 'list', 'list_country',
    'setting'
);

$sql = array();
$sql['before'] = array("SET sql_mode = '';\n");
$sql['tables'] = ParseSqlFile::parseSqlArray($install, $prefix, $tables);
$sql = array_merge($sql['before'], $sql['tables']);

$ret = FileUtil::write($file, implode("--\n", $sql));
if(!$ret) {
    echo "Unable write to file $file \n";
}



// -- UPGRADE ---------------------

$upgrade = FileUtil::read('../../_sql/db_upgrade_5.5.1_to_6.0.1.sql');
$upgrade = ParseSqlFile::parseUpgradeString($upgrade);

$file = 'db/upgrade_5.5.1_to_6.0.1.sql';

// empty means all
$tables['skip'] = array(

);

$tables['parse'] = array(
    'priv_module', 'setting',
    'user_activity', 'log_sphinx', 'user_auth_token',
    'entry_draft_workflow_to_assignee', 'entry_draft_to_category'
);

$tables['data'] = array(
    'priv_module', 'setting'
);

$sql = array();
$sql['before'] = array("SET sql_mode = '';\n\n");
$sql['tables'] = ParseSqlFile::parseSqlArray($install, $prefix, $tables, false);
$sql['command'] = ParseSqlFile::parseSqlArray($upgrade, $prefix, array(), false);
$sql = array_merge($sql['before'], $sql['tables'], $sql['command']);

$ret = FileUtil::write($file, implode("--\n", $sql));
if(!$ret) {
    echo "Unable write to file $file \n";
}




// -- UPGRADE 2 ---------------------

$upgrade = FileUtil::read('../../_sql/db_upgrade_6.0_to_6.0.1_update.sql');
$upgrade = ParseSqlFile::parseUpgradeString($upgrade);

$file = 'db/upgrade_6.0_to_6.0.1.sql';

// empty means all
$tables['skip'] = array();
$tables['parse'] = array('fake_table'); // if not any table required
$tables['data'] = array('fake_table');  // if not any table required

$sql = array();
$sql['before'] = array("SET sql_mode = '';\n\n");
$sql['tables'] = ParseSqlFile::parseSqlArray($install, $prefix, $tables, false);
$sql['command'] = ParseSqlFile::parseSqlArray($upgrade, $prefix, array(), array(), array(), false);
$sql = array_merge($sql['tables'], $sql['command']);

$ret = FileUtil::write($file, implode("--\n", $sql));
if(!$ret) {
    echo "Unable write to file $file \n";
}


// -- DEFAULT SQL -----------------------

// $default = FileUtil::read('../../_sql/default.sql');
// $default = ParseSqlFile::parseDumpString($default);
// echo '<pre>', print_r($install, 1), '</pre>';
// $file = 'db/default.sql';

// $tables['skip'] = array();
// $tables['parse'] = array('trigger');
// $tables['data'] = array('trigger');
// $tables['only_data'] = array('trigger');

// $sql = ParseSqlFile::parseSqlArray($install, $prefix, $tables);
// $sql = ParseSqlFile::parseSqlArray($default, $prefix);
// echo '<pre>', print_r($sql, 1), '</pre>';


// $ret = FileUtil::write($file, implode("--\n", $sql));
// if(!$ret) {
//     echo "Unable write to file $file \n";
// }

?>