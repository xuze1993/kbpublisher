<?php
class KBApiTopicMessage extends KBApiCommon
{		

    var $map_fields = array(
        'entry_id' => 'topic_id'
        );

    var $remove_fields = array(
        'message_index',
        );

    var $html_fields = array(
        'message'
        );
	
    
	function &parse($rows, $manager) {

		// rows
		$data = array();
		foreach(array_keys($rows) as $k) {
			$row = $rows[$k];
		    $data[$row['id']] = $this->getReturnFields($row);
		}

		return $data;
	}

}
?>