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


class SettingParser extends SettingParserCommon
{    
    
    function parseIn($key, $value, &$values = array()) {
        
        $dirs = array('html_editor_upload_dir', 'cache_dir', 'file_dir');
        $tools = array('file_extract_pdf', 'file_extract_doc', 'file_extract_doc2');
        
        if(in_array($key, $dirs) && !empty($value)) {
            $value = $this->parseDirectoryValue($value);
        
        } elseif(in_array($key, $tools)) {
            if(!empty($value) && strtolower($value) != 'off') {
                $value = $this->parseDirectoryValue($value);
            }
        
        } elseif(in_array($key, array('file_denied_extensions', 'file_allowed_extensions'))) {
            $value = str_replace(' ', '', $value);
            $value = str_replace(array(';','.'), ',', $value);
                
        // set max size no more than upload_max_filesize
        } elseif($key == 'file_max_filesize') {
        
            // upload_max_filesize "2M" PHP_INI_SYSTEM|PHP_INI_PERDIR 
            $size = WebUtil::getIniSize('upload_max_filesize')/1024; // in kb
            if(strtolower($value) == 'system') {
                $value = $size;
            }
            
            $value = ($value > $size) ? $size : $value;
            
        } elseif(in_array($key, array('entry_autosave', 'auth_expired'))) {
            $value = (int) $value;
        
        } elseif($key == 'entry_history_max') {
            $value = strtolower($value);
            $value = ($value == 'all') ? $value : (int) $value;
        }
        
        return $value;
    }
    
    
    function parseOut($key, $value) {
    
        // hide in demo mode
        if(APP_DEMO_MODE && $key == 'file_dir') {
            $value = '- - - - - - - - - - -';
        
         // set max size nor more than upload_max_filesize
        } elseif($key == 'file_max_filesize') {

            // upload_max_filesize "2M" PHP_INI_SYSTEM|PHP_INI_PERDIR 
            $size = WebUtil::getIniSize('upload_max_filesize')/1024; // in kb
            if(strtolower($value) == 'system') {
                $value = $size;
            }

            $value = ($value > $size) ? $size : $value;
    }
    
        return $value;
    }
            
    
    // options parse
    function parseSelectOptions($key, $values, $range = array()) {
        
        if($key == 'directory_missed_file_policy') {
            
            // require_once APP_MODULE_DIR . 'setting/list/inc/ListValueModel.php';
            $status =  ListValueModel::getListSelectRange('file_status', false, false);
            
            $options = array();
            $options['none'] = $values['option_1'];
            $options['delete'] = $values['option_2'];
            
            foreach($status as $k => $v) {
                $options['status_' . $k] = 'Set status: ' . $v;
            }

            $values = $options;
        
        // lang
        } elseif($key == 'lang') {
            
            require_once APP_MSG_DIR . 'CompareLang.php';
            $values = CompareLang::getLangSelectRange(APP_MSG_DIR);
            
        // timezone
        } elseif($key == 'timezone') {
            
            $values = SettingParser::getTimezones();
            
        } elseif($key == 'article_default_category') {
            
            require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryModel.php';
            $emanager = new KBEntryModel();
            
            $options = array();
            $options['none'] = $values['option_1'];
            
            foreach ($emanager->getCategoryRecords() as $id => $v) {
                if ($v['parent_id'] == 0) {
                    $options[$id] = $v['name'];
                }   
            }
            
            $values = $options;
            
        } elseif($key == 'file_default_category') {
            
            require_once APP_MODULE_DIR . 'file/entry/inc/FileEntryModel.php';
            $emanager = new FileEntryModel();
            
            $options = array();
            $options['none'] = $values['option_1'];
            
            foreach ($emanager->getCategoryRecords() as $id => $v) {
                if ($v['parent_id'] == 0) {
                    $options[$id] = $v['name'];
                }   
            }
            
            $values = $options;
        }
        
        //echo "<pre>"; print_r($values); echo "</pre>";
        return $values;
    }    
    
    
    function parseDescription($key, $value) {
        if($key == 'cron_mail_critical') {
            $email = $this->manager->getSettings('134', 'admin_email');
            $str = '<a href="%s">%s</a>';
            $str = sprintf($str, AppController::getRefLink('setting', 'email_setting'), $email);
            $value = str_replace('{email}', $str, $value);
        }
        
        return $value;
    }
    
    
    // rearange soting by groups
    function parseGroupId($group_id) {
        $sort = array(12 => '1.1', 13 => '2.1', 14 => '8.1', 15 => '1.2');
        return (isset($sort[$group_id])) ? $sort[$group_id] : $group_id;
    }
    
    
    static function getTimezones() {
        
        $result = array();
        $timezones = array();
        
        $system_timezone = ini_get('date.timezone');
        if(!$system_timezone) {
            $system_timezone = 'UTC';
        }
        
        $offset = timezone_offset_get(new DateTimeZone($system_timezone), new DateTime());
        $offset_str = sprintf('%s%02d:%02d', ($offset >= 0) ? '+' : '-', abs($offset / 3600), abs($offset % 3600));
       
        $result['system'] = sprintf('System - (UTC %s) %s', $offset_str, $system_timezone);
        
        $search = '~^(?:A(?:frica|merica|ntarctica|rctic|tlantic|sia|ustralia)|Europe|Indian|Pacific)/~';
        $tlist  = preg_grep($search, timezone_identifiers_list());

        // only process geographical timezones
        foreach ($tlist as $timezone) {
            $timezone = new DateTimeZone($timezone);
            $id = array();

            // get only the two most distant transitions
            foreach (array_slice($timezone->getTransitions($_SERVER['REQUEST_TIME']), -2) as $transition) {
                // dark magic
                $id[] = sprintf('%b|%+d|%u', $transition['isdst'], $transition['offset'], $transition['ts']);
            }

            // sort by %b (isdst = 0) first, so that we always get the raw offset
            sort($id, SORT_NUMERIC);

            $timezones[implode('|', $id)][] = $timezone->getName();
        }


        if (count($timezones) > 0) {
            uksort($timezones, function($a, $b) // sort offsets by -, 0, +
            {
                foreach (array('a', 'b') as $key) {
                    $$key = explode('|', $$key);
                }

                return intval($a[1]) - intval($b[1]);
            });

            foreach ($timezones as $key => $value) {
                $zone = reset($value); // first timezone ID is our internal timezone
                $result[$zone] = preg_replace(array('~^.*/([^/]+)$~', '~_~'), array('$1', ' '), $value); // "humanize" city names

                // "humanize" the offset 
                if (array_key_exists(1, $offset = explode('|', $key)) === true) {
                    $offset = str_replace(' +00:00', '', sprintf('(UTC %+03d:%02u)', $offset[1] / 3600, abs($offset[1]) % 3600 / 60));
                }

                 // sort city names
                if (asort($result[$zone]) === true) {
                    $result[$zone] = trim(sprintf('%s %s', $offset, implode(', ', $result[$zone])));
                }
            }
        }

        return $result;
    }
}
?>