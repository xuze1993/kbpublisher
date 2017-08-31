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


class ReportStatView_news extends ReportStatView
{
    
    var $template = 'news.html';

    
    function execute(&$obj, &$manager) {
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        
        // all
        $tpl->tplAssign('news_all', $this->getAll($manager));
        
        // news by status 
        $tpl->tplAssign('entry_by_status', $this->getEntryByStatus($manager));
                
        // entry by private
        $tpl->tplAssign('entry_by_private', $this->getEntryByPrivate($manager));
        
        $tpl->tplAssign($this->msg);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    
    function getEntryByStatus($manager, $type = 'html', $send = false) {
        
        $data = $manager->getEntryStatus();
        $title = $this->msg['news_by_status_msg'];
        $status = $this->getCommonStatusData();
        $data = $this->getStatusBlock($data, $title, 'entry_status', $type, $send, $status);

        return $data;
    }
    
        
    function getEntryByPrivate($manager, $type = 'html', $send = false) {
        
        $data = $manager->getEntryPrivate();
        $title = $this->msg['news_by_private_msg'];
        $status = $this->getPrivateStatusData();
        $data = $this->getStatusBlock($data, $title, 'entry_priv', $type, $send, $status);
        return $data;
    }
    
    
    function getAll($manager, $type = 'html', $send = false) {

        $data[1]['title'] = $this->msg['all_news_msg'];
        $data[1]['num'] = $manager->getCountAll();

        $title = $this->msg['all_msg'];  
        $data = $this->getCommonBlock($data, $title, 'all', $type, $send);

        return $data;
    }
    
    
    function executeExport($manager, $type, $mode) {

        switch($mode) {
        case 'all':
            $this->getAll($manager, $type, true);
            break;    
    
        case 'entry_status':                
            $this->getEntryByStatus($manager, $type, true);
            break;
           
        case 'entry_priv':
            $this->getEntryByPrivate($manager, $type, true);
            break;

        default:

        }
    }

}
?>