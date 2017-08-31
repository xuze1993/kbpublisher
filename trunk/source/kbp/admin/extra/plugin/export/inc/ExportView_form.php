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
            'popup_params' => array('export' => 1),
            'hide_private' => true
            );
        $tpl->tplAssign('category_block_tmpl', 
            CommonEntryView::getCategoryBlock($obj, $manager, $categories, $this->controller->module, $this->controller->page, $b_options));
        $select->setRange($categories); 
        $tpl->tplAssign('category_select', $select->select());


        // roles
        $tpl->tplAssign('role_display', ($obj->getUserMode() == 2) ? 'block' : 'none'); 
        $tpl->tplAssign('role_block_tmpl', $this->getRoleBlock($obj, 'users', 'user', true));

        
        // header
        $tpl->tplAssign('header_checked', (isset($data['header'])) ? 'checked' : '');
        
        // duplex
        $tpl->tplAssign('duplex_checked', (isset($data['pdf']['duplex'])) ? 'checked' : '');
        
        // orientation         
        $select->setSelectName('htmldoc[pdf][orientation]');
        $range = array(
            'portrait' => $this->msg['portrait_msg'],
            'landscape' => $this->msg['landscape_msg']
            );
        
        $select->setRange($range); 
        $orientation = isset($data['pdf']['orientation']) ? $data['pdf']['orientation'] : 1;
        $tpl->tplAssign('orientation_select', $select->select($orientation));
        
        // password
        if (isset($data['pdf']['password'])) {
            $tpl->tplAssign('password', $data['pdf']['password']);
        }
        
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
        

        foreach($manager->export_types as $type) {
            $tpl->tplAssign($type . '_display', 'none');
        }

        if (!empty($data['do'])) {
            foreach($data['do'] as $type => $val) {
                $tpl->tplAssign($type . '_checked', 'checked');
                $tpl->tplAssign($type . '_display', 'block');  
            }
        }
                              
        // document options
        if (isset($data['title'])) {
            $tpl->tplAssign('document_title', $data['title']); 
        }
        
        $stuff_popup_link = $this->getLink('stuff', 'stuff_entry');
        $tpl->tplAssign('stuff_popup_link', $stuff_popup_link);
        
        $titleimage = false;
        if (isset($data['titleimage'])) {
            $titleimage = $manager->getStuffImageById($data['titleimage']);
            if ($titleimage) {
                $tpl->tplAssign('document_titleimage_id', $titleimage['id']);
                $tpl->tplAssign('document_titleimage_title', $titleimage['title']);
                $tpl->tplAssign('document_titleimage_name', '(' . $titleimage['filename'] . ')'); 
            }        
        }

        $logoimage = false;
        if(isset($data['pdf']['logoimage'])) {
            $logoimage = $manager->getStuffImageById($data['pdf']['logoimage']);
            if ($logoimage) {
                $tpl->tplAssign('document_logoimage_id', $logoimage['id']);
                $tpl->tplAssign('document_logoimage_title', $logoimage['title']);
                $tpl->tplAssign('document_logoimage_name', '(' . $logoimage['filename'] . ')'); 
            }            
        }


        // link to delete document images
        $link = ($this->controller->action == 'update' && $titleimage) ? 'inline' : 'none';         
        $tpl->tplAssign('titleimage_delete_display', $link);

        $link = ($this->controller->action == 'update' && $logoimage) ? 'inline' : 'none';
        $tpl->tplAssign('logoimage_delete_display', $link);
        
        
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