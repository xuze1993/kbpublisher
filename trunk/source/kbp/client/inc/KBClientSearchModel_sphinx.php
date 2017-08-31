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

require_once 'core/base/SphinxModel.php';


class KBClientSearchModel_sphinx extends KBClientSearchModel
{
    
    var $sphinx;
    var $smanager;
    
    
    function __construct(&$values, $manager) {
        parent::__construct($values, $manager);
        
        $this->smanager = new SphinxModel(true);
        $this->sphinx = $this->smanager->sphinx;
    }
    
    
    function _getSphinxSearchData($limit, $offset) {
        $rows = $this->smanager->getRecords($limit, $offset);
        
        if (empty($rows)) {
            return false;
        }
        
        $data = array();
        if (is_array($rows)) {
            foreach ($rows as $v) {
                $data[$v['entry_id']] = $v['score'];
    		}
        }
        
        $count = $this->smanager->getCountRecords();
        
        return array($count, $data);
    }
    
    
    function _getSearchData($method, $manager, $limit, $offset) {
        list($count, $sphinx_result) = $this->_getSphinxSearchData($limit, $offset);
        
        if (empty($sphinx_result)) {
            return array(0, array());
        }
        
        $rows = $this->_getSearchRows($sphinx_result, $method, $manager);
        
        return array($count, $rows);
    }
    
    
    function _getSearchRows($sphinx_result, $method, $manager) {
        $ids = array_keys($sphinx_result);
        $sql = $this->$method($ids, $manager);
        
        $result = $this->db->Execute($sql) or die(db_error($sql));
        $_rows = $result->GetAssoc();
        
        $rows = array();
        foreach ($ids as $id) {
            if (!empty($_rows[$id])) {
                $_rows[$id]['score'] = $sphinx_result[$id];
                $rows[] = $_rows[$id];
            }
        }
        
        return $rows;
    }
    
    
    // ARTICLE // ------------------
    
    function getArticlesSql($ids, $manager) {
        $ids = implode(',', $ids);
        
        $sql = "
        SELECT e.id AS 'id_',
            e.id AS 'id',
            e.title,
            e.body,
            e.private,
            e.url_title,
            e.entry_type,
            e.hits,
            cat.id AS category_id,
            cat.name AS category_name,
            cat.private AS category_private,
            cat.commentable,
            cat.ratingable,        
            UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            UNIX_TIMESTAMP(e.date_updated) AS ts_updated,
            e.meta_keywords
            
            FROM ({$manager->tbl->entry} e, 
                {$manager->tbl->category} cat,
                {$manager->tbl->entry_to_category} e_to_cat)
            
            WHERE e.id IN ({$ids})
                AND e_to_cat.entry_id = e.id
                AND e_to_cat.category_id = cat.id
                AND cat.active = 1
                
            GROUP BY e.id
            ";
            
        //echo "<pre>"; print_r($sql); echo "</pre>";
        //echo '<pre>', print_r('==============================', 1), '</pre>';
        return $sql;    
    }
    
    
    function getArticleSearchData($limit, $offset, $by = false, $manager = false) {
        if($by == 'attachment') {
            return $this->_getAttachmentSearchData($limit, $offset, $manager);
            
        } else {
            return $this->_getSearchData('getArticlesSql', $this, $limit, $offset);
        }
    }
    
    
    function _getAttachmentSearchData($limit, $offset, $manager) {
        
        $_limit = 100;
        $sphinx_rows = $this->smanager->getRecords($_limit, 0); // all files from sphinx
        $rows = array();
        
        if (!empty($sphinx_rows)) {
            $data = array();
            if (is_array($sphinx_rows)) {
                foreach ($sphinx_rows as $v) {
                    $article_ids = explode(',', $v['article']);
                    foreach ($article_ids as $article_id) {
                        if (empty($data[$article_id])) {
                            $data[$article_id] = $v['score'];
                        }
                    }
                }
            }
            
            $ids = array_keys($data);
            $filtered_ids = $this->_filterArticles($ids, $manager); // actual articles
            
            $count = count($filtered_ids);
            
            
            $chunk_size = 1000;
            $chunks = array_chunk($filtered_ids, $chunk_size);
            
            $_rows = array();
            foreach ($chunks as $chunk) {
                $sql = $this->getArticlesSql($chunk, $manager);
            
                $result = $this->db->Execute($sql) or die(db_error($sql));
                $_rows += $result->GetAssoc();
            }
            
            $_offset = 0;
            foreach ($ids as $id) {
                if (!empty($_rows[$id])) {
                    if ($_offset < $offset) {
                        $_offset ++;
                        continue;
                    }
                    
                    $_rows[$id]['score'] = $data[$id];
                    $rows[] = $_rows[$id];
                    
                    $_offset ++;
                    if ($_offset == $offset + $limit) {
                        break;
                    }
                }
            }

        } else {
            $count = 0;
        }
        
        return array($count, $rows);
    }


