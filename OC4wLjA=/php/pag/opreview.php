<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => $glob['pag'].".html"));
$ft->define_dynamic('template_row','main');
$ft->define_dynamic('css_row','main');
//show top active :)
$filters = get_filters($glob['t'],$glob['f'],$glob['time'],true,true);
extract($filters,EXTR_OVERWRITE);
$ft->assign('FILTER', $glob['time']['time']);

$dbu = new mysql_db();
$users_total = $dbu->query("SELECT SUM(session_activity.duration) AS active_time,member.member_id FROM session_activity 
INNER JOIN session ON session.session_id = session_activity.session_id ".$app_join."
WHERE 1=1 AND session_activity.activity_type < 2 ".$app_filter." GROUP by session.member_id");

$total = array();
$total_time = 0;

while ($users_total->next()){
	$total[$users_total->f('member_id')] = $users_total->f('active_time');
	$total_time += $users_total->f('active_time');
}

$dbu->query("SELECT SUM(session_activity.duration) AS active_time,
					member.member_id,
					member.logon,
					member.first_name,
					member.last_name,
					member.active FROM session_activity 
INNER JOIN session ON session.session_id = session_activity.session_id ".$app_join."
WHERE 1=1 ".$app_filter." AND session_activity.activity_type = 1 GROUP by session.member_id ORDER BY active_time DESC");

$i = 0;

while ($dbu->move_next() && $i <7){	
	$proc = ($dbu->f('active_time') * 100 / $total_time);
	$ft->assign(array(
		'NAME' => ($dbu->f('active') > 1 ? $dbu->f('first_name').' '.$dbu->f('last_name') : $dbu->f('logon')).'('.number_format($proc,2).'%)',
		'I' => $i,
	));
	$ft->parse('TEMPLATE_ROW_OUT','.template_row');
	
	
	$ft->assign(array(
		'COLOR' => $i < 15 ? $_SESSION['colors'][$i] : end($_SESSION['colors']),
		'PROC' => number_format(100 - $proc,2),
		'I' => $i,
	));
	$ft->parse('CSS_ROW_OUT','.css_row');
	$i++;
}

$ft->assign('CURRENT_VERSION_FOLDER',CURRENT_VERSION_FOLDER);

$ft->parse('CONTENT','main');
//$ft->fastprint('CONTENT');
echo $ft->fetch('CONTENT');
exit();