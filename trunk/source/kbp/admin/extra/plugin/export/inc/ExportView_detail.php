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

class ExportView_detail extends AppView
{
    
    var $tmpl = 'form_detail.html';
    
    
    function execute(&$obj, &$manager) {
        
        $this->addMsg('user_msg.ini');
        
        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));

        $roles = $this->stripVars($manager->getRoleRange());

        if (!$obj->get('title')) {
            $obj->set('title', '--'); 
        } 

        
        $options = unserialize($obj->get('export_option'));
        
        // category
        $categories = $manager->cat_manager->getSelectRecords();
                
        $full_categories = &$manager->cat_manager->getSelectRangeFolow($categories);
        $full_categories = $this->stripVars($full_categories);
        
        if ($options['category_id'] != 0) {
            if (isset($full_categories[$options['category_id']])) {
                $category = $full_categories[$options['category_id']]; 
            } else {
                $category = '--';
            }
                  
        } else {
            $category = $this->msg['all_categories2_msg'];
        }

        $tpl->tplAssign('category', $category);

        // user
        $generate_for_user = $manager->getGenerateForUserSelectRange($this->msg);
        $generate_for_msg = $generate_for_user[$options['user_mode']];

        if ($options['user_mode'] == 2 && $options['role_ids']) {
            $generate_for_msg = '%s, %s: %s';
            $user_roles = array();
            
            foreach ($options['role_ids'] as $role_id) {
                if (isset($roles[$role_id])) {
                    $user_roles[] = $roles[$role_id];
                }
            }
            
            if (!empty($user_roles)) {
                $user_roles = implode(', ', $user_roles);
            } else {
                $user_roles = '--';
            }
            
            
            $generate_for_msg = sprintf($generate_for_msg, $this->msg['logged_user_msg'], 
                                                           $this->msg['generate_for_role_msg'], 
                                                           $user_roles);
        }
        
        $tpl->tplAssign('generate_for_user', $generate_for_msg);         
        
        
        $data = $manager->getExportData($obj->get('id'));

        if (!empty($options['do'])) {
    
            $tpl->tplSetNeededGlobal('generated_files');
            
            foreach ($options['do'] as $type => $val) {
                
                $k = array_search($type, $manager->export_types);
                $a = array();
                
                $a['export_file'] = $this->msg[$type . '_export_msg'];

                $more = array('type' => $k);            
                $link = $this->getActionLink('generate', $obj->get('id'), $more);
                $a['generate_link'] = $link;

                if (!empty($data[$k])) {
    
                    $link = $this->getActionLink('file', $obj->get('id'), $more);
                    $a['download_link'] = $link;
                
                    $a['date_generated_msg'] = $this->msg['date_generated_msg'];
                    $a['date_generated'] = $this->getFormatedDate($data[$k]['date_created'], 'datetimesec');
                    $a['export_result'] = $data[$k]['export_result'] ; 
                    
                    $tpl->tplParse(array_merge($a, $this->msg), 'generated_files_row');  
                
                } else {
                                   
                    $tpl->tplParse(array_merge($a, $this->msg), 'not_generated_files_row'); 
                }
            }
            
        } else {
            $tpl->tplSetNeeded('/no_generated_files');
        }
        
        // $more = array('id' => $obj->get('id'));
        // $link = $this->controller->getLink('this', 'this', false, 'update', $more);
        $link = $this->getActionLink('update', $obj->get('id'));
        $tpl->tplAssign('update_link', $link);


        $tpl->tplAssign($this->setCommonFormVars($obj));
        //$tpl->tplAssign($this->setStatusFormVars($obj->get('active')));        
        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);
        
        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }    
}
?>