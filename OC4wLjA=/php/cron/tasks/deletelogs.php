<?php
set_time_limit(0);
ignore_user_abort(true);

$dbu = new mysql_db();
$numbermonths = $_REQUEST['months'];
$limit_date = strtotime('-'.$numbermonths.' months',time());

$query = $dbu->query("SELECT session_id FROM session WHERE date < ".$limit_date);

$sessions = array();

while ($query->next()){
	array_push($sessions,$query->f('session_id'));
}

echo "STARTED DELETING logs older than ".$numbermonths." months \n";

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