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
require_once 'HTTP/Download.php';


class ReportStatView extends AppView
{


    static function factory($view) {
        
        $class = 'ReportStatView_' . $view;
        $file = $class . '.php';
        
        require_once APP_MODULE_DIR . 'report/stat/inc/' . $file;
        return new $class;
    }

    
    // BLOCKS  // --------------------

    function getCommonBlock($data, $title, $mode, $type = 'html', $send = false) {
        
        if($type == 'xml') {
            $data = $this->getCommonXml($data, $title);
            if ($send) {
                $this->sendData($data, 'xml', $title);
                exit;
            } 

        } elseif($type == 'csv') {
            $data = $this->getCommonCsv($data, $title);
            if ($send) {
                $this->sendData($data, 'csv', $title);
                exit;
            } 
            
        } else {
            $detail_link_actions = array('most_popular', 'less_popular', 
                                         'most_searched', 'less_searched');
            $detail_link = in_array($mode, $detail_link_actions);
            $data = $this->getCommonList($data, $title, $mode, $detail_link);
        }

        return $data;
    }
    
    
    function getStatusBlock($data, $title, $mode, $type = 'html', $send = false, $status) {
        
        if($type == 'xml') {
            $data = $this->getStatusXml($data, $title, $status);
            if ($send) {
                $this->sendData($data, 'xml', $title);
                exit;
            }

        } elseif($type == 'csv') {
            $data = $this->getStatusCsv($data, $title, $status);
            if ($send) {
                $this->sendData($data, 'csv', $title);
                exit;
            }
            
        } else {
            $data = $this->getStatusList($data, $title, $status, $mode);
        }
        
        return $data;
    }
    
    
    function getEntryBlock($data, $title, $mode, $type = 'html', $send = false) {
        
        if($type == 'xml') {
            $data = $this->getEntryXml($data, $title);
            if ($send) {
                $this->sendData($data, 'xml', $title);
                exit;
            } 

        } elseif($type == 'csv') {
            $data = $this->getEntryCsv($data, $title);
            if ($send) {
                $this->sendData($data, 'csv', $title);
                exit;
            } 
            
        } else {               
            $data = $this->getEntryList($data, $title, $mode);
        }

        return $data;
    }    
    
    
    
    // COMMON // -------------------
    
