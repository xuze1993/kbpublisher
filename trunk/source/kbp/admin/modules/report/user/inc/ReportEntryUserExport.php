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

require_once 'core/app/AppExport.php'; 


class ReportEntryUserExport extends AppExport
{
    
    
    function &getXml($obj, $manager, $view) {
        
        $view->addMsg('report_msg.ini');
        
        // filter sql
        $params = $view->getFilterSql($manager);
        $manager->setSqlParams($params);
        
        // get records
        $sort = $view->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
        
        $entry_types = $manager->getEntrySelectRange();
        $action_types = $manager->getUserActionSelectRange($view->entry_type);
        
        if (!empty($_GET['filter']['invert'])) {
            $tpl = new tplTemplatez($view->template_dir . 'export_user_xml.html');
            $tpl->strip_vars = true;
            
            $rows = RequestDataUtil::stripVarsXml($manager->getUnrelatedUsers());
            
            foreach($rows as $row) {
                $tpl->tplParse($row, 'row');
            }
            
        } else {
            $tpl = new tplTemplatez($view->template_dir . 'export_xml.html');
            $tpl->strip_vars = true;
            
            $rows = RequestDataUtil::stripVarsXml($manager->getRecords());
            
            $user_ids = $manager->getValuesString($rows, 'user_id');
            $users = (empty($rows)) ? array() : $manager->getUserByIds($user_ids);
            
            foreach ($rows as $row) {
                $entry_type = $entry_types[$row['entry_type']];
                $tpl->tplAssign('entry_type_formatted', $entry_type);
                
                $action_type = $action_types[$row['action_type']];
                $tpl->tplAssign('action_type_formatted', $action_type);
                
                $user = (empty($users[$row['user_id']])) ? '--' : PersonHelper::getShortName($users[$row['user_id']]);
                $tpl->tplAssign('user', $user);
                
                $tpl->tplParse($row, 'row');     
            }
        }
        
        
        // the whole period
        if (!$view->start_day) {
            $date_period = $view->msg['all_msg'];
            
        } else {
            $date_period = sprintf('%s - %s', $view->start_day, $view->end_day); 
        }
        
        $tpl->tplAssign('date_period', $date_period);
        $tpl->tplAssign('encoding', $view->encoding);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }

     
    function &getCsv($obj, $manager, $view, $opts) {

        $view->addMsg('report_msg.ini');
        $view->addMsg('error_msg.ini');
        $view->addMsg('user_msg.ini');
        
        // filter sql
        $params = $view->getFilterSql($manager);
        $manager->setSqlParams($params);
        
        // get records
        $sort = $view->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
        
        $entry_types = $manager->getEntrySelectRange();
        $action_types = $manager->getUserActionSelectRange($view->entry_type);
        
        if (!empty($_GET['filter']['invert'])) {
            
            $rows = $manager->getUnrelatedUsers();
            
            $titles = array(
                $view->msg['id_msg'],
                $view->msg['username_msg'],
                $view->msg['first_name_msg'],
                $view->msg['last_name_msg'],
                $view->msg['email_msg']
            );
            
            // header option
            if (!empty($opts['hr'])) {
                $data[] = $titles;        
            }
                       
            foreach ($rows as $row) {
                $a = array();
                
                $a[] = $row['id'];
                $a[] = $row['username'];
                $a[] = $row['first_name'];
                $a[] = $row['last_name'];
                $a[] = $row['email'];
                   
                $data[] = $a;
            }
            
        } else {
            $rows = $manager->getRecords();
            
            $user_ids = $manager->getValuesString($rows, 'user_id');
            $users = (empty($rows)) ? array() : $manager->getUserByIds($user_ids);
            
            $titles = array(
                $view->msg['type_msg'],
                $view->msg['action_msg'],
                $view->msg['entry_id_msg'],
                $view->msg['user_id_msg'],
                $view->msg['user_msg'],
                $view->msg['user_ip_msg'],
                $view->msg['date_msg']
            );
            
            // header option
            if (!empty($opts['hr'])) {
                $data[] = $titles;        
            }
                       
            foreach ($rows as $row) {
                $a = array();
                
                $a[] = $entry_types[$row['entry_type']];
                $a[] = $action_types[$row['action_type']];
                $a[] = $row['entry_id'];
                $a[] = $row['user_id'];
                $a[] = (empty($users[$row['user_id']])) ? '--' : PersonHelper::getShortName($users[$row['user_id']]);
                $a[] = $row['user_ip_formatted'];
                $a[] = $row['date_action'];
                   
                $data[] = $a;
            }
        }
        
        $data = RequestDataUtil::parseCsv($data, $opts);
        return $data;
    }
}
?>