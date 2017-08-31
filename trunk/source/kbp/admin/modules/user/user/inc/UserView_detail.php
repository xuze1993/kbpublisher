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


class UserView_detail extends AppView
{
    
    var $tmpl = 'detail.html';
    var $account_view = false;
    var $admin_view = true;
    
    
    function execute(&$obj, &$manager, $title = false) {
        
        $this->addMsg('user_msg.ini');

        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);    
        
        $tpl->tplAssign('formatted_date_full', $this->getFormatedDate($obj->get('date_registered'), 'datetime'));
        $tpl->tplAssign('interval_date_full', $this->getTimeInterval($obj->get('date_registered'), true));
        
        $lastauth = ($la = $obj->get('lastauth')) ? $this->getFormatedDate($la, 'datetime') : '-';
        $tpl->tplAssign('formatted_date_lastauth_full', $lastauth);

        $lastauth = ($la) ? sprintf('(%s)', $this->getTimeInterval($la, true)) : '-';
        $tpl->tplAssign('interval_date_lastauth_full', $lastauth);
        
            
        // role
        $roles = $manager->getRoleSelectRangeFolow();
        $roles = $this->stripVars($roles);
        
        $_user_roles = array();
        foreach($obj->getRole() as $role_id) {
            if(isset($roles[$role_id])) {
                $_user_roles[] = $roles[$role_id];
            }
        }
        
        $user_roles = ($_user_roles) ? implode('<br/>', $_user_roles) : '';
        $tpl->tplAssign('role', $user_roles);
        
        
        // priv            
        $priv = $manager->getPrivSelectRange(false);
        $priv = $this->stripVars($priv);

        $_user_priv = array();
        foreach($obj->getPriv() as $priv_id) {
            if(isset($priv[$priv_id])) {
                $_user_priv[] = $priv[$priv_id];
            }
        }

        $user_priv = ($_user_priv) ? implode('<br/>', $_user_priv) : '';
        
        $tpl->tplSetNeeded('/priv');
        $tpl->tplAssign('priv', $user_priv);


        // company
        $companies = $manager->getCompanySelectRange();
        $company = (isset($companies[$obj->get('company_id')])) ? $companies[$obj->get('company_id')] : '';
        $tpl->tplAssign('company', $company);   
        
        
        // state
        require_once 'eleontev/data_arrays/state.php';
        $st = (isset($state[$obj->get('state')])) ? $state[$obj->get('state')] : '';
        $tpl->tplAssign('state', $st);
        
        
        // country
        $countries = $manager->getCountrySelectRange();
        $country = (isset($countries[$obj->get('country')])) ? $countries[$obj->get('country')] : '';
        $tpl->tplAssign('country', $country);
        
        
        // subscription
        $subscription = $manager->getUserSubscription($obj->get('id'));
        $subsc_msg = '';
        if(isset($subscription[$obj->get('id')])) {
            $s_manager = new SubscriptionModel;
            $subs_types = $s_manager->types;
        
            $subsc_msg = array();
            foreach($subscription[$obj->get('id')] as $entry_type => $num) {
                $subs_msg_key = $subs_types[$entry_type] . '_subsc_msg';
                $type_msg = $this->msg[$subs_msg_key];
                $num_msg = ($num == 'all') ? $this->msg['all_msg'] : $num;
                $subsc_msg[] = sprintf('%s: %s', $type_msg, $num_msg);
            }
                 
            $subsc_msg = implode('<br/>', $subsc_msg);
        }
        
