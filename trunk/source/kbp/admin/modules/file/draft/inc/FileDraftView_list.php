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

require_once APP_MODULE_DIR . '/knowledgebase/draft/inc/KBDraftView_list.php';


class FileDraftView_list extends KBDraftView_list
{

    var $tmpl = 'list.html';


    function execute(&$obj, &$manager, $emanager) {

        $this->addMsg('user_msg.ini');
        $this->addMsgPrepend('common_msg.ini', 'knowledgebase');

        $template_dir = APP_MODULE_DIR . 'knowledgebase/draft/template/';
        
        $tmpl = ($this->controller->getMoreParam('popup')) ? $this->template_popup : $this->template;

        $tpl = new tplTemplatez($template_dir . $tmpl);

        // bulk
        $manager->bulk_manager = new FileDraftModelBulk;
        if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv)) {
            $tpl->tplSetNeededGlobal('bulk');
            $tpl->tplAssign('footer', $this->controller->getView($obj, $manager, 'FileDraftView_bulk', $this));
        }

        // all assignees in drafts
        $assignee_all = array();
        $assignee_all_ids = $manager->getAssigneeList();
        if(!empty($assignee_all_ids)) {
            $assignee_all = $manager->getUser(implode(',', $assignee_all_ids), false);
        }

        // filter sql
        $params = $this->getFilterSql($manager, $emanager);
        $manager->setSqlParams($params['where']);
        $manager->setSqlParamsSelect($params['select']);
        $manager->setSqlParamsFrom($params['from']);

        $manager->setSqlParams('AND d.entry_type = ' . $emanager->entry_type);

        // header generate
        $count = (isset($params['count'])) ? $params['count'] : $manager->getCountRecords();
        $bp = &$this->pageByPage($manager->limit, $count);

        $add_button = ($this->controller->getMoreParam('popup') == 'text') ? false : true;
        $tpl->tplAssign('header',
            $this->commonHeaderList($bp->nav, $this->getFilter($manager, $assignee_all), $add_button));

        // sort generate
        $sort = &$this->getSort();
        $psort = (isset($params['sort'])) ? $params['sort'] : $sort->getSql();
        $manager->setSqlParamsOrder($psort);

        // get records
        $offset = (isset($params['offset'])) ? $params['offset'] : $bp->offset;
        $rows = $manager->getRecords($bp->limit, $offset);
        $rows = $this->stripVars($rows, array('entry_obj', 'workflow_action'));

        $step_ids = $manager->getValuesArray($rows, 'last_event_id');
        $step_ids = array_filter($step_ids);
        $entry_assignees = ($step_ids) ? $manager->getAssigneeByStepIds($step_ids) : array();
        $entry_assignees = $this->stripVars($entry_assignees);

        $status = $manager->getDraftStatusData();
        $tooltip_str = 'class="_tooltip" title="%s"';
        
        $tpl->tplAssign('type', 'File');

        foreach($rows as $row) {

            $obj->set($row);

            $formated_date_posted = $this->getFormatedDate($row['date_posted'], 'datetime');
            $tpl->tplAssign('formated_date_posted', $formated_date_posted);

            $interval_date_posted = $this->getTimeInterval($row['date_posted']);
            $tpl->tplAssign('interval_date_posted', $interval_date_posted);

            $formated_date_updated = '--';
            $interval_date_updated = '';
            $ddiff = strtotime($row['date_updated']) - strtotime($row['date_posted']);
            if($ddiff > 60) {
                $formated_date_updated = $this->getFormatedDate($row['date_updated'], 'datetime');
                $interval_date_updated = $this->getTimeInterval($row['date_updated']);
            }

            $tpl->tplAssign('formated_date_updated', $formated_date_updated);
            $tpl->tplAssign('interval_date_updated', $interval_date_updated);

            // author
            $row['author'] = '--';
            if (!empty($row['first_name'])) {
                $name = PersonHelper::getShortName($row);
                if($row['author_id'] != AuthPriv::getUserId()) {
                    $more = array('id' => $row['author_id']);
                    $link = $this->getLink('users', 'user', false, 'detail', $more);
                    $row['author'] = sprintf('<a href="%s">%s</a>', $link, $name);
                } else {
                    $row['author'] = $name;
                }
            }

            // title
            $title = $obj->get('title');
            $tpl->tplAssign('short_title', $this->getSubstringStrip($title, 30));


            // article id
            $tpl->tplAssign('entry_id', ($row['entry_id']) ? $row['entry_id'] : '--');

            // actions/links
            $links = array();
            $links['approval_link'] = $this->getActionLink('approval', $row['id']);
            $links['file_link'] = $this->getActionLink('file', $obj->get('id'));
            $links['fopen_link'] = $this->getActionLink('fopen', $obj->get('id'));
            $links['preview_link'] = $links['file_link'];

            $assignees = array();
            $row['assignee_tip'] = '';
            $row['step'] = '--';
            $status_id = 1;
            $being_approved = $manager->isBeingApprovedByRow($row);
            if (!empty($row['last_event_id'])) { // being approved
                $status_id = $row['last_event_action'] + 1;

                // tooltip
                $tooltip = array();
                if (!empty($entry_assignees[$row['last_event_id']])) {
                    $assignees = $entry_assignees[$row['last_event_id']];
                    foreach($assignees as $assignee_id) {
                        if(isset($assignee_all[$assignee_id])) {
                            $tooltip[] = PersonHelper::getEasyName($assignee_all[$assignee_id]);
                        }
                    }

                    $row['assignee_tip'] = sprintf($tooltip_str, $this->stripVars(implode('<br/>', $tooltip)));
                }

                if ($being_approved) {
                    $actions = unserialize($row['workflow_action']);
                    $row['step'] = sprintf('%d / %d', $row['step_num']-1, count($actions));
                }
            }

            $tpl->tplAssign('status', $status[$status_id]['title']);
            $tpl->tplAssign('status_color', $status[$status_id]['color']);
            $tpl->tplAssign('assignee_num', ($assignees) ? count($assignees) : '--');

            $actions = $this->getListActions($obj, $links, $manager, $being_approved, $assignees);
            $tpl->tplAssign($this->getViewListVarsJsCustom($obj->get(), $actions, $manager));

            $tpl->tplParse($row, 'row');
        }

        if ($this->controller->getMoreParam('popup')) {
            $menu_msg = AppMsg::getMenuMsgs('file');
            $tpl->tplAssign('popup_title', $menu_msg['file_draft']);
        }

        $this->msg['entry_title_msg'] = $this->msg['filename_msg'];

        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
        $tpl->tplAssign($this->parseTitle());

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function getViewListVarsJsCustom($entry, $actions, $manager) {

        $own_record = ($entry['author_id'] == $manager->user_id);
        $row = $this->getViewListVarsJs($entry['id'], 1, $own_record, $actions);

        $row['preview_link'] = $actions['file']['link'];

	    // double click link
        if(isset($actions['approve'])) {
            $row['update_link'] = $actions['approve']['link'];
        }

        return $row;
    }


    function getListActions($obj, $links, $manager, $being_approved, $assignee) {

        $actions = parent::getListActions($obj, $links, $manager, $being_approved, $assignee);
        unset($actions['preview']);

        $actions['file'] = array(
            'msg'  => $this->msg['download_msg'],
            'link' => $links['file_link']);

        $actions['fopen'] = array(
            'msg'  => $this->msg['open_msg'],
            'link' => $links['fopen_link'],
            'link_attributes'  => 'target="_blank"');

        return $actions;
    }


    function &getSort() {

        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        $sort->setCustomDefaultOrder('date_posted', 1);
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);

        $sort->setSortItem('posted_msg', 'date_posted', 'd.date_posted', $this->msg['posted_msg'], 2);
        $sort->setSortItem('updated_msg', 'date_updated', 'd.date_updated', $this->msg['updated_msg']);
        $sort->setSortItem('filename_msg', 'title', 'd.title', $this->msg['filename_msg']);

        return $sort;
    }

}
?>