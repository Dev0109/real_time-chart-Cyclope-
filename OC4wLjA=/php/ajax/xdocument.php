<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/

$ftx=new ft(ADMIN_PATH.MODULE."templates/");
$ftx->define(array('main' => "xdocument.html"));
$ftx->define_dynamic('template_row','main');
$ajax_loaded = false;

$l_r = isset($glob['rp']) && is_numeric($glob['rp']) ? $glob['rp'] : 25;

$dbu = new mysql_db();
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
WHERE application.application_type = 2");
$apps_productivity = array();
while ($apps->next()){
	$apps_productivity[$apps->f('application_id')] = $apps->f('productive');
}

$filters = get_filters($glob['t'],$glob['f'],$glob['time']);
extract($filters,EXTR_OVERWRITE);

$categories = get_categories($glob['f'],2);

$nodes = explode('-',$glob['f']);
$department_id = reset($nodes);
unset($nodes);



if($glob['app'] && $glob['app'] != -1 /*&& in_array($glob['app'],array_keys($ddr))*/){
	$total_filter .= ' AND application_id ='.$glob['app'];
	$app_filter .= ' AND session_document.application_id = '.$glob['app'];
	
	$ftx->assign('EXPORT_APP_FILTER',$glob['app']);
}

//calculate the total
$total = $dbu->field("SELECT SUM(session_document.duration) FROM session_document 
					INNER JOIN session ON session.session_id = session_document.session_id
					".$app_join."
WHERE 1=1 AND session_document.time_type = 0 ".$app_filter);

//	lorand
$sortable_columns = array(
	'duration',
	'document.name',
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

$total = $total ? $total : 1;
$dbu->query("SELECT SUM(session_document.duration) as duration,document.name,document.document_id,
COALESCE(application_productivity.productive,1) AS productive,
application.description,
document.application_id FROM session_document
INNER JOIN document ON document.document_id = session_document.document_id
INNER JOIN application ON document.application_id = application.application_id
INNER JOIN session ON session.session_id = session_document.session_id
".$app_join."
LEFT JOIN application_productivity ON application_productivity.department_id = member.department_id 
AND application_productivity.link_id = document.document_id 
AND application_productivity.link_type = 2
WHERE 1=1 AND session_document.time_type = 0 ".$app_filter."
AND session_document.duration > 0
GROUP BY document.document_id
HAVING duration > 0
" . $sortcolumns . " ".$number_of_rows);


$max_rows=$dbu->records_count();
$dbu->move_to($offset * $l_r);
$i=0;
while($dbu->move_next() && $i < $l_r){
	$cat_name = $ftx->lookup('Uncategorised');
	$cat_id = 1;
	if(isset($categories[$dbu->f('document_id').'-2'])){
		$cat_name = $categories[$dbu->f('document_id').'-2']['category'];
		$cat_id = $categories[$dbu->f('document_id').'-2']['category_id'];
	}
	
	$ftx->assign(array(
		'ICON_SRC' => pdf_media_location('img/icons/' . get_icon($dbu->f('description'),'document').'.png',CURRENT_VERSION_FOLDER),	//	modified for pdf
		'PDF_HIDE' => pdf_hide(),	//	modified for pdf
		'NAME' => $dbu->f('name'),
		'TIME' => format_time($dbu->f('duration')),
		'WIDTH' => ((($dbu->f('duration') * 100) / $total) > 1) ? number_format((($dbu->f('duration') * 100) / $total),2,',','.') : ' < 1',
		'ID' => $dbu->f('document_id'),
		'DOCUMENT_ID' => $dbu->f('document_id'),
		'CATEGORY' => $ftx->lookup($cat_name),
		'CATEGORY_ID' => $cat_id,
		'DURATION' => $dbu->f('duration'),
		'CHILD_TYPE' => 2,//document
		'APPLICATION_ID' => $dbu->f('application_id'),
		'DEPARTMENT' => $department_id
	));
	//do we need the app setting?
	// $productive = $dbu->f('productive');
	// if($apps_productivity[$dbu->f('application_id')] != 3){
		// $productive = $apps_productivity[$dbu->f('application_id')];
	// }
	$dbu_prod = new mysql_db();
	$productive = $dbu_prod->field("SELECT `productive`
						FROM `application_productivity`
						WHERE `department_id` = " . filter_var($department_id, FILTER_SANITIZE_NUMBER_INT) . "
						AND `link_id` = " . $dbu->f('document_id') . "
						AND `link_type` = 2
						LIMIT 1 ");
	if ($productive === false){
		$productive = 1;
	}
	
	switch ($productive){
		case 0://distracting
			$ftx->assign(array(
				'TYPE'=> $ftx->lookup('Distracting'),
				'CSS_CLASS' => 'distracting'
			));
			break;
		case 2:
			$ftx->assign(array(
				'TYPE'=> $ftx->lookup('Productive'),
				'CSS_CLASS' => 'productive'
			));
			break;
		default:
			$ftx->assign(array(
				'TYPE'=> $ftx->lookup('Neutral'),
				'CSS_CLASS' => 'neutral'
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
$end = ceil($max_rows/$l_r);

$ftx->assign(array(
	'PAGES' => bulid_simple_dropdown(array( 25 => 25 , 50 => 50, 100 => 100, 200 => 200,500 => 500),$l_r),
	'INTERVAL' => $offset == 0 ? 1 : $offset +1,
	'TOTAL' => $end,
		'HELPIMG' => CURRENT_VERSION_FOLDER . "/img/prodhelp.gif",
	'FILTER_PAGE' => "index_ajax.php?pag=xdocument"
));
$arguments = '&rp='.$l_r;
if($offset > 0){
     $ftx->assign('BACKLINK',"index_ajax.php?pag=xdocument&offset=".($offset-1).$arguments);
}else{
     $ftx->assign('BACKLINK',"index_ajax.php?pag=xdocument&offset=".($offset).$arguments);
}
if($offset < $end-1){
     $ftx->assign('NEXTLINK',"index_ajax.php?pag=xdocument&offset=".($offset+1).$arguments);
}else{
     $ftx->assign('NEXTLINK',"index_ajax.php?pag=xdocument&offset=".($offset).$arguments);
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
	include(CURRENT_VERSION_FOLDER.'php/chart/chart_documentproductivity.php');
	$ftx->assign(array(
			'PDF_HEADER' => pdf_header(),
			'PDF_HIDE' => pdf_hide(),
			'PDF_CLASS' => pdf_class(),
			'TITLE' => 'Documents',
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
			'AJAX_DOCCHART' => drawGraph($prodstack),
	));
	//	<---	modified for pdf

$ftx->parse('CONTENT','main');
if($_REQUEST['render'] == 'pdf'){
	$page = 'document';
	$html = $ftx->fetch('CONTENT');
		file_put_contents(CURRENT_VERSION_FOLDER.'temp_pdf/'.$page.'.html',"\xEF\xBB\xBF" . $html);
	loadPDF($page,'inline');exit;
} else {
	return $ftx->fetch('CONTENT');
}