    function getCommonList($data, $title, $mode, $detail_link = false) {
            
        $tpl = new tplTemplatez($this->template_dir . 'tmpl_list_common.html');
        
        foreach($data as $k => $v) {
            $tpl->tplParse($v, 'row');
        }

        $xml_link = $this->getActionLink('file', false, array('type' => 'xml', 'mode' => $mode));
        $tpl->tplAssign('xml_link', $xml_link);        
        
        $csv_link = $this->getActionLink('file', false,  array('type' => 'csv', 'mode' => $mode));
        $tpl->tplAssign('csv_link', $csv_link);
        
        if ($detail_link) {
            $detail_link = $this->getActionLink('detail', false,  array('mode' => $mode));
            $tpl->tplAssign('detail_link', $detail_link);
        
            $tpl->tplSetNeeded('/detail_link');
        }

        $tpl->tplAssign('header_title', $title);        
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);        
    }
    
    
    function getCommonXml($data, $title) {
                            
        $tpl = new tplTemplatez($this->template_dir . 'tmpl_xml_common.html');
        
        foreach($data as $id => $k) {
            $v['title'] = $k['title'];
            $v['value'] = $k['num'];
            $tpl->tplParse($v, 'row');
        }
        
        $tpl->tplAssign('report_title', $title);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }

         
    function getCommonCsv($data, $title, $need_total = false) {

        $a = array();
        $total = 0;
        foreach($data as $id => $k) {
            $n['title'] = $k['title'];
            $n['value'] = $k['num'];
            $total += $n['value'];
            $a[] = $n;
        }

        if ($need_total) {
            $a[] = array($this->msg['total_msg'], "$total");
        }
        
        $opts = array('ft' => ',', 'oe' => '"', 'lt' => '\n');

        return RequestDataUtil::parseCsv($a, $opts);
    }
    
        
    
    // STATUS // -------------------
    
    function getStatusList($rows, $title, $status_msg, $mode) {
                       
        $tpl = new tplTemplatez($this->template_dir . 'tmpl_list_status.html');
                           
        $total = 0;
        foreach($status_msg as $num => $v) {
            $v['num'] = (isset($rows[$num])) ? $rows[$num] : 0;
            $total += $v['num'];
        
            $tpl->tplParse($v, 'row');
        }
        
        $tpl->tplAssign('total_num', $total);
        $tpl->tplAssign('header_title', $title);
        
        $xml_link = $this->getActionLink('file', false, array('type' => 'xml', 'mode' => $mode));
        $tpl->tplAssign('xml_link', $xml_link);        
        
        $csv_link = $this->getActionLink('file', false,  array('type' => 'csv', 'mode' => $mode));
        $tpl->tplAssign('csv_link', $csv_link);
                
        $tpl->tplParse();
        return $tpl->tplPrint(1);        
    }
    
    
    function getStatusXml($rows, $title, $status_msg) {
                            
        $tpl = new tplTemplatez($this->template_dir . 'tmpl_xml_status.html');
        
        $total = 0;
        foreach($status_msg as $num => $v) {
            $v['value'] = (isset($rows[$num])) ? $rows[$num] : 0;
            $total += $v['value'];

            $tpl->tplParse($v, 'row');
        }
            
        $tpl->tplAssign('total_num', $total);
        $tpl->tplAssign('report_title', $title);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    

    function getStatusCsv($data, $title, $status_msg) {
        $data2 = array();
        foreach($status_msg as $num => $v) {
            $data2[$num]['title'] = $v['title'];
            $data2[$num]['num'] = (isset($data[$num])) ? $data[$num] : 0;
        }
    
        return $this->getCommonCsv($data2, $title, true);
    }


    // ENTRY // ------------------
    
    function getEntryList($data, $title, $mode) {
            
        $tpl = new tplTemplatez($this->template_dir . 'tmpl_list_entry.html');
        
        foreach($data as $k => $v) {
            $v['num'] = (strpos($v['num'], '.') !== false) ? number_format($v['num'], 1) : $v['num'];    
            $tpl->tplParse($v, 'row');
        }

        $xml_link = $this->getActionLink('file', false, array('type' => 'xml', 'mode' => $mode));
        $tpl->tplAssign('xml_link', $xml_link);        
        
        $csv_link = $this->getActionLink('file', false,  array('type' => 'csv', 'mode' => $mode));
        $tpl->tplAssign('csv_link', $csv_link);
        
        $detail_link = $this->getActionLink('detail', false,  array('mode' => $mode));
        $tpl->tplAssign('detail_link', $detail_link);
        
        $tpl->tplSetNeeded('/detail_link');
        $tpl->tplAssign('header_title', $title);        
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);        
    }
    
    
    function getEntryXml($data, $title) {
                            
        $tpl = new tplTemplatez($this->template_dir . 'tmpl_xml_entry.html');
        
        foreach($data as $id => $k) {
            $v['id'] = $id;
            $v['title'] = $k['title'];
            $v['value'] = $k['num'];
            $tpl->tplParse($v, 'row');
        }
        
        $tpl->tplAssign('report_title', $title);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }

         
    function getEntryCsv($data, $title) {

        // $a = array();
        //         foreach($data as $id => $k) {
        //             $n['id'] = $id;
        //             $n['title'] = $k['title'];
        //             $n['value'] = $k['num'];
        //             $a[] = $n;
        //         }
        
        $opts = array('ft' => ',', 'oe' => '"', 'lt' => '\n');

        return RequestDataUtil::parseCsv($data, $opts);
    }
    


    
    // OTHER // ---------------------------
    
    function getEntryStatusData($list_key) {
        foreach(ListValueModel::getListData($list_key) as $list_value => $v) {
            $data[$v['list_value']] = array('title' => $v['title'],
                                            'color' => $v['custom_1']
                                            );
        }
        
        return $data;
    }    
    
    
    function getCommonStatusData() {
        $data[1]['title'] = $this->msg['status_published_msg'];
        $data[1]['color'] = '';        
        $data[0]['title'] = $this->msg['status_not_published_msg'];
        $data[0]['color'] = '';
        return $data;
    }
    
    
    function getPrivateStatusData() {
            
        $data[0]['title'] = $this->msg['public_msg'];
        $data[3]['title'] = $this->msg['private_read_msg'];
        $data[2]['title'] = $this->msg['private_write_msg'];
        $data[1]['title'] = $this->msg['private_readwrite_msg'];
        return $data;
    }
    
    
    function sendData($data, $type, $title) {
        $params['data'] = $data;
        $params['gzip'] = false;
        
        $h = new HTTP_Download($params); 
        
        $h->setContentType('application/' . $type);
        $h->setContentDisposition(HTTP_DOWNLOAD_ATTACHMENT, $title . '.' . $type);
        $h->send();
    }


    function executeExport($manager, $type, $mode) {

        switch($mode) {
         case 'all':
             $this->getAll($manager, $type, true);
             break;

        case 'entry_status':
            $this->getEntryByStatus($manager, $type, true);
            break;

        case 'category_status':
            $this->getCategoryByStatus($manager, $type, true);
            break;

        case 'entry_priv':
            $this->getEntryByPrivate($manager, $type, true);
            break;

        case 'category_priv':
            $this->getCategoryByPrivate($manager, $type, true);
            break;    
 
        case 'comments':
            $this->getCommentByStatus($manager, $type, true);
            break;
 
        case 'most_viewed':                            
            $this->getMostViewed($manager, $type, true);
            break;
 
        case 'most_commented':
            $this->getMostCommented($manager, $type, true);
            break;
 
        case 'most_useful':
            $this->getMostUseful($manager, $type, true);
            break;

        default:

        }
    }
}
?>