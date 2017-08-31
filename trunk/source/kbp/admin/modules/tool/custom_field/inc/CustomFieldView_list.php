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

class CustomFieldView_list extends AppView
{
        
    var $tmpl = 'list.html';
    
    
    function execute(&$obj, &$manager, $form_view) {
    
        $this->addMsg('custom_field_msg.ini');
    
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        $manager->setSqlParams('AND type_id = ' . $obj->get('type_id'));
        $manager->setSqlParams('AND active = 1');
        
        $total_num = $manager->getCountRecords();
                
        // filter sql
        $manager->setSqlParams($this->getFilterSql($manager), null, true);
        $manager->setSqlParams('AND type_id = ' . $obj->get('type_id'));
        
        // sort generate
        $manager->setSqlParamsOrder('ORDER BY sort_order asc');
        
        // get records
        $rows_by_group = $this->stripVars($manager->getRecordsByStatus());
        
        // header generate
        $button = array();
        $button[] = 'insert';

        // more options
        if($this->priv->isPriv('update')) {
            
            $pmenu = array();
            
            $disabled = (empty($total_num));
            $pmenu[] = array(
                'msg' => $this->msg['reorder_msg'], 
                'link' => 'javascript:xajax_getSortableList();void(0);',
                'disabled' => $disabled
                );

            $button['...'] = $pmenu;
        }

        
        $tpl->tplAssign('header', $this->commonHeaderList('', $this->getFilter($manager), $button, false)); 
        
        $entry_type_has_categories = (in_array($obj->get('type_id'), $manager->entry_type_with_category) !== false);
        if ($entry_type_has_categories) {
            $tpl->tplSetNeededGlobal('category');
        }
        
        if ($obj->get('type_id') != 20) {
            $tpl->tplSetNeededGlobal('searchable');
        }
        
        $field_type = $manager->getFieldTypeSelectRange($this->msg);
        
        
        $group_titles = array(
            1 => $this->msg['active_fields_msg'],
            0 => $this->msg['inactive_fields_msg']);
            
        $display_range = $manager->getDisplayOptionSelectRange(array(1,2,3,4), $this->msg);
		
        foreach ($rows_by_group as $active => $rows_by_display) {
            
			if(!$rows_by_display) {
				continue;
			}
			
            foreach ($rows_by_display as $display => $rows) {
                
                foreach($rows as $row) {
                
                    $obj->set($row);
                    
                    $tpl->tplAssign('field_type', $field_type[$row['input_id']]['title']);
                    
                    $obj->set('is_required', ($obj->get('is_required') ? '<img src="images/icons/bullet.svg" />' : ''));
                
                    if ($entry_type_has_categories) {
                        $tpl->tplAssign('has_categories', ($row['category_id']) ? '<img src="images/icons/bullet.svg" />' : '');
                    }
                    
                    $tpl->tplAssign('searchable', ($row['is_search']) ? '<img src="images/icons/bullet.svg" />' : '');
                    
                    $more = array('filter[q]'=>'custom_id:' . $row['id']);
                    $module = $manager->entry_type_to_url[$row['type_id']][0];
                    $page = $manager->entry_type_to_url[$row['type_id']][1];
                    
                    $entry_link = $this->getLink($module, $page, false, false, $more);
                    $tpl->tplAssign('entry_link', $entry_link);
                    
                    
                    $tpl->tplAssign($this->getViewListVarsJsCustom($obj->get('id'), $obj->get('active'), $obj->get('type_id')));
                    $tpl->tplParse(array_merge($obj->get(), $this->msg), 'group/display_group/row');
                }
                
                $tpl->tplSetNested('group/display_group/row');
                
                // feedback
                if ($obj->get('type_id') != 20) {
                    $tpl->tplSetNeeded('display_group/display_title');
                    $tpl->tplAssign('display_group_title', $display_range[$display]);
                }
                
                $tpl->tplParse($this->msg, 'group/display_group');
            }
            
            $tpl->tplSetNested('group/display_group');
            
            $v['group_title'] = $group_titles[$active];
            $tpl->tplAssign($this->parseTitle());
            $tpl->tplParse(array_merge($v, $this->msg), 'group');
        }
        
        //xajax
        $this->form_view = $form_view;
        
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();
        
        $xajax->registerFunction(array('getSortableList', $this, 'ajaxSetSortableList'));
        
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    } 
    
    
    function getViewListVarsJsCustom($id, $active, $entry_type) {
        
        $actions = array('status', 'update', 'delete');
        
        if($entry_type != 20) {
            $actions['detail'] = array(
                'link' => $this->getActionLink('apply', $id),
                'msg'  => $this->msg['apply_msg']
            );
        }
        
        $row = parent::getViewListVarsJs($id, $active, 1, $actions);
        $row['class'] = 'trLighter'; // rows colors
        
        return $row;
    }
    
        
    function parseTitle() {
        $values = array();
        $values['is_required_msg'] = $this->shortenTitle($this->msg['is_required_msg'], 3);
        $values['category_msg'] = $this->shortenTitle($this->msg['category_msg'], 3);
        $values['searchable_msg'] = $this->shortenTitle($this->msg['searchable_msg'], 3);
        return $values;
    }
    
    
    function getFilter($manager) {

        @$values = $_GET['filter'];
        if(isset($values['q'])) {
            $values['q'] = RequestDataUtil::stripVars($values['q'], array(), true);
            $values['q'] = trim($values['q']);
        }
        
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
    
        $select = new FormSelect();
        $select->select_tag = false;
        
        // field type
        $field_type = $manager->getFieldTypeSelectRange($this->msg);
        $range = array();
        
        foreach ($field_type as $id => $field) {
            $range[$id] = $field['title'];
        }
        
        $select->setRange($range, array('all'=>'__'));
        $field_type_id = (isset($values['input_id'])) ? $values['input_id'] : 'all';
        $tpl->tplAssign('field_type_select', $select->select($field_type_id));
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse(@$_GET['filter']);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql(&$manager) {
        
        // filter
        $arr = array();
        @$values = $_GET['filter'];
        
        // field type
        @$v = $values['input_id'];
        if($v != 'all' && !empty($v)) {
            $v = (int) $v;
            $arr[] = "AND input_id = '$v'";
        }
        
        // search str
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);
            if($ret = $this->isSpecialSearchStr($v)) {
                
                if($ret['rule'] == 'range_id') {
                    $arr[] = sprintf("AND range_id = '%d'", $ret['val']);
                }
            
            } else {
                $v = addslashes(stripslashes($v));
                $arr[]  = "AND (title LIKE '%{$v}%')";
            }
            
        }
        
