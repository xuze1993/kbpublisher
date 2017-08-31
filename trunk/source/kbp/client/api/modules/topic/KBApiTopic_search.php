<?php

class KBApiTopic_search extends KBApiTopic_list
{

	function &execute($controller, $manager) {
	    
        if(empty($this->rq->vars['in'])) {
            $this->rq->vars['in'] = 'forum';
        }
        
	    $params = KBApiSearch::getSearchParams($this->rq->vars);
	    
        $rows = $this->getData($manager, $params);
		return $this->parse($rows, $manager);
	}
	
	
    function validate($controller, $manager) {
        $a = new KBApiSearch();
        $a->rq->vars =& $this->rq->vars;
        $a->validate($controller, $manager, 'forum');
    }
		
	
	function getData($manager, $values, $count = false) {
        
        $view = new KBClientView_search_list();
        $view->engine_name = $view->getSearchEngineName($manager, $values['q']);
        
        $sengine = $view->getSearchEngine($manager, $values, 'forum');
        $smanager = $sengine->manager;
        
        $bp = $this->pageByPage($this->limit, 1);
        
        list($count, $rows) = $smanager->getForumSearchData($bp->limit, $bp->offset, $manager);
        $bp->countAll($count);
        
		$ra = $this->getResultAttributesFromBP($bp);
		$this->setRootAttributes($ra);
        
        return $rows;
	}
		
}
?>