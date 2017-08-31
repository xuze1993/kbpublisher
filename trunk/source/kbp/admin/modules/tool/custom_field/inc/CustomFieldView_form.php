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
require_once 'core/common/CommonEntryView.php';


class CustomFieldView_form extends AppView
{

    var $tmpl = 'form.html';


    function execute(&$obj, &$manager, &$manager2) {

        $this->addMsg('custom_field_msg.ini');

        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));


        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();

        $more_ajax = array('input_id' => $obj->get('input_id'), 'entry_type' => $obj->get('type_id'));
        $xajax->setRequestURI($this->controller->getAjaxLink('all', false, false, false, $more_ajax));
        
        $xajax->registerFunction(array('validate', $this, 'ajaxValidateFormCustom'));


        $skip_tabs = array('categories', 'range', 'validation', 'display_options');

        $select = new FormSelect();

        // checkbox special fields
        if ($obj->get('input_id') == 5) {
            $tpl->tplSetNeeded('/checkbox');

            $select->setSelectName('default_value');
            $select->setRange(array('1' => $this->msg['yes_msg'], '0' => $this->msg['no_msg']));
            $tpl->tplAssign('default_value_select', $select->select($obj->get('default_value')));
        }

        // not checkbox
        if ($obj->get('input_id') != 5) {
            $tpl->tplSetNeeded('/mandatory');
        }

        // radio
        if ($obj->get('input_id') == 7) {
            $obj->set('is_required', 1);
        }

        $tpl->tplAssign('required_ch', $this->getChecked($obj->get('is_required')));


        // range
        if ($manager2->isFieldTypeWithRange($obj->get('input_id'))) {
            unset($skip_tabs[1]);
            $tpl->tplSetNeeded('/range');

            $more = array('input_id' => $obj->get('input_id'));
            $link = $this->getLink('this', 'this', 'ft_range', false, $more);
            $tpl->tplAssign('range_popup_link', $link);

            $xajax->registerFunction(array('setRange', $this, 'ajaxSetRange'));

            if($this->controller->action == 'update') {
                if(!$manager->isFieldInUse($obj->get('id'), $obj->get('type_id'))) {
                    $tpl->tplSetNeeded('/range_set');
                }
            } else {
                $tpl->tplSetNeeded('/range_set');
            }

            if ($obj->get('range_id')) {
                $tpl->tplSetNeeded('/range_block');
                $tpl->tplAssign('range_block', $this->getRangeBlock($obj->get('range_id'), $obj->get('default_value')));
                $tpl->tplAssign('range_id_val', $obj->get('range_id'));

                if ($obj->get('input_id') == 2 || $obj->get('input_id') == 7) {
                    $tpl->tplSetNeeded('/radio_behavior');
                }
            } else {
                $tpl->tplAssign('range_id_val', 0);
            }
        }

        // validation
        if ($manager2->isFieldTypeWithValidation($obj->get('input_id'))) {
            unset($skip_tabs[2]);
            $tpl->tplSetNeeded('/validation');

            $xajax->registerFunction(array('setRegexp', $this, 'ajaxSetRegexp'));

            // valid regexp
            $regexps = array_keys(Validate::getRegexValues());
            $range = array_combine($regexps, $regexps);
            $select->setSelectName('common_regexp');
            $select->setRange($range);
            $tpl->tplAssign('common_regexp_select', $select->select());
            
            $error_message_sign_display = ($obj->get('valid_regexp')) ? 'inline' : 'none';
            $tpl->tplAssign('error_message_sign_display', $error_message_sign_display);
        }

        // display options
        if($this->display_option) {
            unset($skip_tabs[3]);
            $tpl->tplSetNeededGlobal('display_options');

            $select->setSelectName('display');
            $select->setRange($manager->getDisplayOptionSelectRange($this->display_option, $this->msg));
            $tpl->tplAssign('display_options_select', $select->select($obj->get('display')));

            // if top or bottom
            foreach(array(1,2) as $v) {
                if(in_array($v,  $this->display_option)) {
                    $tpl->tplSetNeededGlobal('display_options_template');
                    $tpl->tplAssign('ckeditor', $this->getEditor($obj->get('html_template'), 'custom_field', 'html_template'));
                    break;
                }
            }
            
            $tpl->tplAssign('search_ch', $this->getChecked($obj->get('is_search')));
        }

        if ($this->has_categories) {
            unset($skip_tabs[0]);
        }


        $tabs = $manager->getTabsRange($this->msg, $skip_tabs);
        foreach ($tabs as $id => $title) {
            $row['id'] = $id;
            $row['title'] = $title;
            $tpl->tplParse($row, 'tab_row');
        }

        // select tab if error occurs
        if (!empty($obj->errors)) {
            $error_field = $obj->errors['key'][0]['field'];
            if (is_array($error_field)) {
                $error_field = $error_field[0];
            }
            
            $selected_tab_id = $manager->error_fields_to_tab[$error_field];
            $tpl->tplSetNeeded('/selected_tab');
            $tpl->tplAssign('selected_tab_id', $selected_tab_id);
        }

        $field_type = $manager->getFieldTypeSelectRange($this->msg);
        $tpl->tplAssign('field_type', $field_type[$obj->get('input_id')]['title']);

        $js_str = '%d: ["%s", "%s"]';
        foreach ($manager->entry_type_to_url as $id => $entry_type) {
            if (in_array($id, $manager->entry_type_with_category) !== false) {
                $js_data[] = sprintf($js_str, $id, $entry_type[0], $entry_type[1]);
            }
        }
        $tpl->tplAssign('entry_type_to_url_params', implode(',', $js_data));

        // categories
        $module = '';
        $page = '';
        $categories = array();

        $select->select_tag = true;
        $select->setSelectName('category_id');

        if ($this->has_categories) {
            $tpl->tplSetNeeded('/categories');
            $tpl->tplSetNeeded('/select_handler');
            $tpl->tplSetNeeded('/submit_category');

            $categories = $this->stripVars($manager->getCategoryRecords($obj->get('type_id'), 'getSelectRangeFolow'));
            $module = $manager->entry_type_to_url[$obj->get('type_id')][0];
            $page = $manager->entry_type_to_url[$obj->get('type_id')][1];

        } else {
            $tpl->tplSetNeeded('/category_disabled');
            $tpl->tplSetNeeded('/submit_no_category');
        }
        
        $field_categories = $obj->getCategory(); // check for available
        if (!empty($field_categories)) {
            $displayed_categories = array();
            foreach ($field_categories as $category_id) {
                if (!empty($categories[$category_id])) {
                    $displayed_categories[] = $category_id;
                }
            }

            $obj->setCategory($displayed_categories);
        }

        $b_options = array(
            'no_button' => true,
            'hide_private' => true
            );
        $tpl->tplAssign('category_block_tmpl',
            CommonEntryView::getCategoryBlock($obj, $manager, $categories, $module, $page, $b_options));
        $select->setRange($categories);
        $tpl->tplAssign('category_select', $select->select());


        $tpl->tplAssign($this->setCommonFormVars($obj));
        $tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function getRangeBlock($id, $values = array(), $apply_window = false) {

        require_once APP_MODULE_DIR . 'tool/custom_field_range/inc/CustomFieldRangeGroupModel.php';
        require_once APP_MODULE_DIR . 'tool/custom_field_range/inc/CustomFieldRangeValueModel.php';

        $g_model = new CustomFieldRangeGroupModel;
        $v_model = new CustomFieldRangeValueModel;

        if (!empty($values)) {
            $values = explode(',', $values);
        }

        $tmpl = ($apply_window) ? 'range_block_apply.html' : 'range_block.html';

        $tpl = new tplTemplatez($this->template_dir . $tmpl);

        $range_info = $g_model->getById($id);
        $tpl->tplAssign('range_title', $range_info['title']);

        $v_model->setSqlParams('AND range_id = ' . $id);
        $v_model->setSqlParamsOrder('ORDER BY sort_order');
        $range = $v_model->getRecords();
        $range = RequestDataUtil::stripVars($range, array(), true);

        $values = (!is_array($values) ? array($values) : $values);
        foreach($range as $row) {
            $row['checked'] = (in_array($row['id'], $values)) ? 'checked' : '';
            $tpl->tplParse($row, 'row');
        }

        $tpl->tplAssign($this->msg);
        $tpl->tplParse();

        return $tpl->tplPrint(1);
    }
    
    
    function ajaxValidateFormCustom($values, $options = array()) {
        $objResponse = $this->ajaxValidateForm($values, $options);
        
        if (!empty($this->obj->errors)) {
            $error_field = $this->obj->errors['key'][0]['field'];
            if (is_array($error_field)) {
                $error_field = $error_field[0];
            }
            
            $selected_tab_id = $this->manager->error_fields_to_tab[$error_field];
            $script = "var index = $('#tab-%s').index() - 1;$('#tabs').tabs('option', 'active', index);";
            $script = sprintf($script, $selected_tab_id);
            
            $objResponse->script($script);
        }
        
        return $objResponse;
    }


    function ajaxSetRegexp($rule) {
        $objResponse = new xajaxResponse();

        $regexp = Validate::getRegex($rule);
        $objResponse->addAssign('valid_regexp', 'value', $regexp);
        
        $objResponse->script('$("#error_message_sign").show();');

        return $objResponse;
    }


    function ajaxSetRange($id, $input_id) {
        $objResponse = new xajaxResponse();

        $objResponse->assign('range_block', 'innerHTML', $this->getRangeBlock($id));
        $objResponse->assign('range_id', 'value', $id);

        // default value set radio behavior
        if ($input_id == 2 || $input_id == 7) {
            $objResponse->call('setRadioBehavior');
        }

        return $objResponse;
    }
}
?>