<?php
$dbu = new mysql_db();
if($glob['pag'] == 'overtime'){
	$glob['time']['type'] = 4;
	$filters = get_filters($glob['t'],$glob['f'],$glob['time'],true);
	$glob['time']['type'] = 1;
	$_SESSION['filters']['time']['type'] = 1;
}else{
	$filters = get_filters($glob['t'],$glob['f'],$glob['time'],true);
}

extract($filters,EXTR_OVERWRITE);

$glob['stats_active'] = $dbu->field("SELECT SUM(session_activity.duration) FROM session_activity 
						INNER JOIN session ON session.session_id = session_activity.session_id
						".$app_join."
						WHERE session_activity.activity_type = 1
						".$app_filter);



$glob['stats_idle'] = $dbu->field("SELECT SUM(session_activity.duration) FROM session_activity 
					INNER JOIN session ON session.session_id = session_activity.session_id
						".$app_join."
					WHERE session_activity.activity_type = 0
					".$app_filter);



$glob['stats_online'] = $dbu->field("SELECT SUM(session_application.duration) FROM session_application
					INNER JOIN session ON session.session_id = session_application.session_id
					INNER JOIN application ON session_application.application_id = application.application_id
					".$app_join."
					WHERE 1=1 AND session_application.time_type = 0 AND application.application_type IN (".ONLINE_TIME_INCLUDE.") ".$app_filter);



$glob['stats_active'] =  $glob['stats_active'] ? $glob['stats_active'] : 0;
$glob['stats_idle'] =  $glob['stats_idle'] ? $glob['stats_idle'] : 0;
$glob['stats_online'] = $glob['stats_online'] ? $glob['stats_online'] : 0;

