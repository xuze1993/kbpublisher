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


class KBEntryHistoryModel extends AppModel
{

    var $tbl_pref_custom = 'kb_';
    var $tables = array('table'=>'entry_history', 'entry_history', 'entry');
    var $custom_tables = array('user');


    var $id_field = 'entry_id';
    var $fields = array('body');


    function getHistoryById($entry_id, $revision_num) {
        $this->setSqlParams(sprintf("AND entry_id = %d", $entry_id), null, true);
        $this->setSqlParams(sprintf("AND revision_num = %d", $revision_num));
        $sql = $this->getRecordsSql($entry_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }


    function getRecordsSql() {
        $sql = "SELECT h.*, u.first_name, u.last_name, u.middle_name, u.username, u.email
        FROM {$this->tbl->table} h
        LEFT JOIN {$this->tbl->user} u ON h.entry_updater_id = u.id
        WHERE {$this->sql_params}
        {$this->sql_params_order}";

        return $sql;
    }


    function getCountRecordsSql() {
        $sql = "SELECT COUNT(*) AS num FROM {$this->tbl->table} h WHERE {$this->sql_params}";
        return $sql;
    }


    function getUserById($user_id) {
        $sql = "SELECT * FROM {$this->tbl->user} WHERE id = '%d'";
        $sql = sprintf($sql, $user_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }


    // HISTORY // -------------------------

    function stripvars(&$data, $server = 'addslashes') {
    //function &stripvars($data, $server = 'addslashes') {
        $rd = new RequestData($data);
        $rd->setHtmlValues('body');
        $rd->setCurlyBracesValues('body');
        $rd->stripVars($server);

        return $data;
    }


    function &getVersionData($entry_id, $revision_num) {
        $sql = "SELECT entry_data FROM {$this->tbl->entry_history} WHERE entry_id = %d AND revision_num = %d";
        $sql = sprintf($sql, $entry_id, $revision_num);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $data = $result->Fields('entry_data');

        if(!$data) {
            return false;
        }

        $data = unserialize($data);
        return $data;
    }


    function getEntryMaxVersion($entry_id) {
        $sql = "SELECT MAX(revision_num) AS 'max_num' FROM {$this->tbl->entry_history} WHERE entry_id = %d";
        $sql = sprintf($sql, $entry_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return ($res = $result->Fields('max_num')) ? $res : 0;
    }


    function addRevision($entry_id, $entry_data, $user_id, $data = array()) {

        $revision_num = $this->getEntryMaxVersion($entry_id) + 1;
        $user_id = (int) $user_id;
        $entry_data = addslashes(serialize($entry_data));
        $entry_updater_id = $data['entry_updater_id'];
        $entry_date_updated = $data['entry_date_updated'];
        $comment = (isset($data['comment'])) ? $data['comment'] : '';

        $sql = "INSERT {$this->tbl->entry_history} SET
        entry_id = '$entry_id',
        revision_num = '$revision_num',
        user_id = '$user_id',
        comment = '$comment',
        entry_data = '$entry_data',
        entry_updater_id = '$entry_updater_id',
        entry_date_updated = '$entry_date_updated'";
        
        return $this->db->Execute($sql) or die(db_error($sql));
    }


    function &compare($new_data, $old_data) {

        $history_data = array();
        $fields = array_flip($this->fields);
        $new_data = array_intersect_key($new_data, $fields);
        $old_data = array_intersect_key($old_data, $fields);

        if($new_data != $old_data) {
            $history_data = &$old_data;
        }

        // echo '<pre>old_data: ', print_r($old_data, 1), '</pre>';
        // echo '<pre>new data: ', print_r($new_data, 1), '</pre>';
        // echo '<pre>history_data: ', print_r($history_data, 1), '</pre>';
        //exit;

        return $history_data;
    }


    function &parseVersionData($data) {

        $fields = array_flip($this->fields);
        $data = array_intersect_key($data, $fields);

        return $data;
    }


    // DOWNLOAD // -------------------------

    function sendFileDownload($live_rev, $selected_rev, $live_rev_num) {

        $filename_str = '%d_rev_%d_%s%s.html';
        $filename_archive = 'history_%d_rev_%d-%d.zip';

        $entry_id = $live_rev['id'];
        $rev_num = $selected_rev['revision_num'];

        $filename = sprintf($filename_str, $entry_id, $rev_num, $selected_rev['date_posted'], '');
        $filename_live = sprintf($filename_str, $entry_id, $live_rev_num, $live_rev['date_updated'], '_live');
        $filename_archive = sprintf($filename_archive, $entry_id, $rev_num, $live_rev_num);

        $filename = str_replace(array(' ', ':'), '_', $filename);
        $filename_live = str_replace(array(' ', ':'), '_', $filename_live);


        $data1 = $live_rev['body'];
        $data2 = unserialize($selected_rev['entry_data']);

        $zip = new ZipArchive();

        if ($zip->open(APP_CACHE_DIR . $filename_archive, ZIPARCHIVE::CREATE) !== true) {
            echo 'ERROR create zip archive!';
            exit;
        }

        $zip->addFromString($filename_live, $data1);
        $zip->addFromString($filename, $data2['body']);

        $zip->close();

        $file = APP_CACHE_DIR . $filename_archive;
        $params['data'] = file_get_contents($file);
        $params['gzip'] = false;
        $params['contenttype'] = 'application/zip';

        @unlink($file);

        return WebUtil::sendFile($params, $filename_archive);
    }


    function sendFileDownloadHtml($data) {
        $params['data'] = $data['body'];
        $params['gzip'] = false;
        $params['contenttype'] = 'text/html';

        $filename = '%d_rev_%d_%s.html';
        $filename = sprintf($filename, $data['id'], $data['revision_num'], $data['date_posted']);
        $filename = str_replace(array(' ', ':'), '_', $filename);

        return WebUtil::sendFile($params, $filename);
    }


    static function getHistoryAllowedRevisions($ehmax = false) {
        if($ehmax === false) {
            $reg =& Registry::instance();
            $setting  = &$reg->getEntry('setting');
            $ehmax = $setting['entry_history_max'];
        }

        if(strtolower($ehmax) == 'all') {
            $ret = true;
        } else {
            $ret = (int) $ehmax;
        }

        return $ret;
    }


    // DELETE RELATED // ----------------------------


    function removeExtraRevisions($entry_id, $allowed_rev) {

        if($allowed_rev !== true) { // true means unlimited
            $num_rev = $this->countEntryRevisions($entry_id);
            if($num_rev > $allowed_rev) {
                $remove = $num_rev - $allowed_rev;
                $this->deletExtraRevisions($entry_id, $remove);
            }
        }
    }


    function countEntryRevisions($entry_id) {
        $sql = "SELECT COUNT(*) AS num FROM {$this->tbl->table} WHERE entry_id = %d";
        $sql = sprintf($sql, $entry_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num');
    }


    function deletExtraRevisions($entry_id, $limit) {
        $sql = "DELETE FROM {$this->tbl->table} WHERE entry_id = %d
        ORDER BY date_posted LIMIT %d";
        $sql = sprintf($sql, $entry_id, $limit);
        // echo '<pre>', print_r($sql, 1), '</pre>';
        // exit;
        $ret =  $this->db->Execute($sql) or die(db_error($sql));
        // echo '<pre>', print_r($ret->GetArray(), 1), '</pre>';
        // exit;
    }


    function deleteRevision($entry_id, $revision_num) {
        $sql = "DELETE FROM {$this->tbl->table} WHERE entry_id = %d AND revision_num = %d";
        $sql = sprintf($sql, $entry_id, $revision_num);
        return $this->db->Execute($sql) or die(db_error($sql));
    }


    function deleteRevisionAll($entry_id) {
        $sql = "DELETE FROM {$this->tbl->table} WHERE entry_id = %d";
        $sql = sprintf($sql, $entry_id);
        return $this->db->Execute($sql) or die(db_error($sql));
    }

}
?>