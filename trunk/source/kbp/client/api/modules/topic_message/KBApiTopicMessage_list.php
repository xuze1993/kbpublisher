<?php
class KBApiTopicMessage_list extends KBApiTopicMessage
{
	
	var $allowed_sort_order = array(
        'date-posted-asc'    => 'm.date_posted ASC',
        'date-posted-desc'   => 'm.date_posted DESC'
        );
	
	var $default_sort_order = 'date-posted-asc';
	
	
	function &execute($controller, $manager) {
        $rows = $this->getData($manager, $this->rq->vars, true);
        return $this->parse($rows, $manager);
	}
	
	
    function validate($controller, $manager) {
        $a = new KBApiArticle_list();
        $a->rq = new RequestData($_GET);
        $a->allowed_sort_order = $this->allowed_sort_order;
        $a->validate($controller, $manager);
    }
		
	
	function getData($manager, $values, $count = false) {
		
        // sort order
        $sort = (!empty($values['sort'])) ? $values['sort'] : $this->default_sort_order;
        $sort_order = $this->getSortOrderValue($sort);
        if($sort_order) {
            $manager->setSqlParamsOrder('ORDER BY ' . $sort_order);
        }
        
        $topic_id = false;
        if(isset($values['topic_id'])) {
            $topic_id = (int) $values['topic_id'];
        }
        $rows = $manager->getEntryMessages($topic_id, -1, -1, $sort_order);
        
		// root attr
        if($count) {
    		$ra = $this->getResultAttributes(1, 1, count($rows), count($rows));
    		$this->setRootAttributes($ra);
        }

		return $rows;
	}
    
    
    function getSortOrderValue($value) {
        $ret = false;
        $value = strtolower($value);
        if(isset($this->allowed_sort_order[$value])) {
            $ret = $this->allowed_sort_order[$value];
        }
        return $ret;    
    }
    
}
?>