    function _filterArticles($ids, $manager) {
        $entry_sql = $manager->getPrivateSql(false);
        $cat_sql = $manager->getCategoryRolesSql(false);
        
        $sql_params = 'AND %s AND %s';
        $sql_params = sprintf($sql_params, $entry_sql, $cat_sql);
            
        $ids = implode(',', $ids);
        
        $sql = "SELECT e.id
            
            FROM ({$manager->tbl->entry} e, 
                {$manager->tbl->category} cat,
                {$manager->tbl->entry_to_category} e_to_cat)
            
            WHERE e.id IN ({$ids})
                {$sql_params}
                AND e_to_cat.entry_id = e.id
                AND e_to_cat.category_id = cat.id
                AND cat.active = 1
                
            GROUP BY e.id";
            
        $result = $this->db->Execute($sql) or die(db_error($sql));
        
        $data = array();
        while($row = $result->FetchRow()) {
            $data[] = $row['id'];
        }
        return $data;
    }
    
    
    // FILE // --------------------
    
    function getFilesSql($ids, $manager) {
        $ids = implode(',', $ids);
        
        $sql = "
        SELECT e.id AS 'id_',
            e.id,
            e.title,
            e.description,
            e.filename,
            e.filesize,
            e.private,
            e.downloads,
            cat.id AS category_id,
            cat.name AS category_name,
            cat.private AS category_private,
            UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            UNIX_TIMESTAMP(e.date_updated) AS ts_updated,
            e.meta_keywords
            
            FROM ({$manager->tbl->entry} e, 
                {$manager->tbl->category} cat,
                {$manager->tbl->entry_to_category} e_to_cat)
            
            WHERE e.id IN ({$ids})
                AND e_to_cat.entry_id = e.id
                AND e_to_cat.category_id = cat.id
                AND cat.active = 1
                
            GROUP BY e.id
            ";
            
        //echo "<pre>"; print_r($sql); echo "</pre>";
        //echo '<pre>', print_r('==============================', 1), '</pre>';
        return $sql;    
    }
    
    
    function getFileSearchData($limit, $offset, $manager) {
        return $this->_getSearchData('getFilesSql', $manager, $limit, $offset);
    }

    
    
    // NEWS // --------------------------
    
    function getNewsSql($ids) {
        $ids = implode(',', $ids);
        
        $sql = "
        SELECT 
            e.id AS 'id_',
            e.*,
            YEAR(e.date_posted) AS 'category_id',
            0 AS 'category_private',
            UNIX_TIMESTAMP(e.date_posted) AS ts_posted,
            UNIX_TIMESTAMP(e.date_posted) AS ts_updated,
            e.date_posted AS date_updated
        
        FROM 
            ({$this->tbl->news} e)
            
        WHERE e.id IN ({$ids})";
        
        //echo "<pre>"; print_r($sql); echo "</pre>";
        //echo '<pre>', print_r('==============================', 1), '</pre>';
        return $sql;
    }
    
    
    function getNewsSearchData($limit, $offset, $manager) {
        return $this->_getSearchData('getNewsSql', $manager, $limit, $offset);
    }
    
    
    // FORUM // ------------------------------------
    
    
    
