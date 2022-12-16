<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$fts=new ft(ADMIN_PATH.MODULE."templates/");
$fts->define(array('main' => "topwebsitesprint.html"));
$fts->define_dynamic('template_row','main');

$dbu = new mysql_db();

$filters = get_filters( $_SESSION['filters']['t'], $_SESSION['filters']['f'], $_SESSION['filters']['time'],true);
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
	'TITLE' => 'Top Websites',
	'USER_DEPARTMENT_NAME' => $member_name,
	'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
));

$session = $dbu->row("SELECT SUM(session_website.duration) AS duration FROM session_website 
INNER JOIN session ON session.session_id = session_website.session_id
".$total_join." WHERE 1=1
". $total_filter);
$total = $session['duration'];

$website = $dbu->query("SELECT SUM(session_website.duration) as website_duration, website.url, session_website.website_id FROM session_website
INNER JOIN website ON session_website.website_id = website.website_id
INNER JOIN session ON session.session_id = session_website.session_id
".$app_join."
WHERE session_website.duration > 0
".$app_filter."
GROUP BY session_website.website_id
ORDER BY website_duration DESC");
$i = 0;
$tot = 0;
while ($website->next() && $i < 15){
	$proc = ($website->f('website_duration') * 100 / $total);
	
	$dbu->query("SELECT SUM(session_website.duration) as website_duration, member.logon FROM session_website 
INNER JOIN website ON session_website.website_id = website.website_id
INNER JOIN session ON session.session_id = session_website.session_id
".$app_join."
WHERE session_website.duration > 0 AND session_website.website_id = '".$website->f('website_id')."'
".$app_filter."
GROUP BY member.member_id
ORDER BY website_duration desc");
	$user = '';
	while ($dbu->move_next()) {
		$user .= $dbu->f('logon').' - '.format_time($dbu->f('website_duration')).'<br/>';
	}
	
	$fts->assign(array(
		'WWW' => $website->f('url'),
		'USER' => $user,
		'CLASS' => $i % 2 == 0 ? '' : 'even',
		'PROCENT' => ($proc > 1) ? number_format($proc,2,',','.') : ' < 1',
		
		
		'TIME_H' => intval(intval($website->f('website_duration')) / 3600),
		'TIME_M' => (intval($website->f('website_duration')) / 60) % 60,
		'TIME_S' => intval($website->f('website_duration')) % 60,
	));
	
	$tot += $website->f('website_duration');
	$fts->parse('TEMPLATE_ROW_OUT','.template_row');
	$i++;
}
//asign the rest
if($total != $tot){
	$proc = (($total-$tot) * 100 / $total);
	$fts->assign(array(
		'WWW' => '[!L!]Others[!/L!]',
		'USER' => '',
		'PROCENT' => number_format($proc,2,'.',','), 
		'TIME_H' => intval(intval($total-$tot) / 3600),
		'TIME_M' => (intval($total-$tot) / 60) % 60,
		'TIME_S' => intval($total-$tot) % 60,
		'CLASS' => 'even',
		
	
		));
	$fts->parse('TEMPLATE_ROW_OUT','.template_row');
}

$fts->parse('CONTENT','main');
return $fts->fetch('CONTENT');