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


class ReportEntryExport_entry extends AppExport
{
    
    
    function &getXml($obj, $manager, $view) {
        
        $view->addMsg('report_msg.ini');
        
        
        $tpl = new tplTemplatez($view->template_dir . 'export_entry_xml.html');
        $tpl->strip_vars = true;
        
        // filter sql
        $params = $view->getFilterSql($obj, $manager);
        $manager->setSqlParams($params);
        
        $entry_ids = $obj->get('entry_id');
        
        // group
        $manager->sql_params_group = ($view->group_field) ? 'entry_id,' . $view->group_field : 'entry_id';
        
        // get records
        $rows = RequestDataUtil::stripVarsXml($manager->getEntryRecords($view->force_index, $view->week_start));
        //echo '<pre>', print_r($rows, 1), '</pre>';
        
        $report_type = $view->getReportToGenerate();
        $entry_ids_str = implode(',', $obj->get('entry_id'));
        $titles = RequestDataUtil::stripVarsXml($manager->getTitlesByIds($report_type, $entry_ids_str));
        
        $report_types = $manager->getReportTypeSelectRange($view->msg);
        $tpl->tplAssign('report_type_formatted', $report_types[$report_type]);
        
        // titles, total
        $a = array();
        foreach ($entry_ids as $v) {
            $hits = $manager->getValuesArray($rows, $v);
            $a['total'] = array_sum($hits);        
            
            $a['type_id'] = $v;
            $a['type_title'] = (empty($titles[$v])) ? '--' : $titles[$v];            
            $tpl->tplParse($a, 'row_entry');
        }
        

        // list records
        foreach($view->date_range as $date) {
            foreach($entry_ids as $v) {
                $a1['value'] = (isset($rows[$date][$v])) ? $rows[$date][$v] : '--';
                $a1['value_formatted'] = (isset($rows[$date][$v])) ? number_format($rows[$date][$v], 0, '', ' ') : '--';
                $a1['type_id'] = $v;
                $a1['type_title'] = (empty($titles[$v])) ? '--' : $titles[$v];
                $tpl->tplParse($a1, 'row/row_item');
            }
        
            $row['date'] = $date;
            
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
        $params = $view->getFilterSql($obj, $manager);
        $manager->setSqlParams($params);
        
        $entry_ids = $obj->get('entry_id');
        
        // group
        $manager->sql_params_group = ($view->group_field) ? 'entry_id,' . $view->group_field : 'entry_id';
        
        // get records
        $rows = $manager->getEntryRecords($view->force_index, $view->week_start);
        
        $report_type = $view->getReportToGenerate();
        $entry_ids_str = implode(',', $obj->get('entry_id'));
        $entry_titles = $manager->getTitlesByIds($report_type, $entry_ids_str);

        $titles[] = $view->msg['date_msg'];
        $total = array($view->msg['total_msg']);
        
        foreach ($entry_ids as $k => $v) {
            $hits = $manager->getValuesArray($rows, $v);
            $total[] = (string) array_sum($hits);
            $titles[] = (empty($entry_titles[$v])) ? '--' : $entry_titles[$v];
        }
        
        // with header option
        if (!empty($opts['hr'])) {
            $data[] = $titles;
        }

        // list records
        foreach($view->date_range as $date) {
            $a = array();
            
            $a[] = (string) $date;
                                   
            foreach($entry_ids as $v) {
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