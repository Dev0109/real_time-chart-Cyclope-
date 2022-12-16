<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$fts=new ft(ADMIN_PATH.MODULE."templates/");
$fts->define(array('main' => "topapplicationsprint.html"));
$fts->define_dynamic('application_row','main');

$dbu = new mysql_db();

$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true);
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
	'TITLE' => 'Top Applications',
	'USER_DEPARTMENT_NAME' => $member_name,
	'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
));
//select all and make a total

$session = $dbu->row("SELECT SUM(session_application.duration) AS duration,session.session_id FROM session_application
INNER JOIN session ON session.session_id = session_application.session_id
".$total_join."  WHERE 1 = 1 ". $total_filter);
$total = $session['duration'];

$application = $dbu->query("SELECT SUM(session_application.duration) as app_duration,
application.description as name, 
session_application.application_id, 
application_category.application_category_id,
application_category.category FROM session_application 
INNER JOIN application ON application.application_id = session_application.application_id
INNER JOIN session ON session.session_id = session_application.session_id
INNER JOIN application_category ON application_category.application_category_id = application.application_category_id
".$app_join."
WHERE session_application.duration > 0
".$app_filter."
GROUP BY session_application.application_id
ORDER BY app_duration desc");
$i = 0;
$tot = 0;

while ($application->next() && $i < 15){
	$proc = ($application->f('app_duration') * 100 / $total);
	
	$dbu->query("SELECT SUM(session_application.duration) as app_duration,application.description as name,member.logon FROM session_application 
INNER JOIN application ON application.application_id = session_application.application_id
INNER JOIN session ON session.session_id = session_application.session_id
".$app_join."
WHERE session_application.duration > 0 AND session_application.application_id = '".$application->f('application_id')."'
".$app_filter."
GROUP BY member.member_id
ORDER BY app_duration desc");
	$user = '';
	while ($dbu->move_next()) {
		$user .= $dbu->f('logon').' - '.format_time($dbu->f('app_duration')).'<br/>';
	}
	
	$fts->assign(array(
		'APPLICATION' => $application->f('name'),
		'USER' => $user,
		'PROCENT' => ($proc > 1) ? number_format($proc,2,',','.') : ' < 1',
		'TIME' => format_time($application->f('app_duration')),
		'TIME_H' => intval(intval($application->f('app_duration')) / 3600),
		'TIME_M' => (intval($application->f('app_duration')) / 60) % 60,
		'TIME_S' => intval($application->f('app_duration')) % 60,
		'CATEGORY' => $fts->lookup($application->f('category')), 
		
		
	));
	$fts->assign('CLASS_APP', $i%2 == 0 ? '' : 'even');
	$fts->parse('APPLICATION_ROW_OUT','.application_row');
	$i++;
}


//asign the rest
$j=0;
while ($application->next()){
	$proc = ($application->f('app_duration') * 100 / $total);
	
	if(number_format($proc,2) == 0.00){
		break;
	}
	
	$total_proc += $proc;
	$total_duration += $application->f('app_duration');
	$j++;
}

if($j){
	$fts->assign(array(
		'APPLICATION' => '[!L!]Others[!/L!]',
		'CLASS_APP' => 'even',
		'USER' => '',
		'PROCENT' => number_format($total_proc,2,',','.'),  
		'TIME_H' => intval(intval($total_duration) / 3600),
		'TIME_M' => (intval($total_duration) / 60) % 60,
		'TIME_S' => intval($total_duration) % 60,
		'CATEGORY' => '', 
		
		'TIME' => format_time($total_duration),
		'WIDTH' => ceil(($total_duration * 140) / $total),
		'HIDE_OTHER_CATEGORY' => 'hide',
	));
	$fts->parse('APPLICATION_ROW_OUT','.application_row');
}

$fts->parse('CONTENT','main');
return $fts->fetch('CONTENT');