    // ALL // --------------------------------------
    
    function getAllSearchData($limit, $offset, $managers) {
        
        $rows = array();
        $count = array();
        
        $this->smanager->setSqlParamsSelect('source_id');
        $this->smanager->setSqlParamsFrom($this->smanager->idx->client);
        $_rows = $this->smanager->getRecords($limit, $offset);
        
        $data = array();
        if (is_array($_rows)) {
            foreach ($_rows as $v) {
                $data[$v['source_id']][$v['entry_id']] = $v['score'];
    		}
        }
        
        if (!empty($data[1])) {
            $rows['article'] = $this->_getSearchRows($data[1], 'getArticlesSql', $this);
        }
        
        if (!empty($data[2])) {
            $rows['file'] = $this->_getSearchRows($data[2], 'getFilesSql', $managers['file']);
        }
        
        if (!empty($data[3])) {
            $rows['news'] = $this->_getSearchRows($data[3], 'getNewsSql', $managers['news']);
        }
        
        
        // count
        if (!empty($data)) {
            $this->smanager->setSqlParamsSelect('COUNT(*) as count, source_id');
            $this->smanager->sql_params_group = 'GROUP BY source_id';
            $this->smanager->match_check = false; 
            $count_rows = $this->smanager->getRecords(5, 0);
            
            if (is_array($count_rows)) {
                foreach ($count_rows as $v) {
                    $entry_type = $this->record_type[$v['source_id']];
                    $count[$entry_type] = $v['count'];
        		}
            }
        }
        
        return array($count, $rows);
    }
    
    
    // GENERATOR // ------------------------------------
    
    function setCategoryStatusParams($managers) {
        
        $single_source = !is_array($managers);
        
        if ($single_source) {
            $managers = array($managers);
        }
        
        $select = array();
        $select_str = 'IF(%sLENGTH(visible_category), 1, 0)';
        $select_no_cat_str = 'IF(source_id = %d, 1, 0)';
        
        foreach($managers as $manager) {
            if($manager->categories) {
                $source_param = ($single_source) ? '' : sprintf('source_id = %d AND ', $manager->entry_type);
                $select[] = sprintf($select_str, $source_param);
                
            } elseif (!$single_source) {
                
                $select[] = sprintf($select_no_cat_str, $manager->entry_type);                
            }
        }
        
        if (!empty($select)) {
            $select = implode(' + ', $select) . ' as _category_active';
            $this->smanager->setSqlParamsSelect($select);
            
            $where = 'AND _category_active = 1';
            $this->smanager->setSqlParams($where);
        }
    }
    
    
    function setStatusParams($managers) {
        
        $single_source = !is_array($managers);
        
        if ($single_source) {
            $managers = array($managers);
        }
        
        $select = array();
        $select_str = 'IF(%sIN(active, %s), 1, 0)';
        foreach($managers as $manager) {
            $source_param = ($single_source) ? '' : sprintf('source_id = %d AND ', $manager->entry_type);
            
            $statuses = $manager->getEntryPublishedStatusRaw($manager->entry_list_id);
            $statuses = (!empty($statuses)) ? array_values($statuses) : array(1);
            
            $select[] = sprintf($select_str, $source_param, implode(',', $statuses));
        }
        
        $select = implode(' + ', $select) . ' as _status';
        $this->smanager->setSqlParamsSelect($select);
        
        $where = 'AND _status  = 1';
        $this->smanager->setSqlParams($where);
    }
    
    
    function filterById($ids, $entry_type) {
        
        $source_id = array_search($entry_type, $this->record_type);
        
        if ($source_id) {
            $this->smanager->setSourceParams($source_id);
        }
        
        $where = sprintf("AND entry_id IN (%s)", implode(',', $ids));
        $this->smanager->setSqlParams($where);
    }
    
    
    function filterByTitle($str) {
        $this->smanager->setSqlParamsMatch("@title $str");
    }
    
    
    function filterByTag($tag_ids) {
        $tag_ids = ($tag_ids) ? implode(',', $tag_ids) : 0;
        $where = sprintf("AND tag IN (%s)", $tag_ids);
        $this->smanager->setSqlParams($where);
    }
    
    
    function filterByAuthor($ids) {
        $where = sprintf("AND author_id IN (%s)", implode(',', $ids));
        $this->smanager->setSqlParams($where);
    }
    
    
    function filterArticleByAllFields($str) {
        $this->smanager->setSqlParamsMatch("$str");
    }
    
    
    function filterFileByAllFields($str) {
        $this->smanager->setSqlParamsMatch("$str");
    }
    
    
    function filterByFilename($str) {
        $this->smanager->setSqlParamsMatch("@title $str");
    }
    
