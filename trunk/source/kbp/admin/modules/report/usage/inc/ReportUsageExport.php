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


class ReportUsageExport extends AppExport
{
    
    
    function &getXml($obj, $manager, $view) {
        
        $view->addMsg('report_msg.ini');
        
        
        $tpl = new tplTemplatez($view->template_dir . 'export_xml.html');
        $tpl->strip_vars = true;
        
        // filter sql
        $params = $view->getFilterSql($manager);
        $manager->setSqlParams($params);
        $filter = $view->getReportToGenerate();      
        
        // group
        $manager->sql_params_group = ($view->group_field) ? 'report_id,' . $view->group_field : 'report_id';
        
        // get records
        $rows = RequestDataUtil::stripVarsXml($manager->getRecords($view->force_index, $view->week_start));
        //echo '<pre>', print_r($rows, 1), '</pre>';
    
        $rows_total = $manager->getRecordsTotal();

        //total / types
        $a = array();
        foreach ($filter as $k => $v) {
            $a['value'] = (isset($rows_total[$v])) ? $rows_total[$v] : '--';
            $a['type_id'] = $v;
            $a['type_title'] = $view->msg['report_type'][$manager->report_type[$v]];            
             $tpl->tplParse($a, 'row_type');
        }
        

        // list records
        foreach($view->date_range as $date) {
            foreach($filter as $k => $v) {
                $a1['value'] = (isset($rows[$date][$v])) ? $rows[$date][$v] : '--';
                $a1['value_formatted'] = (isset($rows[$date][$v])) ? number_format($rows[$date][$v], 0, '', ' ') : '--';
                $a1['type_id'] = $v;
                $a1['type_title'] = $view->msg['report_type'][$manager->report_type[$v]];
                $tpl->tplParse($a1, 'row/row_item');
            }
        
            $row['date'] = $date;
            $row['date_formatted'] = $view->getFormatedDateReport($date);
            
            $tpl->tplSetNested('row/row_item');                       
            $tpl->tplParse($row, 'row');
        }        
        
        $tpl->tplAssign('title', '');
        $tpl->tplAssign('compare', 0);
        $tpl->tplAssign('range', $view->getRangeToGenerate());
        
        $d = sprintf('%s - %s', $view->start_day, $view->end_day);
        $tpl->tplAssign('date_period', $d);       
        
        $tpl->tplAssign('encoding', $view->encoding);
        
        $tpl->tplParse();                   
        return $tpl->tplPrint(1);
    }

     
    function &getCsv($obj, $manager, $view, $opts) {

        $view->addMsg('report_msg.ini');


        // filter sql
        $params = $view->getFilterSql($manager);
        $manager->setSqlParams($params);
        $filter = $view->getReportToGenerate();
        
        // group
        $manager->sql_params_group = ($view->group_field) ? 'report_id,' . $view->group_field : 'report_id';
        
        // get records
        $rows = $manager->getRecords($view->force_index, $view->week_start);   
        $rows_total = $manager->getRecordsTotal();

        $titles[] = $view->msg['date_msg'];
        $total = array($view->msg['total_msg']);
        
        foreach ($filter as $k => $v) {
            $value = (isset($rows_total[$v])) ? $rows_total[$v] : '--';
            $total[] = $value; 
            $titles[] = $view->msg['report_type'][$manager->report_type[$v]];
        }
        
        // with header option
        if (!empty($opts['hr'])) {
            $data[] = $titles;        
        }


        // list records
        foreach($view->date_range as $date) {
            $a = array();
            
            $a[] = (string) $date;
                                   
            foreach($filter as $k => $v) {
                $a[] = (isset($rows[$date][$v])) ? $rows[$date][$v] : '--';
            }

            $data[] = $a; 
        }
        
        // with total option
        if (!empty($opts['tr'])) {
            $data[] = $total;
        }
        
        $data = RequestDataUtil::parseCsv($data, $opts);
        return $data;
    }
}
?>