        return implode(" \n", $arr);
    }
    
    
    function isSpecialSearchStr($str) {
        
        if($ret = parent::isSpecialSearchStr($str)) {
            return $ret;
        }
        
        $search['range_id'] = "#^range_id:(\d+)$#";
        
        return $this->parseSpecialSearchStr($str, $search);
    }
    
    
    function ajaxSetSortableList() {
        
        $objResponse = new xajaxResponse();
        
        $tpl = new tplTemplatez($this->template_dir . 'list_sortable.html');
        
        $display_range = $this->manager->getDisplayOptionSelectRange($this->form_view->display_option, $this->msg);
        
        if (empty($display_range)) { // feedback
            $display_range = array(0 => '');
        }
        
        $rows = $this->manager->getRecordsByStatus();
        foreach ($display_range as $display => $title) {
            $v = array();
            
            if (!empty($rows[1][$display])) {
                foreach($rows[1][$display] as $row) {
                    $tpl->tplParse($row, 'display_group/row');
                }
            } else {
                $v['class'] = 'custom_sortable_empty';
            }
            
            $tpl->tplSetNested('display_group/row');
            
            $v['display_id'] = $display;
            $v['display_title'] = $title;
            $tpl->tplParse(array_merge($v, $this->msg), 'display_group');
        }
        
        $cancel_link = $this->controller->getCommonLink();
        $tpl->tplAssign('cancel_link', $cancel_link);
        
        $tpl->tplParse($this->msg);        
        $objResponse->addAssign('field_list', 'innerHTML', $tpl->tplPrint(1));
        
        $objResponse->call('initSort');
    
        return $objResponse;    
    }
}
?>