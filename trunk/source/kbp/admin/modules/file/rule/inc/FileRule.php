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

class FileRule extends AppObj
{
    var $error;
    var $properties = array('id'            => NULL,
                            'entry_type'    => 2,    // nor file only
                            'directory'     => '',    
                            'parse_child'   => 1,
                            'is_draft'      => 0,
                            'description'   => '',
                            // 'date_executed'    => '',                                                                                    
                            'entry_obj'     => '',
                            'active'        => 1
                            );
                            

    var $hidden = array('id', 'entry_type');

                            
    function validate($values, $manager) {

        require_once 'eleontev/Validator.php';
        
        $required = array('directory');
        
        $v = new Validator($values, true);
        
        // check for required first, return errors
        $v->required('required_msg', $required);
        if($v->getErrors()) {
            $this->errors =& $v->getErrors();
            return true;
        }
         
        if (!is_readable($values['directory'])) {
            $v->setError('dir_not_readable_msg', 'directory');
            $this->errors =& $v->getErrors();
            return true;
        }
         
        
        $values['directory'] = preg_replace("#[/\\\]+$#", '', trim($values['directory']));
        $values['directory'] = str_replace('\\', '/', $values['directory']) . '/';
        
        $id = (isset($values['id'])) ? $values['id'] : false;
        if($manager->isSubDirectory(addslashes($values['directory']), $id)) { 
            $v->setError('subdir_msg', 'directory');
            $this->errors =& $v->getErrors();
            return true;
        }
        
        if($manager->isDirectoryAdded(addslashes($values['directory']), $id)) {
            //$v->setError(...);
        }
                
    }
}
?>