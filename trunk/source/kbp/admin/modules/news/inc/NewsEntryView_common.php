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


class NewsEntryView_common
{

    static function getEntryMenu($obj, $manager, $view) {

        $tabs = array('update', 'detail');

        $entry = $obj->get();
        $own_record = ($entry['author_id'] == $manager->user_id);
        $record_id = $obj->get('id');


        // preview
        $link = $view->getActionLink('preview', $record_id);
        $tabs['preview'] = array(
            'link' => sprintf("javascript:PopupManager.create('%s', 'r', 'r', 2);", $link),
            'title'  => $view->msg['preview_msg']
        );

        // public
        if($entry['active'] == 1) {
            $client_controller = &$view->controller->getClientController();
            $tabs['public'] = array(
                'link' => $client_controller->getLink('news', false, $record_id),
                'title'  => $view->msg['entry_public_link_msg'],
                'options'  => array('target' => '_blank')
            );
        }

        // back button
        $options = array();
        if($referer = @$_GET['referer']) {
            if(strpos($referer, 'client') !== false) {
                $client_link = array('news', false, $record_id);
                $back_link = $view->controller->getClientLink($client_link);
                $options['back_link'] = $back_link;
            }
        }

        // menu
        if($view->priv->isPriv('delete')) {
            $options['more'] = array('clone', 'delete');
        }

        return $view->getViewEntryTabs($entry, $tabs, $own_record, $options);
    }
}
?>