        $tpl->tplAssign('subsc_msg', $subsc_msg);
        
        
        // api data
        $api_rule_id = $manager->extra_rules['api']; 
        $api_data = $obj->getExtraValues($api_rule_id);
        $api_access = (!empty($api_data['api_access']));
        $tpl->tplAssign('api_access_value', ($api_access) ? $this->msg['enabled_msg'] : $this->msg['disabled_msg']);
        
        
        if($this->account_view) {
            $tpl->tplSetNeededGlobal('account');
            if($manager->account_updateable) {
                $tpl->tplSetNeeded('/account_update');
            }
            
            if($api_access || $manager->is_admin) {
                $tpl->tplSetNeeded('/api_update');
            }
        
        } else {
            
            $tpl->tplAssign('menu_block', UserView_common::getEntryMenu($obj, $manager, $this));     
            
            $this->msg['update_profile_msg'] = $this->msg['update_msg'];
            $title = $this->msg['detail_msg'];
            $tpl->tplSetNeededGlobal('not_account');
            
            // login as user
            if($this->priv->isPriv('login')) {
                $tpl->tplSetNeeded('/login_as_user');
                $tpl->tplAssign('login_user_link', $this->getActionLink('login', $obj->get('id')));
            }
            
            // activity 
            $activity_block = '';
            $a_manager = new UserModel_activity;
            $activity = $a_manager->getUserActivities($obj->get('id'));
            $activity = $activity[$obj->get('id')];
            if (array_sum($activity)) {                
                if($rows = $this->getUserActivityArray($activity, $obj->get('id'))) {
                    $activity_block = '<li>' . implode('</li><li>', $rows) . '</li>';
                }
            }
            
            $tpl->tplAssign('activity_block', $activity_block);
        }
        
        // status
        $status = $obj->get('active'); 
        $status_range = $manager->getListSelectRange('user_status', true, $status);
        $tpl->tplAssign('status', $status_range[$status]); 
        
        
        if(!$title) {
            $title = $this->msg['user_account_msg'];
        }
        
        if($this->admin_view) {
            $vars = $this->setCommonFormVars($obj);
            
            $more = array('back'=>'detail');
            $update_link = $this->getActionLink('update', $obj->get('id'), $more);
            $update_password_link = $this->getActionLink('password', $obj->get('id'), $more);
            $update_api_link = $this->getActionLink('api', $obj->get('id'), $more);
        } else {
            $vars = array();
            $update_link = $this->getLink('member_account', false, false, 'update');
            $update_password_link = $this->getLink('member_account', false, false, 'password');
            $update_api_link = $this->getLink('member_account', false, false, 'api');
        }
        
        
        $tpl->tplAssign('update_link', $update_link);
        $tpl->tplAssign('update_password_link', $update_password_link);
        $tpl->tplAssign('update_api_link', $update_api_link);
        $tpl->tplAssign('form_title', $title);
        
