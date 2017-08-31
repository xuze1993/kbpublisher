<?php
// +---------------------------------------------------------------------------+
// | This file is part of the KBPublisher package                              |
// | KBPublisher - web based knowledgebase publisher tool                      |
// |                                                                           |
// | Author:  Evgeny Leontev <eleontev@gmail.com>                              |
// | Copyright (c) 2005 Evgeny Leontev                                         |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code.                          |
// +---------------------------------------------------------------------------+


class KBClientForumModel extends KBClientModel_common
{

	var $tbl_pref_custom = 'forum_';

	var $custom_tables = array('forum_message',
                               'message'=>'forum_message',
                               'forum_featured',
                               'message_attachment' => 'forum_message_attachment',
							   'file_entry',
							   'file_category',
							   'file_entry_to_category',
							   'user',
							   'user_company',
							   'list_value',
							   'data_to_value'=>'data_to_user_value',
							   'data_to_value_string'=>'data_to_user_value_string',
							   'entry_hits',
                               'tag',
                               'tag_to_entry',
                               'user_subscription');

    var $ban_type = 2; // forum


	// rules id in data to user rule
	var $role_entry_read_id = 111;
	var $role_entry_write_id = 112;
    var $role_category_read_id = 11;
    var $role_category_write_id = 12;

	var $entry_list_id = 8; // id in list statuses
	var $entry_type = 4; // forum entry
	var $entry_type_cat = 14; // entry type for category

    var $session_view_name = 'kb_view_topic_';
    
    // used to display msg for private forums, banned, etc
    var $no_write_reason = false; 



