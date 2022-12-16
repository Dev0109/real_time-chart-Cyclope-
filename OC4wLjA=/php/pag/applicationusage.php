<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/

set_time_limit(0);
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => "applicationusage.html"));
$ft->define_dynamic('template_row','main');
$ft->define_dynamic('template_other_row','main');

$dbu = new mysql_db();

if(($_SESSION['NUMBER_OF_ROWS']) && (is_numeric($_SESSION['NUMBER_OF_ROWS'])))
	$number_of_rows =  "LIMIT 0,".$_SESSION['NUMBER_OF_ROWS'];

$filters = get_filters($glob['t'],$glob['f'],$glob['time'],true);
extract($filters,EXTR_OVERWRITE);

$categories = get_categories($glob['f'],'all');
/*print "<pre>";
print_r($categories);
print "</pre>";*/
$categories_this = $categories;

$nodes = explode('-',$glob['f']);
$department_id = reset($nodes);
unset($nodes);
$export_header = get_export_header($glob['f']);
extract($export_header,EXTR_OVERWRITE);
$glob['append'] = trialEncrypt($member_name);
if(!$_REQUEST['render'] || ($_REQUEST['tab'] == 'peruser' && $_REQUEST['pag'] == 'applicationusage') ){
$ft->assign(array(
	'PERUSER_TABLE' => include(CURRENT_VERSION_FOLDER.'php/ajax/xapplicationusageperuser.php')
));
}

