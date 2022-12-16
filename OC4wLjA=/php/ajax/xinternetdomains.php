<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftx=new ft(ADMIN_PATH.MODULE."templates/");
$ftx->define(array('main' => "xinternetdomains.html"));
$ftx->define_dynamic('template_row','main');
$l_r = isset($glob['rp']) && is_numeric($glob['rp']) ? $glob['rp'] : 25;
$ajax_loaded = false;
if(($glob['ofs']) || (is_numeric($glob['ofs'])))
{
	$glob['offset']=$glob['ofs'];
}
if((!$glob['offset']) || (!is_numeric($glob['offset'])))
{
        $offset=0;
}
else
{
        $offset=$glob['offset'];
        $ftx->assign('OFFSET',$glob['offset']);
}

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

$dbu = new mysql_db();

$filters = get_filters($glob['t'],$glob['f'],$glob['time']);
extract($filters,EXTR_OVERWRITE);

$categories = get_categories($glob['f'],3);

$nodes = explode('-',$glob['f']);
$department_id = reset($nodes);
unset($nodes);


$session_website = get_session_website_table();
if($glob['app'] && $glob['app'] != -1 /*&& in_array($glob['app'],array_keys($ddr))*/){
	$total_filter .= ' AND application_id ='.$glob['app'];
	$app_filter .= " AND session_" . $session_website . ".application_id = ".$glob['app'];
	$ftx->assign('EXPORT_APP_FILTER','&app='.$glob['app']);
	$auxiliary_app_filter = ' AND application_id = '.$glob['app'];
}
//build application
$apps = $dbu->query("SELECT application.application_id,application.description, COALESCE(application_productivity.productive,1) AS productive,application_productivity.application_productivity_id,application_productivity.department_id FROM application 
LEFT JOIN application_productivity ON application_productivity.link_id = application.application_id
AND application_productivity.link_type = 0
WHERE application.application_type = 3" 
.$auxiliary_app_filter); 

