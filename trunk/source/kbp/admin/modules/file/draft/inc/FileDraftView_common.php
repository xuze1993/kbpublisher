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


class FileDraftView_common
{

    static function getEntryMenu($obj, $manager, $view, $emanager) {

        $tabs = array('update', 'detail');

        $entry = $obj->get();
        $own_record = ($entry['author_id'] == $manager->user_id);
        $record_id = $obj->get('id');

        // open
        $tabs['fopen'] = array(
            'link' => $view->getActionLink('fopen', $record_id),
            'title'  => $view->msg['open_msg'],
            'options'  => array('target' => '_blank')
        );

        // download
        $tabs['download'] = array(
            'link' => $view->getActionLink('file', $record_id),
            'title'  => $view->msg['download_msg']
        );

        // file text, not implemented
        // $tabs['filetext'] = array(
        //     'link' => $view->getActionLink('text', $record_id),
        //     'title'  => $view->msg['filetext_msg']
        // );

        // approval log
        $approval_log = $manager->getApprovalLog($obj->get('id'));
        if (!empty($approval_log)) {
            $tabs['approval_log'] = array(
                'link' => $view->getActionLink('approval_log', $record_id),
                'title'  => $view->msg['workflow_log_msg']
            );
        }

        // approval
        if ($manager->checkForApproval($record_id, 'approval')) {
            $tabs['approval'] = array(
                'link' => $view->getActionLink('approval', $record_id),
                'title'  => $view->msg['review_msg']
            );
        }

        if (!$manager->checkForApproval($record_id, 'update')) {
            $index = array_search('update', $tabs);
            unset($tabs[$index]);
        }

        // menu
        $options['more'] = array();

        if($obj->get('entry_id')) {
            $options['more']['entry'] = array(
                'link' => $view->getLink('file', 'file_entry', false, 'detail', array('id' => $obj->get('entry_id'))),
                'title'  => $view->msg['view_file_msg']
            );
        }

        $options['more'][] = 'delete';

        return $view->getViewEntryTabs($entry, $tabs, $own_record, $options);
    }
}
?>