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
$prod = array('red', 'rest', 'green');
$prod_color = array('red' => 'EB544D', 'rest'=>end($_SESSION['colors']),'green' => '5EE357');
$labels = array('red' => $ftchart->lookup('Distracting'), 'rest' => $ftchart->lookup('Neutral'), 'green' => $ftchart->lookup('Productive'));
$session_website = get_session_website_table();
$nodes = explode('-',$glob['f']);
$department_id = reset($nodes);
$filters = get_filters($glob['t'],$glob['f'],$glob['time'],true);
extract($filters,EXTR_OVERWRITE);
$chart = array('red'=>0,'rest'=>0,'green'=>0);

$session = $dbu->row("SELECT SUM(session_" . $session_website . ".duration) AS duration FROM session_" . $session_website . "
						INNER JOIN session ON session.session_id = session_" . $session_website . ".session_id 
						".$app_join."
						WHERE session_" . $session_website . ".time_type = 0 AND 1=1 ".$app_filter);
$chart['total'] = $session['duration'];

$productivity_filter = ' = 1';
$count = 1;
$department_list = get_department_children($department_id,1);
$querystring = "SELECT     sum(session_" . $session_website . ".duration)               AS app_duration,
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
	
$chart['rest'] = $chart['total'] - ($chart['red'] + $chart['green']);
$chart['red'] = is_numeric($chart['red']) ? $chart['red'] : 1;
$chart['green'] = is_numeric($chart['green']) ? $chart['green'] : 1;
$total = $chart['total'];
if ($total == 0){
	$total = 1;
}
$chart['red'] = is_numeric($chart['red']) ? number_format(($chart['red'] * 100) / $total,2) : 0;
$chart['green'] = is_numeric($chart['green']) ? number_format(($chart['green'] * 100) / $total,2) : 0;
$chart['rest'] = is_numeric($chart['rest']) ? number_format(($chart['rest'] * 100) / $total,2) : 0;
$chart['rest'] = ((100 - $chart['green'] - $chart['red']) < 99.9) ? number_format((100 - $chart['green'] - $chart['red']),2) : 100;

$productivitychart = new stdClass;
$productivitychart = array( "settings" => array( "container" => array("selector" => "productivitychart", "height" => "150px", "width" => "300px")), "theme" => "theme1");
// $productivitychart->settings->container->selector = "productivitychart";
// $productivitychart->settings->container->height = "150px";
// $productivitychart->settings->container->width = "300px";

// $productivitychart->theme = "theme1";
$productivitychart['animationEnabled'] = pdf_animate();
$productivitychart['interactivityEnabled'] = true;
$productivitychart['axisY']['valueFormatString'] = ' ';
$productivitychart['axisY']['tickLength'] = 0;
$productivitychart['axisY']['margin'] = 80;
$productivitychart['axisX']['margin'] = 80;

$productivitychart['data[0]']['type'] = "doughnut";
$productivitychart['data[0]']['startAngle'] = -90;
$productivitychart['data[0]']['indexLabelFontColor'] = "#000000";
$productivitychart['data[0]']['toolTipContent'] = "{legendText} - {y}%";
	$productivitychart['data[0]']['dataPoints[0]']['y'] = (float)$chart['red'];
	$productivitychart['data[0]']['dataPoints[0]']['legendText'] = $ftchart->lookup('Distracting');
	$productivitychart['data[0]']['dataPoints[0]']['label'] = $ftchart->lookup('Distracting');
	$productivitychart['data[0]']['dataPoints[0]']['color'] = "#EB544D";
	$productivitychart['data[0]']['dataPoints[0]']['index']['Label']FontSize = 12;
	$productivitychart['data[0]']['dataPoints[1]']['y'] = (float)$chart['rest'];
	$productivitychart['data[0]']['dataPoints[1]']['legendText'] = $ftchart->lookup('Neutral');
	$productivitychart['data[0]']['dataPoints[1]']['label'] = $ftchart->lookup('Neutral');
	$productivitychart['data[0]']['dataPoints[1]']['color'] = "#EDEDED";
	$productivitychart['data[0]']['dataPoints[1]']['index']LabelFontSize = 12;
	$productivitychart['data[0]']['dataPoints[2]']['y'] = (float)$chart['green'];
	$productivitychart['data[0]']['dataPoints[2]']['legendText'] = $ftchart->lookup('Productive');
	$productivitychart['data[0]']['dataPoints[2]']['label'] = $ftchart->lookup('Productive');
	$productivitychart['data[0]']['dataPoints[2]']['color'] = "#5EE357";
	$productivitychart['data[0]']['dataPoints[2]']['index']LabelFontSize = 12;

if($ajax_loaded){
	$ftchart->assign('THE_CHART_OUTPUT',drawGraph($productivitychart));

	//	**************************************************
	//	**************************************************

	$ftchart->parse('CONTENT','main');
	echo $ftchart->fetch('CONTENT');
	exit();
}