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

require_once 'core/app/BulkModel.php';


class KBEntryModelBulk extends BulkModel
{

    var $actions = array('category_move', 'category_add', 'tag',
                         'private', 'public', 'schedule',
                         'rate_reset', 'type', /*'meta_description',*/ 'author',
                         /*'external_link',*/ 'sort_order', 'custom',
                         'status', /*'delete',*/ 'trash');
    
    
    function setActionsAllowed($manager, $priv, $allowed = array()) {
    
        $actions = $this->getActionAllowedCommon($manager, $priv, $allowed);
                
        if(!$manager->show_bulk_sort) {
            unset($actions['sort_order']);
        }
        
        if(!AuthPriv::isAdmin()) {
            unset($actions['author']);
        }
        
        $this->actions_allowed = array_keys($actions);
        return $this->actions_allowed;        
    }
    
    
    function setCategoryMove($cat, $ids) {
        $ids_str = $this->model->idToString($ids);
        if($cat) {
            $max_sort_values = $this->model->getMaxSortOrderValues($cat);
            $cat_id = current($cat);
            foreach($ids as $entry_id) {
                $sql = "UPDATE {$this->model->tbl->entry} 
                SET category_id = '{$cat_id}', date_updated = date_updated 
                WHERE id = ($entry_id)";
                $this->model->db->Execute($sql) or die(db_error($sql));                
            }
            
            $this->model->deleteEntryToCategory($ids_str);
            $this->model->saveEntryToCategory($cat, $ids, $max_sort_values, false);
            
            $category_mva = sprintf('(%s)', implode(',', $cat));
            $this->updateSphinxAttributes('category', $category_mva, $ids_str);
        }
    }
    
    
    function setCategoryAdd($cat, $ids) {
        $ids_str = $this->model->idToString($ids);
        if($cat) {
            $max_sort_values = $this->model->getMaxSortOrderValues($cat);
            $this->model->saveEntryToCategory($cat, $ids, $max_sort_values, true);
            
            $categories = $this->model->getCategoryByIds(implode(',', $ids));
            foreach ($ids as $id) {
                $category_mva = sprintf('(%s)', implode(',', array_keys($categories[$id])));
                $this->updateSphinxAttributes('category', $category_mva, $id);
            }
        }
    }        
    
    
    function setPrivate($values, $private, $ids) {
        $ids_str = $this->model->idToString($ids);
        $private = PrivateEntry::getPrivateValue($private);
        
        $this->updateEntryPrivate($private, $ids_str);
        $this->model->deleteRoleToEntry($ids_str);
        
        $role_read = (!empty($values['role_read'])) ? $values['role_read'] : array();
        $role_write = (!empty($values['role_write'])) ? $values['role_write'] : array();
        $this->model->saveRoleToEntry($private, $role_read, $role_write, $ids);        
        
        $role_read_mva = (empty($role_read)) ? '(0)' : sprintf('(%s)', implode(',', $role_read));
        $role_write_mva = (empty($role_write)) ? '(0)' : sprintf('(%s)', implode(',', $role_write));
        
        $this->updateSphinxAttributes('private', $private, $ids_str);
        $this->updateSphinxAttributes('private_roles_read', $role_read_mva, $ids_str);
        $this->updateSphinxAttributes('private_roles_write', $role_write_mva, $ids_str);
    }
        
    
    function setPublic($ids) {
        $ids_str = $this->model->idToString($ids);
        $this->updateEntryPrivate(0, $ids_str);
        $this->model->deleteRoleToEntry($ids_str);
        
        $this->updateSphinxAttributes('private', 0, $ids_str);
        $this->updateSphinxAttributes('private_roles_read', '(0)', $ids_str);
        $this->updateSphinxAttributes('private_roles_write', '(0)', $ids_str);
    }
    
    
    function updateEntryPrivate($val, $ids) {
        $sql = "UPDATE {$this->model->tbl->entry} 
        SET private = '{$val}', date_updated = date_updated WHERE id IN ($ids)";
        $this->model->db->Execute($sql) or die(db_error($sql));        
    }
    
    
    function setSchedule($values_on, $values, $ids) {
        $ids_str = $this->model->idToString($ids);
        $this->model->deleteSchedule($ids_str, $this->model->entry_type);

        $values_ = array();
        $values_[1]['date'] = DatePicker::sqlDate2($values[1]['date']);
        $values_[1]['st'] = $values[1]['st'];
        $values_[1]['note'] = $values[1]['note'];
        
        if(isset($values_on[2])) {
            $values_[2]['date'] = DatePicker::sqlDate2($values[2]['date']);
            $values_[2]['st'] = $values[2]['st'];
              $values_[2]['note'] = $values[2]['note'];
        }

        if($values_) {
            $this->model->saveSchedule($values_, $ids, $this->model->entry_type);    
        }
    }    
    
    
    function removeSchedule($ids) {
        $ids = $this->model->idToString($ids);
        $this->model->deleteSchedule($ids, $this->model->entry_type);    
    }    
    
    
    function setEntryType($values, $ids) {
        $ids = $this->model->idToString($ids);
        $sql = "UPDATE {$this->model->tbl->entry} 
        SET entry_type = '$values', date_updated = date_updated WHERE id IN($ids)";
        $this->model->db->Execute($sql) or die(db_error($sql));
        
        $this->updateSphinxAttributes('entry_type', $values, $ids);
    }    
    
    
    function setMetaDescription($val, $ids) {
        $ids = $this->model->idToString($ids);
        $sql = "UPDATE {$this->model->tbl->entry} 
        SET meta_description = '{$val}', date_updated = date_updated WHERE id IN ($ids)";
        $this->model->db->Execute($sql) or die(db_error($sql));                
    }
        
    
    function setExternalLink($val, $ids) {
        $ids = $this->model->idToString($ids);
        $sql = "UPDATE {$this->model->tbl->entry} 
        SET external_link = '{$val}', date_updated = date_updated WHERE id IN ($ids)";
        $this->model->db->Execute($sql) or die(db_error($sql));                
    }        
    
    
    function setSortOrder($val, $ids) {
    
        $val_sort = array();
        $val_category = array();
        foreach($val as $k => $v) {
            list($entry_id, $category_id) = explode('_', $k);
            $val_sort[$entry_id] = $v;
            $val_category[$entry_id] = $category_id;
        }
        
        foreach($ids as $entry_id) {
            $sort = $val_sort[$entry_id];
            $category_id = $val_category[$entry_id];
            
            $sql = "UPDATE {$this->model->tbl->entry_to_category} 
            SET sort_order = '{$sort}'
            WHERE entry_id = '{$entry_id}' AND category_id = '{$category_id}'";
            $this->model->db->Execute($sql) or die(db_error($sql));
        }
    }
    
    
    function resetRate($ids) {
        $ids = $this->model->idToString($ids);
        $sql = "DELETE FROM {$this->model->tbl->rating} WHERE entry_id IN ($ids)";
        $this->model->db->Execute($sql) or die(db_error($sql));        
    }
    
    
    function setTags($val, $ids, $action) {
        
        $ids_str = $this->model->idToString($ids);
        
        if($action == 'remove') {
            $this->model->tag_manager->deleteTagToEntry($ids_str);
            $this->setMetaKeywords('', $ids);
        
        } elseif($val) {

            // meta keywords
            $tags = $this->model->tag_manager->getTagByIds($this->model->idToString($val));
            $tags = RequestDataUtil::addslashes($tags);
            //$keywords = $this->model->getValuesArray($tags, 'title');
            $keywords = array_values($tags);
            $tag_ids = $val;

            if($action == 'add') {

                $etags = $this->model->tag_manager->getTagToEntry($ids_str);
                foreach($ids as $entry_id) {
                    if(isset($etags[$entry_id])) {
                        
                        foreach($etags[$entry_id] as $tag_id => $title) {
                            if(in_array($tag_id, $tag_ids)) {
                                unset($etags[$entry_id][$tag_id]);
                            }
                        }
                        
                        $tag_keywords = RequestDataUtil::addslashes($etags[$entry_id]);
                        $tag_keywords = array_merge($tag_keywords, $keywords);
                        
                    } else {
                        $tag_keywords = $keywords;
                    }
                    
                    $this->setMetaKeywords($tag_keywords, $entry_id);
                }

                $this->model->tag_manager->saveTagToEntry($val, $ids);
                

            } elseif($action == 'set') {
                
                $this->model->tag_manager->deleteTagToEntry($ids_str);
                $this->model->tag_manager->saveTagToEntry($val, $ids);            
                $this->setMetaKeywords($keywords, $ids);
            }
        }
    }
    
    
    function setMetaKeywords($val, $ids) {
        $ids = $this->model->idToString($ids);
        $delim = $this->model->tag_manager->getKeywordDelimeter();
        $val = implode($delim, $val);
        
        $sql = "UPDATE {$this->model->tbl->entry} 
        SET meta_keywords = '{$val}', 
        date_updated = date_updated WHERE id IN ($ids)";
        $this->model->db->Execute($sql) or die(db_error($sql));                
    }
    
    
    function setAuthor($author_val, $updater_val, $ids) {
        $ids_str = $this->model->idToString($ids);
        
        $params = array();
        
        if ($author_val) {
            $params[] = 'author_id = ' . $author_val;
        }
        
        if ($updater_val) {
            $params[] = 'updater_id = ' . $updater_val;
        }
        
        if (!empty($params)) {
            $params = implode(', ', $params);
            
            $sql = "UPDATE {$this->model->tbl->entry} 
                SET {$params}, date_updated = date_updated
                WHERE id IN ($ids_str)";
            
            $this->model->db->Execute($sql) or die(db_error($sql)); 
        }               
    }
    
    
    function setCustomData($val, $ids, $form_values) {
        
        $ids_str = $this->model->idToString($ids);
        $field_id = $form_values['custom_field'];
        $cvalues = array();

        if($field_id == 'remove') {
            $this->model->cf_manager->delete($ids_str);
            return;
            
        } elseif($field_id == 'set') {
            $values = $val;
            $this->model->cf_manager->delete($ids_str);
        
        } else {
            $field_id = (int) $field_id;
            $values[$field_id] = (isset($val[$field_id])) ? $val[$field_id] : '';
            
            if(!empty($form_values['custom_append'][$field_id])) {
                $cvalues = $this->model->cf_manager->getCustomDataCurrent($ids_str, $field_id);
            }
            
            $this->model->cf_manager->deleteByEntryIdAndFieldId($ids_str, $field_id);
        }
        
        $this->model->cf_manager->save($values, $ids, $cvalues);
    }


