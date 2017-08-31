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


class ReportEntryExport extends AppExport
{
    
    
    function &getXml($obj, $manager, $view) {
        
        $view->addMsg('report_msg.ini');
        
        $tpl = new tplTemplatez($view->template_dir . 'export_xml.html');
        $tpl->strip_vars = true;
        
        $entry_managers = array();
        $entry_managers[1] = new KBEntryModel;
        $entry_managers[2] = new FileEntryModel;
        
        // categories
        $categories = array();
        foreach ($entry_managers as $entry_type => $entry_manager) {
            $categories[$entry_type] = $entry_manager->getCategoryRecords();
            $categories[$entry_type] = RequestDataUtil::stripVarsXml($categories[$entry_type], array(), false);
        }
        
        // filter sql
        $params = $view->getFilterSql($manager, $entry_managers, $categories);
        $manager->setSqlParams($params['where']);
        $manager->setSqlParamsFrom($params['from']);
        
        // get records
        $limit = $view->getLimit();
        $sort = $view->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
        
        $rows = RequestDataUtil::stripVarsXml($manager->getRecords($limit));
        
        foreach ($rows as $row) {
            $tpl->tplParse($row, 'row');     
        }
        
        // the whole period
        if (!$view->start_day) {
            $date_period = $view->msg['all_msg'];
            
        } else {
            $date_period = sprintf('%s - %s', $view->start_day, $view->end_day);    
        }
        
        $tpl->tplAssign('date_period', $date_period);
        
        $report_type = $manager->getReportTypeSelectRange($view->msg);
        $tpl->tplAssign('report_type_formatted', $report_type[$view->report_type]);
        
        $tpl->tplAssign('encoding', $view->encoding);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }

     
    function &getCsv($obj, $manager, $view, $opts) {

        $view->addMsg('report_msg.ini');
        $view->addMsg('user_msg.ini');
        $view->addMsg('error_msg.ini');

        $entry_managers = array();
        $entry_managers[1] = new KBEntryModel;
        $entry_managers[2] = new FileEntryModel;
        
        // categories
        $categories = array();
        foreach ($entry_managers as $entry_type => $entry_manager) {
            $categories[$entry_type] = $entry_manager->getCategoryRecords();
            $categories[$entry_type] = RequestDataUtil::stripVarsXml($categories[$entry_type], array(), false);
        }
        
        // filter sql
        $params = $view->getFilterSql($manager, $entry_managers, $categories);
        $manager->setSqlParams($params['where']);
        $manager->setSqlParamsFrom($params['from']);
        
        // get records
        $limit = $view->getLimit();
        $sort = $view->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
        
        $rows = $manager->getRecords($limit);
        
        $report_type = $manager->getReportTypeSelectRange($view->msg);

        $titles = array(
            $view->msg['type_msg'],
            $view->msg['entry_id_msg'],
            $view->msg['value_msg']);
        
        $total = array($view->msg['total_msg'], '');
        
        // with header option
        if (!empty($opts['hr'])) {
            $data[] = $titles;        
        }
        
        $total_value = 0;
                   
        foreach ($rows as $row) {
            $a = array(); 
            
            $a[] = $report_type[$view->report_type];
            $a[] = $row['entry_id'];
            $a[] = $row['value'];
            
            $total_value += $row['value'];
            
            $data[] = $a;
        }
        
        // with total option
        if (!empty($opts['tr'])) {
            $total[] = (string) $total_value;
            $data[] = $total;
        }
        
        $data = RequestDataUtil::parseCsv($data, $opts);
        return $data;
    }
}
?>