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
            
        if($key == 'admin_email' && empty($value)) {
            $value = $this->manager->getSettings('134', 'from_email');
        }
        
        return $value;
    }
    
    
    // options parse
    function parseSelectOptions($key, $values, $range = array()) {
        
        if($key == 'mailer') {
        
            $options = array();
            $options['mail'] = $values['option_1'];
        
            // remove Sendmail in cloud
            if(!BaseModel::isCloud()) {
                $options['smtp'] = $values['option_2'];
                $options['sendmail'] = $values['option_3'];
            }
            
            $values = $options;
        }
    
        return $values;
    }
    
    
    function parseInputOptions($key, $value) {
        $ret = false;
        
        // readonly in cloud
        if($key == 'noreply_email' && BaseModel::isCloud()) {
            $ret = ' readonly';
        }
        
        return $ret;
    }
    
    
    function parseSubmit($template_dir, $msg) {
        $tpl = new tplTemplatez($template_dir . 'form_submit_email.html');
        $tpl->tplParse($msg);
        return $tpl->tplPrint(1);
    }
}
?>