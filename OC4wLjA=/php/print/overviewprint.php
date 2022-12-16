<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/

$ft = new ft(ADMIN_PATH.MODULE.'templates/');
$ft->define(array('main'=>'overviewprint.html'));
$ft->define_dynamic('day_summary_row','main');
$ft->define_dynamic('week_summary_row','main');
$ft->define_dynamic('application_summary_row','main');

$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true);
extract($filters,EXTR_OVERWRITE);

$dbu = new mysql_db();

//hours summary
$matches = array(); 
preg_match_all('!([0-9]{1,2}/[0-9]{1,2}/[0-9]{4})( [0-9]+\:[0-9]+ [AM|PM]+)?!',$_SESSION['filters']['time']['time'],$matches);
$matches = array_shift($matches);
$start = strtotime($matches[0]);
$end = strtotime($matches[1]);

if (!$end) {
	$days= 1;
}
else 
{
	$days = ( $end - $start ) / 86400;
	$days++;
}

$pieces = split('-',$_SESSION['filters']['f']);
$members = 0;
if ( count($pieces) == 3 ) {
	$members = 1;
	$member_row = $dbu->row("SELECT member.logon, computer.ip FROM member
	INNER JOIN computer2member ON member.member_id = computer2member.member_id
	INNER JOIN computer ON computer2member.computer_id = computer.computer_id
	WHERE member.member_id='".end($pieces)."'AND computer.computer_id='".prev($pieces)."'");
	
	$member_name = $member_row['logon'].'('.$member_row['ip'].')';
}
else 
{
	$pieces[0] = substr($pieces[0],1);
	
	if(!$pieces[0]) {
		$pieces[0] = 1;
	}
	
	$positions = $dbu->row("SELECT lft,rgt,name FROM department WHERE department_id =".$pieces[0]);
	
	$member_name = $positions['name'];
	
	$members = $dbu->field("SELECT count(member_id) FROM department  
	INNER JOIN member ON member.department_id = department.department_id 
	WHERE lft >= ".$positions['lft']." and lft <= ".$positions['rgt']);
}

$active = $dbu->query("SELECT SUM(session_activity.duration) AS duration,
								session_activity.hour,
								session_activity.day 
								FROM session_activity
								INNER JOIN session ON session.session_id = session_activity.session_id
								".$app_join."
								WHERE session_activity.activity_type = 1
								".$app_filter."
								GROUP BY session_activity.hour");
while ($active->next())
{
	if(!is_array($data[$active->f('hour')])){
		$data[$active->f('hour')] = array('active'=>0,'active_format' => 0,'idle'=>0,'idle_format' =>0);
	}
	$data[$active->f('hour')]['active'] = ($active->f('duration') * 100) / (3600 * $days * $members );
	$data[$active->f('hour')]['active_format'] = format_time($active->f('duration'));
}

$idle = $dbu->query("SELECT SUM(session_activity.duration) AS duration,
					session_activity.hour,
					session_activity.day 
					FROM session_activity
					INNER JOIN session ON session.session_id = session_activity.session_id
					".$app_join."
					WHERE session_activity.activity_type = 0
					".$app_filter."
					GROUP BY session_activity.hour");

while ($idle->next()){
	if(!is_array($data[$idle->f('hour')])){
		$data[$idle->f('hour')] = array('active'=>0,'active_format' => 0,'idle'=>0,'idle_format' =>0);
	}
	
	$data[$idle->f('hour')]['idle'] = ($idle->f('duration') * 100)/ (3600 * $days * $members);
	$data[$idle->f('hour')]['idle_format'] = format_time($idle->f('duration'));
}

if(empty($data)){

	$ld['error'].='No data to export!<br>';
	return false;
}


$ft->assign(array(
	'TITLE' => 'Overview',
	'USER_DEPARTMENT_NAME' => $member_name,
	'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
));

for($i = 0; $i < 24; $i++){
	
	if($data[$i]['active'] || $data[$i]['idle'])
	{
		$start_hour = ($i > 12) ? $i-12 : $i;
		$start_ampm = ($i < 12) ? 'AM' : 'PM';
		
		$end_hour = ($i+1 > 12) ? $i+1-12 : $i+1;
		$end_ampm = ($i+1 < 12) ? 'AM' : 'PM';
		
		if($end_hour == 12 && $end_ampm == 'PM')
		{
			$end_hour = 0;
			$end_ampm = 'AM';
			
		}
		
		$ft->assign('CLASS', $i%2 == 0 ? 'even' : '');
		$ft->assign('HOUR', $start_hour.$start_ampm.' - '.$end_hour.$end_ampm);
		$ft->assign('ACTIVE',number_format($data[$i]['active'],2));	
		$ft->assign('IDLE',number_format($data[$i]['idle'],2));
		$ft->parse('DAY_SUMMARY_ROW_OUT','.day_summary_row');
	}
	
}

// weekday summary 

$count = $dbu->row("SELECT ((MAX(date)+86400)-MIN(date))/86400 AS days,MAX(date) AS last_day_on_earth 
FROM session ".$total_join." WHERE 1=1  ".$total_filter);

$day = floor($count['days'] / 7);

if($day == 0){
	$totals = array_fill(0,7,1);
}else{
	$totals = array_fill(0,7,$day);
}

$data = array();

$active = $dbu->query("SELECT SUM(session_activity.duration) AS duration,
					session_activity.day 
				 	FROM session_activity
				 	INNER JOIN session ON session.session_id = session_activity.session_id
				 	".$app_join."
				WHERE session_activity.activity_type = 1 
				".$app_filter."
				GROUP BY session_activity.day");

while ($active->next()){
	if(!is_array($data[$active->f('day')])){
		$data[$active->f('day')] = array('active'=>0,'idle'=>0);
	}
	$data[$active->f('day')]['active'] = $active->f('duration') / 3600;
	$data[$active->f('day')]['days'] = $totals[$active->f('day')];
}

$idle = $dbu->query("SELECT SUM(session_activity.duration) AS duration,
							session_activity.day FROM session_activity
							INNER JOIN session ON session.session_id = session_activity.session_id
							".$app_join."
						WHERE session_activity.activity_type = 0 
						".$app_filter."
						GROUP BY day");

while ($idle->next()){
	if(!is_array($data[$idle->f('day')])){
		$data[$idle->f('day')] = array('active'=>0,'idle'=>0,'days' => 0);
	}
	$data[$idle->f('day')]['idle'] = $idle->f('duration') / 3600 ;
	$data[$idle->f('day')]['days'] = $totals[$idle->f('day')];
}

/*echo '<pre>';
print_r($data);
return;*/

$days = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');

for($i = 0; $i< 7; $i++){

	$proc_active =  $proc_idle = 0;
	
	$proc_active = $data[$i]['active'] ? $data[$i]['active'] : 0;
	$proc_idle = $data[$i]['idle'] ? $data[$i]['idle'] : 0;
	
	if($proc_active ==  0 && $proc_idle ==  0 )
	{
		continue;
	}
		
	$ft->assign('DAY',$days[$i]);
	$ft->assign('CLASS_WEEK', $i%2 == 0 ? '' : 'even');
	$ft->assign('WEEK_ACTIVE',number_format($proc_active,2));
	$ft->assign('WEEK_IDLE',number_format($proc_idle,2));
	$ft->parse('WEEK_SUMMARY_ROW_OUT','.week_summary_row');
}

// application usage

$session = $dbu->row("SELECT SUM(session_application.duration) AS duration,session.session_id FROM session_application
INNER JOIN session ON session.session_id = session_application.session_id
".$total_join."  WHERE 1 = 1 ". $total_filter);

$total = $session['duration'];

$dbu->query("SELECT SUM(session_application.duration) as app_duration,application.description as name FROM session_application 
INNER JOIN application ON application.application_id = session_application.application_id
INNER JOIN session ON session.session_id = session_application.session_id
".$app_join."
WHERE session_application.duration > 0
".$app_filter."
GROUP BY session_application.application_id
ORDER BY app_duration desc");
$i = 0;
$tot = 0;

while ($dbu->move_next() && $i < 6){
	$proc = ($dbu->f('app_duration') * 100 / $total);
	
	$ft->assign(array(
		'APPLICATION' => $dbu->f('name'),
		'PROCENTAGE' => number_format($proc,2,'.',','),
		'TIME_H' => intval(intval($dbu->f('app_duration')) / 3600),
		'TIME_M' => (intval($dbu->f('app_duration')) / 60) % 60,
		'TIME_S' => intval($dbu->f('app_duration')) % 60,
	));
	
	$ft->assign('CLASS_APP', $i%2 == 0 ? '' : 'even');
	$tot += $dbu->f('app_duration');
	$ft->parse('APPLICATION_SUMMARY_ROW_OUT','.application_summary_row');
	$i++;
}

if($total != $tot){
	
	$proc = (($total-$tot) * 100 / $total);
	
	$ft->assign('CLASS_APP', $i%2 == 0 ? '' : 'even');
	
	$ft->assign(array(
		'APPLICATION' => 'Other',
		'PROCENTAGE' => number_format($proc,2,'.',','), 
		'TIME_H' => intval(intval($total-$tot) / 3600),
		'TIME_M' => (intval($total-$tot) / 60) % 60,
		'TIME_S' => intval($total-$tot) % 60,
	));
	
	$ft->parse('APPLICATION_SUMMARY_ROW_OUT','.application_summary_row');
}

global $site_meta_title;
$site_meta_title .= ' - Overview Print';

$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');
?>