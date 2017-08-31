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


class ExportView_list extends AppView
{
                                            
    var $tmpl = 'list.html';
    
    
    function execute(&$obj, &$manager) {
    
        $this->addMsg('user_msg.ini');

        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        
        $manager->setSqlParams('AND export_type = 1');
        
        // sort generate
        $sort = &$this->getSort();
        $manager->setSqlParamsOrder($sort->getSql());
                 
        // header generate
        $bp =& $this->pageByPage($manager->limit, $manager->getCountRecordsSql());
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav));
  
        // get records
        $rows = $this->stripVars($manager->getRecords($bp->limit, $bp->offset), array('export_option'));
        $ids = $manager->getValuesString($rows, 'id');
        $user_ids = $manager->getValuesString($rows, 'user_id'); 
        
        if (!empty($rows)) {
            $users = $manager->getUserByIds($user_ids);
            $last_generated = $manager->getLastGenerated($ids); 
        }

        // roles
        // $roles = $manager->role_manager->getSelectRecords();
        // $full_roles = &$manager->role_manager->getSelectRangeFolow($roles);
        // $full_roles = $this->stripVars($full_roles);        
        

        // categories
        $categories = $manager->cat_manager->getSelectRecords();
        $full_categories = &$manager->cat_manager->getSelectRangeFolow($categories);
        $full_categories = $this->stripVars($full_categories);
        
        $categories[0]['name'] = $this->msg['all_categories2_msg'];
        $full_categories[0] = $this->msg['all_categories2_msg'];
        
        foreach($rows as $row) {
            
            $obj->set($row);
            
            $tpl->tplAssign('title',  ($obj->get('title')) ? $obj->get('title') : '--');
            
            $options = unserialize($obj->get('export_option'));

            // category
            $category_id = $options['category_id'];
            
            if (isset($full_categories[$category_id])) {
                $tpl->tplSetNeeded('row/category'); 
                $tpl->tplAssign('full_category', $full_categories[$category_id]);
                $tpl->tplAssign('category', $this->getSubstringSignStrip($categories[$category_id]['name'], 30));
            } else {
                $tpl->tplSetNeeded('row/no_category');
            }            
            
            // last generated
            $lg = '--';
            if(isset($last_generated[$obj->get('id')])) {
                $lg = $this->getFormatedDate($last_generated[$obj->get('id')], 'datetimesec');
            }
            $tpl->tplAssign('last_generated', $lg); 

            $dmsg = sprintf('%s / %s', $this->msg['detail_msg'], $this->msg['download_msg']);
            $actions = array('detail' => array('msg'=>$dmsg),'update','delete');
            $tpl->tplAssign($this->getViewListVarsJs($obj->get('id'), $obj->get('active'), 1, $actions));
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
        $sort->setCustomDefaultOrder('id', 2);
        $sort->setDefaultSortItem('id', 2);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);        
        
        $sort->setSortItem('id_msg','id', 'id', $this->msg['id_msg']);
        //$sort->setSortItem('last_generated_msg', 'last_gen', 'ed.date_created',  $this->msg['date_msg']);
        $sort->setSortItem('title_msg',  'title', 'title',  $this->msg['title_msg']);
        
        return $sort;
    }
}
?>