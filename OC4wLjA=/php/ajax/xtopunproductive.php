<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftx=new ft(ADMIN_PATH.MODULE."templates/");
$ftx->define(array('main' => "xtopunproductive.html"));
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

$apps = $dbu->query("SELECT application.application_id,application.description, application_productivity.department_id, COALESCE(application_productivity.productive,1) AS productive FROM application 
	LEFT JOIN application_productivity ON application_productivity.link_id = application.application_id
	AND application_productivity.link_type = 0
	WHERE application.application_type = 3");


$productivity = $dbu->query("SELECT SUM(session_application.duration) AS app_duration,
						COALESCE(application_productivity.productive,1) AS productive,
						application.application_type AS type_id, 
						application.application_id,
						member.member_id,
						member.logon,
						member.first_name,
						member.last_name,
						member.active,
						member.alias,
						casualty.cost_per_hour,
						casualty.currency
						FROM session_application
						INNER JOIN application ON application.application_id = session_application.application_id 
						INNER JOIN session ON session.session_id = session_application.session_id
						".$app_join."
						INNER JOIN application_productivity ON application_productivity.department_id = member.department_id 
														  AND application_productivity.link_id = application.application_id 
													      AND application_productivity.link_type = 0
						LEFT JOIN casualty ON member.department_id = casualty.department_id
						WHERE session_application.duration > 0
						AND session_application.time_type = 0
						AND (productive = 0 OR productive = 3)
						".$app_filter."
						GROUP BY member.member_id,session_application.application_id
						ORDER BY app_duration DESC 
					");
$data = array();
$durations = array();
$session_website = get_session_website_table();
while ($productivity->next()){
	$duration = 0;
	if(!is_array($data[$productivity->f('member_id')])){
		$data[$productivity->f('member_id')] = array();
	} 
	$duration = $productivity->f('app_duration');
	if($productivity->f('productive') == 3){
		
		switch ($productivity->f('type_id')){
			case 1:
		    // 1 chat
		    	$session_table = 'chat';
			    $table = 'chat';
			    $primary = $table.'_id';
		    	break;
		    case 2:
		    // 2 document
		    	$session_table = 'document';
		    	$table = 'document';
			    $primary = $table.'_id';
		    	break;
		    case 3:
		    // 3 site 
		    	$session_table = $session_website;
		    	$table = 'domain';
			    $primary = $table.'_id';
		    	break;
		}
		
		$duration = 0;
		
		$children = $dbu->query("SELECT SUM(session_".$session_table.".duration) AS app_duration,
	    			COALESCE(application_productivity.productive,1) AS productive,
					application_productivity.link_id as app_link_id,
					member.department_id as member_department_id
	    			FROM session_".$session_table."
	    			INNER JOIN ".$table." ON ".$table.".".$primary." = session_".$session_table.".".$primary."
					INNER JOIN session ON session.session_id = session_".$session_table.".session_id
					".$app_join."
					 INNER JOIN application_productivity ON application_productivity.department_id = member.department_id 
							AND application_productivity.link_id = ".$table.".".$primary."
							AND application_productivity.link_type = '".$productivity->f('type_id')."'
					WHERE ".(($productivity->f('type_id') == 3)?"":"session_".$session_table.".time_type = 0 AND ")."2=2 AND session_".$session_table.".application_id = '".$productivity->f('application_id')."'
					AND member.member_id = ".$productivity->f('member_id')."
					AND application_productivity.productive = 0
					".$app_filter."
					GROUP BY application_productivity.productive");
		
		while ($children->next()){
			$division_number = get_division_number($children->f('app_link_id'),$children->f('member_department_id'));
			if ($productivity->f('type_id') == 3){$tempduration = ($children->f('app_duration') / $division_number);} else {$tempduration = $children->f('app_duration');}
			$duration += $tempduration;
		}
	}
	$data[$productivity->f('member_id')]['name'] = $productivity->f('alias') == 1 ? $productivity->f('first_name').' '.$productivity->f('last_name') : $productivity->f('logon');
	$data[$productivity->f('member_id')]['duration'] += $duration;
	$data[$productivity->f('member_id')]['cost'] = $productivity->f('cost_per_hour');
	$data[$productivity->f('member_id')]['currency'] = $productivity->f('currency');
	
	$durations[$productivity->f('member_id')] = $data[$productivity->f('member_id')]['duration'];
}

arsort($durations);
$i = 0;

/*$pieces = split('-',$_SESSION['filters']['f']);
$department_id = substr($pieces[0],1);

//$cost = $dbu->row("SELECT cost_per_hour, currency FROM casualty WHERE department_id='".$department_id."'");*/

foreach ($durations as $member_id => $duration){
	$tags = $data[$member_id];
	$proc = ($tags['duration'] * 100 / $total[$member_id]);
	$ftx->assign(array(
		'NAME' => trialEncrypt($tags['name']),
		'PROCENT' => ($proc > 1) ? number_format($proc,2,',','.') : ' < 1',
		'TOTAL' => format_time($total[$member_id]),
		'TIME' => format_time($tags['duration']),
		'COST' => number_format($data[$member_id]['cost'] * ($tags['duration'] / 3600),2),
		'CURRENCY' => get_currency($data[$member_id]['currency']),
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

//	**************************************
//	**************************************
//	**************************************

$apps = $dbu->query("SELECT application.application_id,application.description, application_productivity.department_id, COALESCE(application_productivity.productive,1) AS productive FROM application 
	LEFT JOIN application_productivity ON application_productivity.link_id = application.application_id
	AND application_productivity.link_type = 0
	WHERE application.application_type = 3");

$productivity = $dbu->query("SELECT SUM(session_application.duration) AS app_duration,
						COALESCE(application_productivity.productive,1) AS productive,
						application.application_type AS type_id, 
						application.application_id,
						member.member_id,
						member.logon,
						member.first_name,
						member.last_name,
						member.active,
						member.alias,
						casualty.cost_per_hour,
						casualty.currency
						FROM session_application
						INNER JOIN application ON application.application_id = session_application.application_id 
						INNER JOIN session ON session.session_id = session_application.session_id
						".$app_join."
						INNER JOIN application_productivity ON application_productivity.department_id = member.department_id 
														  AND application_productivity.link_id = application.application_id 
													      AND application_productivity.link_type = 0
						LEFT JOIN casualty ON member.department_id = casualty.department_id
						WHERE session_application.duration > 0
						AND session_application.time_type = 0
						AND (productive = 0 OR productive = 3)
						".$app_filter."
						GROUP BY member.member_id,session_application.application_id
						ORDER BY app_duration DESC 
					");
$data = array();
$durations = array();
while ($productivity->next()){
	$duration = 0;
	if(!is_array($data[$productivity->f('member_id')])){
		$data[$productivity->f('member_id')] = array();
	} 
	$duration = $productivity->f('app_duration');
	if($productivity->f('productive') == 3){
		
		switch ($productivity->f('type_id')){
			case 1:
		    // 1 chat
		    	$session_table = 'chat';
			    $table = 'chat';
			    $primary = $table.'_id';
		    	break;
		    case 2:
		    // 2 document
		    	$session_table = 'document';
		    	$table = 'document';
			    $primary = $table.'_id';
		    	break;
		    case 3:
		    // 3 site 
		    	$session_table = 'website';
		    	$table = 'domain';
			    $primary = $table.'_id';
		    	break;
		}
		
		$duration = 0;
		$children = $dbu->query("SELECT SUM(session_".$session_table.".duration) AS app_duration,
	    			COALESCE(application_productivity.productive,1) AS productive,
					application_productivity.link_id as app_link_id,
					member.department_id as member_department_id
	    			FROM session_".$session_table."
	    			INNER JOIN ".$table." ON ".$table.".".$primary." = session_".$session_table.".".$primary."
					INNER JOIN session ON session.session_id = session_".$session_table.".session_id
					".$app_join."
					 INNER JOIN application_productivity ON application_productivity.department_id = member.department_id 
							AND application_productivity.link_id = ".$table.".".$primary."
							AND application_productivity.link_type = '".$productivity->f('type_id')."'
					WHERE ".(($productivity->f('type_id') == 3)?"":"session_".$session_table.".time_type = 0 AND ")."2=2 AND session_".$session_table.".application_id = '".$productivity->f('application_id')."'
					AND member.member_id = ".$productivity->f('member_id')."
					AND application_productivity.productive = 0
					".$app_filter."
					GROUP BY application_productivity.productive");
		
		while ($children->next()){
			$division_number = get_division_number($children->f('app_link_id'),$children->f('member_department_id'));
			if ($productivity->f('type_id') == 3){$tempduration = ($children->f('app_duration') / $division_number);} else {$tempduration = $children->f('app_duration');}
			$duration += $tempduration;
		}
	}
	$data[$productivity->f('member_id')]['name'] = $productivity->f('alias') == 1 ? $productivity->f('first_name').' '.$productivity->f('last_name') : $productivity->f('logon');
	$data[$productivity->f('member_id')]['duration'] += $duration;
	
	$durations[$productivity->f('member_id')] = $data[$productivity->f('member_id')]['duration'];
}

arsort($durations);

$i = 0;



$topbarcharunprod = new stdClass;
$topbarcharunprod->settings->container->selector = "topbarcharunprod";
$topbarcharunprod->settings->container->height = "300px";
$topbarcharunprod->settings->container->width = "685px";

$topbarcharunprod->height = 300;	//	ajax fix
$topbarcharunprod->width = 685;		//	ajax fix
$topbarcharunprod->theme = "theme1";
$topbarcharunprod->animationEnabled = pdf_animate();
$topbarcharunprod->interactivityEnabled = true;
$topbarcharunprod->barwidth = 30;
$topbarcharunprod->axisX->labelWrap = true;
$topbarcharunprod->axisX->labelMaxWidth = 100;
$topbarcharunprod->axisX->labelFontSize = 11;
$topbarcharunprod->axisY->labelFontSize = 11;
$topbarcharunprod->axisX->interval = 1;
$topbarcharunprod->axisX->labelAngle = 270;
$topbarcharunprod->axisY->minimum = 0;
$topbarcharunprod->axisY->suffix = "h";
$topbarcharunprod->legend->verticalAlign = "bottom";
$topbarcharunprod->legend->horizontalAlign = "center";

$topbarcharunprod->data[0]->type = "column";

foreach ($durations as $member_id => $duration){
	if($i < 15){
		$tags = $data[$member_id];
			$topbarcharunprod->data[0]->dataPoints[$i]->y = (float)number_format($tags['duration'] / 3600,2,'.',',');
			$topbarcharunprod->data[0]->dataPoints[$i]->toolTipContent = trialEncrypt($tags['name']) . ' - ' . format_time($tags['duration']);
			$topbarcharunprod->data[0]->dataPoints[$i]->label = trialEncrypt($tags['name']);
			$topbarcharunprod->data[0]->dataPoints[$i]->color = "#" . $_SESSION['colors'][$i];
		$ftx->assign(array(
			'NAME' => trialEncrypt($tags['name']),
			'TIME' => $tags['duration'] / 3600,
			'COLOR' => $_SESSION['colors'][$i] 
		));
	}
	$i++;
}
$ftx->assign('TOPBAR_CHART',drawGraph($topbarcharunprod));
	
	//	modified for pdf	--->
	$export_header = get_export_header($_SESSION['filters']['f']);
	extract($export_header,EXTR_OVERWRITE);
	$ftx->assign(array(
		'PDF_HEADER' => pdf_header(),
		'PDF_HIDE' => pdf_hide(),
		'PDF_CLASS' => pdf_class(),
		'TITLE' => $ftx->lookup('Top Unproductive'),
		'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
		'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
	));
	//	<---	modified for pdf

$ftx->parse('CONTENT','main');

	//	modified for pdf	--->
	if($_REQUEST['render'] == 'pdf'){
		$page = 'topunproductive';
		$html = $ftx->fetch('CONTENT');
			file_put_contents(CURRENT_VERSION_FOLDER.'temp_pdf/'.$page.'.html',"\xEF\xBB\xBF" . $html);
		loadPDF($page,'inline');exit;
	} else {
		return $ftx->fetch('CONTENT');
	}
	//	<---	modified for pdf