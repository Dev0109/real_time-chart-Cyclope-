<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$fts=new ft(ADMIN_PATH.MODULE."templates/");
$fts->define(array('main' => "topidleprint.html"));
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
	'TITLE' => 'Top Idle',
	'USER_DEPARTMENT_NAME' => $member_name,
	'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
));

$users_total = $dbu->query("SELECT SUM(session_activity.duration) AS idle_time,member.member_id FROM session_activity 
INNER JOIN session ON session.session_id = session_activity.session_id ".$total_join."
WHERE 1 = 1 ".$total_filter." GROUP by session.member_id");

$total = array();
$total_time = 0;
while ($users_total->next()){
	$total[$users_total->f('member_id')] = $users_total->f('idle_time');
	$total_time += $users_total->f('idle_time');
}

$dbu->query("SELECT SUM(session_activity.duration) AS idle_time, member.member_id, member.logon FROM session_activity 
INNER JOIN session ON session.session_id = session_activity.session_id ".$total_join."
WHERE 1=1 ".$total_filter." AND session_activity.activity_type = 0 GROUP by session.member_id ORDER BY idle_time DESC LIMIT 15");

$i = 0;

while ($dbu->move_next()){
	$proc = ($dbu->f('idle_time') * 100 / $total[$dbu->f('member_id')]);
	$fts->assign(array(
		'USER' => $dbu->f('logon'),
		'PROCENT' => ($proc > 1) ? number_format($proc,2,',','.') : ' < 1',
		'CLASS' => $i%2 == 0 ? '' : 'even',
		
		'TOTAL_TIME_H' => intval(intval($total[$dbu->f('member_id')]) / 3600),
		'TOTAL_TIME_M' => (intval($total[$dbu->f('member_id')]) / 60) % 60,
		'TOTAL_TIME_S' => intval($total[$dbu->f('member_id')]) % 60,
		
		'IDLE_TIME_H' => intval(intval($dbu->f('idle_time')) / 3600),
		'IDLE_TIME_M' => (intval($dbu->f('idle_time')) / 60) % 60,
		'IDLE_TIME_S' => intval($dbu->f('idle_time')) % 60,
	));
	$fts->parse('TEMPLATE_ROW_OUT','.template_row');
	$i++;
}

$fts->parse('CONTENT','main');
return $fts->fetch('CONTENT');