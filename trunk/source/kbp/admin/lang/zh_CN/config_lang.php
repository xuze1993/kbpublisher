<?php
$conf['lang'] = array();
$conf['lang']['name']         = 'Chinese';
$conf['lang']['meta_content'] = 'zh_CN';
$conf['lang']['meta_charset'] = 'UTF-8';
$conf['lang']['db_charset']   = 'UTF-8';


// This is the default date format.
// It will be used everywhere unless you specify a
// custom value in custom function/class.
// For possible formats see http://php.net/strftime
$conf['lang']['date_format']   = ' %Y年 %b %d日';
$conf['lang']['time_format']   = '%H:%M';
$conf['lang']['sec_format']    = '%H:%M:%S';

// Week start  0 - sunday, 1 - monday 
$conf['lang']['week_start'] = 0;

// Fields delimeter for excel files, used in exporting to excel
$conf['lang']['excel_delim'] = ","; 


// TITLE URL REWRITE //
// This will be used to replace non-latin characters to their latin equivalent
// Example: $conf['lang']['replace'] = array('character_to_find' => 'character_to_replace', ...);
$conf['lang']['replace'] = array();


// LOCALE //
// Use this to set different locality names
// Example for German:     setlocale(LC_ALL, 'de_DE@euro', 'de_DE', 'de', 'ge');
// Example for Portuguese: setlocale(LC_ALL, 'pt_BR', 'pt_BR.iso-8859-1', 'pt_BR.utf-8', 'portuguese');
// to find available locales on unix use locale -a command
// zh_CN, zh_CN.eucCN, zh_CN.GB18030, zh_CN.GB2312, zh_CN.GBK, zh_CN.UTF-8
// zh_HK, zh_HK.Big5HKSCS, zh_HK.UTF-8, 
// zh_TW, zh_TW.Big5, zh_TW.UTF-8
setlocale(LC_ALL, 'zh_CN.utf8', 'zh_CN.utf-8', 'zh_CN.UTF-8', 'zh_CN');

// another variant - reset to the server-setting
//setlocale(LC_ALL, NULL);

// MBSTRING //
// set mbstring if charset utf8
$conf['lang']['mbstring'] = true;
// ini_set('mbstring.http_input', 'pass');
// ini_set('mbstring.http_output', 'pass');
// ini_set('mbstring.encoding_translation', 0);
ini_set('default_charset', 'UTF-8');
ini_set('mbstring.internal_encoding', 'UTF-8');
ini_set('mbstring.language', 'Chinese');
?>