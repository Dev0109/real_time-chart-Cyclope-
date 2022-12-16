<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftx=new ft(ADMIN_PATH.MODULE."templates/");
$ftx->define(array('main' => "xtoponline.html"));
$ftx->define_dynamic('template_row','main');

$filters = get_filters($glob['t'],$glob['f'],$glob['time'],true,true);
extract($filters,EXTR_OVERWRITE);

$users_total = $dbu->query("SELECT SUM(session_activity.duration) AS active_time,
									member.member_id 
									FROM session_activity 
									INNER JOIN session ON session.session_id = session_activity.session_id ".$app_join."
									WHERE 1=1 AND session_activity.activity_type = 1 ".$app_filter." GROUP by session.member_id");

$total = array();
$total_time = 0;

while ($users_total->next()){
	$total[$users_total->f('member_id')] = $users_total->f('active_time');
	$total_time += $users_total->f('active_time');
}

//	lorand
$sortable_columns = array(
	'app_duration',
	'member.logon',
	);

$sortcolumns = get_sorting($sortable_columns,'','desc');

$ftx->assign(array(
	'ANCHOR_INNER_0' => render_anchor_inner(0),
	'ANCHOR_INNER_1' => render_anchor_inner(1),
	'DEBUGMESSAGE' => '',
	// 'DEBUGMESSAGE' => basename($_SERVER['PHP_SELF']),
	// 'DEBUGMESSAGE' => $sortcolumns,
));
//END

$dbu->query("SELECT SUM(session_application.duration) as app_duration,
					member.member_id,
					member.logon,
					member.alias,
					member.first_name,
					member.last_name,
					member.active  FROM session_application
INNER JOIN session ON session.session_id = session_application.session_id
INNER JOIN application ON application.application_id = session_application.application_id
".$app_join."
WHERE 1=1 AND session_application.time_type = 0 AND application.application_type  IN (".ONLINE_TIME_INCLUDE.")".$app_filter."
GROUP BY member.member_id
" . $sortcolumns . " ");

$i = 0;

if ($total[$dbu->f('member_id')] < 1 || !is_numeric($total[$dbu->f('member_id')])){
	$total[$dbu->f('member_id')] = 1;
}

while ($dbu->move_next()){	
	$proc = ($dbu->f('app_duration') * 100 / $total[$dbu->f('member_id')]);
	$ftx->assign(array(
		'NAME' => trialEncrypt($dbu->f('alias') == 1 ? $dbu->f('first_name').' '.$dbu->f('last_name') : $dbu->f('logon')),
		'PROCENT' => ($proc > 1) ? number_format($proc,2,',','.') : ' < 1',
		'TOTAL_ACTIVE' => format_time($total[$dbu->f('member_id')]),
		'TIME' => format_time($dbu->f('app_duration')),
		'WIDTH' => ceil(($dbu->f('app_duration') * 140) / $total_time),
		'COLOR' => $i < 15 ? $_SESSION['colors'][$i] : end($_SESSION['colors']),
	));
	$ftx->parse('TEMPLATE_ROW_OUT','.template_row');
	$i++;
}

if(!$i)
{
	return '';
}

//	------------------------------
//	------------------------------
//	------------------------------

$toponlinechart = new stdClass;
$toponlinechart = array("settings" => array("container" => array("select" => "toponlinechart", "height" => "300px", "width" => "690px")), "theme" => "theme1",);
// $toponlinechart->settings->container->selector = "toponlinechart";
// $toponlinechart->settings->container->height = "300px";
// $toponlinechart->settings->container->width = "690px";
// $toponlinechart->theme = "theme1";
$toponlinechart['animationEnabled'] = pdf_animate();
$toponlinechart['interactivityEnabled'] = true;
$toponlinechart['barwidth'] = 30;
$toponlinechart['axisX']['labelWrap'] = true;
$toponlinechart['axisX']['labelMaxWidth'] = 100;
// $toponlinechart['axisX']['labelFontSize'] = 11;
$toponlinechart['axisY']['labelFontSize'] = 11;
$toponlinechart['axisX']['interval'] = 1;
$toponlinechart['axisX']['labelAngle'] = 270;
$toponlinechart['axisY']['minimum'] = 0;
$toponlinechart['axisY']['suffix'] = "h";
$toponlinechart['legend']['verticalAlign'] = "bottom";
$toponlinechart['legend']['horizontalAlign'] = "center";

$toponlinechart['data'][0]['type'] = "column";
$dbu->query("SELECT SUM(session_application.duration) as app_duration,
					member.member_id,
					member.logon,
					member.first_name,
					member.last_name,
					member.alias,
					member.active FROM session_application
INNER JOIN session ON session.session_id = session_application.session_id
INNER JOIN application ON application.application_id = session_application.application_id
".$app_join."
WHERE 1=1 AND session_application.time_type = 0 AND application.application_type  IN (".ONLINE_TIME_INCLUDE.")".$app_filter."
GROUP BY member.member_id
ORDER BY app_duration DESC LIMIT 15");

$i = 0;

while ($dbu->move_next() && $i < 15){
			$toponlinechart['data'][0]['dataPoints'][$i]['y'] = (float)$dbu->f('app_duration') / 3600;
			$toponlinechart['data'][0]['dataPoints'][$i]['toolTipContent'] = trialEncrypt($dbu->f('alias') == 1 ? $dbu->f('first_name').' '.$dbu->f('last_name') : $dbu->f('logon')) . ' - ' . format_time($dbu->f('app_duration'));
			$toponlinechart['data'][0]['dataPoints'][$i]['label'] = trialEncrypt($dbu->f('alias') == 1 ? $dbu->f('first_name').' '.$dbu->f('last_name') : $dbu->f('logon'));
			$toponlinechart['data'][0]['dataPoints'][$i]['color'] = "#" . $_SESSION['colors'][$i];
	$ftx->assign(array(
		'NAME' => trialEncrypt($dbu->f('alias') == 1 ? $dbu->f('first_name').' '.$dbu->f('last_name') : $dbu->f('logon')),
		'TIME' => $dbu->f('app_duration') / 3600,
		'COLOR' => $_SESSION['colors'][$i] 
	));
	$i++;
}
$ftx->assign('TOPONLINE_CHART',drawGraph($toponlinechart));
	
	//	modified for pdf	--->
	$export_header = get_export_header($_SESSION['filters']['f']);
	extract($export_header,EXTR_OVERWRITE);
	$ftx->assign(array(
		'PDF_HEADER' => pdf_header(),
		'PDF_HIDE' => pdf_hide(),
		'PDF_CLASS' => pdf_class(),
		'TITLE' => $ftx->lookup('Top Online'),
		'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
		'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
	));
	//	<---	modified for pdf

$ftx->parse('CONTENT','main');

	//	modified for pdf	--->
	if($_REQUEST['render'] == 'pdf'){
		$page = 'toponline';
		$html = $ftx->fetch('CONTENT');
			file_put_contents(CURRENT_VERSION_FOLDER.'temp_pdf/'.$page.'.html',"\xEF\xBB\xBF" . $html);
		loadPDF($page,'inline');exit;
	} else {
		return $ftx->fetch('CONTENT');
	}
	//	<---	modified for pdf