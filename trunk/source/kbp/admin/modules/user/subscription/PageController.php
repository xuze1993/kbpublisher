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

$controller->loadClass('Subscription');
$controller->loadClass('SubscriptionModel');
$controller->loadClass('SubscriptionView_list');
$controller->loadClass('SubscriptionView_form');


// initialize objects
$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);

$obj = new Subscription;

$manager =& $obj->setManager(new SubscriptionModel());

// subscription, set inactive in settings table
//$manager->setting['allow_subscribe_news'] = 2;  // all registered
//$manager->setting['allow_subscribe_entry'] = 2; // all registered


// add this to not strip such actions/buttons
$priv->setPrivArea('account_subscribe');
$priv->addPriv('insert');
$priv->addPriv('delete');
//$manager->checkPriv($priv, $controller->action, @$rq->id);

$controller->setMoreParams('type');

$uid = AuthPriv::getUserId();


// type of subscription
if (isset($rq->type)) {
    if ($manager->types[$rq->type]) {
        $list_type = $manager->types[$rq->type];
    } else {
        $list_type = 'types';
    }
}


switch ($controller->action) {
case 'delete': // ------------------------------
    $entry_id = (int) $rq->id;
    $entry_type = (int) $rq->type;

    $manager->deleteSubscription($entry_id, $entry_type, $uid);

    $controller->go();

    break;


case 'update': // ------------------------------
case 'insert': // ------------------------------

    // news
    if ($rq->type == 3) {
        $is_subsc = $rp->news_subsc;
        $values = array (0, 3, $uid);

        if (!$is_subsc) {
            $manager->saveSubscription(array(0), 3, $uid);
        } else {
            $manager->deleteSubscription(0, 3, $uid);
        }

        $controller->removeMoreParams('type');
        $controller->go();


    // category
    } else {
    //if ($rq->type == 11 || $rq->type == 12) {

        if(isset($rp->filter)) {

            $is_error = $obj->validate($rp->vars, array('subscriptions'));

            if($is_error) {
                $rp->stripVars(true);
                $obj->set($rp->vars);

            } else {

                //$rp->stripVars();
                //$obj->set($rp->vars);

                // all
                if(in_array('0', $rp->subscriptions)) {
                    $manager->deleteByEntryType($rq->type, $uid);
                    $manager->saveSubscription(array(0), $rq->type, $uid);

                // selected
                } else {
                    $subs = $manager->getSubscription($rq->type, $uid);
                    $subs = array_merge($subs, $rp->subscriptions);
                    $subs = $manager->parseCategories($subs, $rq->type);

                    if($subs['remove']) {
                        $manager->deleteSubscription($subs['remove'], $rq->type, $uid);
                    }

                    $manager->deleteSubscription(0, $rq->type, $uid);
                    $manager->saveSubscription($subs['add'], $rq->type, $uid);
                }

                $controller->go();
            }
        }
    }

    $subsc = 'SubscriptionView_form_' . $list_type;
    $view = $controller->getView($obj, $manager, $subsc);

    break;


default: // ------------------------------------

    if(!empty($rq->type)) {
         $view = 'SubscriptionView_list_' . $list_type;
        $view = $controller->getView($obj, $manager, $view);
    } else {
         $view = $controller->getView($obj, $manager, 'SubscriptionView_types');
    }
}
?>