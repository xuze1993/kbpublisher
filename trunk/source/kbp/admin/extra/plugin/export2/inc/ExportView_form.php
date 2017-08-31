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

require_once 'core/common/CommonEntryView.php';


class ExportView_form extends AppView
{
    
    var $tmpl = 'form.html';

    
    function execute(&$obj, &$manager, $data) {

        $this->addMsg('user_msg.ini');                
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));

        $error_msg = false;
        if($this->controller->action == 'update') {
            $error_msg = $this->checkValues($obj, $manager);
        }
        
        if (!$obj->errors && !empty($error_msg)) {
            $msg_vars = array('body' => implode('<br/>', $error_msg));
            $tpl->tplAssign('error_msg', BoxMsg::factory('error', $msg_vars));             
        }

        
        $select = new FormSelect();
        $select->setSelectWidth(250);
      
        // categories         
        $select->setSelectName('category_id');
        $categories = $manager->getCategoryRange();
        $categories[0] = $this->msg['all_categories2_msg'];
        
        $b_options = array(
            'no_button' => true, 
            'all_option' => true, 
            'default_button' => false, 
            // 'popup_params' => array('export' => 1),
            'hide_private' => true
            );
        
        $tpl->tplAssign('category_block_tmpl', 
            CommonEntryView::getCategoryBlock($obj, $manager, $categories,
                                               $this->controller->module, $this->controller->page, $b_options));
        $select->setRange($categories); 
        $tpl->tplAssign('category_select', $select->select());


        // roles
        $tpl->tplAssign('role_display', ($obj->getUserMode() == 2) ? 'block' : 'none'); 
        $tpl->tplAssign('role_block_tmpl', $this->getRoleBlock($obj, 'users', 'user', true));
        
        // generate for user
        $select->setSelectName('user');
        $range = $manager->getGenerateForUserSelectRange($this->msg);
        $select->setRange($range);
        $tpl->tplAssign('user_select', $select->select($obj->getUserMode()));
                     
        // generated files
        if($this->controller->action == 'update') {
            $tpl->tplSetNeeded('/generated_files_block');
        }
                    
        $e_data = $manager->getExportData($obj->get('id'));
        
        if (!empty($e_data)) {
            $detail_link = $this->getActionLink('detail', $obj->get('id'));
            $detail_link = sprintf('<a href="%s">%s</a>', $detail_link, $this->msg['view_files_msg']);
            
            $tpl->tplAssign('detail_link', $detail_link); 
        } else {
            $tpl->tplAssign('detail_link', '--');            
        }
        
        
        // wkhtmltopdf
        if (SettingModel::getQuick(140, 'plugin_wkhtmltopdf_path') != 'off') {
            $tpl->tplSetNeeded('/pdf');
            
            // orientation
            $select->setSelectName('params[pdf][orientation]');
            $range = array(
                'Portrait' => $this->msg['portrait_msg'],
                'Landscape' => $this->msg['landscape_msg']
                );
            
            $select->setRange($range); 
            $orientation = isset($data['pdf']['orientation']) ? $data['pdf']['orientation'] : 1;
            $tpl->tplAssign('orientation_select', $select->select($orientation));
            
            
            $custom_templates = array('cover', 'header', 'footer');
            foreach ($custom_templates as $k) {
                $v = array();
                
                $v['type'] = $k;
                $v['title'] = $this->msg[$k . '_msg'];
                $v['url'] = $this->getActionLink('template', false, array('type' => $k));
                
                $checked = 'checked';
                if (empty($data['pdf'][$k])) {
                    if ($this->controller->action == 'insert') {
                        if (!SettingModel::getQuick(140, 'plugin_export_' . $k)) {
                            $checked = '';
                        }
                        
                    } else {
                        $checked = '';
                    }
                }
                $v['checked'] = $checked;
                
                $value = isset($data['pdf'][$k]) ? $data['pdf'][$k] : SettingModel::getQuick(140, sprintf('plugin_export_%s_tmpl', $k));
                $v['value'] = RequestDataUtil::stripVars($value, array(), true);
                
                if ($k == 'header') {
                    $v['margin_value'] = isset($data['pdf']['margin_top']) ? $data['pdf']['margin_top'] : SettingModel::getQuick(140, 'plugin_wkhtmltopdf_margin_top');
                    $v['margin_type'] = 'top';
                    
                    $tpl->tplSetNeeded('pdf_custom_template/margin');
                }
                
                if ($k == 'footer') {
                    $v['margin_value'] = isset($data['pdf']['margin_bottom']) ? $data['pdf']['margin_bottom'] : SettingModel::getQuick(140, 'plugin_wkhtmltopdf_margin_bottom');
                    $v['margin_type'] = 'bottom';
                    
                    $tpl->tplSetNeeded('pdf_custom_template/margin');
                }
                
                $tpl->tplParse($v, 'pdf_custom_template');
            }
        }
        
        
        foreach($manager->export_types as $type) {
            $tpl->tplAssign($type . '_display', 'none');
        }

        $tpl->tplAssign('print_info_checked', $this->getChecked(isset($data['print_info'])));

        if (!empty($data['do'])) {
            foreach($data['do'] as $type => $val) {
                $tpl->tplAssign($type . '_checked', 'checked');
                $tpl->tplAssign($type . '_display', 'block');  
            }
        }
        
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateForm'));
        
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
                       
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function getRoleBlock($obj, $module = 'users', $page = 'user', $no_button = false) {
        require_once APP_MODULE_DIR . 'user/user/inc/UserView_form.php';
        require_once APP_MODULE_DIR . 'user/user/inc/UserModel.php';
        
        $manager = new UserModel();
        $view = new UserView_form();
        return $view->getRoleBlock($obj, $manager, $module, $page, $no_button);    
    }


    function checkValues(&$obj, $manager) {
        
        $error_msg = array();

        $category = $obj->getCategory();
        $category_id = @$category[0];

        // category was deleted
        if ($category_id && !$manager->cat_manager->getById($category_id)) {
            $obj->setCategory(array());
            $msg = AppMsg::getMsgs('after_action_msg.ini', false, 'category_not_exists');
            $error_msg[] = $msg['body'];
        }


        $roles = $obj->getRole();
        $new_roles = array();
        
        foreach ($roles as $role_id) {
            if (!$manager->role_manager->getById($role_id)) { // role was deleted
                $msg = AppMsg::getMsgs('after_action_msg.ini', false, 'role_not_exists');
                $error_msg[3] = $msg['body']; 
            } else {
                $new_roles[] = $role_id;
            }
        }
        
        $obj->setRole($new_roles);
        
        return $error_msg;
    }
}
?>