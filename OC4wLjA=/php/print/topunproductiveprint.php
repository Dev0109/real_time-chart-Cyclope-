<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$fts=new ft(ADMIN_PATH.MODULE."templates/");
$fts->define(array('main' => "topunproductiveprint.html"));
$fts->define_dynamic('template_row','main');

$dbu = new mysql_db();

$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true,true);
extract($filters,EXTR_OVERWRITE);

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

$fts->assign(array(
	'TITLE' => 'Top Unproductive',
	'USER_DEPARTMENT_NAME' => $member_name,
	'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
));

$users_total = $dbu->query("SELECT SUM(session_activity.duration) AS total_time,member.member_id FROM session_activity 
INNER JOIN session ON session.session_id = session_activity.session_id ".$total_join."
WHERE 1 = 1 ".$total_filter." GROUP by session.member_id");

$total = array();
$total_time = 0;
while ($users_total->next()){
	$total[$users_total->f('member_id')] = $users_total->f('total_time');
	$total_time += $users_total->f('total_time');
}

$productivity = $dbu->query("SELECT SUM(session_log.duration) AS app_duration,
						COALESCE(application_productivity.productive,-1) AS productive,
						session_log.type_id,
						application.application_id,
						member.logon,						
						member.member_id
						FROM session_log
						INNER JOIN application ON application.application_id = session_log.application_id 
						INNER JOIN session ON session.session_id = session_log.session_id
						".$app_join."
						INNER JOIN application_productivity ON application_productivity.department_id = member.department_id 
														  AND application_productivity.link_id = application.application_id 
													      AND application_productivity.link_type = 0
						WHERE session_log.duration > 0
						AND session_log.active = 1
						AND (productive = 0 OR productive = 3)
						AND application.application_type = session_log.type_id
						".$app_filter."
						GROUP BY member.member_id,session_log.application_id
						ORDER BY app_duration desc
						LIMIT 15");
$data = array();
$durations = array();
while ($productivity->next()){
	$duration = 0;
	if(!is_array($data[$productivity->f('member_id')])){
		$data[$productivity->f('member_id')] = array();
	} 
	$duration = $productivity->f('app_duration');
	if($productivity->f('productive') == 3){
		$duration = 0;
		$children = $dbu->query("SELECT SUM(session_log.duration) AS app_duration,
       								application_productivity.productive AS productive
							FROM session_log
							INNER JOIN session ON session.session_id = session_log.session_id
							".$app_join."
							INNER JOIN application_productivity ON application_productivity.department_id = member.department_id 
											  AND application_productivity.link_id = session_log.link_id
											  AND application_productivity.link_type = session_log.type_id
							WHERE session_log.duration > 0
							AND session_log.active = 1
							AND 2=2
							AND application_productivity.productive = 0
							AND session_log.application_id = ".$productivity->f('application_id')."
							AND session_log.type_id = ".$productivity->f('type_id')."
							".$app_filter."
							AND member.member_id = ".$productivity->f('member_id')."
							GROUP BY application_productivity.productive");
		while ($children->next()){
			$duration += $children->f('app_duration');
		}
	}
	$data[$productivity->f('member_id')]['name'] = $productivity->f('logon');
	$data[$productivity->f('member_id')]['duration'] += $duration;
	
	$durations[$productivity->f('member_id')] = $data[$productivity->f('member_id')]['duration'];
}

arsort($durations);
$i = 0;

foreach ($durations as $member_id => $duration){
	$tags = $data[$member_id];
	$proc = ($tags['duration'] * 100 / $total[$member_id]);
	$fts->assign(array(
		'USER' => $tags['name'],
		'PROCENT' => ($proc > 1) ? number_format($proc,2,',','.') : ' < 1',
		'CLASS' => $i%2 == 0 ? '' : 'even',
		
		'TOTAL_TIME_H' => intval(intval($total[$member_id]) / 3600),
		'TOTAL_TIME_M' => (intval($total[$member_id]) / 60) % 60,
		'TOTAL_TIME_S' => intval($total[$member_id]) % 60,
		
		'UNPRODUCTIVE_TIME_H' => intval(intval($tags['duration']) / 3600),
		'UNPRODUCTIVE_TIME_M' => (intval($tags['duration']) / 60) % 60,
		'UNPRODUCTIVE_TIME_S' => intval($tags['duration']) % 60,
	));
	
	$fts->parse('TEMPLATE_ROW_OUT','.template_row');
	$i++;
}

$fts->parse('CONTENT','main');
return $fts->fetch('CONTENT');