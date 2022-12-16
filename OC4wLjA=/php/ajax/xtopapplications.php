<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftx=new ft(ADMIN_PATH.MODULE."templates/");
$ftx->define(array('main' => "xtopapplications.html"));
$ftx->define_dynamic('template_row','main');
$ftx->define_dynamic('other_row','main');

$dbu = new mysql_db();

$filters = get_filters($glob['t'],$glob['f'],$glob['time'],true);
extract($filters,EXTR_OVERWRITE);

$categories = get_categories($glob['f'],'all');
$nodes = explode('-',$glob['f']);
$department_id = reset($nodes);
unset($nodes);


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

//select all and make a total

$session = $dbu->row("SELECT SUM(session_application.duration) AS duration,session.session_id FROM session_application
INNER JOIN session ON session.session_id = session_application.session_id
".$app_join."  WHERE 1 = 1 AND session_application.time_type = 0 ". $app_filter);
$total = $session['duration'];

//	lorand
$sortable_columns = array(
	'app_duration',
	'name',
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

$application = $dbu->query("SELECT SUM(session_application.duration) as app_duration,
application.description as name,
application.application_type as type,
session_application.application_id 
FROM session_application 
INNER JOIN application ON application.application_id = session_application.application_id
INNER JOIN session ON session.session_id = session_application.session_id
".$app_join."
WHERE session_application.duration > 0
AND session_application.time_type = 0 
".$app_filter."
GROUP BY session_application.application_id
" . $sortcolumns . " " .$number_of_rows);
$i = 0;
$tot = 0;

while ($i < 15 && $application->next()){
	
	$proc = ($application->f('app_duration') * 100 / $total);
	
	$dbu->query("SELECT SUM(session_application.duration) as app_duration,application.description as name,
					member.member_id,
					member.logon,
					member.alias,
					member.first_name,
					member.last_name,
					member.active FROM session_application 
INNER JOIN application ON application.application_id = session_application.application_id
INNER JOIN session ON session.session_id = session_application.session_id
".$app_join."
WHERE session_application.duration > 0 AND session_application.application_id = '".$application->f('application_id')."'
AND session_application.time_type = 0 ".$app_filter."
GROUP BY member.member_id
ORDER BY app_duration desc");
	$user = '';
	$usercount = 0;
	while ($dbu->move_next()) {
		$logon = trialEncrypt($dbu->f('alias') == 1 ? $dbu->f('first_name').' '.$dbu->f('last_name') : $dbu->f('logon'));
		$user .= $usercount + 1 . ') ' . $logon.' - '.format_time($dbu->f('app_duration')).'<br/>';
		$usercount++;
	}
	
	$cat_name = $ftx->lookup('Uncategorised');
	$cat_id = 1;
	if(isset($categories[$application->f('application_id').'-0'])){
			$cat_name = $ftx->lookup($categories[$application->f('application_id').'-0']['category']);
			$cat_id = $categories[$application->f('application_id').'-0']['category_id'];
		}
		else if(isset($categories[$application->f('application_id').'-1'])){
				$cat_name = $ftx->lookup($categories[$application->f('application_id').'-1']['category']);
				$cat_id = $categories[$application->f('application_id').'-1']['category_id'];
			}
			else if(isset($categories[$application->f('application_id').'-2'])){
					$cat_name = $ftx->lookup($categories[$application->f('application_id').'-2']['category']);
					$cat_id = $categories[$application->f('application_id').'-2']['category_id'];
				}
				else if(isset($categories[$application->f('application_id').'-3'])){
						$cat_name = $ftx->lookup($categories[$application->f('application_id').'-3']['category']);
						$cat_id = $categories[$application->f('application_id').'-3']['category_id'];
					}

	
	$ftx->assign(array(
		'DEPARTMENT' => $department_id,
		'NAME' => $application->f('name') . ' (' . $usercount . ')',
		'USER' => $user,
		'PROCENT' => ($proc > 1) ? number_format($proc,2,',','.') : ' < 1',
		'TIME' => format_time($application->f('app_duration')),
		'WIDTH' => ceil(($application->f('app_duration') * 140) / $total),
		'COLOR' => $_SESSION['colors'][$i],
		'APPLICATION_ID' => $application->f('application_id'),
		'CATEGORY' => $cat_name, 
		'CATEGORY_ID' => $cat_id,
		'HIDE_OTHER_CATEGORY' => '',
		'CAT_TYPE' => $application->f('type'),
	));
	
	$top_total +=  $application->f('app_duration');
	$top_procent += number_format($proc,2);
	
	$ftx->parse('TEMPLATE_ROW_OUT','.template_row');
	$i++;
}


//asign the rest
$j=0;
while ($application->next()){
	
	$proc = ($application->f('app_duration') * 100 / $total);
	
	$dbu->query("SELECT SUM(session_application.duration) as app_duration,application.description as name,
					member.member_id,
					member.logon,
					member.first_name,
					member.last_name,
					member.active FROM session_application 
INNER JOIN application ON application.application_id = session_application.application_id
INNER JOIN session ON session.session_id = session_application.session_id
".$app_join."
WHERE session_application.duration > 0 AND session_application.application_id = '".$application->f('application_id')."'
AND session_application.time_type = 0 ".$app_filter."
GROUP BY member.member_id
ORDER BY app_duration desc");
	$user = '';
	while ($dbu->move_next()) {
		$logon = trialEncrypt($dbu->f('active') > 1 ? $dbu->f('first_name').' '.$dbu->f('last_name') : $dbu->f('logon'));
		$user .= $logon.' - '.format_time($dbu->f('app_duration')).'<br/>';
	}
	
	$cat_name = $ftx->lookup('Uncategorised');
	$cat_id = 1;

	if(isset($categories[$application->f('application_id').'-0'])){
			$cat_name = $ftx->lookup($categories[$application->f('application_id').'-0']['category']);
			$cat_id = $categories[$application->f('application_id').'-0']['category_id'];
		}
		else if(isset($categories[$application->f('application_id').'-1'])){
				$cat_name = $ftx->lookup($categories[$application->f('application_id').'-1']['category']);
				$cat_id = $categories[$application->f('application_id').'-1']['category_id'];
			}
			else if(isset($categories[$application->f('application_id').'-2'])){
					$cat_name = $ftx->lookup($categories[$application->f('application_id').'-2']['category']);
					$cat_id = $categories[$application->f('application_id').'-2']['category_id'];
				}
				else if(isset($categories[$application->f('application_id').'-3'])){
						$cat_name = $ftx->lookup($categories[$application->f('application_id').'-3']['category']);
						$cat_id = $categories[$application->f('application_id').'-3']['category_id'];
					}

	
	$ftx->assign(array(
		'OTHER_DEPARTMENT' => $department_id,
		'OTHER_NAME' => $application->f('name'),
		'OTHER_USER' => $user,
		'OTHER_PROCENT' => ($proc > 1) ? number_format($proc,2,',','.') : ' < 1',
		'OTHER_TIME' => format_time($application->f('app_duration')),
		'OTHER_WIDTH' => ceil(($application->f('app_duration') * 140) / $total),
		'OTHER_COLOR' => end($_SESSION['colors']),
		'OTHER_APPLICATION_ID' => $application->f('application_id'),
		'OTHER_CATEGORY' => $cat_name, 
		'OTHER_CATEGORY_ID' => $cat_id,
		'OTHER_CAT_TYPE' => $application->f('type')
	));
	
	
	$ftx->parse('OTHER_ROW_OUT','.other_row');
	
	$other_total +=  $application->f('app_duration');
	$other_procent += number_format($proc,2);
	
	$j++;
}

if($j){
	$proc = ( $other_total * 100 / $total);
	$ftx->assign(array(
		'NAME' => '<a class="toggleother" href="#">[!L!]Others[!/L!]</a>',
		'USER' => '',
		'PROCENT' => number_format($proc,2),  
		'COLOR' => $_SESSION['colors'][$i],
		'TIME' => format_time($other_total),
		'WIDTH' => ceil ( $other_total * 140 / $total),
		'HIDE_OTHER_CATEGORY' => 'hide',
	));
	$ftx->parse('TEMPLATE_ROW_OUT','.template_row');
}

global $bottom_includes;
if(count(explode('-',$glob['f'])) == 1){
	$bottom_includes.= '<script type="text/javascript">flobn.register("thouShallNotMove",0,true);</script>';	
	$glob['thouShallNotMove'] = 0;
}else{
	$bottom_includes.= '<script type="text/javascript">flobn.register("thouShallNotMove",1,true);</script>';	
	$glob['thouShallNotMove'] = 1;
}

if(!$i)
{
	return '';
}

//	****************************
//	****************************
//	****************************

$session = $dbu->row("SELECT SUM(session_application.duration) AS duration,session.session_id FROM session_application
INNER JOIN session ON session.session_id = session_application.session_id
".$app_join."  WHERE 1 = 1  AND session_application.time_type = 0 ". $app_filter);
$total = $session['duration'];

//get top 6 apps
//desi par multe joinuri din cauza indexurilor ar trebui sa mearga foarte repede, este doar un mic file sort, 
//using temporary pe table de member sau computer cea ce e ok..ptr ca alea sunt cele mai mici tabele oricum
$dbu->query("SELECT SUM(session_application.duration) as app_duration,application.description as name FROM session_application 
INNER JOIN application ON application.application_id = session_application.application_id
INNER JOIN session ON session.session_id = session_application.session_id
".$app_join."
WHERE session_application.duration > 0 AND 1= 1 AND session_application.time_type = 0
".$app_filter."
GROUP BY session_application.application_id
ORDER BY app_duration desc");
$i = 0;
$tot = 0;

$appusagechart = new stdClass;
$appusagechart->settings->container->selector = "appusagechart";
$appusagechart->settings->container->height = "300px";
$appusagechart->settings->container->width = "650px";

$appusagechart->theme = "theme1";
$appusagechart->animationEnabled = pdf_animate();
$appusagechart->interactivityEnabled = true;
$appusagechart->axisY->valueFormatString = ' ';
$appusagechart->axisY->tickLength = 0;
$appusagechart->axisY->margin = 80;
$appusagechart->axisX->margin = 80;
$appusagechart->axisX->interval = 1;

$appusagechart->data[0]->type = "pie";
$appusagechart->data[0]->startAngle = -90;
$appusagechart->data[0]->indexLabelFontColor = "#000000";
$appusagechart->data[0]->toolTipContent = "{legendText} - {y}%";

while ($dbu->move_next() && $i < 15){
	$proc = ($dbu->f('app_duration') * 100 / $total);
	$appusagechart->data[0]->dataPoints[$i]->y = (float)number_format($proc,2,'.',',');
	$appusagechart->data[0]->dataPoints[$i]->legendText = decode_numericentity($dbu->f('name'));
	$appusagechart->data[0]->dataPoints[$i]->label = decode_numericentity($dbu->f('name'));
	$appusagechart->data[0]->dataPoints[$i]->color = "#" . $_SESSION['colors'][$i];
	$ftx->assign(array(
		'NAME' => decode_numericentity($dbu->f('name')),
		'SHORTNAME' => trim_text(decode_numericentity($dbu->f('name')),30),
		'PROCENT' => number_format($proc,2,'.',','), 
		'COLOR' => $_SESSION['colors'][$i],
	));
	
	$tot +=  $dbu->f('app_duration');
	$i++;
}
if($total != $tot){
	$proc = (($total-$tot) * 100 / $total);
	$appusagechart->data[0]->dataPoints[$i]->y = (float)number_format($proc,2,'.',',');
	$appusagechart->data[0]->dataPoints[$i]->legendText = $ftx->lookup('Others');
	$appusagechart->data[0]->dataPoints[$i]->label = $ftx->lookup('Others');
	$appusagechart->data[0]->dataPoints[$i]->color = "#" . end($_SESSION['colors']);
	$ftx->assign(array(
		'NAME' => '[!L!]Others[!/L!]',
		'SHORTNAME' => '[!L!]Others[!/L!]',
		'PROCENT' => number_format($proc,2,'.',','), 
		'VALUE' => $total-$tot, 
		'COLOR' => end($_SESSION['colors'])
	));
}
$ftx->assign('APPUSAGE_CHART_OUTPUT',drawGraph($appusagechart));
	
	//	modified for pdf	--->
	$export_header = get_export_header($_SESSION['filters']['f']);
	extract($export_header,EXTR_OVERWRITE);
	$ftx->assign(array(
		'PDF_HEADER' => pdf_header(),
		'PDF_HIDE' => pdf_hide(),
		'PDF_CLASS' => pdf_class(),
		'TITLE' => $ftx->lookup('Top Applications'),
		'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
		'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
	));
	//	<---	modified for pdf

$ftx->parse('CONTENT','main');

	//	modified for pdf	--->
	if($_REQUEST['render'] == 'pdf'){
		$page = 'topapps';
		$html = $ftx->fetch('CONTENT');
			file_put_contents(CURRENT_VERSION_FOLDER.'temp_pdf/'.$page.'.html',"\xEF\xBB\xBF" . $html);
		loadPDF($page,'inline');exit;
	} else {
		return $ftx->fetch('CONTENT');
	}
	//	<---	modified for pdf