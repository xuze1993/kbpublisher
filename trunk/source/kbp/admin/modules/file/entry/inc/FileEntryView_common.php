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

require_once APP_MODULE_DIR . '/knowledgebase/entry/inc/KBEntryView_common.php';


class FileEntryView_common extends KBEntryView_common
{
    
    static function getEntryMenu($obj, $manager, $view) {

        $tabs = array('update', 'detail');

        $entry = $obj->get();
        $status = $obj->get('active');
        $record_id = $obj->get('id');
        $own_record = ($entry['author_id'] == $manager->user_id);
        
        $entry['title'] = $obj->get('filename'); // for title in form
        
        
        $tabs['fopen'] = array(
            'link' => $view->getActionLink('fopen', $record_id),
            'title'  => $view->msg['open_msg'],
            'options'  => array('target' => '_blank')
        );
            
        $tabs['download'] = array(
            'link' => $view->getActionLink('file', $record_id),
            'title'  => $view->msg['download_msg']
        );
        
        
        if($view->isEntryUpdateable($record_id, $status, $own_record)) {
            if(!$view->priv->isPrivOptional('update', 'draft')) {
                $tabs['filetext'] = array(
                    'link' => $view->getActionLink('text', $record_id),
                    'title'  => $view->msg['filetext_msg']
                );
            }
        }

        
        // approval
        $approval_log = $manager->isApprovalLogAvailable($obj->get('id'));
        if (!empty($approval_log)) {
            $tabs['approval'] = array(
                'link' => $view->getActionLink('approval_log', $record_id),
                'title'  => $view->msg['workflow_log_msg']
            );
        }
        
        // menu
        $options['more'] = KBEntryView_common::getMoreMenu($obj, $manager, $view, 'file_draft');
        if($options['more']) {
            $options['more']['delete'] = array(
                'title'  => $view->msg['delete_msg']
            );
        }
        
        // if some of categories is private
        // and user do not have this role so he can't access to some actions
        $has_private = $manager->isCategoryNotInUserRole($obj->getCategory());
        if($has_private) {
            unset($tabs[array_search('update', $tabs)]);
            unset($tabs['approval']);
            unset($tabs['filetext']);
            $options['more'] = array();
        }
        
        return $view->getViewEntryTabs($entry, $tabs, $own_record, $options);
    }
    
    
    static function getUpdateAsDraftLink($view, $entry_id) {
        $link = parent::getUpdateAsDraftLink($view, $entry_id);
        $link = str_replace('kb_draft', 'file_draft');
        return $link;
    }
}
?>