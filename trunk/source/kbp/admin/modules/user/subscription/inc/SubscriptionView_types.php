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

class SubscriptionView_types extends AppView
{

    var $tmpl = 'types_list.html';
    var $admin_view = true;


    function execute(&$obj, &$manager) {

        $this->addMsg('user_msg.ini');

        $lview = new SubscriptionView_list();
        $lview->addMsg('user_msg.ini');
        
        $is_all = SubscriptionView_list::getAll($manager);
        $all_rows = SubscriptionView_list::getAllRowsCounts($manager);

        // remove subsc types
        $setting = SettingModel::getQuick(2);
        if(!$setting['module_news']) {
            unset($manager->types[3]);
        }

        if (!BaseModel::isModule('forum')) {
            unset($manager->types[4]);
            unset($manager->types[14]);
        }


        $tpl = new tplTemplatez($this->template_dir . $this->tmpl);
        $tpl->tplAssign('error_msg', AppMsg::errorBox($obj->errors));

        foreach ($manager->types as $type_id => $type_key) {
            $a = array();

            $count_num = (in_array($type_id, $is_all)) ? $this->msg['all_msg']  : $all_rows[$type_id];
            $a['item_count'] = ($count_num === 0) ? 0 : $count_num;
            $a['item_count_ch'] = ($count_num) ? 'checked' : '';
            $a['item_title'] = $lview->getTitle($manager, $type_id);


            if($this->admin_view) {
                $a['item_link'] = $this->getLink('this', 'this', 'this', 'this', array('type'=>$type_id));
            } else {
                $a['item_link'] = $_SERVER['PHP_SELF'] . '?View=member_subsc&type=' . $type_id;
            }

            $msg_tip = $this->msg['subscribe_msg'] . '/' . $this->msg['unsubscribe_msg'];
            $a['tip_msg'] = $msg_tip;
            $a['item_img'] = $this->getImgLink($a['item_link'], 'load', $msg_tip);
            @$a['class'] = ($i++ & 1) ? 'trDarker' : 'trLighter'; // rows colors

            $tpl->tplParse($a, 'row');
        }

        if($this->admin_view) {
            $tpl->tplAssign($this->setCommonFormVars($obj));
            //$tpl->tplAssign($this->setStatusFormVars($obj->get('active')));
        }

        $tpl->tplAssign($obj->get());
        $tpl->tplAssign($this->msg);

        $tpl->tplParse();
        return $tpl->tplPrint(1);
    }

}
?>