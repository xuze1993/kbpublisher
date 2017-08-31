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

class KBClientSearchModel extends KBClientModel
{

    var $values = array();
    var $period = 'all';
    var $order;

    var $count_limit = 100;


    function __construct(&$values, $manager) {
        parent::__construct();

        $this->cf_manager = new CommonCustomFieldModel($manager);

        $this->values = &$values;
        if(!empty($values['period'])) {
            $this->period = $values['period'];
        }

        $in_vals = KBClientSearchHelper::getInValue($values, $manager);
        $this->values['in'] = $in_vals['in'];
        $this->values['by'] = $in_vals['by'];
    }


    function getTagIds($tags) {

        $tags = (is_array($tags)) ? $tags : array($tags);
        foreach($tags as $k => $tag) {
            // $tags[$k] = _strtolower(trim($tag));
            $tags[$k] = trim($tag);
        }

        $tags = implode("','", $tags);

        $sql = "SELECT id, id AS id2 FROM {$this->tbl->tag} WHERE title IN ('{$tags}')";
        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $result->GetAssoc();
    }


    function logUserSearch($values, $search_type, $exitcode, $user_id = NULL, $user_ip = NULL) {

        // no log if bp (by page)
        if(isset($values['bp'])) {
            return;
        }

        // getting rid of a button
        if (isset($values['sb'])) {
            unset($values['sb']);
        }

        $user_id = ($user_id === NULL) ? $this->user_id : $user_id;
        $user_id = ($user_id) ? $user_id : 0;
        $user_ip = ($user_ip === NULL) ? WebUtil::getIP() : $user_ip;

        $search_str = $values['q'];
        $search_str = RequestDataUtil::stripVars($search_str, array());

        $search_opt = RequestDataUtil::stripVars($values, array(), 'stripslashes');
        $search_opt = addslashes(serialize($search_opt));

        $sql = "INSERT {$this->tbl->log_search} SET
        user_id = '{$user_id}',
        search_type = '{$search_type}',
        search_option = '{$search_opt}',
        search_string = '{$search_str}',
        user_ip = IFNULL(INET_ATON('{$user_ip}'), 0),
        exitcode = '{$exitcode}'";

        $result = $this->db->Execute($sql) or die(db_error($sql));
        return $this->db->Insert_ID();
    }


    function setFullTextParams($entry_type, $search_type = 1) {
        
        $in = $this->values['in'];
        $by = $this->values['by'];

        // keywords
        if(!empty($this->values['q'])) {
            
            $str = trim($this->values['q']);
            $str = SphinxModel::getSphinxString($str); // sphinx test mode
            if (empty($this->sphinx)) {
                $str = addslashes(stripslashes($str));
            }

            $arr_title = array('title');
            $arr_tag = array('keyword');
            $arr_ids = array('id');
            $arr_author_ids = array('author_id');

            // ids
            if (in_array($by, $arr_ids)) {
                $search = array('#[\.,:;]#', '[ +]');
                $replace = array(',', '');
                $str = preg_replace($search, $replace, $str);

                foreach(explode(',', $str) as $v) {
                    $ids[] = (int)$v;
                }

                $this->filterById($ids, $entry_type);

            // titles
            } elseif (in_array($by, $arr_title)) {
                $this->filterByTitle($str);

            // keywords
            } elseif (in_array($by, $arr_tag)) {

                $tags = str_replace(array('[',']'), '', $str);
                $tags = explode(',', $tags);
                foreach($tags as $k => $v) {
                    $tags[$k] = trim($v);
                }

                $tag_ids = $this->getTagIds($tags);

                $this->filterByTag($tag_ids);

            // user id (author_id)
            } elseif (in_array($by, $arr_author_ids)) {
                $search = array('#[\.,:;]#', '[ +]');
                $replace = array(',', '');
                $str = preg_replace($search, $replace, $str);

                foreach(explode(',', $str) as $v) {
                    $ids[] = (int)$v;
                }

                $this->filterByAuthor($ids);
                
            // attachment
            } elseif($by == 'attachment') {
                $this->filterByArticleAttachments($str);
				
            // filename
            } elseif($by == 'filename') {
                $this->filterByFilename($str);

            //article all
            } elseif($in == 'article') {
                $this->filterArticleByAllFields($str);

            // file all
            } elseif($in == 'file') {
                $this->filterFileByAllFields($str);

            // news all
            } elseif($in == 'news') {
                $this->filterNewsByAllFields($str);

			// forum all
			} elseif($in == 'forum') {

                $topic_id = false;
                $_str = $str;
                if (!empty($this->values['topic_id'])) {
			        $topic_id = $this->values['topic_id'];

			    } elseif (preg_match('#topic\:(\d+)\s+(.*)$#', $str, $matches)) {
			        $topic_id = $matches[1];
                    $_str = $matches[2];
			    }

                $this->filterForumByAllFields($_str, $topic_id);
            }

        } else {
            $this->filterEmpty($entry_type);
        }
    }


    /*
    [search_period_range]
    all                = All time
    last_10_day        = Last 10 days
    last_30_day        = Last 30 days
    last_90_day        = Last 90 days
    last_1_year        = Last year
    custom            = Custom period
    */
    function setDateParams($entry_type = 'article') {

        if($this->period == 'all') {
            return;
        }

        switch ($this->period) {
        case 'custom': // ------------------------------
            $this->filterByCustomDate($entry_type);
            break;

        default:

            //last_10_day...
            $ret = preg_match("/last_(\d+)_(day|week|month|year)/", $this->period, $match);
            if($ret) {
                $this->filterByDate($entry_type, $match);
            }

            break;
        }
    }


    function setEntryTypeParams() {

        if(!empty($this->values['et'])) {
            $c = array();
            foreach($this->values['et'] as $k => $v) {
                $c[$k] = (int) $v;
            }

            if($c) {
                $this->filterByEntryType($c);
            }
        }
    }


    function setCategoryParams($categories) {

        if(isset($this->values['cf']) && $this->values['cf'] != 'all' && $this->values['cf'] != 'top') {
            if(empty($this->values['c'])) {
                $this->values['c'][] = $this->values['cf'];
            }
        }

        // categories
        if(!empty($this->values['c']) && $this->values['c'] != 'all') {
            $c = array();
            if(!is_array($this->values['c'])) {
                $this->values['c'] = array($this->values['c']);
            }

            foreach($this->values['c'] as $k => $v) {
                $c[$k] = (int) $v;
            }

            // all child
            if(!empty($this->values['cp'])) {

                $tree = new TreeHelper();
                foreach($categories as $k => $row) {
                    $tree->setTreeItem($row['id'], $row['parent_id']);
                }

                $child = array();
                foreach($c as $k => $v) {
                    $_child = $tree->getChildsById($v);

                    foreach($_child as $id) {
                        if(!$id) { continue; }
                        $child[] = $id;
                    }
                }

                //echo "<pre>"; print_r($child); echo "</pre>";
                //echo "<pre>"; print_r($this->values['c']); echo "</pre>";
                //echo "<pre>"; print_r(array_unique(array_merge($child,$this->values['c']))); echo "</pre>";

                $c = array_unique(array_merge($child, $c));
            }

            $this->filterByCategory($c);
        }
    }

}
?>