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


class CustomFieldRangeGroupView_list extends AppView
{

    var $template = 'list_group.html';
    var $template_popup = 'list_group_popup.html';


    function execute(&$obj, &$manager) {

        $this->addMsg('custom_field_msg.ini');

        $tmpl = ($this->controller->getMoreParam('popup')) ? $this->template_popup :  $this->template;
        $tpl = new tplTemplatez($this->template_dir . $tmpl);

        // header generate
        $bp =& $this->pageByPage($manager->limit, $manager->getRecordsSql());
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav));

        // sort generate
        $sort = &$this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());

        // get records
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        $ids = $manager->getValuesString($rows, 'id');

        $values_num = ($ids) ? $manager->getValuesNum($ids) : array();
        $fields_num = ($ids) ? $manager->getReferencedFieldsNum($ids) : array();
        $entry_type_msg = AppMsg::getMsg('ranges_msg.ini', false, 'record_type');

        // list records
        foreach(array_keys($rows) as $k) {
            $row = $rows[$k];
            
            $row['fields_num'] = '--';
            $row['fields_num_tip'] = '';
            $tip_str = '<b>%s</b>: <a href=\'%s\'>%d</a>';
            
            $in_use = isset($fields_num[$row['id']]);
            
            if($in_use && !$this->controller->getMoreParam('popup')) {
                
                $tpl->tplSetNeeded('row/field_num_tip');
                $fields_num_tip = array();
                foreach ($fields_num[$row['id']] as $entry_type => $field_num) {
                    $entry_type_key = $manager->record_type[$entry_type];
                    $more = array('filter[q]'=>'range_id:' . $row['id']);
                    $link = $this->getLink('tool', 'field_tool', 'ft_' . $entry_type_key, false, $more);
                    
                    $fields_num_tip[] = sprintf($tip_str, $entry_type_msg[$entry_type_key], $link, $field_num);  
                }
                
                $row['fields_num'] = array_sum($fields_num[$row['id']]);
                $row['fields_num_tip'] = implode('<br/>', $fields_num_tip);
            }
            
            $more = array('filter[q]'=>'range_id:' . $row['id']);
            $field_link = $this->getLink('this', 'this', 'field_tool', false, $more);
            $tpl->tplAssign('field_link', $field_link);

            if ($this->controller->getMoreParam('popup') && isset($values_num[$row['id']])) {
				$tpl->tplSetNeeded('row/assign');
            }

            $tpl->tplAssign($this->getViewListVarsJsCustom($row['id']));
            $tpl->tplParse(array_merge($row, $this->msg), 'row');
        }

        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    // reassign for use with parents
    // used it to set links such as delete, update
    function getViewListVarsJsCustom($record_id = false, $active = false, $own_records = true) {

        $actions = array(
            'update' => array('link' => $this->getActionLink('update_group', $record_id)),
            'delete' => array('link' => $this->getActionLink('delete_group', $record_id))
        );

        $more = array('range_id' => $record_id);
        if ($this->controller->getMoreParam('popup')) {
            $more['popup'] = 1;
        }

        $actions['load'] = array(
            'link' => $this->controller->getLink('this', 'this', 'this', false, $more),
            'msg' => $this->msg['view_items_msg']
            );

        $row = parent::getViewListVarsJs($record_id, $active, 1, $actions);
        $row['load_link'] = $actions['load']['link'];
        $row['load_img'] = $this->getImgLink($row['load_link'], 'info', $this->msg['view_items_msg']);

        return $row;
    }


    function &getSort() {

        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);

        $sort->setSortItem('title_msg',  'title', 'title',  $this->msg['title_msg']);

        return $sort;
    }
}
?>