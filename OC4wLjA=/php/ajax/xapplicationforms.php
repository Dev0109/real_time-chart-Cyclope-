<?php
/************************************************************************
	* @Author: MedeeaWeb Works                                              *
	************************************************************************/
	$ftx=new ft(ADMIN_PATH.MODULE."templates/");
	$ftx->define(array('main' => "xapplicationforms.html"));
	$ftx->define_dynamic('template_row','main');

	$dbu = new mysql_db();

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

	if(($_SESSION['NUMBER_OF_ROWS']) && (is_numeric($_SESSION['NUMBER_OF_ROWS'])))
		$number_of_rows =  "LIMIT 0,".$_SESSION['NUMBER_OF_ROWS'];
		


	$filters = get_filters($glob['t'],$glob['f'],$glob['time']);
	extract($filters,EXTR_OVERWRITE);

	if($glob['app'] && $glob['app'] != -1 /*&& in_array($glob['app'],array_keys($ddr))*/){
		$total_filter .= ' AND application_id ='.$glob['app'];
		$app_filter .= ' AND session_log.application_id = '.$glob['app'];
		$ftx->assign('EXPORT_APP_FILTER','&app='.$glob['app']);
	}


if(($_SESSION['NUMBER_OF_ROWS']) && (is_numeric($_SESSION['NUMBER_OF_ROWS']))){
	$rowcount =  $_SESSION['NUMBER_OF_ROWS'];
	$number_of_rows =  "LIMIT 0,".$rowcount;
} else {
	$rowcount =  500;
	$number_of_rows =  "";
}

$totaltime = array('total' => 0, 'active' => 0, 'inactive' => 0);

//	modified for pdf	--->
if($_REQUEST['render'] == 'pdf'){
	$l_r = $rowcount;
	$number_of_rows =  "";
}
if($_REQUEST['render'] == 'pdf' && $_REQUEST['send'] == 'email'){
	$rowcount = get_email_rowcount();
	$l_r = $rowcount;
	$number_of_rows =  "LIMIT 0,".$rowcount;
}
//	<---	modified for pdf


	//calculate the total
	$total = $dbu->field("SELECT SUM(session_log.duration) FROM session_log
						INNER JOIN session ON session.session_id = session_log.session_id
						".$app_join." WHERE 1=1 AND session_log.active < 2 ".$app_filter);

	$total = $total ? $total : 1;
	$all_time= array();

	$dbu->query("SELECT
	SUM(session_log.duration) AS duration,
	window.name,
	window.application_id,
	window.window_id FROM session_log
	INNER JOIN window ON window.window_id = session_log.window_id
	INNER JOIN session ON session.session_id = session_log.session_id
	".$app_join."
	WHERE 1=1 AND session_log.active < 2 ".$app_filter."
	GROUP BY session_log.window_id
	HAVING duration > 0
	ORDER BY duration desc");

	while ($dbu->move_next())
	{
		$all_time[$dbu->f('window_id')] = $dbu->f('duration');
	}

	//	lorand
	$sortable_columns = array(
		'duration',
		'application.description',
		'application.application_type',
		'window.name',
		);

	$sortcolumns = get_sorting($sortable_columns,'','desc');

	$ftx->assign(array(
		'ANCHOR_INNER_0' => render_anchor_inner(0),
		'ANCHOR_INNER_1' => render_anchor_inner(1),
		'ANCHOR_INNER_2' => render_anchor_inner(2),
		'ANCHOR_INNER_3' => render_anchor_inner(3),
		'DEBUGMESSAGE' => '',
		// 'DEBUGMESSAGE' => basename($_SERVER['PHP_SELF']),
		// 'DEBUGMESSAGE' => $sortcolumns,
	));
	//END

	$dbu->query("SELECT
	SUM(session_log.duration) AS duration,
	window.name,
	window.application_id,
	application.description,
	application.application_type,
	window.window_id FROM session_log
	INNER JOIN window ON window.window_id = session_log.window_id
	INNER JOIN session ON session.session_id = session_log.session_id
	INNER JOIN application ON application.application_id = window.application_id
	".$app_join."
	WHERE 1=1 AND session_log.active = 1 ".$app_filter."
	GROUP BY session_log.window_id
	" . $sortcolumns . " ".$number_of_rows);
	$max_rows=$dbu->records_count();
	$dbu->move_to($offset * $l_r);
	$i=0;

	$options = array(
		'0' => 'Application Forms',
		'1' => 'Chat',
		'2' => 'Document',
		'3' => 'Internet'
	);
	while($dbu->move_next() && $i < $l_r){
		if(!$dbu->f('duration'))
		{
			break;
		}
		
		$ftx->assign(array(
			'NAME' => $dbu->f('name'), 
			'APPLICATION' => $dbu->f('description'), 
			'ID' => $dbu->f('application_id'), 
			'CATEGORY_ID' => $dbu->f('application_type'), 
			'CATEGORY' => $options[$dbu->f('application_type')], 
			'TOTAL_TIME' => format_time($all_time[$dbu->f('window_id')]),
			'ACTIVE_TIME' => format_time($dbu->f('duration')),
			'IDLE_TIME' => format_time($all_time[$dbu->f('window_id')] - $dbu->f('duration')),
			'WIDTH' => ceil(($dbu->f('duration') * 140) / $total),
		'PDF_HIDE' => pdf_hide(),
		));
		
		$totaltime['total']		= $totaltime['total'] + $all_time[$dbu->f('window_id')];
		$totaltime['active']	= $totaltime['active'] + $dbu->f('duration');
		$totaltime['idle']		= $totaltime['idle'] + ($all_time[$dbu->f('window_id')] - $dbu->f('duration'));
		
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
		'TOTALTOTAL' => format_time($totaltime['total']),
		'TOTALACTIVE' => format_time($totaltime['active']),
		'TOTALIDLE' => format_time($totaltime['idle']),
		'FILTER_PAGE' => "index_ajax.php?pag=xapplicationforms"
	));
	$arguments = '&rp='.$l_r;
	if($offset > 0){
		 $ftx->assign('BACKLINK',"index_ajax.php?pag=xapplicationforms&offset=".($offset-1).$arguments);
	}else{
		 $ftx->assign('BACKLINK',"index_ajax.php?pag=xapplicationforms&offset=".($offset).$arguments);
	}
	if($offset < $end-1){
		 $ftx->assign('NEXTLINK',"index_ajax.php?pag=xapplicationforms&offset=".($offset+1).$arguments);
	}else{
		 $ftx->assign('NEXTLINK',"index_ajax.php?pag=xapplicationforms&offset=".($offset).$arguments);
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
		'TITLE' => $ftx->lookup('Application Forms'),
		'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
		'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
	));
	//	<---	modified for pdf

	if(count(explode('-',$glob['f'])) == 1){
		$bottom_includes.= '<script type="text/javascript">flobn.register("thouShallNotMove",0);</script>';	
		$glob['thouShallNotMove'] = 0;
	}else{
		$bottom_includes.= '<script type="text/javascript">flobn.register("thouShallNotMove",1);</script>';	
		$glob['thouShallNotMove'] = 1;
	}

	$ftx->parse('CONTENT','main');
	//	modified for pdf	--->
	if($_REQUEST['render'] == 'pdf'){
		$page = 'applicationforms';
		$html = $ftx->fetch('CONTENT');
			file_put_contents(CURRENT_VERSION_FOLDER.'temp_pdf/'.$page.'.html',"\xEF\xBB\xBF" . $html);
		loadPDF($page,'inline');exit;
	} else {
		return $ftx->fetch('CONTENT');
	}
	//	<---	modified for pdf