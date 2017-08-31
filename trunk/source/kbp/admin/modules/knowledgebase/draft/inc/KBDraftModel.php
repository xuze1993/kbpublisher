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

require_once 'core/common/CommonEntryModel.php';
require_once APP_MODULE_DIR . 'tool/workflow/inc/WorkflowEntryModel.php';


/*
entry_draft.step_num
0 rejected back to user, not being approved
1,2,... steps numbers

entry_draft.active
1 - submited, updated
2 - rejected back
3 - published
*/


class KBDraftModel extends CommonEntryModel
{

    var $tbl_pref_custom = '';
    var $tables = array('table' => 'entry_draft',
                        'entry' => 'entry_draft',
                        'draft_workflow' => 'entry_draft_workflow',
                        'workflow_history' => 'entry_draft_workflow_history',
                        'workflow_to_assignee' => 'entry_draft_workflow_to_assignee',
                        'data_to_value'=>'data_to_user_value',
                        'data_to_value_string'=>'data_to_user_value_string',
                        'draft_to_category' => 'entry_draft_to_category',
                        //'entry_to_category' => 'entry_draft_to_category',
                        'user',
                        'lock' => 'entry_lock',
                        'autosave' => 'entry_autosave',
                        'trigger',
                        'data_to_user_value');

    
    var $role_write_rule = 'kb_draft_to_role_write';
    var $role_write_id = 103;

    var $entry_type = 7;
    var $from_entry_type = 1; // article
    var $mail_use_pool = false;
    
    var $draft_status = array(
        'in_progress'   => 1,
        'submitted'     => 2,
        'rejected'      => 3
    );

    var $draft_status_colors = array(
        'in_progress' => 'grey',
        'submitted' => 'yellow',
        'rejected' => 'red'
    );


    function __construct() {
        
        parent::__construct();
        
        $this->dv_manager = new DataToValueModel();
        $this->cat_manager = ($this->entry_type == 7) ? new KBCategoryModel() : new FileCategoryModel;
        $this->wf_manager = new WorkflowEntryModel;
        
        $this->user_id = AuthPriv::getUserId();
        $this->user_priv_id = AuthPriv::getPrivId();
        $this->user_role_id = AuthPriv::getRoleId();
        
        $this->role_manager = &$this->cat_manager->role_manager;
        $this->setEntryRolesSql('write', true);
        $this->setCategoriesNotInUserRole('write');
    }


    function getById($record_id) {
        
        $this->setSqlParams('AND d.id = ' . $record_id);
        $this->setSqlParams('AND d.entry_type = ' . $this->from_entry_type);
        
        $sql = $this->getRecordsSql();
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }


    function getRecordsSql() {
        
        $sql = "SELECT d.*,
                dw1.id as last_event_id,
                dw1.workflow_id,
                dw1.step_num,
                dw1.comment,
                dw1.active as last_event_action,
                t.action as workflow_action,
                u.username,
                u.first_name,
                u.last_name,
                u.middle_name

            FROM {$this->tbl->table} d

            LEFT JOIN (
                {$this->tbl->draft_workflow} dw1
                INNER JOIN (
                    SELECT MAX(dw.id) as id, dw.draft_id FROM {$this->tbl->draft_workflow} dw 
                    GROUP BY dw.draft_id) dw2
                ON dw1.id = dw2.id
            ) ON dw1.draft_id = d.id

            LEFT JOIN {$this->tbl->trigger} t ON dw1.workflow_id = t.id
            LEFT JOIN {$this->tbl->user} u ON d.author_id = u.id
            LEFT JOIN {$this->tbl->workflow_to_assignee} da 
                ON da.draft_workflow_id = dw1.id

            -- LEFT JOIN {$this->tbl->draft_to_category} d_to_cat
                -- ON d_to_cat.draft_id = d.id

            {$this->entry_role_sql_from}

            WHERE {$this->sql_params}
            AND {$this->entry_role_sql_where}
            
            GROUP BY d.id
            
            {$this->sql_params_order}";

        // echo '<pre>', print_r($sql, 1), '</pre>';
        return $sql;
    }


