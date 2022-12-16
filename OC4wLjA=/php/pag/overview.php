<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => "overview.html"));

$dbu = new mysql_db();
//clear tmplog
$today_start = mktime(0,0,0);
//$dbu->query("DELETE FROM tmplog WHERE arrival_date < ".$today_start." AND parsed = 1");

if($glob['wonly']){
	$glob['time']['type'] = 3;
}

// $ft->assign('APP_USAGE',include(CURRENT_VERSION_FOLDER.'php/ajax/xappusage.php'));
include(CURRENT_VERSION_FOLDER.'php/ajax/xstats.php');
// include(CURRENT_VERSION_FOLDER.'misc/populate_db.php');
$filters = get_filters($glob['t'],$glob['f'],$glob['time']);
extract($filters,EXTR_OVERWRITE);

$dates = $dbu->row("SELECT MIN(date) AS genesis,MAX(date) AS last_day_on_earth FROM session ".$total_join.' WHERE 1=1 '.$total_filter);
$monitored = $dbu->row("SELECT count(member.member_id) AS members_number
									FROM member 
									INNER JOIN computer2member ON computer2member.member_id = member.member_id
									INNER JOIN computer ON computer.computer_id = computer2member.computer_id
									WHERE member.active != 3 AND member.department_id != 0
										AND computer2member.last_record > (" . time() . " - (computer.connectivity * 60 * 2)) ");
$monitored_all = $dbu->row("SELECT count(member.member_id) AS members_number
									FROM member 
									INNER JOIN computer2member ON computer2member.member_id = member.member_id
									INNER JOIN computer ON computer.computer_id = computer2member.computer_id
									WHERE member.active != 3 AND member.department_id != 0");

//ALEX - START ANCOM level idle time with Attendance idle time

if (strpos($glob['f'],'c') !== false || (strpos($glob['f'],'u') !== false && $filterCount == 3)){

	$onlycompfilter = '';
}else {
	$onlycompfilter = '
INNER JOIN computer2member ON computer2member.member_id = member.member_id
INNER JOIN computer ON computer.computer_id = session.computer_id';
}

$_SESSION['first_last_only'] = 1;
$secondary_condition = "AND session_activity.activity_type = 1 AND session_attendance.active = 1";

$sortable_columns = array(
	'session_attendance.start_time',
	'member.logon',
	'session_attendance.start_time',
	);
$sortcolumns = get_sorting($sortable_columns,'','desc');

$dbu->query("SELECT MIN(session_attendance.start_time) AS start_work,
					MAX(session_attendance.end_time) AS end_work
			FROM session_activity
			INNER JOIN session ON session.session_id = session_activity.session_id
			".$app_join."
			INNER JOIN session_attendance ON session_attendance.session_id = session_activity.session_id
			".$onlycompfilter."
			WHERE 1=1
			".$secondary_condition."
			AND session_attendance.start_time >= session.date
			".$app_filter."
			GROUP BY member.member_id,session_activity.session_id
			" . $sortcolumns . " ");
$ttl_total = 0;

while ($dbu->move_next()){
	$ttl_total = $ttl_total + ($dbu->f('end_work') - $dbu->f('start_work'));
	/*print "<pre>";
	print_r("<br/>");
	print "</pre>";*/
}

$total_time = $ttl_total;
$new_idle = $total_time - $glob['stats_active'];
$glob['stats_idle'] = $new_idle;

//ALEX - END ANCOM level idle time with Attendance idle time

//$total_time = $glob['stats_active'] + $glob['stats_idle'];

$export_header = get_export_header($glob['f']);
extract($export_header,EXTR_OVERWRITE);
$glob['append'] = trialEncrypt($member_name);
$ft->assign(array(
	'PAGE_TITLE' => $ft->lookup('Overview for'),
	'APPEND' => $glob['append'],
	'MESSAGE' => get_error($glob['error']),

	'MONITOR_COUNT' => $monitored['members_number'],
	'MONITOR_ALL_COUNT' => $monitored_all['members_number'],
	
	'ALERT_NUMBER' => get_error($glob['error']),
	
	'TOTAL' => format_time($glob['stats_active'] + $glob['stats_idle'],false),
	'ACTIVE' => format_time($glob['stats_active'],false),
	'IDLE' => format_time($glob['stats_idle'],false),
	'ONLINE' => format_time($glob['stats_online'],false),
	
	'ACTIVE_PROC' => $total_time ? number_format($glob['stats_active'] * 100 / $total_time,2) : 0,
	'IDLE_PROC' => $total_time ? number_format($glob['stats_idle'] * 100 / $total_time,2) : 0,
	'ONLINE_PROC' => $total_time ? number_format($glob['stats_online'] * 100 / $total_time,2) : 0,
	
	'DEFAULT_VALUE' => isset($glob['time']) ? $glob['time']['time'] : date('n/j/Y',$dates['genesis']).' - '.date('n/j/Y',$dates['last_day_on_earth']) ,
	'DATE_BEFORE' => date('n/j/Y',$dates['genesis']),
	'TIME_'.($glob['time']['type'] ? $glob['time']['type'] : 1) => 'selected="selected"',
	'HELP_LINK' => 'help.php?pag=overview',
));

$timechart = new stdClass; 
$timechart = array( "settings" => array( "container" => array("selector" => "timechart", "height" => "274px", "width" => "274px")), "theme" => "theme1");
$timechart['animationEnabled'] = pdf_animate();
$timechart["interactivityEnabled"] = true;
$timechart["axisY"]["valueFormatString"] = ' ';

$timechart["axisY"]["tickLength"] = 0;
$timechart["axisY"]["margin"] = 80;
$timechart["axisX"]["valueFormatString"] = ' ';
$timechart["axisX"]["tickLength"] = 0;
$timechart["axisX"]["margin"] = 80;
$test_total = $total_time;
// var_dump($total_time);
// die();
// $test_y = (float)$total_time ? number_format($glob['stats_active'] * 100 / $total_time,2) : 0;
// $test = array(array("y"=> (float)$total_time ? number_format($glob['stats_idle'] * 100 / $total_time,2) : 0, "legendText" =>  $ft->lookup('Idle'), "color" => "#eaeaea"),  array("y"=> (float)$total_time ? number_format($glob['stats_active'] * 100 / $total_time,2) : 0, "legendText" =>  $ft->lookup('Active'), "color" => "#55a099"));

// $timechart['data'] = array(array("type" => "doughnut", "startAngle" => -90, "toolTipContent"=> "{legendText} - {y}%", "dataPoints" => array(array("y"=> (float)$total_time ? number_format($glob['stats_idle'] * 100 / $total_time,2) : 0, "legendText" =>  $ft->lookup('Idle'), "color" => "#eaeaea"),  array("y"=> (float)$total_time ? number_format($glob['stats_active'] * 100 / $total_time,2) : 0, "legendText" =>  $ft->lookup('Active'), "color" => "#55a099"))));

// $timechart['data'][0]['startAngle'] = -90;
// $timechart['data'][0]['toolTipContent'] = "{legendText} - {y}%";
// $timechart['data'][0]['dataPoints[0]']['y'] = (float)$total_time ? number_format($glob['stats_idle'] * 100 / $total_time,2) : 0;
// $timechart['data'][0]['dataPoints[0]']['legendText'] = $ft->lookup('Idle');
// $timechart['data'][0]['dataPoints[0]']['color'] = " #eaeaea";
// $timechart['data'][0]['dataPoints[1]']['y'] = (float)$total_time ? number_format($glob['stats_active'] * 100 / $total_time,2) : 0;
// $timechart['data'][0]['dataPoints[1]']['legendText'] = $ft->lookup('Active');
// $timechart['data'][0]['dataPoints[1]']['color'] = "#55a099";

$timechart['data'][0]['type'] ="doughnut";
$timechart['data'][0]['startAngle'] = -90;
$timechart['data'][0]['toolTipContent'] = "{legendText} - {y}%";
$timechart['data'][0]['dataPoints'][0]['y'] = (float)$total_time ? number_format($glob['stats_idle'] * 100 / $total_time,2) : 0;
$timechart['data'][0]['dataPoints'][0]['legendText'] = $ft->lookup('Idle');
$timechart['data'][0]['dataPoints'][0]['color'] = " #eaeaea";
$timechart['data'][0]['dataPoints'][1]['y'] = (float)$total_time ? number_format($glob['stats_active'] * 100 / $total_time,2) : 0;
$timechart['data'][0]['dataPoints'][1]['legendText'] = $ft->lookup('Active');
$timechart['data'][0]['dataPoints'][1]['color'] = "#55a099";


 $ft->assign('TIME_OUTPUT',drawGraph($timechart));
	$monichart = new stdClass;
	$monichart = array( "settings" => array( "container" => array("selector" => "monichart", "height" => "140px", "width" => "265px")), "theme" => "theme1" );
	// $monichart['settings']['container']['selector'] = "monichart";
	// $monichart['settings']['container']['height'] = "140px";
	// $monichart['settings']['container']['width'] = "265px";

	// $monichart['theme'] = "theme1";
	$monichart['animationEnabled'] = pdf_animate();
	$monichart['interactivityEnabled'] = true;
	$monichart['axisY']['valueFormatString'] = ' ';
	$monichart['axisY']['tickLength'] = 0;
	$monichart['axisX']['valueFormatString'] = ' ';
	$monichart['axisX']['tickLength'] = 0;

	// $monichart['data[0]']['type'] = "doughnut";
	// $monichart['data[0]']['startAngle'] = -90;
	// $monichart['data[0]']['startAngle'] = -90;
	// $monichart['data[0]']['toolTipContent'] = "{legendText}: {y}";
	// $monichart['data[0]']['dataPoints[0]']['y'] = (int)$monitored_all['members_number'] - $monitored['members_number'];
	// $monichart['data[0]']['dataPoints[0]']['legendText'] = $ft->lookup('Offline');
	// $monichart['data[0]']['dataPoints[0]']['color'] = "#E0E0E2";
	// $monichart['data[0]']['dataPoints[1]']['y'] = (int)$monitored['members_number'];
	// $monichart['data[0]']['dataPoints[1]']['legendText'] = $ft->lookup('Monitored Users');
	// $monichart['data[0]']['dataPoints[1]']['color'] = "#FABB46";


	$monichart['data'][0]['type'] = "doughnut";
	$monichart['data'][0]['startAngle'] = -90;
	$monichart['data'][0]['startAngle'] = -90;
	$monichart['data'][0]['toolTipContent'] = "{legendText}: {y}";
	$monichart['data'][0]['dataPoints'][0]['y'] = (int)$monitored_all['members_number'] - $monitored['members_number'];
	$monichart['data'][0]['dataPoints'][0]['legendText'] = $ft->lookup('Offline');
	$monichart['data'][0]['dataPoints'][0]['color'] = "#E0E0E2";
	$monichart['data'][0]['dataPoints'][1]['y'] = (int)$monitored['members_number'];
	$monichart['data'][0]['dataPoints'][1]['legendText'] = $ft->lookup('Monitored Users');
	$monichart['data'][0]['dataPoints'][1]['color'] = "#FABB46";

//  $ft->assign('MONITORED_OUTPUT', drawGraph($monichart));

//	**************************************************************************************
//	*************** day summary **********************************************************
//	**************************************************************************************


$matches = array(); 
preg_match_all('!([0-9]{1,2}/[0-9]{1,2}/[0-9]{4})( [0-9]+\:[0-9]+ [AM|PM]+)?!',$glob['time']['time'],$matches);
$matches = array_shift($matches);
$start = strtotime($matches[0]);
$end = strtotime($matches[1]);

if (!$end) {
	$days= 1;
}
else 
{
	$days = ( $end - $start ) / 86400;
	$days++;
}

$export_header = get_export_header($_SESSION['filters']['f']);
extract($export_header,EXTR_OVERWRITE);

$active = $dbu->query("SELECT 	SUM(session_activity.duration) AS duration,
								session_activity.hour,
								session_activity.day 
								FROM session_activity
								INNER JOIN session ON session.session_id = session_activity.session_id
								".$app_join."
								WHERE session_activity.activity_type = 1
								".$app_filter."
								GROUP BY session_activity.hour");
while ($active->next()){
	if(!is_array($data[$active->f('hour')])){
		$data[$active->f('hour')] = array('private'=>0,'private_format' => 0,
										  'active'=>0,'active_format' => 0,
										  'idle'=>0,'idle_format' =>0);
	}
	$data[$active->f('hour')]['active'] = ($active->f('duration') * 100) / (3600 * $days * $members );
	$data[$active->f('hour')]['active_format'] = format_time($active->f('duration'));// / (60 * $active->f('days'));
}

$idle = $dbu->query("SELECT SUM(session_activity.duration) AS duration,
							session_activity.hour,
							session_activity.day 
							FROM session_activity
							INNER JOIN session ON session.session_id = session_activity.session_id
							".$app_join."
							WHERE session_activity.activity_type = 0
							".$app_filter."
							GROUP BY session_activity.hour");

while ($idle->next()){
	if(!is_array($data[$idle->f('hour')])){
		$data[$idle->f('hour')] = array('private'=>0,'private_format' => 0,
										'active'=>0,'active_format' => 0,
										'idle'=>0,'idle_format' =>0);
	}
	
	$data[$idle->f('hour')]['idle'] = ($idle->f('duration') * 100)/ (3600 * $days * $members);
	$data[$idle->f('hour')]['idle_format'] = format_time($idle->f('duration'));
}

$private = $dbu->query("SELECT SUM(session_activity.duration) AS duration,
							session_activity.hour,
							session_activity.day 
							FROM session_activity
							INNER JOIN session ON session.session_id = session_activity.session_id
							".$app_join."
							WHERE session_activity.activity_type > 1
							".$app_filter."
							GROUP BY session_activity.hour");

while ($private->next()){
	if(!is_array($data[$private->f('hour')])){
		$data[$private->f('hour')] = array('private'=>0,'private_format' => 0,
										'active'=>0,'active_format' => 0,
										'idle'=>0,'idle_format' =>0);
	}
	
	$data[$private->f('hour')]['private'] = ($private->f('duration') * 100)/ (3600 * $days * $members);
	$data[$private->f('hour')]['private_format'] = format_time($private->f('duration'));
}

// $color_active = "#f40140";
// $color_idle = "#eaeaea";
// $color_private = "#ffffff";

 $color_active = "#55a099";
 $color_idle = "#55a09966";
 $color_private = "#DDDDDD";

for($i = 0; $i< 24; $i++){
	
	$total = $data[$i]['active'] + $data[$i]['idle'] + $data[$i]['private'];
	$diff = 0;
	
	if($total > 100){
		$diff = $total - 100;
		//now find the biggest one
		$max = $data[$i]['active'];
		$field = 'active';
		if($max < $data[$i]['idle']){
			$max = $data[$i]['idle'];
			$field = 'idle';
		}
		if($max < $data[$i]['private']){
			$max = $data[$i]['private'];
			$field = 'private';
		}
		$data[$i][$field] -=  $diff;
		if($data[$i][$field] < 0){
			$data[$i][$field]=$data[$i][$field]*(-1);
		}
	}

	$daysummary['active[$i]'] =		$data[$i]['active'] ? $data[$i]['active'] : 0;
	$daysummary['idle[$i]'] =		$data[$i]['idle'] ? $data[$i]['idle'] : 0;
	$daysummary['private[$i]'] =	$data[$i]['private'] ? $data[$i]['private'] : 0;
}

$daysummarychart = new stdClass;

$daysummarychart = array( "settings" => array( "container" => array("selector" => "daysummarychart", "height" => "280px", "width" => "100%")));

// $daysummarychart->settings->container->selector = "daysummarychart";
// $daysummarychart->settings->container->height = "280px";
// $daysummarychart->settings->container->width = "100%";
$daysummarychart['barwidth'] = 15;
$daysummarychart['axisY']['maximum'] = 100;
$daysummarychart['axisX']['labelFontSize'] = 12;
$daysummarychart['axisY']['interval'] = 25;

$daysummarychart['axisY']['labelFontSize'] = 15;
$daysummarychart['axisX']['interval'] = 1;
$daysummarychart['legend']['fontSize'] = 15;

$daysummarychart['data'][0]['type'] = "stackedColumn";
$daysummarychart['data'][0]['showInLegend'] = true;
$daysummarychart['data'][0]['legendText'] = $ft->lookup('Active');
$daysummarychart['data'][0]['color'] = $color_active;
$daysummarychart['data'][0]['toolTipContent'] = $ft->lookup('Active') . ": {y}%";

	foreach($daysummary['active'] as $k=>$v){
		$daysummarychart['data'][0]['dataPoints'][$k]['y'] = intval($v);
		$daysummarychart['data'][0]['dataPoints'][$k]['label'] = $k;

	}

$daysummarychart['data'][1]['type'] = "stackedColumn";
$daysummarychart['data'][1]['showInLegend'] = true;
$daysummarychart['data'][1]['legendText'] = $ft->lookup('Idle');
$daysummarychart['data'][1]['color'] = $color_idle;
$daysummarychart['data'][1]['toolTipContent'] = $ft->lookup('Idle') . ": {y}%";

	foreach($daysummary['idle'] as $k=>$v){
		$daysummarychart['data'][1]['dataPoints'][$k]['y'] = intval($v);
		$daysummarychart['data'][1]['dataPoints'][$k]['label'] = $k;
	}

$daysummarychart['data'][2]['type'] = "stackedColumn";
$daysummarychart['data'][2]['showInLegend'] = true;
$daysummarychart['data'][2]['legendText'] = $ft->lookup('Private');
$daysummarychart['data'][2]['color'] = $color_private;
$daysummarychart['data'][2]['toolTipContent'] = $ft->lookup('Private') . ": {y}%";

	foreach($daysummary['private'] as $k=>$v){
		$daysummarychart['data'][2]['dataPoints'][$k]['y'] = intval(100 - $daysummarychart['data'][0]['dataPoints'][$k]['y'] - $daysummarychart['data'][1]['dataPoints'][$k]['y']);
		$daysummarychart['data'][2]['dataPoints'][$k]['label'] = $k;
	}

$ft->assign('DAYSUMMARY_OUTPUT',drawGraph($daysummarychart));
//=============================================================================================
//=============================================================================================

//	**************************************************************************************
//	*************** top applications *****************************************************
//	**************************************************************************************

$session = $dbu->row("SELECT SUM(session_application.duration) AS duration,session.session_id FROM session_application
INNER JOIN session ON session.session_id = session_application.session_id
".$app_join."  WHERE 1 = 1  AND session_application.time_type = 0 ". $app_filter);
$total = $session['duration'];

//get top 6 apps
//desi par multe joinuri din cauza indexurilor ar trebui sa mearga foarte repede, este doar un mic file sort, 
//using temporary pe table de member sau computer cea ce e ok..ptr ca alea sunt cele mai mici tabele oricum
$dbu->query("SELECT * FROM(SELECT SUM(session_application.duration) as app_duration,application.description as name FROM session_application 
INNER JOIN application ON application.application_id = session_application.application_id
INNER JOIN session ON session.session_id = session_application.session_id
".$app_join."
WHERE session_application.duration > 0 AND 1= 1 AND session_application.time_type = 0
".$app_filter."
GROUP BY session_application.application_id
ORDER BY app_duration desc
LIMIT 0,5) as app_duration
ORDER BY app_duration asc");
$i = 0;
$tot = 0;

$topapps = new stdClass;
$topapps = array( "settings" => array( "container" => array("selector" => "topapps", "height" => "280px", "width" => "100%")), "theme" => "theme1" );
// $topapps->settings->container->selector = "topapps";
// $topapps->settings->container->height = "280px";
// $topapps->settings->container->width = "100%";


// $topapps->theme = "theme1";
$topapps['animationEnabled'] = pdf_animate();
$topapps['barwidth'] = 25;
$topapps['interactivityEnabled'] = true;
$topapps['axisX']['labelMaxWidth'] = 120;
$topapps['axisX']['labelFontSize'] = 16;
$topapps['axisY']['labelFontSize'] = 15;
$topapps['axisX']['labelFontColor'] = "#343a41";
//$topapps->axisX->labelFontWeight = "bold";
$topapps['axisX']['labelFontFamily']="Avinira-regular";
$topapps['axisy']['labelFontFamily']="Avinira-regular";

$topapps['data'][0]['type'] = "bar";
$topapps['data'][0]['color'] = "#343a41";
$topapps['data'][0]['toolTipContent'] = "{legendText} - {y}%";

while ($dbu->move_next() && $i < 5){
	$proc = ($dbu->f('app_duration') * 100 / $total);
    
    $topapps['data'][0]['dataPoints'][0]['color'] = "#f39c12";
	$topapps['data'][0]['dataPoints'][1]['color'] = "#3498db";
	$topapps['data'][0]['dataPoints'][2]['color'] = "#2ecc71";
	$topapps['data'][0]['dataPoints'][3]['color'] = "#f1c40f";
	$topapps['data'][0]['dataPoints'][4]['color'] = "#e74c3c";

	
	
	$topapps['data'][0]['dataPoints'][$i]['y'] = intval($proc);
	$topapps['data'][0]['dataPoints'][$i]['label'] = trim_text(decode_numericentity($dbu->f('name')),10);
	$topapps['data'][0]['dataPoints'][$i]['legendText'] = decode_numericentity($dbu->f('name'));
	$ft->assign(array(
		'NAME' => decode_numericentity($dbu->f('name')),
		'SHORTNAME' => trim_text(decode_numericentity($dbu->f('name')),25),
		'PROCENT' => number_format($proc,2,'.',','), 
		'COLOR' => $_SESSION['colors'][$i],
		'TIME' => format_time($dbu->f('app_duration')),
	));
	
	$tot +=  $dbu->f('app_duration');
	$i++;
}

$ft->assign('TOPAPPS_OUTPUT',drawGraph($topapps));
//=============================================================================================
//=============================================================================================


//	**************************************************************************************
//	*************** top websites *****************************************************
//	**************************************************************************************

$session_website = get_session_website_table();

$session = $dbu->row("SELECT SUM(session_" . $session_website . ".duration) AS duration FROM session_" . $session_website . " 
INNER JOIN session ON session.session_id = session_" . $session_website . ".session_id
INNER JOIN domain ON session_" . $session_website . ".domain_id = domain.domain_id
".$app_join." WHERE 1=1
". $app_filter);
$total = $session['duration'];


$dbu->query("SELECT * FROM (SELECT SUM(session_" . $session_website . ".duration) as website_duration,
domain.domain,
session_" . $session_website . ".domain_id
FROM session_" . $session_website . "
INNER JOIN domain ON domain.domain_id = session_" . $session_website . ".domain_id
INNER JOIN session ON session.session_id = session_" . $session_website . ".session_id
".$app_join."
WHERE 1=1".$app_filter."
GROUP BY session_" . $session_website . ".domain_id
ORDER BY website_duration DESC
LIMIT 0,5) as website_duration
ORDER BY website_duration asc");


$i = 0;
$total_top_duration = 0;
$total_top_procent = 0;

$topwebsites = new stdClass; 
$topwebsites = array( "settings" => array( "container" => array("selector" => "topwebsites", "height" => "280px", "width" => "100%")), "theme" => "theme1" );
// $topwebsites->settings->container->selector = "topwebsites";
// $topwebsites->settings->container->height = "280px";
// $topwebsites->settings->container->width = "100%";

// $topwebsites->theme = "theme1";
$topwebsites['animationEnabled'] = pdf_animate();
$topwebsites['interactivityEnabled'] = true;
$topwebsites['barwidth'] = 25;
$topwebsites['axisX']['labelMaxWidth'] = 120;
$topwebsites['axisX']['labelFontSize'] = 16;
$topwebsites['axisX']['labelFontFamily']="Avinira-regular";
$topwebsites['axisY']['labelFontSize'] = 16;
$topwebsites['axisX']['labelFontColor'] = "#343a41";
$topwebsites['axisY']['labelFontColor'] = "#343a41";
// $topwebsites['axisX']['labelFontWeight'] = "bold";

$topwebsites['data'][0]['type'] = "bar";
$topwebsites['data'][0]['color'] = "#FABB46";
$topwebsites['data'][0]['toolTipContent'] = "{legendText} - {y}%";

//print_r($topwebsites);die();

while($i < 5 && $dbu->move_next()){
	$proc = ($dbu->f('website_duration') * 100 / $total);

	$topwebsites['data'][0]['dataPoints'][0]['color'] = "#f39c12";
	$topwebsites['data'][0]['dataPoints'][1]['color'] = "#3498db";
	$topwebsites['data'][0]['dataPoints'][2]['color'] = "#2ecc71";
	$topwebsites['data'][0]['dataPoints'][3]['color'] = "#f1c40f";
	$topwebsites['data'][0]['dataPoints'][4]['color'] = "#e74c3c";
	
	$topwebsites['data'][0]['dataPoints'][$i]['y'] = intval($proc);
	$topwebsites['data'][0]['dataPoints'][$i]['label'] = trim_text(decode_numericentity($dbu->f('domain')),10);
	$topwebsites['data'][0]['dataPoints'][$i]['legendText'] = decode_numericentity($dbu->f('domain'));
	$ft->assign(array(
		'NAME' => decode_numericentity($dbu->f('domain')),
		'SHORTNAME' => trim_text(decode_numericentity($dbu->f('domain')),25),
		'PROCENT' => number_format($proc,2,'.',','), 
		'COLOR' => $_SESSION['colors'][$i],
		'TIME' => format_time($dbu->f('website_duration')),
	));
	
	$total_top_duration += $dbu->f('website_duration');
	$total_top_procent += number_format($proc,2);
	
	$i++;
}

//echo "";print_r($topwebsites);
$ft->assign('TOPWEBSITES_OUTPUT',drawGraph($topwebsites));
//=============================================================================================
//=============================================================================================


//	**************************************************************************************
//	*************** productivity *********************************************************
//	**************************************************************************************

$chart = array();
$apps = array();
$session_website = get_session_website_table();
$nodes = explode('-',$glob['f']);
$department_id = reset($nodes);
$prod_color = array('red' => 'EB544D', 'rest'=>end($_SESSION['colors']),'green' => '5EE357');
$prod = array('red', 'rest', 'green');
$labels = array('red' => $ft->lookup('Distracting'), 'rest' => $ft->lookup('Neutral'), 'green' => $ft->lookup('Productive'));

$filters = get_filters($glob['t'],$glob['f'],$glob['time'],true);
extract($filters,EXTR_OVERWRITE);
$chart = array('red'=>0,'rest'=>0,'green'=>0);

$session = $dbu->row("SELECT SUM(session_application.duration) AS duration FROM session_application
						INNER JOIN session ON session.session_id = session_application.session_id 
						".$app_join."
						WHERE session_application.time_type = 0 AND 1=1 ".$app_filter);
$chart['total'] = $session['duration'];
$session_website = get_session_website_table();
$productivity_filter = ' = 1';
$count = 1;
$department_list = get_department_children($department_id,1);
$querystring = "SELECT     Sum(session_application.duration) AS app_duration, 
                             application.description           AS name, 
                             application.application_type      AS type, 
                             application.application_id, 
                             application_productivity.application_productivity_id AS application_productivity_id,
                             COALESCE(application_productivity.productive,1) AS productive
                  FROM       session_application 
                  INNER JOIN application 
                  ON         application.application_id = session_application.application_id 
					AND      application.application_type != 3 
                  INNER JOIN session 
                  ON         session.session_id = session_application.session_id 
                  ".$app_join."
                  LEFT JOIN  application_productivity 
                  ON         application_productivity.department_id ".$productivity_filter." 
                  AND        application_productivity.link_id = application.application_id 
                  AND        application_productivity.link_type < 3 
                  AND        member.department_id IN (" . $department_list . ")
                  WHERE      session_application.time_type = 0 
                  AND        2=2 
                  ".$app_filter." 
                  GROUP BY   session_application.application_id 
union 
         SELECT     sum(session_" . $session_website . ".duration)               AS app_duration,
                               domain.domain                                   AS name, 
                               '3'                                             AS type, 
                               domain.domain_id                                AS application_id,
                               application_productivity.application_productivity_id AS application_productivity_id, 
                               COALESCE(application_productivity.productive,1) AS productive
                    FROM       session_" . $session_website . " 
                    INNER JOIN domain 
                    ON         domain.domain_id = session_" . $session_website . ".domain_id 
                    INNER JOIN session 
                    ON         session.session_id = session_" . $session_website . ".session_id 
                    ".$app_join."
                    LEFT JOIN  application_productivity 
                    ON         application_productivity.department_id ".$productivity_filter." 
                    AND        application_productivity.link_id = domain.domain_id 
                    AND        application_productivity.link_type = 3 
                    AND        member.department_id IN (" . $department_list . ")
                    WHERE      session_" . $session_website . ".time_type = 0 
                    AND        2=2 
                    ".$app_filter."
                    GROUP BY   session_" . $session_website . ".domain_id";

$productivity = $dbu->query($querystring);
while ($productivity->next()){
	switch ($productivity->f('productive')){
		case 0:
			$chart['red'] += $productivity['app_duration'];
			break;			
		case 2:
			$chart['green'] += $productivity['app_duration'];
			break;
	}
}
$chart['rest'] = $chart['total'] - (($chart['red'] + $chart['green']));
$total = $chart['total'] > 0 ? $chart['total'] : 1;
$chart_prod = $chart['green'];
$chart_dist = $chart['red'];
$chart_neut = $chart['rest'];
unset($chart);
$chart['green'] = $chart_prod;
$chart['red'] = $chart_dist;
$chart['rest'] = $chart_neut;


$prodstack = new stdClass; $prodstack = array( "settings" => array( "container" => array("selector" => "doughnut", "height" => "280px", "width" => "100%")), "theme" => "theme1" );

// $prodstack->[settings]->container->selector = "doughnut";
// $prodstack->[settings]->container->height = "280px";
// $prodstack->[settings]->container->width = "100%";

// $prodstack->theme = "theme1";
$prodstack['legend']['fontSize'] = 13;
$prodstack['animationEnabled'] = pdf_animate();
$prodstack['axisX']['valueFormatString'] = ' ';
$prodstack['axisX']['tickLength'] = 0;
$prodstack['axisY']['interval'] = 10;
$prodstack['axisY']['labelFontSize'] = 13;
$prodstack['legend']['verticalAlign'] = "bottom";
$prodstack['legend']['horizontalAlign'] = "center";

//Old code for Bar Graph
/*$i = 0;
foreach ($chart as $key => $val){
	$proc =  $val / $count;
	// if ((($val * 100) / $total) > 1) {
		// $theminus = 0.01;
	// } else {
		$theminus = 0;
	// }

$prodstack->data[$i]->type = "stackedBar100";
$prodstack->data[$i]->showInLegend = true;
$prodstack->data[$i]->legendText = $labels[$key] . ' (' . (float)number_format(((($val * 100) / $total) - $theminus),2) . '%)';
$prodstack->data[$i]->color = '#' . $prod_color[$key];
$prodstack->data[$i]->toolTipContent = "{legendText}";
	$prodstack->data[$i]->dataPoints[0]->y = (float)number_format(((($val * 100) / $total) - $theminus),2);
	$ft->assign(array(
		'NAME' => $labels[$key] . ' ('.number_format(($val * 100) / $total,2).')',
		'PROCENT' => number_format((($val * 100) / $total) - $theminus,2),	//	 - 0.01	to prevent above 100%
		'COLOR' => $prod_color[$key]
	));
	$i++;
}*/
//===================

//New Code for doughnut Graph
	$i = 0;
	foreach ($chart as $key => $val){
		$proc =  $val / $count;
		// if ((($val * 100) / $total) > 1) {
		// $theminus = 0.01;
		// } else {
		$theminus = 0;
		// }

		$prodstack['data'][0]['type'] = "doughnut";
		$prodstack['data'][0]['startAngle'] = -90;
		$prodstack['data'][0]['showInLegend'] = true;
		$prodstack['data'][0]['legendText'] = $labels[$key] . ' (' . (float)number_format(((($val * 100) / $total) - $theminus),2) . '%)';
		$prodstack['data'][0]['toolTipContent'] = "{legendText} - {y}%";
		$prodstack['data'][0]['dataPoints'][$i]['y'] = (float)number_format(((($val * 100) / $total) - $theminus),2);
		$prodstack['data'][0]['dataPoints'][$i]['legendText'] = $labels[$key] . ' (' . (float)number_format(((($val * 100) / $total) - $theminus),2) . '%)';
		$prodstack['data'][0]['dataPoints'][$i]['color'] = '#' . $prod_color[$key];
		$ft->assign(array(
				'NAME' => $labels[$key] . ' ('.number_format(($val * 100) / $total,2).')',
				'PROCENT' => number_format((($val * 100) / $total) - $theminus,2),	//	 - 0.01	to prevent above 100%
				'COLOR' => $prod_color[$key]
		));
		$i++;
	}
//====================
$ft->assign('PRODSTACK_OUTPUT',drawGraph($prodstack));
//=============================================================================================
//=============================================================================================








	$trial = $dbu->field("SELECT value FROM `settings` WHERE `constant_name` = 'TRUENC'");
	$ecrypted_text = $dbu->field("SELECT message FROM `notification` WHERE `constant_name` = 'ENCRYPTED_TEXT'");
	if ($trial != 2236985){
		$ft->assign('ENCRYPTMESSAGERAW', '<div class="encryptmessage">' . $ft->lookup($ecrypted_text) . '</div>');
	}
if( $glob['stats_active'] == 0 && $glob['stats_idle'] == 0 && $glob['stats_online'] == 0 )
{
	$ft->assign(array(
		'NO_DATA_MESSAGE' => get_error($ft->lookup('No data to display for your current filters'),'warning'),
		'HIDE_CONTENT'	=> 'hide',
	));
}
else 
{
	$ft->assign(array(
		'NO_DATA_MESSAGE' => '',
		'HIDE_CONTENT'	=> '',
	));
}
	//	modified for pdf	--->
	$export_header = get_export_header($_SESSION['filters']['f']);
	extract($export_header,EXTR_OVERWRITE);
	$ft->assign(array(
		'PDF_HEADER' => pdf_header(),
		'PDF_HIDE' => pdf_hide(),
		'PDF_CLASS' => pdf_class(),
		'TITLE' => $ft->lookup('Overview'),
		'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
		'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
	));
	//	<---	modified for pdf
global $bottom_includes;
$bottom_includes .= '
<script type="text/javascript">flobn.register("genesis",new Date("'.date('n/j/Y',$dates['genesis']).'"));</script><script type="text/javascript" src="'.CURRENT_VERSION_FOLDER.'ui/overview-ui.js"></script>';

if(!$glob['is_ajax']){
	$ft->define_dynamic('ajax','main');
	$ft->parse('AJAX_OUT','ajax');
}


$site_meta_title=$meta_title;
$site_meta_keywords=$meta_keywords;
$site_meta_description=$meta_description;

$ft->parse('CONTENT','main');

	
	//	modified for pdf	--->
	if($_REQUEST['render'] == 'pdf'){
	$page = 'overview';
	$html = $ft->fetch('CONTENT');
		file_put_contents(CURRENT_VERSION_FOLDER.'temp_pdf/'.$page.'.html',"\xEF\xBB\xBF" . $html);
	loadPDF($page,'inline');exit;
	} else {
	return $ft->fetch('CONTENT');
	}
	//	<---	modified for pdf
