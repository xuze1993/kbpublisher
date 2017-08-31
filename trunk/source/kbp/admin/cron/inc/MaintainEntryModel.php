<?php

require_once 'eleontev/SQL/MultiInsert.php';
require_once APP_MODULE_DIR . 'tool/tag/inc/TagModel.php';


class MaintainEntryModel extends AppModel
{
    
    var $tbl_pref_custom = '';
    var $tables = array(
        'kb_entry', 'file_entry', 'news',
        'kb_entry_history', 'entry_task', 'entry_schedule', 
        'entry_lock', 'entry_autosave',
        'tag', 'tag_to_entry', 'tag_to_entry_update',
        'entry_draft', 'entry_draft_workflow_history', 'entry_featured',
        'forum_message_attachment');
    

    // HISTORY // -----------------

    /**
     * Get records if more than $revisions for one entry
     *
     * @return db resultset ($result)
     */
    function &getHistoryEntries($revisions, $limit = 200) {
        $sql = "SELECT entry_id, COUNT(*) AS 'num_revisions' FROM {$this->tbl->kb_entry_history}
            GROUP BY entry_id
            HAVING num_revisions > %d";
        $sql = sprintf($sql, $revisions);
        $result = $this->db->SelectLimit($sql, $limit, 0);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;    
    }


    /**
     * Remove earlier records from entry history
     *
     * @return bool Result of query execution.
     */
    function deleteHistoryEntryLimit($entry_id, $num_rows_delete) {
        $sql = "DELETE FROM {$this->tbl->kb_entry_history}
            WHERE entry_id = %d
            ORDER BY date_posted ASC";
        $sql = sprintf($sql, $entry_id);
        $result = $this->db->SelectLimit($sql, $num_rows_delete);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }


