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

require_once $controller->working_dir . 'inc/Company.php';
require_once $controller->working_dir . 'inc/CompanyModel.php';


// initialize objects
$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);

$obj = new Company;

$manager =& $obj->setManager(new CompanyModel());
$manager->checkPriv($priv, $controller->action, @$rq->id);


switch ($controller->action) {
case 'delete': // ------------------------------
    
    if($manager->isCompanyInUse($rq->id)) {
        $controller->go('notdeleteable_entry', true);
    }
    
    $manager->delete($rq->id);
    $controller->go();

    break;
    

case 'status': // ------------------------------
    
    $manager->status($rq->status, $rq->id);
    $controller->go();

    break;
    
    
case 'update': // ------------------------------
case 'insert': // ------------------------------
    
    if(isset($rp->submit)) {
        
        $is_error = $obj->validate($rp->vars);
        
        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);
        
        } else {
            $rp->stripVars();
            $obj->set($rp->vars);
        
            $manager->save($obj, $controller->action);
            
            $controller->go();
        }
        
    } elseif($controller->action == 'update') {
    
        $data = $manager->getById($rq->id);
        $rp->stripVarsValues($data, true);
        $obj->set($data);
    }
    
    $view = $controller->getView($obj, $manager, 'CompanyView_form');

    break;


default: // ------------------------------------
    
    $view = $controller->getView($obj, $manager, 'CompanyView_list');
}
?>