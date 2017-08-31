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

require_once APP_MODULE_DIR . 'setting/setting/inc/SettingView_form.php';

class SettingView_popup extends SettingView_form
{
    
    
    function execute(&$obj, &$manager) {
        
        $parser = &$manager->getParser();
        $setting_msg = $parser->getSettingMsg($manager->module_name);
        
        $form_data = $this->parseMultiIni($this->template_dir . 'form.ini');
        $r = new Replacer();
        
        
        $tpl = new tplTemplatez($this->template_dir . 'form_page_to_load.html');
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors, $manager->module_name));
        // $tpl->tplAssign('js_error', $this->getErrorJs($obj->errors));

        $rows = &$manager->getRecords();
        
        $fname = $this->controller->getMoreParam('popup');
        $fid = $this->controller->getMoreParam('field_name');
        
        if ($fname == 'page_to_load_mobile') {
            $group_id = 2;
            $fname2 = 'page_to_load_tmpl_mobile';
            $fid2 = $manager->getSettingIdByKey('page_to_load_tmpl_mobile');
            
            $tpl->tplSetNeeded('/header_hidden');
            $tpl->tplSetNeeded('/footer_hidden');
            
        } else {
            $group_id = 1;
            $fname2 = 'page_to_load_tmpl';
            $fid2 = $manager->getSettingIdByKey('page_to_load_tmpl');
            
            $tpl->tplSetNeeded('/header');
            $tpl->tplSetNeeded('/footer');
        }
        
        foreach ($rows[$group_id] as $row) {
            if ($row['input']) {
                
                $row['value'] = $obj->get($row['setting_key']);
                
                if ($row['setting_key'] == 'left_menu_width') {
                    $tpl->tplSetNeeded('/left_menu_slider');
                    $tpl->tplAssign('left_menu_width', ($row['value']) ? $row['value'] : 230);
                    
                    $row['options'] = 'class="not_color trLighter" readonly';
                    $row['id'] = 'left_menu';
                    $row['style'] = 'border: 0;font-weight: bold;';
                    
                } else {
                    $row['options'] = 'onblur="checkColor(this)"';
                    $row['color'] = ($row['value']) ? $row['value'] : '#F8F8F3';
                    $tpl->tplSetNeeded('row/color_box');
                }
                
                $row['form_input'] = $r->parse($form_data[$row['input']], $row);
                $row['form_input'] = str_replace('width: 298px;', 'width: 150px;', $row['form_input']);
                
                if ($row['setting_key'] == 'left_menu_width') {
                    $row['form_input'] = '<div id="left_menu_slider" style="margin: 5px 1px;"></div>' . $row['form_input'];
                }
                
                $msg_key = $parser->parseMsgKey($row['setting_key']);
                $row['title_msg'] = $parser->parseTitle($msg_key, $setting_msg[$msg_key]['title']);
                $row['description_msg'] = $parser->parseDescription($msg_key, $setting_msg[$msg_key]['descr']);
                    
                $tpl->tplParse($row, 'row');  
            }
        }

        $tpl->tplAssign('fid', $fid);
        $tpl->tplAssign('fid2', $fid2); // page_to_load_tmpl
        $tpl->tplAssign('fid_value', 'html'); // set page_to_load to "html"
        
        $value = explode('--delim--', $obj->get($fname2));
        $tpl->tplAssign('header', $value[0]);
        $tpl->tplAssign('footer', (isset($value[1])) ? $value[1] : '');
        $tpl->tplAssign('head_code', (isset($value[2])) ? $value[2] : '');
        
        $tpl->tplAssign('page_title', $setting_msg[$fname]['title']);
        
        $tpl->tplAssign('title', $setting_msg[$fname2]['title']);
        $tpl->tplAssign('header_msg', $setting_msg[$fname2]['header']);
        $tpl->tplAssign('footer_msg', $setting_msg[$fname2]['footer']);
        $tpl->tplAssign('head_code_msg', $setting_msg[$fname2]['head_code']);
        $tpl->tplAssign('style_msg', $setting_msg[$fname2]['style']);
        $tpl->tplAssign('add_example_msg', $setting_msg[$fname2]['example']);
        
        if(!empty($_GET['saved']) && !$obj->errors) {
            $tpl->tplSetNeeded('/close_window');
            $tpl->tplAssign('parent_setting_name', $fname);
            
            $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
            $msgs = AppMsg::parseMsgsMultiIni($file);
            $msg['title'] = '';
            $msg['body'] = $msgs['custom_template_saved'];
            $vars['public_link'] = APP_CLIENT_PATH;
            
            $tpl->tplAssign('hint', BoxMsg::factory('hint', $msg, $vars));
        }
        
        $vars = $this->setCommonFormVars($obj);
        $tpl->tplAssign($vars);
        $tpl->tplAssign($this->msg);       
        
        $tpl->tplParse();
        
        return $tpl->tplPrint(1);
    }
}
?>