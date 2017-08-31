<?php
function processNewsSubscription() {
    include_once 'inc/SubscriptionCommonModel.php';
    include_once 'inc/SubscriptionNewsModel.php';

    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new SubscriptionNewsModel;
    $sender = new AppMailSender();

    $settings = SettingModel::getQuickCron(2);
    if ($settings === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    //0 = not allowed, 2 = registered only, 3 - with any priv only
    $allow_subscription = $settings['allow_subscribe_news'];
    if(!$allow_subscription) {
        $cron->logNotify('News subscription disabled for all.');
        $cron->logNotify('%d message(s) processed.', 0);
        return $exitcode;
    }
    
    $interval = $settings['subscribe_news_interval'];
    if($interval == 'daily') {
        $mailing_hour = $settings['subscribe_news_time'];
        
        $start = mktime(intval($mailing_hour), 0, 0) - 300; // 5 minutes before 
        $end = mktime(intval($mailing_hour), 0, 0) + 600;   // 10 minutes after
        $timestamp = time();
        
        // echo 'start: ', date('Y-m-d H:i:s', $start), "\n";
        // echo 'end: ', date('Y-m-d H:i:s', $end), "\n";
        // echo 'timestamp: ', date('Y-m-d H:i:s', $timestamp), "\n";
        
        if ($timestamp > $start && $timestamp < $end) {
            // $cron->logNotify('Mailing daily newsletters ...', 0);
        } else {
            $cron->logNotify('Skipped. The next daily newsletters scheduled at %s:00.', $mailing_hour);
            $cron->logNotify('%d message(s) processed.', 0);
            return $exitcode;
        }
    }

    $latest_date  = $model->getLatestEntryDate();
    if ($latest_date === false) {
        $exitcode = 0;
        return $exitcode;
    }

    $latest_date = ($latest_date) ? $latest_date : date('Y-m-d H:i:s');
    $active_status = implode(',', $manager->getUserActiveStatus());    
        
    $sent = 0;
    $skip_priv = 0;
    $subs =& $model->getSubscribers($active_status, $latest_date);
    if ($subs === false) {
        $exitcode = 0;
        $cron->logCritical('Cannot get news subscriptions.');
    
    } else {
        
        while ($su = $subs->FetchRow()) {
            
            $user_id = $su['user_id'];
            $user['user_id'] = $su['user_id'];
            
            $user['priv_id'] = $manager->getUserPrivId($user_id);
            if($user['priv_id'] === false) {
                $exitcode = 0;
                return $exitcode;
            }
            
            // not allowed for users without priv
            if(!$user['priv_id'] && $allow_subscription == 3) {
                $skip_priv++;
                continue;
            }
            
            $user['role_id'] = $manager->getUserRoleId($user_id);
            if($user['role_id'] === false) {
                $exitcode = 0;
                return $exitcode;
            }            
            
            $news =& $model->getRecentEntriesForUser($user, $su['date_lastsent']);
            if ($news === false) {
                $exitcode = 0;
                $cron->logCritical('Cannot get recent news.');
                $news = array();
            }
        
            if (count($news) > 0) {
                if ($pool_id = $sender->sendNewsSubscription($user_id, $news)) {
                    if (!$model->updateSubscription($user_id)) {
                        $exitcode = 0;
                        $cron->logCritical('Cannot update news subscription status for the user: %d.', $user_id);
                        $sender->model->deletePoolById($pool_id);  // remove pool if not updated
                    
                    } else {
                        $sent += 1;
                    }
                    
                } else {
                    $exitcode = 0;
                    $cron->logCritical('Cannot add news subscription into pool for the user: %d.', $user_id);
                }
            }
        }
        
    }
    
    if($skip_priv) {
        $cron->logNotify('%d message(s) skipped, allowed for users with any priv only.', $skip_priv);
    }    
    
    $cron->logNotify('%d message(s) processed.', $sent);

    return $exitcode;
}


function processEntriesSubscription() {
    
    include_once 'inc/SubscriptionCommonModel.php';
    include_once 'inc/SubscriptionEntryModel.php';
    
    $exitcode = 1;    

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new SubscriptionEntryModel;
    $sender = new AppMailSender();
    
    $settings = SettingModel::getQuickCron(2);
    if ($settings === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    //0 = not allowed, 2 = registered only, 3 - with any priv only
    $allow_subscription = $settings['allow_subscribe_entry'];
    if(!$allow_subscription) {
        $cron->logNotify('Entry subscription disabled for all.');
        $cron->logNotify('%d message(s) processed.', 0);
        return $exitcode;
    }
    
    $interval = $settings['subscribe_entry_interval'];
    if($interval == 'daily') {
        $mailing_hour = $settings['subscribe_entry_time'];
        
        $start = mktime(intval($mailing_hour), 0, 0) - 300; // 5 minutes before 
        $end = mktime(intval($mailing_hour), 0, 0) + 600;   // 10 minutes after
        $timestamp = time();

        if ($timestamp > $start && $timestamp < $end) {
            // $cron->logNotify('Mailing daily newsletters...', 0);
        } else {
            $cron->logNotify('Skipped. The next daily newsletters scheduled at %s:00.', $mailing_hour);
            $cron->logNotify('%d message(s) processed.', 0);
            return $exitcode;
        }
    }    
    
    // simple check for new/updated/commented entries
    $latest_dates = array();
    $latest_dates['article'] = $model->getLatestEntryDate('article');
    $latest_dates['file'] = $model->getLatestEntryDate('file');
    $latest_dates['comment'] = $model->getLatestEntryDate('comment');
    
    foreach($latest_dates as $v) {
        if ($v === false) {
            $exitcode = 0;
            return $exitcode;
        }
    }
    
    sort($latest_dates);
    $latest_date = ($latest_dates[2]) ? $latest_dates[2] : date('Y-m-d H:i:s');
    // echo 'latest_dates: ' . print_r($latest_dates, 1) . "\n";
    // echo 'latest_date: ' . print_r($latest_date, 1) . "\n";
    
    
    $allow_comments = SettingModel::getQuickCron(100, 'allow_comments');
    if ($allow_comments === false) {
        $exitcode = 0;
        return $exitcode;
    }
    $active_status = implode(',', $manager->getUserActiveStatus());
    
    $su_map = array(1=>'updated_article', 2=>'updated_file', 11=>'new_article', 12=>'new_file');
    $su_map_entry = array(1,2);
    $su_map_comment = array(1,11);


    $sent = 0;
    $skip_priv = 0;
    $subs =& $model->getSubscribers($active_status, $latest_date);
    
    if ($subs === false) {
        $exitcode = 0;
        $cron->logCritical('Cannot get entry subscribers.');

    } else {
        
        while ($su = $subs->FetchRow()) {

            $recent = array(
                'new_article'       => array(), 
                'updated_article'   => array(), 
                'commented_article' => array(), 
                'new_file'          => array(), 
                'updated_file'      => array()
                );        

            $user_id = $su['user_id'];
            $user['user_id'] = $su['user_id'];
            
            $user['priv_id'] = $manager->getUserPrivId($user['user_id']);
            if($user['priv_id'] === false) {
                $exitcode = 0;
                return $exitcode;
            }
            
            // not allowed for users without priv
            if(!$user['priv_id'] && $allow_subscription == 3) {
                $skip_priv++;
                continue;
            }
            
            
            $user['role_id'] = $manager->getUserRoleId($user['user_id']);
            if($user['role_id'] === false) {
                $exitcode = 0;
                return $exitcode;
            }        
            
            $subscription = &$model->getUserSubscriptions($user_id);
            if($subscription === false) {
                $exitcode = 0;
                return $exitcode;
            }            

            
            foreach($subscription as $entry_type) {

                $data_type_commented = 'commented_article';
                $data_type = $su_map[$entry_type];
                $emanager = &$model->getEntryManager($user, $entry_type);

                if(empty($ps[$entry_type])) {
                    $ps[$entry_type] = $emanager->getEntryStatusPublishedConcrete();
                    $ps[$entry_type] = implode(',', $ps[$entry_type]);                    
                }
                
                
                // entry
                if(in_array($entry_type, $su_map_entry)) {
                    
                    // updates
                    $res = &$model->getUpdatedEntries($user, $entry_type, $emanager, $ps[$entry_type]);                        
                    if($res) {
                        $recent[$data_type] += $res;
                    }

                    if ($res === false) {
                        $exitcode = 0;
                        $cron->logCritical('Cannot get updated entries.');
                        $recent[$data_type] = array();
                        break;
                    }
                
                    // comments
                    if($allow_comments && in_array($entry_type, $su_map_comment)) {
                            
                        $res = &$model->getCommentedEntries($user, $entry_type, $emanager, $ps[$entry_type]);                        
                        if($res) {
                            $recent[$data_type_commented] += $res;
                        }

                        if ($res === false) {
                            $exitcode = 0;
                            $cron->logCritical('Cannot get commented entries.');
                            $recent[$data_type_commented] = array();
                            break;
                        }
                    }
                    
                
                // category
                } else {

                    // subscribed to all categories
                    $cats = $model->getUserSubscribedCategories($user_id, $entry_type);
                    if(count($cats) == 1 && key($cats) === 0) {

                        $last_sent = $cats[0];
                        $subs_ = &$model->getAllEntries($last_sent, $emanager, $ps[$entry_type]);
                        
                        // comments
                        if($subs_ !== false) {
                            if($allow_comments && in_array($entry_type, $su_map_comment)) {
                                $subs_comment_ = &$model->getAllCommentedEntries($last_sent, $emanager, $ps[$entry_type]);
                                if($subs_comment_ === false) {
                                    $subs_ = false;
                                } else {
                                    $subs_ += $subs_comment_;     
                                }
                            }
                        }


                    // for concrete category
                    } else {

                        if(empty($categories[$entry_type])) {
                            $categories[$entry_type] = &$emanager->getCategoryRecords();                    
                        }

                        $subs_ = array(0=>array(), 1=>array());
                        foreach($cats as $cat_id => $last_sent) {
                            $child = $emanager->getChilds($categories[$entry_type], $cat_id);
                            $child = array_merge($child, array($cat_id));
                            $child = implode(',', $child);

                            $t_ = &$model->getCategoryEntries($last_sent, $emanager, $ps[$entry_type], $child);
                            if ($t_ === false) {
                                $subs_ = false;
                                break;
                            }

                            foreach($t_ as $type_ => $v_) {
                                if($v_) {
                                    $subs_[$type_] += $v_;
                                }
                            }
                            
                            
                            // comments
                            if($subs_ !== false) {
                                if($allow_comments && in_array($entry_type, $su_map_comment)) {
                                    $t_ = &$model->getCommentedCategoryEntries($last_sent, $emanager, $ps[$entry_type], $child);
                                    if ($t_ === false) {
                                        $subs_ = false;
                                        break;
                                    }
                                    
                                    foreach($t_ as $type_ => $v_) {
                                        if($v_) {
                                            $subs_[$type_] += $v_;
                                        }
                                    }
                                }
                            } // -> сomments
                            
                        }
                    }

                    if($subs_ === false) {
                        $exitcode = 0;
                        $cron->logCritical('Cannot get category subscription.');
                        break;
                    }

                    // new
                    if($subs_[1]) {
                        $k = $su_map[$entry_type];
                        $recent[$k] += $subs_[1];
                    }

                    // updated
                    if($subs_[0]) {
                        $k = $su_map[$entry_type-10];
                        $recent[$k] += $subs_[0];
                    }
                    
                    // commented
                    if($subs_[2]) {
                        $k = $data_type_commented;
                        $recent[$k] += $subs_[2];
                    }                    
                }
            
            } // -> foreach($subscription as $entry_type) {


            // remove duplicates, it is possiblle if subscribed to category and to entry in this category
            // remove from new if exists in updated, concrete entry priority
            if(is_array($recent['new_article']) && is_array($recent['updated_article'])) {
                $inter = array_intersect(array_keys($recent['new_article']), 
                                         array_keys($recent['updated_article']));
                foreach($inter as $entry_id) {
                    unset($recent['new_article'][$entry_id]);
                }
            }

            if(is_array($recent['new_file']) && is_array($recent['updated_file'])) {
                $inter = array_intersect(array_keys($recent['new_file']), 
                                         array_keys($recent['updated_file']));
                foreach($inter as $entry_id) {
                    unset($recent['new_file'][$entry_id]);
                }
            }

            
            // to know if there is at least one item to send
            $recent_items = false;
            foreach(array_keys($recent) as $type) {
                if(is_array($recent[$type]) && count($recent[$type]) > 0) {
                    $recent_items = true;
                    break;                
                }
            }
            
            // echo $user_id, "\n";
            // echo print_r($subs_, 1);
            // echo print_r($recent, 1);
            // echo "\n===========\n";
            // continue;
            
            // have item(s) to send and no errors
            if ($recent_items && $exitcode == 1) {

                if ($pool_id = $sender->sendEntrySubscription($user_id, $recent)) {
                    if (!$model->updateSubscription($user_id)) {
                        $exitcode = 0;
                        $cron->logCritical('Cannot update entry subscription status for the user: %d.', $user_id);
                        $sender->model->deletePoolById($pool_id);  // remove pool if not updated
                    } else {
                        $sent += 1;
                    }
                    
                } else {
                    $exitcode = 0;
                    $cron->logCritical('Cannot add entry subscription into pool for the user: %d.', $user_id);
                }
            }
            
        } // -> while ($su = $subs->FetchRow()), all subscribers
        
    } // -> else
    
    
    if($skip_priv) {
        $cron->logNotify('%d message(s) skipped, allowed for users with any priv only.', $skip_priv);
    }
    
    $cron->logNotify('%d message(s) processed.', $sent);

    return $exitcode;
}


function processCommentSubscription() {
    
    include_once 'inc/SubscriptionCommonModel.php';
    include_once 'inc/SubscriptionCommentModel.php';
    
    $exitcode = 1;    

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new SubscriptionCommentModel;
    $sender = new AppMailSender();

    //0 = not allowed, 2 = registered only, 3 - with any priv only
    $allow_subscription = SettingModel::getQuickCron(100, 'allow_subscribe_comment');
    if ($allow_subscription === false) {
        $exitcode = 0;
        return $exitcode;
    }
    
    if(!$allow_subscription) {
        $cron->logNotify('Comment subscription disabled for all.');
        $cron->logNotify('%d message(s) processed.', 0);
        return $exitcode;
    }
    
    $latest_date  = $model->getLatestEntryDate();
    if ($latest_date === false) {
        $exitcode = 0;
        return $exitcode;
    }    
    
    $latest_date = ($latest_date) ? $latest_date : date('Y-m-d H:i:s');
    $active_status = implode(',', $manager->getUserActiveStatus());
    // echo 'Latest Date: ', $latest_date, "\n";


    $sent = 0;
    $skip_priv = 0;
    $subs =& $model->getSubscribers($active_status, $latest_date);

    if ($subs === false) {
        $exitcode = 0;
        $cron->logCritical('Cannot get comment subscribers.');

    } else {
        
        while ($su = $subs->FetchRow()) {    

            $user_id = $su['user_id'];
            $user['user_id'] = $su['user_id'];
            
            $user['priv_id'] = $manager->getUserPrivId($user['user_id']);
            if($user['priv_id'] === false) {
                $exitcode = 0;
                return $exitcode;
            }
            
            // not allowed for users without priv
            if(!$user['priv_id'] && $allow_subscription == 3) {
                $skip_priv++;
                continue;
            }
            
            $user['role_id'] = $manager->getUserRoleId($user['user_id']);
            if($user['role_id'] === false) {
                $exitcode = 0;
                return $exitcode;
            }
                    
            $comments =& $model->getRecentEntriesForUser($user, $su['date_lastsent']);
            
            // echo 'Subscribers: ', print_r($su, 1), "\n";
            // echo 'Comments: ', print_r($comments, 1), "\n";
            // continue;
            
            if ($comments === false) {
                $exitcode = 0;
                $cron->logCritical('Cannot get recent comments.');
                $comments = array();
            }

            if (count($comments) > 0) {
                if ($pool_id = $sender->sendCommentSubscription($user_id, $comments)) {
                    if (!$model->updateSubscription($user_id)) {
                        $exitcode = 0;
                        $cron->logCritical('Cannot update comment subscription status for user: %d.', $user_id);
                        $sender->model->deletePoolById($pool_id);  // remove pool if not updated
                    
                    } else {
                        $sent += 1;
                    }
                    
                } else {
                    $exitcode = 0;
                    $cron->logCritical('Cannot add comment subscription into pool for the user: %d.', $user_id);
                }
            }
            
        } // -> while ($su = $subs->FetchRow()), all subscribers
        
    } // -> else
    
    
    if($skip_priv) {
        $cron->logNotify('%d message(s) skipped, allowed for users with any priv only.', $skip_priv);
    }
    
    $cron->logNotify('%d message(s) processed.', $sent);

    return $exitcode;
}


function processTopicSubscription() {
    
    include_once 'inc/SubscriptionCommonModel.php';
    include_once 'inc/SubscriptionTopicModel.php';
    
    $exitcode = 1;    

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new SubscriptionTopicModel;
    $sender = new AppMailSender();

    //0 = not allowed, 2 = registered only
    $allow_subscription = SettingModel::getQuick(600, 'allow_subscribe_forum');
    if(!$allow_subscription) {
        $cron->logNotify('Topic subscription disabled for all.');
        $cron->logNotify('%d message(s) processed.', 0);
        return $exitcode;
    }
    
    $latest_date  = $model->getLatestEntryDate();
    if ($latest_date === false) {
        $exitcode = 0;
        return $exitcode;
    }    
    
    $latest_date = ($latest_date) ? $latest_date : date('Y-m-d');
    $active_status = implode(',', $manager->getUserActiveStatus());
    // echo 'Latest Date: ', $latest_date, "\n";


    $sent = 0;
    $skip_priv = 0;
    $subs =& $model->getSubscribers($active_status, $latest_date);

    if ($subs === false) {
        $exitcode = 0;
        $cron->logCritical('Cannot get topic subscribers.');

    } else {
        
        while ($su = $subs->FetchRow()) {

            $user_id = $su['user_id'];
            $user['user_id'] = $su['user_id'];
            
            $user['priv_id'] = $manager->getUserPrivId($user['user_id']);
            if($user['priv_id'] === false) {
                $exitcode = 0;
                return $exitcode;
            }
            
            
            $user['role_id'] = $manager->getUserRoleId($user['user_id']);
            if($user['role_id'] === false) {
                $exitcode = 0;
                return $exitcode;
            }        
            
                    
            $messages =& $model->getRecentEntriesForUser($user);
            
            // echo 'Subscribers: ', print_r($su, 1), "\n";
            // echo 'Messages: ', print_r($messages, 1), "\n";
            // continue;
            
            if ($messages === false) {
                $exitcode = 0;
                $cron->logCritical('Cannot get recent messages.');
                $messages = array();
            }

            if (count($messages) > 0) {
                if ($pool_id = $sender->sendTopicSubscription($user_id, $messages)) {
                    if (!$model->updateSubscription($user_id)) {
                        $exitcode = 0;
                        $cron->logCritical('Cannot update comment subscription status for user: %d.', $user_id);
                        $sender->model->deletePoolById($pool_id);  // remove pool if not updated
                    
                    } else {
                        $sent += 1;
                    }
                    
                } else {
                    $exitcode = 0;
                    $cron->logCritical('Cannot add topic subscription into pool for the user: %d.', $user_id);
                }
            }
            
        } // -> while ($su = $subs->FetchRow()), all subscribers
        
    } // -> else
    
    
    if($skip_priv) {
        $cron->logNotify('%d message(s) skipped, allowed for users with any priv only.', $skip_priv);
    }
    
    $cron->logNotify('%d message(s) processed.', $sent);

    return $exitcode;
}


function processForumSubscription() {
    
    include_once 'inc/SubscriptionCommonModel.php';
    include_once 'inc/SubscriptionForumModel.php';
    
    $exitcode = 1;    

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $manager =& $cron->manager;
    $model = new SubscriptionForumModel;
    $sender = new AppMailSender();

    //0 = not allowed, 2 = registered only
    $allow_subscription = SettingModel::getQuick(600, 'allow_subscribe_forum');
    if(!$allow_subscription) {
        $cron->logNotify('Forum subscription disabled for all.');
        $cron->logNotify('%d message(s) processed.', 0);
        return $exitcode;
    }
    
    
    $latest_date  = $model->getLatestEntryDate();
    if ($latest_date === false) {
        $exitcode = 0;
        return $exitcode;
    }    
    
    $latest_date = ($latest_date) ? $latest_date : date('Y-m-d');
    $active_status = implode(',', $manager->getUserActiveStatus());

    $sent = 0;
    $skip_priv = 0;
    $subs =& $model->getSubscribers($active_status, $latest_date);
    
    if ($subs === false) {
        $exitcode = 0;
        $cron->logCritical('Cannot get forum subscribers.');

    } else {
        
        require_once APP_MODULE_DIR . 'forum/entry/inc/ForumEntryModel.php';
        
        $emanager = new ForumEntryModel($user, 'read');
            
        $categories = &$emanager->getCategoryRecords();
            
        $ps = $emanager->getEntryStatusPublishedConcrete();
        $ps = implode(',', $ps);
        
        
        while ($su = $subs->FetchRow()) {
            
            $recent = array('updated_topic' => array(), 'new_topic' => array());        

            $user_id = $su['user_id'];
            $user['user_id'] = $su['user_id'];
            
            $user['priv_id'] = $manager->getUserPrivId($user['user_id']);
            if($user['priv_id'] === false) {
                $exitcode = 0;
                return $exitcode;
            }
            
            // not allowed for users without priv
            if(!$user['priv_id'] && $allow_subscription == 3) {
                $skip_priv++;
                continue;
            }
            
            
            $user['role_id'] = $manager->getUserRoleId($user['user_id']);
            if($user['role_id'] === false) {
                $exitcode = 0;
                return $exitcode;
            }

            // subscribed to all categories
            $cats = $model->getUserSubscribedCategories($user_id);
            if(count($cats) == 1 && key($cats) === 0) {

                $last_sent = $cats[0];
                $subs_ = &$model->getAllEntries($last_sent, $emanager, $ps);
            
            } else { // for a concrete category
                $subs_ = array(0 => array(), 1 => array());
                foreach($cats as $cat_id => $last_sent) {
                    $child = $emanager->getChilds($categories, $cat_id);
                    $child = array_merge($child, array($cat_id));
                    $child = implode(',', $child);

                    $t_ = &$model->getCategoryEntries($last_sent, $emanager, $ps, $child);
                    if ($t_ === false) {
                        $subs_ = false;
                        break;
                    }

                    foreach($t_ as $type_ => $v_) {
                         if($v_) {
                             $subs_[$type_] += $v_;
                         }
                     }
                    
                }
            }

            if($subs_ === false) {
                $exitcode = 0;
                $cron->logCritical('Cannot get category subscription.');
                break;
            }
            
            // new
            if($subs_[1]) {
                $recent['new_topic'] += $subs_[1];
            }

            // updated
            if($subs_[0]) {
                $recent['updated_topic'] += $subs_[0];
            }


            // remove duplicates, it is possible if subscribed to the forum and to the topic in this forum
            // remove from new if exists in updated, concrete entry priority
            if(is_array($recent['new_topic']) && is_array($recent['updated_topic'])) {
                $inter = array_intersect(array_keys($recent['new_topic']), 
                                         array_keys($recent['updated_topic']));
                foreach($inter as $entry_id) {
                    unset($recent['new_topic'][$entry_id]);
                }
            }
            
            
            // to know if there is at least one item to send
            $recent_items = false;
            foreach(array_keys($recent) as $type) {
                if(is_array($recent[$type]) && count($recent[$type]) > 0) {
                    $recent_items = true;
                    break;                
                }
            }
            
            // echo $user_id, "\n";
            // echo print_r($subs_, 1);
            // echo print_r($recent, 1);
            // echo "\n===========\n";
            // continue;
            
            // have item(s) to send and no errors
            if ($recent_items && $exitcode == 1) {
                if ($pool_id = $sender->sendForumSubscription($user_id, $recent)) {
                    if (!$model->updateSubscription($user_id)) {
                        $exitcode = 0;
                        $cron->logCritical('Cannot update forum subscription status for the user: %d.', $user_id);
                        $sender->model->deletePoolById($pool_id);  // remove pool if not updated
                    } else {
                        $sent += 1;
                    }
                    
                } else {
                    $exitcode = 0;
                    $cron->logCritical('Cannot add forum subscription into pool for the user: %d.', $user_id);
                }
            }
            
        } // -> while ($su = $subs->FetchRow()), all subscribers
        
    } // -> else
    
    
    if($skip_priv) {
        $cron->logNotify('%d message(s) skipped, allowed for users with any priv only.', $skip_priv);
    }
    
    $cron->logNotify('%d message(s) processed.', $sent);

    return $exitcode;
}


function normalizeCategorySubscription() {
    
    include_once 'inc/SubscriptionCommonModel.php';
    include_once 'inc/SubscriptionEntryModel.php';
    
    $exitcode = 1;
    
    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    $model = new SubscriptionEntryModel;
    
    $deleted = 0;
    $entry_types = array('11', '12');
    
    
    foreach ($entry_types as $entry_type) {
    
        $subs =& $model->getAllSubscribers($entry_type);
        if ($subs === false) {
            $exitcode = 0;
            $cron->logCritical('Cannot get category subscribers.');
            continue;
        }
                        
        $categories = $model->getAllCategories($entry_type);
        if ($categories === false) {
            $exitcode = 0;
            continue;
        }

        while ($su = $subs->FetchRow()) {
            $user_id = $su['user_id'];
            $cats_to_delete = array();  

            $cats = $model->getUserSubscribedCategories($user_id, $entry_type);
            if ($cats === false) {
                $exitcode = 0;
                return $exitcode;
            }

            if (!empty($cats)) {

                // subscribed to all
                if(in_array(0, array_keys($cats), true)) {
                    unset($cats[0]);
                    $cats_to_delete = array_keys($cats);

                } else {
                    $user_subscribed = array_keys($cats);

                    foreach ($user_subscribed as $id) {
                        $parents = array();
                        $parent_id = $categories[$id]['parent_id'];
                        while ($parent_id > 0) {
                            $parents[] = $parent_id;
                            $parent_id = $categories[$parent_id]['parent_id'];
                        }

                        foreach ($parents as $parent) {
                            if (in_array($parent, $user_subscribed)) {
                                $cats_to_delete[] = $id;
                                break;
                            }
                        }
                    }
                }

                if (!empty($cats_to_delete)) {
                    $ret = $model->deleteSubscription($user_id, $entry_type, implode(',', $cats_to_delete));
                    if($ret) {
                        $deleted += count($cats_to_delete); 
                    } else {
                        $exitcode = 0;
                        return $exitcode;
                    }
                }
            }
        
        } // -> while
        
    } // -> $entry_types
    
    
    $cron->logNotify('%d category(s) have been removed.', $deleted);

    return $exitcode;
}

?>