    /**
     * Remove records from entry history if no article
     *
     * @return bool Result of query execution.
     */
    function deleteHistoryEntryNoArticle() {    
        $sql = "DELETE eh FROM {$this->tbl->kb_entry_history} eh 
        LEFT JOIN {$this->tbl->kb_entry} e ON eh.entry_id = e.id 
        WHERE e.id IS NULL;";    
        $sql = sprintf($sql, $entry_id);
        $result = $this->db->Execute($sql);

        if ($result) {
            $result = $this->db->Affected_Rows();
        } else {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }


    // TAGS // ---------------------

    function getTagsToEntry($entry_id, $entry_type) {
        $sql = "SELECT t.id, t.title 
        FROM {$this->tbl->tag} t, {$this->tbl->tag_to_entry} te
        WHERE te.entry_id = %d AND t.id = te.tag_id AND te.entry_type = %d";
        $sql = sprintf($sql, $entry_id, $entry_type);
        $result = $this->db->Execute($sql);
        
        if ($result) {
            $result = $result->GetAssoc();
        } else {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }
    

    function getEntryIdToTag($tag_id, $entry_type) {
        $sql = "SELECT DISTINCT(entry_id) AS entry_id
        FROM {$this->tbl->tag_to_entry}
        WHERE tag_id = '{$tag_id}' AND entry_type = '{$entry_type}'";
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }
    
    
    function getEntryIdToTagAll($entry_type) {
        $sql = "SELECT DISTINCT(entry_id) AS entry_id
        FROM {$this->tbl->tag_to_entry}
        WHERE entry_type = '{$entry_type}'";
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }
        

    function setMetaKeyword($entry_id, $entry_type, $keywords) {
        $table = $this->record_type_to_table[$entry_type];
        
        $sql = "UPDATE {$this->tbl->$table} SET 
        meta_keywords = '%s',
        date_updated=date_updated
        WHERE id = %d";
        $sql = sprintf($sql, $keywords, $entry_id);
        $result = $this->db->Execute($sql);
        if ($result) {
            $result = $this->db->Affected_Rows();
        } else {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }
    

    function getTagIdByTitle($title) {
        $sql = "SELECT id FROM {$this->tbl->tag} WHERE title = '{$title}'";
        $result = $this->db->Execute($sql);
        if($result) {
            $result = $result->Fields('id');
        } else {
            trigger_error($this->db->ErrorMsg());
        }

        return $result;
    }
    
    
    function addTag($title, $visible = 1) {
        $sql = "INSERT {$this->tbl->tag} SET title = '{$title}', active = '{$visible}'";
        $result = $this->db->Execute($sql);
        if($result) {
            $result = $this->db->Insert_ID();
        } else {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }
    
    
    function addTagsToEntry($tag_to_entry_array) {
        $sql = "INSERT IGNORE {$this->tbl->tag_to_entry} (tag_id, entry_id, entry_type) VALUES ?";
        $sql = MultiInsert::get($sql, $tag_to_entry_array);
        $result =& $this->db->Execute($sql);
        if(!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }
    
    // when tag deleted from Tag module, it is not deleted from entry_to_tag 
    function deleteEmptyTagToEntry($rule_id) {
        $sql = "DELETE e_to_tag 
        FROM {$this->tbl->tag_to_entry} e_to_tag, {$this->tbl->entry_task} etask
        WHERE etask.rule_id = %d
        AND etask.entry_id = e_to_tag.tag_id
        -- value1 = 1 means tag deleted
        AND etask.value1 = 1
        --  active = 0 successfully parsed
        AND etask.active = 0";
        $sql = sprintf($sql, $rule_id);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }   
    

    // BODY INDEX // ----------------
    
    function getBody($entry_id, $entry_type) {
        $table = $this->record_type_to_table[$entry_type];
        $sql = "SELECT body FROM {$this->tbl->$table} WHERE id = '{$entry_id}'";
        $result = $this->db->Execute($sql);
        if ($result) {
            $result = $result->Fields('body');
        } else {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }
    
    
    function updateBodyIndex($entry_id, $entry_type, $body_index) {
        $table = $this->record_type_to_table[$entry_type];
        $sql = "UPDATE {$this->tbl->$table} SET 
        body_index = '%s', date_updated=date_updated
        WHERE id = %d";
        $sql = sprintf($sql, $body_index, $entry_id);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }
    

    function getEntryBodyIndex($entry_type) {
        $table = $this->record_type_to_table[$entry_type];
        $sql = "SELECT 
            id AS 'entry_id', 
            '{$entry_type}' AS 'entry_type',
            body 
        FROM {$this->tbl->$table}";
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }


    // OTHER // --------------------
    
    /**
     * Remove locked status of old entries.
     *
     * @param int $older Older than $older seconds.
     */
    function unlockEntries($older) {
        $sql = "DELETE FROM {$this->tbl->entry_lock}
            WHERE date_locked < DATE_SUB(NOW(), INTERVAL $older SECOND)";
        $result = $this->db->Execute($sql);
        if ($result) {
            return true;
        } else {
            trigger_error($this->db->ErrorMsg());
            return false;
        }
    }
    
    
    function deleteExpiredForumAttachments() {
        $sql = "DELETE FROM {$this->tbl->forum_message_attachment}
                WHERE message_id = 0
                    AND HOUR(TIMEDIFF(date_uploaded, NOW())) >= 1;";    
        $sql = sprintf($sql, $entry_id);
        $result = $this->db->Execute($sql);

        if ($result) {
            $result = $this->db->Affected_Rows();
        } else {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }
    


    // ENTRY TASK // --------------------

    /**
     * Get records from entry task table.
     *
     * @param int $rule_id.
     * @param int (optional) $entry_type.
     *
     * @return db resultset ($result)
     */    
    function getEntryTasksResult($rule_id, $entry_type = false) {
        $sql = "SELECT * FROM {$this->tbl->entry_task} WHERE rule_id IN (%s) AND active = 1";
        $sql .= ($entry_type) ? sprintf(' AND entry_type = %d', $entry_type) : ''; 
        $sql = sprintf($sql, $rule_id);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;    
    }
    
    
    // function getEntryTasks($rule_id, $entry_type = false) {
    //     $result = $this->getEntryTasksResult($rule_id, $entry_type);
    //     if(!$result) {
    //         return $result;
    //     }
    //
    //     return $result->FetchRow();
    // }
    
    
    function getEntryTask($rule_id, $entry_type = false) {
        $result = $this->getEntryTasksResult($rule_id, $entry_type);
        if(!$result) {
            return $result;
        }
        
        $row = $result->FetchRow();
        return ($row) ? $row : array();
    }
    
    
    function getEntryTasks($rule_ids, $entry_type = false) {
		$rule_ids = is_array($rule_ids) ? implode(',', $rule_ids) : $rule_ids; 
        $result = $this->getEntryTasksResult($rule_ids, $entry_type);
        if(!$result) {
            return $result;
        }
        
        $row = $result->GetAssoc();
        return ($row) ? $row : array();
    }
    
    
    /**
     * Remove records from entry temporary table.
     *
     * @param int $status. 1/0
     * @param int $rule_id.
     * @param int $entry_id.
     * @param int (optional) $entry_type.
     */    
    function statusEntryTask($status, $rule_id, $entry_id, $entry_type = false) {
        $sql = "UPDATE {$this->tbl->entry_task} 
            SET active = %d
            WHERE rule_id = %d AND entry_id = %d";
        $sql .= ($entry_type) ? sprintf(' AND entry_type = %d', $entry_type) : '';
        $sql = sprintf($sql, $status, $rule_id, $entry_id, $entry_type);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;       
    }


    /**
     * Remove records from entry temporary table.
     * 
     * @param int $rule_id.
     */    
    function removeEntryTasks($rule_id) {
        $sql = "DELETE FROM {$this->tbl->entry_task} WHERE rule_id = %d AND active = 0";
        $sql = sprintf($sql, $rule_id);
        $result = $this->db->Execute($sql);
        if (!$result) {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;        
    }
    
    
    // DRAFTS/AUTOSAVE // --------------------------------------
    
    
    /**
     * Remove old autosaved entries.
     *
     * @param int $older Older than $older seconds.
     */
    function freshEntryAutosave($older) {
        $sql = "DELETE FROM {$this->tbl->entry_autosave}
            WHERE date_saved < DATE_SUB(NOW(), INTERVAL $older SECOND)";
        $result = $this->db->Execute($sql);
        if ($result) {
            $result = $this->db->Affected_Rows();
        } else {
            trigger_error($this->db->ErrorMsg());
            return false;
        }
    }
    
    
    /**
     * Remove records from drafts if no article/file
     *
     * @return bool Result of query execution.
     */
    function deleteDraftNoEntry($entry_type) {
        
        $table = $this->record_type_to_table[$entry_type];
            
        $sql = "DELETE d FROM {$this->tbl->entry_draft} d 
        LEFT JOIN {$this->tbl->$table} e ON d.entry_id = e.id
        WHERE d.entry_type = %d 
        AND d.entry_id != 0 
        AND e.id IS NULL;";    
        $sql = sprintf($sql, $entry_type);
        $result = $this->db->Execute($sql);

        if ($result) {
            $result = $this->db->Affected_Rows();
        } else {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }

    
    /**
     * Remove records from drafts workflow if no article/file
     *
     * @return bool Result of query execution.
     */
    function deleteWorkflowHistoryNoEntry($entry_type) {
        
        $table = $this->record_type_to_table[$entry_type];
            
        $sql = "DELETE d FROM {$this->tbl->entry_draft_workflow_history} d 
        LEFT JOIN {$this->tbl->$table} e ON d.entry_id = e.id
        WHERE d.entry_type = %d 
        AND d.entry_id != 0 
        AND e.id IS NULL;";   
        $sql = sprintf($sql, $entry_type);
        $result = $this->db->Execute($sql);

        if ($result) {
            $result = $this->db->Affected_Rows();
        } else {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }
  
  
    /**
     * Remove records from featured if no article/file
     *
     * @return bool Result of query execution.
     */
    function deleteFeaturedNoEntry($entry_type) {
        
        $table = $this->record_type_to_table[$entry_type];
            
        $sql = "DELETE d FROM {$this->tbl->entry_featured} d 
        LEFT JOIN {$this->tbl->$table} e ON d.entry_id = e.id
        WHERE d.entry_type = %d 
        AND d.entry_id != 0 
        AND e.id IS NULL;";   
        $sql = sprintf($sql, $entry_type);
        $result = $this->db->Execute($sql);

        if ($result) {
            $result = $this->db->Affected_Rows();
        } else {
            trigger_error($this->db->ErrorMsg());
        }
        return $result;
    }
  
}
?>