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


class ReportStatView_search extends ReportStatView
{
    
    var $template = 'search.html';

    
    function execute(&$obj, &$manager) {
        
        $tpl = new tplTemplatez($this->template_dir . $this->template);
        
        //most popular
        $tpl->tplAssign('most_popular', $this->getMostPopular($manager));
        
        // less popular
        $tpl->tplAssign('less_popular', $this->getLessPopular($manager));
        
        $tpl->tplAssign($this->msg);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }


    function getMostPopular($manager, $type = 'html', $send = false, $limit = 10) {
        
        $data = $manager->getMostPopular($limit);
        $title = $this->msg['most_searched_msg'];    
        $data = $this->getCommonBlock($data, $title, 'most_searched', $type, $send);

        return $data;
    }
    
    
    function getLessPopular($manager, $type = 'html', $send = false, $limit = 10) {
        
        $data = $manager->getLessPopular($limit);
        $title = $this->msg['less_searched_msg'];    
        $data = $this->getCommonBlock($data, $title, 'less_searched', $type, $send);

        return $data;
    }
    
    
    function executeExport($manager, $type, $mode, $limit = 10) {

        switch($mode) {
        case 'most_searched':
            $this->getMostPopular($manager, $type, true, $limit);
            break;
            
        case 'less_searched':
            $this->getLessPopular($manager, $type, true, $limit);
            break;            

        default:
        
        }
    }

}
?>