$ddr = array();
$apps_productivity = array();
$apps_productivity_id = array();
while ($apps->next()){
	$ddr[$apps->f('application_id')] = $apps->f('description');
	$apps_productivity[$apps->f('application_id')] = $apps->f('productive');
	if(!array_key_exists($apps->f('application_id'),$apps_productivity_id))
		$apps_productivity_id[$apps->f('application_id')]=array();
	array_push ($apps_productivity_id[$apps->f('application_id')],$apps->f('application_productivity_id')?$apps->f('application_productivity_id'):0);
}
//calculate the total
$total = $dbu->field("SELECT SUM(session_" . $session_website . ".duration) FROM session_" . $session_website . "
INNER JOIN session ON session.session_id = session_" . $session_website . ".session_id
".$app_join." WHERE 1=1 ".$app_filter);

//	lorand
$sortable_columns = array(
	'duration',
	'domain.domain',
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

$domains=$dbu->query("SELECT SUM(session_" . $session_website . ".duration) AS duration,
domain.domain,
session_" . $session_website . ".domain_id,
session_" . $session_website . ".application_id,
COALESCE(application_productivity.productive,1) AS productive,
member.department_id AS department_id
FROM session_" . $session_website . "
INNER JOIN domain ON domain.domain_id = session_" . $session_website . ".domain_id
INNER JOIN session ON session.session_id = session_" . $session_website . ".session_id
".$app_join."
LEFT JOIN application_productivity ON application_productivity.department_id = 1
AND application_productivity.link_id = domain.domain_id
AND application_productivity.link_type = 3
WHERE 1=1
".$app_filter."
GROUP BY session_" . $session_website . ".domain_id
HAVING duration > 0
" . $sortcolumns . " ".$number_of_rows);







$max_rows=$domains->records_count();
$domains->move_to($offset * $l_r);
$i=0;
while($domains->next() && $i < $l_r){
	if($dbu->f('duration') == 0)
	{
		continue;
	}
	
	$cat_name = $ftx->lookup('Uncategorised');
	$cat_id = 1;
	if(isset($categories[$domains->f('domain_id').'-3'])){
		$cat_name = $ftx->lookup($categories[$domains->f('domain_id').'-3']['category']);
		$cat_id = $categories[$domains->f('domain_id').'-3']['category_id'];
	}
	
	
	
	$ftx->assign(array(
		'ID' => $domains->f('domain_id'),
		'URL' => $domains->f('domain'),
		'URL_DOMAIN' =>$_REQUEST['render'] == 'pdf' ? '' : '<img src="http://www.google.com/s2/favicons?domain=' . $domains->f('domain') . '" width="16" height="16" hspace="5"  />',
	    'LINK_URL_DOMAIN' =>"http://".$domains->f('domain'), 
		'TIME' => format_time(intval($domains->f('duration')/count(explode(',',$domains->f('parrentaps'))))),
		'WIDTH' => ((($domains->f('duration') * 100) / $total) > 1) ? number_format((($domains->f('duration') * 100) / $total),2,',','.') : ' < 1',
		'CATEGORY' => $cat_name,
		'CATEGORY_ID' => $cat_id,
		'DURATION' => $domains->f('duration'),
		'CHILD_TYPE' => 3,
		'DEPARTMENT' => $department_id,
		'FIRST_APPLICATION_ID'=> $domains->f('application_id'),
		'PDF_HIDE' => pdf_hide(),
		
	));
	
	//do we need the app setting?
	// $productive = $domains->f('productive');
	$dbu_prod = new mysql_db();
	$productive = $dbu_prod->field("SELECT `productive`
						FROM `application_productivity`
						WHERE `department_id` = 1
						AND `link_id` = " . $domains->f('domain_id') . "
						AND `link_type` = 3
						LIMIT 1 ");
	if ($productive === false){
		$productive = 1;
	}
	
	switch ($productive){
		case 0://distracting
			$ftx->assign(array(
				'TYPE'=> $ftx->lookup('Distracting'),
				'CSS_CLASS' => 'distracting',
			));
			break;
		case 2:
			$ftx->assign(array(
				'TYPE'=> $ftx->lookup('Productive'),
				'CSS_CLASS' => 'productive',
			));
			break;
		default:
			$ftx->assign(array(
				'TYPE'=> $ftx->lookup('Neutral'),
				'CSS_CLASS' => 'neutral',
			));
	}
	
	
	if($i % 2 != 0 ){
		$ftx->assign('EVEN','even');
	}else{
		$ftx->assign('EVEN','');
	}
	$ftx->parse('TEMPLATE_ROW_OUT','.template_row');
	$i++;
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
$end = ceil($max_rows/$l_r);

$ftx->assign(array(
	'PAGES' => bulid_simple_dropdown(array( 25 => 25 , 50 => 50, 100 => 100, 200 => 200,500 => 500),$l_r),
	'INTERVAL' => $offset == 0 ? 1 : $offset +1,
	'TOTAL' => $end,
		'HELPIMG' => CURRENT_VERSION_FOLDER . "/img/prodhelp.gif",
	'FILTER_PAGE' => "index.php?pag=".$glob['pag']
));
$arguments = '&rp='.$l_r;
if($offset > 0){
     $ftx->assign('BACKLINK',"index.php?pag=".$glob['pag']."&offset=".($offset-1).$arguments);
}else{
     $ftx->assign('BACKLINK',"index.php?pag=".$glob['pag']."&offset=".($offset).$arguments);
}
if($offset < $end-1){
     $ftx->assign('NEXTLINK',"index.php?pag=".$glob['pag']."&offset=".($offset+1).$arguments);
}else{
     $ftx->assign('NEXTLINK',"index.php?pag=".$glob['pag']."&offset=".($offset).$arguments);
}

if(count(explode('-',$glob['f'])) == 1){
	$bottom_includes.= '<script type="text/javascript">flobn.register("thouShallNotMove",0);</script>';	
	$glob['thouShallNotMove'] = 0;
}else{
	$bottom_includes.= '<script type="text/javascript">flobn.register("thouShallNotMove",1);</script>';	
	$glob['thouShallNotMove'] = 1;
}
include(CURRENT_VERSION_FOLDER.'php/chart/chart_internetbyproductivity.php');
include(CURRENT_VERSION_FOLDER.'php/chart/chart_internetbycategory.php');

	
	//	modified for pdf	--->
	$export_header = get_export_header($_SESSION['filters']['f']);
	extract($export_header,EXTR_OVERWRITE);
	$ftx->assign(array(
		'PDF_HEADER' => pdf_header(),
		'PDF_HIDE' => pdf_hide(),
		'PDF_CLASS' => pdf_class(),
		'TITLE' => $ftx->lookup('Internet Domains'),
		'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
		'AJAX_INTERNETPRODUCTIVITY' => drawGraph($productivitychart),
		'AJAX_INTERNETCATEGORY' => drawGraph($internetcategorychart),
		'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
	));
	//	<---	modified for pdf

$glob['selector'] = 'domains';
$site_meta_title=$meta_title;
$site_meta_keywords=$meta_keywords;
$site_meta_description=$meta_description;

$export_header = get_export_header($glob['f']);
extract($export_header,EXTR_OVERWRITE);
$glob['append'] = trialEncrypt($member_name);

$ftx->parse('CONTENT','main');
	//	modified for pdf	--->
	if($_REQUEST['render'] == 'pdf'){
		$page = 'domains';
		$html = $ftx->fetch('CONTENT');
			file_put_contents(CURRENT_VERSION_FOLDER.'temp_pdf/'.$page.'.html',"\xEF\xBB\xBF" . $html);
		loadPDF($page,'inline');exit;
	} else {
		if(!$glob['is_ajax']){
			echo $ftx->fetch('CONTENT');
			exit();
		}
	}

$ftx->parse('CONTENT','main');
return $ftx->fetch('CONTENT');
	//	<---	modified for pdf