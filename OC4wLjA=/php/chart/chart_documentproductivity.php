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

var_dump("test");
$prod_color = array('red' => 'EB544D', 'rest'=>end($_SESSION['colors']),'green' => '5EE357');
$prod = array('red', 'rest', 'green');
$labels = array('red' => $ftchart->lookup('Distracting'), 'rest' => $ftchart->lookup('Neutral'), 'green' => $ftchart->lookup('Productive'));
$chart = array();

$filters = get_filters($glob['t'],$glob['f'],$glob['time']);
extract($filters,EXTR_OVERWRITE);

$apps = array();

$application = $dbu->query("SELECT SUM(session_application.duration) AS duration,
									session_application.application_id		
						FROM session_application
						INNER JOIN application ON application.application_id = session_application.application_id
						INNER JOIN session ON session.session_id = session_application.session_id
						".$app_join."
						WHERE session_application.time_type = 0 
						AND application.application_type = 2
						AND 1=1".$app_filter."
						GROUP BY session_application.application_id");
//get total for all apps which we can later use to define productivity
while ($application->next()){
	$apps[$application->f('application_id')]['total'] = $application->f('duration');
	$apps[$application->f('application_id')]['type'] = $application->f('type_id');
	$chart['total'] += $application->f('duration');
}


$productivity = $dbu->query("SELECT SUM(session_application.duration) AS app_duration,
							application.application_id, 
							application_productivity.productive
							FROM session_application
							INNER JOIN application ON application.application_id = session_application.application_id
							INNER JOIN session ON session.session_id = session_application.session_id
							".$app_join."
							LEFT JOIN application_productivity ON application_productivity.department_id = member.department_id
											   AND application_productivity.link_id = session_application.application_id 
											   AND application_productivity.link_type = 0
							WHERE session_application.time_type = 0 
							AND application.application_type = 2
							AND application_productivity.productive >=0
							AND 2=2".$app_filter."
							GROUP BY session_application.application_id");


$prodstack = new stdClass;
$prodstack = array( "settings" => array( "container" => array("selector" => "prodstack", "height" => "100px", "width" => "100%")), "theme" => "theme1");
// $prodstack->settings->container->selector = "prodstack";
// $prodstack->settings->container->height = "100px";
// $prodstack->settings->container->width = "100%";

// $prodstack->theme = "theme1";
$prodstack['legend']['fontSize'] = 12;
$prodstack['animationEnabled'] = pdf_animate();
$prodstack['axisX']['valueFormatString'] = ' ';
$prodstack['axisX']['tickLength'] = 0;
$prodstack['axisY']['interval'] = 10;
$prodstack['axisY']['labelFontSize'] = 10;
$prodstack['legend']['verticalAlign'] = "bottom";
$prodstack['legend']['horizontalAlign'] = "center";

while ($productivity->next()){
	if($productivity->f('productive') == 3){
		//need to see the children for info
		$children = $dbu->query("SELECT SUM(session_document.duration) AS app_duration,
									application_productivity.productive AS productive
	    			 FROM session_document
					 INNER JOIN session ON session.session_id = session_document.session_id
					".$app_join."
					LEFT JOIN application_productivity ON application_productivity.department_id = member.department_id 
								  							AND application_productivity.link_id = session_document.document_id
								  							AND application_productivity.link_type = 2  							
					WHERE session_document.time_type = 0 AND 5=5 
					AND application_productivity.productive >=0
					AND session_document.application_id = '".$productivity->f('application_id')."'
					".$app_filter."
					GROUP BY application_productivity.productive
					ORDER BY app_duration DESC");
		while ($children->next()){
			$key = $prod[$children->f('productive')];
			$apps[$productivity->f('application_id')][$key] = $children->f('app_duration');
			$chart[$key] += $children->f('app_duration');;
		}
		continue;
	}
	$key = $prod[$productivity->f('productive')];
	$apps[$productivity->f('application_id')][$key] = $productivity->f('app_duration');
	$chart[$key] += $productivity->f('app_duration');;
}
$chart['rest'] = $chart['total'] - ($chart['red'] + $chart['green']);

$total = $chart['total'];
unset($chart['total']);

if ($total == 0) {
	$total = 1;
}

$i = 0;
foreach ($chart as $key => $val){
$prodstack['data[$i]']['type'] = "stackedBar100";
$prodstack['data[$i]']['showInLegend'] = true;
$prodstack['data[$i]']['legendText'] = $labels[$key];
$prodstack['data[$i]']['color'] = '#' . $prod_color[$key];
$prodstack['data[$i]']['toolTipContent'] = "{legendText} - {y}%";
	$prodstack['data[$i]']['dataPoints[0]']['y'] = (float)number_format(($val * 100) / $total,2);
	$i++;
}

if($ajax_loaded){

$ftchart->assign('THE_CHART_OUTPUT',drawGraph($prodstack));

	//	**************************************************
	//	**************************************************

	$ftchart->parse('CONTENT','main');
	echo $ftchart->fetch('CONTENT');
	exit();
}