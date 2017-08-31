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


class CronLogView_list_group extends AppView
{
    
    var $template = 'list_group.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('log_msg.ini');
        

        $tpl = new tplTemplatez($this->template_dir . $this->template);

        $magic = $manager->getCronMagic();
        foreach($magic as $title => $num) {
        
            $row = $this->stripVars($manager->getSummaryRecord($num));
            if($row) {
                
                $str = '%s - %s';
                $start_date = $this->getFormatedDate($row['date_started_ts'], 'datetimesec');
                $finish_date = $this->getFormatedDate($row['date_finished_ts'], 'datetimesec');                
                $row['date_range'] = sprintf($str, $start_date, $finish_date);
                
                $str = '<b>%s</b>&nbsp;&nbsp;(%s)';
                $formatted_date = $this->getFormatedDate($row['date_finished_ts'], 'datetime');
                $interval_date = $this->getTimeInterval($row['date_finished_ts']);                
                $row['last_executed'] = sprintf($str, $interval_date, $formatted_date);
                
                $row['is_error'] = ($row['exitcode']) ? '' : '<img src="images/icons/bullet.svg" />';
                $row['output2'] = $this->jsEscapeString(nl2br($row['output']));
                $row['output2'] = $this->getSubstringSign($row['output2'], 350);
                
                if(APP_DEMO_MODE) { 
                    $row['output2'] = 'Hidden in DEMO mode';
                }
                            
            } else {
                $row = array();
                $row['id'] = false;
                $row['exitcode'] = 1;
                $row['date_range'] = '';
                $row['last_executed'] = '--';
                $row['is_error'] = '';
                $row['output2'] = '';
            }
            
            $row['title'] = $this->msg['cron_type'][$title];

            $tpl->tplAssign($this->getViewListVarsCustom($row['id'], $row['exitcode'], $num));
            
            $tpl->tplParse($row, 'row');
        }


        //$tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getViewListVarsCustom($id, $active, $magic) {
        
        $row = parent::getViewListVars($id, $active);
        $row['style'] = ($active == 0) ? 'color: red;' : '';
        
        $row['view_msg'] = $this->msg['view_msg'];
        $row['magic_link'] = $this->getLink('this', 'this', 'this', false, array('filter[s]'=>$magic));
        $row['magic_img'] = $this->getImgLink($row['magic_link'], 'load', $this->msg['detail_msg']);        
        
        if(!$id) {
            $row['detail_link'] = '';
            $row['view_msg'] = '';
            
            $row['magic_link'] = '';
            $row['magic_img'] = '';            
        }
        
        return $row;
    }
}
?>