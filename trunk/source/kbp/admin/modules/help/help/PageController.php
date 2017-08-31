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

// initialize objects
$rq = new RequestData($_GET, array('id'));
$rp = new RequestData($_POST);

$obj = '';
$manager = new AppModel();

$priv->setCustomAction('check_updates', 'select');
$priv->setCustomAction('ticket', 'select');
//$manager->checkPriv($priv, $controller->action, @$rq->id);


switch ($controller->action) {
case 'check_updates': // ------------------------------
    
    $view = $controller->getView($obj, $manager, 'KBHelpView_updates');
    break;


case 'ticket': // ------------------------------
    
    if(isset($rp->submit)) {
        
        $is_error = $obj->validateTicket($rp->vars);
        
        if($is_error) {
            $rp->stripVars(true);
            $obj->set($rp->vars);
        
        } else {
            $rp->stripVars();
            $obj->set($rp->vars);
            
            $manager->save($obj);
            
            $controller->go();
        }
    }

    $view = $controller->getView($obj, $manager, 'KBHelpView_ticket');

    break;    
    
    
default: // ------------------------------------

    $view = $controller->getView($obj, $manager, 'KBHelpView_index');
}    
    

?>