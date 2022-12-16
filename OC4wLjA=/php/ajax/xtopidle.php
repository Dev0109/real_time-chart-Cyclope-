<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftx=new ft(ADMIN_PATH.MODULE."templates/");
$ftx->define(array('main' => "xtopidle.html"));
$ftx->define_dynamic('template_row','main');


$dbu = new mysql_db();

$filters = get_filters($glob['t'],$glob['f'],$glob['time'],true,true);
extract($filters,EXTR_OVERWRITE);

$users_total = $dbu->query("SELECT SUM(session_activity.duration) AS idle_time,member.member_id FROM session_activity 
INNER JOIN session ON session.session_id = session_activity.session_id ".$app_join."
WHERE 1 = 1  AND session_activity.activity_type < 2 ".$app_filter." GROUP by session.member_id");

$total = array();
$total_time = 0;
while ($users_total->next()){
	$total[$users_total->f('member_id')] = $users_total->f('idle_time');
	$total_time += $users_total->f('idle_time');
}

$dbu->query("SELECT SUM(session_activity.duration) AS idle_time,
					member.member_id,
					member.logon,
					member.first_name,
					member.last_name,
					member.alias,
					member.active  FROM session_activity 
INNER JOIN session ON session.session_id = session_activity.session_id ".$app_join."
WHERE 1=1 ".$app_filter." AND session_activity.activity_type = 0 GROUP by session.member_id ORDER BY idle_time DESC");

$i = 0;

while ($dbu->move_next()){
	$proc = ($dbu->f('idle_time') * 100 / $total[$dbu->f('member_id')]);
	$ftx->assign(array(
		'NAME' => trialEncrypt($dbu->f('alias') == 1 ? $dbu->f('first_name').' '.$dbu->f('last_name') : $dbu->f('logon')),
		'PROCENT' => ($proc > 1) ? number_format($proc,2,',','.') : ' < 1',
		'TOTAL' => format_time($total[$dbu->f('member_id')]),
		'TIME' => format_time($dbu->f('idle_time')),
		'WIDTH' => ceil(($dbu->f('idle_time') * 140) / $total_time),
		'COLOR' => $i < 15 ? $_SESSION['colors'][$i] : end($_SESSION['colors']),
	));
	$ftx->parse('TEMPLATE_ROW_OUT','.template_row');
	$i++;
}

if(!$i)
{
	return '';
}

//	*************************************
//	*************************************
//	*************************************


$dbu->query("SELECT SUM(session_activity.duration) AS idle_time,
					member.member_id,
					member.logon,
					member.first_name,
					member.last_name,
					member.alias,
					member.active FROM session_activity 
INNER JOIN session ON session.session_id = session_activity.session_id ".$app_join."
WHERE 1=1 ".$app_filter." AND session_activity.activity_type = 0 GROUP by session.member_id ORDER BY idle_time DESC LIMIT 15");



$topbarchart = new stdClass;
$topbarchart = array("settings" => array("container" => array("select" => "topbarchart", "height" => "300px", "width" => "685px")));
// $topbarchart->settings->container->selector = "topbarchart";
// $topbarchart->settings->container->height = "300px";
// $topbarchart->settings->container->width = "685px";

$topbarchart['height'] = 300;	//	ajax fix
$topbarchart['width'] = 685;		//	ajax fix
$topbarchart['theme'] = "theme1";
$topbarchart['animationEnabled'] = pdf_animate();
$topbarchart['interactivityEnabled'] = true;
$topbarchart['barwidth'] = 30;

$topbarchart['axisX']['labelWrap'] = true;
$topbarchart['axisX']['labelMaxWidth'] = 100;
$topbarchart['axisX']['labelFontSize'] = 11;
$topbarchart['axisX']['labelAngle'] = 270;
$topbarchart['axisX']['interval'] = 1;

$topbarchart['axisY']['labelFontSize'] = 11;
$topbarchart['axisY']['interval'] = 1;
$topbarchart['axisY']['minimum'] = 0;
$topbarchart['axisY']['suffix'] = "h";
$topbarchart['legend']['verticalAlign'] = "bottom";
$topbarchart['legend']['horizontalAlign'] = "center";


$topbarchart['data'][0]['type'] = "column";
$i = 0;
while ($dbu->move_next() && $i < 15){
			$topbarchart['data'][0]['dataPoints'][$i]['y'] = (float)number_format($dbu->f('idle_time') / 3600,2,'.',',');
			$topbarchart['data'][0]['dataPoints'][$i]['toolTipContent'] = trialEncrypt($dbu->f('alias') == 1 ? decode_numericentity($dbu->f('first_name')).' '.decode_numericentity($dbu->f('last_name')) :decode_numericentity($dbu->f('logon'))) . ' - ' . format_time($dbu->f('idle_time'));
			$topbarchart['data'][0]['dataPoints'][$i]['label'] = trialEncrypt($dbu->f('alias') == 1 ? decode_numericentity($dbu->f('first_name')).' '.decode_numericentity($dbu->f('last_name')) :decode_numericentity($dbu->f('logon')));
			$topbarchart['data'][0]['dataPoints'][$i]['color'] = "#" . $_SESSION['colors'][$i];
	$ftx->assign(array(
		'NAME' => trialEncrypt($dbu->f('alias') == 1 ? decode_numericentity($dbu->f('first_name')).' '.decode_numericentity($dbu->f('last_name')) :decode_numericentity($dbu->f('logon'))),
		'TIME' => $dbu->f('idle_time') / 3600,
		'COLOR' => $_SESSION['colors'][$i] 
	));
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
		'TITLE' => $ftx->lookup('Top Idle'),
		'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
		'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
	));
	//	<---	modified for pdf

$ftx->parse('CONTENT','main');

	//	modified for pdf	--->
	if($_REQUEST['render'] == 'pdf'){
		$page = 'topidle';
		$html = $ftx->fetch('CONTENT');
			file_put_contents(CURRENT_VERSION_FOLDER.'temp_pdf/'.$page.'.html',"\xEF\xBB\xBF" . $html);
		loadPDF($page,'inline');exit;
	} else {
		return $ftx->fetch('CONTENT');
	}
	//	<---	modified for pdf