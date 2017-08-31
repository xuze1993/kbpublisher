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

require_once APP_MODULE_DIR . 'setting/setting/inc/SettingModelUser.php';


class KBHomeView_default extends AppView
{
    
    var $num = 10;
    
    function execute(&$obj, &$manager) {

        $this->addMsg('common_msg.ini', 'knowledgebase');
        
        $tpl = new tplTemplatez($this->template_dir . 'page.html');
        
        $active_portlets_ids = $manager->getActivePortletsIds();
        $portlets_range = $manager->getPortletSelectRange($this->msg);
        $portlets = array();
        $hidden_portlets_ids = array();
        
        foreach ($portlets_range as $id => $title) {
            
            $portlet_key = $manager->getPortletKeyById($id);
            $portlets[$id] = $this->getPortlet($portlet_key, $manager);
            
            if (!$portlets[$id]) {
                continue;
            }
            
            $v['block_name'] = $title;
            $v['block_id'] = $id;
            
            $is_portlet_visible = (in_array($id, $active_portlets_ids[0]) || in_array($id, $active_portlets_ids[1]));
            if (!$is_portlet_visible) {
                $hidden_portlets_ids[] = $id;
            }
            
            $v['checked'] = ($is_portlet_visible) ? 'checked' : '';
            
            $tpl->tplParse($v, 'portlet_row');
        }
        
        $available_portlets = array_filter($portlets);
        if (empty($available_portlets)) { // nothing to display
            return '';
        }
        
        $hidden_portlets[1] = array_slice($hidden_portlets_ids, 0, round(count($hidden_portlets_ids) / 2));
        $hidden_portlets[2] = array_slice($hidden_portlets_ids, round(count($hidden_portlets_ids) / 2));
        
        for ($i = 1; $i <= 2; $i ++) {
            
            $column_ids = implode(',', $active_portlets_ids[$i - 1]);
            $tpl->tplAssign(sprintf('column%d_ids', $i), $column_ids);
            
            $tpl->tplAssign('percentage_column_width', ($i == 1) ? '60' : '40');
            
            $v = array();
            foreach ($active_portlets_ids[$i - 1] as $portlet_id) {
                
                if (empty($portlets[$portlet_id])) { // user doesn't have access to this portlet
                    continue;
                }
                
                $v['portlet'] = $portlets[$portlet_id];
                $v['id'] = $portlet_id;
                $v['display'] = 'block';
                
                $tpl->tplParse($v, 'column/portlet');
            }
            
            // add hidden portlets to the end of a column
            foreach ($hidden_portlets[$i] as $portlet_id) {
                    
                if (empty($portlets[$portlet_id])) { // user doesn't have access to this portlet
                    continue;
                }
            
                $v['portlet'] = $portlets[$portlet_id];
                $v['id'] = $portlet_id;
                $v['display'] = 'none';
                
                $tpl->tplParse($v, 'column/portlet');
            }
            
            $row['column_id'] = $i;
            $row['placeholder_display'] = (empty($hidden_portlets_ids)) ? 'none' : 'block';
            
            $tpl->tplSetNested('column/portlet');
            $tpl->tplParse(array_merge($row, $this->msg), 'column');
        }
        
        //xajax
        $ajax = &$this->getAjax();
        $xajax = &$ajax->getAjax();

        $xajax->registerFunction(array('setUserHome', $this, 'ajaxSetUserHome'));
        
        
        $tpl->tplParse($this->msg);
        return $tpl->tplPrint(1);
    }
    
    
    function instance($class) {
        // if(isset($this->$class)) {
        //     return $this->$class;
        // }
        
        // $this->$class = new $class;
        // return $this->$class;
        
        return new $class;
    }
    
    
    function getPortlet($key, $manager) {
        
        // probably we should show stat at all if user do not have priv ?
        $reg =& Registry::instance();
        $priv = $reg->getEntry('priv');
        
        $setting = SettingModel::getQuick(100);
        
        switch ($key) {
            case 'article':
            	if($priv->isPriv('select', 'kb_entry')) {
                    return $this->getArticleStat($manager);
                }
            	break;
                
            case 'file':
            	if($priv->isPriv('select', 'file_entry')) {
                    return $this->getFileStat($manager);
                }
            	break;
                
            case 'draft_article':
            	if($priv->isPriv('select', 'kb_draft')) {
            	    return $this->getDraftArticleStat($manager);
                }
            	break;
                
            case 'draft_file':
            	if($priv->isPriv('select', 'file_draft')) {
            	    return $this->getDraftFileStat($manager);
                }
            	break;
                           
            case 'approval':
            	if($priv->isPriv('select', 'kb_draft') || $priv->isPriv('select', 'file_draft')) {
            	    return $this->getApprovalStat($manager);
                }
            	break;
                
        }
    }
    
    
    function getArticleStat($manager) {
        
        require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryModel.php';
        
        // $manager2 = new KBEntryModel;
        $manager2 = $this->instance('KBEntryModel');
        
        // roles sql
        $manager2->setSqlParams('AND ' . $manager2->getCategoryRolesSql(false));
        $manager2->setSqlParams('AND author_id = ' . $manager->user_id);
        
        $rows = $manager2->getStatRecords();
        $status_msg = $manager2->getEntryStatusData('article_status');
        
        $tpl = new tplTemplatez($this->template_dir . 'article_stat.html');
        
        $total = 0;
        foreach($status_msg as $num => $v) {
            
            $v['num'] = 0;
            if(isset($rows[$num])) {
                $v['num'] = $rows[$num];
            }
            
            $more = array('filter[s]' => $num, 'filter[q]' => 'author:' . $manager->user_id);
            $v['status_link'] = $this->controller->getLink('knowledgebase', 'kb_entry', false, false, $more);
            $v['status_title'] = $v['title'];
            $total += $v['num'];
        
            $tpl->tplParse($v, 'row');
        }
        
        $tpl->tplAssign('total_num', $total);
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign('title_msg', $this->msg['my_article_stats_msg']);        
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getFileStat($manager) {
        
        require_once APP_MODULE_DIR . 'file/entry/inc/FileEntryModel.php';
        
        // $manager2 = new FileEntryModel;
        $manager2 = $this->instance('FileEntryModel');
        
        // roles sql
        $manager2->setSqlParams('AND ' . $manager2->getCategoryRolesSql(false));
        $manager2->setSqlParams('AND author_id = ' . $manager->user_id);
        
        $rows = $manager2->getStatRecords();
        $status_msg = $manager2->getEntryStatusData('file_status');        
        
        $tpl = new tplTemplatez($this->template_dir . 'article_stat.html');
        
        $total = 0;
        foreach($status_msg as $num => $v) {
            
            $v['num'] = 0;
            if(isset($rows[$num])) {
                $v['num'] = $rows[$num];
            }
            
            $more = array('filter[s]' => $num, 'filter[q]' => 'author:' . $manager->user_id);
            $v['status_link'] = $this->controller->getLink('file', 'file_entry', false, false, $more);
            $v['status_title'] = $v['title'];
            $total += $v['num'];
        
            $tpl->tplParse($v, 'row');
        }
        
        $tpl->tplAssign('total_num', $total);
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign('title_msg', $this->msg['my_file_stats_msg']);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getDraftArticleStat($manager) {
        
        require_once APP_MODULE_DIR . 'knowledgebase/draft/inc/KBDraftModel.php';
        
        // $manager2 = new KBDraftModel;
        $manager2 = $this->instance('KBDraftModel');
        
        $manager2->setSqlParams('AND author_id = ' . $manager->user_id);
        
        $rows = $manager2->getDraftStatRecords($manager2->from_entry_type);
        $status_msg = $manager2->getDraftStatusData();
        
        $tpl = new tplTemplatez($this->template_dir . 'article_stat.html');
        
        $total = 0;
        foreach($status_msg as $num => $v) {
            
            $v['num'] = 0;
            if(isset($rows[$num - 1])) {
                $v['num'] = $rows[$num - 1];
            }
            
            $more = array('filter[s]' => $num, 'filter[q]' => 'author:' . $manager->user_id);
            $v['status_link'] = $this->controller->getLink('knowledgebase', 'kb_draft', false, false, $more);
            $v['status_title'] = $v['title'];
            $total += $v['num'];
        
            $tpl->tplParse($v, 'row');
        }
        
        $tpl->tplAssign('total_num', $total);
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign('title_msg', $this->msg['my_draft_article_stats_msg']);        
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getDraftFileStat($manager) {
        
        require_once APP_MODULE_DIR . 'file/draft/inc/FileDraftModel.php';
        
        // $manager2 = new FileDraftModel;
        $manager2 = $this->instance('FileDraftModel');
        $manager2->setSqlParams('AND author_id = ' . $manager->user_id);
        
        $rows = $manager2->getDraftStatRecords($manager2->from_entry_type);
        $status_msg = $manager2->getDraftStatusData();
        
        $tpl = new tplTemplatez($this->template_dir . 'article_stat.html');
        
        $total = 0;
        foreach($status_msg as $num => $v) {
            
            $v['num'] = 0;
            if(isset($rows[$num - 1])) {
                $v['num'] = $rows[$num - 1];
            }
            
            $more = array('filter[s]' => $num, 'filter[q]' => 'author:' . $manager->user_id);
            $v['status_link'] = $this->controller->getLink('file', 'file_draft', false, false, $more);
            $v['status_title'] = $v['title'];
            $total += $v['num'];
        
            $tpl->tplParse($v, 'row');
        }
        
        $tpl->tplAssign('total_num', $total);
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign('title_msg', $this->msg['my_draft_file_stats_msg']);        
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getApprovalStat($manager) {
        
        require_once APP_MODULE_DIR . 'knowledgebase/draft/inc/KBDraftModel.php';
        
        $reg =& Registry::instance();
        $priv = $reg->getEntry('priv');
        
        $manager2 = $this->instance('KBDraftModel');
        $rows = $manager2->getAwaitingDrafts();
        // if(empty($rows)) {
            // return false;
        // }
        
        $msg = AppMsg::getMsgs('ranges_msg.ini', false, 'approval_rule_match');
        $range = array(
            'article' => array(1,7), 
            'file' => array(2,8)
            );
            
        
        $tpl = new tplTemplatez($this->template_dir . 'article_stat.html');
        
        $total = 0;
        foreach ($range as $entry_type_str => $v) {
            
            $entry_type = $v[0];
            $draft_type = $v[1];
            
            list($module, $page) = $manager->entry_type_to_url[$draft_type];
            
            if ($priv->isPriv('select', $page)) {
                $v['num'] = (!empty($rows[$entry_type])) ? $rows[$entry_type] : 0;
                $v['color'] = ($v['num']) ? 'red' : '#BFBFBF';
            
                $more = array(
                    'filter[s]' => 2, 
                    'filter[t]' => $manager->user_id
                    );
                
                $link = $this->controller->getLink($module, $page, false, false, $more);
                $v['status_link'] = $link;
                $v['status_title'] = $msg[$entry_type_str];
                $total += $v['num'];
            
                $tpl->tplParse($v, 'row');
            }
        }
        
        
        $tpl->tplAssign('total_num', $total);
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign('title_msg', $this->msg['my_approval_stats_msg']);        
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function ajaxSetUserHome($ids) {

        $sm = new SettingModelUser(AuthPriv::getUserId());
        $sm->user_id = AuthPriv::getUserId();
        
        $setting_id = $sm->getSettingIdByKey('home_user_portlet_order');
        
        $column1_ids = implode(',', $ids[0]);
        $column2_ids = implode(',', $ids[1]);
        
        $value = $column1_ids . '|' . $column2_ids;
        if (strlen($column1_ids) == 0 && strlen($column2_ids) == 0) {
            $value = 'empty';
        }
        
        $sm->setSettings(array($setting_id => $value));

        $objResponse = new xajaxResponse();
        
    
        return $objResponse;    
    }    
}
?>