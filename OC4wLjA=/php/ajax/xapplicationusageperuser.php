<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
set_time_limit(0);
$ftx=new ft(ADMIN_PATH.MODULE."templates/");
$ftx->define(array('main' => "xapplicationusageperuser.html"));
$ftx->define_dynamic('template_row','main');

$dbu = new mysql_db();
        
$filters = get_filters($glob['t'],$glob['f'],$glob['time'],true);
extract($filters,EXTR_OVERWRITE);

$categories = get_categories($glob['f'],0);

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

$session = $dbu->query("SELECT SUM(session_application.duration) AS duration,
session.session_id,
member.member_id 
FROM session_application 
INNER JOIN session ON session.session_id = session_application.session_id
".$app_join." 
WHERE 1=1 
AND session_application.time_type = 0
". $app_filter." 
GROUP BY member.member_id");
$i=0;
$total = array();
$user_color = array();

while($session->next()){
	$total[$session->f('member_id')] = $session->f('duration');
	$user_color[$session->f('member_id')] = $_SESSION['colors'][$i];
	$i++;
}
/*print_r($total);
$bug = get_debug_instance();
$glob['sql'] = array_pop($bug->display());*/

$application = $dbu->query("SELECT SUM(session_application.duration) as app_duration,
application.description as name, 
session_application.application_id,
application_productivity.productive,
member.logon,
CONCAT(member.first_name,' ',member.last_name) AS member_name,
member.alias,
member.member_id
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
GROUP BY session_application.application_id,member.member_id
ORDER BY member.member_id, app_duration desc ".$number_of_rows);

$i = 0;

$prev = 0;
while ($application->next()){
	
	$proc = ($application->f('app_duration') * 100 / $total[$application->f('member_id')]);
	
	$cat_name = $ftx->lookup('Uncategorised');
	$cat_id = 1;
	if(isset($categories[$application->f('application_id').'-0'])){
		$cat_name = $categories[$application->f('application_id').'-0']['category'];
		$cat_id = $categories[$application->f('application_id').'-0']['category_id'];
	}
	
	if(!$prev)
	{
		$prev= $application->f('member_id');
		$ftx->assign('TOP_BORDER','');
	}
	else
	{
		if ($application->f('member_id') != $prev)
		{
			$prev= $application->f('member_id');
			$ftx->assign('TOP_BORDER','top_border');
		}
		else 
		{
			$ftx->assign('TOP_BORDER','');
		}	
	}
	
	$ftx->assign(array(
		'USERNAME'	=> $application->f('alias') ? $application->f('member_name') : $application->f('logon'),
		'COLOR' => $user_color[$application->f('member_id')],
		'NAME' => $application->f('name'),
		'PROCENT' => ($proc > 1) ? number_format($proc,2,',','.') : ' < 1',
		'TIME' => format_time($application->f('app_duration')),
		'WIDTH' => ceil(($application->f('app_duration') * 140) / $total[$application->f('member_id')]),
		/*'ID' => $dbu->f('application_id'),
		'SEL' => !is_null($application->f('productive')) && in_array($application->f('productive'),array(0,2)) ? $application->f('productive') : 1,
		'CSS_CLASS' => 'neutral',*/
		'APPLICATION_ID' => $dbu->f('application_id'),
		'CATEGORY' => $cat_name,
		'CATEGORY_ID' => $cat_id,
	));
	$ftx->parse('TEMPLATE_ROW_OUT','.template_row');
	$i++;
}



if($i==0)
{
	$ftx->assign(array(
		'NO_DATA_MESSAGE' => get_error($ftx->lookup('No data to display for your current filters'),'warning'),
		'HIDE_CONTENT'	=> 'hide',
	));
}
else 
{
	$ftx->assign(array(
		'NO_DATA_MESSAGE' => '',
		'HIDE_CONTENT'	=> '',
	));
	


		//	**************************************
		//	**************************************
		//	**************************************


		$pieces = explode('-',$glob['f']);
		$pieces[0] = substr($pieces[0],1);

		if(!$pieces[0]){
			$pieces[0] = 1;
		}

		$positions = $dbu->row("SELECT lft,rgt FROM department WHERE department_id =".$pieces[0]);


		$filter = '';
		$filter_join = '';
		// if(in_array($_SESSION[ACCESS_LEVEL],array(MANAGER_LEVEL,LIMITED_LEVEL))){
		if(in_array($_SESSION[ACCESS_LEVEL],array(MANAGER_LEVEL,DPO_LEVEL))){
			$filter_join = 'INNER JOIN member2manage ON member2manage.member_id = member.member_id';
			$filter = ' AND member2manage.manager_id = '.$_SESSION[U_ID];
		}


		$members = $dbu->query("SELECT member.member_id, member.logon,member.active, member.last_name,member.first_name FROM department  
		INNER JOIN member ON member.department_id = department.department_id 
		".$filter_join."
		WHERE lft >= ".$positions['lft']." AND lft <= ".$positions['rgt']." ".$filter);


		$member_array = array();
		$application_array = array();

		while ($members->next()){
			$member_array[$members->f('member_id')]['name']=$members->f('active') > 1 ? ($members->f('first_name').' '.$members->f('last_name')) : $members->f('logon');
			$member_array[$members->f('member_id')]['active'] = false;
		}
		foreach ($member_array as $member_id => &$member){	
			$applications = $dbu->query("SELECT SUM(session_application.duration)/3600 as app_duration,
			application.description as name, 
			session_application.application_id FROM session_application 
			INNER JOIN application ON application.application_id = session_application.application_id
			INNER JOIN session ON session.session_id = session_application.session_id
			".$app_join."
			WHERE session_application.duration > 0
			AND session_application.time_type = 0
			".$app_filter."
			AND member.member_id = '".$member_id."'
			GROUP BY session_application.application_id
			ORDER BY app_duration desc");
			
			$i = 0;
			if($applications->getTotalRecords()){
				$member['active'] = true;
				while ($applications->next()){
					if(!array_key_exists ($applications->f('application_id') ,$application_array)){
						$application_array[$applications->f('application_id')]['name']=decode_numericentity($applications->f('name'));
						$application_array[$applications->f('application_id')]['color']=$_SESSION['colors'][$i];
						$application_array[$applications->f('application_id')]['duration']==array();
					}		
					$application_array[$applications->f('application_id')]['duration'][$member_id] = ($applications->f('app_duration') ? $applications->f('app_duration') : 0);
					$i++;
				}
			}
			unset($member);
		}

		foreach ($member_array as $member_id => $member){
			if($member['active'] == true){
				$chartdata[$member['name']]['name'] = $member['name'];
				$ftx->assign('MEMBER_NAME',decode_numericentity($member['name']));
			}
		}

		reset($application_array);

		$groupappusagechart = new stdClass;
		$groupappusagechart->settings->container->selector = "groupappusagechart";
		$groupappusagechart->settings->container->height = "300px";
		$groupappusagechart->settings->container->width = "685px";

		$groupappusagechart->height = 300;	//	ajax fix
		$groupappusagechart->width = 685;		//	ajax fix
$groupappusagechart->barwidth = 30;
		$groupappusagechart->axisX->labelFontSize = 11;
		$groupappusagechart->axisY->labelFontSize = 11;
$groupappusagechart->axisX->labelAngle = 270;
$groupappusagechart->axisX->labelWrap = true;
$groupappusagechart->axisX->labelMaxWidth = 100;
$groupappusagechart->axisX->interval = 1;
		$groupappusagechart->axisY->suffix = "h";
		$groupappusagechart->axisY->minimum = 0;
		$groupappusagechart->axisY->labelFontSize = 10;

		$i = 0;
		foreach ($application_array as $application){
			$ftx->assign(array(
				'COLOR' => $application['color'],
				'APPLICATION_NAME' => $application['name'],
			));

			$groupappusagechart->data[$i]->type = "stackedColumn";
			$groupappusagechart->data[$i]->toolTipContent = $application['name'] . ": {y}h";
			$j = 0;
			foreach ($member_array as $member_id => $member){
				if($member['active']  == true && $j < 15){
						$ftx->assign('TIME',$application['duration'][$member_id]);
						$groupappusagechart->data[$i]->dataPoints[$j]->y = (float)number_format($application['duration'][$member_id],2);
						$groupappusagechart->data[$i]->dataPoints[$j]->label = $member['name'];
						$groupappusagechart->data[$i]->dataPoints[$j]->color = '#' . $application['color'];
					$j++;
				}
			}
			$i++;
		}


		$ftx->assign('APPUSAGE_USER_CHART_OUTPUT',drawGraph($groupappusagechart));
		//	-----------------------------------------------------------
}
	
	//	modified for pdf	--->
	$export_header = get_export_header($_SESSION['filters']['f']);
	extract($export_header,EXTR_OVERWRITE);
	$ftx->assign(array(
		'PDF_HEADER' => pdf_header(),
		'PDF_HIDE' => pdf_hide(),
		'PDF_CLASS' => pdf_class(),
		'TITLE' => $ftx->lookup('Application Usage per User'),
		'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
		'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
	));
	//	<---	modified for pdf

$export_header = get_export_header($glob['f']);
extract($export_header,EXTR_OVERWRITE);
$glob['append'] = trialEncrypt($member_name);

$ftx->parse('CONTENT','main');

	//	modified for pdf	--->
	if($_REQUEST['render'] == 'pdf'){
		$page = 'appusageperuser';
		$html = $ftx->fetch('CONTENT');
			file_put_contents(CURRENT_VERSION_FOLDER.'temp_pdf/'.$page.'.html',"\xEF\xBB\xBF" . $html);
		loadPDF($page,'inline');exit;
	} else {
		return $ftx->fetch('CONTENT');
	}
	//	<---	modified for pdf