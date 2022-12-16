<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/

$ftappusage=new ft(ADMIN_PATH.MODULE."templates/");
$ftappusage->define(array('main' => "xappusage.html"));
$ftappusage->define_dynamic('template_row','main');
$ftappusage->define_dynamic('template_other_row','main');


$dbu = new mysql_db();
$glob['time']['type'] = 4;
$filters = get_filters($glob['t'],$glob['f'],$glob['time']);
extract($filters,EXTR_OVERWRITE);

//	lorand
$sortable_columns = array(
	'app_duration',
	'name',
	);

$sortcolumns = get_sorting($sortable_columns,'','desc');

$ftappusage->assign(array(
	'ANCHOR_INNER_0' => render_anchor_inner(0),
	'ANCHOR_INNER_1' => render_anchor_inner(1),
	'DEBUGMESSAGE' => '',
	// 'DEBUGMESSAGE' => basename($_SERVER['PHP_SELF']),
	// 'DEBUGMESSAGE' => "Not done yet, to be ok, needs sorting function, and links in template",
));
//END

//select all and make a total
$session = $dbu->row("SELECT SUM(session_application.duration) AS duration,session.session_id FROM session_application
INNER JOIN session ON session.session_id = session_application.session_id
".$app_join."  WHERE 1 = 1 AND session_application.time_type = 0 ". $app_filter);

$total = $session['duration'];
$dbu->query("SELECT SUM(session_application.duration) as app_duration,application.description as name FROM session_application 
INNER JOIN application ON application.application_id = session_application.application_id
INNER JOIN session ON session.session_id = session_application.session_id
".$app_join."
WHERE session_application.duration > 0 AND session_application.time_type = 0 
".$app_filter."
GROUP BY session_application.application_id
" . $sortcolumns . " ");
$i = 0;
$tot = 0;
while ($i < 9 && $dbu->move_next() ){
	
	$proc = ($dbu->f('app_duration') * 100 / $total);
	
	$ftappusage->assign(array(
		'NAME' => $dbu->f('name'),
		'PROCENT' => ($proc > 1) ? number_format($proc,2,',','.') : ' < 1',
		'TIME' => format_time($dbu->f('app_duration')),
		'COLOR' => $_SESSION['colors'][$i] 
	));
	
	$tot += $dbu->f('app_duration');
	$ftappusage->parse('TEMPLATE_ROW_OUT','.template_row');
	$i++;
}

//asign the rest
while ($dbu->move_next()){
	$proc = ($dbu->f('app_duration') * 100 / $total);
	
	$ftappusage->assign(array(
		'OTHER_NAME' => $dbu->f('name'),
		'OTHER_PROCENT' => ($proc > 1) ? number_format($proc,2,',','.') : ' < 1',
		'OTHER_TIME' => format_time($dbu->f('app_duration')),
		'OTHER_COLOR' => end($_SESSION['colors'])
	));
	$ftappusage->parse('TEMPLATE_OTHER_ROW_OUT','.template_other_row');
}
if($total != $tot){
	$proc = (($total-$tot) * 100 / $total);
	$ftappusage->assign(array(
		'NAME' => '<a href="#" class="toggleother">'.$ft->lookup('Others').'</a>',
		'PROCENT' => number_format($proc,2,'.',','), 
		'TIME' => format_time($total-$tot), 
		'COLOR' => end($_SESSION['colors'])
	));
	$ftappusage->parse('TEMPLATE_ROW_OUT','.template_row');
}
$glob['time']['type'] = 1;
$_SESSION['filters']['time']['type'] = 1;

$ftappusage->parse('CONTENT','main');
//$ftappusage->fastprint('CONTENT');
return $ftappusage->fetch('CONTENT');