if(!$_REQUEST['render'] || (!$_REQUEST['tab'] && $_REQUEST['pag'] == 'applicationusage')) {

	$session = $dbu->row("SELECT SUM(session_application.duration) AS duration,session.session_id FROM session_application
	INNER JOIN session ON session.session_id = session_application.session_id
	".$app_join."  WHERE 1 = 1 
	AND session_application.time_type = 0
	". $app_filter);
	$total = $session['duration'];
	//	lorand
	$sortable_columns = array(
		'app_duration',
		'name',
		'productive',
		);

	$sortcolumns = get_sorting($sortable_columns,'','desc');

	$ft->assign(array(
		'ANCHOR_INNER_0' => render_anchor_inner(0),
		'ANCHOR_INNER_1' => render_anchor_inner(1),
		'ANCHOR_INNER_2' => render_anchor_inner(2),
		'DEBUGMESSAGE' => '',
		// 'DEBUGMESSAGE' => basename($_SERVER['PHP_SELF']),
		// 'DEBUGMESSAGE' => $sortcolumns,
	));
	//END
	$application = $dbu->query("SELECT SUM(session_application.duration) as app_duration,
	application.description as name, 
	session_application.application_id,
	application.application_type as type,
	COALESCE(application_productivity.productive,1) AS productive,
	(SELECT GROUP_CONCAT(application_productivity_id) FROM application_productivity
		WHERE application_productivity.link_id = application.application_id ) AS parrentaps
	FROM session_application 
	INNER JOIN application ON application.application_id = session_application.application_id
	INNER JOIN session ON session.session_id = session_application.session_id
	".$app_join."
	LEFT JOIN application_productivity ON application_productivity.department_id = 1 
	AND application_productivity.link_id = application.application_id 
	AND application_productivity.link_type = 0
	WHERE session_application.duration > 0 
	AND session_application.time_type = 0
	".$app_filter."
	GROUP BY session_application.application_id
	" . $sortcolumns . " ".$number_of_rows);

	$i = 0;

	while ($i < 15 && $application->next()){
		
		$proc = ($application->f('app_duration') * 100 / $total);
		$cat_name = $ft->lookup('Uncategorised');
		$cat_id = 1;

		if(isset($categories_this[$application->f('application_id').'-0'])){
			$cat_name = $ft->lookup($categories_this[$application->f('application_id').'-0']['category']);
			$cat_id = $categories_this[$application->f('application_id').'-0']['category_id'];
		}
		else if(isset($categories_this[$application->f('application_id').'-1'])){
				$cat_name = $ft->lookup($categories_this[$application->f('application_id').'-1']['category']);
				$cat_id = $categories_this[$application->f('application_id').'-1']['category_id'];
			}
			else if(isset($categories_this[$application->f('application_id').'-2'])){
					$cat_name = $ft->lookup($categories_this[$application->f('application_id').'-2']['category']);
					$cat_id = $categories_this[$application->f('application_id').'-2']['category_id'];
				}
				else if(isset($categories_this[$application->f('application_id').'-3'])){
						$cat_name = $ft->lookup($categories_this[$application->f('application_id').'-3']['category']);
						$cat_id = $categories_this[$application->f('application_id').'-3']['category_id'];
					}
		
		
		$ft->assign(array(
			'DEPARTMENT' => $department_id,
			'COLOR' => $_SESSION['colors'][$i],
			'NAME' => $application->f('name'),
			'PROCENT' => ($proc > 1) ? number_format($proc,2,',','.') : ' < 1',
			'TIME' => format_time($application->f('app_duration')),
			'WIDTH' => ceil(($application->f('app_duration') * 140) / $total),
			'APPLICATION_ID' => $application->f('application_id'),
			'CATEGORY' => $cat_name,
			'CATEGORY_ID' => $cat_id,
			'HIDE_OTHER_CATEGORY' => '',
			'HELP_LINK' => 'help.php?pag='.str_replace("simple","",$glob['pag']),
			'CAT_TYPE' => $application->f('type'),
		));
		/* switch ($application->f('productive')){
			case 0:
				$ft->assign(array(
					'TYPE'=> $ft->lookup('Distracting'),
					'CSS_CLASS'=> 'distracting',
					'TYPE_INPUT' => '<span class="flobn-slider distracting"' . pdf_hide() . '></span>'
				));
				break;			
			case 2:
				$ft->assign(array(
					'TYPE'=> $ft->lookup('Productive'),
					'CSS_CLASS'=> 'productive',
					'TYPE_INPUT' => '<span class="flobn-slider productive"' . pdf_hide() . '></span>'
				));
				break;
			case 3:
				$glob['app_duration'] = $application->f('app_duration');
				$glob['app'] = $application->f('application_id');
				$glob['type'] = $application->f('application_type');
				$glob['parentapp'] = $application->f('parrentaps');
				$ft->assign(array(
					'TYPE_INPUT' => include(CURRENT_VERSION_FOLDER.'php/ajax/xproductivitybar.php'),
					'TYPE'=> $glob['procs'],
					'CSS_CLASS' => $glob['procs']
				));
				$glob['procs'] = '';
				$glob['type'] = '';
				$glob['parentapp'] = '';
				break;
			default:
				$ft->assign(array(
					'TYPE'=> $ft->lookup('Neutral'),
					'CSS_CLASS'=> 'neutral',
					'TYPE_INPUT' => '<span class="flobn-slider neutral"' . pdf_hide() . '></span>'
				));
		}*/
		
		
		$ft->parse('TEMPLATE_ROW_OUT','.template_row');
		$i++;
	}
	$j = 0;

	while ($application->next())
	{
		$proc = ($application->f('app_duration') * 100 / $total);
		
		$cat_name = 'Uncategorised';
		$cat_id = 1;
		if(isset($categories_this[$application->f('application_id').'-0'])){
			$cat_name = $ft->lookup($categories_this[$application->f('application_id').'-0']['category']);
			$cat_id = $categories_this[$application->f('application_id').'-0']['category_id'];
		}
		else if(isset($categories_this[$application->f('application_id').'-1'])){
				$cat_name = $ft->lookup($categories_this[$application->f('application_id').'-1']['category']);
				$cat_id = $categories_this[$application->f('application_id').'-1']['category_id'];
			}
			else if(isset($categories_this[$application->f('application_id').'-2'])){
					$cat_name = $ft->lookup($categories_this[$application->f('application_id').'-2']['category']);
					$cat_id = $categories_this[$application->f('application_id').'-2']['category_id'];
				}
				else if(isset($categories_this[$application->f('application_id').'-3'])){
						$cat_name = $ft->lookup($categories_this[$application->f('application_id').'-3']['category']);
						$cat_id = $categories_this[$application->f('application_id').'-3']['category_id'];
					}

		$ft->assign(array(
			'OTHER_DEPARTMENT' => $department_id,
			'OTHER_COLOR' => end($_SESSION['colors']),
			'OTHER_NAME' => $application->f('name'),
			'OTHER_PROCENT' => ($proc > 1) ? number_format($proc,2,',','.') : ' < 1',
			'OTHER_TIME' => format_time($application->f('app_duration')),
			'OTHER_WIDTH' => ceil(($application->f('app_duration') * 140) / $total),
			'OTHER_ID' => $application->f('application_id'),
			'OTHER_APPLICATION_ID' => $application->f('application_id'),
			
			'OTHER_CATEGORY' => $cat_name,
			'OTHER_CATEGORY_ID' => $cat_id,
			'OTHER_CAT_TYPE' => $application->f('type'),
		));
		/*switch ($application->f('productive')){
			case 0:
				$ft->assign(array(
					'OTHER_TYPE'=> $ft->lookup('Distracting'),
					'OTHER_CSS_CLASS'=> 'distracting',
					'OTHER_TYPE_INPUT' => '<span class="flobn-slider distracting"></span>'
				));
				break;			
			case 2:
				$ft->assign(array(
					'OTHER_TYPE'=> $ft->lookup('Productive'),
					'OTHER_CSS_CLASS'=> 'productive',
					'OTHER_TYPE_INPUT' => '<span class="flobn-slider productive"></span>'
				));
				break;
			case 3:
				$glob['app_duration'] = $application->f('app_duration');
				$glob['app'] = $application->f('application_id');
				$glob['type'] = $application->f('application_type');
				$ft->assign(array(
					'OTHER_TYPE_INPUT' => include(CURRENT_VERSION_FOLDER.'php/ajax/xproductivitybar.php'),
					'OTHER_TYPE'=> $glob['procs'],
					'OTHER_CSS_CLASS' => $glob['procs']
				));
				$glob['procs'] = '';
				break;
			default:
				$ft->assign(array(
					'OTHER_TYPE'=> $ft->lookup('Neutral'),
					'OTHER_CSS_CLASS'=> 'neutral',
					'OTHER_TYPE_INPUT' => '<span class="flobn-slider neutral"></span>'
				));
		}*/
		
		
		$ft->parse('TEMPLATE_OTHER_ROW_OUT','.template_other_row');
			
		$total_proc += $proc;
		$total_duration += $application->f('app_duration');
		
		$j++;
	}

	if($j)
	{

		$ft->assign(array(
			'COLOR' => end($_SESSION['colors']),
			'NAME' => '<a href="#" class="toggleother">'.$ft->lookup('Others').'</a>',
			'PROCENT' => number_format($total_proc,2,',','.'),
			'TIME' => format_time($total_duration),
			'WIDTH' => ceil(($total_duration * 140) / $total),
			'CSS_CLASS'=> '',
			'TYPE_INPUT' => '',
			'HIDE_OTHER_CATEGORY' => 'hide',
			'CAT_TYPE' => $application->f('type'),
		));
		
		$ft->parse('TEMPLATE_ROW_OUT','.template_row');
	}

		$trial = $dbu->field("SELECT value FROM `settings` WHERE `constant_name` = 'TRUENC'");
		$ecrypted_text = $dbu->field("SELECT message FROM `notification` WHERE `constant_name` = 'ENCRYPTED_TEXT'");
		if ($trial != 2236985){
			$ft->assign('ENCRYPTMESSAGERAW', '<div class="encryptmessage">' . $ft->lookup($ecrypted_text) . '</div>');
		}

	if($i==0)
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
			'TITLE' => $ft->lookup('Applications'),
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		//	<---	modified for pdf

	$dates = $dbu->row("SELECT MIN(date) AS genesis,MAX(date) AS last_day_on_earth FROM session ");

	$ft->assign(array(
		'DEFAULT_VALUE' => isset($glob['time']) ? $glob['time']['time']: date('n/j/Y',$dates['genesis']).' - '.date('n/j/Y',$dates['last_day_on_earth']) ,
		'DATE_BEFORE' => date('n/j/Y',$dates['genesis']),
		'TIME_'.($glob['time']['type'] ? $glob['time']['type'] : 1) => 'selected="selected"'
	));
	//
	/*$type = count(explode('-',$glob['f'])) == 1 ? true : false ;
	$glob['applicationUsageType'] = ($type ? 'xmlgroupusage' :'xmlapplicationusage');*/
	$glob['applicationUsageType'] = 'xmlapplicationusage';
	global $bottom_includes;
	$bottom_includes.='
	<script type="text/javascript">flobn.register("genesis",new Date("'.date('n/j/Y',$dates['genesis']).'"));flobn.register("applicationUsageType","'.($type ? 'xmlgroupusage' :'xmlapplicationusage').'");</script><script type="text/javascript" src="ui/applicationusage-ui.js"></script>';

	if(count(explode('-',$glob['f'])) == 1){

		$bottom_includes.= '<script type="text/javascript">flobn.register("thouShallNotMove",0,true);</script>';	
		$glob['thouShallNotMove'] = 0;
	}else{
		$bottom_includes.= '<script type="text/javascript">flobn.register("thouShallNotMove",1,true);</script>';	
		$glob['thouShallNotMove'] = 1;
	}

	//	**************************************
	//	**************************************
	//	**************************************

	$session = $dbu->row("SELECT SUM(session_application.duration) AS duration,session.session_id FROM session_application
	INNER JOIN session ON session.session_id = session_application.session_id
	".$app_join."  WHERE 1 = 1 AND session_application.time_type = 0 ". $app_filter);

	$total = $session['duration'];

	$dbu->query("SELECT SUM(session_application.duration) as app_duration,application.description as name FROM session_application 
	INNER JOIN application ON application.application_id = session_application.application_id
	INNER JOIN session ON session.session_id = session_application.session_id
	".$app_join."
	WHERE session_application.duration > 0 AND session_application.time_type = 0 
	".$app_filter."
	GROUP BY session_application.application_id
	ORDER BY app_duration desc");
	$i = 0;
	$tot = 0;

	$appusagechart = new stdClass;
	$appusagechart = array( "settings" => array( "container" => array("selector" => "appusagechart", "height" => "300px", "width" => "690px")), "theme" => "theme1");
	// $appusagechart->settings->container->selector = "appusagechart";
	// $appusagechart->settings->container->height = "300px";
	// $appusagechart->settings->container->width = "690px";

	// $appusagechart->theme = "theme1";
	$appusagechart['animationEnabled'] = pdf_animate();
	$appusagechart['interactivityEnabled'] = true;
	$appusagechart['axisY']['valueFormatString'] = ' ';
	$appusagechart['axisY']['tickLength'] = 0;
	$appusagechart['axisY']['margin'] = 80;
	$appusagechart['axisX']['margin'] = 80;

	$appusagechart['data[0]']['type'] = "pie";
	$appusagechart['data[0]']['startAngle'] = -90;
	$appusagechart['data[0]']['indexLabelFontColor'] = "#000000";
	$appusagechart['data[0]']['toolTipContent'] = "{legendText} - {y}%";

	while ($dbu->move_next() && $i < 15){
		$proc = ($dbu->f('app_duration') * 100 / $total);
		$appusagechart['data[0]']['dataPoints[$i]']['y'] = (float)number_format($proc,2,'.',',');
		$appusagechart['data[0]']['dataPoints[$i]']['legendText'] = decode_numericentity($dbu->f('name'));
		$appusagechart['data[0]']['dataPoints[$i]']['label'] = decode_numericentity($dbu->f('name'));
		$appusagechart['data[0]']['dataPoints[$i]']['color'] = "#" . $_SESSION['colors'][$i];
		
		$ft->assign(array(
			'NAME' => decode_numericentity($dbu->f('name')),
			'PROCENT' => number_format($proc,2,'.',','), 
			'VALUE' => $dbu->f('app_duration'), 
			'COLOR' => $_SESSION['colors'][$i] 
		));
		
		$tot +=  $dbu->f('app_duration');
		$i++;
	}
	if($total != $tot){
		$proc = (($total-$tot) * 100 / $total);
		$appusagechart['data[0]']['dataPoints[$i]']['y'] = (float)number_format($proc,2,'.',',');
		$appusagechart['data[0]']['dataPoints[$i]']['legendText'] = $ft->lookup('Others');
		$appusagechart['data[0]']['dataPoints[$i]']['label'] = $ft->lookup('Others');
		$appusagechart['data[0]']['dataPoints[$i]']['color'] = "#" . end($_SESSION['colors']);
		$ft->assign(array(
			'NAME' => '[!L!]Others[!/L!]',
			'PROCENT' => number_format($proc,2,'.',','), 
			'VALUE' => $total-$tot,
			'COLOR' => end($_SESSION['colors'])
		));
	}
		
	$ft->assign('APPUSAGE_CHART_OUTPUT',drawGraph($appusagechart));

	//	-----------------------------------------------------------
}

$ft->assign('PAGE_TITLE',$ft->lookup('Applications for'));
$ft->assign('APPEND', $glob['append']);
if(!$glob['is_ajax']){
	$ft->define_dynamic('ajax','main');
	$ft->parse('AJAX_OUT','ajax');
}
$ft->parse('CONTENT','main');
//$ft->fastprint('CONTENT');
	//	modified for pdf	--->
	if($_REQUEST['render'] == 'pdf'){
		$page = 'appusage';
		$html = $ft->fetch('CONTENT');
			file_put_contents(CURRENT_VERSION_FOLDER.'temp_pdf/'.$page.'.html',"\xEF\xBB\xBF" . $html);
		loadPDF($page,'inline');exit;
	} else {
		return $ft->fetch('CONTENT');
	}
	//	<---	modified for pdf
