<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/

$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => $glob['pag'].".html"));
$ft->define_dynamic('template_row','main');

$dbu = new mysql_db();
//Culmea gandului?Sa cazi pe ganduri si sa-ti spargi capul!
		$dbu->query("SELECT window.name,
		duration,
		session_log.start_time,
		session_log.active,
		session_log.end_time FROM session_log 
		INNER JOIN window ON window.window_id = session_log.window_id
		WHERE session_id = ? 
		AND session_log.duration > 0
		AND session_log.application_id = ? 
		AND session_log.start_time  >= ?
		AND session_log.end_time  <= ?
		ORDER BY duration DESC
		",array($glob['sid'],$glob['app'],$glob['start'],$glob['end']));
$max_rows=$dbu->records_count();
$i=0;
while($dbu->move_next()){
	$ft->assign(array(
		'ID' => $i+1,
		'TIME' => format_time($dbu->f('duration')),
		'WINDOW' => $dbu->f('name') ? $dbu->f('name') : $dbu->f('window_title'),
		'ACTIVE' => $dbu->f('active') ? '' : ' (<b>Idle</b>)'
	));
	if($i % 2){
		$ft->assign('CLASS','even');
	}else{
		$ft->assign('CLASS','');
	}
	$ft->parse('TEMPLATE_ROW_OUT','.template_row');
	$i++;
}
if($i==0){
	unset($ft);
	return '<ul></ul>';
}
$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');