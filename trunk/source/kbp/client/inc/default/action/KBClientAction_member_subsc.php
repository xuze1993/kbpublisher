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

require_once 'core/base/BaseObj.php';
require_once 'core/app/AppObj.php';
require_once 'core/app/AppView.php';

require_once APP_MODULE_DIR . 'user/subscription/inc/Subscription.php';
require_once APP_MODULE_DIR . 'user/subscription/inc/SubscriptionModel.php';
require_once APP_MODULE_DIR . 'user/subscription/inc/SubscriptionView_list.php';
require_once APP_MODULE_DIR . 'user/subscription/inc/SubscriptionView_form.php';
require_once APP_MODULE_DIR . 'user/subscription/inc/SubscriptionView_types.php';


class KBClientAction_member_subsc extends KBClientAction_common
{

    function &execute($controller, $manager) {

        // if remote
        if(AuthPriv::isRemote()) {
            $controller->go();
        }

        // check if registered
        if(!$manager->is_registered) {
            $controller->go('login', false, false, 'member');
        }

        // subscription not allowed
        if(!$manager->isSubscribtionAllowed('entry') && !$manager->isSubscribtionAllowed('news')) {
            $controller->go();
        }


        $controller->loadClass('member');
        $controller->setModRewrite(false);

        $view = &$controller->getView();
        $view->client = true;


        $view->obj_2 = new Subscription;
        $view->manager_2 = new SubscriptionModel;

        $uid = AuthPriv::getUserId();

        // type of subscription
        $list_type = 'types';
        if(isset($this->rq->type)) {
            if(!empty($view->manager_2->types[$this->rq->type])) {
                $list_type = $view->manager_2->types[$this->rq->type];
            }
        }


        if(isset($_GET['action'])) {

            $more = array('type'=>$_GET['type']);

            if($_GET['action'] == 'delete') {

                $entry_id = (int) $this->rq->id;
                $entry_type = (int) $this->rq->type;

                $view->manager_2->deleteSubscription($entry_id, $entry_type, $uid);

                $controller->go('member_subsc', false, false, 'deleted', $more, true);


            } elseif($_GET['action'] == 'insert') {

                // news
                if ($this->rq->type == 3) {

                    if (!$this->rp->news_subsc) {
                        $view->manager_2->saveSubscription(array(0), 3, $uid);
                    } else {
                        $view->manager_2->deleteSubscription(0, 3, $uid);
                    }

                    $controller->go('member_subsc', false, false, 'success', array(), true);


                // categories
                // } elseif ($this->rq->type == 11 || $this->rq->type == 12) {
                } else {

                    if(isset($this->rp->filter)) {

                        // all
                        if(in_array('0', $this->rp->subscriptions)) {
                            $view->manager_2->deleteByEntryType($this->rq->type, $uid);
                            $view->manager_2->saveSubscription(array(0), $this->rq->type, $uid);

                        // selected
                        } else {

                            $subs = $view->manager_2->getSubscription($this->rq->type, $uid);
                            $subs = array_merge($subs, $this->rp->subscriptions);
                            $subs = $view->manager_2->parseCategories($subs, $this->rq->type);

                            if($subs['remove']) {
                                $view->manager_2->deleteSubscription($subs['remove'], $this->rq->type, $uid);
                            }

                            $view->manager_2->deleteSubscription(0, $this->rq->type, $uid);
                            $view->manager_2->saveSubscription($subs['add'], $this->rq->type, $uid);
                        }

                        $controller->go('member_subsc', false, false, 'success', $more, true);
                    }
                }

                $class = 'SubscriptionView_form_' . $list_type;
                $view->viewContainer = $class;
                require_once APP_MODULE_DIR . 'user/subscription/inc/' . $class . '.php';
            }

        } else {

            if(!empty($this->rq->type)) {
                $class = 'SubscriptionView_list_' . $list_type;
                 $view->viewContainer = $class;
                require_once APP_MODULE_DIR . 'user/subscription/inc/' . $class . '.php';
            } else {
                $view->viewContainer = 'SubscriptionView_types';
            }
        }

        return $view;
    }
}
?>