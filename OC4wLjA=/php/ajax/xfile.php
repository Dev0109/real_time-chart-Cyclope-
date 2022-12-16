<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftx=new ft(ADMIN_PATH.MODULE."templates/");
$ftx->define(array('main' => "xfile.html"));
$ftx->define_dynamic('template_row','main');

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

$filters = get_filters($glob['t'],$glob['f'],$glob['time'],true);
extract($filters,EXTR_OVERWRITE);

$filter = "";

if(is_numeric($glob['app']) && ( $glob['app'] != -1 ) )
{
	$filter = ' AND fixed = '.$glob['app'];
	$ftx->assign('EXPORT_APP_FILTER','&app='.$glob['app']);
}

//	lorand
$sortable_columns = array(
	'session_file.eventtime',
	'logon',
	'session_file.action',
	);

$sortcolumns = get_sorting($sortable_columns,'','desc');

$ftx->assign(array(
	'ANCHOR_INNER_0' => render_anchor_inner(0),
	'ANCHOR_INNER_1' => render_anchor_inner(1),
	'ANCHOR_INNER_2' => render_anchor_inner(2),
	'DEBUGMESSAGE' => '',
));
//END

	
	
	$pieces = explode('-',$glob['f']);
	$filterCount = count($pieces);
	
	if (strpos($glob['f'],'c') !== false || (strpos($glob['f'],'u') !== false && $filterCount == 3)){
		$onlycompfilter = '';
	}else {
		$onlycompfilter = 'INNER JOIN computer ON computer.computer_id = session.computer_id';
	}

$dbu->query("SELECT session_file.eventtime, computer.name as computername, session_file.action,file.*,member.logon, member.active, CONCAT(member.first_name,' ',member.last_name) AS membername, member.alias FROM session_file
INNER JOIN file ON file.file_id = session_file.file_id
INNER JOIN session ON session.session_id = session_file.session_id
".$onlycompfilter."
".$app_join."
WHERE 1=1 AND session_file.time_type = 0 " . $app_filter.$filter . $files_filter ."
GROUP by file.file_id,action
" . $sortcolumns . " ".$number_of_rows);
$max_rows=$dbu->records_count();
$dbu->move_to($offset * $l_r);
$i=0;
$drive_type = array($ftx->lookup("Removable"), $ftx->lookup("Fixed"), $ftx->lookup("Remote"), $ftx->lookup("CDRom"), $ftx->lookup("RAMDisk"), $ftx->lookup("Unknown"));
 // 0 REMOVABLE
 // 1 FIXED
 // 2 REMOTE
 // 3 CDROM
 // 4 RAMDISK
 // 5 UNKNOWN
while($dbu->move_next() && $i < $l_r){
	if (strlen($dbu->f('path')) > 2) {
		$ftx->assign(array(
			'NAME' => $dbu->f('alias') == 1 ? trialEncrypt($dbu->f('membername')) : trialEncrypt($dbu->f('logon')),
			'COMPUTER' => trialEncrypt($dbu->f('computername')),
			'DATE' => date('d/m/y H:i',$dbu->f('eventtime')),
			'ACTION' => $dbu->f('action'),
			'TYPE' => $drive_type[$dbu->f('fixed')],
			'PATH' => html_entity_decode(urldecode($dbu->f('drive').$dbu->f('path')),ENT_QUOTES)
		));
		
		if($i % 2 != 0 ){
			$ftx->assign('EVEN','even');
		}else{
			$ftx->assign('EVEN','');
		}
		$ftx->parse('TEMPLATE_ROW_OUT','.template_row');
		$i++;
	}
}

$end = ceil($max_rows/$l_r);

$ftx->assign(array(
	'PAGES' => bulid_simple_dropdown(array( 25 => 25 , 50 => 50, 100 => 100, 200 => 200,500 => 500),$l_r),
	'INTERVAL' => $offset == 0 ? 1 : $offset +1,
	'TOTAL' => $end,
	'FILTER_PAGE' => "index_ajax.php?pag=xfile"
));
$arguments = '&rp='.$l_r;
if($offset > 0){
     $ftx->assign('BACKLINK',"index_ajax.php?pag=xfile&offset=".($offset-1).$arguments);
}else{
     $ftx->assign('BACKLINK',"index_ajax.php?pag=xfile&offset=".($offset).$arguments);
}
if($offset < $end-1){
     $ftx->assign('NEXTLINK',"index_ajax.php?pag=xfile&offset=".($offset+1).$arguments);
}else{
     $ftx->assign('NEXTLINK',"index_ajax.php?pag=xfile&offset=".($offset).$arguments);
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
	
	//	modified for pdf	--->
	$export_header = get_export_header($_SESSION['filters']['f']);
	extract($export_header,EXTR_OVERWRITE);
	$ftx->assign(array(
		'PDF_HEADER' => pdf_header(),
		'PDF_HIDE' => pdf_hide(),
		'PDF_CLASS' => pdf_class(),
		'TITLE' => $ftx->lookup('Files'),
		'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
		'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
	));
	//	<---	modified for pdf

$ftx->parse('CONTENT','main');
	
	//	modified for pdf	--->
	if($_REQUEST['render'] == 'pdf'){
		$page = 'file';
		$html = $ftx->fetch('CONTENT');
			file_put_contents(CURRENT_VERSION_FOLDER.'temp_pdf/'.$page.'.html',"\xEF\xBB\xBF" . $html);
		loadPDF($page,'inline');exit;
	} else {
		return $ftx->fetch('CONTENT');
	}
	//	<---	modified for pdf