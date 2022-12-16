<?php
if($ajax_loaded){
	// header('Content-type:text/plain');
	$ftchart=new ft(ADMIN_PATH.MODULE."templates/");
	$ftchart->define(array('main' => "chartproductivityheader.html"));

	$dbu = new mysql_db();
} else {
	$ftchart = $ftx;
}

//	**************************************************
//	**************************************************
$chart = array();
$apps = array();
$session_website = get_session_website_table();
$nodes = explode('-',$glob['f']);
$department_id = reset($nodes);
$prod_color = array('red' => 'EB544D', 'rest'=>end($_SESSION['colors']),'green' => '5EE357');
$prod = array('red', 'rest', 'green');
$labels = array('red' => $ftchart->lookup('Distracting'), 'rest' => $ftchart->lookup('Neutral'), 'green' => $ftchart->lookup('Productive'));

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
$department_list = get_department_children($department_id,1);
$count = 1;
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
$total = $chart['total'];
unset($chart['total']);
asort($chart);
$sortedchart = array();
$sortedchart['red'] = $chart['red'];
$sortedchart['rest'] = $chart['rest'];
$sortedchart['green'] = $chart['green'];

$prodpie = new stdClass;
$prodpie = array( "settings" => array( "container" => array("selector" => "prodpie", "height" => "188px", "width" => "188px")), "theme" => "theme1");
// $prodpie->settings->container->selector = "prodpie";
// $prodpie->settings->container->height = "188px";
// $prodpie->settings->container->width = "188px";

// $prodpie->theme = "theme1";
$prodpie['animationEnabled'] = pdf_animate();
$prodpie['interactivityEnabled'] = true;
$prodpie['widthPercentage'] = .85;
$prodpie['radiusMultiplier'] = 1.25;
$prodpie['axisY']['valueFormatString'] = ' ';
$prodpie['axisY']['tickLength'] = 0;
$prodpie['axisX']['valueFormatString'] = ' ';
$prodpie['axisX']['tickLength'] = 0;

$prodpie['data[0]']['type'] = "doughnut";
$prodpie['data[0]']['startAngle'] = -90;
$prodpie['data[0]']['widthPercentage'] = .85;
$prodpie['data[0]']['radiusMultiplier'] = 1.25;
$prodpie['data[0]']['toolTipContent'] = "{legendText} - #percent %";
$i = 0;
if ($total < 1) {$total = 1;}
foreach ($sortedchart as $key => $val){
	$proc =  $val / $count;
	$prodpie['data[0]']['dataPoints[$i]']['y'] = (float)(number_format(($val * 100) / $total,2));
	$prodpie['data[0]']['dataPoints[$i]']['legendText'] = $labels[$key];
	$prodpie['data[0]']['dataPoints[$i]']['color'] = "#" . $prod_color[$key];
	$i++;
}

if($ajax_loaded){
	$ftchart->assign('THE_CHART_OUTPUT',drawGraph($prodpie));
	$ftchart->assign('PRODUCTIVE_TOTAL',(float)(number_format(($sortedchart['green'] * 100) / $total,2)));
	$ftchart->assign('DISTRACTING_TOTAL',(float)(number_format(($sortedchart['red'] * 100) / $total,2)));
	$ftchart->assign('NEUTRAL_TOTAL',(float)( (number_format(($sortedchart['rest'] * 100) / $total,2)) < 1 ? 0 : (number_format(($sortedchart['rest'] * 100) / $total,2)) ));
	$ftchart->assign('PRODUCTIVE_TOTAL_TIME', format_time($sortedchart['green'],false));
	$ftchart->assign('DISTRACTING_TOTAL_TIME', format_time($sortedchart['red'],false));
	$ftchart->assign('NEUTRAL_TOTAL_TIME', format_time($sortedchart['rest'],false));

	//	**************************************************
	//	**************************************************

	$ftchart->parse('CONTENT','main');
	echo $ftchart->fetch('CONTENT');
	exit();
}