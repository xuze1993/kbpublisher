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


class KBClientView_search extends KBClientView_common
{
    
    static $search_in_range = array(
        'all',
        'article',
        'file',
        'news',
        'forum'
    );
    
    static $search_by_range = array(
        'all',
        'title',
        'keyword',
        'id'
    );

    static $search_by_range_extra = array(
		'article' => array(
			//'attachment'
		)
    );
    
    
    static function getSearchInRange($manager, $skip_disabled = false) {
        
        $range_msg = AppMsg::getMsgs('ranges_msg.ini', 'public', 'search_in_range');
        
        $range = array();
        foreach(self::$search_in_range as $v) {
            
            if($skip_disabled) {
                if($v == 'file' && !$manager->getSetting('module_file')) {
                    continue;
                }
          
                if($v == 'news' && !$manager->getSetting('module_news')) {
                    continue;
                }

                if($v == 'forum' && (!$manager->getSetting('module_forum') || !BaseModel::isModule('forum'))) {
                    continue;
                }
            }
            
            $range[$v] = $range_msg[$v];
        }
        
        return $range;
    }

    
    function getSearchInSelect($range, $current) {
        
        $select = new FormSelect();
        $select->select_name = 'in';
        $select->select_tag = false;

        $select->setRange($range);
        return $select->select($current);
    }
    
    
    // SERACH BY // ------------------------
    
    static function getSearchByRange($current_in = false) {
        $range_msg = AppMsg::getMsgs('ranges_msg.ini', 'public', 'search_by_range');
        
        $range = array();
        foreach(self::$search_by_range as $k => $v) {
            $range[$v] = $range_msg[$v];
        }
        
        if ($current_in) {
            $extra_range = self::getSearchByExtraRange($current_in);
            $range = array_merge($range, $extra_range);
        }
		
        return $range;
    }
    
    
    static function getSearchByExtraRange($current_in) {
        $range_msg = AppMsg::getMsgs('ranges_msg.ini', 'public', 'search_by_range');
        
        $range = array();
        if(isset(self::$search_by_range_extra[$current_in])) {
            foreach(self::$search_by_range_extra[$current_in] as $k => $v) {
                $range[$v] = $range_msg[$v];
            }
        }
        
        return $range;
    }
    
    
    function parseSearchByBlock($tpl, $current_by, $current_in) {
        $range = self::getSearchByRange();
        $range = $this->stripVars($range);
        
        $extra_range = self::getSearchByExtraRange($current_in);
        $extra_range = $this->stripVars($extra_range);
        
        $range = array_merge($range, $extra_range);
        
        if ($this->mobile_view) {
            $select = new FormSelect();
            $select->select_name = 'by';
            $select->select_tag = false;
    
            $select->setRange($range);
            $tpl->tplAssign('search_by_select', $select->select($current_by));
            
        } else {
            foreach ($range as $k => $v) {
                $v1['name'] = $v;
                $v1['value'] = $k;
                $v1['checked'] = ($current_by == $k) ? 'checked' : '';
                $v1['class'] = (!empty($extra_range[$k])) ? 'search_item search_extra_item' : 'search_item';
                
                $tpl->tplParse($v1, 'by_row');
            }
        }
        
    }
    
    
    // SPECIAL SEARCH // --------------------
    
    // if some special search used, 
    // parse $str and change by reference and in all child "isSpecialSearchStr" it will be ok
    function isSpecialSearchStr(&$str) {

        // $str = urldecode($str);
        
        $search = array();
        $search['id'] = "#^(?:id:)(\d+)$#";
        // $search['topic_id'] = "#^(?:id:)(\d+)\s+(.*?)$#":
        $search['author_id'] = "#^(?:author_id:)(\d+)$#";
        
        return self::parseSpecialSearchStr($str, $search);
    }
        
    
    function parseSpecialSearchStr($str, $preg_arr) {
        
        $str = trim($str);
        foreach ($preg_arr as $k => $v) {
            preg_match($v, $str, $match);
            // echo '<pre>', print_r($match, 1), '</pre>';
            
            if(!empty($match[0])) {
                $ret['rule'] = $k;
                $ret['val'] = (isset($match[1])) ? $match[1] : false;
                
                if($ret['rule'] == 'id') {
                    $ret['val'] = (int) $ret['val'];
                }
                
                return $ret;
            }
        }
        
        return false;        
    }
    

    // static function getSpecialSearchSql($ret, $string, $id_field = 'e.id') {
    //     
    //     $arr = array();
    //     
    //     if($ret['rule'] == 'id') {
    //         $arr['where'] = sprintf("AND {$id_field} = '%d'", $ret['val']);
    //         
    //     } elseif ($ret['rule'] == 'author_id') {
    //         $arr['where'] = sprintf("AND {$id_field} = '%d'", $ret['val']);
    //     }
    // 
    //     return $arr;    
    // }
    
    
    static function getSpecialSearchIn($manager, $rule, $in) {
        $range = self::getSearchInRange($manager);
        // $top_in_range = array('article' => '123');
        
        // echo '<pre>', print_r("--", 1), '</pre>';
        // echo '<pre>', print_r($in, 1), '</pre>';
        // echo '<pre>', print_r("--", 1), '</pre>';
        
        foreach(array_keys($range) as $v) {
            if($in == $v) {
                return sprintf('%s_%s', $v, $rule);
            }
        }
        
        return $in;
    }
    
    
}
?>