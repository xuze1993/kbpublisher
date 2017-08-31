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

require_once APP_MODULE_DIR . 'user/subscription/inc/SubscriptionModel.php';


class KBClientAction_unsubscribe extends KBClientAction_common
{

    function &execute($controller, $manager) {    
    
        // if($manager->is_registered) {
            // $controller->go();
        // }
    
        // not implemented yet
        // if(!$manager->getSetting('unsubscribe_policy')) {
            // $controller->go();
        // }    nsusbscription_wrong');


        // just redirect if no confirm str or type
        // if(!isset($this->rq->ec) && !isset($this->rq->et)) {
            // $controller->go();
        // }

        $values = $this->rq->vars;
        
        $view = &$controller->getView('unsubscribe');
        @$view->sub_type = $values['et'];


        if(isset($this->rp->submit)) {
            
            $user = $this->validateUser($values, $manager);
            if(!$user) {
                $controller->go('unsubscribe', false, false, 'unsusbscription_error');
            }
            
            $type = $this->validateType($values, $manager);
            if(!$type) {
                $controller->go('unsubscribe', false, false, 'unsusbscription_error');
            }
            
            
            $user_id = $user['id'];  

            $s = new SubscriptionModel();
            if($type == 'all') {
                $s->deleteByUserId($user_id); // all per user
            } else {
                $s->deleteByEntryType($type, $user_id);
            }

            $controller->go('unsubscribe', false, false, 'unsusbscription_success');   
        }
        
        
        return $view;
    }
    
    
    function validateUser($values, $manager) {
        
        $user = array();
        if(!empty($values['ec'])) {
            // $user_id = (!empty($values['ec'])) ? (int) $values['ec'] : 0;
            $code = addslashes(stripslashes($values['ec']));
            $user = $manager->isUser($code);
        }

        return $user;
    }


    function validateType($values, $manager) {

        $sub_type = false;
        if(!empty($values['et'])) {
            
            if($values['et'] == 'all') {
                $sub_type = 'all';
                
            } elseif($values['et'] == 'entry') {
                $sub_type = '1,11,2,12'; //all article and files 
            
            } elseif($values['et'] == 'news') {
                $sub_type = 3;
            
            } elseif($values['et'] == 'comment') {
                $sub_type = 31;
            }
        }

        return $sub_type;
    }
    
}
?>