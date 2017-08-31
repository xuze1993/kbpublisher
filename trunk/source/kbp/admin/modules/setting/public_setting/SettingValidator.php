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


class SettingValidator
{
     
    function validate($values) {
        
        $required = array();

        $v = new Validator($values, true);

        $v->required('required_msg', $required);
        if($v->getErrors()) {
            return $v->getErrors();
        }        

    
        if(!empty($values['page_to_load'])) {
            
            $val = $values['page_to_load'];
            
            // default
            if(strtolower($val) == 'default') {
            
            // template
            } elseif(strtolower($val) == 'html') {

            // file path
            } elseif(strpos($val, '[file]') !== false) {
            
                $val = trim(str_replace('[file]', '', $val));
                if(@!fopen(trim($val), "rb")) {
                    $v->setError('page_not_exists_msg', 'page_to_load');
                }
            } else {
                $v->setError('page_wrong_msg', 'page_to_load');
            }
        }
        
        return $v->getErrors();
    }
    
}
?>