    function status($val, $ids) {
        $ids = $this->model->idToString($ids);
        $sql = "UPDATE {$this->model->tbl->entry} 
        SET active = '{$val}', date_updated = date_updated WHERE id IN ($ids)";
        $this->model->db->Execute($sql) or die(db_error($sql));
        
        $this->updateSphinxAttributes('active', $val, $ids);
    }
    
    
    function delete($ids) {
        
        // to skip enties that have inline links to articles to be deleted
        // $related_ids in array (deleted entry[] = entry that has reference)
        $related_ids = $this->model->getEntryToRelated($this->model->idToString($ids), '2,3', true);
        if($related_ids) {
            $ids = array_diff($ids, array_keys($related_ids));
        }
        
        $this->model->delete($ids, true); // false to skip sort updating  ???
        return array_keys($related_ids);
    }
    
    
    function trash($ids) {
        
        // to skip enties that have inline links to articles to be deleted
        // $related_ids in array (deleted entry[] = entry that has reference)
        $related_ids = $this->model->getEntryToRelated($this->model->idToString($ids), '2,3', true);
        if($related_ids) {
            $ids = array_diff($ids, array_keys($related_ids));
        }
        
        if (!empty($ids)) {
            $this->model->setSqlParams(sprintf('AND e.id IN(%s)', $this->model->idToString($ids)));
            $rows = $this->model->getRecords();
            
            $data = array();
            foreach ($rows as $row) {
                $obj = new KBEntry;
                $obj->collect($row['id'], $row, $this->model, 'save');
                
                $data[] = array($row['id'], addslashes(serialize($obj)));
            }
            
            $sql = MultiInsert::get("INSERT IGNORE {$this->model->tbl->entry_trash}
                                    (entry_id, entry_obj, entry_type, user_id)
                                    VALUES ?", $data, array($this->model->entry_type, AuthPriv::getUserId()));
            
            $this->model->db->Execute($sql) or die(db_error($sql));
            
            $this->model->delete($ids, true);
        }

        return array_keys($related_ids);
    }
    
    
    function addSphinxRebuildTask($entry_type) {
        $rule_id = array_search('sphinx_index', $this->model->entry_task_rules);
        
        $sql = "REPLACE {$this->model->tbl->entry_task} (rule_id, entry_type, entry_id) VALUES (%d, %d, 0)";
        $sql = sprintf($sql, $rule_id, $entry_type);
        
        $this->model->db->Execute($sql) or die(db_error($sql));
    }
}
?>