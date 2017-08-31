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


class ReportStatView_list extends AppView
{
    
    var $tmpl = 'tmpl_list_entry.html';
    var $tmpl2 = 'tmpl_list_common.html';
    
    
    function execute(&$obj, &$manager, $mode) {
    
        $this->addMsg('report_msg.ini');
        
        $no_id_modes = array('most_popular', 'less_popular');
        $tmpl = (in_array($mode, $no_id_modes)) ? $this->tmpl2 : $this->tmpl;
                
        $tpl = new tplTemplatez($this->template_dir . $tmpl);


        // header generate
        // $limit_range = array(10, 20, 50, 100);
        // $bp =& $this->pageByPage($manager->limit, $manager->getRecordsCount($mode), true, $limit_range);
        
        $bp_options = array('limit_range'=>array(10, 20, 50, 100));
        $bp =& $this->pageByPage($manager->limit, $manager->getRecordsCount($mode), $bp_options);
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, false, false));
        
        $tpl->tplAssign('header_title', sprintf('<b>%s</b>', $this->msg[$mode . '_msg'])); 

        // get records                                 
        $rows = $this->stripVars($manager->getRecords($mode, $bp->limit, $bp->offset));

        foreach($rows as $row) {
            
            if (isset($row['first_name'])) {
                $row['title'] = $row['first_name'] . $row['last_name'];
            }
            
            $tpl->tplParse(array_merge($row, $this->msg), 'row');
        }
        
        $more = array('type' => 'xml', 'mode' => $mode, 'limit' => $bp->limit);
        $xml_link = $this->getActionLink('file', false, $more);
        $tpl->tplAssign('xml_link', $xml_link);        
        
        $more = array('type' => 'csv', 'mode' => $mode, 'limit' => $bp->limit);
        $csv_link = $this->getActionLink('file', false, $more);
        $tpl->tplAssign('csv_link', $csv_link);
        
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }

}
?>