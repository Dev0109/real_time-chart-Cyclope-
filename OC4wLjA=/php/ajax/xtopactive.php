<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftx=new ft(ADMIN_PATH.MODULE."templates/");
$ftx->define(array('main' => "xtopactive.html"));
$ftx->define_dynamic('template_row','main');

$filters = get_filters($glob['t'],$glob['f'],$glob['time'],true,true);
extract($filters,EXTR_OVERWRITE);

if(($_SESSION['NUMBER_OF_ROWS']) && (is_numeric($_SESSION['NUMBER_OF_ROWS']))){
	$rowcount =  $_SESSION['NUMBER_OF_ROWS'];
	$number_of_rows =  "LIMIT 0,".$rowcount;
} else {
	$rowcount =  500;
	$number_of_rows =  "";
}

	//	modified for pdf	--->
	if($_REQUEST['render'] == 'pdf'){
		$l_r = $rowcount;
	}
if($_REQUEST['render'] == 'pdf' && $_REQUEST['send'] == 'email'){
	$rowcount = get_email_rowcount();
	$l_r = $rowcount;
	$number_of_rows =  "LIMIT 0,".$rowcount;
}
	//	<---	modified for pdf

$users_total = $dbu->query("SELECT SUM(session_activity.duration) AS active_time,member.member_id FROM session_activity 
INNER JOIN session ON session.session_id = session_activity.session_id ".$app_join."
WHERE 1=1 AND session_activity.activity_type < 2 ".$app_filter." GROUP by session.member_id");

$total = array();
$total_time = 0;

while ($users_total->next()){
	$total[$users_total->f('member_id')] = $users_total->f('active_time');
	$total_time += $users_total->f('active_time');
}

//	lorand
$sortable_columns = array(
	'active_time',
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

$dbu->query("SELECT SUM(session_activity.duration) AS active_time,
					member.member_id,
					member.logon,
					member.first_name,
					member.last_name,
					member.alias,
					member.active FROM session_activity 
INNER JOIN session ON session.session_id = session_activity.session_id ".$app_join."
WHERE 1=1 ".$app_filter." AND session_activity.activity_type = 1 GROUP by session.member_id " . $sortcolumns . " ".$number_of_rows);

$i = 0;

while ($dbu->move_next()){	
	$proc = ($dbu->f('active_time') * 100 / $total[$dbu->f('member_id')]);
	$ftx->assign(array(
		'NAME' => trialEncrypt($dbu->f('alias') == 1 ? $dbu->f('first_name').' '.$dbu->f('last_name') : $dbu->f('logon')),
		'PROCENT' => ($proc > 1) ? number_format($proc,2,',','.') : ' < 1',
		'TOTAL' => format_time($total[$dbu->f('member_id')]),
		'TIME' => format_time($dbu->f('active_time')),
		'WIDTH' => ceil(($dbu->f('active_time') * 140) / $total_time),
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

$dbu->query("SELECT SUM(session_activity.duration) AS active_time,
					member.member_id,
					member.logon,
					member.first_name,
					member.last_name,
					member.active,
					member.alias
					FROM session_activity 
INNER JOIN session ON session.session_id = session_activity.session_id ".$app_join."
WHERE 1=1 ".$app_filter." AND session_activity.activity_type = 1 GROUP by session.member_id ORDER BY active_time DESC LIMIT 15");

$topbarchartinactiv = new stdClass;
$topbarchartinactiv = array("settings" => array("container" => array("select" => "topbarchartinactiv", "height" => "300px", "width" => "685px")));

// $topbarchartinactiv->settings->container->selector = "topbarchartinactiv";
// $topbarchartinactiv->settings->container->height = "300px";
// $topbarchartinactiv->settings->container->width = "685px";

$topbarchartinactiv['height'] = 300;	//	ajax fix
$topbarchartinactiv['width'] = 685;		//	ajax fix
$topbarchartinactiv['theme'] = "theme1";
$topbarchartinactiv['animationEnabled'] = pdf_animate();
$topbarchartinactiv['interactivityEnabled'] = true;
$topbarchartinactiv['barwidth'] = 30;

$topbarchartinactiv['axisX']['labelWrap'] = true;
$topbarchartinactiv['axisX']['labelMaxWidth'] = 100;
$topbarchartinactiv['axisX']['labelFontSize'] = 11;
$topbarchartinactiv['axisX']['labelAngle'] = 270;
$topbarchartinactiv['axisX']['interval'] = 1;

$topbarchartinactiv['axisY']['labelFontSize'] = 11;
$topbarchartinactiv['axisY']['interval'] = 1;
$topbarchartinactiv['axisY']['minimum'] = 0;
$topbarchartinactiv['axisY']['suffix'] = "h";
$topbarchartinactiv['legend']['verticalAlign'] = "bottom";
$topbarchartinactiv['legend']['horizontalAlign'] = "center";

$topbarchartinactiv['data'][0]['type'] = "column";

$i = 0;
while ($dbu->move_next() && $i < 15){
			$topbarchartinactiv['data'][0]['dataPoints'][$i]['y'] = (float)number_format($dbu->f('active_time') / 3600,2,'.',',');
			$topbarchartinactiv['data'][0]['dataPoints'][$i]['toolTipContent'] = trialEncrypt($dbu->f('alias') == 1 ? decode_numericentity($dbu->f('first_name')).' '.decode_numericentity($dbu->f('last_name')) :decode_numericentity($dbu->f('logon'))) . ' - ' . format_time($dbu->f('active_time'));
			$topbarchartinactiv['data'][0]['dataPoints'][$i]['label'] = trialEncrypt($dbu->f('alias') == 1 ? decode_numericentity($dbu->f('first_name')).' '.decode_numericentity($dbu->f('last_name')) : decode_numericentity($dbu->f('logon')));
			$topbarchartinactiv['data'][0]['dataPoints'][$i]['color'] = "#" . $_SESSION['colors'][$i];
	$ftx->assign(array(
		'NAME' => trialEncrypt($dbu->f('alias') == 1 ? decode_numericentity($dbu->f('first_name')).' '.decode_numericentity($dbu->f('last_name')) : decode_numericentity($dbu->f('logon'))),
		'TIME' => $dbu->f('active_time') / 3600,
		'COLOR' => $_SESSION['colors'][$i] 
	));
	$i++;
}
$ftx->assign('TOPBAR_CHART',drawGraph($topbarchartinactiv));
	
	//	modified for pdf	--->
	$export_header = get_export_header($_SESSION['filters']['f']);
	extract($export_header,EXTR_OVERWRITE);
	$ftx->assign(array(
		'PDF_HEADER' => pdf_header(),
		'PDF_HIDE' => pdf_hide(),
		'PDF_CLASS' => pdf_class(),
		'TITLE' => $ftx->lookup('Top Active'),
		'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
		'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
	));
	//	<---	modified for pdf


$ftx->parse('CONTENT','main');

	//	modified for pdf	--->
	if($_REQUEST['render'] == 'pdf'){
		$page = 'topactive';
		$html = $ftx->fetch('CONTENT');
			file_put_contents(CURRENT_VERSION_FOLDER.'temp_pdf/'.$page.'.html',"\xEF\xBB\xBF" . $html);
		loadPDF($page,'inline');exit;
	} else {
		return $ftx->fetch('CONTENT');
	}
	//	<---	modified for pdf