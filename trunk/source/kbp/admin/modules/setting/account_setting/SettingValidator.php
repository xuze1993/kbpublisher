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

require_once 'eleontev/Validator.php';


// have to copy validate rules from appropriate module
// each setting page should have your own validator

class SettingValidator
{

    function validate($values) {
    
        $required = array('app_width');
    
    
        $v = new Validator($values, true);
    
        $v->required('required_msg', $required);
        
        return $v->getErrors();
    }
}
?>