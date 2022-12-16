<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/

$ftx=new ft(ADMIN_PATH.MODULE."templates/");
$ftx->define(array('main' => "xappusage.html"));
$ftx->define_dynamic('template_row','main');
$ftx->define_dynamic('template_other_row','main');


$dbu = new mysql_db();

$filters = get_filters($glob['t'],$glob['f'],$glob['time']);
extract($filters,EXTR_OVERWRITE);

//	lorand
$sortable_columns = array(
	'app_duration',
	'name',
	);

$sortcolumns = get_sorting($sortable_columns,'','desc');

$ftx->assign(array(
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
while ( $i < 9 && $dbu->move_next() ){
	$proc = ($dbu->f('app_duration') * 100 / $total);
	$ftx->assign(array(
		'NAME' => $dbu->f('name'),
		'PROCENT' => ($proc > 1) ? number_format($proc,2,',','.') : ' < 1',
		'TIME' => format_time($dbu->f('app_duration')),
		'COLOR' => $_SESSION['colors'][$i] 
	));
	$tot += $dbu->f('app_duration');
	$ftx->parse('TEMPLATE_ROW_OUT','.template_row');
	$i++;
}

//asign the rest
while ($dbu->move_next()){
	$proc = ($dbu->f('app_duration') * 100 / $total);
	
	$ftx->assign(array(
		'OTHER_NAME' => $dbu->f('name'),
		'OTHER_PROCENT' => ($proc > 1) ? number_format($proc,2,',','.') : ' < 1',
		'OTHER_TIME' => format_time($dbu->f('app_duration')),
		'OTHER_COLOR' => end($_SESSION['colors'])
	));
	$ftx->parse('TEMPLATE_OTHER_ROW_OUT','.template_other_row');
}
if($total != $tot){
	$proc = (($total-$tot) * 100 / $total);
	$ftx->assign(array(
		'NAME' => '<a href="#" class="toggleother">'.$ftx->lookup('Others').'</a>',
		'PROCENT' => number_format($proc,2,'.',','), 
		'TIME' => format_time($total-$tot), 
		'COLOR' => end($_SESSION['colors'])
	));
	$ftx->parse('TEMPLATE_ROW_OUT','.template_row');
}
	
$export_header = get_export_header($glob['f']);
extract($export_header,EXTR_OVERWRITE);
$glob['append'] = trialEncrypt($member_name);

	//	modified for pdf	--->
	$export_header = get_export_header($_SESSION['filters']['f']);
	extract($export_header,EXTR_OVERWRITE);
	$ftx->assign(array(
		'PDF_HEADER' => pdf_header(),
		'PDF_HIDE' => pdf_hide(),
		'PDF_CLASS' => pdf_class(),
		'TITLE' => $ftx->lookup('Application'),
		'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
		'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
	));
	//	<---	modified for pdf

$ftx->parse('CONTENT','main');
//$ftx->fastprint('CONTENT');
	//	modified for pdf	--->
	if($_REQUEST['render'] == 'pdf'){
		$page = 'appusage';
		$html = $ftx->fetch('CONTENT');
			file_put_contents(CURRENT_VERSION_FOLDER.'temp_pdf/'.$page.'.html',"\xEF\xBB\xBF" . $html);
		loadPDF($page,'inline');exit;
	} else {
		return $ftx->fetch('CONTENT');
	}
	//	<---	modified for pdf
