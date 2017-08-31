<?php

class AutomationModel extends AppModel
{
    
    var $tbl_pref_custom = '';
    var $tables = array('trigger', 'log_trigger', 'user', 'kb_entry', 'file_entry');
    
    var $emanager;
    var $etype = false; // article, file
    var $trigger_type = 2; // automations
    
    var $num_tries = 2;
     
        
    function setEntryTable($entry_type) {
        
    }
        
    
    function getAutomationsCount() {
        $sql = "SELECT COUNT(*) AS num FROM {$this->tbl->trigger} 
        WHERE trigger_type = %d AND active = 1";
        $sql = sprintf($sql, $this->trigger_type);
        
        $result = $this->db->Execute($sql);
        if ($result) {
            return $result->Fields('num');
            
        } else {
            trigger_error($this->db->ErrorMsg());
            return $result;
        }        
    }
    
    
    function getAutomations($entry_type) {
        $sql = "SELECT * FROM {$this->tbl->trigger}
            WHERE trigger_type = %d
            AND entry_type = %d
            AND active = 1";
        $sql = sprintf($sql, $this->trigger_type, $entry_type);
        
        $result = $this->db->Execute($sql);
        if ($result) {
            return $result->GetArray();
            
        } else {
            trigger_error($this->db->ErrorMsg());
            return $result;
        }
    }
    
    
    function sortActions($a, $b) { // send an email at the end
        if ($a['item'] == 'email') {
            return 1;
            
        } elseif ($b['item'] == 'email') {
            return -1;
        }
        
        return 0;
    }
    
    
    function getUserById($id) {
        $result = $this->_getUserResult($id); 
        return $result->FetchRow();
    }
    
    
    function getUserByIds($ids) {
        $result = $this->_getUserResult($ids);
        return $result->GetAssoc();
    }
    
    
    function _getUserResult($ids) {
        $sql = "SELECT * FROM {$this->tbl->user} WHERE id IN (%s)";
        $sql = sprintf($sql, $ids);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result;
    }
         
    
    // actions
    function runAction($method, $params) {
        $callback = array($this, $method);
        return call_user_func_array($callback, $params);
    }
    
    
    function setStatus($entries, $rule, $automation, $extra_params) {
        $new_status = $rule[0];
        $status_key = $this->etype . '_status';
        
        // validation
        $statuses = $this->emanager->getEntryStatusData($status_key);
        if (empty($statuses[$new_status])) {
            trigger_error('There is no such status, ID: ' . $new_status);
            return false;
        }
        
        return $this->_setSimpleField($entries, 'active', $new_status);
    }
    
    
    function setType($entries, $rule, $automation, $extra_params) {
        $new_type = $rule[0];
        
        // validation
        $types = $this->emanager->getEntryStatusData('article_type');
        if (empty($types[$new_type])) {
            trigger_error('There is no such type, ID: ' . $new_type);
            return false;
        }
        
        return $this->_setSimpleField($entries, 'entry_type', $new_type);
    }
    
    
    function _setSimpleField($entries, $field, $new_value) {
        
        $ids_str = BaseModel::getValuesString($entries, 'id');
                
        $sql = "UPDATE {$this->emanager->tbl->table}
            SET %s = %s, date_updated=date_updated
            WHERE id IN (%s)";
        $sql = sprintf($sql, $field, $new_value, $ids_str);
        $result = $this->db->Execute($sql);
                                    
        if ($result === false) {
            trigger_error($this->db->ErrorMsg());
            return false;
        }
        
        return true;
    }
    
    
    function emailUser($entries, $rule, $automation, $extra_params = false) {
        
        foreach ($entries as $entry) {
            
            // email address the email is sent to
            if (is_numeric($rule[0])) { // user id
                $recipient_id = $rule[0];
                
            } elseif ($rule[0] == 'author') {
                $recipient_id = $entry['author_id'];
                
            } elseif ($rule[0] == 'updater') {
                $recipient_id = $entry['updater_id'];
            }
            
            $recipient = $this->getUserById($recipient_id);
            $recipients = array($recipient);
            
            $entry['author'] = $this->getUserById($entry['author_id']);
            $entry['updater'] = $this->getUserById($entry['updater_id']);
            
            if (!empty($extra_params['custom'][$entry['id']])) {
                $entry['custom'] = $extra_params['custom'][$entry['id']];
            }
            
            $vars = $this->_getEmailVars($entry);
            $sent = $this->_sendEmail($recipients, $vars, $rule, $automation, $extra_params);
            
            if (!$sent) {
                return false;
            }
        }
        
        return true;
    }
    
    
    function emailGroup($entries, $rule, $automation, $extra_params = false) {
        
        $categories = $this->emanager->getCategoryRecords();
        
        $entry_ids = $this->getValuesArray($entries);
        $entry_ids = implode(',', $entry_ids);
        $entry_to_categories = $this->emanager->getCategoryByIds($entry_ids);
        
        $cat_to_supervisor = $this->emanager->cat_manager->getSupervisorsArray($entry_to_categories, $categories);
        
        foreach ($entries as $entry) {
            
            if ($rule[0] == 'supervisors') {
                $entry_categories = array_keys($entry_to_categories[$entry['id']]);
                $recipients_ids = $this->emanager->cat_manager->getSupervisors($entry_categories, $categories, $cat_to_supervisor);
                
                if (empty($recipients_ids)) {
                    return true;
                }
                
                $recipients_ids = implode(',', $recipients_ids);
                $recipients = $this->getUserByIds($recipients_ids);
            }
            
            $entry['author'] = $this->getUserById($entry['author_id']);
            $entry['updater'] = $this->getUserById($entry['updater_id']);
            
            if (!empty($extra_params['custom'][$entry['id']])) {
                $entry['custom'] = $extra_params['custom'][$entry['id']];
            }
            
            $vars = $this->_getEmailVars($entry);
            $sent = $this->_sendEmail($recipients, $vars, $rule, $automation, $extra_params);
            
            if (!$sent) {
                return false;
            }
        }
        
        return true;
    }
    
    
    function emailUserGrouped($entries, $rule, $automation, $extra_params = false) {
        
        if (is_numeric($rule[0])) { // user id
            $recipient_id = $rule[0];
            $recipient = $this->getUserById($recipient_id);
            $recipients = array($recipient);
            
            $vars = $this->_getEmailVarsGrouped($entries);
            
            return $this->_sendEmail($recipients, $vars, $rule, $automation, $extra_params);
            
        } elseif ($rule[0] == 'author') {
            $entries_by_author = array();
            foreach ($entries as $entry) {
                $entries_by_author[$entry['author_id']][] = $entry;
            }
            
            foreach ($entries_by_author as $author_id => $author_entry) {
                $recipient = $this->getUserById($author_id);
                $recipients = array($recipient);
                
                $vars = $this->_getEmailVarsGrouped($entries_by_author[$author_id]);
                $sent = $this->_sendEmail($recipients, $vars, $rule, $automation, $extra_params);
                if (!$sent) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    
    function emailGroupGrouped($entries, $rule, $automation, $extra_params = false) {
        
        $categories = $this->emanager->getCategoryRecords();
        
        $entry_ids = $this->getValuesArray($entries);
        $entry_ids = implode(',', $entry_ids);
        $entry_to_categories = $this->emanager->getCategoryByIds($entry_ids);
        
        $cat_to_supervisor = $this->emanager->cat_manager->getSupervisorsArray($entry_to_categories, $categories);
        
        if ($rule[0] == 'supervisors') {
            
            $supervisor_to_entry = array();
            
            foreach ($entries as $entry) {
                $entry_categories = array_keys($entry_to_categories[$entry['id']]);
                $supervisor_ids = $this->emanager->cat_manager->getSupervisors($entry_categories, $categories, $cat_to_supervisor);
                
                foreach ($supervisor_ids as $supervisor_id) {
                    $supervisor_to_entry[$supervisor_id][] = $entry['id'];
                }
            }
            
            foreach ($supervisor_to_entry as $supervisor_id => $entry_ids) {
                $supervised_entries = array();
                
                foreach ($entries as $entry) {
                    if (in_array($entry['id'], $entry_ids)) {
                        $supervised_entries[$entry['id']] = $entry;
                    
                        if (!empty($extra_params['custom'][$entry['id']])) {
                            $supervised_entries[$entry['id']]['custom'] = $extra_params['custom'][$entry['id']];
                        }
                    }
                }
                
                $vars = $this->_getEmailVarsGrouped($supervised_entries);
                
                $recipient = $this->getUserById($supervisor_id);
                $recipients = array($recipient);
                
                $sent = $this->_sendEmail($recipients, $vars, $rule, $automation, $extra_params);
                if (!$sent) {
                    return false;
                }

            }
        }
        
        return true;
    }
    
    
    function _getEmailVars($entry) {
        
        $vars = array();
        
        $vars[$this->etype . '.title'] = $entry['title'];
        $vars[$this->etype . '.id'] = $entry['id'];
        
        $user_fields = array('id', 'username', 'first_name', 'middle_name', 'last_name');
        $users = array('author', 'updater');
        
        foreach ($users as $user) {
            $_user = $entry[$user];
            
            foreach ($user_fields as $user_field) {
                $key = sprintf('%s.%s.%s', $this->etype, $user, $user_field);
                $vars[$key] = $_user[$user_field];
            }
            
            $key = sprintf('%s.%s.name', $this->etype, $user);
            $vars[$key] = sprintf('%s %s', $_user['first_name'], $_user['last_name']);
        }
        
        $cc = &AppController::getClientController();
        $view = ($this->etype == 'article') ? 'index' : 'file';
        $vars[$this->etype . '.link'] = $cc->getFolowLink($view, false, $entry['id']);
        
        
        list($module, $page) = ($this->etype == 'article') ? array('knowledgebase', 'kb_entry') : array('file', 'file_entry');
        $vars[$this->etype . '.link.update'] = sprintf('%sindex.php?module=%s&page=%s&action=update&id=%s', APP_ADMIN_PATH, $module, $page, $entry['id']);
        
        $status = $this->emanager->getEntryStatusData($this->etype . '_status');
        $vars[$this->etype . '.status'] = $status[$entry['active']]['title'];
        
        if ($this->etype == 'article') {
            $types = $this->emanager->getEntryStatusData('article_type');
            $vars['article.type'] = $types[$entry['entry_type']]['title'];
        }
        
        if ($this->etype == 'file') {
            $vars['file.filename'] = $entry['filename'];
        }
        
        if (!empty($entry['custom'])) {
            foreach ($entry['custom'] as $custom_id => $v) {
                $key = sprintf('%s.custom.%d.title', $this->etype, $custom_id);
                $vars[$key] = $v['title'];
                
                $key = sprintf('%s.custom.%d.value', $this->etype, $custom_id);
                $vars[$key] = (!empty($v['value'])) ? $v['value'] : '';
            }
        }
    
        return $vars;
    }
    
    
    function _getEmailVarsGrouped($entries) {
        $vars = array();
        $vars['loop'] = array();
        
        foreach($entries as $entry) {
            /*$vars['loop'][] = array(
                $this->etype . '.id' => $entry['id'],
                $this->etype . '.title' => $entry['title'] 
            );*/
            $vars['loop'][] = $this->_getEmailVars($entry);
        }
        
        $vars[$this->etype . 's.num'] = count($entries);
        $vars[$this->etype . 's.link.filtered.outdated'] = APP_ADMIN_PATH . 'index.php?module=knowledgebase&page=kb_entry&filter%5Bs%5D=4';
    
        return $vars;
    }
    
    
    function _sendEmail($recipients, $vars, $rule, $automation, $extra_params) {
        
        require_once 'core/app/AppMailSender.php';
        
        $sender = new AppMailSender;
        if (!empty($vars['loop'])) {
            $sender->parser->replacer->s_loop_tag = '<row>';
            $sender->parser->replacer->e_loop_tag = '</row>';
        }
        
        $subject = $rule[1];
        if (strlen($subject) == 0 && strlen($automation['trigger_key']) > 0) {
            $predefined_trigger = AppMsg::getMsg('trigger_predefined_msg.ini', false, $automation['trigger_key']);
            $subject = $predefined_trigger['subject'];
        }
        
        // getting a template
        $template = $rule[2];
        if (strlen($template) == 0 && strlen($automation['trigger_key']) > 0) { // predefined
            $p = new AppMailParser;
            $template = $p->getTemplate($automation['trigger_key']);
            
            $replacer = new Replacer();
            $replacer->strip_var = false;
            
            $msg = AppMsg::getMsgs('common_msg.ini');
            $template = $replacer->parse($template, $msg);
        }
        
        $parser = $sender->parser;
        $parser->assign($vars);
        
        $tvars = array(
            'from_email' => '[support_email]',
            'subject' => $subject
        );
        
        
        $mail = $sender->getMailerObj($tvars);
        
        // setting recipients
        $to_header_is_set = false;
        foreach($recipients as $recipient) {
            $email = $recipient['email'];
            $name = sprintf('%s %s', $recipient['first_name'], $recipient['last_name']);
            
            if (!$to_header_is_set) {
                $mail->AddAddress($email, $name);
                $to_header_is_set = true;
            } else {
                $mail->AddCC($email, $name);
            }
        }
        
        $mail->Body = &$parser->parse($template);
        
         return $mail->Send();
        $sent = $sender->addToPool($mail, array_search('automation', $sender->letter_type));
        
        return $sent;
    }
    
    
    // email automations
    
    // conditions
    function triggerAutomation($cond_match, $conditions, $message) {
        $is_met = true;
        
        foreach($conditions as $k => $v) {
            $key = $v['item'];
            
            if ($key == 'auto_email') {
                $is_met = $this->checkForAutomatedResponse($message['header']);
                
            } else {
                $rule = $v['rule'][0];
                $value = $v['rule'][1];
                
                if ($rule == 'contain') {
                    $is_met = (strpos($message[$key], $value) !== false);
                }
                
                if ($rule == 'not_contain') {
                    $is_met = (strpos($message[$key], $value) === false);
                }
                
                if ($rule == 'start_with') {
                    $is_met = (strpos($message[$key], $value) === 0);
                }
                
                if ($rule == 'end_with') {
                    $length = strlen($value);
                    $is_met = (substr($message[$key], -$length) === $value);
                }
                
                if ($rule == 'equal') {
                    $is_met = ($value == $message[$key]);
                }
            }
            
            // any, at least one matched
            if($cond_match == 1 && $is_met) {
                return true;
                
            } elseif ($cond_match == 2 && !$is_met) { // all, at least 1 not matched
                return false;
                
            }
        }
        
        // all matched
        if($is_met) {
           return true; 
        }
    }
    
    
    function checkForAutomatedResponse($header) {
        $auto_response_headers = array(
            'Auto-Submitted: auto-replied',
            'Auto-Submitted: auto-generated',
            'Auto-Submitted: auto-notified',
            'X-Autoreply:',
            'X-Autorespond:',
            'X-Auto-Response-Suppress:',
            'Precedence: auto_reply',
            'X-Precedence: auto_reply',
            'X-Failed-Recipients'
        );
        
        foreach ($auto_response_headers as $v) {
            if (stripos($header, $v) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    
    function createArticle($rule, $message, $users) {
        
        require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntry.php';
                                
        $eobj = unserialize($rule[0]);
        $eobj->set('date_posted', null);
        
        $this->_parseEmailPlaceholders($eobj, $message);
        $this->_checkSender($eobj, $message, $users);
        
        $eobj->set('body_index', addslashes(RequestDataUtil::getIndexText($eobj->get('body'))));
        
        $emanager = DataConsistencyModel::getEntryManager('admin', 1);
        $entry_id = $emanager->save($eobj);
        
        return true;
    }
    
    
    function createDraft($rule, $message, $users) {
        
        require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntry.php';
        require_once APP_MODULE_DIR . 'knowledgebase/draft/inc/KBDraft.php';
        require_once APP_MODULE_DIR . 'knowledgebase/draft/inc/KBDraftModel.php';
        require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryModel.php';
        
        $eobj = unserialize($rule[0]);
        $eobj->set('date_posted', null);
        
        $this->_parseEmailPlaceholders($eobj, $message);
        $this->_checkSender($eobj, $message, $users);
        
        $draft_obj = new KBDraft;
        $draft_obj->set('entry_type', 1);
        $draft_obj->set('date_posted', date('Y-m-d H:i:s'));
        $draft_obj->set('title', $eobj->get('title'));
        $draft_obj->set('author_id', $eobj->get('author_id'));
        $draft_obj->properties['updater_id'] = $eobj->get('author_id');
        
        $entry_obj = clone $eobj;
        $entry_obj = RequestDataUtil::stripslashesObj($entry_obj);
        $entry_obj = addslashes(serialize($entry_obj));
        $draft_obj->set('entry_obj', $entry_obj);
        
        $emanager = new KBDraftModel;
        
        $entry_id = $emanager->save($draft_obj);
        
        // workflow
        $approval = !empty($rule[1]);
        if ($approval) {
            $options = array(
                'user_id' => $eobj->get('author_id'),
                'source' => 'email'
            );
            
            $workflow = $emanager->getAppliedWorkflow($options);
            if ($workflow) {
                $entry_manager = new KBEntryModel;
                $assignees = $emanager->getAssignees($draft_obj, $eobj, $entry_manager, $workflow, 1);
                $emanager->moveToStep($entry_id, $workflow['id'], $assignees, 1, '', '', 1);
            }
        }
        
        return true;
    }
    
    
    function createNews($rule, $message, $users) {
        
        require_once APP_MODULE_DIR . 'news/inc/NewsEntry.php';
                                
        $eobj = unserialize($rule[0]);
        
        $date_posted = ($eobj->get('date_posted') == '[date_received]') ? $message['date'] : time();
        $eobj->set('date_posted', date('Y-m-d H:i:s', $date_posted));
        $eobj->set('date_updated', $eobj->get('date_posted'));
        
        $this->_parseEmailPlaceholders($eobj, $message);
        $this->_checkSender($eobj, $message, $users);
        
        $eobj->set('body_index', addslashes(RequestDataUtil::getIndexText($eobj->get('body'))));
        
        $emanager = DataConsistencyModel::getEntryManager('admin', 3);
        $entry_id = $emanager->save($eobj);
        
        return true;
    }
    
    
    function _parseEmailPlaceholders($eobj, $message) {
        $replacer = new Replacer;
        
        $replacer->s_var_tag = "[";
        $replacer->e_var_tag = "]";
        $replacer->strip_var = false;
                        
        $replacer->assign('message.from.email', $message['from']);
        $replacer->assign('message.from.name', $message['name']);
        $replacer->assign('message.to.email', $message['to']);
        $replacer->assign('message.cc.email', (!empty($message['cc'])) ? $message['cc'] : '');
        $replacer->assign('message.subject', $message['subject']);
        $replacer->assign('message.content', addslashes($message['body']));
        $replacer->assign('message.date.received', date('Y-m-d H:i:s', $message['date']));
        $replacer->assign('date_created', date('Y-m-d H:i:s'));
        
        $title = $replacer->parse($eobj->get('title'));
        $eobj->set('title', $title);
        
        $body = $replacer->parse($eobj->get('body'));
        $eobj->set('body', $body);
    }
    
    
    function _checkSender($eobj, $message, $users) {
        $check_sender = @$eobj->get('check_sender');
        if ($check_sender && !empty($users[$message['from']])) {
            $user_id = $users[$message['from']];
            $eobj->set('author_id', $user_id);
            $eobj->properties['updater_id'] = $user_id;
        }
        
        unset($eobj->properties['check_sender']);
    }
    
    
    function getUserByEmail($emails) {
        $emails = implode('","', $emails);
        $emails = sprintf('"%s"', $emails);
        
        $sql = "SELECT * FROM {$this->tbl->user} WHERE email IN (%s)";
        $sql = sprintf($sql, $emails);
        $result = $this->db->Execute($sql);
        
        if ($result) {
            $rows = $result->GetAssoc();
            $data = array();
            foreach ($rows as $user_id => $row) {
                $data[$row['email']] = $user_id;
            }
            
            return $data;
            
        } else {
            trigger_error($this->db->ErrorMsg());
            return $result;
        }
        
        return $result;
    }
    
    
    // logs
    function logFinished($trigger, $output) {
        return $this->log($trigger, $output, 1);
    }
    
    
    function logFailed($trigger, $output) {
        return $this->log($trigger, $output, 0);
    }
    
    
    function log($trigger, $output, $exitcode) {
        $sql = "INSERT {$this->tbl->log_trigger}
            (trigger_id, trigger_type, entry_type, output, exitcode)
            VALUES (%s, %s, %s, '%s', %d)";
        $sql = sprintf($sql, $trigger['id'], $trigger['trigger_type'], $trigger['entry_type'], $output, $exitcode);
        $result = $this->db->Execute($sql);
        if(!$result) {
            trigger_error($this->db->ErrorMsg());
        }

        return $result;
    }
    
}
?>