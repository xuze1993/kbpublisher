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


class SubscriptionView_list extends AppView
{
    
    var $admin_view = true;
    
    
    function getPageByPage($manager) {
        
        $rq = new RequestData($_GET, array('id'));
        $type = (int) $rq->type;
        $user_id = AuthPriv::getUserId();
        
        if (array_key_exists($rq->type, $manager->types)) {
            $manager->setSqlParams("AND entry_type = '$type'");
            $manager->setSqlParams("AND user_id = '$user_id'");
        }
        
        $mobile_view = false;
        if (isset($this->controller->setting)) {
            $mobile_view = ($this->controller->getSetting('view_format') == 'mobile');
        }
        
        $bp_class = ($mobile_view) ? 'mobile' : 'form';
        $bp_options = array('class'=>$bp_class);
        $bp =& $this->pageByPage($manager->limit, $manager->getRecordsSql(), $bp_options);
        // $bp =& $this->pageByPage($manager->limit, $manager->getRecordsSql(), true, array(), $bp_class);
        
        return $bp;
    }
    
    
    static function getAll($manager) {
         
        $user_id = AuthPriv::getUserId();
        
        $manager->setSqlParams("AND entry_id = '0'", null, true);
        $manager->setSqlParams("AND user_id = '$user_id'");
        
        $rows = $manager->getRecords();
        $types = array();
        
        foreach($rows as $row) {
            $types[] = $row['entry_type'];           
        }
        
        return $types;
    }
    

    static function getAllRowsCounts($manager) {
        
        $data = array();
        $rows = $manager->getRowsCount(AuthPriv::getUserId());

        foreach($manager->types as $type_id => $type_key) {
            $num = (isset($rows[$type_id])) ? $rows[$type_id] : 0;
            $data[$type_id] = $num;
        }
   
        return $data;
    }
    
    
    function getTitle($manager, $type_id = false) {
        $type_id = ($type_id == false) ? $_GET['type'] : $type_id;
        $key = $manager->types[$type_id];
        
        return sprintf('<b class="subsc_type_title">%s</b>', $this->msg[$key . '_subsc_msg']);
    }

    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        $sort->setCustomDefaultOrder('user_num', 2);
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        
        //$sort->setSortItem('title_msg', 'title', 'title', $this->msg['title_msg'], 1);
        
        return $sort;
    }
    
    
    // For integration with client view
    // reassigning some functions 
    
    function subscriptionHeaderList($nav, $title, $button, $mobile_view) {
        if($this->admin_view) {
            return $this->titleHeaderList($nav, $title, $button);
        } else {
            return $this->titleHeaderList2($nav, $title, $button, false, $mobile_view);
        }
    }
    
    
    function getViewListVars($record_id = false, $active = NULL, $own_record = true) {
        if($this->admin_view) {
            return parent::getViewListVars($record_id, $active, $own_record);
        } else {
            return $this->getViewListVars2($record_id, $active, $own_record);
        }        
    }
    
    
    function &getClientController() {
        if($this->admin_view) {
            return $this->controller->getClientController();
        } else {
            return $this->controller;
        }
    }

    
    // for client view 
    function &titleHeaderList2($nav = '', $left_side = '', $button_msg = true, $bulk_form = true, $mobile_view = false) {
        
        $tpl = new tplTemplatez(APP_TMPL_DIR . 'title_list_header.html');
        
        if($button_msg) {
            if ($mobile_view) {
                $str = '<a href="%s" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-plus"></span> &nbsp;&nbsp;%s</a>';
                
            } else {
                $str = '<a href="%s"><b>%s</b></a>';
            }
            
            $query = http_build_query($_GET);
            $link = $_SERVER['PHP_SELF'] . '?' . $query . '&action=insert';
            $tpl->tplAssign('add_link', sprintf($str, $link, $this->msg['add_new_msg']));
        }

        if($nav) {
            $tpl->tplSetNeeded('/by_page');
            $tpl->tplAssign('by_page_tpl', $nav);
        }
        
        if($left_side) {
            $tpl->tplAssign('left_side', $left_side);
        }
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    // used it to set links such as delete, update
    function getViewListVars2($record_id = false, $active = NULL, $own_record = true) {
        static $i = 0;
        
        $row['class'] = ($i++ & 1) ? 'trDarker' : 'trLighter'; // rows colors
        $row['style'] = ($active !== null && !$active) ? $this->inactive_style : ''; // style for not active
        
        $str = '<a href="%s" onclick="confirm2(\'%s\', \'%s\');return false;">%s</a>';
        $query = http_build_query($_GET);
        $link = $_SERVER['PHP_SELF'] . '?' . $query . '&action=delete&id=' . $record_id;
        $row['delete_link'] = $link;
        $row['delete_img']    = sprintf($str, $row['delete_link'], $this->msg['sure_delete_msg'], $row['delete_link'], $this->msg['delete_msg']);
        
        return $row;
    }
    
    
    function getHeader($manager, $title, $add_button, $bp = false) {
        
        $mobile_view = false;
        if (isset($this->controller->setting)) {
            $mobile_view = ($this->controller->getSetting('view_format') == 'mobile');
        }
        
        $nav = false;
        if($bp) {
            $row2 = array();

            $nav = $bp->nav;
            if ($mobile_view) {
                $nav = '';
                $row2['by_page_tpl'] = ($bp->num_pages > 1) ? $bp->nav : '';
            }
        }
        
        $row2['header'] = $this->subscriptionHeaderList($nav, $title, $add_button, $mobile_view); 
        
        return $row2;
    }
}
?>