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
require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryModelBulk.php';

class ForumEntryModelBulk extends KBEntryModelBulk
{

	var $actions = array('forum_move', 'tag', 'status', 'delete');
	var $actions_allowed = array();
	var $actions_allowed_common = array();
	var $bulk_access = 1; // 1 = allowed for full priv only, 2 = also allowed for self priv
	
	
	function setActionsAllowed($manager, $priv, $allowed = array()) {
	
		$actions = array_flip($this->actions);
		
		// here we know we show only self records
		// and $bulk_access = 2 - allowed to use bulk for self records
		if($priv->isPrivConcrete('self_select') && $this->bulk_access == 2) {
			$pref = 'self_';
		} else {
			$pref = '';
		}
		
		if(!$priv->isPrivConcrete($pref . 'delete') || $priv->isPrivStatusActionAny('delete')) {
			unset($actions['delete']);
		}	
		
		if(!$priv->isPrivConcrete($pref . 'status')) {
			unset($actions['status']);
		}				
		
		if(!$priv->isPrivConcrete($pref . 'update') || $priv->isPrivStatusActionAny('update')) {
			unset($actions['type'], $actions['keyword'], $actions['sort_order'],
			      $actions['forum_move'], $actions['category_add'], 
				  $actions['public'], $actions['private']
				  );
		}
				
		if(!$manager->show_bulk_sort) {
			unset($actions['sort_order']);
		}
		
		if($allowed) {
			foreach($actions as $k => $v) {
				if(!in_array($k, $allowed)) {
					unset($actions[$k]);
				}
			}
		}
		
		
		$this->actions_allowed = array_keys($actions);
		//$this->actions_allowed_common = 
		return $this->actions_allowed;		
	}
	
	
	function setCategoryMove($cat, $cat_mirror, $ids) {
		$ids_str = $this->model->idToString($ids);
		$cat_mirror = (is_array($cat_mirror)) ? $cat_mirror : array();
		if($cat) {
			
			$max_sort_values = $this->model->getMaxSortOrderValues($cat, $cat_mirror);
			
			$cat_id = current($cat);
			foreach($ids as $entry_id) {
				$sql = "UPDATE {$this->model->tbl->entry} 
				SET category_id = '{$cat_id}', date_updated = date_updated 
				WHERE id = ($entry_id)";
				$this->model->db->Execute($sql) or die(db_error($sql));				
			}
			
			$this->model->deleteEntryToCategory($ids_str);
			$this->model->saveEntryToCategory($cat, $cat_mirror, $ids, $max_sort_values, false);
		}
	}
	
	
	function setCategoryAdd($cat, $cat_mirror, $ids) {
		$ids_str = $this->model->idToString($ids);
		$cat_mirror = (is_array($cat_mirror)) ? $cat_mirror : array();
		if($cat) {
			$max_sort_values = $this->model->getMaxSortOrderValues($cat, $cat_mirror);
			$this->model->saveEntryToCategory($cat, $cat_mirror, $ids, $max_sort_values, true);
		}
	}		
	
	
	function setPrivate($values, $private, $ids) {
		$ids_str = $this->model->idToString($ids);
		$private = PrivateEntry::getPrivateValue($private);
		
		$this->updateEntryPrivate($private, $ids_str);
		$this->model->deleteRoleReadToEntry($ids_str);
		
		if($values) {
			$rule_id = $this->model->getPrivateRule($private);
			$this->model->saveRoleReadToEntry($values, $ids, $rule_id);
		}
	}
	
	
	function setPublic($ids) {
		$ids = $this->model->idToString($ids);
		$this->updateEntryPrivate(0, $ids);
		$this->model->deleteRoleReadToEntry($ids);
	}
	
	
	function updateEntryPrivate($val, $ids) {
		$sql = "UPDATE {$this->model->tbl->entry} 
		SET private = '{$val}', date_updated = date_updated WHERE id IN ($ids)";
		$this->model->db->Execute($sql) or die(db_error($sql));		
	}
    

	function status($val, $ids) {
		$ids = $this->model->idToString($ids);
		$sql = "UPDATE {$this->model->tbl->entry} 
		SET active = '{$val}', date_updated = date_updated WHERE id IN ($ids)";
		$this->model->db->Execute($sql) or die(db_error($sql));		
	}

    
	function delete($ids) {
		$this->model->delete($ids, true);
		return array_keys($related_ids);
	}
    
    
    function makeSticky($ids, $value) {
        $ids = $this->model->idToString($ids);
        $sql = "UPDATE {$this->model->tbl->entry} 
        SET sticky = '{$value}' WHERE id IN($ids)";
        return $this->model->db->Execute($sql) or die(db_error($sql));
    }
    
    
    function setTags($val, $ids, $action) {
        
        $ids_str = $this->model->idToString($ids);
        
        if($action == 'remove') {
            $this->model->tag_manager->deleteTagToEntry($ids_str);
        
        } elseif($val) {

            // meta keywords
            $tags = $this->model->tag_manager->getTagByIds($this->model->idToString($val));
            $tags = RequestDataUtil::addslashes($tags);
            //$keywords = $this->model->getValuesArray($tags, 'title');
            $keywords = array_values($tags);
            $tag_ids = $val;

            if($action == 'add') {

                $etags = $this->model->tag_manager->getTagToEntry($ids_str);
                $this->model->tag_manager->saveTagToEntry($val, $ids);
                

            } elseif($action == 'set') {
                
                $this->model->tag_manager->deleteTagToEntry($ids_str);
                $this->model->tag_manager->saveTagToEntry($val, $ids);
            }
        }
    }
    	
}
?>