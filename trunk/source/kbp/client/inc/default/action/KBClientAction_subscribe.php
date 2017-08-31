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

// this action executes only after request to subscribe
// but user not logged

require_once APP_MODULE_DIR . 'user/subscription/inc/SubscriptionModel.php';


class KBClientAction_subscribe extends KBClientAction_common
{

    function &execute($controller, $manager) {

        $entry_type = (isset($_GET['t'])) ? (int) $_GET['t'] : 0;

        if(!$manager->is_registered) {
            $more = array('t' => $entry_type);
            $controller->go('login', false, $this->entry_id, 'subscribe', $more);
        }

        $smanager = new SubscriptionModel();

        if(!in_array($entry_type, array_keys($smanager->types))) {
            $controller->go();
        }


        if($entry_type == 1) {
            $url = array('entry', false, $this->entry_id);

        } elseif($entry_type == 2) {
            $url = array('files', false, false);

        } elseif($entry_type == 11) {
            $url = array('index', $this->entry_id, false);

        } elseif($entry_type == 12) {
            $url = array('files', $this->entry_id, false);
        }


        // not allowed, could be for user without any priv
        if(!$manager->isSubscribtionAllowed()) {
            $controller->go($url[0], $url[1], $url[2], 'subscription_not_allowed');
        }

        // not allowed to concrete category if susbcribel to all
        if($entry_type == 11 || $entry_type == 12) {
            if($manager->isEntrySubscribedByUser(0, $entry_type)) {
                $controller->go($url[0], $url[1], $url[2]);
            }
        }

        if($this->entry_id) {
            $entry_id = (int) $this->entry_id;
            $user_id = (int) $manager->user_id;
            $type = array($entry_type);

            $smanager->saveSubscription(array($entry_id), $type, $user_id);
        }

        // $link = $controller->getLink($url[0], $url[1], $url[2]);
        // echo '<pre>link: ', print_r($link, 1), '</pre>';
        // exit;

        $controller->go($url[0], $url[1], $url[2]);
    }

}
?>