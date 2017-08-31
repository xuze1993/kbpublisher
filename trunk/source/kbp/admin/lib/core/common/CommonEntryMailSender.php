<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KnowledgebasePublisher package                   |
// | KnowledgebasePublisher - web based knowledgebase publishing tool          |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005-2008 Evgeny Leontev                                    |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+

require_once 'core/app/AppMailSender.php';


class CommonEntryMailSender extends AppMailSender
{
    
    
    // DRAFTS //-------------------------
    
    function sendDraftReview($vars, $assignees, $use_pool = false) {
        
        $this->letter_key = 'draft_approval_request';

        // parser
        $parser = &$this->parser;
        $parser->assign($vars);
        // $parser->assignUser($vars);
        
        $tvars = $this->getTemplateVars();
        
        if($assignees) {
            $assignees  = (is_array($assignees)) ? implode(',', $assignees) : $assignees; 
            $users = $this->model->_getUser($assignees);
            if($users) {
                $mail = &$this->getMailerObj($tvars);
                $mail->ClearAddresses(); // remove [to_email]
                foreach($users as $k => $v) {
                    $mail->AddAddress($v['email']);
                }
            }
            
        } else {
            $tvars['to_email'] = '[admin_email]';
            $mail = &$this->getMailerObj($tvars);
        }
        
        $mail->Body = $parser->parse($this->getTemplate());
        
        // echo "<pre>"; print_r($mail); echo "</pre>";
        // exit();
        
        if($use_pool) {
            $sent = $this->addToPool($mail, array_search('workflow', $this->letter_type));
        } else {
            $sent = $mail->Send();
        }
        
        // if (!$send) {
        //     trigger_error("Cannot send mail: {$mail->ErrorInfo}");
        // }
        
        return $sent;
    }
    
    
    function sendDraftRejectionToSubmitter($vars, $use_pool = false) {
        
        $this->letter_key = 'draft_rejection';  
        
        // parser
        $parser = &$this->parser;
        $parser->assign($vars);
        
        // user
        $user = $this->model->getUser($vars['user_id']);
        $parser->assignUser($user);
        
        // mail object
        $mail = &$this->getMailerObj($this->getTemplateVars());
        $mail->Body = $parser->parse($this->getTemplate());
        
        //echo "<pre>"; print_r($mail); echo "</pre>"; 
        //exit();   
        
        if($use_pool) {
            $sent = $this->addToPool($mail, array_search('workflow', $this->letter_type));
        } else {
            $sent = $mail->Send();
        }
        
        // if (!$send) {
        //     trigger_error("Cannot send mail: {$mail->ErrorInfo}");
        // }
        
        return $sent;
    }
    
    
    function sendDraftRejectionToAssignee($vars, $use_pool = false) {
        
        $this->letter_key = 'draft_rejection_to_approver';   
        
        // parser
        $parser = &$this->parser;
        $parser->assign($vars);

        // user
        $user = $this->model->getUser($vars['user_id']);
        $parser->assignUser($user);
        
        // mail object
        $mail = &$this->getMailerObj($this->getTemplateVars());
        $mail->Body = $parser->parse($this->getTemplate());
        
        //echo "<pre>"; print_r($mail); echo "</pre>"; 
        //exit();    
        
        if($use_pool) {
            $sent = $this->addToPool($mail, array_search('workflow', $this->letter_type));
        } else {
            $sent = $mail->Send();
        }
        
        // if (!$send) {
        //     trigger_error("Cannot send mail: {$mail->ErrorInfo}");
        // }
        
        return $sent;
    }
    
    
    function sendDraftPublication($vars, $use_pool = false) {
        
        $this->letter_key = 'draft_publication';  
        
        // parser
        $parser = &$this->parser;
        $parser->assign($vars);

        // user
        $user = $this->model->getUser($vars['user_id']);
        $parser->assignUser($user);
        
        // mail object
        $mail = &$this->getMailerObj($this->getTemplateVars());
        $mail->Body = $parser->parse($this->getTemplate());
        
        //echo "<pre>"; print_r($mail); echo "</pre>"; 
        //exit();    
        
        if($use_pool) {
            $sent = $this->addToPool($mail, array_search('workflow', $this->letter_type));
        } else {
            $sent = $mail->Send();
        }
        
        // if (!$send) {
        //     trigger_error("Cannot send mail: {$mail->ErrorInfo}");
        // }
        
        return $sent;
    }
    
    
    // function setDarftMail($vars) {
    //     
    //     // parser
    //     $parser = &$this->parser;
    //     $parser->assign($vars);
    // 
    //     // user
    //     if($vars['user_id']) {
    //         $user = $this->model->_getUser($vars['user_id']);
    //         $parser->assignUser($user);   
    //     }
    //     
    //     // mail object
    //     $mail = &$this->getMailerObj($this->getTemplateVars());
    //     $mail->Body = $parser->parse($this->getTemplate());
    //     
    //     return $mail;
    // }
    
}
?>