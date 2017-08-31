<?php
include_once 'inc/DataConsistencyModel.php';

// set private attributes to child catefories,
// child categories can't has lower atrributes than parent 
function inheritCategoryPrivateAttributes() {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    
    $updated = 0;
    $entry_types = array('11', '12', '14'); // article, files, forum
    
    if(!BaseModel::isModule('forum')) {
        $entry_types = array('11', '12');
    }
    
    foreach ($entry_types as $v) {
        
        $emanager = DataConsistencyModel::getCategoryManager('admin', $v);
        $emanager->error_die = false;
        $categories = &$emanager->getSelectRecords();
        if(!$categories) {
            continue;
        }
        
        $tree = $emanager->getTreeHelperArray($categories);
        
        $ids = $emanager->getValuesString($categories, 'id');
        $roles_read = $emanager->getRoleReadById($ids, 'id_list');
        $roles_write = $emanager->getRoleWriteById($ids, 'id_list');
        
        $data = array();
        foreach ($tree as $cat_id => $level) {
            $data[$level][] = $cat_id;
        }
        
        $max_level = max(array_keys($data));
        
        for ($i = 1; $i <= $max_level; $i ++) {
            foreach ($data[$i] as $cat_id) {
                $cat_private = $categories[$cat_id]['private'];
                
                $parent_id = $categories[$cat_id]['parent_id'];
                $parent_private = $categories[$parent_id]['private'];
                
                $need_change = false;
                
                // check for read/write
                if ($parent_private != $cat_private && $parent_private != 0 && $cat_private != 1) {
                    
                    if ($parent_private == 1) {
                        $cat_private = 1;
                    }
                    
                    if ($parent_private == 2) {
                        $cat_private = ($cat_private == 3) ? 1 : 2;
                    }
                    
                    if ($parent_private == 3) {
                        $cat_private = ($cat_private == 2) ? 1 : 3;
                    }
                    
                    $need_change = true;
                }
                
                
                // handling roles
                $cat_roles_read = (!empty($roles_read[$cat_id])) ? $roles_read[$cat_id] : array();
                if (isset($roles_read[$parent_id])) { // check for parents' roles
                    foreach ($roles_read[$parent_id] as $role_id) {
                        if (!in_array($role_id, $cat_roles_read)) {
                            $need_change = true;
                            $cat_roles_read[] = $role_id; // add a role
                        }   
                    }
                }
                
                $cat_roles_write = (!empty($roles_write[$cat_id])) ? $roles_write[$cat_id] : array();
                if (isset($roles_write[$parent_id])) { // check for parents' roles
                    foreach ($roles_write[$parent_id] as $role_id) {
                        if (!in_array($role_id, $cat_roles_write)) {
                            $need_change = true;
                            $cat_roles_write[] = $role_id; // add a role
                        }   
                    }
                }

                
                if ($need_change) { // update a category
                    $emanager->setPrivate($cat_private, $cat_id);
                    $categories[$cat_id]['private'] = $cat_private;
                    
                    $emanager->deleteRoleToCategory($cat_id);
                    $emanager->saveRoleToCategory($cat_private, $cat_roles_read, $cat_roles_write, $cat_id);
                    
                    $roles_read[$cat_id] = $cat_roles_read;
                    $roles_write[$cat_id] = $cat_roles_write;
                    
                    $updated ++;
                }
            }
        }
    }
    
    $cron->logNotify('%d category(ies) have been updated.', $updated);
    
    return $exitcode;
}



// set not active to child catefories if parent not active
function inheritCategoryNotActiveStatus() {
    $exitcode = 1;

    $reg =& Registry::instance();
    $cron =& $reg->getEntry('cron');
    
    $updated = 0;
    $entry_types = array('11', '12', '14'); // article, files, forum
    
    if(!BaseModel::isModule('forum')) {
        $entry_types = array('11', '12');
    }
    
    foreach ($entry_types as $v) {
        
        $emanager = DataConsistencyModel::getCategoryManager('admin', $v);
        $emanager->error_die = false;
        $categories = &$emanager->getSelectRecords();
        if(!$categories) {
            continue;
        }
        
        $tree = $emanager->getTreeHelperArray($categories);
        
        $data = array();
        foreach ($tree as $cat_id => $level) {
            $data[$level][] = $cat_id;
        }
        
        $max_level = max(array_keys($data));
        
        for ($i = 1; $i <= $max_level; $i ++) {
            foreach ($data[$i] as $cat_id) {
                
                $child_active = $categories[$cat_id]['active'];
                $parent_id = $categories[$cat_id]['parent_id'];
                
                $parent_cat_id = $categories[$parent_id]['id'];
                $parent_active = $categories[$parent_id]['active'];
                                
                // parent not active but current active 
                if($parent_active == 0 && $child_active == 1) {
                    
                    // echo 'child_id: ', print_r($cat_id, 1), "\n";
                    // echo 'child_active: ', print_r($child_active, 1), "\n";
                    // echo '--------------------------', "\n";
                    //
                    // echo 'parent_cat_id: ', print_r($parent_cat_id, 1), "\n";
                    // echo 'parent_active: ', print_r($parent_active, 1), "\n";
                    // echo '===========================', "\n";

                    $ret = $emanager->statusChild(0, $parent_cat_id);
                    if ($ret === false) {
                        $exitcode = 0;
                    } else {
                        $updated += $ret;
                    }
                }
            }
        }
    }
    
    $cron->logNotify('%d category(ies) have been updated.', $updated);
    
    return $exitcode;
}

?>