    function getEntryMessages($id = false, $limit = -1, $offset = -1, $order_by = 'm.date_posted') {
		$sql = "SELECT m.*,
			u.username,
			u.first_name,
			u.last_name,
            u2.first_name as updater_first_name,
            u2.last_name as updater_last_name

		FROM {$this->tbl->message} m

		LEFT JOIN {$this->tbl->user} u
        ON m.user_id = u.id
        
        LEFT JOIN {$this->tbl->user} u2
        ON m.updater_id = u2.id

		WHERE %s
			AND m.active = 1

        ORDER BY %s";

        $sql = sprintf($sql, ($id) ? 'm.entry_id = ' . $id : '1', $order_by);

        $result =& $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));
        return $result->GetArray();
    }
    
    
    function getUserPostsCount() {
		$sql = "SELECT COUNT(*) as num

		FROM ({$this->tbl->message} m,
            {$this->tbl->entry} e,
            {$this->tbl->user} u)

		WHERE m.user_id = u.id
            AND m.entry_id = e.id
            AND m.user_id = %d
			AND m.active = 1";

        $sql = sprintf($sql, $this->user_id);

        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('num'); 
    }
    
    
    function getUserPosts($limit = -1, $offset = -1) {
		$sql = "SELECT m.*,
            e.title,
			u.username,
			u.first_name,
			u.last_name

		FROM ({$this->tbl->message} m,
            {$this->tbl->entry} e,
            {$this->tbl->user} u)

		WHERE m.user_id = u.id
            AND m.entry_id = e.id
            AND m.user_id = %d
			AND m.active = 1

        ORDER BY date_posted DESC";

        $sql = sprintf($sql, $this->user_id);

        $result =& $this->db->SelectLimit($sql, $limit, $offset) or die(db_error($sql));
        return $result->GetArray();
    }


    // COUNT // --------------------------

	function countEntriesPerCategory($category_ids) {

		$private_sql = $this->getPrivateSql('count');

		$sql = "SELECT
			e_to_cat.category_id,
			COUNT(*) AS num_entry,
			SUM(posts) AS num_message,
            MAX(last_post_id) AS last_category_post_id
		FROM
			({$this->tbl->entry} e,
			{$this->tbl->entry_to_category} e_to_cat)
		{$this->entry_role_sql_from}

		WHERE 1
			AND e_to_cat.category_id IN($category_ids)
			AND e_to_cat.entry_id = e.id
			AND e.active IN ({$this->entry_published_status})
			AND {$this->entry_role_sql_where}
			AND {$private_sql}
		GROUP BY e_to_cat.category_id";

		$result =& $this->db->Execute($sql) or die(db_error($sql));
        // echo $this->getExplainQuery($this->db, $result->sql);

		return $result->GetAssoc();
	}


	// reassign



	// TOPICS // ------------------------


	// it is for sake of speed on index page
	function getEntriesSqlIndex($body_words = 100, $force_index = '') {

		$sql = "SELECT
			e.*,
			cat.id AS category_id,
			cat.name AS category_name,
			cat.active AS category_active,
			cat.private AS category_private,
			UNIX_TIMESTAMP(e.date_updated) AS ts_updated,
			UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            m.message AS first_message

		FROM
			-- ({$this->tbl->entry} e {$force_index},
			({$this->tbl->entry} e,
			{$this->tbl->category} cat,
            {$this->tbl->forum_message} m
            {$this->sql_params_from})        

		{$this->entry_role_sql_from}

		WHERE 1
			AND e.category_id = cat.id
			AND e.active IN ({$this->entry_published_status})
			AND cat.active = 1
            AND m.id = e.first_post_id
			AND {$this->entry_role_sql_where}
			AND {$this->sql_params}
		{$this->entry_role_sql_group}
		{$this->sql_params_order}";

		// echo "<pre>"; print_r($sql); echo "</pre>";
		return $sql;
	}


	function getEntriesSqlCategory($body_words = 100) {

		$sql = "SELECT
			e.*,
			e_to_cat.sort_order AS real_sort_order,
			cat.id AS category_id,
			cat.name AS category_name,
			cat.active AS category_active,
			cat.private AS category_private,
			UNIX_TIMESTAMP(e.date_updated) AS ts_updated,
			UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            f.id AS is_sticky,
            IFNULL(f.sort_order, 0) AS sort_order_num,
            m.message AS first_message

		FROM
			({$this->tbl->entry} e,
			{$this->tbl->category} cat,
			{$this->tbl->entry_to_category} e_to_cat,
            {$this->tbl->forum_message} m
            {$this->sql_params_from})

		{$this->entry_role_sql_from}

        LEFT JOIN {$this->tbl->forum_featured} f
        ON e.id = f.entry_id
            AND (f.date_to > NOW() OR f.date_to IS NULL)
            AND message_id = 0

		WHERE 1
			AND e_to_cat.entry_id = e.id
			AND e_to_cat.category_id = cat.id
			AND e.active IN ({$this->entry_published_status})
			AND cat.active = 1
            AND m.id = e.first_post_id
			AND {$this->entry_role_sql_where}
			AND {$this->sql_params}
		{$this->entry_role_sql_group}
		{$this->sql_params_order}";

		// echo "<pre>"; print_r($sql); echo "</pre>";
		return $sql;
	}


	// CATEGORIES

    function getCategoriesSql($sort) {
        $sql = "SELECT *
        FROM {$this->tbl->category} c FORCE INDEX ( sort_order )
        WHERE c.active = 1
        ORDER BY {$sort}";

        return $sql;
    }


    function getEntryCategories($id) {
        $sql = "SELECT e.category_id, c.name
        FROM
            ({$this->tbl->entry_to_category} e,
            {$this->tbl->category} c)
        WHERE
            e.category_id = c.id
            AND c.active = 1
            AND e.entry_id = '{$id}'";

        $result =& $this->db->Execute($sql) or die ($this->db->ErrorMsg());
        return $result->GetAssoc();
    }



	function getCategoryType($category_id) {
		return 1;
	}


	function getSortOrder($setting_sort = false) {
		//return 'f.sort_order, e.date_updated';
        return 'sort_order_num = 0, sort_order_num, e.date_updated';
	}
    
    
    function isMessageSticky($entry_id, $message_id) {
        return (bool) $this->getStickyDate($entry_id, $message_id);
    }
    
    
    function getStickyDate($entry_id, $message_id = 0) {
        $sql = "SELECT date_to
            FROM {$this->tbl->forum_featured}
            WHERE entry_id = '{$entry_id}'
            AND message_id = '{$message_id}'";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('date_to');
    }


    // MESSAGE ATTACHMENTS

    function getMessageListAttachment($id) {
        $sql = "SELECT
            a.id,
            a.message_id,
            a.filename,
            a.mime_type
        FROM
            ({$this->tbl->message_attachment} a,
            {$this->tbl->message} m)
        WHERE
            m.entry_id = {$id}
            AND a.message_id = m.id";

        $result =& $this->db->Execute($sql) or die ($this->db->ErrorMsg());
        $data = $result->GetArray();

        $data2 = array();

        foreach ($data as $attach) {
            $data2[$attach['message_id']][] = array('filename' => $attach['filename'],
                                                    'mime_type' => $attach['mime_type'],
                                                    'id' => $attach['id']);
        }

        return $data2;
    }


    function getMessageAttachment($id) {
        $sql = "SELECT
            a.id,
            a.message_id,
            a.filename,
            a.mime_type
        FROM
            {$this->tbl->message_attachment} a
        WHERE
            a.message_id = {$id}";

        $result =& $this->db->Execute($sql) or die ($this->db->ErrorMsg());

        return $result->GetArray();
    }


    function download($id) {
        $data = $this->getAttachmentByIds($id);

        require_once 'HTTP/Download.php';
        PEAR::setErrorHandling(PEAR_ERROR_PRINT);

        session_write_close();
        ini_set('zlib.output_compression', 'Off');

        $params['gzip'] = false;
        $params['data'] = base64_decode($data[0]['filecontent']);
        $params['contenttype'] = $data[0]['mime_type'];


        $h = new HTTP_Download($params);
        //$h->setContentDisposition(HTTP_DOWNLOAD_ATTACHMENT, $data[0]['filename']);
        $h->setContentDisposition(HTTP_DOWNLOAD_INLINE);

        return $h->send();
    }
    
    
    function getAttachmentByIds($ids) {
        $sql = "SELECT * FROM {$this->tbl->message_attachment} WHERE id IN ({$ids})";
        $result =& $this->db->Execute($sql) or die ($this->db->ErrorMsg());
        return $result->GetArray();
    }


    function getEntryLastPoster($ids) {
        $sql = "SELECT u.id, u.username FROM {$this->tbl->user} u WHERE u.id IN ({$ids})";
        $result =& $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    function getStickyMessage($entry_id) {
        $sql = "SELECT m.*,
            u.username,
			u.first_name,
			u.last_name,
            IF(f.id, 1, 0) as is_sticky

        FROM {$this->tbl->message} m

        LEFT JOIN {$this->tbl->user} u
        ON m.user_id = u.id

        LEFT JOIN {$this->tbl->forum_featured} f
        ON f.message_id = m.id

        WHERE m.entry_id IN ({$entry_id})
        ORDER BY m.date_posted
        LIMIT 1";

        $result =& $this->db->Execute($sql) or die(db_error($sql));
        $data = $result->FetchRow();

        return ($data['is_sticky']) ? $data : false;
    }


    // MESSAGES

    function getMessageById($record_id) {
        $sql = "SELECT m.*, u.username
                FROM ({$this->tbl->message} m,
                    {$this->tbl->user} u)
                WHERE m.user_id = u.id
                    #AND {$this->sql_params}
                    AND m.id = %d";
        $sql = sprintf($sql, $record_id);
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->FetchRow();
    }
    
    
    function getMessagesInfo($ids) {
        $sql = "SELECT e.category_id, m.*, e.title, u.username
                FROM ({$this->tbl->message} m,
                    {$this->tbl->entry} e,
                    {$this->tbl->user} u)
                WHERE m.entry_id = e.id
                    AND m.user_id = u.id
                    AND m.id IN (%s)";
        $sql = sprintf($sql, implode(',', $ids));
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    function saveMessage($obj) {
        $sql = ModifySql::getSql('REPLACE', $this->tbl->message, $obj->get());
		$result = $this->db->Execute($sql) or die(db_error($sql));

        $id = $this->db->Insert_ID();
        return $id;
    }


    function updateMessageFieldsForEntry($entry_id, $action = 'add', $first_post_id = false) {

        $latest_message = $this->getTopicLastMessage($entry_id);

        $date_updated = ($latest_message) ? $latest_message['date_posted'] : NULL;
        $date_updated = ModifySql::_getQuoted($date_updated);
        
        $last_post_id = ($latest_message) ? $latest_message['id'] : 0;
        $updater_id = ($latest_message) ? $latest_message['user_id'] : 0;
        $posts_num = ($action == 'add') ? '+ 1' : '- 1';
        
        $first_post_exp = ($first_post_id) ? ', first_post_id = ' . $first_post_id : '';

        $sql = "UPDATE {$this->tbl->entry}
                SET posts = posts %s,
                    date_updated = %s,
                    last_post_id = '%s',
                    updater_id = '%s'
                    %s
                WHERE id = '{$entry_id}'";
        
        $sql = sprintf($sql, $posts_num, $date_updated, $last_post_id, $updater_id, $first_post_exp);
        $result =& $this->db->Execute($sql) or die(db_error($sql));
        return $result;
    }


    function getTopicFirstMessage($entry_id) {
        return $this->_getTopicMessage($entry_id, 'ASC');
    }


    function getTopicLastMessage($entry_id) {
        return $this->_getTopicMessage($entry_id, 'DESC');
    }


    function _getTopicMessage($entry_id, $sort_keyword) {
        $sql = "SELECT * FROM {$this->tbl->message}
            WHERE entry_id = %d AND active = 1
            ORDER BY date_posted %s";
        $sql = sprintf($sql, $entry_id, $sort_keyword);
        $result =& $this->db->SelectLimit($sql, 1, 0) or die(db_error($sql));
        return $result->FetchRow();
    }


    function updateMessageIndex($id, $str) {
        $sql = "UPDATE {$this->tbl->message}
            SET message_index = CONCAT('%s', message_index)
            WHERE id = %s";
        $sql = sprintf($sql, $str, $id);
        $result =& $this->db->Execute($sql) or die(db_error($sql));
        return $result;
    }


    function getMessagePosition($entry_id, $message_id) {
        $sql = "SELECT COUNT(*) AS position
            FROM {$this->tbl->message} m
            WHERE id <= %d
                AND entry_id = %d
                AND active = 1
            ORDER BY id";
        $sql = sprintf($sql, $message_id, $entry_id);

        $result =& $this->db->Execute($sql) or die(db_error($sql));
        return $result->Fields('position');
	}


    function saveAttachment($id, $upload) {

        require_once 'eleontev/SQL/MultiInsert.php';

        $ins = new MultiInsert;
        $data = array();

        foreach ($upload as $file) {
            $content = Uploader::getFileContent($file['tmp_name']);
            $content = addslashes($content);

            $mime_type = $file['type'];
            $name = $file['name'];

            $data[] = array($name, $mime_type, $content);
        }

        $ins->setFields(array('filename', 'mime_type', 'filecontent'), 'message_id');
        $ins->setValues($data, $id);
        $sql = $ins->getSql($this->tbl->message_attachment, 'INSERT');

        $result = &$this->db->Execute($sql) or die(db_error($sql));
        return $this->db->Insert_ID();
    }


    function deleteMessage($id) {
        $sql = "DELETE FROM {$this->tbl->message} WHERE id = '%s'";
        $sql = sprintf($sql, $id);

        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result;
    }


    function deleteAttachment($record_id) {
        $sql = "DELETE FROM {$this->tbl->message_attachment} WHERE message_id IN ({$record_id})";
        return $this->db->Execute($sql) or die(db_error($sql));
    }


    function deleteAttachmentByIds($record_id) {
        $sql = "DELETE FROM {$this->tbl->message_attachment} WHERE id IN ({$record_id})";
        return $this->db->Execute($sql) or die(db_error($sql));
    }


    function featureMessage($entry_id, $message_id) {
        $sql = "INSERT {$this->tbl->forum_featured} VALUES (NULL, %s, %s, 0, 0, 1)";
        $sql = sprintf($sql, $entry_id, $message_id);
        $this->db->Execute($sql) or die(db_error($sql));
    }


    function unfeatureMessage($message_id) {
        $sql = "DELETE FROM {$this->tbl->forum_featured} WHERE message_id = '%s'";
        $sql = sprintf($sql, $message_id);
        $this->db->Execute($sql) or die(db_error($sql));
    }


    function sendFileDownload($data) {
        $params['data'] = $data['filecontent'];
        $params['gzip'] = false;
        $params['contenttype'] = $data['mime_type'];

        return WebUtil::sendFile($params, $data['filename']);
    }


    function upload() {

        require_once 'eleontev/Dir/Uploader.php';

        $upload = new Uploader;
        $upload->store_in_db = true;
        $upload->safe_name = false;
        $upload->safe_name_extensions = array();

        $upload->setAllowedExtension($this->setting['file_allowed_extensions']);
        $upload->setMaxSize($this->setting['file_max_filesize']);

        $f = $upload->upload($_FILES);

        if(isset($f['bad'])) {
            $f['error_msg'] = $upload->errorBox($f['bad']);
        }

        return $f;
    }
    
    
    // PRIVATE && ALLOWED
    
	function getPostActionsAllowedAdmin($topic_id, $category_id) {
	    
		$action = array('update', 'delete');
        $allowed = AuthPriv::getPrivAllowed('forum_entry');
        if($allowed) {
            
            // forum private write
            $cat_private = $this->categories[$category_id]['private'];
            $is_priv = $this->isEntryInUserRoles($category_id, $cat_private, 'category', 'write');

            // topic private write, not used now
            /*if($is_priv) {
                $topic = $this->getEntryData($topic_id);
                $is_priv = $this->isEntryInUserRoles($topic_id, $topic['private'], 'entry', 'write');
            }*/
            
            if(!$is_priv) {
                $allowed = array();
            }
        }
        
        return array_intersect($action, $allowed);
	}


	function getPostActionsAllowedUser($user_id, $messade_time, $skip_action, $add_time = 0) {

		$allowed = array();
		$allowed_time = 300 + $add_time;
		$action = array('update');
		$action = array_diff($action, $skip_action);

		if ($user_id == $this->user_id) {
			 foreach ($action as $v) {
				$t = time() - strtotime($messade_time);
				if($t < $allowed_time) {
					$allowed[] = $v;
                }
            }
		}

		return $allowed;
	}
    
    
    function isTopicAddingAllowed($category_id) {
        
        $in_section = $this->isForumInSection($category_id);
        $cat_status = $this->categories[$category_id]['active'];
        
        $ret = true;
        if ($in_section || $cat_status == 0) {
            $ret = false;
        }
        
        return $ret;
    }
    
    
    function isTopicAddingAllowedByUser($category_id = false) {
        
        $is_priv = true;;
        if(!$this->is_registered) {
            $is_priv = false;
        }
        
        // forum private write
        if($is_priv && $category_id) {
            $private = $this->categories[$category_id]['private'];
            $is_priv = $this->isEntryInUserRoles($category_id, $private, 'category', 'write');
            $this->no_write_reason = (!$is_priv) ? 'private_write' : false;
        }

        // user banned 
        if($is_priv && $this->isUserBanned()) {
            $is_priv = false;
            $this->no_write_reason = (!$is_priv) ? 'user_banned' : false;
        }

        return $is_priv;
    }
    

    // check status, section etc.
    function isPostAllowed($topic_id, $category_id, $entry_status = false) {
        
        $in_section = $this->isForumInSection($category_id);
        $cat_status = $this->categories[$category_id]['active'];
        
        if($entry_status === false) {
            $entry_status = $this->getEntryData($topic_id);
            $entry_status = $entry_status['active'];
        }
        
        $ret = true;
        if ($in_section || $entry_status == 2 || $cat_status == 0) {
            $ret = false;
        }
        
        $this->no_write_reason = ($entry_status == 2) ? 'closed_topic' : false;
        
        return $ret;
    }


    function isPostAllowedByUser($topic_id, $category_id) {
                
        // the same as when adding topic, just make sure it logged 
        // and topic is not private write
        $is_priv = $this->isTopicAddingAllowedByUser($category_id);
        
        // topic private write, not used now
        /*if($is_priv) {
            $topic = $this->getEntryData($topic_id);
            $is_priv = $this->isEntryInUserRoles($topic_id, $topic['private'], 'entry', 'write');
            $this->no_write_reason = (!$is_priv) ? 'private_write' : false;
        }*/

        return $is_priv;
    }
    
    
    function isUserBanned() {
        
        $ban_manager = BanModel::factory('forum');
        
        $user_ip = WebUtil::getIP();
        $params = array('user_id' => $this->user_id, 'ip' => $user_ip);
        
        $date_banned = $ban_manager->isBan($params);
        
        return $date_banned;
    }
    
    
    function isForumInSection($category_id) {
        $use_sections = $this->getSetting('forum_sections');
        $in_section = ($use_sections && $this->categories[$category_id]['parent_id'] == 0);
        return $in_section;
    }
}
?>