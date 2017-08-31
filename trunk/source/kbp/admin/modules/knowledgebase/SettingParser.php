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
    
    function parseOut($key, $value) {
        return $value;
    }
        
    
    // rearange soting by groups
    function parseGroupId($group_id) {
        $sort = array(
            8=>'2.1', 9=>'4.1', 10=>'4.6', 11=>'4.7', 12=>'4.8', 
            14=>'4.4', 15=>'5.5', 16=>'4.5', 13=>'5.2', 14=>'4.9');
        return (isset($sort[$group_id])) ? $sort[$group_id] : $group_id;
    }
    
    
    // options parse
    function parseSelectOptions($key, $values, $range = array()) {
        
        // remove "allowed for all" and "with priv only" for comment subscription
        if($key == 'allow_subscribe_comment') {
            unset($values['option_2']);
            unset($values['option_4']);
        }
        
        //echo "<pre>"; print_r($values); echo "</pre>";
        return $values;
    }
        
    
    function parseDescription($key, $value) {
        if($key == 'show_author_format') {
            $tags = '[first_name], [last_name], [middle_name], 
            [short_first_name], [short_last_name], [short_middle_name], 
            [username], [email], [phone], [id], [company]';
            $value = str_replace('{tags}', $tags, $value);        
        
        } elseif($key == 'comments_author_format') {
            $tags = '[first_name], [last_name], [middle_name], 
            [short_first_name], [short_last_name], [short_middle_name],
            [username], [email], [phone], [user_id]';
            $value = str_replace('{tags}', $tags, $value);        
        }
        
        return $value;
    }


    function parseMsgKey($key) {
        
        if($key =='show_send_link') {
            $key = 'allow_send_link';
        }

        return $key;
    }

}
?>