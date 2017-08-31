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

class KBClientSearchHelper
{
    
    
    static function getInValue($values, $manager) {
                
        $q = (isset($values['q'])) ? trim($values['q']) : '';
        
        // validate in, reset to all if not known $in
        $in = (!empty($values['in'])) ? $values['in'] : $manager->getSetting('search_default');
        $range = KBClientView_search::getSearchInRange($manager);
        $range = array_filter(array_keys($range), 'is_string');
        if(!in_array($in, $range)) {
            $in = 'all';
            $_GET['in'] = $in;
        }
        
        // validate by, reset to all if not known $by
        $by = (!empty($values['by'])) ? $values['by'] : 'all';
        $range = KBClientView_search::getSearchByRange($in);
        $range['author_id'] = ''; // add to range for api
        $range = array_filter(array_keys($range), 'is_string');
        if(!in_array($by, $range)) {
            $by = 'all';
            $_GET['by'] = $by;
        }
        
        
        if($in == 'all' && $by == 'all') {
            
            // article id
            if($manager->getSetting('search_article_id') && is_numeric($q)) {
                $in = 'article';
                $_GET['in'] = $in;
            
                $by = 'id';
                $_GET['by'] = $by;
                
            // tags (keywords)
            } elseif(preg_match("#^\[(.*?)\]$#", $q)) {
                $in = 'article';
                $_GET['in'] = $in;
                $_GET['q'] = preg_replace("#^\[(.*?)\]$#", "\\1", $q);
            
                $by = 'keyword';
                $_GET['by'] = $by;
            }
        }
         
        // $in = 'article';
        // $by = 'attachment';
         
        $ret = array('in'=>$in, 'by'=>$by);
        return $ret;
    }
        
    
    static function isOrderByScore($values) {
        $val = false;
        if(!empty($values['q'])) {
            if($values['by'] != 'id' &&
               $values['by'] != 'keyword' && 
               $values['by'] != 'author_id') {
                
                $val = true;
            }
        }
        
        return $val;        
    }
    
}
?>