    function filterByArticleAttachments($str) {
        $this->smanager->setSqlParamsMatch("$str");
        $this->smanager->setIndexParams('file', true);
        
        $where = 'AND active = 1';
        $this->smanager->setSqlParams($where);
        
        $select = 'article, LENGTH(article) as _att_num';
        $this->smanager->setSqlParamsSelect($select);
        
        $where = 'AND _att_num > 0';
        $this->smanager->setSqlParams($where);
    }
    
    function filterNewsByAllFields($str) {
        $this->smanager->setSqlParamsMatch("$str");
    }
    
    
    function filterForumByAllFields($str, $topic_id, $manager) {
    }
    
    
    function filterEmpty($entry_type) {
    }
    
    
    function filterByEntryType($c) {
        $where = sprintf("AND entry_type IN (%s)", implode(',', $c));
        $this->smanager->setSqlParams($where);
    }
    
    
    function filterByCategory($c) {
        $where = sprintf("AND category IN (%s)", implode(',', $c));
        $this->smanager->setSqlParams($where);
    }
    
    
    function filterByCustomDate($entry_type) {
        
        if(isset($this->values['date_from'])) {
            $min = $this->values['date_from'];
            $min = strtotime($min);
        }
        
        if(isset($this->values['date_to'])) {
            $max = $this->values['date_to'];
            $max = strtotime($max);    
        }
        
        $attribute = $this->_getFilterDateAttribute($entry_type);
        
        if(!empty($max) && !empty($min)) {
            $where = "AND $attribute BETWEEN {$min} AND {$max}";
        
        } elseif(!empty($min)) {
            $where = "AND $attribute >= {$min}";
        
        } elseif(!empty($max)) {
            $where = "AND $attribute <= {$max}";
        }
        
        $this->smanager->setSqlParams($where);
    }
    
    
    function filterByDate($entry_type, $match) {
        $attribute = $this->_getFilterDateAttribute($entry_type);
        
        $min = strtotime(sprintf('-%s %s', $match[1], $match[2]));
        $where = "AND $attribute >= {$min}";
        $this->smanager->setSqlParams($where);
    }
    
    
    function _getFilterDateAttribute($entry_type) {
        // added or updated
        @$attribute = ($this->values['pv'] == 'u') ? 'date_updated' : 'date_posted';
        return $attribute;
    }
    
    
    function setEntryRolesParams($manager) {
        
        if($manager->isUserPrivIgnorePrivate()) {
            return;
        }
        
        $user_role_ids = array();
        
        if($manager->user_role_id) {
            $user_role_ids = $manager->getUserRolesIdsChildByUserId($manager->user_role_id);        
        }
        
        $user_role_ids[] = 0; // no roles
        
        $where = sprintf("AND private_roles_read IN (%s)", implode(',', $user_role_ids));
        $this->smanager->setSqlParams($where);
    }
    
    
    function setPrivateParams($manager) {
        if($manager->getSetting('private_policy') == 1 && !$manager->is_registered) {
            $private = $manager->private_rule['read']; // 1,3
            $where = sprintf("AND private NOT IN (%s)", implode(',', $private));
            $this->smanager->setSqlParams($where);
            
            $where = 'AND category_readable = 1';
            $this->smanager->setSqlParams($where);
        }
    }
    
    
    function setCategoryRolesParams($managers) {
        
        if (!is_array($managers)) {
            $managers = array($managers);
        }
        
        $select = array();
        $select_str = 'IF(source_id = %d AND IN(category, %s), 1, 0)';
        $select_no_cat_str = 'IF(source_id = %d, 1, 0)';
        
        $cats_to_skip = false;
        
        foreach($managers as $manager) {
            if($manager->role_skip_categories) {
                $allowed_categories = array_keys($manager->categories);
                $select[] = sprintf($select_str, $manager->entry_type, implode(',', $allowed_categories));
                
                $cats_to_skip = true;
            } else {
                $select[] = sprintf($select_no_cat_str, $manager->entry_type);
            }
        }
        
        if ($cats_to_skip) {
            $select = implode(' + ', $select) . ' as _category_roles';
            $this->smanager->setSqlParamsSelect($select);
            
            $where = 'AND _category_roles = 1';
            $this->smanager->setSqlParams($where);
        }
    }
    
    
    function setCustomFieldParams($manager) {
        
        $v = RequestDataUtil::stripVars($this->values['custom']);
        $custom_sql = $this->cf_manager->getCustomFieldSphinxQL($v);
        
        if ($custom_sql['where']) {
            $this->smanager->setSqlParamsSelect($custom_sql['select']);
            $this->smanager->setSqlParams('AND ' . $custom_sql['where']);
        }
        
        if ($custom_sql['match']) {
            $this->smanager->setSqlParamsMatch($custom_sql['match']);
        }
    }
    
    
    function setOrderParams($values, $entry_type) {
        
        $val = 'ORDER BY date_updated DESC';
        if($entry_type == 'news') {
            $val = 'ORDER BY date_posted DESC';
        }
        
        if(KBClientSearchHelper::isOrderByScore($values)) {
            $val = 'ORDER BY score DESC';
        }
        
        $this->smanager->setSqlParamsOrder($val);
    }
    
    
    // HIGHLIGHTING // ------------------
    
