<?php
class KBApiTopic_entry extends KBApiTopic
{
	
	
	function &execute($controller, $manager) {

        $row = $manager->getEntryById($this->entry_id, $this->category_id);

        // does not matter why no article, deleted, or inactive or private
        // always send 404
        if(!$row) { 
            KBApiError::error404();
        }
		
		$row = array('entry' => $row);
		
		return $this->parse($row, $manager);
	}	
	
}
?>