    function getCountRecordsSql() {
        
        $sql = "SELECT COUNT(DISTINCT d.id) AS num
            FROM {$this->tbl->table} d
            LEFT JOIN (
                {$this->tbl->draft_workflow} dw1
                INNER JOIN (
                    SELECT MAX(dw.id) as id, dw.draft_id FROM {$this->tbl->draft_workflow} dw 
                    GROUP BY dw.draft_id) dw2
                ON dw1.id = dw2.id
            ) ON dw1.draft_id = d.id
            
            LEFT JOIN {$this->tbl->workflow_to_assignee} da 
                ON da.draft_workflow_id = dw1.id
                
            -- LEFT JOIN {$this->tbl->draft_to_category} d_to_cat
                -- ON d_to_cat.draft_id = d.id
                
            {$this->entry_role_sql_from}

            WHERE {$this->sql_params}
            AND {$this->entry_role_sql_where}";
        
        return $sql;
    }    
    
    
    // hide in list if any assigned category is private, 
    // ignore if in workflow 
    function getDraftCategoryRolesSql($emanager) {
        
        $role_skip_sql = 1;
    
        if($emanager->role_skip_categories) {
            $cats = implode(',', $emanager->role_skip_categories);
            
            // $role_sql = sprintf("d_to_cat.category_id NOT IN(%s)", $cats);
            // $role_skip_sql = "IF(dw1.workflow_id AND dw1.step_num != 0, 1, {$role_sql})";
            
            // new way, if any category not allowed it will not displayed 5 June 2017
            $role_sql = sprintf("
                (SELECT COUNT(*) FROM {$this->tbl->draft_to_category} d_to_cat2 
                    WHERE d.id = d_to_cat2.draft_id AND d_to_cat2.category_id IN(%s)) < 1", $cats);
            $role_skip_sql = "IF(dw1.workflow_id AND dw1.step_num != 0, 1, {$role_sql})"; 
        
        }
        
        return $role_skip_sql;
    }
    
    
    function getAssigneeList($entry_type = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->from_entry_type;
        
        $sql = "SELECT da.assignee_id
        
            FROM ({$this->tbl->draft_workflow} dw1,
                {$this->tbl->workflow_to_assignee} da,
                {$this->tbl->table} d)
            
            INNER JOIN (
                SELECT MAX(dw.id) as id, dw.draft_id
                FROM {$this->tbl->draft_workflow} dw
                GROUP BY dw.draft_id
            ) dw2
            ON dw1.id = dw2.id
            
            WHERE dw1.draft_id = d.id
            AND d.entry_type = '{$entry_type}'
            AND dw1.id = da.draft_workflow_id
            
            GROUP BY da.assignee_id";
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        $data = array();
        while($row = $result->FetchRow()) {
            $data[] = $row['assignee_id'];
        }
        
        return $data;
    }
    
    
    function getAssigneeByStepIds($step_ids) {
        if (!is_array($step_ids)) {
            $step_ids = array($step_ids);
        }
        
        $sql = "SELECT * FROM {$this->tbl->workflow_to_assignee} WHERE draft_workflow_id IN (%s)";
        $sql = sprintf($sql, implode(',', $step_ids));
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        $data = array();
        while($row = $result->FetchRow()) {
            $data[$row['draft_workflow_id']][] = $row['assignee_id'];
        }
        
        return $data;
    }


    function getDraftStatusSelectRange() {
        $msg = AppMsg::getMsgs('ranges_msg.ini', false, 'draft_status');
        foreach($this->draft_status as $k => $v) {
            $range[$v] = $msg[$k];
        }

        return $range;
    }
    
    
    function getByEntryId($record_id, $entry_type) {
        $sql = "SELECT * FROM {$this->tbl->table}
        WHERE entry_id = '{$record_id}' 
        AND entry_type = '{$entry_type}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    
    
    // return workflow if draft matched rules, false otherwise
    function getAppliedWorkflow($options = array()) {
        
        // workflow disabled
        if(!BaseModel::isModule('workflow')) {
            return false;
        }
        
        if (empty($options['user_id'])) {
            $user_id = $this->user_id;
            $priv_id = $this->user_priv_id;
            
        } else {
            $user_id = $options['user_id'];
            
            $user_manager = new UserModel;
            $priv = $user_manager->getPrivById($options['user_id']);
            $priv_id = key($priv);
        }
        
        $source = (empty($options['source'])) ? 'web' : $options['source'];
        
        $workflows = $this->getAvailableWorkflows();

        $table = array(
            'is'     => '==', 
            'is_not' => '!=', 
            'less'   => '<', 
            'more'   => '>',
            'equal'   => '=='
        );

        $rule_to_value = array(
            'draft' => 'published', // one value in form 
            'draft_source' => $source,
            'privilege_level' => $priv_id,
            'author' => $user_id
        );
        
        foreach ($workflows as $v) {
            
            $v['action'] = unserialize($v['action']);
            $conditions = unserialize($v['cond']);
            $is_met = false;
            
            foreach ($conditions as $condition) {
                
                $item = $condition['item'];
                $rule = $condition['rule'][0];
                
                $value_1 = $this->getQuotedVar($condition['rule'][1]);
                $value_2 = $this->getQuotedVar($rule_to_value[$item]);
                $sign = $table[$rule];
                
                $eval_str = '$is_met = (%s %s %s) ? true : false;';
                $eval_str = sprintf($eval_str, $value_1, $sign, $value_2);
                eval($eval_str);
                
                // echo '<pre>', print_r($item, 1), '</pre>';
                // echo '<pre>', print_r($rule, 1), '</pre>';
                // echo '<pre>', print_r($eval_str, 1), '</pre>';
                // echo '<pre>', print_r($is_met, 1), '</pre>';
                // echo '<pre>', print_r("===========", 1), '</pre>';
                
                // any, at least one matched
                if($v['cond_match'] == 1 && $is_met) {
                    return $v;
                
                // all, at least 1 not matched
                } elseif ($v['cond_match'] == 2 && !$is_met) {
                    continue 2;
                }
            }
            
            // all matched
            if($is_met) {
               return $v; 
            }
        }
        
        return false;
    }
    
    
    function getQuotedVar($val) {        
        if($val === NULL) {
            return "NULL";
        } elseif(is_numeric($val)) {
            return $val;
        } else {
            return sprintf("'%s'", addslashes($val));
        }
    }
    
    
    function getAssignees($obj, $eobj, $emanager, $workflow, $step_num) {
        $assignees = array();
        $last_event = $this->getLastApprovalEvent($obj->get('id'), $step_num);
        if ($last_event) {
            $assignees = $this->getAssigneeByStepIds($last_event['id']);
            $assignees = (!empty($assignees)) ? $assignees[$last_event['id']] : array();
        }
        
        if ($assignees) {
            return $assignees;
        }
        
        $rule = $workflow['action'][$step_num]['rule'][0];
        
        if ($rule == 'category_admin') {
            // $assignees = $this->getSupervisorsByCatId($emanager, $eobj->get('category_id'));
            $assignees = $this->getSupervisors($emanager, $eobj->getCategory());
            return $assignees;
            
        } elseif ($rule == 'author') {
            return $obj->get('author_id');
            
        } elseif (strpos($rule, 'priv_') === 0) {
            $priv_id = substr($rule, 5);
            
            $u_manager = new UserModel;
            $u_manager->setSqlParams('AND p.priv_name_id = ' . $priv_id);
            $assignees = $u_manager->getRecords();
            
            return $this->getValuesArray($assignees, 'id');

        } elseif (is_numeric($rule)) {
            return $rule;
        }
        
    }
    
    
    function getAvailableWorkflows($entry_type = false) {
        $entry_type = ($entry_type) ? $entry_type : $this->from_entry_type;
        $sql = "SELECT * FROM {$this->tbl->trigger}
            WHERE entry_type = {$entry_type}
            AND trigger_type = 4
            AND active = 1
            ORDER BY sort_order";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetArray();
    }
    
    
    function moveToStep($draft_id, $workflow_id, $step_number, $step_title, $comment, $status) {
        $sql = "INSERT {$this->tbl->draft_workflow}
        (draft_id, workflow_id, user_id, step_num, step_title, comment, active) 
        VALUES (%d, %d, %d, %d, '%s', '%s', %d)";
        $sql = sprintf($sql, $draft_id, $workflow_id, $this->user_id, $step_number, $step_title, $comment, $status);
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        return $this->db->Insert_ID();
    }
    
    
    function saveAssignees($draft_id, $step_id, $assignees) {
    
        if(empty($assignees)) {
            return;
        }
        
        require_once 'eleontev/SQL/MultiInsert.php';
        
        $data = array();
        $assignees = (is_array($assignees)) ? $assignees : array($assignees);
                                             
        foreach($assignees as $assignee_id) {
            $data[] = array($draft_id, $step_id, $assignee_id);
        }     
                                                      
        $sql = MultiInsert::get("INSERT IGNORE {$this->tbl->workflow_to_assignee} (draft_id, draft_workflow_id, assignee_id) VALUES ?", $data); 
                                                                                                            
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function getLastApprovalEvent($draft_id, $step_num = false) {
        $sql = "SELECT * FROM {$this->tbl->draft_workflow} 
        WHERE draft_id = %d %s
        ORDER BY id DESC LIMIT 1";
        
        $step_cond = ($step_num) ? 'AND step_num = '. $step_num : ''; 
        $sql = sprintf($sql, $draft_id, $step_cond);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    

    function getLastSubmissionEvent($draft_id) {
        $sql = "SELECT * FROM {$this->tbl->draft_workflow} 
        WHERE draft_id = '{$draft_id}' 
        AND step_num = 1 AND active = 1
        ORDER BY id DESC LIMIT 1";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    
    
    function getApprovalLog($draft_id) {
        $sql = "SELECT * FROM {$this->tbl->draft_workflow} 
        WHERE draft_id = '{$draft_id}'
        ORDER BY date_posted ASC";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function addApprovalHistory($draft_id, $entry_id, $entry_type) {
        $sql = "INSERT {$this->tbl->workflow_history} 
            SELECT draft_id, {$entry_id}, {$entry_type},
            user_id, date_posted, step_num, step_title, comment, active
            FROM {$this->tbl->draft_workflow} 
            WHERE draft_id = '{$draft_id}'";
        $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function getDraftStatusData() {
        
        $msg = AppMsg::getMsg('ranges_msg.ini', false, 'draft_status', 1, 1);
          
        $data = array();
        foreach($this->draft_status as $k => $v) {
            $data[$v] = array(
                'title' => $msg[$k],
                'color' => $this->draft_status_colors[$k]
                );
        }

        return $data;
    }
    
    
    function getSupervisors($emanager, $category_ids, $categories = array()) {
        return $emanager->cat_manager->getSupervisors($category_ids, $categories);
    }
        
    
    function isUserAllowedToApprove($assignees) {

        if (AuthPriv::isAdmin()) {
            return true;
        }

        return in_array($this->user_id, $assignees);
    }
    
    
    /*
    function getSubmissionDate($draft_id) {
        $sql = "SELECT MAX(date_posted) as date_submitted 
            FROM {$this->tbl->draft_workflow}
                WHERE draft_id = '{$draft_id}'
                    AND step_num = 1
                    AND active = 1";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('date_submitted');
    }
    */
    
    
    function getAwaitingDrafts($entry_type = '1,2', $user_id = false, $group = true) {
        
        $sql = "SELECT %s
            FROM {$this->tbl->draft_workflow} dw1
            
            INNER JOIN (
                SELECT MAX(dw.id) as id, dw.draft_id 
                FROM {$this->tbl->draft_workflow} dw GROUP BY dw.draft_id) dw2
            ON dw1.id = dw2.id
            
            INNER JOIN {$this->tbl->table} d
            ON dw1.draft_id = d.id
            AND d.entry_type IN (%s)
            
            LEFT JOIN {$this->tbl->workflow_to_assignee} da 
                ON da.draft_workflow_id = dw1.id
            
            WHERE step_num != 0
                AND dw1.active != 3
                AND da.assignee_id = %s %s";
        
        
        if ($group) {
            $select_str = 'd.entry_type, COUNT(dw1.draft_id) AS num';
            $group_by_str = 'GROUP BY d.entry_type';
            
        } else {
            $select_str = 'dw1.*';
            $group_by_str = '';
        }
        
        $user_id = ($user_id) ? $user_id : $this->user_id;
        
        $sql = sprintf($sql, $select_str, $entry_type, $user_id, $group_by_str);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        // echo $this->getExplainQuery($this->db, $result->sql);
        
        return $result->GetAssoc();
    }
    
    
    function getSupervisedCategories($rule_id, $user_id = false) {
        $user_id = ($user_id) ? $user_id : $this->user_id;
           
        $sql = "SELECT data_value 
            FROM {$this->tbl->data_to_user_value}
            WHERE user_value = %d
                AND rule_id = %d";
        $sql = sprintf($sql, $user_id, $rule_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        $data = array();
        while($row = $result->FetchRow()) {
            $data[] = $row['data_value'];
        }
        
        return $data;    
    }
    
    
    function getDraftStatRecords($entry_type = false) {
        
        $entry_type = ($entry_type) ? $entry_type : $this->entry_type;
        
        $sql = "SELECT IFNULL(dw1.active, 0) as status,
            dw1.step_num,
            COUNT(*) AS 'num'
            
            FROM {$this->tbl->table} d

            LEFT JOIN (
                {$this->tbl->draft_workflow} dw1
                INNER JOIN (
                    SELECT MAX(dw.id) as id, dw.draft_id 
                    FROM {$this->tbl->draft_workflow} dw GROUP BY dw.draft_id) dw2
                ON dw1.id = dw2.id
            ) ON dw1.draft_id = d.id

            WHERE d.entry_type = '{$entry_type}'
                AND {$this->sql_params}
            GROUP BY dw1.active, dw1.step_num";

        $result = $this->db->Execute($sql) or die(db_error($sql));
        //echo $this->getExplainQuery($this->db, $result->sql);

        $rows = $result->GetArray();
        $data = array(0, 0, 0);
        
        foreach ($rows as $row) {
            if ($row['status'] == 2 && $row['step_num'] != 0) {
                $data[1] += $row['num'];
                
            } else {
                $data[$row['status']] += $row['num']; 
            }
        }
        
        return $data;
    }


    // PRIV // -------------------
    
    // return categories array for entry
    function getDraftCategoryById($record_id) {
        $sql = "SELECT category_id, category_id as 'cat_id' 
            FROM {$this->tbl->draft_to_category} 
            WHERE draft_id IN ($record_id)";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }
    
    
    function isDraftInUserRole($record_id) {
        $sql = "SELECT 1 FROM {$this->tbl->entry} d
            LEFT JOIN (
                {$this->tbl->draft_workflow} dw1
                INNER JOIN (
                    SELECT MAX(dw.id) as id, dw.draft_id FROM {$this->tbl->draft_workflow} dw 
                    GROUP BY dw.draft_id) dw2
                ON dw1.id = dw2.id
            ) ON dw1.draft_id = d.id
                {$this->entry_role_sql_from}
            WHERE d.id = %d 
                AND {$this->entry_role_sql_where}
            GROUP BY d.id";
        $sql = sprintf($sql, $record_id);
        
        // echo '<pre>', print_r($sql, 1), '</pre>';
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return ($result->Fields(1));
    } 
    

    function checkPriv(&$priv, $action, $record_id = false, $bulk_action = false, 
                            $controller = false, $emanager = false) {
        
        $priv->setCustomAction('category', 'select');
        $priv->setCustomAction('role', 'select');
        $priv->setCustomAction('tags', 'select');
        $priv->setCustomAction('preview', 'select');
        $priv->setCustomAction('lock', 'select');
        $priv->setCustomAction('approval_lock', 'select');
        $priv->setCustomAction('autosave', 'select');
        $priv->setCustomAction('approval', 'update');
        $priv->setCustomAction('approval_log', 'select');
        $priv->setCustomAction('convert', 'insert');
        
        // files
        $priv->setCustomAction('text', 'update');
        $priv->setCustomAction('file', 'select');
        $priv->setCustomAction('fopen', 'select');
        
        // actions with entry and draft exixts 
        $priv->setCustomAction('entry_update', 'select');
        // $priv->setCustomAction('entry_delete', 'select');
        
        
        if($action == 'bulk') {
            $bulk_manager = new KBDraftModelBulk();
            $allowed_actions = $bulk_manager->setActionsAllowed($this, $priv);
        
            if(!in_array($bulk_action, $allowed_actions)) {
                echo $priv->errorMsg();
                exit;
            }
        }
        
        
        // check for roles on insert
        if($action == 'insert') {
            
            $categories = array();
            if(!empty($_POST['category'])) {
                $categories = $_POST['category'];
                
            } elseif($entry_id = (int) $controller->getMoreParam('entry_id')) {
                $categories = $emanager->getCategoryById($entry_id);
            }

            $has_private = $this->isCategoryNotInUserRole($categories);
            if($has_private) {
                echo $priv->errorMsg();
                exit;
            }
        }
        
        // check not assigned (not in workflow drafts)
        $actions = array('approval', 'update', 'delete');
        if(in_array($action, $actions) && $record_id) {

            $last_event = $this->getLastApprovalEvent($record_id);
            
            if (!$this->isBeingApproved($last_event)) {
                
                // entry is private and user no role
                if(!$this->isDraftInUserRole($record_id)) {
                    echo $priv->errorMsg();
                    exit;
                }

                // if some of categories is private and user no role
                $categories = $this->getDraftCategoryById($record_id);
                $has_private = $this->isCategoryNotInUserRole($categories);
                if($has_private) {
                    echo $priv->errorMsg();
                    exit;
                }
            }
        }
        
        
        $sql = $this->getOwnSql($record_id);
        $priv->setOwnSql($sql);
        
        // it will die if use_exit_screen = true
        $priv_no_exit = $priv->check($action);
        
        
        $actions = array('approval', 'update', 'delete');
        if(in_array($action, $actions)) {
            
            // check for priv and not using use_exit_screen
            if (!$priv_no_exit) {
                return $priv_no_exit;
            }
            
            // if entry in approval queue check if user can access it
            $priv_approval = $this->checkForApproval($record_id, $action);
            
            if (!$priv_approval) {
                if ($priv->use_exit_screen) {
                
                    // specially for approve action, user can clik from email
                    // if($action == 'approve') { 
                    if($action == 'approval') { // replace above line 2017-12-06 eleontev looks like a typo 
                        $controller->go('record_not_exists', true);
                    
                    // die in other cases
                    } else  {
                        echo $priv->errorMsg();
                        exit;
                    }
                }

                return false;
            }
            
            return true;
        }
        
        
        // set sql to select own records
        $priv->setOwnParam($this->getOwnParams($priv));

        $this->setSqlParams('AND ' . $priv->getOwnParam());
    }
    
    
    // if entry in approval queue check if user can access it
    function checkForApproval($record_id, $action) {
        
        $ret = true;
        $last_event = $this->getLastApprovalEvent($record_id);
        
        if ($this->isBeingApproved($last_event)) {
            $assignees = $this->getAssigneeByStepIds($last_event['id']);
            $assignees = (!empty($assignees)) ? $assignees[$last_event['id']] : array();
            
            $ret = false;
            if ($this->isUserAllowedToApprove($assignees)) {
                $ret = true;
            }
            
        } elseif ($action == 'approval') { // nothing to approve
            $ret = false;
        }
        
        return $ret;
    }
    
    
    // being approved
    function isBeingApproved($last_event) {
        return (!empty($last_event) && $last_event['step_num'] != 0);
    }
    
    function isBeingApprovedByRow($row) {
        return (!empty($row['last_event_id']) && $row['step_num'] != 0);
    }
    
    
    function getOwnSql($record_id) {
        $sql = "SELECT 1 FROM %s WHERE id = '%s' AND author_id = '%s' AND entry_type = '%d'";
        $sql = sprintf($sql, $this->tbl->table, $record_id, $this->user_id, $this->from_entry_type);
        return $sql;
    }


    function getOwnParams($priv) {
        return sprintf("author_id=%d", $priv->user_id);
    }
    
    
    function saveDraftToCategory($cat, $record_id) {
        
        require_once 'eleontev/SQL/MultiInsert.php';
        
        $data = array();
        $record_id = (is_array($record_id)) ? $record_id : array($record_id);
        $cat = (is_array($cat)) ? $cat : array($cat);
        
        foreach($cat as $cat_id) {
            foreach($record_id as $draft_id) {
                $data[] = array($cat_id, $draft_id);
            }
        }        
        
        $sql = MultiInsert::get("INSERT IGNORE {$this->tbl->draft_to_category} (category_id, draft_id) 
                                 VALUES ?", $data);
        
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function deleteDraftToCategory($record_id, $entry_type = false) {
        $sql = "DELETE FROM {$this->tbl->draft_to_category} 
        WHERE draft_id IN ({$record_id})";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function save($obj) {

        $action = (!$obj->get('id')) ? 'insert' : 'update';

        // insert
        if($action == 'insert') {

            $id = $this->add($obj);
            
            if($obj->getCategory()) {
                $this->saveDraftToCategory($obj->getCategory(), $id);
            }

            if($obj->get('private')) {
                $this->saveRoleToEntryObj($obj, $id);
            }

        // update
        } else {

            $id = $obj->get('id');

            $this->update($obj);

            $this->deleteDraftToCategory($id);
            if($obj->getCategory()) {
                $this->saveDraftToCategory($obj->getCategory(), $id);
            }

            $this->deleteRoleWriteToEntry($id);
            if($obj->get('private')) {
                $this->saveRoleToEntryObj($obj, $id);
            }
        }
        
        return $id;
    }


    // DELETE // -----------------------
    
    function deleteDraftEntry($record_id) {
        $sql = "DELETE FROM {$this->tbl->entry} WHERE id IN ({$record_id})";
        $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function deleteApprovalLog($record_id) {
        $sql = "DELETE FROM {$this->tbl->draft_workflow} WHERE draft_id IN ({$record_id})";
        $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function deleteAssignees($record_id) {
        $sql = "DELETE FROM {$this->tbl->workflow_to_assignee} WHERE draft_id IN ({$record_id})";
        return $this->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function deleteFileData($record_id) {
        return true;
    }
    
    
    function delete($record_id) {
        $record_id = $this->idToString($record_id);
        // echo '<pre>', print_r($record_id, 1), '</pre>';
        // exit;
        
        $this->deleteDraftEntry($record_id);
        $this->deleteRoleWriteToEntry($record_id);
        $this->deleteDraftToCategory($record_id);
        
        $this->deleteApprovalLog($record_id);
        $this->deleteAssignees($record_id);
        $this->deleteFileData($record_id); // this if for files module
        
        AppSphinxModel::updateAttributes('is_deleted', 1, $record_id, $this->entry_type);
    }
    
    
    // MAIL // -----------------------------
    
    function sendDraftReview($obj, $controller, $comment, $assignees) {

        require_once 'core/common/CommonEntryMailSender.php';

        $params = array('action' => 'approval');
        $vars = $this->setMailVars($obj, $comment, $params, $controller);

        $m = new CommonEntryMailSender();
        return $this->sendMail(array($m, 'sendDraftReview'), array($vars, $assignees));
    }
    
    
    function sendDraftRejectionToSubmitter($obj, $controller, $comment, $submitter_id) {

        require_once 'core/common/CommonEntryMailSender.php';

        $params = array(
            'action' => 'detail', 
            'user_id' => $submitter_id
            );
        $vars = $this->setMailVars($obj, $comment, $params, $controller);

        $m = new CommonEntryMailSender();
        return $this->sendMail(array($m, 'sendDraftRejectionToSubmitter'), array($vars));
    }
    
    
    function sendDraftRejectionToAssignee($obj, $controller, $comment, $approver_id) {

        require_once 'core/common/CommonEntryMailSender.php';
        
        $params = array(
            'action' => 'detail', 
            'user_id' => $approver_id
            );
        $vars = $this->setMailVars($obj, $comment, $params, $controller);

        $m = new CommonEntryMailSender();
        return $this->sendMail(array($m, 'sendDraftRejectionToAssignee'), array($vars));
    }
    
    
    function sendDraftPublication($obj, $controller, $comment, $submitter_id, $entry_id) {

        require_once 'core/common/CommonEntryMailSender.php';

        $cc = &$controller->getClientController();
        $link = $cc->getFolowLink('entry', false, $entry_id);

        $params = array(
            'link' => $link, 
            'user_id' => $submitter_id
            );
        $vars = $this->setMailVars($obj, $comment, $params, $controller);
        
        $m = new CommonEntryMailSender();
        return $this->sendMail(array($m, 'sendDraftPublication'), array($vars));
    }


    function sendMail($function, $args) {
        
        $args['pool'] = $this->mail_use_pool;
        
        // pool
        if($this->mail_use_pool) {
            $sent = call_user_func_array($function, $args);
        
        // direct
        } else {
            $sent = call_user_func_array($function, $args);
            
            // failed, add to pool then
            if(!$sent) {
                $args['pool'] = true;
                $sent = call_user_func_array($function, $args);
            }
        }
        
        return $sent;
    }

    
    function setMailVars($obj, $comment, $params, $controller) {
        
        $vars = $obj->get();
        $vars['comment'] = $comment;
        $vars['user_id'] = (!empty($params['user_id'])) ? $params['user_id'] : 0;
        
        if($this->entry_type == 7) {
            $module = 'knowledgebase';
            $page = 'kb_draft';
        } else {    
            $module = 'file';
            $page = 'file_draft';
        }
        
        $msg = AppMsg::getMsg('common_msg.ini', $module);
        $vars['entry_type'] = strtolower($msg['entry_msg']);
        
        if(!empty($params['link'])) {
            $vars['link'] = $params['link'];
            
        } else {
            $more = array('id' => $vars['id']);
            $vars['link'] = $controller->getRefLink($module, $page, false, $params['action'], $more);
        }
        
        return $vars;
    }

}
?>