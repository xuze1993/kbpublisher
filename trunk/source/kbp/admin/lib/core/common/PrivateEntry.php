<?php
// +----------------------------------------------------------------------+
// | Author:  Evgeny Leontev <eleontev@gmail.com>                         |
// | Copyright (c) 2005 Evgeny Leontev                                    |
// +----------------------------------------------------------------------+
// | This source file is free software; you can redistribute it and/or    |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This source file is distributed in the hope that it will be useful,  |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// +----------------------------------------------------------------------+


class PrivateEntry
{


    // CATEGORY FORM // --------------

    static function getPrivateCategoryBlock(&$xajax, $obj, $manager, $view, $module = 'knowledgebase', $page = 'kb_category') {

        $tpl = new tplTemplatez(APP_MODULE_DIR . 'knowledgebase/entry/template/block_private_entry.html');

        $select = new FormSelect();
        $select->setSelectWidth(250);
        $select->select_tag = false;

        $roles = $manager->role_manager->getSelectRangeFolow();

        // read
        $range = array();
        foreach($obj->getRoleRead() as $role_id) {
            $range[$role_id] = $roles[$role_id];
        }

        $select->setRange($range);
        $tpl->tplAssign('role_select', $select->select());

        // write
        $range = array();
        foreach($obj->getRoleWrite() as $role_id) {
            $range[$role_id] = $roles[$role_id];
        }

        $select->setRange($range);
        $tpl->tplAssign('role_write_select', $select->select());


        $private = $obj->get('private');
        $tpl->tplAssign('private_options', $view->getChecked(PrivateEntry::isPrivateRead($private)));
        $tpl->tplAssign('private_write_options', $view->getChecked(PrivateEntry::isPrivateWrite($private)));


        if($xajax) {
            $xajax->registerFunction(array('getCategoryRoles', $view, 'ajaxGetCategoryRoles'));
            $xajax->registerFunction(array('addParentRoles', $view, 'ajaxAddParentRoles'));
        }


        $tpl->tplSetNeeded('/category_add_from_parent_btn');

        $link = $view->controller->getFullLink($module, $page, false, 'role');
        $tpl->tplAssign('popup_link', $link);
        
        $tpl->tplAssign('confirm', ($obj && $obj->get('id')) ? 'true' : 'false');

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    // in category form
    // show private info for selected category
    static function ajaxGetCategoryRoles($category_id, $manager, $view, $sub_category = false) {

        $roles_msg = PrivateEntry::getPrivateCategoryMsg($manager, $category_id, $sub_category, $view->msg);

        $objResponse = new xajaxResponse();

        //$objResponse->addAlert($category_id);
        $objResponse->addAssign("category_info", "innerHTML", $roles_msg);

        return $objResponse;
    }


    // in category form assign roles from parent category
    static function ajaxAddParentRoles($category_id, $obj, $manager) {

        // updating and choose top category as parent
        // preserve private values
        if($obj->get('id') && $category_id == 0) {
            $category_id = $obj->get('id');
        }

        $objResponse = new xajaxResponse();
        //$objResponse->addAlert($category_id);

        $private_value = 0;
        if($category_id != 0) {
            $private_value = $manager->isPrivate($category_id);
        }

        $private = false;
        $roles_options = array();

        // read
        if(privateEntry::isPrivateRead($private_value)) {
            $private = true;
            $roles = $manager->role_manager->getSelectRangeFolow();
            $roles_ids = $manager->getRoleReadById($category_id);
            foreach($roles_ids as $id) {
                $roles_options[] = array('value' => $id, 'text' => $roles[$id]);
            }
        }

        $objResponse->addScriptCall('AssignPrivateReadFromParent', $private, $roles_options);


        // write
        $roles_options = array();

        if(privateEntry::isPrivateWrite($private_value)) {
            $private = true;
            if(empty($roles)) {
                $roles = $manager->role_manager->getSelectRangeFolow();
            }

            $roles_ids = $manager->getRoleWriteById($category_id);
            foreach($roles_ids as $id) {
                $roles_options[] = array('value' => $id, 'text' => $roles[$id]);
            }
        }

        $objResponse->addScriptCall('AssignPrivateWriteFromParent', $private, $roles_options);

        return $objResponse;
    }


    // get private category roles msg
    static function getPrivateCategoryMsg($manager, $category_id, $sub_category, $view_msg) {

        $roles = false;
        $msg = '';
        $is_private = $manager->isPrivate($category_id);

        if($is_private) {
            $private_msg = BaseView::getPrivateTypeMsg($is_private, $view_msg);
            $category_msg = $view_msg['private_category_msg'];

            $str = '<span style="color:#cc0000;"><b>%s</b> (%s)</span>';
            $msg = sprintf($str, $category_msg, $private_msg);

            $roles_follow = $manager->getRoleRangeFolow();
        }

        // read
        if(privateEntry::isPrivateRead($is_private)) {
            $roles = $manager->getRoleReadById($category_id, false);
            $msg .= PrivateEntry::_getPrivateCategoryRolesMsg($roles, $roles_follow, $view_msg, 'read');
        }

        // write
        if(privateEntry::isPrivateWrite($is_private)) {
            $roles = $manager->getRoleWriteById($category_id, false);
            $msg .= PrivateEntry::_getPrivateCategoryRolesMsg($roles, $roles_follow, $view_msg, 'write');
        }

        return $msg;
    }


    static function _getPrivateCategoryRolesMsg($roles, $roles_follow, $msg, $type) {

        $ret = '';
        $type_msg = ($type == 'read') ? $msg['private2_read_msg'] : $msg['private2_write_msg'];
        $type_msg = _strtoupper($type_msg);

        if($roles) {

            foreach($roles as $id) {
                $roles[$id] = $roles_follow[$id];
            }
            $roles = '- ' . implode('<br/>', $roles);

            $str = '<div>%s</div><div>%s</div>';
            $ret = sprintf($str, $type_msg, $roles);
        }

        return $ret;
    }




    // ENTRY FORM // ------------------

    // to set article, files, news private and assign roles
    static function getPrivateEntryBlock(&$xajax, $obj, $manager, $view, $module = 'knowledgebase', $page = 'kb_entry') {

        $tpl = new tplTemplatez(APP_MODULE_DIR . 'knowledgebase/entry/template/block_private_entry.html');

        $select = new FormSelect();
        $select->setSelectWidth(250);
        $select->select_tag = false;

        $roles = $manager->role_manager->getSelectRangeFolow(false, 0, ' :: ');

        // read
        $range = array();
        foreach($obj->getRoleRead() as $role_id) {
            $range[$role_id] = $roles[$role_id];
        }

        $select->setRange($range);
        $tpl->tplAssign('role_select', $select->select());

        // write
        $range = array();
        foreach($obj->getRoleWrite() as $role_id) {
            $range[$role_id] = $roles[$role_id];
        }

        $select->setRange($range);
        $tpl->tplAssign('role_write_select', $select->select());

        $private = $obj->get('private');
        $tpl->tplAssign('private_options', $view->getChecked(PrivateEntry::isPrivateRead($private)));
        $tpl->tplAssign('private_write_options', $view->getChecked(PrivateEntry::isPrivateWrite($private)));

        if($xajax) {
            $xajax->registerFunction(array('getCategoryPrivateInfo', $view, 'ajaxGetCategoryPrivateInfo'));
        }

        $link = $view->controller->getFullLink($module, $page, false, 'role');
        $tpl->tplAssign('popup_link', $link);
        
        $tpl->tplAssign('confirm', ($obj && $obj->get('id')) ? 'true' : 'false');

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    // ENTRY/CATEGORY FORM

    // get private category roles read msg on entry form
    static function getCategoryPrivateInfo($category_id, $category_title, $manager, $detail_view = false) {

        $roles = false;
        $html = '';
        $is_private = $manager->isPrivate($category_id);
        $msg = AppMsg::getMsg('user_msg.ini');

        if($is_private) {

            $roles_ret = '';
            $str = '<div>%s</div><div>%s</div>';
            $roles = $manager->getRoleById($category_id, false);

            if($roles) {
                $roles_range = $manager->getRoleRangeFolow();
                foreach($roles AS $rule => $v) {
                    if(!empty($v)) {
                        $roles_msg = array();
                        foreach($v as $id) {
                            $roles_msg[] = $roles_range[$id];
                        }

                        $mkey = "private2_{$rule}_msg";
                        $roles_msg = ' - ' . implode('<br> - ', $roles_msg);
                        $roles_ret .= sprintf($str, _strtoupper($msg[$mkey]), $roles_msg);
                    }
                }
            }

            $private_msg = BaseView::getPrivateTypeMsg($is_private, $msg);
            $category_msg = $msg['private_category_msg'];

            $html = array();
            $html[] = '<div class="privateCategoryDiv">';

            if($detail_view) {
                $title = '%s (%s): %s';
                $title = sprintf($title, $category_msg, $private_msg, $category_title);

                $html[] = '<div style="padding-bottom: 2px;">' . $title . '</div>';
                $html[] = '<div style="margin: 5px;padding-bottom: 6px;">';

            } else {
                $html[] = '<div style="padding-bottom: 2px;"><b>'. $category_title .':</b></div>';
                $html[] = '<div style="padding-bottom: 6px; padding-left: 15px;">';
                $str = '<span style="color:#cc0000;">%s (%s)</span>';
                $html[] = sprintf($str, $category_msg, $private_msg);
            }

            $html[] = '<div>'. $roles_ret .'</div>';
            $html[] = '</div>';
            $html[] = '</div>';

            $html = implode("\n\t", $html);
        }

        return $html;
    }


    // on entry form page
    static function ajaxGetCategoryPrivateInfo($category_id, $category_title, $manager) {

        $objResponse = new xajaxResponse();

        $manager = (isset($manager->cat_manager)) ? $manager->cat_manager : $manager;
        $html = PrivateEntry::getCategoryPrivateInfo($category_id, $category_title, $manager);

        if (strlen($html) > 0) {
            $objResponse->script('$("#category_private_content").show();');
            $objResponse->script('$("#category_toggle_title").show();');
            $objResponse->script('$("#category_private_content2").show();');

            $objResponse->addAppend('writeroot_private', 'innerHTML', $html);
            $objResponse->script('populateCategoryPrivateContent();');
        }

        return $objResponse;
    }


    // BULK FORM // --------------

    static function getPrivateBulkBlock($obj, $manager, $module = 'knowledgebase', $page = 'kb_entry') {

        $tpl = new tplTemplatez(APP_MODULE_DIR . 'knowledgebase/entry/template/block_private_bulk.html');

        $tpl->tplAssign('module', $module);
        $tpl->tplAssign('page', $page);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    // called in bulk action, to load private attributes for selected items
    static function ajaxLoadRoles($ids, $manager) {

        $objResponse = new xajaxResponse();

        // $objResponse->addAlert('selRoleHandler');

        $ids = implode(',', $ids);

        // read
        $roles = array();
        $roles_ids = $manager->getRoleReadById($ids);

        if($roles_ids) {
            $_roles = $manager->getRoleRangeFolow();
            foreach($roles_ids as $id) {
                $roles[] = array('value' => $id, 'text' => $_roles[$id]);
            }
        }

        $objResponse->addScriptCall('selRoleHandler.createSelectCategories', $roles);
        $objResponse->addScriptCall('ShowBulkRolesDiv', 'read', ($roles) ? true : false);


        // write
        $roles = array();
        $roles_ids = $manager->getRoleWriteById($ids);

        if($roles_ids) {
            $_roles = (empty($_roles)) ? $manager->getRoleRangeFolow() : $_roles;
            foreach($roles_ids as $id) {
                $roles[] = array('value' => $id, 'text' => $_roles[$id]);
            }
        }

        $objResponse->addScriptCall('selRoleWriteHandler.createSelectCategories', $roles);
        $objResponse->addScriptCall('ShowBulkRolesDiv', 'write', ($roles) ? true : false);


        return $objResponse;
    }


    // OTHER // ------------------

    static function getPrivateValue($private_arr) {
        $ret = 0;
        if($private_arr) {
            $read = (in_array('r', $private_arr)) ? 3 : 0;
            $write = (in_array('w', $private_arr)) ? 2 : 0;
            if($read && $write) {
                $ret = 1;
            } elseif($read) {
                $ret = $read;
            } else{
                $ret = $write;
            }
        }

        return $ret;
    }


    static function isPrivateRead($value) {
        return (in_array($value, array(1,3)));
    }


    static function isPrivateWrite($value) {
        return (in_array($value, array(1,2)));
    }


}
?>