<?php

if(!$glob['app']){
	return '';
}
$dbu = new mysql_db();
$rest = 0;
$prod = array('red', 'rest', 'green');
$session_website = get_session_website_table();

			
			//	clean up the db from trash
			$dbu->query("DELETE FROM application_productivity WHERE link_id=0");

if(!$glob['f'])
	$filters = get_filters($_SESSION['filters']['t'],$_SESSION['filters']['f'],$_SESSION['filters']['time'],true);
else
	$filters = get_filters($glob['t'],$glob['f'],$glob['time'],true);

extract($filters,EXTR_OVERWRITE);


if(isset($glob['app_duration']) && is_numeric($glob['app_duration'])){
	$prod_total = $glob['app_duration'];
}else{
	//need to calculate the app total which requires app id and app type
	$app_info = $session = $dbu->row("SELECT SUM(session_application.duration) AS duration
						FROM session_application
						INNER JOIN session ON session.session_id = session_application.session_id 
						".$app_join."
						WHERE session_application.time_type = 0 AND session_application.application_id = '".$glob['app']."' AND 1=1 ".$app_filter);
	$prod_total = $app_info['duration'];
}

$table = '';
$primary = '';
switch ($glob['type']){
	case 1:
    // 1 chat
    	$session_table = 'chat';
	    $table = 'chat';
    	break;
    case 2:
    // 2 document
    	$session_table = 'document';
    	$table = 'document';
    	break;
    case 3:
    // 3 site 
    	$table = 'domain';
    	$session_table = $session_website;
    	break;	
    default:
   		return '';
}
$primary = $table.'_id';
$secondary_productivity = array('red' => 0, 'rest' => 0, 'green' => 0);
//calculation time :D
$glob_parentapp = cleanArrayToString($glob['parentapp']);

$productivity = $dbu->query("SELECT SUM(session_".$session_table.".duration) AS app_duration,
									application_productivity.productive AS productive,
									application_productivity.department_id AS dep_id,
									application_productivity.link_id AS link_id
	    			 FROM session_".$session_table."
					 INNER JOIN session ON session.session_id = session_".$session_table.".session_id
					".$app_join."
					INNER  JOIN application_productivity ON application_productivity.department_id = 1 
								  							AND application_productivity.link_id = session_".$session_table.".".$primary."
															AND application_productivity.link_type = ".$glob['type']." WHERE ".
					(($glob['type'] == 3)?"":"session_".$session_table.".time_type = 0 AND ")."
					 5=5 AND session_".$session_table.".application_id = '".$glob['app']."'
					".$app_filter."
					GROUP BY session_".$session_table.".".$primary."
					ORDER BY app_duration DESC");
					

$k = 0;
		
while ($productivity->next()){

		$dbu_prod = new mysql_db();
		$productive = $dbu_prod->field("SELECT `productive`
							FROM `application_productivity`
							WHERE `department_id` = 1
							AND `link_id` = " . $productivity->f('link_id') . "
							AND `link_type` = " . $glob['type'] . "
							LIMIT 1 ");
		if ($productive === false){
			$productive = 1;
		}
	//variable variable watch out!!!!!
	$$prod[$productive] += $productivity->f('app_duration');
	$secondary_productivity[$prod[$productive]] += $productivity->f('app_duration');
}
$secondary_productivity['rest'] = $prod_total - ($secondary_productivity['red'] + $secondary_productivity['green']);

//calculate
$total_width = 0;
$rest = $prod_total - ($red+ $green);
if($rest <= 0 ){
	$rest = 0;
}
$widths = array();
$procs = array();
foreach ($prod as $index => $var){
	//variable variable named var!!
	if($prod_total == 0)
		{
			$widths[$index] = 0;
			$procentual_value = 0;
		} else {
			$widths[$index] = floor(($$var * 102) / $prod_total);
			$procentual_value = round((($$var * 100) / $prod_total),2);
		}
	if($procentual_value == 0){
		continue;
	}
	$procs[$index] = '<b class="'.$var.'">'.$procentual_value.'%</b> / ';	
	if($procentual_value == 100){
		break;
	}
}

$glob['procs'] = trim(join('',array($procs[0],$procs[2],$procs[1])),' / ');
$total_width = array_sum($widths);
if($total_width < 102){
	//we need to add a few pixels
	//find the max
	$max_value = $widths[0];
	$max_index = 0;
	for($k = 1; $k <= 2; $k++){
		if($widths[$k] < $max_value){
			continue;
		}
		$max_value = $widths[$k];
		$max_index = $k;
	}
	//add to the max
	$widths[$max_index] += 102 - $total_width;
}

	$l = new LanguageParser();
	 if(($prod_total==$red || $prod_total==$green) && $glob['type']!=3){
		$template = '<span id="prod-slider-{APP_ID}" class="slider flobn-slider {CSS_CLASS}" rel="{APP_ID}" data-brother="{APP_ID}" data-type="{TYPE}" data-app="{APP_ID}" data-parent="true" data-duration="{TIME}"><a class="handle">&nbsp;</a></span><span style="text-align:center;">{TYPE}</span>';
		$template = str_replace(array(
		'{APP_ID}',
		'{TIME}',
		'{CSS_CLASS}',
		'{TYPE}',
		),array(
			$glob['app'],	
			$prod_total ? $prod_total : 0,
			$green ? 'productive' : 'distracting',
			$green ? $l->lookup('Productive') : $l->lookup('Distracting')
		),$template);
	} elseif(($prod_total==$rest) && $glob['type']!=3) {
		$template = '<span id="prod-slider-{APP_ID}" class="slider flobn-slider {CSS_CLASS}" rel="{APP_ID}" data-brother="{APP_ID}" data-type="{TYPE}" data-app="{APP_ID}" data-parent="true" data-duration="{TIME}"><a class="handle">&nbsp;</a></span><span style="text-align:center;">{TYPE}</span>';
		$template = str_replace(array(
		'{APP_ID}',
		'{TIME}',
		'{CSS_CLASS}',
		'{TYPE}',
		),array(
			$glob['app'],	
			$prod_total ? $prod_total : 0,
			'neutral',
			$l->lookup('Neutral')
		),$template);	
	}else{
		$template = '<span id="prod-bar-{APP_ID}" class="procentbar" data-red="{RED}" data-green="{GREEN}" data-rest="{REST}"><b class="negative" style="width:{RED_WIDTH}px;">&nbsp;</b><b class="positive" style="width:{GREEN_WIDTH}px;">&nbsp;</b><b class="neutral" style="width:{REST_WIDTH}px;">&nbsp;</b></span>';
		$template = str_replace(array(
		'{APP_ID}',
		'{RED}',
		'{GREEN}',
		'{REST}',
		'{RED_WIDTH}',
		'{GREEN_WIDTH}',
		'{REST_WIDTH}',
		),array(
			$glob['app'],	//appid
			$red ? $red : 0,
			$green ? $green : 0,
			$rest ? $rest : 0,
			$widths[0] ? $widths[0] : 0,
			$widths[2] ? $widths[2] : 0,
			$widths[1] ? $widths[1] : 0
		),$template);
	 }
unset($red,$green,$rest,$widths);
$_SESSION['productivitybar'] = $template;
return $template;