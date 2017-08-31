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


//mbstring
if(!empty($conf['lang']['mbstring']) && extension_loaded('mbstring')) {
    
    function _strlen($str) { return mb_strlen($str); }
    function _strtolower($str) { return mb_strtolower($str); }
    function _strtoupper($str) { return mb_strtoupper($str); }
    function _substr($str, $start, $length = NULL) { 
        if($length === NULL) { return mb_substr($str, $start); }
        else                 { return mb_substr($str, $start, $length); }
    }
    function _strpos($haystack, $needle, $offset = 0) { return mb_strpos($haystack, $needle, $offset); }
    function _strrpos($haystack, $needle) { return mb_strrpos($haystack, $needle); }
    // function _strrpos($haystack, $needle, $offset = 0) { return mb_strrpos($haystack, $needle, $offset); }

} else {
    
    function _strlen($str) { return strlen($str); }
    function _strtolower($str) { return strtolower($str); }
    function _strtoupper($str) { return strtoupper($str); }
    function _substr($str, $start, $length = NULL) { 
        if($length === NULL) { return substr($str, $start); }
        else                 { return substr($str, $start, $length); }
    }
    function _strpos($haystack, $needle, $offset = 0) { return strpos($haystack, $needle, $offset); }
    function _strrpos($haystack, $needle) { return strrpos($haystack, $needle); }
    // function _strrpos($haystack, $needle, $offset = 0) { return strrpos($haystack, $needle, $offset); }
}
?>