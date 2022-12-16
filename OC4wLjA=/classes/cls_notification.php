<?php
class notification
{
	var $dbu;
	var $_error = array();
	
	function notification()
	{
		$this->dbu = new mysql_db();
		set_time_limit(0);
	}
	function delete(&$ld)
	{
		$this->dbu->query("DELETE FROM session_notification WHERE session_notification_id = '".$ld['id']."'");
		$ld['error'] = 'Notification has been deleted !';
		return true;
	}
	function mark_as_read(&$ld)
	{
		$this->dbu->query("UPDATE session_notification
							SET mark_as_read=1
							WHERE session_notification_id = '".$ld['id']."'");
		$ld['error'] = 'Notification has been updated !';
		return true;
		
	}
}