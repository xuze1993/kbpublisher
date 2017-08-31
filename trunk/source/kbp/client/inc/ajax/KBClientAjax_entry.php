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


class KBClientAjax_entry extends KBClientAjax
{

    function doRateResponse($rate) {

        $objResponse = new xajaxResponse();
        

        $rate = (int) $rate;
        if($rate) {
            if($this->manager->isUserVoted($this->entry_id) === false) {
                $this->manager->addVote($this->entry_id, $rate);
                // $this->manager->setUserVoted($this->entry_id); 

                //$objResponse->addAlert($str);
                $objResponse->addAssign('rateResponce', 'style.display', 'block');
                $objResponse->addAssign('rateQuery', 'style.display', 'none');
                $objResponse->addAssign('currentRating', 'style.display', 'none');

                if($this->manager->getSetting('allow_rating_comment')) {
                    $objResponse->addAssign('rateFeedbackForm', 'style.display', 'block');
                    $objResponse->addAssign('rate_rating', 'value', $rate);

                    $div = ($rate < 5) ? 'comment_rate_neg' : 'comment_rate_pos';
                    $objResponse->addAssign($div, 'style.display', 'inline');
                    $objResponse->addAssign('comment_report', 'style.display', 'none');
                }
                
                $objResponse->addAssign('rateFeedbackForm', 'style.float', 'none');

            } else {
                $objResponse->addAlert('The entry was voted already!');
            }

        } else {
            $objResponse->addAlert('Error!');
        }

        return $objResponse;
    }


    // used for rate comments and reporn an issue
    function doRateFeedbackResponse($comment, $rate_value) {

        $objResponse = new xajaxResponse();
        //$objResponse->addAlert($rate_value);

        $comment = trim($comment);
        if($comment) {
            $rating = (int) $rate_value;
            $comment = RequestDataUtil::stripVars($comment, array(), 'addslashes');            
            $rating_id = $this->manager->addVoteFeedback($this->entry_id, $comment, $rating);

            if($rating) {
                $msg_key = ($this->manager->getSetting('rating_type') == 1) ? 'rating' : 'rating2';
                $msg = AppMsg::getMsg('ranges_msg.ini', 'public', $msg_key);
                $vars['rating'] = $msg[$rating];
            }

            $vars['user_id'] = $this->manager->user_id;
            $vars['message'] = RequestDataUtil::stripVars($comment, array(), 'stripslashes');
            $vars['entry_id'] = $this->entry_id;
            $vars['category_id'] = $this->category_id;
            $vars['title'] = $this->manager->getEntryTitle($this->entry_id);

            $more = array('id'=>$rating_id);
            $vars['link'] = $this->controller->getAdminRefLink('knowledgebase', 'kb_rate', false, 'update', $more);
            $vars['entry_link'] = $this->controller->getLink('entry', false, $this->entry_id);

            // growl
            $growl_cmd = '$.growl({title: "%s", message: "%s"});';
            $msg_key = ($rating) ? 'thanks_rate2_msg' : 'thanks_report_msg';
            $msg = AppMsg::getMsg('client_msg.ini', 'public');
            $objResponse->AddScript(sprintf($growl_cmd, '', $msg[$msg_key]));

            $ret = $this->manager->sendRatingNotification($vars);
            // $objResponse->addAlert('<pre>' . print_r($ret, 1) . '</pre>');
        }

        $objResponse->addAssign('rateFeedbackForm', 'style.display', 'none');
        $objResponse->script('$("#bootstrap_issue").collapse("hide");'); // mobile
        
        return $objResponse;
    }


    function doSubscribeArticleResponse($value) {
        $this->entry_type = 1; // article
        return $this->_doSubscribeResponse($value, $this->entry_id, false);
    }


    function doSubscribeFileResponse($value, $entry_id, $id_suffics) {
        $this->entry_type = 2; // file
        return $this->_doSubscribeResponse($value, $entry_id, $id_suffics);
    }


    function doSubscribeArticleCatResponse($value) {
        $this->entry_type = 11; // article category
        return $this->_doSubscribeResponse($value, $this->category_id, false);
    }


    function doSubscribeFileCatResponse($value) {
        $this->entry_type = 12; // file category
        return $this->_doSubscribeResponse($value, $this->category_id, false);
    }


    function doSubscribeTopicResponse($value) {
        $objResponse = new xajaxResponse();
        $objResponse->script('$("#subscribe").click();');

        $this->entry_type = 4; // topic
        return $this->_doSubscribeResponse($value, $this->entry_id, false, $objResponse);
    }


