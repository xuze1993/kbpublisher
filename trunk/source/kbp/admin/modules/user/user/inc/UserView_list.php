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

require_once APP_MODULE_DIR . 'user/subscription/inc/SubscriptionModel.php';


class UserView_list extends AppView
{
    
    var $template = 'list.html';
    var $template_popups = array(
        1 => 'list_popup.html',
        2 => 'list_popup2.html',
        3 => 'list_popup_trigger.html',
        'text' => 'list_popup.html',
    );
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        
        $this->company_range = $manager->getCompanySelectRange();
        
        $roles = $manager->getRoleRecords();
        $this->role_range = $manager->getRoleSelectRange($roles);                
        
        $popup = $this->controller->getMoreParam('popup');
        $tmpl = ($popup) ? $this->template_popups[$popup] :  $this->template;
        
        $tpl = new tplTemplatez($this->template_dir . $tmpl);
        
        $show_msg2 = $this->getShowMsg2();
        $tpl->tplAssign('msg', $show_msg2);
        
        // check 
        $add_button = true;
        $update_allowed = true;
        $bulk_allowed = array();        
        $au = KBValidateLicense::getAllowedUserRest($manager);
        if($au !== true && $au < 0) {
            $tpl->tplAssign('msg', AppMsg::licenseBox('license_exceed_users'));
            
            $update_allowed = false;
            $bulk_allowed = array('delete', 'priv');
            $add_button = false;    
        }        
        
        // popup 
        if($popup) {
            $max_allowed = ($this->controller->getMoreParam('limit')) ? 1 : 0;
            $tpl->tplAssign('max_allowed', $max_allowed);
        
            $close = ($this->controller->getMoreParam('close')) ? 1 : 0;
            $tpl->tplAssign('close_on_action', $close);
            
            $field_name = $this->controller->getMoreParam('field_name');
            if ($field_name == 'r') {
                $field_name = 'admin_user';
            }
            $tpl->tplAssign('field_name', $field_name);
            
            if ($this->controller->getMoreParam('popup') == 'text') {
                $add_button = false;
            }
        }
        
        // bulk
        $manager->bulk_manager = new UserModelBulk();
        if($manager->bulk_manager->setActionsAllowed($manager, $manager->priv, $bulk_allowed)) {
            $tpl->tplSetNeededGlobal('bulk');
            $tpl->tplAssign('footer', $this->controller->getView($obj, $manager, 'UserView_bulk', $this));
        }        
        
        
        if(APP_DEMO_MODE) {
            $manager->setSqlParams("AND u.grantor_id = '{$manager->user_id}'");
        }
        
        // status_msg
        $status = $manager->getEntryStatusData();
        
        // filter sql        
        $params = $this->getFilterSql($manager, $roles);
        $manager->setSqlParams($params['where']);
        // $manager->setSqlParamsSelect($params['select']);
        $manager->setSqlParamsFrom($params['from']);
        
        
        // header generate
        $count = (isset($params['count'])) ? $params['count'] : $manager->getCountRecords();
        $bp = &$this->pageByPage($manager->limit, $count);
        $tpl->tplAssign('header', $this->commonHeaderList($bp->nav, 
                                  $this->getFilterForm($manager, $roles), $add_button));
        
        // sort generate
        $sort = &$this->getSort();
        $psort = (isset($params['sort'])) ? $params['sort'] : $sort->getSql();
        $manager->setSqlParamsOrder($psort);
        
        // get records
        $offset = (isset($params['offset'])) ? $params['offset'] : $bp->offset;
        $users = $this->stripVars($manager->getRecords($bp->limit, $offset));
        $ids = $manager->getValuesString($users, 'id');
        
        // get user priv / role / extra
        $user_priv = array();
        $user_role = array();
        $user_extra = array();
        if($ids) {
            $user_priv = &$manager->getPrivByIds($ids);
            $priv_msg = $this->stripVars($manager->getUserPrivMsg());
            
            $subscription = $manager->getUserSubscription($ids);
            $user_extra = $manager->getExtraByIds($ids);
        
            $user_role = &$manager->getRoleByIds($ids);
            $user_role = $this->stripVars($user_role);
            
            $full_roles = &$manager->role_manager->getSelectRangeFolow($roles);
            $full_roles = $this->stripVars($full_roles);
        }
        
        // author&updater
        if($ids) {
            $author_article = $manager->getNumAuthor($ids, 'article');
            $author_file = $manager->getNumAuthor($ids, 'file');
        }
        
        
        $s_manager = new SubscriptionModel;
        $subs_types = $s_manager->types;
        
