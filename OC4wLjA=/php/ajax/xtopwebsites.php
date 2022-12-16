<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftx=new ft(ADMIN_PATH.MODULE."templates/");
$ftx->define(array('main' => "xtopwebsites.html"));
$ftx->define_dynamic('template_row','main');
$ftx->define_dynamic('other_row','main');

$dbu = new mysql_db();

$filters = get_filters($glob['t'],$glob['f'],$glob['time'],true);
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

$session_website = get_session_website_table();
$session = $dbu->row("SELECT SUM(session_" . $session_website . ".duration) AS duration FROM session_" . $session_website . " 
INNER JOIN session ON session.session_id = session_" . $session_website . ".session_id
INNER JOIN domain ON session_" . $session_website . ".domain_id = domain.domain_id
".$app_join." WHERE 1=1
". $app_filter);

$total = $session['duration'];

$website = $dbu->query("SELECT SUM(session_" . $session_website . ".duration) as website_duration,
domain.domain,
session_" . $session_website . ".domain_id
FROM session_" . $session_website . "
INNER JOIN domain ON domain.domain_id = session_" . $session_website . ".domain_id
INNER JOIN session ON session.session_id = session_" . $session_website . ".session_id
".$app_join."
WHERE 1=1 ".$app_filter." AND session_" . $session_website . ".duration > 0
GROUP BY session_" . $session_website . ".domain_id
ORDER BY website_duration desc ".$number_of_rows);




$i = 0;
$total_top_duration = 0;
$total_top_procent = 0;

while ( $i < 15 && $website->next()){
	
	$proc = ($website->f('website_duration') * 100 / $total);
	
	$dbu->query("SELECT SUM(session_" . $session_website . ".duration) as website_duration, member.logon,
	member.alias,
	CONCAT(member.first_name,' ',member.last_name) AS member_name FROM session_" . $session_website . " 
INNER JOIN session ON session.session_id = session_" . $session_website . ".session_id
INNER JOIN domain ON session_" . $session_website . ".domain_id = domain.domain_id
".$app_join."
WHERE session_" . $session_website . ".duration > 0 AND session_" . $session_website . ".domain_id = '".$website->f('domain_id')."'
".$app_filter."
GROUP BY member.member_id
ORDER BY website_duration desc");
	$user = '';
	$usercount = 0;
	while ($dbu->move_next()) {
		$logon = trialEncrypt($dbu->f('alias') == 1 ? $dbu->f('member_name') : $dbu->f('logon'));
		$user .= $usercount + 1 . ') ' . $logon.' - '.format_time($dbu->f('website_duration')).'<br/>';
		$usercount++;
	}
	
	$ftx->assign(array(
		'NAME' => $website->f('domain') . ' (' . $usercount . ')',
		'USER' => $user,
		'PROCENT' => ($proc > 1) ? number_format($proc,2,',','.') : ' < 1',
		'TIME' => format_time($website->f('website_duration')),
		'WIDTH' => ceil(($website->f('website_duration') * 140) / $total),
		'COLOR' => $_SESSION['colors'][$i] 
	));
	
	$total_top_duration += $website->f('website_duration');
	$total_top_procent += number_format($proc,2);
	
	$ftx->parse('TEMPLATE_ROW_OUT','.template_row');
	$i++;
}


$j = 0;

$total_other_duration = 0;
$total_other_procent = 0;

while ($website->next()) {
	
	$proc = ($website->f('website_duration') * 100 / $total);
	
	$dbu->query("SELECT SUM(session_" . $session_website . ".duration) as website_duration, member.logon,
	member.alias,
	CONCAT(member.first_name,' ',member.last_name) AS member_name FROM session_" . $session_website . " 
INNER JOIN session ON session.session_id = session_" . $session_website . ".session_id
".$app_join."
WHERE session_" . $session_website . ".duration > 0  AND session_" . $session_website . ".domain_id = '".$website->f('domain_id')."'
".$app_filter."
GROUP BY member.member_id
ORDER BY website_duration desc");
	$user = '';
	while ($dbu->move_next()) {
		$logon = trialEncrypt($dbu->f('alias') == 1 ? $dbu->f('member_name') : $dbu->f('logon'));
		$user .= $logon.' - '.format_time($dbu->f('website_duration')).'<br/>';
	}
	
	$ftx->assign(array(
		'OTHER_NAME' => $website->f('domain'),
		'OTHER_USER' => $user,
		'OTHER_PROCENT' => ($proc > 1) ? number_format($proc,2,',','.') : ' < 1',
		'OTHER_TIME' => format_time($website->f('website_duration')),
		'OTHER_WIDTH' => ceil(($website->f('website_duration') * 140) / $total),
		'OTHER_COLOR' => end($_SESSION['colors'])
	));
	
	$total_other_duration += $website->f('website_duration');
	$total_other_procent += number_format($proc,2);
	
	$ftx->parse('OTHER_ROW_OUT','.other_row');
	$j++;
}
//asign the rest
if($total_other_duration){
	$proc = ($total_other_duration * 100 / $total);
	$ftx->assign(array(
		'NAME' => '<a class="toggleother" href="#">[!L!]Others[!/L!]</a>',
		'PROCENT' => number_format($proc,2,'.',','), 
		'TIME' => format_time($total_other_duration), 
		'USER' => '',
		'COLOR' => end($_SESSION['colors'])
	));
	$ftx->parse('TEMPLATE_ROW_OUT','.template_row');
}

// if(!$i)
// {
// 	return '';
// }

//	************************************
//	************************************
//	************************************



$session = $dbu->row("SELECT SUM(session_" . $session_website . ".duration) AS duration FROM session_" . $session_website . " 
INNER JOIN session ON session.session_id = session_" . $session_website . ".session_id
INNER JOIN domain ON session_" . $session_website . ".domain_id = domain.domain_id
".$app_join." WHERE 1=1
". $app_filter);
$total = $session['duration'];


$dbu->query("SELECT SUM(session_" . $session_website . ".duration) as website_duration,
domain.domain,
session_" . $session_website . ".domain_id
FROM session_" . $session_website . "
INNER JOIN domain ON domain.domain_id = session_" . $session_website . ".domain_id
INNER JOIN session ON session.session_id = session_" . $session_website . ".session_id
".$app_join."
WHERE 1=1".$app_filter."
GROUP BY session_" . $session_website . ".domain_id
ORDER BY website_duration DESC");


$i = 0;
$total_top_duration = 0;
$total_top_procent = 0;

$topwebsiteschart = new stdClass;
$topwebsiteschart = array("settings" => array("container" => array("select" => "topwebsiteschart", "height" => "320px", "width" => "685px")));
// $topwebsiteschart->settings->container->selector = "topwebsiteschart";
// $topwebsiteschart->settings->container->height = "320px";
// $topwebsiteschart->settings->container->width = "685px;";

$topwebsiteschart['height'] = 320;	//	ajax fix
$topwebsiteschart['width'] = 685;		//	ajax fix
$topwebsiteschart['theme'] = "theme1";
$topwebsiteschart['animationEnabled'] = pdf_animate();
$topwebsiteschart['interactivityEnabled'] = true;
$topwebsiteschart['axisY']['valueFormatString'] = ' ';
$topwebsiteschart['axisY']['tickLength'] = 0;
$topwebsiteschart['axisY']['margin'] = 80;
$topwebsiteschart['axisX']['margin'] = 80;

$topwebsiteschart['data'][0]['type'] = "pie";
$topwebsiteschart['data'][0]['startAngle'] = -90;
$topwebsiteschart['data'][0]['indexLabelFontColor'] = "#000000";
$topwebsiteschart['data'][0]['toolTipContent'] = "{legendText} - {y}%";

while($i < 15 && $dbu->move_next()){
	$proc = ($dbu->f('website_duration') * 100 / $total);
	$topwebsiteschart['data'][0]['dataPoints'][$i]['y'] = (float)number_format($proc,2,'.',',');
	$topwebsiteschart['data'][0]['dataPoints'][$i]['legendText'] = decode_numericentity($dbu->f('domain'));
	$topwebsiteschart['data'][0]['dataPoints'][$i]['label'] = decode_numericentity($dbu->f('domain'));
	$topwebsiteschart['data'][0]['dataPoints'][$i]['color'] = "#" . $_SESSION['colors'][$i];
	
	$total_top_duration += $dbu->f('website_duration');
	$total_top_procent += number_format($proc,2);
	
	$i++;
}

//asign the rest

if($total != $total_top_duration){
	$proc = (($total-$total_top_duration) * 100 / $total);
	$topwebsiteschart['data'][0]['dataPoints'][$i]['y'] = (float)number_format($proc,2,'.',',');
	$topwebsiteschart['data'][0]['dataPoints'][$i]['legendText'] = $ftx->lookup('Others');
	$topwebsiteschart['data'][0]['dataPoints'][$i]['label'] = $ftx->lookup('Others');
	$topwebsiteschart['data'][0]['dataPoints'][$i]['color'] = "#" . end($_SESSION['colors']);
}
$ftx->assign('TOPWEBSITES_CHART_OUTPUT',drawGraph($topwebsiteschart));
	
	//	modified for pdf	--->
	$export_header = get_export_header($_SESSION['filters']['f']);
	extract($export_header,EXTR_OVERWRITE);
	$ftx->assign(array(
		'PDF_HEADER' => pdf_header(),
		'PDF_HIDE' => pdf_hide(),
		'PDF_CLASS' => pdf_class(),
		'TITLE' => $ftx->lookup('Top Websites'),
		'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
		'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
	));
	//	<---	modified for pdf

$ftx->parse('CONTENT','main');
// var_dump($topwebsiteschart);
	//	modified for pdf	--->
	if($_REQUEST['render'] == 'pdf'){
		$page = 'topwebs';
		$html = $ftx->fetch('CONTENT');
			file_put_contents(CURRENT_VERSION_FOLDER.'temp_pdf/'.$page.'.html',"\xEF\xBB\xBF" . $html);
		loadPDF($page,'inline');exit;
	} else {
		return $ftx->fetch('CONTENT');
	}
	//	<---	modified for pdf