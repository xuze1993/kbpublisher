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

class CompanyView_list extends AppView
{
        
    var $template = 'list.html';
    var $template_popup = 'list_popup.html';
    
    function execute(&$obj, &$manager) {
    
        $this->addMsg('user_msg.ini');
        
        $tmpl = ($this->controller->getMoreParam('popup')) ? $this->template_popup :  $this->template;
        $tpl = new tplTemplatez($this->template_dir . $tmpl);

        // header generate
        $bp =& $this->pageByPage($manager->limit, $manager->getRecordsSql());     
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav));
        
        // sort generate
        $sort = &$this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
        
        // get records
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset));
        $ids = $manager->getValuesString($rows, 'id');
        
        $users_num = ($ids) ? $manager->getUsersNum($ids) : array();
        
        foreach($rows as $row) {
            
            $obj->set($row);
            
            $user_num = (isset($users_num[$row['id']])) ? $users_num[$row['id']] : '';
            $user_link = $this->getLink('users', 'user', false, false, array('filter[comp]' => $obj->get('id')));
  
            $tpl->tplAssign('user_num', $user_num);
            $tpl->tplAssign('user_link', $user_link);
            
            $www_link = '';
            if($row['url']) {
                $pref = (strpos($row['url'], 'https://') !== false) ? 'https://' : 'http://';
                $link = $pref . str_replace($pref, '', $row['url']);
                $title = str_replace(array('https://', 'http://'), '', $row['url']);
                $www_link = sprintf('<a href="%s" target="_blank">%s</a>', $link, $title);
            }
            $tpl->tplAssign('www_link', $www_link);
            
            $tpl->tplAssign('escaped_name', addslashes($obj->get('title')));
            
            $tpl->tplAssign($this->getViewListVarsJs($obj->get('id'),1,1,array('update','delete')));
            $tpl->tplParse(array_merge($obj->get(), $this->msg), 'row');
        }
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function &getSort() {
        
        //$sort = new TwoWaySort();
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        $sort->setCustomDefaultOrder('user_num', 2);
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        
        $sort->setSortItem('title_msg', 'title', 'title', $this->msg['title_msg'], 1);
        $sort->setSortItem('email_msg', 'email', 'email', $this->msg['email_msg']);
        $sort->setSortItem('www_msg', 'www', 'url', $this->msg['www_msg']);
        $sort->setSortItem('phone_msg', 'phone', 'phone', $this->msg['phone_msg']);
        $sort->setSortItem('fax_msg', 'fax', 'fax', $this->msg['fax_msg']);
        $sort->setSortItem('status_msg', 'status', 'active', $this->msg['status_msg']);
        
        return $sort;
    }
}
?>