        // list records
        foreach($users as $row) {
            $obj->set($row);
            $obj->setFullName();
            
            // priv            
            $privilege = array();
            if(isset($user_priv[$obj->get('id')])) {
                foreach($user_priv[$obj->get('id')] as $k => $v) {
                    $privilege[] = $priv_msg[$v]['name'];
                }            
            }
            $tpl->tplAssign('privilege', implode(', ',  $privilege));


            // role
            $_roles = '';
            $_full_roles = array();
            if(isset($user_role[$obj->get('id')])) {
                $_roles = implode('<br />',  $user_role[$obj->get('id')]);
                
                foreach($user_role[$obj->get('id')] as $role_id => $v) {
                    $_full_roles[] = $full_roles[$role_id];
                }

                $_full_roles = implode('<br />',  $_full_roles);
            }
            
            $tpl->tplAssign('role', $_roles);
            $tpl->tplAssign('full_role', $_full_roles);
                            
                            
            // subscription            
            $subsc_hint = '';
            if(isset($subscription[$obj->get('id')])) {
                $subsc_hint = array();
                foreach($subscription[$obj->get('id')] as $entry_type => $num) {                                    
                    $subs_msg_key = $subs_types[$entry_type] . '_subsc_msg';
                    $type_msg = $this->msg[$subs_msg_key];
                    $num_msg = ($num == 'all') ? $this->msg['all_msg'] : $num;
                    $subsc_hint[] = sprintf('%s: %s', $type_msg, $num_msg);
                }
                
                $subsc_hint = implode('<br/>', $subsc_hint);
                $tpl->tplSetNeeded('row/if_subscription');
            }
            
            $tpl->tplAssign('subsc_msg', $subsc_hint);
            
            $tpl->tplAssign('escaped_name', addslashes($obj->getFullName()));
            $tpl->tplAssign('status', $status[$row['active']]['title']);
            $tpl->tplAssign('color', $status[$row['active']]['color']);            
            $tpl->tplAssign('formatted_date', $this->getFormatedDate($row['ts']));
            $tpl->tplAssign('formatted_date_full', $this->getFormatedDate($row['ts'], 'datetime'));
            
            //  author&updater
            $article_num = '--';
            $file_num = '--';
            if(isset($author_article[$obj->get('id')]) || isset($author_file[$obj->get('id')])) {
                $str = '<a href="%s" style="{style}" title="%s">%s</a>';
                $more = array('filter[q]'=>'author_id:' . $obj->get('id'));
                
                if(isset($author_article[$obj->get('id')])) {
                    $link = $this->getLink('knowledgebase', 'kb_entry', false, false, $more);
                    $article_num = sprintf($str, $link, $this->msg['author_num_article_msg'], 
                                                    $author_article[$obj->get('id')]);                
                }
                                
                if(isset($author_file[$obj->get('id')])) {
                    $link = $this->getLink('file', 'file_entry', false, false, $more);
                    $file_num = sprintf($str, $link, $this->msg['author_num_file_msg'],
                                                $author_file[$obj->get('id')]);            
                }            
            }
            
            $tpl->tplAssign('author_article_num', $article_num);
            $tpl->tplAssign('author_file_num', $file_num);
            
            
            // last auth
            $fd_lastauth = '-';
            $fd_lastauth_full = '';
            if($row['lastauth']) {
                $fd_lastauth = $this->getTimeInterval($row['lastauth'], true);            
                $fd_lastauth_full = $this->getFormatedDate($row['lastauth'], 'datetime');                
            }
            
            $tpl->tplAssign('formatted_date_lastauth', $fd_lastauth);
            $tpl->tplAssign('formatted_date_lastauth_full', $fd_lastauth_full);
            
            
            // api access
            if(!empty($user_extra[$obj->get('id')][$manager->extra_rules['api']]['value1'])) {
                $tpl->tplSetNeeded('row/if_api');
            }
            
            if ($this->controller->getMoreParam('popup') == 2) {
                $val = addslashes($obj->getFullName());
                if($this->controller->getMoreParam('field_name') == 1) {
                    $val = $obj->get('id');
                }
                
                $tpl->tplAssign('assigned_value', $val);    
            }
            
            $tpl->tplAssign('name', $obj->getFullName());
            $tpl->tplAssign($this->getViewListVarsCustomJs($obj->get('id'), $obj->get('active'),
                                                        $row, $manager, $update_allowed, $privilege));
            
            if(APP_DEMO_MODE) {
                $tpl->tplAssign('name', $row['first_name']);
                $tpl->tplAssign('email', '--');                
            }
            
            $tpl->tplParse(array_merge($obj->get(), $this->msg), 'row');
        }
        
