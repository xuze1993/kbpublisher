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


class ListValueView_list extends AppView
{

    var $tmpl = 'list_value.html';


    function execute(&$obj, &$manager) {

        $this->addMsg('setting_msg.ini');


        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);

        // header generate
        $tpl->tplAssign('header', $this->titleHeaderList(false, $obj->group_title));

        // sort generate
        $sort = &$this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());

        // get records
        $rows = $this->stripVars($manager->getRecords());
        $rows_msg = ParseListMsg::getValueMsg($obj->group_key);

        // list records
        foreach($rows as $row) {

            $obj->set($row);
            $this->setTitle($obj, $rows_msg);

            $tpl->tplAssign($this->getViewListVarsJsCustom($obj->get('id'), $obj->get('active'), $obj));
            $tpl->tplParse(array_merge($obj->get(), $this->msg), 'row');
        }

        // user
        if($obj->get('list_id') == 4) {
            $this->msg['list_entry_status_msg'] = $this->msg['list_user_status_msg'];
        }

        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function &getSort() {

        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);

        $sort->setSortItem('', 'sort', 'sort_order', '', 1);

        return $sort;
    }


    function setTitle(&$obj, $msg) {
        if(!$obj->get('title')) {
            $obj->set('title', $msg[$obj->get('list_key')]);
        }
    }


    function getViewListVarsJsCustom($record_id = false, $active = false, $obj = true) {

        $entry_active = $obj->get('custom_3');
        $predifined = $obj->get('predifined');
        
        $actions = array(
            'update' => true, 
            'delete' => true
            );
        
        // locked
        if($predifined == 2) {
            $actions['update'] = false;
        }

        if($predifined) {
            $actions['delete'] = false;
        }
        
        $row = $this->getViewListVarsJs($record_id, $active, 1, $actions);


        // entry active link
        $act = $entry_active;
        $active_img = ($act == 0) ? 'active_0' : 'active_1';
        $row['active_entry_img'] = $this->getImgLink(false, $active_img, false);

        // entry default
        $default = $obj->get('custom_4');
        $default_img = ($default == 0) ? 'active_0' : 'active_1';
        $row['default_img'] = $this->getImgLink(false, $default_img, false);

        // active item
        $active_img = ($active == 0) ? 'active_0' : 'active_1';
        $row['active_img'] = $this->getImgLink(false, $active_img, $this->msg['set_status_msg']);

        return $row;
    }
    
}
?>