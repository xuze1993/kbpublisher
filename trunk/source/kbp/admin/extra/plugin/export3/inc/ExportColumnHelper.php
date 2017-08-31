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


class ExportColumnHelper
{
    
    static $columns = array(
        'id', 'category', 'category_id',
        'title', 'body', 'meta_keywords',
        'meta_description', 'hits',
        'date_posted', 'date_updated',
        'author_id', 'author_first_name',
        'author_last_name', 'updater_id',
        'updater_first_name', 'updater_last_name',
        'rating', 'active'
        );
                                    
    
    static $default_selected_columns = array('id', 'category', 'title', 'body', 'active');
    static $renamed_columns = array('meta_keywords' => 'tags');
    
    
    static function validateFields($keys) {
        return array_intersect($keys, self::$columns);
    }
    
    
    static function getDefaultSelectedColumns() {
        $data = array();
        
        foreach (self::$default_selected_columns as $key) {        
            $data[$key] = $key;
        }
        return $data;
    }
    
    
    static function getSkippedColumns($selected) {
        $skipped_keys = array_diff(self::$columns, $selected);
        
        $data = array();
        foreach ($skipped_keys as $key) {
            $data[$key] = $key;
        }
        return $data;
    }
    
    
    static function getColumnTitle($key) {
        if (!empty(self::$renamed_columns[$key])) {
            return self::$renamed_columns[$key];
            
        } else {
            return $key;
        }
    }
    
    
    static function getColumnsNumber() {
        return count(self::$columns);
    }
}
?>