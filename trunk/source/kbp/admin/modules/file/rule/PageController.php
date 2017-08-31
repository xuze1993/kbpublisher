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
                          
$controller->loadClass('FileRule');
$controller->loadClass('FileRuleModel');

$controller->loadClass('FileEntry', 'file/entry');
$controller->loadClass('FileEntryModel', 'file/entry');
$controller->loadClass('FileEntryModel_dir', 'file/entry');
$controller->loadClass('FileEntryView_form', 'file/entry');
$controller->loadClass('FileEntryView_common', 'file/entry');

require_once 'eleontev/HTML/DatePicker.php';
require_once 'eleontev/Util/TimeUtil.php';
require_once 'eleontev/Dir/Uploader.php';


// initialize objects
$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);
$controller->rp = &$rp;

$obj = new FileRule;
$manager =& $obj->setManager(new FileRuleModel);

$priv->setCustomAction('dir', 'select');
$priv->setCustomAction('category', 'select');
$priv->setCustomAction('execute', 'update');
$manager->checkPriv($priv, $controller->action, @$rq->id);

$eobj = new FileEntry;       
// $obj_file->set('author_id', null);

$setting = SettingModel::getQuick(1);
$emanager = new FileEntryModel_dir();
$emanager->setFileSetting($setting); 


switch ($controller->action) {
case 'delete': // ------------------------------
     
    $manager->delete($rq->id);
    $controller->go();

    break;
    

case 'status': // ------------------------------
    
    $manager->status($rq->status, $rq->id);
    $controller->go();

    break;
    
    
case 'dir': // ------------------------------

    if(APP_DEMO_MODE) { 
        echo AppMsg::afterActionBox('not_allowed_demo', 'error');
        exit;
    }

    $view = $controller->getView($obj, $manager, 'FileRuleView_dir', $emanager);
    break;


case 'category': // ------------------------------

    $controller->loadClass('FileEntryView_category', 'file/entry');
    $view = new FileEntryView_category;
    $view = $view->execute($obj, $emanager);
    break;

/*
case 'execute': // ------------------------------

    if(APP_DEMO_MODE) {
        $controller->go('not_allowed_demo', true);
    }

    $_SERVER['argv'] = array('--debug', '--wipe');
    require_once APP_ADMIN_DIR . 'cron/cron_common.php';

    ob_start();
    
    $cron = new Cron('daily');
    $cron->skip_log = true; // do not write to table 
    $cron->add('scripts/directory.php', 'spyDirectoryFiles', array($rq->id));
    $cron->run();

    $msg = ob_get_contents();
    
    $msg2 = AppMsg::getAfterActionMsg('execute_result');
    $msg2['body'] = nl2br($msg);
    $_SESSION['msg_']['exexute_result'] = $msg2;

    $controller->go('success', false, 'exexute_result-success');                

    break;
*/

    
case 'update': // ------------------------------
case 'insert': // ------------------------------

    // if(isset($rp->submit) || isset($rp->submit_parse) || isset($rp->submit_back) || isset($rp->submit_confirmed)) {
    if(isset($rp->submit) || isset($rp->submit_test) || isset($rp->submit_back) ) {
        
        if(APP_DEMO_MODE) { 
            $controller->go('not_allowed_demo', true);
        }
        
        
        $is_error = $obj->validate($rp->vars, $manager);
        
        if(!$is_error) {
            $is_error = $eobj->validate($rp->vars, 'rule', $emanager);
            $obj->errors = $eobj->errors;
        }
        
        if($is_error) {
            $rp->stripVars(true);
                        
            $obj->set($rp->vars);
            $obj->set('directory', str_replace('\\', '/', $obj->get('directory')));
            $obj->set('parse_child', (isset($rp->parse_child)) ? 1 : 0);

            // file obj
            $eobj->populate($rp->vars, $emanager, true);
            $eobj->set('active', $rp->file_active);
                                                          
            $author_id = (!empty($rp->vars['author_id'])) ? $rp->vars['author_id'] : null;
            $eobj->set('author_id', $author_id);
            
        } else {
            
            $directory = $rp->directory;
            $directory = preg_replace("#[/\\\]+$#", '', trim($directory));
            $directory = str_replace('\\', '/', $directory) . '/';
            
            $rp->stripVars();
            
            $obj->set($rp->vars);
            $obj->set('directory', $directory);
            $obj->set('parse_child', (isset($rp->parse_child)) ? 1 : 0);             

            // file obj
            $eobj->populate($rp->vars, $emanager);
            $eobj->set('description', '');
            $eobj->set('active', $rp->file_active);

            $author_id = (!empty($rp->vars['author_id'])) ? $rp->vars['author_id'] : null;
            $eobj->set('author_id', $author_id);
            
            // confirm / test screen
            if (isset($rp->submit_test)) {
                $obj->set('entry_obj', $eobj);
                $view = $controller->getView($obj, $manager, 'FileRuleView_confirm', $emanager);
                break;
            }
            
            // save 
            if (!isset($rp->submit_back)) {
                $obj->set('entry_obj', serialize($eobj));
                $entry_id = $manager->save($obj);

                // if(isset($rp->submit_parse)) {
                    // $controller->goPage('this', 'this', false, 'execute', array('id'=>$entry_id));
                // }            
            
                $controller->go();;
            }
            
        }
        
    } elseif($controller->action == 'update') {
        
        $data = $manager->getById($rq->id);
        $rp->setSkipKeys('entry_obj');
        $rp->stripVarsValues($data);
        $obj->set($data);
    
        $eobj = unserialize($data['entry_obj']);
        $rp->stripVarsValues($eobj->properties);
        
        
    } elseif($controller->action == 'insert') {
        
        $eobj->set('author_id', AuthPriv::getUserId());
        
        $status = ListValueModel::getListDefaultEntry('file_status');
        $status = ($status !== null) ? $status : $eobj->get('active');
        $eobj->set('active', $status);
    } 
    

    $file_view = $controller->getView($eobj, $emanager, 'FileRuleView_obj');
    
    $view = $controller->getView($obj, $manager, 'FileRuleView_form', $file_view);

    break;    


default: // ------------------------------------
    
    $view = $controller->getView($obj, $manager, 'FileRuleView_list');
}
?>