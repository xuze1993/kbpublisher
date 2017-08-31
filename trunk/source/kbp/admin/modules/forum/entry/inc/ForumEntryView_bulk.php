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

require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryView_bulk.php';

class ForumEntryView_bulk  extends KBEntryView_bulk
{

	var $tmpl = 'form_bulk.html';


	function execute(&$obj, &$manager, $view) {

		// $manager->bulk_manager->getActionsMsg('bulk_kbentry');
		$this->addMsg('bulk_msg.ini', false, 'bulk_common');
		$this->addMsg('user_msg.ini');
		$this->addMsgOnOtherModule('common_msg.ini', 'knowledgebase');

		$tpl = new tplTemplatez($this->template_dir . $this->tmpl);

		$select = new FormSelect();
		$select->select_tag = false;

        //xajax
        $ajax = &$this->getAjax($obj, $manager);
        $xajax = &$ajax->getAjax();

        // tag
        $items = array('add', 'set', 'remove');
        $range = $manager->bulk_manager->getSubActionSelectRange($items, 'bulk_tag');
        $select->setRange($range);
        $tpl->tplAssign('tag_action_select', $select->select());

        $link = $this->getLink('news', 'news_entry', false, 'tags');
        $tpl->tplAssign('block_tag_tmpl', CommonEntryView::getTagBlock($obj->getTag(), $link));

		// private
		if($manager->bulk_manager->isActionAllowed('private')) {
			$tpl->tplSetNeeded('/private');
            $tpl->tplAssign('block_private_tmpl', PrivateEntry::getPrivateBulkBlock($obj, $manager));
		} else {
			$manager->bulk_manager->removeActionAllowed('private');
		}


		// status
		$status_range = array();
		if($manager->bulk_manager->isActionAllowed('status')) {
			$extra_range = array();
			$range = $manager->getListSelectRange('forum_status', true);
			$range = $this->priv->getPrivStatusSet($range, 'select');
			$status_range = &$range;

			if($range || $extra_range) {
				$select->setRange($range, $extra_range);
				$tpl->tplAssign('status_select', $select->select());
			} else {
				$manager->bulk_manager->removeActionAllowed('status');
			}
		}

        // sticky
        $range = array(1 => $this->msg['yes_msg'],
                       0 => $this->msg['no_msg']);

        $select->setRange($range);
        $tpl->tplAssign('sticky_select', $select->select());


		// schedule
		if($manager->bulk_manager->isActionAllowed('schedule') && $status_range) {
			$tpl->tplAssign('block_schedule_tmpl', CommonEntryView::getScheduleBlock($obj, $status_range, true));
		} else {
			$manager->bulk_manager->removeActionAllowed('schedule');
		}

        $xajax->registerFunction(array('parseCutomBulkAction', $this, 'ajaxParseCutomBulkAction'));
        $xajax->registerFunction(array('addTag', $this, 'ajaxAddTag'));
        $xajax->registerFunction(array('getTags', $this, 'ajaxGetTags'));

		// actions js
		$actions_allowed = $manager->bulk_manager->getActionsAllowed();
		$tpl->tplAssign('bulk_actions', "'" . implode("','",($actions_allowed)) . "'");

		// action
		@$val = $values['action'];
        $range = $manager->bulk_manager->getActionsMsg('bulk_kbentry');
		$select->setRange($range, array('none' => $this->msg['with_checked_msg']));
		$tpl->tplAssign('action_select', $select->select($val));


		$tpl->tplAssign($this->setCommonFormVarsFilter());
		$tpl->tplAssign($this->msg);

		$tpl->tplParse();
		return $tpl->tplPrint(1);
	}


    function ajaxAddTag($string) {
        return CommonEntryView::ajaxAddTag($string, $this->manager);
    }


    function ajaxGetTags($limit = false, $offset = 0) {
        return CommonEntryView::ajaxGetTags($limit, $offset, $this->manager);
    }
}
?>