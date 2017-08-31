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

require_once 'core/common/CommonCategoryView.php';


class KBCategoryView_form extends AppView
{
    var $tmpl = 'form.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));
        
        //private, hide private write categories
        $manager->setSqlParams($manager->getPrivateParams());
        
        $rows = $manager->getSelectRecords();
        $range = &$manager->getSelectRange($rows);
        
        // sort
        uasort($rows, array('CommonCategoryView', 'sortCategoriesByOrder'));
                
        $js_values = CommonCategoryView::getSortJsArray($rows, $obj->get('id'));
        $js_values = $this->stripVars($js_values);
        
        // all records, ignore private
        $sort_rows = $manager->getSortRecords($obj->get('parent_id'));
        $sort_val = CommonCategoryView::getSortOrder($obj->get('id'), $obj->get('sort_order'), $sort_rows);
        
        foreach($js_values as $k => $v) {
            $a['js_option_list_values'] = $v['str'];
            $key = ($k == $obj->get('parent_id')) ? $sort_val : $v['default'];
            $a['js_option_default'] = sprintf("'%s', '%s'", $k, $key);
            $tpl->tplParse($a, 'js_option_list');
        }
        
        // category
        $select = new FormSelect();
        $select->setFormMethod($_POST);
        $select->setSelectWidth(250);
        $select->select_tag = false;
        
        // set disabled for self and all childs
        if($obj->get('id')) { 
            $select->setOptionParam($obj->get('id'), 'disabled');
            foreach($manager->getChilds($rows, $obj->get('id')) as $v) {
                $select->setOptionParam($v, 'disabled');
            }
        }
        
        foreach($rows as $k => $v) {
            if($v['private']) {
                $select->setOptionParam($k, 'style="color: #cc0033;"');
            }
        }
        
        // category popup
        $tpl->tplAssign('category_popup_block', 
            CommonCategoryView::getCategoryPopupBlock($this->controller, $this->msg));
        
        
        //$select->setMultiple(5, false);
        $select->setSelectName('parent_id');
        $select->setRange($range, array(0=>$this->msg['top_level_msg']));
        $tpl->tplAssign('category_select', $select->select($obj->get('parent_id')));
        
        // category type
        $select->resetOptionParam();
        $select->setSelectName('category_type');
        $select->setRange($manager->getCategoryTypeSelectRange());
        $tpl->tplAssign('category_type_select', $select->select($obj->get('category_type')));        
        
        
        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        // other values
        $xajax->registerFunction(array('setCategoryValues', $this, 'ajaxSetCategoryValues'));
        
        // category admin
        $user_popup_link = $this->getLink('users', 'user', false, false, 
                                            array('filter[priv]' => 'any', 'filter[s]' => 1));
        $tpl->tplAssign('user_popup_link', $user_popup_link);
        foreach($obj->getAdminUser() as $id => $name) {
            $data = array('user_id'=>$id, 'name'=>$name);
            $data['delete_msg'] = $this->msg['delete_msg'];
            $tpl->tplParse($data, 'admin_user_row');
        }
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateForm'));
        $xajax->registerFunction(array('getCategoryAdmin', $this, 'ajaxGetCategoryAdmin'));
                
        // roles
        $this->parsePrivateStuff($tpl, $xajax, $obj, $manager);
                
        
        $tpl->tplAssign('commentable_options', $this->getChecked($obj->get('commentable')));
        $tpl->tplAssign('ratingable_options', $this->getChecked($obj->get('ratingable')));    
                
        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        
        $cat_id = ($obj->get('id')) ? $obj->get('id') : $obj->get('parent_id');
        $tpl->tplAssign($this->setRefererFormVars(@$_GET['referer'], array('index', $cat_id)));
        
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        // popup
        if($this->controller->getMoreParam('popup')) {
            $tpl->tplAssign('action_title', $this->msg['add_category_msg']);
        }
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    //category admin
    function ajaxGetCategoryAdmin($category_id) {
        return CommonCategoryView::ajaxGetCategoryAdmin($category_id, $this->manager);
    }
    
    // other values to set from parent
    function ajaxSetCategoryValues($category_id) {
        return CommonCategoryView::ajaxSetCategoryValues($category_id, $this->manager);
    }
    
    // private
    function parsePrivateStuff(&$tpl, &$xajax, $obj, $manager) {
        $tpl->tplAssign('block_private_tmpl', 
            PrivateEntry::getPrivateCategoryBlock($xajax, $obj, $manager, $this));
        
        // get private info on load and reload ? (now it works with ajax)
        $info = PrivateEntry::getPrivateCategoryMsg($manager, $obj->get('parent_id'), true, $this->msg);
        $tpl->tplAssign('category_info', $info);
    }        
    
    function ajaxGetCategoryRoles($category_id) {
        return PrivateEntry::ajaxGetCategoryRoles($category_id, $this->manager, $this, true);
    }
    
    function ajaxAddParentRoles($category_id) {
        return PrivateEntry::ajaxAddParentRoles($category_id, $this->obj, $this->manager);
    }
}
?>