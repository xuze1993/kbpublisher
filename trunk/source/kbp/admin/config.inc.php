<?php

/* GENERAL */
$conf['db_host']      = "localhost";
$conf['db_base']      = "kbpublisher";
$conf['db_user']      = "username";
$conf['db_pass']      = "password";
$conf['db_driver']    = "mysql";             // no other were tested 
$conf['tbl_pref']     = 'kbp_';
  
$conf['session_name'] = '9fceb95f8384ddf941c3d96b1a0fc014';        // session name
$conf['lang']         = 'en';              // see admin/lang/ directory for available languages
$conf['allow_setup']  = 1;                 // allow or not setup wizard, redirect to setup wizard if 1


/* PATHS */
// $_SERVER['DOCUMENT_ROOT'] = '/home/username/www';
// $_SERVER['HTTP_HOST']     = 'localhost';

// admin path
$conf['admin_home_dir']   = '/kb/admin/';         // path to admin dir, relative to DOCUMENT_ROOT

// client path
$conf['client_home_dir']  = '/kb/';               // path to kb dir, relative to DOCUMENT_ROOT

// other path
$conf['site_address']     = $_SERVER['HTTP_HOST'];
$conf['cache_dir']        = '/home/username/kb_cache/';        // full path to cache dir, it should be writeable


/* SECURITY */
$conf['auth_check_ip']    = 1;        // on every request IP will be checked with saved one on login
$conf['ssl_admin']        = 0;        // 0 = OFF, 1 = ON (default port 443) or use concrete port number
$conf['ssl_client']       = 0;        // 0 = OFF, 1 = ON (default port 443) or use concrete port number
$conf['ssl_client_2']     = 0;        // client custom ssl, 0 = automatic, 1 = off
$conf['auth_remote']      = 0;        // remote auth 0 - disabled, 1 - enabled 

/* DEBUG */                           // some configs 1 = yes, 0 = no
$conf['debug_info']       = 0;        // display $_GET, $_SESSION, $_POST and also set displaying all errors
$conf['debug_speed']      = 0;        // display page generating speed
$conf['debug_db_error']   = 1;        // 0 - just a notice about db error, 1 - real error short format, 2 - full format 
$conf['debug_db_sql']     = 0;        // display all sent sql (adodb format)
$conf['config_local']     = '';		  // 0 - disabled, or set full path to config file


/* OTHER */
$conf['php_dir']          = '/usr/local/bin/php'; // probably could be required, in cron job for example								
$conf['use_ob_gzhandler'] = 1;                    // on some instalations it should be set to 0
$conf['timezone']         = '';                   // if empty php.ini setting (date.timezone) will be used 
                                                  // if you need special one or have php errors set it here
                                                  // available timezones see at http://www.php.net/manual/en/timezones.php
?>