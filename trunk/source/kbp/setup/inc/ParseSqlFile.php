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


class ParseSqlFile
{
    
    static function getPrefix($prefix) {
        if($prefix && substr($prefix, -1) != '_') {
            $prefix .= '_';
        }
        
        return $prefix;
    }
    

    static function getCommandAndTable($str) {
        preg_match('#^([A-Z ]+) `?(\w+)`?(?:.*SELECT.*FROM `?(\w+)`?)?#s', $str, $match);
        $tables = (!empty($match[3])) ? array($match[2], $match[3]) : array($match[2]);
		
		// RENAME TABLE `kbp_entry_draft` TO `kbp_entry_autosave`;
		if(strpos($str, 'RENAME TABLE') !== false) {
			preg_match('#^(RENAME TABLE) `?(\w+)`? ([A-Z ]+) `?(\w+)`?#s', $str, $match2);
			if(!empty($match2[4])) {
				$tables[] = $match2[4];
			}
		}
		
        return array($match[1], $match[2], $tables);
    }
    
    
    static function getTable($str) {
        $arr = ParseSqlFile::getCommandAndTable($str);
        return $arr[1];
    }
    
    
    static function parseDumpArray($data) {
    
        foreach(array_keys($data) as $k) {
            
            $data[$k] = ltrim($data[$k]);
            if(!preg_match("#ALTER|CREATE|INSERT|DROP|UPDATE|REPLACE#", $data[$k])) {
                unset($data[$k]);
            }
        }
        
        $data = array_merge($data, array());
        return $data;
    }
    
    
    static function parseDumpString($data) {
        $data = preg_replace("#AUTO_INCREMENT\s*=\s*\d+#i", "", $data);
        $data = preg_replace("#DEFAULT CHARSET\s*=\s*\w+#i", "", $data);
        $data = preg_replace("#(DROP TABLE IF EXISTS `?\w+`?;)#", "$1\n--", $data);
        $data = str_replace("TYPE=MyISAM   ;", "TYPE=MyISAM;", $data);
        
        // ALTER TABLE `kbp_entry_index` ADD FULLTEXT KEY `title` (`title`);
        $data = preg_replace("#`\);\s#", "`);\n--\n", $data);
        
        $data = preg_split("#^--\s+#m", $data, -1, PREG_SPLIT_NO_EMPTY);
        
        return ParseSqlFile::parseDumpArray($data);
    }
    
    
    static function parseUpgradeArray($data) {
    
        foreach(array_keys($data) as $k) {
            
            $data[$k] = ltrim($data[$k]);
            if(!preg_match("#ALTER|INSERT|UPDATE|DELETE|DROP|TRUNCATE#", $data[$k])) {
                unset($data[$k]);
                continue;
            }        
        }
        
        $data = array_merge($data, array());
        return $data;
    }
    
    
    static function parseUpgradeString($data) {
        $data = preg_replace("#AUTO_INCREMENT\s*=\s*\d+#i", "", $data);
        $data = preg_replace("#DEFAULT CHARSET\s*=\s*\w+#i", "", $data);
        $data = preg_split("#^--\s+#m", $data, -1, PREG_SPLIT_NO_EMPTY);
        return ParseSqlFile::parseUpgradeArray($data);
    }    
    
    
    // prepare array
    // function parseSqlArray($data, $prefix, $parse = array(), $data = array(), $skip = array(), 
                                // $install = true, $default = false) {
    static function parseSqlArray($data, $prefix, $tables = array(), $install = true) {

        $skip = (isset($tables['skip'])) ? $tables['skip'] : array();
        $parse = (isset($tables['parse'])) ? $tables['parse'] : array();
        $with_data = (isset($tables['data'])) ? $tables['data'] : array();
        $only_data = (isset($tables['only_data'])) ? $tables['only_data'] : array();
        
    
        $_data = array();
        foreach($data as $k => $v) {
            
            list($command, $table, $table_all) = ParseSqlFile::getCommandAndTable($v);
            
            $table_no_pref = $table;
            if ($prefix) {
                $prefix_pattern = sprintf("#^%s#", preg_quote(key($prefix)));
                $table_no_pref = preg_replace($prefix_pattern, '', $table);
            }
    
        
            // to skip
            if(in_array($table_no_pref, $skip)) {
                continue;
            }    
            
            // not to parse
            if($parse && !in_array($table_no_pref, $parse)) {
                continue;
            }
    
            // with data
            if($with_data && !in_array($table_no_pref, $with_data)) {
                if($command == 'INSERT INTO' || $command == 'UPDATE') {
                    continue;
                }
            }
            
            // data only
            if($only_data && in_array($table_no_pref, $only_data)) {
                if($command == 'CREATE TABLE IF NOT EXISTS' || $command == 'CREATE TABLE') {
                    continue;
                }
            }
            
            if($install && $command == 'DROP TABLE IF EXISTS') {
                continue;
            }    
        
            if($prefix ) {
                $new_prefix = current($prefix);
                foreach($table_all as $t) {
                    $new_table = preg_replace($prefix_pattern, $new_prefix, $t);
                    $v = str_replace($t, $new_table, $v);
                }
            }
            
            $_data[] = $v;
        }
        
        return $_data;
    }
    
    
    // with prepared
    static function parsePreparedArray($data, $prefix = false, $remove_end = true) {
    
        foreach($data as $k => $v) {
            
            $v = trim($v);
            list($command, $table, $table_all) = ParseSqlFile::getCommandAndTable($v);
			
            if($prefix) {
                foreach($table_all as $t) {
                    $new_table = strtr($t, $prefix);
                    $v = str_replace($t, $new_table, $v);
                }
            }        
            
            if($remove_end && strpos($v, -1) == ';') {
                $v = substr($v, -1);
            }            
            
            $data[$k] = trim($v);
        }
        
        return $data;
    }
    
    
    static function parsePreparedString($data, $prefix, $mysql_version = false) {
        
        if($mysql_version) {
        
            // replace TYPE to ENGINE
            if($mysql_version >= 5) {
                $data = ParseSqlFile::replaceTypeSyntax($data);
        
            // replace ENGINE to TYPE
            } else {
                $data = ParseSqlFile::replaceTypeSyntax2($data);
            }
        }
        
        $data = explode('--', $data);
		$data = array_filter($data);
        return ParseSqlFile::parsePreparedArray($data, $prefix);
    }
    
    
    static function replaceTypeSyntax($str) {
        return str_replace('TYPE=MyISAM', 'ENGINE=MyISAM', $str);
    }

    static function replaceTypeSyntax2($str) {
        return str_replace('ENGINE=MyISAM', 'TYPE=MyISAM', $str);
    }

}
?>