        if ($popup == 3) {
            $select_id = $this->controller->getMoreParam('field_id');
            $tpl->tplAssign('select_id', $select_id);
        }
        
        // create an empty box for a message block
        if ($this->controller->getMoreParam('popup')) {
            $msg = BoxMsg::factory('success');
            $tpl->tplAssign('after_action_message_block', $msg->get());
        }
        
        $tpl->tplAssign($this->msg);
        $tpl->tplAssign($sort->toHtml());
        $tpl->tplAssign($this->parseTitle());
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function parseTitle() {
        $values = array();
        $values['author_num_article_msg'] = $this->shortenTitle($this->msg['author_num_article_msg'], 3);
        $values['author_num_file_msg'] = $this->shortenTitle($this->msg['author_num_file_msg'], 3);
        $values['subscription_msg'] = $this->shortenTitle($this->msg['subscription_msg'], 3);
        return $values;
    }    
    
    
    function &getSort() {
    
        //$sort = new TwoWaySort();    
        $sort = new OneWaySort($_GET);
        $sort->setDefaultOrder(1);
        $sort->setCustomDefaultOrder('dr', 2);    
        $sort->setCustomDefaultOrder('da', 2);
        $sort->setDefaultSortItem('dr', 2);
        
        $sort->setTitleMsg('asc',  $this->msg['sort_asc_msg']);
        $sort->setTitleMsg('desc', $this->msg['sort_desc_msg']);
        
        $sort->setSortItem('name_msg',     'last_name',  'last_name', $this->msg['name_msg']);
        $sort->setSortItem('email_msg',    'email',      'email',     $this->msg['email_msg']);
        $sort->setSortItem('phone_msg',    'phone',      'phone',     $this->msg['phone_msg']);
        $sort->setSortItem('username_msg', 'username',   'username',  $this->msg['username_msg']);
        //$sort->setSortItem('role_msg',     'role',       'role_id',   $this->msg['role_msg']);
        //$sort->setSortItem('priv_msg',     'priv',       'p.priv_name_id',   $this->msg['priv_msg']);
        $sort->setSortItem('id_msg',       'id',         'id',        $this->msg['id_msg']);
        $sort->setSortItem('signing_date_msg',  'dr',     'date_registered',   $this->msg['signing_date_msg']);
        $sort->setSortItem('last_logged_msg',   'da',     'lastauth',  $this->msg['last_logged_msg']);
        $sort->setSortItem('entry_status_msg',  'active', 'active',    $this->msg['entry_status_msg']);
        // $sort->setSortItem('api_msg',           'api',    'api_public_key',    $this->msg['api_msg']);
        
        //echo '<pre>', print_r($sort->getSql(), 1), '</pre>';
        return $sort;
    }


