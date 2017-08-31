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


class TrashAction
{
	
	function factory($type) {
		$class = 'TrashAction_' . $type;
		return new $class;
	}
}



class TrashAction_article extends TrashAction
{
    
    function __construct() {
        require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntry.php';
        require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryModel.php';        
    }
    
    
    function restore($entry_obj) {
        
        $id = $entry_obj->get('id');
        
        $manager = new KBEntryModel;
        
        $category_ids = implode(',', $entry_obj->getCategory());
        $manager->cat_manager->setSqlParams(sprintf('AND c.id IN (%s)', $category_ids));
        $categories = $manager->cat_manager->getRecords();
        
        if (empty($categories)) {
            return false;
        }
        
        $entry_obj->setCategory(array_keys($categories));
        $entry_obj->set('body_index', $entry_obj->get('body'));
        
        $sort_values = $manager->updateSortOrder($id,
                                                 $entry_obj->getSortValues(),
                                                 $entry_obj->getCategory(),
                                                 'insert');
        // checking related entries
        $related_saved = $entry_obj->getRelated();
        if (!empty($related_saved)) {
            $related_ids = implode(',', array_keys($related_saved));
            $manager->setSqlParams(sprintf('AND e.id IN (%s)', $related_ids));
            $related_actual = $manager->getRecords();
            
            $related = array();
            foreach ($related_actual as $v) {
                $related[$v['id']] = $related_saved[$v['id']];
            }
            
            $entry_obj->setRelated($related);
        }
        
        
        // checking roles
        $role_read = $entry_obj->getRoleRead();
        $role_write = $entry_obj->getRoleWrite();
        $all_roles = $role_read + $role_write;
        
        if (!empty($all_roles)) {
            $role_ids = implode(',', array_keys($all_roles));
            
            $manager->role_manager->setSqlParams(sprintf('AND r.id IN (%s)', $role_ids));
            $roles_actual = $manager->role_manager->getRecords();
            
            foreach (array_keys($role_read) as $k) {
                $role_id = $role_read[$k];
                if (empty($roles_actual[$role_id])) {
                     unset($role_read[$k]);
                }
            }
            
            $entry_obj->setRoleRead($role_read);
            
            foreach (array_keys($role_write) as $k) {
                $role_id = $role_write[$k];
                if (empty($roles_actual[$role_id])) {
                     unset($role_write[$k]);
                }
            }
            
            $entry_obj->setRoleWrite($role_write);
        }
        
        
        $manager->add($entry_obj);
        $manager->saveEntryToCategory($entry_obj->getCategory(), $id, $sort_values);
        $manager->saveRelatedToEntry($entry_obj, $id);
        $manager->saveAttachmentToEntry($entry_obj, $id);
        
        
        $schedule = $entry_obj->getSchedule();
        foreach (array_keys($schedule) as $k) {
            $schedule[$k]['date'] = date('YmdHi00', $schedule[$k]['date']);
        }
        $manager->saveSchedule($schedule, $id);
        
        if($entry_obj->get('private')) {
            $manager->saveRoleToEntryObj($entry_obj, $id);
        }
        
        $manager->tag_manager->saveTagToEntry($entry_obj->getTag(), $id);
        $manager->cf_manager->save($entry_obj->getCustom(), $id);
        
        AppSphinxModel::updateAttributes('is_deleted', 0, $id, $manager->entry_type);
        
        return true;
    }
    
    
    function getPreview($entry_obj, $controller) {
        
        $entry_obj = unserialize($entry_obj);
        $manager = new KBEntryModel;
        
        $controller->loadClass('KBEntryView_preview', 'knowledgebase/entry');
        $view = new KBEntryView_preview;        
        $view = $view->execute($entry_obj, $manager);
        
        return $view;
    }       


    function deleteOnTrashEmpty() {
        $manager = new KBEntryModel;
        $manager->deleteOnTrashEmpty();
    }       

}

?>