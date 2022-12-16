<?php
if($ajax_loaded){
	// header('Content-type:text/plain');
	$ftchart=new ft(ADMIN_PATH.MODULE."templates/");
	$ftchart->define(array('main' => "chart.html"));

	$dbu = new mysql_db();
} else {
	$ftchart = $ftx;
}

//	**************************************************
//	**************************************************
$cats = array();

$filters = get_filters($glob['t'],$glob['f'],$glob['time'],true);
extract($filters,EXTR_OVERWRITE);

$pieces = explode('-',$_SESSION['filters']['f']);
$department_id = substr($pieces[0],1);
$dbu->query("SELECT * FROM (SELECT SUM(session_website.duration) as duration,  
					application_category.category,
					application_category.application_category_id
			 FROM session_website 
			 INNER JOIN session ON session.session_id = session_website.session_id
			 INNER JOIN application2category ON application2category.link_id = session_website.domain_id AND 
			 									application2category.link_type = 3
			 INNER JOIN application_category ON application_category.application_category_id = application2category.application_category_id
			 ".$app_join."
			 WHERE application2category.department_id = ".$department_id."  AND session_website.time_type = 0 ".$app_filter."
			 GROUP BY application_category.application_category_id
			 ORDER BY duration desc) as duration
			 ORDER BY duration asc
			");
$domain = array();
$i = 0;
$tot = 0;
while ($dbu->move_next()){
	$domain[$i]['name'] = $ftchart->lookup(decode_numericentity($dbu->f('category')));
	$domain[$i]['duration'] = $dbu->f('duration');
	$i++;
	$tot +=  $dbu->f('duration');
}
$max = $i;

$internetcategorychart = new stdClass;
$internetcategorychart = array( "settings" => array( "container" => array("selector" => "internetcategorychart", "height" => "150px", "width" => "400px")), "theme" => "theme1");
// $internetcategorychart->settings->container->selector = "internetcategorychart";
// $internetcategorychart->settings->container->height = "150px";
// $internetcategorychart->settings->container->width = "400px";

// $internetcategorychart->theme = "theme1";
$internetcategorychart['animationEnabled'] = true;
$internetcategorychart['barwidth'] = 15;
$internetcategorychart['interactivityEnabled'] = true;
$internetcategorychart['axisX']['labelMaxWidth'] = 120;
$internetcategorychart['axisX']['labelFontSize'] = 12;
$internetcategorychart['axisX']['labelFontColor'] = "#000000";
$internetcategorychart['axisX']['labelFontWeight'] = "bold";
$internetcategorychart['axisY']['labelFontSize'] = 10;
// $internetcategorychart->axisY->maximum = 100;
$internetcategorychart['axisY']['minimum'] = 0;

$internetcategorychart['data[0]']['type'] = "bar";
$internetcategorychart['data[0]']['color'] = "#FABB46";
$internetcategorychart['data[0]']['toolTipContent'] = "{legendText}";

$i = 0;
$j = 0;
if (count($domain) > 0){
	foreach ($domain as $k=>$v){
		if ($j > ($max - 6)){
			$proc = (($v['duration'] * 100 / $tot) >= 0) ? ($v['duration'] * 100 / $tot) : 0;
			$proc_sterilized = ($proc == 100) ? 99.9 : $proc;
			$internetcategorychart['data[0]']['dataPoints[$i]']['y'] = (float)number_format($proc_sterilized,2,'.',',');
			$internetcategorychart['data[0]']['dataPoints[$i]']['label'] = trim_text($v['name'],15);
			$internetcategorychart['data[0]']['dataPoints[$i]']['legendText'] = $v['name'] . ' - ' . number_format($proc,2,'.',',') . '%';
			$i++;
		}
		$j++;
	}
} else {
			$internetcategorychart['data[0]']['dataPoints[0]']['y'] = (float)number_format(99.9,2,'.',',');
			$internetcategorychart['data[0]']['dataPoints[0]']['label'] = trim_text($ftchart->lookup('Uncategorized'),15);
			$internetcategorychart['data[0]']['dataPoints[0]']['legendText'] = $ftchart->lookup('Uncategorized') . ' - 100%';
}

if($ajax_loaded){	
$ftchart->assign('THE_CHART_OUTPUT',drawGraph($internetcategorychart));

	//	**************************************************
	//	**************************************************

	$ftchart->parse('CONTENT','main');
	echo $ftchart->fetch('CONTENT');
	exit();
}