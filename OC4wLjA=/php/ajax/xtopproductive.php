<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/

$ftx=new ft(ADMIN_PATH.MODULE."templates/");
$ftx->define(array('main' => "xtopproductive.html"));
$ftx->define_dynamic('template_row','main');
$dbu = new mysql_db();

$filters = get_filters($glob['t'],$glob['f'],$glob['time'],true,true);
extract($filters,EXTR_OVERWRITE);



$users_total = $dbu->query("SELECT SUM(session_application.duration) AS total_time,
									session.member_id
						FROM session_application
						INNER JOIN application ON application.application_id = session_application.application_id
						INNER JOIN session ON session.session_id = session_application.session_id
						".$app_join."
						WHERE session_application.time_type = 0 AND 1=1
						".$app_filter."
						GROUP BY session.member_id");

$total = array();
$total_time = 0;
while ($users_total->next()){
	$total[$users_total->f('member_id')] = $users_total->f('total_time');
	$total_time += $users_total->f('total_time');
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
$session_website = get_session_website_table();
$productivity = $dbu->query("SELECT SUM(session_application.duration) AS app_duration,
						COALESCE(application_productivity.productive,1) AS productive,
						application.application_type AS type_id, 
						application.application_id                               AS application_id,
						member.member_id,
						member.logon,
						member.first_name,
						member.last_name,
						member.alias,
						member.active
						FROM session_application
						INNER JOIN application ON application.application_id = session_application.application_id 
						INNER JOIN session ON session.session_id = session_application.session_id
						".$app_join."
						INNER JOIN application_productivity ON application_productivity.department_id = 1 
														  AND application_productivity.link_id = application.application_id 
													      AND application_productivity.link_type < 3
						WHERE session_application.duration > 0
						AND session_application.time_type = 0
						AND      application.application_type != 3
						AND (productive = 2 OR productive = 3)
						".$app_filter."
						GROUP BY member.member_id, application_id
	union 
			 SELECT     sum(session_" . $session_website . ".duration)               AS app_duration,
						COALESCE(application_productivity.productive,1) AS productive,
						3 AS type_id, 
						domain.domain_id                                AS application_id,
						member.member_id,
						member.logon,
						member.first_name,
						member.last_name,
						member.alias,
						member.active
						FROM session_" . $session_website . "
						INNER JOIN domain ON domain.domain_id = session_" . $session_website . ".domain_id 
						INNER JOIN session ON session.session_id = session_" . $session_website . ".session_id
						".$app_join."
						INNER JOIN application_productivity ON application_productivity.department_id = 1 
														  AND application_productivity.link_id = domain.domain_id 
													      AND application_productivity.link_type = 3
						WHERE session_" . $session_website . ".duration > 0
						AND session_" . $session_website . ".time_type = 0
						AND (productive = 2 OR productive = 3)
                    ".$app_filter."
						GROUP BY member.member_id, application_id");
					
$data = array();
$durations = array();
while ($productivity->next()){
	$duration = 0;
	if(!is_array($data[$productivity->f('member_id')])){
		$data[$productivity->f('member_id')] = array();
	} 
	$duration = $productivity->f('app_duration');
	$data[$productivity->f('member_id')]['name'] = $productivity->f('alias') == 1 ? $productivity->f('first_name').' '.$productivity->f('last_name') : $productivity->f('logon');
	$data[$productivity->f('member_id')]['duration'] += $duration;
	
	$durations[$productivity->f('member_id')] = $data[$productivity->f('member_id')]['duration'];
}
if ($sortcolumns == " ORDER BY app_duration desc ")
{
	arsort($durations);
} elseif ($sortcolumns == " ORDER BY app_duration asc "){
	asort($durations);
}
$i = 0;

foreach ($durations as $member_id => $duration){
	$tags = $data[$member_id];
	$proc = ($tags['duration'] * 100 / $total[$member_id]);
	$ftx->assign(array(
		'NAME' => trialEncrypt($tags['name']),
		'PROCENT' => ($proc > 1) ? number_format($proc,2,',','.') : ' < 1',
		'TOTAL' => format_time($total[$member_id]),
		'TIME' => format_time($tags['duration']),
		'WIDTH' => ceil(($tags['duration'] * 140) / $total_time),
		'COLOR' => $i < 15 ? $_SESSION['colors'][$i] : end($_SESSION['colors']),
	));
	$ftx->parse('TEMPLATE_ROW_OUT','.template_row');
	$i++;
}

if(!$i)
{
	return '';
}

//	**********************************
//	**********************************
//	**********************************	

$productivity = $dbu->query("SELECT SUM(session_application.duration) AS app_duration,
						COALESCE(application_productivity.productive,1) AS productive,
						application.application_type AS type_id, 
						application.application_id                               AS application_id,
						member.member_id,
						member.logon,
						member.first_name,
						member.last_name,
						member.alias,
						member.active
						FROM session_application
						INNER JOIN application ON application.application_id = session_application.application_id 
						INNER JOIN session ON session.session_id = session_application.session_id
						".$app_join."
						INNER JOIN application_productivity ON application_productivity.department_id = 1 
														  AND application_productivity.link_id = application.application_id 
													      AND application_productivity.link_type < 3
						WHERE session_application.duration > 0
						AND session_application.time_type = 0
						AND      application.application_type != 3
						AND (productive = 2 OR productive = 3)
						".$app_filter."
						GROUP BY member.member_id, application_id
	union 
			 SELECT     sum(session_" . $session_website . ".duration)               AS app_duration,
						COALESCE(application_productivity.productive,1) AS productive,
						3 AS type_id, 
						domain.domain_id                                AS application_id,
						member.member_id,
						member.logon,
						member.first_name,
						member.last_name,
						member.alias,
						member.active
						FROM session_" . $session_website . "
						INNER JOIN domain ON domain.domain_id = session_" . $session_website . ".domain_id 
						INNER JOIN session ON session.session_id = session_" . $session_website . ".session_id
						".$app_join."
						INNER JOIN application_productivity ON application_productivity.department_id = 1 
														  AND application_productivity.link_id = domain.domain_id 
													      AND application_productivity.link_type = 3
						WHERE session_" . $session_website . ".duration > 0
						AND session_" . $session_website . ".time_type = 0
						AND (productive = 2 OR productive = 3)
                    ".$app_filter."
						GROUP BY member.member_id, application_id");

$data = array();
$durations = array();
while ($productivity->next()){
	$duration = 0;
	if(!is_array($data[$productivity->f('member_id')])){
		$data[$productivity->f('member_id')] = array();
	} 
	$duration = $productivity->f('app_duration');
	$data[$productivity->f('member_id')]['name'] = $productivity->f('alias') == 1 ? $productivity->f('first_name').' '.$productivity->f('last_name') : $productivity->f('logon');
	$data[$productivity->f('member_id')]['duration'] += $duration;
	
	$durations[$productivity->f('member_id')] = $data[$productivity->f('member_id')]['duration'];
}
arsort($durations);

$i = 0;



$topbarchart = new stdClass;
$topbarchart->settings->container->selector = "topbarchart";
$topbarchart->settings->container->height = "300px";
$topbarchart->settings->container->width = "650px";

$topbarchart->theme = "theme1";
$topbarchart->animationEnabled = pdf_animate();
$topbarchart->interactivityEnabled = true;
$topbarchart->barwidth = 30;
$topbarchart->axisX->labelWrap = true;
$topbarchart->axisX->labelMaxWidth = 100;
$topbarchart->axisX->labelFontSize = 11;
$topbarchart->axisY->labelFontSize = 11;
$topbarchart->axisX->interval = 1;
$topbarchart->axisX->labelAngle = 270;
$topbarchart->axisY->minimum = 0;
$topbarchart->axisY->suffix = "h";
$topbarchart->legend->verticalAlign = "bottom";
$topbarchart->legend->horizontalAlign = "center";

$topbarchart->data[0]->type = "column";

foreach ($durations as $member_id => $duration){
	if($i < 15){
		$tags = $data[$member_id];
			$topbarchart->data[0]->dataPoints[$i]->y = (float)number_format($tags['duration'] / 3600,2,'.',',');
			$topbarchart->data[0]->dataPoints[$i]->toolTipContent = trialEncrypt($tags['name']) . ' - ' . format_time($tags['duration']);
			$topbarchart->data[0]->dataPoints[$i]->label = trialEncrypt($tags['name']);
			$topbarchart->data[0]->dataPoints[$i]->color = "#" . $_SESSION['colors'][$i];
		$ftx->assign(array(
			'NAME' => trialEncrypt($tags['name']),
			'TIME' => $tags['duration'] / 3600,
			'COLOR' => $_SESSION['colors'][$i] 
		));
	}
	$i++;
}
$ftx->assign('TOPBAR_CHART',drawGraph($topbarchart));
	
	//	modified for pdf	--->
	$export_header = get_export_header($_SESSION['filters']['f']);
	extract($export_header,EXTR_OVERWRITE);
	$ftx->assign(array(
		'PDF_HEADER' => pdf_header(),
		'PDF_HIDE' => pdf_hide(),
		'PDF_CLASS' => pdf_class(),
		'TITLE' => $ftx->lookup('Top Productive'),
		'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
		'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
	));
	//	<---	modified for pdf

$ftx->parse('CONTENT','main');

	//	modified for pdf	--->
	if($_REQUEST['render'] == 'pdf'){
		$page = 'topproductive';
		$html = $ftx->fetch('CONTENT');
			file_put_contents(CURRENT_VERSION_FOLDER.'temp_pdf/'.$page.'.html',"\xEF\xBB\xBF" . $html);
		loadPDF($page,'inline');exit;
	} else {
		return $ftx->fetch('CONTENT');
	}
	//	<---	modified for pdf