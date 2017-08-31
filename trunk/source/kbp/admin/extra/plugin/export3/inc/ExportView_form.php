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

require_once 'ExportColumnHelper.php';
require_once 'core/common/CommonEntryView.php';


class ExportView_form extends AppView
{
    
    var $tmpl = 'form.html';

    
    function execute(&$obj, &$manager, $data) {

        $this->addMsg('user_msg.ini');
        $this->addMsg('random_msg.ini');
        $this->addMsg('common_msg.ini', 'knowledgebase');
        
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
        
        
        // fields
        $columns = $obj->getColumns();
        if (!empty($columns)) {
            $selected = array();
            $fields = ExportColumnHelper::validateFields($columns);
            
            foreach ($fields as $field) {
                $selected[$field] = $field;
            }
            
        } else {
            $selected = ExportColumnHelper::getDefaultSelectedColumns();
        }
        
        $skipped = ExportColumnHelper::getSkippedColumns($selected);
        
        $blocks = array(
            1 => $skipped,
            2 => $selected
        );
        
        foreach ($blocks as $block_num => $block_values) {
            $i = 1;
            $a = array();
            foreach($block_values as $k => $v) {
                $a['num'] = $i ++;
                $a['field_value'] = $k;
                $a['field_title'] = ExportColumnHelper::getColumnTitle($v);
                
                $tpl->tplParse($a, 'fields' . $block_num);
            }
        }
        
        
        $tpl->tplAssign('num_drop_rows', ExportColumnHelper::getColumnsNumber());
        
        $tpl->tplAssign('include_images_checked', (isset($data['include_images'])) ? 'checked' : '');
        $tpl->tplAssign('published_only_checked', (isset($data['published_only'])) ? 'checked' : '');
        
        
        // csv options
        $fields_terminated = ',';
        if (isset($data['csv']['fields_terminated'])) {
            $fields_terminated = $data['csv']['fields_terminated'];
        }
        $tpl->tplAssign('fields_terminated', $fields_terminated);
        
        $lines_terminated = '\n';
        if (isset($data['csv']['lines_terminated'])) {
            $lines_terminated = $data['csv']['lines_terminated'];
        }
        $tpl->tplAssign('lines_terminated', $lines_terminated);
        
        $tpl->tplAssign('header_row_checked', (isset($data['csv']['header_row'])) ? 'checked' : '');
        
                     
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
        
        foreach($manager->export_types as $type) {
            $tpl->tplAssign($type . '_display', 'none');
            
            // document options
            
        }

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