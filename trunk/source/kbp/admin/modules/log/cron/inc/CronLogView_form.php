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


class CronLogView_form extends AppView
{
    
    var $template = 'form.html';
    
    
    function execute(&$obj, &$manager, $data) {
        
        $this->addMsg('log_msg.ini');        
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);


        $formatted_date = $this->getFormatedDate($data['date_started_ts'], 'datetimesec');
        $interval_date = $this->getTimeInterval($data['date_started_ts']);                
        $tpl->tplAssign('formatted_date_started', $formatted_date);
        $tpl->tplAssign('interval_date_started', $interval_date);


        if($obj->get('date_finished')) {
            $formatted_date = $this->getFormatedDate($data['date_finished_ts'], 'datetimesec');
            $interval_date = $this->getTimeInterval($data['date_finished_ts']);                
            $tpl->tplAssign('formatted_date_finished', $formatted_date);
            $tpl->tplAssign('interval_date_finished', $interval_date);
        }
        

        $is_error = ($obj->get('exitcode')) ? '' : '<img src="images/icons/bullet.svg" />';
        $tpl->tplAssign('is_error', $is_error);

        $magic = array_flip($manager->getCronMagic());
        $title = $this->msg['cron_type'][$magic[$obj->get('magic')]];
        $tpl->tplAssign('range_title', $title);
        
        if(APP_DEMO_MODE) { 
            $obj->set('output', 'Hidden in DEMO mode');
        }        
        
        
        $tpl->tplAssign($this->setCommonFormVars($obj));
        // $tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
}
?>