    function highlightTitle($str, $query, $keywords) {
        
        if(!$query) {
            return $str;
        }
        
        $str = RequestDataUtil::stripVars($str);
        
        $_sql = $this->_getSnippetSql();  
        $query = $this->smanager->sql_params_match;
        
        $sql = sprintf($_sql, $str, $query, 1000); // no limit
        $result = $this->sphinx->Execute($sql);
        $snippet = $result->GetArray();
        
        if(!empty($snippet[0]['snippet'])) {
            $str = $snippet[0]['snippet'];
            
        } else {
            $str = DocumentParser::getTitleSearch($str, implode(' ', $keywords));
        }
        
        return $str;
    }
    
    
    function highlightBody($str, $query, $keywords, $limit) {
        
        if(!$limit ) {
            return;
        }

        if(!$query) {
            return $str;
        }
        
        $str = RequestDataUtil::stripVars($str);
        DocumentParser::parseCurlyBracesSimple($str);
        $str = DocumentParser::stripHTML($str);
        
        $_sql = $this->_getSnippetSql();  
        $query = $this->smanager->sql_params_match;
        
        $sql = sprintf($_sql, $str, $query, $limit);
        $result = $this->sphinx->Execute($sql) or die(DBUtil::error($sql, false, $this->sphinx));
        $snippet = $result->GetArray();
        
        if(!empty($snippet[0]['snippet'])) {
            $str = $snippet[0]['snippet'];
            
        } else {
            $str = RequestDataUtil::stripslashes($str);
            $str = DocumentParser::getSummarySearch($str, implode(' ', $keywords), $limit);
        }
        
        return $str;
    }
    
    
    function _getSnippetSql() {
        $idx = SphinxModel::setIndexNames();
        $sql = "CALL SNIPPETS('%s', '{$idx->kbpArticleIndex_main}', '%s',
            1 as query_mode,
            '<span class=\"highlightSearch\">' as before_match,
            '</span>' as after_match,
            %d as limit,
            1 as allow_empty)";
        
        return $sql;
    }
    
    
    