        $tpl->tplAssign($vars);
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }
    
    
    function getUserActivityArray($data, $user_id, $critical_color = false) {
        
        $stuff = array(
            'author' => array(
                'article_author' => array(
                    'page' => 'kb_entry',
                    'msg' => 'articles_msg'),
                    
                'file_author' => array(
                    'page' => 'file_entry',
                    'msg' => 'files_msg'
                )
            ),
            'updater' => array(
                'article_updater' => array(
                    'page' => 'kb_entry',
                    'msg' => 'articles_msg'
                ),
                'file_updater' => array(
                    'page' => 'file_entry',
                    'msg' => 'files_msg'
                )
            ),
            
            'supervisor' => array(
                'kb_category_to_user_admin' => array(
                    'page' => 'kb_category',
                    'msg' => 'article_cats_msg'),
                    
                'file_category_to_user_admin' => array(
                    'page' => 'file_category',
                    'msg' => 'file_cats_msg'),
                    
                'feedback_user_admin' => array(
                    'page' => 'feedback',
                    'msg' => 'feedback_subjects_msg'
                 )
            ),
            'assignee' => array(
                'article_workflow' => array(
                    'page' => 'workflow',
                    'msg' => 'article_workflow_msg'),
                    
                'file_workflow' => array(
                    'page' => 'workflow',
                    'msg' => 'file_workflow_msg'
                ),
                
                'article_draft' => array(
                    'page' => 'kb_draft',
                    'msg' => 'article_draft_msg'),
                    
                'file_draft' => array(
                    'page' => 'file_draft',
                    'msg' => 'file_draft_msg'
                ),
            ),
            'participant' => array(
                'article_automation' => array(
                    'page' => 'automation',
                    'msg' => 'article_automation_msg'),
                    
                'file_automation' => array(
                    'page' => 'automation',
                    'msg' => 'file_automation_msg'
                ),
                'file_rule' => array(
                    'page' => 'file_rule',
                    'msg' => 'file_rule_msg'
                 )
            )
        );
        
        $critical_keys = array('article_workflow', 'file_workflow', 'file_rule');
        
        $links = array(
            'article_author' => $this->getLink('knowledgebase', 'kb_entry', false, false, array('filter[q]' => 'author_id:' . $user_id)),
            'article_updater' => $this->getLink('knowledgebase', 'kb_entry', false, false, array('filter[q]' => 'updater_id:' . $user_id)),
            'file_author' => $this->getLink('file', 'file_entry', false, false, array('filter[q]' => 'author_id:' . $user_id)),
            'file_updater' => $this->getLink('file', 'file_entry', false, false, array('filter[q]' => 'updater_id:' . $user_id)),
            'kb_category_to_user_admin' => $this->getLink('knowledgebase', 'kb_category', false, false, array('filter[c]' => 'all', 'filter[supervisor_id]' => $user_id)),
            'file_category_to_user_admin' => $this->getLink('file', 'file_category', false, false, array('filter[c]' => 'all', 'filter[supervisor_id]' => $user_id)),
            'feedback_user_admin' => $this->getLink('tool', 'list_tool', 'feedback_subj', false, array('filter[supervisor_id]' => $user_id)),
            'article_workflow' => $this->getLink('tool', 'workflow', 'wf_article', false, array('filter[approver_id]' => $user_id)),
            'file_workflow' => $this->getLink('tool', 'workflow', 'wf_file', false, array('filter[approver_id]' => $user_id)),
            'article_draft' => $this->getLink('knowledgebase', 'kb_draft', false, false, array('filter[t]' => $user_id)),
            'file_draft' => $this->getLink('file', 'file_draft', false, false, array('filter[t]' => $user_id)),
            'article_automation' => $this->getLink('tool', 'automation', 'am_article', false, array('filter[approver_id]' => $user_id)),
            'file_automation' => $this->getLink('tool', 'automation', 'am_file', false, array('filter[approver_id]' => $user_id)),
            'file_rule' => $this->getLink('file', 'file_rule', false, false, array('filter[q]' => 'author_id:' . $user_id))
        );
        
        $reg =& Registry::instance();
        $priv = $reg->getEntry('priv');
        
        $msg = AppMsg::getMsgs('ranges_msg.ini', false, 'user_stuff');
        
        $this->critical_activity = false;
        $rows = array();
        
        foreach ($stuff as $section_key => $section_stuff) {
            $section_content = array();
            foreach ($section_stuff as $k => $v) {
                
                if ($data[$k]) {

                    $style = '';
                    if (in_array($k, $critical_keys) && $critical_color) {
                        $this->critical_activity = true;
                        $style = sprintf(' style="color: %s;"', $critical_color);
                    }

                    $item = sprintf('<span%s>%s %s', $style, $data[$k], $this->msg[$v['msg']]);
                    if ($priv->isPriv('select', $v['page'])) {
                        $item = sprintf('<a href="%s"%s>%s</a>', $links[$k], $style, $item);                        
                    }
                    
                    $section_content[] = $item;
                }
            }
            
            if (!empty($section_content)) {
                $rows[$section_key] = sprintf('%s %s', $msg[$section_key], implode(', ', $section_content));
            }
        }
        
        return $rows;
    }
        
}
?>