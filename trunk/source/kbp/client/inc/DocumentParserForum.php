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


class DocumentParserForum
{
        
    static function isQuote($str) {
        return (stripos($str, '[quote') !== false);
    }
    
    
    static function &parseQuote(&$str, $msg) {
        $search = '#\[quote=(\w*)\](.*?)\[\/quote\]#si';
        $replace = '<blockquote><div class="forumQuote"><div class="forumQuoteAuthor">$1 %s:</div>$2</div></blockquote>';
        $replace = sprintf($replace, $msg);
        $str = preg_replace($search, $replace, $str);
        return $str;
    }
    
    
    static function &cutQuote(&$str) {
        $search = '#\[quote=\w*\].*?\[\/quote\]#si';
        $str = preg_replace($search, '', $str);
        return $str;
    }

}

?>