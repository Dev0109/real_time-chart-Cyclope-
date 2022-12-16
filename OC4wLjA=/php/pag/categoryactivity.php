<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => $glob['pag'].".html"));
$ft->define_dynamic('template_row','main');
$dbu = new mysql_db();
	$trial = $dbu->field("SELECT value FROM `settings` WHERE `constant_name` = 'TRUENC'");
	$ecrypted_text = $dbu->field("SELECT message FROM `notification` WHERE `constant_name` = 'ENCRYPTED_TEXT'");
	if ($trial != 2236985){
		$ft->assign('ENCRYPTMESSAGERAW', '<div class="encryptmessage">' . $ft->lookup($ecrypted_text) . '</div>');
	}
$filters = get_filters($glob['t'],$glob['f'],$glob['time']);
extract($filters,EXTR_OVERWRITE);

$sets = get_categories($glob['f'],'all');
//pas 1: se selecteaza toate categoriile
$categories = $dbu->query("SELECT * FROM application_category ORDER BY lft ASC");
$assigns = array();
$category_durations = array();
$category_totals = array();
$i=0;
$total=0;
$rights = array();
while ($categories->next()){
	while (!empty($rights) && (end($rights) < $categories->f('rgt'))){
		array_pop($rights);
	}
	//GRAPH?	
	$assigns[$categories->f('application_category_id')] = array(
		'SPACER'      => str_repeat('&nbsp;&nbsp;&nbsp;',count($rights)),
		'CATEGORY'    => $categories->f('category')=='Uncategorised'? $ft->lookup('Uncategorised'):$ft->lookup($categories->f('category')),
		'TIME'        => 0,
	);
	$rights[] = $categories->f('rgt'); 
	$i++;
}
$export_header = get_export_header($glob['f']);
extract($export_header,EXTR_OVERWRITE);
$glob['append'] = trialEncrypt($member_name);

