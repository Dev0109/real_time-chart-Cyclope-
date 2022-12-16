<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
// exit;
$ftx=new ft(ADMIN_PATH.MODULE."templates/");
$ftx->define(array('main' => "productivityreport.html"));
$ftx->define_dynamic('template_row','main');
$dbu = new mysql_db();
$filters = get_filters($glob['t'],$glob['f'],$glob['time'],true);
$ajax_loaded = false;
extract($filters,EXTR_OVERWRITE);
	$trial = $dbu->field("SELECT value FROM `settings` WHERE `constant_name` = 'TRUENC'");
	$ecrypted_text = $dbu->field("SELECT message FROM `notification` WHERE `constant_name` = 'ENCRYPTED_TEXT'");
	if ($trial != 2236985){
		$ftx->assign('ENCRYPTMESSAGERAW', '<div class="encryptmessage">' . $ftx->lookup($ecrypted_text) . '</div>');
	}
//$categories = get_categories($glob['f'],0) + get_categories($glob['f'],3);
$categories = get_categories($glob['f'],'all');
/*print "<pre>";
print_r($categories);
print "</pre>";*/

$nodes = explode('-',$glob['f']);
$department_id = reset($nodes);

$export_header = get_export_header($glob['f']);
extract($export_header,EXTR_OVERWRITE);
$glob['append'] = trialEncrypt($member_name);

