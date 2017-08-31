<?php
class KBApiArticle_entry extends KBApiArticle
{
	
	
	function &execute($controller, $manager) {

        $row = $manager->getEntryById($this->entry_id, $this->category_id);

        // does not matter why no article, deleted, or inactive or private
        // always send 404
        if(!$row) { 
            KBApiError::error404();
        }
		
        // views
        if(empty($this->rq->skip_hit)) {
            $manager->addView($this->entry_id);
        }
        
		// related
        $related = &$manager->getEntryRelated($this->entry_id);
        $row['related'] = $related['attached'];
        
        // custom
        $custom = $manager->getCustomDataByEntryId($this->entry_id);
        $row['custom']['item'] = $this->getCustomDataApi($custom);
        
        // attachments
        $row['attachments'] = $manager->getAttachmentList($this->entry_id);
        
        // parse images
        $row['body'] = $this->parseImages($row['body'], $controller->baseUrl);
        
        if(DocumentParser::isTemplate($row['body'])) {
            DocumentParser::parseTemplate($row['body'], array($manager, 'getTemplate'));
        }        
            
        if(DocumentParser::isLink($row['body'])) {
            DocumentParser::parseLink($row['body'], array($this->cv, 'getLink'), $manager, 
                $related['inline'], $this->entry_id, $this->cc);    
        }
            
        if(DocumentParser::isCode($row['body'])) {
            DocumentParser::parseCode($row['body'], $manager, $this->cc);
        }
        
		$row = array('entry' => $row);
		
		return $this->parse($row, $manager);
	}
	
}
?>