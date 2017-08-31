<?php
$conf['lang'] = array();
$conf['lang']['name']         = 'Japanese';
$conf['lang']['meta_content'] = 'jp';
$conf['lang']['meta_charset'] = 'UTF-8';
$conf['lang']['db_charset']   = 'UTF-8';
$conf['lang']['iso_charset']  = 'shift-jis';


// This is the default date format.
// It will be used everywhere unless you specify a
// custom value in custom function/class.
// For possible formats see http://php.net/strftime
$conf['lang']['date_format']   = '%Y年%m月%d日';
$conf['lang']['time_format']   = '%H時%M分';
$conf['lang']['sec_format']    = '%H時%M分%S秒';

// Week start  0 - sunday, 1 - monday 
$conf['lang']['week_start'] = 1;

// Fields delimeter for excel files, used in exporting to excel
$conf['lang']['excel_delim'] = ","; 


// TITLE URL REWRITE //
// This will be used to replace non-latin characters to their latin equivalent
// Example: $conf['lang']['replace'] = array('character_to_find' => 'character_to_replace', ...);
$conf['lang']['replace'] = array(

);

// LOCALE //
// Use this to set different locality names
// Example for German:     setlocale(LC_ALL, 'de_DE@euro', 'de_DE', 'de', 'ge');
// Example for Portuguese: setlocale(LC_ALL, 'pt_BR', 'pt_BR.iso-8859-1', 'pt_BR.utf-8', 'portuguese');
// to find available locales on unix use locale -a command
setlocale(LC_ALL, 'ja_JP.utf8', 'ja_JP.utf-8', 'ja_JP.eucjp', 'ja_JP');

// another variant - reset to the server-setting
//setlocale(LC_ALL, NULL);


// MBSTRING //
// set mbstring if charset utf8
$conf['lang']['mbstring'] = true;
// ini_set('mbstring.http_input', 'pass');
// ini_set('mbstring.http_output', 'pass');
// ini_set('mbstring.encoding_translation', 0);
ini_set('default_charset', 'UTF-8');
ini_set('mbstring.language', 'Japanese');

if(version_compare(phpversion(), '5.6', '<' )) {
    ini_set('mbstring.internal_encoding', 'UTF-8');
}
?>