//pas 2: se scot applicatiile
$app_cats = array();
$total = 0;
$applications = $dbu->query("SELECT SUM(session_application.duration) AS duration,
					application.application_id
			 FROM session_application 
 			 INNER JOIN session ON session.session_id = session_application.session_id
 			 INNER JOIN application ON application.application_id = session_application.application_id
			 ".$app_join."
			 WHERE session_application.duration > 0 
			 AND session_application.time_type = 0 ".$app_filter." 
			 GROUP BY application.application_id");
while ($applications->next()){
	$cat_id = 1;
	if(isset($sets[$applications->f('application_id').'-0'])){
		$cat_id = $sets[$applications->f('application_id').'-0']['category_id'];
	}
	else if(isset($sets[$applications->f('application_id').'-1'])){
			$cat_id = $sets[$applications->f('application_id').'-1']['category_id'];
		}
		else if(isset($sets[$applications->f('application_id').'-2'])){
				$cat_id = $sets[$applications->f('application_id').'-2']['category_id'];
			}
			else if(isset($sets[$applications->f('application_id').'-3'])){
					$cat_id = $sets[$applications->f('application_id').'-3']['category_id'];
				}
	
	if(!is_array($category_durations[$cat_id])){
		$category_durations[$cat_id] = array();
	}
	
	
	$category_durations[$cat_id][$applications->f('application_id')] = $applications->f('duration');
	$app_cats[$applications->f('application_id')] = $cat_id;
	$total += $applications->f('duration');
	$category_totals[$cat_id] += $applications->f('duration');
}

//pas 3: se scot copii
//pas 3.1 se scot site-urile
$children = $dbu->query("SELECT SUM(session_website.duration) AS duration,
			 session_website.domain_id,
			 session_website.application_id
			 FROM session_website 
 			 INNER JOIN session ON session.session_id = session_website.session_id
			 ".$app_join."
			 WHERE session_website.duration > 0 
			 AND session_website.time_type = 0 ".$app_filter." 
			 GROUP BY session_website.domain_id");

while ($children->next()){
	$cat_id = 1;
	if(isset($sets[$children->f('domain_id').'-3'])){
		$cat_id = $sets[$children->f('domain_id').'-3']['category_id'];
	}
	
	
	if(!is_array($category_durations[$cat_id])){
		$category_durations[$cat_id] = array();
	}
	if(isset($category_durations[$cat_id][$children->f('application_id')])){
		continue;
	}
	
	$category_id = $app_cats[$children->f('application_id')];
	$category_durations[$category_id][$children->f('application_id')] -= $children->f('duration');
	// $category_totals[$category_id] -= $children->f('duration');
	
	$category_durations[$cat_id][$children->f('application_id')] += $children->f('duration');		
	$category_totals[$cat_id] += $children->f('duration');
}
/*
//pas 3.2 se scot chat-urile
$children = $dbu->query("SELECT SUM(session_chat.duration) AS duration,
								chat.chat_id,
								chat.application_id
						 FROM session_chat
			 			 INNER JOIN session ON session.session_id = session_chat.session_id
			 			 INNER JOIN chat ON chat.chat_id = session_chat.chat_id
						 ".$app_join."
						 WHERE session_chat.duration > 0 
						 AND session_chat.time_type = 0 ".$app_filter." 
						 GROUP BY chat.chat_id
			           ");
while ($children->next()){
	$cat_id = 1;
	if(isset($sets[$children->f('chat_id').'-1'])){
		$cat_id = $sets[$children->f('chat_id').'-1']['category_id'];
	}
	
	if(!is_array($category_durations[$cat_id])){
		$category_durations[$cat_id] = array();
	}
	if(isset($category_durations[$cat_id][$children->f('application_id')])){
		continue;
	}
	
	$category_id = $app_cats[$children->f('application_id')];
	$category_durations[$category_id][$children->f('application_id')] -= $children->f('duration');
	// $category_totals[$category_id] -= $children->f('duration');
	
	$category_durations[$cat_id][$children->f('application_id')] += $children->f('duration');		
	$category_totals[$cat_id] += $children->f('duration');
}

//pas 3.3 se scot documentele
$children = $dbu->query("SELECT SUM(session_document.duration) AS duration,
								document.document_id,
								document.application_id
						 FROM session_document
			 			 INNER JOIN session ON session.session_id = session_document.session_id
			 			 INNER JOIN document ON document.document_id = session_document.document_id
						 ".$app_join."
						 WHERE session_document.duration > 0 
						 AND session_document.time_type = 0 ".$app_filter." 
						 GROUP BY document.document_id
			           ");

while ($children->next()){
	$cat_id = 1;
	if(isset($sets[$children->f('document_id').'-2'])){
		$cat_id = $sets[$children->f('document_id').'-2']['category_id'];
	}

	if(!is_array($category_durations[$cat_id])){
		$category_durations[$cat_id] = array();
	}
	if(isset($category_durations[$cat_id][$children->f('application_id')])){
		continue;
	}
	
	$category_id = $app_cats[$children->f('application_id')];
	$category_durations[$category_id][$children->f('application_id')] -= $children->f('duration');
	// $category_totals[$category_id] -= $children->f('duration');
	
	$category_durations[$cat_id][$children->f('application_id')] += $children->f('duration');		
	$category_totals[$cat_id] += $children->f('duration');
}
*/

$v = $category_totals[1];
unset($category_totals[1]);
arsort($category_totals);
$category_totals['1'] = $v;
$position = array_flip(array_keys($category_totals));
$i=0;

//	assign help link
$ft->assign(array(
	'HELP_LINK' => 'help.php?pag='.$glob['pag'],
));

$categorychart = new stdClass;
$categorychart = array("settings" => array("container" => array("selector" => "categorychart", "height" => "300px", "width" => "710px")), "theme" => "theme1");
// $categorychart->settings->container->selector = "categorychart";
// $categorychart->settings->container->height = "300px";
// $categorychart->settings->container->width = "710px";

// $categorychart->theme = "theme1";
$categorychart['animationEnabled'] = pdf_animate();
$categorychart['interactivityEnabled'] = true;
$categorychart['barwidth'] = 30;
$categorychart['axisX']['labelWrap'] = true;
$categorychart['axisX']['labelMaxWidth'] = 100; 
$categorychart['axisX']['labelFontSize'] = 11;
$categorychart['axisX']['interval'] = 1;
$categorychart['axisX']['labelAngle'] = 270;
$categorychart['axisY']['minimum'] = 0;
$categorychart['axisY']['valueFormatString'] = "#";
$categorychart['legend']['verticalAlign'] = "bottom";
$categorychart['legend']['horizontalAlign'] = "center";

$categorychart['data[0]']['type'] = "column";
$categorychart['data[0]']['toolTipContent'] = "{label} - {y}%";

if(!$total)
{
	$ft->assign(array(
		'NO_DATA_MESSAGE' => get_error($ft->lookup('No data to display for your current filters'),'warning'),
		'HIDE_CONTENT'	=> 'hide',
		'HELP_LINK' => 'help.php?pag='.$glob['pag'],
	));
}
else 
{
	foreach ($category_totals as $category_id => $cat_total){

        if($i > 15){
        	break;
        }
		if (count($category_totals) > 15 AND $i == 15){
			$cat_total = $category_totals[1];
			$tags = $assigns[1];
		} else {
			$tags = $assigns[$category_id];
		}
	    $time = $cat_total; 
		
		if($time < 0){
			continue;
		}	
		if($time)
			$proc = $time * 100 / $total;
		else
			$proc = 0;
		if ($i < 15 && $tags['CATEGORY'] != $ft->lookup('Uncategorised')){
			$categorychart['data[0]']['dataPoints[$i]']['y']= (float)number_format($proc,2);
			$categorychart['data[0]']['dataPoints[$i]']['label'] = $tags['CATEGORY'];
			$categorychart['data[0]']['dataPoints[$i]']['color'] = "#" . $_SESSION['colors'][$position[$category_id]];
		}
		$tags['COLOR']    = $_SESSION['colors'][$position[$category_id]];	
		$tags['WIDTH']    = ($cat_total * 100) / $total;
		$tags['PROCENT']  = ($proc > 1) ? number_format($proc,2,',','.') : ' < 1';
		$tags['TIME']     = format_time($time);
		$ft->assign($tags);
		$ft->parse('TEMPLATE_ROW_OUT','.template_row');	
		$i++;
	}
}

$ft->assign('CATEGORY_CHART',drawGraph($categorychart));



$dates = $dbu->row("SELECT MIN(date) AS genesis,MAX(date) AS last_day_on_earth FROM session ");

$ft->assign(array(
	'DEFAULT_VALUE'  => isset($glob['time']) ? $glob['time']['time']: date('n/j/Y',$dates['genesis']).' - '.date('n/j/Y',$dates['last_day_on_earth']) ,
	'DATE_BEFORE'    => date('n/j/Y',$dates['genesis']),
	'TIME_'.($glob['time']['type'] ? $glob['time']['type'] : 1) => 'selected="selected"'
));
global $bottom_includes;
$bottom_includes.='
<script type="text/javascript">flobn.register("genesis",new Date("'.date('n/j/Y',$dates['genesis']).'"));</script><script type="text/javascript" src="ui/categoryactivity-ui.js"></script>';

$ft->assign('PAGE_TITLE',$ft->lookup('Activity Categories for'));
$ft->assign(
	'APPEND', $glob['append']);
	
	//	modified for pdf	--->
	$export_header = get_export_header($_SESSION['filters']['f']);
	extract($export_header,EXTR_OVERWRITE);
	$ft->assign(array(
		'PDF_HEADER' => pdf_header(),
		'PDF_HIDE' => pdf_hide(),
		'PDF_CLASS' => pdf_class(),
		'TITLE' => $ft->lookup('Activity Categories'),
		'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
		'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
	));
	//	<---	modified for pdf
	
if(!$glob['is_ajax']){
	$ft->define_dynamic('ajax','main');
	$ft->parse('AJAX_OUT','ajax');
}

$site_meta_title=$meta_title;
$site_meta_keywords=$meta_keywords;
$site_meta_description=$meta_description;

$ft->parse('CONTENT','main');
	//	modified for pdf	--->
	if($_REQUEST['render'] == 'pdf'){
		$page = 'categories';
		$html = $ft->fetch('CONTENT');
			file_put_contents(CURRENT_VERSION_FOLDER.'temp_pdf/'.$page.'.html',"\xEF\xBB\xBF" . $html);
		loadPDF($page,'inline');exit;
	} else {
		return $ft->fetch('CONTENT');
	}
	//	<---	modified for pdf