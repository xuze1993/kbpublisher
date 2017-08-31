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

class MailPoolLogView_list extends AppView
{
        
    var $tmpl = 'list.html';
    
    
    function execute(&$obj, &$manager) {
    
        $this->addMsg('user_msg.ini');
        $this->addMsg('log_msg.ini');
    
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        // filter
        $params = $this->getFilterSql($manager);
        $manager->setSqlParams($params);

        // sort generate
        $sort = &$this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());

        // header generate
        $bp =& $this->pageByPage($manager->limit, $manager->getRecordsSql());
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, $this->getFilter($manager), false));
        
        // get records
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        
        $letter_type = $manager->getLetterTypeSelectRange($this->msg);
        
        $i = 0;

        foreach($rows as $row) {
            
            $obj->set($row);
            
            $tpl->tplAssign('date_created_formatted', $this->getFormatedDate($obj->get('date_created'), 'datetime'));
            $tpl->tplAssign('date_created_interval', $this->getTimeInterval($obj->get('date_created')));
            
            // date sent
            $date_sent_formatted = '--';
            if($obj->get('date_sent')) {
                $str = '<b>%s</b> (%s)';
                $date_sent_formatted = sprintf($str, $this->getTimeInterval($obj->get('date_sent')), 
                                                     $this->getFormatedDate($obj->get('date_sent'), 'datetime'));
            }
            $tpl->tplAssign('date_sent_formatted', $date_sent_formatted);
            
            $tpl->tplAssign('letter_type_str',  $letter_type[$obj->get('letter_type')]);
            $tpl->tplAssign('is_sent', ($obj->get('status') == 1) ? $this->msg['yes_msg'] : $this->msg['no_msg']);
            
            $row['view_msg'] = $this->msg['view_msg'];

            $tpl->tplAssign($this->getViewListVars($obj->get('id'), $obj->get('status'), $obj->get('failed')));
            $tpl->tplParse($row, 'row');
        }
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    

    function getViewListVars($record_id = false, $active = false, $failed = true) {
        
        $row = parent::getViewListVars($record_id, $active);
        
        $row['style'] = ($failed > 0 && $active != 1) ? 'color: red;' : '';
        $row['failed'] = ($failed == 0 && $active == 1) ? 1 : $failed;
        
        return $row;
    }
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);

        $sort->setSortItem('date_created_msg',  'date_created', 'date_created', $this->msg['date_created_msg'], 2);
        $sort->setSortItem('date_executed_msg', 'date_sent', 'date_sent', $this->msg['date_executed_msg']);
        $sort->setSortItem('type_msg','letter_type', 'letter_type', $this->msg['type_msg']);
        $sort->setSortItem('num_tries_msg','failed', 'failed', $this->msg['num_tries_msg']);
        $sort->setSortItem('mail_sent_msg','status', 'status', $this->msg['mail_sent_msg']); 
        
        return $sort;
    }
    
    
    function getFilter($manager) {
        
        @$values = $_GET['filter'];
          
        if(isset($values['q'])) {
            $values['q'] = RequestDataUtil::stripVars($values['q'], array(), true);
            $values['q'] = trim($values['q']);
        }
        
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
        
        $select = new FormSelect();
        $select->select_tag = false;
        
        // type
        $letter_type = $manager->getLetterTypeSelectRange($this->msg); 
        $select->setRange($letter_type, array('all'=> '__'));

        @$status = $values['t'];
        $tpl->tplAssign('type_select', $select->select($status));
        
        
        // status
        $select->setRange(array(
			'all'=> '__', 
			'1' => $this->msg['yes_msg'], 
			'0' => $this->msg['no_msg'])
		);

        @$status = $values['s'];
        $tpl->tplAssign('status_select', $select->select($status));  
        

        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql($manager) {

        $arr = array();
        $arr_select = array();       
        
        @$values = $_GET['filter']; 

        // letter type
        @$v = $values['t'];
        if($v != 'all' && !empty($v)) {
            $v = (int) $v;
            $arr[] = "AND letter_type = '$v'";
        }

        // mail sent
        @$v = $values['s'];
        if($v != 'all' && isset($v)) {
            $v = (int) $v;
            $v = ($v == 0) ? '0,2' : 1;
            $arr[] = "AND status IN ($v)";
        }
		
        // search str
        @$v = $values['q'];
        if(!empty($v)) {
            $v = trim($v);
            $v = addslashes(stripslashes($v));
            $arr[] = "AND (message LIKE '%{$v}%' OR failed_message LIKE '%{$v}%')";
        }

        $arr = implode(" \n", $arr);

        return $arr;
    }
}
?>