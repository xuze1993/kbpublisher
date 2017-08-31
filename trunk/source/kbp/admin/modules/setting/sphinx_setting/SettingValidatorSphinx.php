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


class SettingValidatorSphinx
{

    function validateConnection($values) {

        $sphinx = SphinxModel::connect(false, $values);

        $ret = ($sphinx) ? true : AppMsg::getMsgs('error_msg.ini', 'sphinx_setting', 'sphinx_connect');
        
        return $ret;
    }
}
?>