    function getViewListVarsCustomJs($record_id = false, $active = false, $data, $manager, $priv, $update_allowed) {
        
        $actions = array(
            'detail' => true, 
            'update' => true, 
            'delete' => true
        );
        
        $own_record = ($data['grantor_id'] == $manager->user_id);
        $bulk_ids_ch_option = '';

        $actions['activity'] = array(
            'link' => $this->getLink('this', 'this', false, 'activity', array('id' => $record_id)),
            'msg' => $this->msg['activities_msg']
        );

        // login as user
        if($this->priv->isPriv('login')) {
            $actions['login'] = array(
                'link' => $this->getActionLink('login', $record_id),
                'msg'  => $this->msg['login_as_user_msg']
            );          
        }
        
        // self login
        if($this->priv->isSelfPriv('login') && !$own_record) {
             $actions['login'] = false;
        }        
        
        // priv level
        if(!$manager->isUpdateablePrivLevel($data['priv_level'])) {
            $actions['login'] = false;
            $actions['detail'] = false; // no detail button for bigger priv level
            $actions['activity'] = false;
            $actions['update'] = false;
            $actions['delete'] = false;
            $bulk_ids_ch_option = 'disabled';
        }

        // yourself    
        if($manager->user_id == $record_id) {
            $actions['login'] = false;
			$actions['activity'] = false;
            $actions['update'] = false;
            $actions['delete'] = false;
            $bulk_ids_ch_option = 'disabled';

            $actions['detail'] = array(
                'link' => $this->getLink('account', 'account_user', false, false),
                'msg'  => $this->msg['user_account_msg']
            );
        }
        
        // for licensing
        if(!$priv) {
            if($update_allowed == false) {
                $actions['update'] = false;
            }            
        }
        
        $row = $this->getViewListVarsJs($record_id, $active, $own_record, $actions);
        $row['bulk_ids_ch_option'] = $bulk_ids_ch_option;
        if(!$actions['update']) {
            $row['update_link'] = $this->controller->getCurrentLink();
        }       
        
        return $row;
    }
    
    
    function getFilterForm($manager) {

        @$values = $_GET['filter'];
        if(isset($values['q'])) {
            $values['q'] = RequestDataUtil::stripVars($values['q'], array(), true);
            $values['q'] = trim($values['q']);
        }    
    
        $tpl = new tplTemplatez($this->template_dir . 'form_filter.html');
    
        $select = new FormSelect();
        $select->select_tag = false;
        
        // priv
        $v = (!empty($values['priv'])) ? $values['priv'] : 'all';
        $extra_range = array('all'  => '___', 
                             'none' => $this->msg['none_priv_msg'],
                             'any' => $this->msg['any_priv_msg']);
        $select->setRange($manager->getPrivSelectRange(false), $extra_range);
        $tpl->tplAssign('priv_select', $select->select($v));    
        
        // role
        $v = (!empty($values['role'])) ? $values['role'] : 'all';
        $extra_range = array('all'  => '___', 
                             'none' => $this->msg['none_role_msg'],
                             'any' => $this->msg['any_role_msg']);
        $select->setRange($this->role_range, $extra_range);
        $tpl->tplAssign('role_select', $select->select($v));
        $tpl->tplAssign('ch_checked', $this->getChecked((!empty($values['ch']))));
        
        // company
        $v = (!empty($values['comp'])) ? $values['comp'] : 'all';
        $extra_range = array('all'  => '___', 
                             'none' => $this->msg['none_company_msg'],
                             'any' => $this->msg['any_company_msg']);
        $select->setRange($this->company_range, $extra_range);
        $tpl->tplAssign('company_select', $select->select($v));
        
        // status
        @$v = $values['s'];
        $extra_range = array('all'=>'__');
        $select->setRange($manager->getListSelectRange(false), $extra_range);
        $tpl->tplAssign('status_select', $select->select($v));
        
        
        // by
        //SEARCH
        $range = array('all' => '__', 
                        'id' => $this->msg['id_msg'],
                        'last_name' => $this->msg['last_name_msg'], 
                        'first_name' => $this->msg['first_name_msg'], 
                        'username' => $this->msg['username_msg'],
                        'email' => $this->msg['email_msg'],
                        'phone' => $this->msg['phone_msg'],
                       //'address'=>'Address', 'city'=>'City', 'state'=>'State', 'zip'=>'Zip', 
                       //'day_phone'=>'Day Phone', 'evening_phone'=>'Evening Phone', 
                       //'mobile_phone'=>'Mobile Phone', 'fax'=>'Fax'
                       );
        //$select->setRange($range);
        //$tpl->tplAssign('by_select', $select->select($values['by']));
        
        unset($range['all']);
        $msg = sprintf('%s: %s', $this->msg['search_msg'], implode(', ', $range));
        $tpl->tplAssign('search_infield', $msg);            
        
        
        $tpl->tplAssign($this->setCommonFormVarsFilter());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse($values);
        return $tpl->tplPrint(1);
    }
    
    
    function getFilterSql($manager, $roles) {
        
        // filter
        $mysql = array();
        $sphinx = array();
        @$values = $_GET['filter'];
        
        // priv
        @$v = $values['priv'];
        if($v == 'none') {
            $mysql['where'][] = "AND p.priv_name_id IS NULL";
            $sphinx['where'][] = "AND priv_name_id = 0";
        
        } elseif($v == 'any') {
            $mysql['where'][] = "AND p.priv_name_id IS NOT NULL";
            $sphinx['where'][] = "AND priv_name_id != 0";
        
        } elseif($v != 'all' && !empty($v)) {
            $priv_id = intval($v);
            $mysql['where'][] = "AND p.priv_name_id = '{$priv_id}'";
            $sphinx['where'][] = "AND priv_name_id = {$priv_id}";
        }    
        
        
        // role
        @$v = $values['role'];
        if($v == 'none') {
            $mysql['where'][] = "AND ur.role_id IS NULL";
            
            $sphinx['select'][] = 'LENGTH(role_ids) as _role_ids';
            $sphinx['where'][] = 'AND _role_ids = 0';
        
        } elseif($v == 'any') {
            $mysql['where'][] = "AND ur.role_id IS NOT NULL";
            
            $sphinx['select'][] = 'LENGTH(role_ids) as _role_ids';
            $sphinx['where'][] = 'AND _role_ids != 0';
        
        } elseif($v != 'all' && !empty($v)) {
            
            $role_id = (int) $v;            
            if(!empty($_GET['filter']['ch'])) {
                $child = $manager->getChildRoles($roles, $role_id);
                $child[] = $role_id;
                $child = implode(',', $child);    
                $mysql['where'][] = "AND ur.role_id IN($child)";
                $sphinx['where'][] = "AND role_ids IN ($child)";
                
            } else {
                $mysql['where'][] = "AND ur.role_id = $role_id";
                $sphinx['where'][] = "AND role_ids = $role_id";
            }
        }
        
        
        // company
        @$v = $values['comp'];
        if($v == 'none') {
            $mysql['where'][] = "AND u.company_id = 0";
            $sphinx['where'][] = "AND company_id = 0";
        
        } elseif($v == 'any') {
            $mysql['where'][] = "AND u.company_id != 0";
            $sphinx['where'][] = "AND company_id != 0";
        
        }  elseif($v != 'all' && !empty($v)) {
            $company_id = intval($v);
            $mysql['where'][] = "AND u.company_id = '{$company_id}'";
            $sphinx['where'][] = "AND company_id = $company_id";
        }
        
        
        // status
        @$v = $values['s'];
        if($v != 'all' && isset($_GET['filter']['s'])) {
            $v = (int) $v;
            $mysql['where'][] = "AND u.active = '$v'";
            $sphinx['where'][] = "AND active = $v";
        }                        
        
        
        // by
        @$v = $values['q'];
        @$by = $values['by'];
        
        if(!empty($v)) {
            $v = trim($v);
            
            if($ret = $this->isSpecialSearchStr($v)) {
                
                if($sql = $this->getSpecialSearchSql($manager, $ret, $v, 'u.id')) {
                    $mysql['where'][] = $sql['where'];
                    if(isset($sql['from'])) {
                        $mysql['from'][] = $sql['from'];    
                    }
                    
                } elseif($ret['rule'] == 'subscription') {
                    require_once APP_MODULE_DIR . 'user/subscription/inc/SubscriptionModel.php';
                    $m = new SubscriptionModel();
                    
                    $type_sql = 1;
                    $stype = false;
                    foreach($m->types as $snum => $stype) {
                        $stype = ($stype != 'news') ? str_replace('s', '', $stype) : $stype;
                        if(strpos($v, $stype . ':') !== false) {
                            $type_sql = "us.entry_type = '{$snum}'";
                            break;
                        } 
                    }
                    
                    $entry_sql = 1;
                    if($ret['val'] != '' && $type_sql != 1) {
                        $val = explode(',', $ret['val']);
                        array_walk($val, create_function('&$v,$k', '$v = (int) $v;'));
                        $val = implode(',', $val);
                        $entry_sql = "us.entry_id IN($val)";
                    }
                    
                    $mysql['from'][] = ", {$manager->tbl->user_subscription} us";
                    $mysql['where'][] = "AND us.user_id = u.id AND {$type_sql} AND {$entry_sql}";
                }
                            
            } else {
                
                $v = addslashes(stripslashes($v));
                $_v = str_replace('*', '%', $v);
                $sql_str = "%s LIKE '%%%s%%'";
                $f = array('first_name', 'last_name', 'username', 'email', 'username', 'phone');
                foreach($f as $field) {
                    $sql[] = sprintf($sql_str, $field, $_v);
                }
                
                $mysql['where'][] = 'AND (' . implode(" OR \n", $sql) . ')';
                
                $sphinx['match'][] = $v;
            }    
        }
        
        $options = array('index' => 'user', 'id_field' => 'u.id');
        $arr = $this->parseFilterSql($manager, $v, $mysql, $sphinx, $options);
        // echo '<pre>', print_r($arr, 1), '</pre>';
        
        return $arr;
    }
    
    
    // if some special search used
    function isSpecialSearchStr($str) {
        
        if($ret = parent::isSpecialSearchStr($str)) {
            return $ret;
        }
        
        $search = array();
        $search['subscription'] = "#^(?:subs|subscriber)(?:-news|-article_cat|-file_cat|-article|-file|-all)?:(\s?[\d,\s?]+)?$#";
                
        return $this->parseSpecialSearchStr($str, $search);
    }
    
    
    function getShowMsg2() {
        @$key = $_GET['show_msg2'];
        if ($key == 'note_remove_user_bulk') {
            $file = AppMsg::getCommonMsgFile('after_action_msg2.ini');
            $msgs = AppMsg::parseMsgsMultiIni($file);
            $msg['title'] = $msgs['title_remove_user_bulk'];
            $msg['body'] = $msgs['note_remove_user_bulk'];
            return BoxMsg::factory('error', $msg);
        }
    }
}
?>