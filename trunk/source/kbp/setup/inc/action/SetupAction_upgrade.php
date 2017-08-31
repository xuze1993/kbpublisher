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

$controller->getAction('install');


class SetupAction_upgrade extends SetupAction_install
{

    function &execute($controller, $manager) {
        
        $view = &$controller->getView('install');
        
        if(isset($this->rp->setup)) {
            
            $data = $manager->getSetupData();
            $errors = $this->process($data, $manager);
            
            if($errors) {
                $this->rp->stripVars(true);
                $view->setErrors($errors);
            
            } else {
                $controller->go($controller->getNextStep());
            }
        }
        
        return $view;
    }
    
    
    function process(&$values, $manager, $_key = false) {
        
        require_once 'eleontev/Validator.php';
        
        $v = new Validator($values, false);
                
        $values = $this->parseDirectoryValues($values);    
        $values['tbl_pref'] = ParseSqlFile::getPrefix($values['tbl_pref']);
        
        $key = ($_key) ? $_key : $manager->getSetupData('setup_upgrade');        
        $manager = &SetupModelUpgrade::factory($key);
        
        $ret = $manager->execute($values);
        if($ret !== true) {
            $v->setError($ret, '', '', 'formatted');
            return $v->getErrors();            
        }
    }

}
?>