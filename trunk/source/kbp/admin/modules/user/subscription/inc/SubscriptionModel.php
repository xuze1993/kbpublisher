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


class SubscriptionModel extends AppModel
{

    var $tbl_pref_custom = '';
    var $tables = array('table'=>'user_subscription',
                        'files' => 'file_entry',
                        'news' => 'news',
                        'category_files' => 'file_category',
                        'category_articles' => 'kb_category',
                        'articles' => 'kb_entry',
                        'category' => 'kb_category',
                        'entry_to_category' => 'kb_entry_to_category',
                        'forum_topics' => 'forum_entry',
                        'forums' => 'forum_category',
                        'entry_trash');


    var $types = array(
        '3'   => 'news',
        '1'   => 'articles',
        '11'  => 'articles_cat',
        '31'  => 'comments',
        '2'   => 'files',
        '12'  => 'files_cat',
        '4'   => 'topics',
        '14'  => 'forums');


    function getArticleManager() {
        require_once APP_MODULE_DIR . 'knowledgebase/entry/inc/KBEntryModel.php';
        return new KBEntryModel(false, 'read');
    }


    function getFileManager() {
        require_once APP_MODULE_DIR . 'file/entry/inc/FileEntryModel.php';
        return new FileEntryModel(false, 'read');
    }

    function getForumManager() {
        require_once APP_MODULE_DIR . 'forum/entry/inc/ForumEntryModel.php';
        return new ForumEntryModel(false, 'read');
    }


    function getRowsByIds($record_id) {
        $sql = "SELECT t.id AS id2, t.* FROM {$this->tbl->table} t
        WHERE id IN ($record_id)
        {$this->sql_params_order}";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    // changed 18 June, 2014as entry could be in trash
    // but subscription not deleted
    function getRowsCount($user_id) {
        $sql = "SELECT t.entry_type, COUNT(*) AS num
        FROM {$this->tbl->table} t
        LEFT JOIN {$this->tbl->entry_trash} th
            ON th.entry_id = t.entry_id
            AND IF(t.entry_type = 31, th.entry_type=1, th.entry_type=t.entry_type)
        WHERE t.user_id = '{$user_id}'
        AND th.id IS NULL
        GROUP BY t.entry_type";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }



    function getSubscription($entry_type, $user_id) {
        $sql = "SELECT entry_id, entry_id AS 'eid'
        FROM {$this->tbl->table}
        WHERE entry_type = '{$entry_type}' AND user_id = '{$user_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    function parseCategories($cats, $type) {

        $m = ($type == 11) ? $this->getArticleManager()
                             : $this->getFileManager();

        $c = &$m->cat_manager->getChildCategories(false, $cats);

        $ret['remove'] = array();
        foreach($c as $parent_id => $v) {
            foreach($v as $child_id) {
                if(isset($c[$child_id])) {
                    $ret['remove'][$child_id] = $child_id;
                }
            }
        }

        $ret['add'] = array_diff($cats, $ret['remove']);

        return $ret;
    }


    function saveSubscription($values, $entry_type, $user_id) {

        require_once 'eleontev/SQL/MultiInsert.php';

        $values2 = array();
        $values = (is_array($values)) ? $values : array($values);
        $entry_type = (is_array($entry_type)) ? $entry_type : array($entry_type);
        $user_id = (is_array($user_id)) ? $user_id : array($user_id);

        foreach($entry_type as $type) {
            foreach($values as $entry_id) {
                foreach($user_id as $_user_id) {
                    $values2[$entry_id . $type . $_user_id] = array ($entry_id, $type, $_user_id);
                }
            }
        }

        if($values2) {
            $ins = new MultiInsert;
            $ins->setFields(array('entry_id', 'entry_type', 'user_id'), array('date_subscribed', 'date_lastsent'));
            $ins->setValues($values2, array('NOW()', 'NOW()'));
            $sql = $ins->getSql($this->tbl->table, 'INSERT IGNORE');

            //echo '<pre>', print_r($sql, 1), '</pre>';
            //exit;

            return $this->db->Execute($sql) or die(db_error($sql));
        }
    }


    function updateDateLastsent($entry_id, $entry_type, $user_id) {
        $sql = "UPDATE {$this->tbl->table} SET date_lastsent = NOW()
        WHERE  entry_id IN ({$entry_id})
        AND entry_type = '{$entry_type}'
        AND user_id = '{$user_id}'";

        return $this->db->Execute($sql) or die(db_error($sql));
    }


    function deleteByUserId($user_id) {
        $sql = "DELETE FROM {$this->tbl->table} WHERE user_id = '{$user_id}'";
        return $this->db->Execute($sql) or die(db_error($sql));
    }


    function deleteByEntryType($entry_type, $user_id) {
        $sql = "DELETE FROM {$this->tbl->table}
        WHERE entry_type IN ({$entry_type})
        AND user_id = '{$user_id}'";

        return $this->db->Execute($sql) or die(db_error($sql));
    }


    function deleteSubscription($entry_id, $entry_type, $user_id) {
        $entry_id = (is_array($entry_id)) ? implode(',', $entry_id) : $entry_id;
        $sql = "DELETE FROM {$this->tbl->table}
        WHERE  entry_id IN ({$entry_id})
        AND entry_type = '{$entry_type}'
        AND user_id = '{$user_id}'";

        return $this->db->Execute($sql) or die(db_error($sql));
    }
}
?>