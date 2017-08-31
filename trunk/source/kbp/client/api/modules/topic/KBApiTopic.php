<?php
class KBApiTopic extends KBApiCommon
{		

    var $map_fields = array(
        'category_id' => 'forum_id',
        'category_name' => 'forum_name',
        'category_active' => 'forum_active',
        'category_private' => 'forum_private',
        'posts' => 'message_num'
        );

    var $remove_fields = array(
        'real_sort_order',
        'url_title',
        'ts_posted',
        'ts_updated',
        'is_sticky'
        );
    
    var $html_fields = array(
        'message'
        );
        
	
	function &parse($rows, $manager) {
		
		// rows
		$data = array();
		foreach(array_keys($rows) as $k) {
		    
			$row = $rows[$k];

			$entry_id = $this->cc->getEntryLinkParams($row['id'], $row['title'], $row['url_title']);
			$row['link'] = $this->cc->getLink('topic', $row['category_id'], $entry_id);			
			$row['print_link'] = $this->cc->getLink('print-topic', $row['category_id'], $row['id']);			
			
            $row['sticky'] = ($row['is_sticky']) ? 1 : 0;
			//$row['message_num'] = count($row['messages']);
			
            // decoding
            /*foreach(array_keys($row['messages']) as $k) {
                $row['messages'][$k]['message'] = $this->encodeHTML($row['messages'][$k]['message'], 'html'); 
            }*/
            
		    $data[$row['id']] = $this->getReturnFields($row);
		}

		return $data;
	}

}
?>