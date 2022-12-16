<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$fts=new ft(ADMIN_PATH.MODULE."templates/");
$fts->define(array('main' => "toponlineprint.html"));
$fts->define_dynamic('template_row','main');

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
	'TITLE' => 'Top Online',
	'USER_DEPARTMENT_NAME' => $member_name,
	'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
));

$users_total = $dbu->query("SELECT SUM(session_activity.duration) AS active_time, member.member_id FROM session_activity 
INNER JOIN session ON session.session_id = session_activity.session_id ".$total_join."
WHERE 1=1 ".$total_filter." GROUP by session.member_id");

$total = array();
$total_time = 0;

while ($users_total->next()){
	$total[$users_total->f('member_id')] = $users_total->f('active_time');
	$total_time += $users_total->f('active_time');
}

$dbu->query("SELECT SUM(session_application.duration) as app_duration,member.logon,member.member_id FROM session_application
INNER JOIN session ON session.session_id = session_application.session_id
INNER JOIN application ON application.application_id = session_application.application_id
".$app_join."
WHERE 1=1 AND application.application_type  IN (".ONLINE_TIME_INCLUDE.")".$app_filter."
GROUP BY member.member_id
ORDER BY app_duration DESC LIMIT 15");

$i = 0;

while ($dbu->move_next()){	
	$proc = ($dbu->f('app_duration') * 100 / $total[$dbu->f('member_id')]);
	$fts->assign(array(
		'USER' => $dbu->f('logon'),
		'PROCENT' => ($proc > 1) ? number_format($proc,2,',','.') : ' < 1',
		'CLASS' => $i%2 == 0 ? '' : 'even',
		'TOTAL_TIME_H' => intval(intval($total[$dbu->f('member_id')]) / 3600),
		'TOTAL_TIME_M' => (intval($total[$dbu->f('member_id')]) / 60) % 60,
		'TOTAL_TIME_S' => intval($total[$dbu->f('member_id')]) % 60,
		
		'ONLINE_TIME_H' => intval(intval($dbu->f('app_duration')) / 3600),
		'ONLINE_TIME_M' => (intval($dbu->f('app_duration')) / 60) % 60,
		'ONLINE_TIME_S' => intval($dbu->f('app_duration')) % 60,
		
	));
	$fts->parse('TEMPLATE_ROW_OUT','.template_row');
	$i++;
}

$fts->parse('CONTENT','main');
return $fts->fetch('CONTENT');