<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftx=new ft(ADMIN_PATH.MODULE."templates/");
$ftx->define(array('main' => "xinterneturls.html"));
$ftx->define_dynamic('template_row','main');

$l_r = isset($glob['rp']) && is_numeric($glob['rp']) ? $glob['rp'] : 25;

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
//build application
$apps = $dbu->query("SELECT application.application_id,application.description, COALESCE(application_productivity.productive,1) AS productive FROM application 
LEFT JOIN application_productivity ON application_productivity.link_id = application.application_id
AND application_productivity.link_type = 0
WHERE application.application_type = 3");
$ddr = array();
$apps_productivity = array();
while ($apps->next()){
	$ddr[$apps->f('application_id')] = $apps->f('description');
	$apps_productivity[$apps->f('application_id')] = $apps->f('productive');
}

global $apps_productivity;

$dbu = new mysql_db();

$nodes = explode('-',$glob['f']);
$department_id = reset($nodes);
unset($nodes);

$filters = get_filters($glob['t'],$glob['f'],$glob['time']);
extract($filters,EXTR_OVERWRITE);

$categories = get_categories($glob['f'],3);

if($glob['app'] && $glob['app'] != -1 && in_array($glob['app'],array_keys($ddr))){
	$total_filter .= ' AND application_id ='.$glob['app'];
	$app_filter .= ' AND session_website.application_id = '.$glob['app'];
	
	$ftx->assign('EXPORT_APP_FILTER','&app='.$glob['app']);
}
//get the domains so we can display producitivity:
$domain_productivity = array();
$domains = $dbu->query("SELECT * FROM domain
INNER JOIN application_productivity ON application_productivity.link_id = domain.domain_id
AND application_productivity.link_type = 3");
while ($domains->next()){
	$domain_productivity[$domains->f('domain_id')] = $domains->f('productive');
}


//calculate the total
$total = $dbu->field("SELECT SUM(session_website.duration) FROM session_website
INNER JOIN session ON session.session_id = session_website.session_id
".$app_join." WHERE 1=1 AND session_website.time_type = 0 ".$app_filter);

//	lorand
$sortable_columns = array(
	'duration',
	'website.url',
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

$dbu->query("SELECT SUM(session_website.duration) as duration,
website.url,
session_website.domain_id,
website.website_id,
website.application_id
FROM session_website
INNER JOIN website ON website.website_id = session_website.website_id
INNER JOIN session ON session.session_id = session_website.session_id
".$app_join."
WHERE 7=7 AND session_website.time_type = 0 ".$app_filter."
AND session_website.application_id = website.application_id
GROUP BY session_website.website_id
HAVING duration > 0
" . $sortcolumns . " ".$number_of_rows);
$max_rows=$dbu->records_count();
$dbu->move_to($offset * $l_r);
$i=0;
while($dbu->move_next()  && $i < $l_r){
	
	if($dbu->f('duration') == 0)
	{
		continue;
	}
	
	$cat_name = $ftx->lookup('Uncategorised');
	$cat_id = 1;
	if(isset($categories[$dbu->f('website_id').'-3'])){
		$cat_name = $categories[$dbu->f('website_id').'-3']['category'];
		$cat_id = $categories[$dbu->f('website_id').'-3']['category_id'];
	}
	
	
	
	$ftx->assign(array(
		'ID' => $dbu->f('website_id'),
		'URL' => $dbu->f('url'),
		'URL_SHORT' => trim_text($dbu->f('url'), $length=70),
		'URL_DOMAIN' =>getDomain($dbu->f('url')),
		'TIME' => format_time($dbu->f('duration')),
		'WIDTH' => ((($dbu->f('duration') * 100) / $total) > 1) ? number_format((($dbu->f('duration') * 100) / $total),2,',','.') : ' < 1',
		'CATEGORY' => $cat_name,
		'CATEGORY_ID' => $cat_id,
		'DURATION' => $dbu->f('duration'),
		'CHILD_TYPE' => 3,
		'APPLICATION_ID' => $dbu->f('application_id'),
		'DEPARTMENT' => $department_id
		
	));
	
	//do we need the app setting?
	// $productive = isset($domain_productivity[$dbu->f('domain_id')]) ? $domain_productivity[$dbu->f('domain_id')] : 1;
	$dbu_prod = new mysql_db();
	$productive = $dbu_prod->field("SELECT `productive`
						FROM `application_productivity`
						WHERE `department_id` = 1
						AND `link_id` = " . $dbu->f('domain_id') . "
						AND `link_type` = 3
						LIMIT 1 ");
	if ($productive === false){
		$productive = 1;
	}

	if($apps_productivity[$dbu->f('application_id')] != 3 && !isset($domain_productivity[$dbu->f('domain_id')])){
		$productive = $apps_productivity[$dbu->f('application_id')];
	}
	switch ($productive){
		case 0://distracting
			$ftx->assign(array(
				'TYPE'=> $ftx->lookup('Distracting'),
				'CSS_CLASS' => 'distracting',
				'TYPE_INPUT' => '<span class="flobn-slider distracting"' . pdf_hide() . '></span>'
			));
			break;
		case 2:
			$ftx->assign(array(
				'TYPE'=> $ftx->lookup('Productive'),
				'CSS_CLASS' => 'productive',
				'TYPE_INPUT' => '<span class="flobn-slider productive"' . pdf_hide() . '></span>'
			));
			break;
		default:
			$ftx->assign(array(
				'TYPE'=> $ftx->lookup('Neutral'),
				'CSS_CLASS' => 'neutral',
				'TYPE_INPUT' => '<span class="flobn-slider neutral"' . pdf_hide() . '></span>'
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


	
	
	
	//	modified for pdf	--->
	$export_header = get_export_header($_SESSION['filters']['f']);
	extract($export_header,EXTR_OVERWRITE);
	$ftx->assign(array(
		'PDF_HEADER' => pdf_header(),
		'PDF_HIDE' => pdf_hide(),
		'PDF_CLASS' => pdf_class(),
		'TITLE' => $ftx->lookup('Internet URLs'),
		'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
		'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
	));
	//	<---	modified for pdf

$glob['selector'] = 'urls';
$site_meta_title=$meta_title;
$site_meta_keywords=$meta_keywords;
$site_meta_description=$meta_description;

$export_header = get_export_header($glob['f']);
extract($export_header,EXTR_OVERWRITE);
$glob['append'] = trialEncrypt($member_name);

$ftx->parse('CONTENT','main');
	//	modified for pdf	--->
	if($_REQUEST['render'] == 'pdf'){
		$page = 'urls';
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