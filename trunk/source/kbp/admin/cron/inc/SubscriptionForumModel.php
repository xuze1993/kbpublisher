<?php
class SubscriptionForumModel extends SubscriptionCommonModel
{

    var $tbl_pref_custom = 'forum_';
    var $tables = array('entry', 'category', 'entry_to_category', 'message');
    var $custom_tables =  array('user_subscription', 'user');

    var $entry_types = '14';


    function getUserSubscribedCategories($user_id) {

        $sql = "SELECT s.entry_id, s.date_lastsent
            FROM
                {$this->tbl->user_subscription} s
            WHERE s.user_id = %d
            AND s.entry_type = %d";

        $sql = sprintf($sql, $user_id, $this->entry_types);
        $result = $this->db->Execute($sql);
        if ($result) {
            return $result->GetAssoc();
        } else {
            trigger_error($this->db->ErrorMsg());
            return $result;
        }
    }


    function &getCategoryEntries($last_sent, $emanager, $published, $cats, $date_field = 'e.date_updated') {

        // need distinct because one entry could belong to many categories
        $sql = "SELECT DISTINCT
            e.id,
            e.title AS 'title',
            {$date_field} AS 'date',
            IF(date_posted > '{$last_sent}', 1, 0) AS 'new'
        FROM
            ({$emanager->tbl->entry} e,
            {$emanager->tbl->category} cat,
            {$emanager->tbl->entry_to_category} e_to_cat)
        {$emanager->entry_role_sql_from}

        WHERE 1
            AND e.id = e_to_cat.entry_id
            AND cat.id = e_to_cat.category_id
            AND cat.active = 1
            AND e_to_cat.category_id IN(%s)
            AND e.active IN(%s)
            AND {$date_field} > '%s'
            AND %s
            AND {$emanager->entry_role_sql_where}";

        $sql = sprintf($sql, $cats, $published, $last_sent, $emanager->getCategoryRolesSql(false));
        //echo print_r($sql, 1), "\n=============\n";

        $data = false;
        $result = $this->db->SelectLimit($sql, 30, 0);
        if ($result) {
            $data = array(0=>array(), 1=>array());
            while($row = $result->FetchRow()) {
                $data[$row['new']][$row['id']] = array('title'=>$row['title'], 'date'=>$row['date']);
            }
        } else {
            trigger_error($this->db->ErrorMsg());
        }

        return $data;
    }


    // when subscribed to all categories
    function &getAllEntries($last_sent, $emanager, $published, $date_field = 'e.date_updated') {

        // no need in distinct because of AND e.category_id = cat.id
        $sql = "SELECT
            e.id,
            e.title AS 'title',
            {$date_field} AS 'date',
            IF(date_posted > '{$last_sent}', 1, 0) AS 'new'
        FROM
            ({$emanager->tbl->entry} e,
            {$emanager->tbl->category} cat)
        {$emanager->entry_role_sql_from}

        WHERE 1
            AND e.category_id = cat.id
            AND cat.active = 1
            AND e.active IN(%s)
            AND {$date_field} > '%s'
            AND %s
            AND {$emanager->entry_role_sql_where}
            {$emanager->entry_role_sql_group}";

        $sql = sprintf($sql, $published, $last_sent, $emanager->getCategoryRolesSql(false));
        //echo '<pre>', print_r($sql, 1), '</pre>';

        $data = false;
        $result = $this->db->SelectLimit($sql, 30, 0);
        if ($result) {
            $data = array(0=>array(), 1=>array());
            while($row = $result->FetchRow()) {
                $data[$row['new']][$row['id']] = array('title'=>$row['title'], 'date'=>$row['date']);
            }
        } else {
            trigger_error($this->db->ErrorMsg());
        }

        return $data;
    }


    function getLatestEntryDate() {
        $sql = "SELECT MAX(date_posted) AS 'last_date' FROM {$this->tbl->message} WHERE active = 1";
        $result = $this->db->SelectLimit($sql, 1, 0);
        if ($result) {
            return $result->Fields('last_date');
        } else {
            trigger_error($this->db->ErrorMsg());
            return false;
        }
    }
}
?>