unset($nodes);
$session_website = get_session_website_table();
$session = $dbu->row("SELECT SUM(session_application.duration) AS duration FROM session_application
						INNER JOIN session ON session.session_id = session_application.session_id 
						".$app_join."
						WHERE session_application.time_type = 0 AND 1=1 ".$app_filter);
$productivity_filter = ' = 1';
$total = $session['duration'];
//	lorand
$sortable_columns = array(
	'app_duration',
	'name',
	'productive',
	);

$sortcolumns = get_sorting($sortable_columns,'','desc');

$ftx->assign(array(
	'ANCHOR_INNER_0' => render_anchor_inner(0),
	'ANCHOR_INNER_1' => render_anchor_inner(1),
	'ANCHOR_INNER_2' => render_anchor_inner(2),
	'DEBUGMESSAGE' => '',
	// 'DEBUGMESSAGE' => basename($_SERVER['PHP_SELF']),
	// 'DEBUGMESSAGE' => $sortcolumns,
));
//END
$department_list = get_department_children($department_id,1);
	
$all_parents =  $dbu->field("SELECT GROUP_CONCAT(application_id)  AS all_parrents FROM `application` WHERE `application_type` = 3");

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
                    GROUP BY   session_" . $session_website . ".domain_id
					" . $sortcolumns . " ";

$application = $dbu->query($querystring);
					

$i = 0; 
$main_productivity = array('red'=>0,'rest'=>0,'green'=>0);
while ($application->next()){
	if($i % 2){
		$class = 'even';
	}else{
		$class = '';
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
	
	if ($total < 1) {$total = 1;}
	$ftx->assign(array(
		'ICON_SRC' => $_REQUEST['render'] == 'pdf' ? '' : '<img class="vcentered" width="16" hspace="5" height="16" src="' . ($application->f('type') == 3 ? "http://www.google.com/s2/favicons?domain=" . $application->f('name') : pdf_media_location('img/icons/' . get_icon($application->f('name'),'document').'.png',CURRENT_VERSION_FOLDER)) . '" /> ',	//	modified for pdf
		'DEPARTMENT'     => $department_id,
		'NAME'       	 => $application->f('name'),
		'TIME'      	 => format_time($application->f('app_duration')),
		'WIDTH'     	 => ((($application->f('app_duration') * 100) / $total) > 1) ? number_format((($application->f('app_duration') * 100) / $total),2,',','.') : ' < 1',
		'APPLICATION_ID' => $application->f('application_id'),
		'CATEGORY'       => $cat_name,
		'CATEGORY_ID' 	 => $cat_id,
		'VIEW_CHILD'     => $view_child,
		'CLASS'          => $class,
		'CAT_TYPE'		 => $application->f('type'),
	));
	
	switch ($application->f('productive')){
		case 0:
			$ftx->assign(array(
				'TYPE'=> $ftx->lookup('Distracting'),
				'CSS_CLASS'=> 'distracting',
				'TYPE_INPUT' => '<span id="prod-slider-'.$application->f('application_id').'" ' . pdf_hide() . ' data-duration="'.$application->f('app_duration').'" data-parent="true" data-app="'.$application->f('application_id').'" data-type="'.$application->f('type').'" data-brother="'.$application->f('application_id').'" class="slider flobn-slider distracting" rel="'.$application->f('application_id').'"><a class="handle">&nbsp;</a></span>'
			));
			$main_productivity['red'] += $application['app_duration'];
			break;			
		case 2:
			$ftx->assign(array(
				'TYPE'=> $ftx->lookup('Productive'),
				'CSS_CLASS'=> 'productive',
				'TYPE_INPUT' => '<span id="prod-slider-'.$application->f('application_id').'"  ' . pdf_hide() . ' data-duration="'.$application->f('app_duration').'" data-parent="true" data-app="'.$application->f('application_id').'" data-type="'.$application->f('type').'" data-brother="'.$application->f('application_id').'" class="slider flobn-slider productive" rel="'.$application->f('application_id').'"><a class="handle">&nbsp;</a></span>'
			));
			$main_productivity['green'] += $application['app_duration'];
			break;
		default:
			$ftx->assign(array(
				'TYPE'=> $ftx->lookup('Neutral'),
				'CSS_CLASS'=> 'neutral',
				'TYPE_INPUT' => '<span id="prod-slider-'.$application->f('application_id').'"  ' . pdf_hide() . ' data-duration="'.$application->f('app_duration').'" data-parent="true" data-app="'.$application->f('application_id').'" data-type="'.$application->f('type').'" data-brother="'.$application->f('application_id').'" class="slider flobn-slider neutral" rel="'.$application->f('application_id').'"><a class="handle">&nbsp;</a></span>'
			));
	}
	
	
	$ftx->parse('TEMPLATE_ROW_OUT','.template_row');
	$i++;
}

$main_productivity['rest'] = $total - ($main_productivity['red'] + $main_productivity['green']);
$ftx->assign(array(
	'MAIN_RED' => $main_productivity['red'],
	'MAIN_GREEN' => $main_productivity['green'],
	'MAIN_REST' => $main_productivity['rest']
));

if ($total != 0)
{
include(CURRENT_VERSION_FOLDER.'php/chart/chart_productivityreport.php');
	$neutral_total = 100 - number_format($main_productivity['green'] / $total *100,2,'.',',') - number_format($main_productivity['red'] / $total * 100, 2,'.',',');
	$ftx->assign(array(
		'PRODUCTIVE_TOTAL' => number_format($main_productivity['green'] / $total *100,2,'.',','),
		'PRODUCTIVE_TOTAL_TIME' => format_time($main_productivity['green'],false),
		'PRODUCTIVE_TOTAL_TIME_RAW' => $main_productivity['green'],
		'DISTRACTING_TOTAL' => number_format($main_productivity['red'] / $total * 100, 2,'.',','),
		'DISTRACTING_TOTAL_TIME' => format_time($main_productivity['red'],false),
		'DISTRACTING_TOTAL_TIME_RAW' => $main_productivity['red'],
		'NEUTRAL_TOTAL' => (float) ($neutral_total) < 1 ? 0 : $neutral_total,
		'NEUTRAL_TOTAL_TIME' => format_time($main_productivity['rest'],false),
		'NEUTRAL_TOTAL_TIME_RAW' => $main_productivity['rest'],
		'TOTAL_TIME_RAW' => $main_productivity['rest'] + $main_productivity['red'] + $main_productivity['green'],
		'AJAX_DONUT' => drawGraph($prodpie),
		'HELPIMG' => CURRENT_VERSION_FOLDER . "/img/prodhelp.gif",
	));
}

if($i==0)
{
	$ftx->assign(array(
		'NO_DATA_MESSAGE' => get_error($ftx->lookup('No data to display for your current filters'),'warning'),
		'HIDE_CONTENT'	=> 'hide',
		'HIDE_STYLE' => 'style="display: none;"',
	));
}
else 
{
	$ftx->assign(array(
		'NO_DATA_MESSAGE' => '',
		'HIDE_CONTENT'	=> '',
	));
}
	
	//	modified for pdf	--->
	$export_header = get_export_header($_SESSION['filters']['f']);
	extract($export_header,EXTR_OVERWRITE);
	$ftx->assign(array(
		'PDF_HEADER' => pdf_header(),
		'PDF_HIDE' => pdf_hide(),
		'PDF_CLASS' => pdf_class(),
		'PDF_SUFFIX' => '&render=pdf',
		'TITLE' => $ftx->lookup('Productivity'),
		'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
		'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
	));
	//	<---	modified for pdf

$dates = $dbu->row("SELECT MIN(date) AS genesis,MAX(date) AS last_day_on_earth FROM session ".$total_join.' WHERE 1=1 '.$total_filter);

$ftx->assign(array(
	'PAGE_TITLE' => $ftx->lookup('Productivity for'),
	'APPEND' => $glob['append'],
	'DEFAULT_VALUE' => isset($glob['time']) ? $glob['time']['time']: date('n/j/Y',$dates['genesis']).' - '.date('n/j/Y',$dates['last_day_on_earth']) ,
	'DATE_BEFORE' => date('n/j/Y',$dates['genesis']),
	'TIME_'.($glob['time']['type'] ? $glob['time']['type'] : 1) => 'selected="selected"',
	'HELP_LINK' => 'help.php?pag=productivityreport',
));

if(!$glob['is_ajax']){
	$ftx->define_dynamic('ajax','main');
	$ftx->parse('AJAX_OUT','ajax');
}


global $bottom_includes;
$bottom_includes .= '
<script type="text/javascript">flobn.register("genesis",new Date("'.date('n/j/Y',$dates['genesis']).'"));</script><script type="text/javascript" src="ui/productivityreport-ui.js"></script>';
//department or user?
if(count(explode('-',$glob['f'])) == 1){
	$bottom_includes.= '<script type="text/javascript">flobn.register("thouShallNotMove",0);</script>';	
	$glob['thouShallNotMove'] = 0;
}else{
	$bottom_includes.= '<script type="text/javascript">flobn.register("thouShallNotMove",1);</script>';	
	$glob['thouShallNotMove'] = 1;
}
$site_meta_title=$meta_title;
$site_meta_keywords=$meta_keywords;
$site_meta_description=$meta_description;

$ftx->parse('CONTENT','main');

	//	modified for pdf	--->
	if($_REQUEST['render'] == 'pdf'){
		$page = 'productivity';
		$html = $ftx->fetch('CONTENT');
			file_put_contents(CURRENT_VERSION_FOLDER.'temp_pdf/'.$page.'.html',"\xEF\xBB\xBF" . $html);
		loadPDF($page,'inline');exit;
	} else {
		return $ftx->fetch('CONTENT');
	}
	//	<---	modified for pdf