<?php
$conf['lang'] = array();
$conf['lang']['name']         = 'Portuguese (Brazil)';
$conf['lang']['meta_content'] = 'pt';
$conf['lang']['meta_charset'] = 'UTF-8';
$conf['lang']['db_charset']   = 'UTF-8';
$conf['lang']['iso_charset']  = 'ISO-8859-1';


// This is the default date format.
// It will be used everywhere unless you specify a
// custom value in custom function/class.
// For possible formats see http://php.net/strftime
$conf['lang']['date_format']   = '%d %b, %Y';
$conf['lang']['time_format']   = '%H:%M';
$conf['lang']['sec_format']    = '%H:%M:%S';


// Week start  0 - sunday, 1 - monday 
$conf['lang']['week_start'] = 1;

// Fields delimeter for excel files, used in exporting to excel
$conf['lang']['excel_delim'] = ","; 


// TITLE URL REWRITE //
// This will be used to replace non-latin characters to their latin equivalent
// Example: $conf['lang']['replace'] = array('character_to_find' => 'character_to_replace', ...);
$conf['lang']['replace'] = array(
	'á'=>'a', 'ã'=>'a', 'â'=>'a', 'é'=>'e', 'ê'=>'e', 'í'=>'i', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ú'=>'u', 'ü'=>'u', 'ñ'=>'n', 
	'Á'=>'A', 'Ã'=>'A', 'Â'=>'A', 'É'=>'E', 'Ê'=>'E', 'Í'=>'I', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ú'=>'U', 'Ü'=>'U', 'Ñ'=>'N', 
	'¿'=>'' ,'¡'=>''
	);
	

// LOCALE //
// Use this to set different locality names
// Example for German: setlocale(LC_ALL, 'de_DE@euro', 'de_DE', 'de', 'ge');
// to find available locales on unix use locale -a command
setlocale(LC_ALL, 'pt_BR.utf8', 'pt_BR.utf-8', 'pt_BR');

// another variant - reset to the server-setting
//setlocale(LC_ALL, NULL);
?>