/*
    function highlight($title, $body, $query, $summary_limit, $keywords) {
        
        if ($query && $summary_limit) {
            $title = RequestDataUtil::stripVars($title);
            
            $body = RequestDataUtil::stripVars($body);
            DocumentParser::parseCurlyBracesSimple($body);
            $body = DocumentParser::stripHTML($body);
            
            $title_match = false;
            $body_match = false;
            
            $idx = SphinxModel::setIndexNames();
            
            $_sql = "CALL SNIPPETS('%s', '{$idx->kbpArticleIndex_main}', '%s',
                1 as query_mode,
                '<span class=\"highlightSearch\">' as before_match,
                '</span>' as after_match,
                %d as limit,
                1 as allow_empty)";
                
            //$query = SphinxModel::getSphinxString($query);
            $query = $this->smanager->sql_params_match;
            
            // title
            $sql = sprintf($_sql, $title, $query, 1000); // no limit
            $result = $this->sphinx->Execute($sql);
            $snippet = $result->GetArray();
            
            if(!empty($snippet[0]['snippet'])) {
                $title = $snippet[0]['snippet'];
                $title_match = true;
                
            } else {
                $title = RequestDataUtil::stripslashes($title);
            }
            
            // body
            $sql = sprintf($_sql, $body, $query, $summary_limit);
            $result = $this->sphinx->Execute($sql) or die(DBUtil::error($sql, false, $this->sphinx));
            $snippet = $result->GetArray();
            
            if(!empty($snippet[0]['snippet'])) {
                $body = $snippet[0]['snippet'];
                $body_match = true;
                
            } else {
                $body = RequestDataUtil::stripslashes($body);
            }
            
            if (!$title_match && !$body_match) { // no matches at all
                $title = DocumentParser::getTitleSearch($title, implode(' ', $keywords));
                $body = DocumentParser::getSummarySearch($body, implode(' ', $keywords), $summary_limit);
                
            } elseif (!$body_match) {
                $body = DocumentParser::getSummarySearch($body, implode(' ', $keywords), $summary_limit);
            }
            
        } elseif ($summary_limit == 0) {
            $body = '';
        }
        
        $ret = array();
        $ret['title'] = $title;
        $ret['body'] = $body;
        
        return $ret;
    }*/
    
    
    function getKeywords() {
		$index_name = sprintf('%skbpBaseIndex', SphinxModel::getSphinxPrefix());
        $sql = "CALL KEYWORDS('%s', '%s')";
        $query = $this->smanager->sql_params_match;
        
        $sql = sprintf($sql, $query, $index_name);
        $result = $this->sphinx->Execute($sql) or die(DBUtil::error($sql, false, $this->sphinx));
		
        $rows = $result->GetArray();
        $keywords = array();
        foreach ($rows as $v) {
            $keywords[] = $v['tokenized'];
            $keywords[] = $v['normalized'];
        }
        
        $keywords = array_unique($keywords);
        return $keywords;
    }
    
    
    // GLOSSARY, TAGS // ------------------
    
    static function parseFilterSql($sphinx_sql, $options) {
        
        $bp = PageByPage::factory('form', $options['limit'], $_GET);
            
        $smanager = new SphinxModel;
        $smanager->setIndexParams($options['index']);
        
        $smanager->setSqlParamsMatch($sphinx_sql['match']);
        $smanager->setSqlParamsOrder($options['sort']);
        
        if (!empty($sphinx_sql['where'])) {
            $smanager->setSqlParams($sphinx_sql['where']);
        }
            
        $ids = $smanager->getRecordsIds($bp->limit, $bp->offset);
        
        if(!empty($ids)) {
            $arr['where'] = sprintf('AND id IN(%s)', implode(',', $ids));
            $arr['sort'] = sprintf('ORDER BY FIELD(id, %s)', implode(',', $ids));
            
        } else {
            $arr['where'] = 'AND 0';
            $arr['sort'] = 'ORDER BY id';
        }
        
        $arr['count'] = $smanager->getCountRecords();
        
        return $arr;
    }
}
?>