    function doSubscribeForumResponse($value) {
        $this->entry_type = 14; // forum
        return $this->_doSubscribeResponse($value, $this->category_id, false);
    }


    function _doSubscribeResponse($value, $entry_id, $id_suffics, $objResponse = false) {

        if (!$objResponse) {
            $objResponse = new xajaxResponse();
        }

        if(!$this->manager->is_registered) {
            $more = array('t'=>$this->entry_type);
            $link = $this->controller->getAjaxLink('login', false, $entry_id, 'subscribe', $more);

            $objResponse->addRedirect($link);
            $rlink = $objResponse->aCommands[0]['data'];
            $rlink = $this->controller->_replaceArgSeparator($rlink);
            return $objResponse;
        }

        $value = (int) $value;
        $entry_id = (int) $entry_id;
        $user_id = (int) $this->manager->user_id;
        $type = $this->entry_type;

        require_once APP_MODULE_DIR . 'user/subscription/inc/SubscriptionModel.php';
        $manager = new SubscriptionModel();

        $growl_cmd = '$.growl({title: "%s", message: "%s"});';
        
        $visible_display = ($this->view->mobile_view) ? 'block' : 'inline';

        if($value) {
            $manager->saveSubscription(array($entry_id), $type, $user_id);
            $objResponse->addAssign('div_subscribe_yes' . $id_suffics, 'style.display', 'none');
            $objResponse->addAssign('div_subscribe_no' . $id_suffics, 'style.display', $visible_display);
            $objResponse->AddScript(sprintf($growl_cmd, '', $this->view->msg['successfully_subscribed_msg']));

        } else {
            $manager->deleteSubscription($entry_id, $type, $user_id);
            $objResponse->addAssign('div_subscribe_yes' . $id_suffics, 'style.display', $visible_display);
            $objResponse->addAssign('div_subscribe_no' . $id_suffics, 'style.display', 'none');
            $objResponse->AddScript(sprintf($growl_cmd, '', $this->view->msg['successfully_unsubscribed_msg']));
        }

        // for mobile ???
        $objResponse->post_action_params = $entry_id;
        
        $objResponse->addAssign('subsc_icon', 'style.display', $visible_display);

        //$objResponse->addAlert(123);
        return $objResponse;
    }


    function loadNextEntries($offset) {

        $objResponse = new xajaxResponse();

        $limit = $this->view->dynamic_limit + 1;

        switch ($this->view->dynamic_type) {
            case 'recent':
                $rows = $this->view->getEntryListRecent($this->manager, $limit, $offset);
                break;

            case 'popular':
                $rows = $this->view->getEntryListMostViewed($this->manager, $limit, $offset);
            	break;

            case 'featured':
            	$rows = $this->view->getEntryListFeatured($this->manager, $limit, $offset);
            	break;
        }

        if (empty($rows)) {
            $objResponse->call('DynamicEntriesScrollLoader.insert', '', 1);
            
            return $objResponse;
        }


        $end_reached = (int) (count($rows) <= $this->view->dynamic_limit);
        if (!$end_reached) {
            array_pop($rows);
        }

        $sname = sprintf($this->view->dynamic_sname, $this->view->dynamic_type);
        $_SESSION[$sname] = $offset + count($rows);

        // replace bad utf
        $replace_utf = RequestDataUtil::badUtfLoad($this->encoding);
        if($replace_utf) {
            $summary_limit = $this->manager->getSetting('preview_article_limit');
            foreach(array_keys($rows) as $k) {
                $rows[$k]['title'] = RequestDataUtil::stripVarBadUtf($rows[$k]['title']);
                $rows[$k]['body'] = DocumentParser::getSummary($rows[$k]['body'], $summary_limit);
                $rows[$k]['body'] = RequestDataUtil::stripVarBadUtf($rows[$k]['body']);
            }
        }

        $rows = $this->view->stripVars($rows);
        $tpl = $this->view->_parseArticleList($this->manager, $rows, '', '', false);

        if ($tpl instanceof tplTemplatez) {
		    $data = $tpl->parsed['row'];

            // $objResponse->addAlert('<pre>' . print_r($this->manager->sql_params_order, 1) . '</pre>');
            $objResponse->call('DynamicEntriesScrollLoader.insert', $data, $end_reached);
            
            if (!$this->view->mobile_view) {
                $objResponse->call('DynamicEntriesScrollLoader.resetLoader');
            }
        }

        return $objResponse;
    }

}
?>