<?php
/*error_reporting(0);*/
set_time_limit(0);
ignore_user_abort(true);
$dbu = new mysql_db();
include_once('php/gen/startup.php');
include_once('misc/json.php');
debug_log('.. ENTERED IN AUTODELETELOGSHALF ================================= at ' . date('l jS \of F Y h:i:s A'),'cronlog');

$numbermonths = 6;
$limit_date = strtotime('-'.$numbermonths.' months',time());

$dbu->query("SELECT value FROM settings WHERE constant_name='AUTODELETE_LOGSHALF'");
$dbu->move_next();
if($dbu->f('value') == 1){
	echo "STARTED AUTOMATIC DELETION of logs older than ".$numbermonths." months \n";
	
	$query = $dbu->query("SELECT session_id FROM session WHERE date < ".$limit_date);
	
	$sessions = array();
	while ($query->next()){
		array_push($sessions,$query->f('session_id'));
	}

	if(!empty($sessions)){
		
		$dbu->query("DELETE FROM session_activity WHERE session_id IN (".join(',',$sessions).")");
		$dbu->query("DELETE FROM session_application WHERE session_id IN (".join(',',$sessions).")");
		$dbu->query("DELETE FROM session_chat WHERE session_id IN (".join(',',$sessions).")");
		$dbu->query("DELETE FROM session_document WHERE session_id IN (".join(',',$sessions).")");
		$dbu->query("DELETE FROM session_website WHERE session_id IN (".join(',',$sessions).")");
		$dbu->query("DELETE FROM session_website_agg WHERE session_id IN (".join(',',$sessions).")");
		$dbu->query("DELETE FROM session_file WHERE session_id IN (".join(',',$sessions).")");
		$dbu->query("DELETE FROM session_log WHERE session_id IN (".join(',',$sessions).")");
		$dbu->query("DELETE FROM session WHERE session_id IN (".join(',',$sessions).")");
		
	}
	
	$dbu->query("DELETE FROM chat WHERE last_access < ".$limit_date);
	$dbu->query("DELETE FROM document WHERE last_access < ".$limit_date);
	$dbu->query("DELETE FROM domain WHERE last_access < ".$limit_date);
	$dbu->query("DELETE FROM file WHERE last_access < ".$limit_date);
	$dbu->query("DELETE FROM fileprint WHERE last_access < ".$limit_date);
	$dbu->query("DELETE FROM website WHERE last_access < ".$limit_date);
	$dbu->query("DELETE FROM window WHERE last_access < ".$limit_date);
	
	echo "FINISHED \n";
}



