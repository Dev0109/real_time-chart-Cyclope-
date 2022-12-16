<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
ignore_user_abort(true);
set_time_limit(0);
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => $glob['pag'].".html"));
$ft->define_dynamic('template_row','main');
$ft->define_dynamic('window_template_row','template_row');
$ft->define_dynamic('sequencecount_row','main');
$dbu = new mysql_db();
	$trial = $dbu->field("SELECT value FROM `settings` WHERE `constant_name` = 'TRUENC'");
	$ecrypted_text = $dbu->field("SELECT message FROM `notification` WHERE `constant_name` = 'ENCRYPTED_TEXT'");
	if ($trial != 2236985){
		$ft->assign('ENCRYPTMESSAGERAW', '<div class="encryptmessage">' . $ft->lookup($ecrypted_text) . '</div>');
	}
// show next button 
if($glob['app']){
	$ft->assign('BACK_BUTTON', '<a href="index.php?pag=sequence" class="back-link"> <img src="'.CURRENT_VERSION_FOLDER.'img/back.png" /></a>');
}

//max one user

//max one day
$pieces = explode('-',$glob['time']['time']);
if(count($pieces) == 2){
	$start_time = strtotime(reset($pieces));
	$end_time = strtotime(end($pieces));
	if(($end_time - $start_time) > 86400){
		$end_time = $start_time + 86400;
	}
	$glob['time']['time'] = date('n/d/Y g:i A',$start_time).' - '.date('n/d/Y g:i A',$end_time);
	// $glob['time']['time'] = date('n/d/Y',mktime(0,0,0,date('m'),date('d'),date('Y')));
}
$pieces = explode('-',$glob['f']);
$type = substr($pieces[0],0,1);
$pieces[0] = substr($pieces[0],1);
if(count($pieces) == 1){
			$ft->assign(array(
				'NO_DATA_MESSAGE' => get_error($ft->lookup('Please select a user'),'warning'),
				'HIDE_CONTENT'	=> 'hide',
			));
} else {

	if($type == 'u')
	{
		if(count($pieces) == 2)
		{
			$dbu->query("SELECT COUNT(computer2member.computer_id) AS computers, computer2member.computer_id FROM computer2member WHERE computer2member.member_id = '".end($pieces)."'");
			if($dbu->move_next())
			{
				if($dbu->f('computers') > 1 )
				{
					$glob['f'] = 'u'.reset($pieces).'-'.$dbu->f('computer_id').'-'.end($pieces);
				}
			}
			file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log'," end\n".end($pieces)."\n",FILE_APPEND);
			file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log'," reset\n".reset($pieces)."\n",FILE_APPEND);
		}
	}

	if($type == 'c')
	{
		if(count($pieces) == 2)
		{
			$dbu->query("SELECT COUNT(computer2member.member_id) AS members, computer2member.member_id, member.department_id
			FROM computer2member INNER JOIN member ON computer2member.member_id = member.member_id
			WHERE computer2member.computer_id = '".end($pieces)."'");
			
			if($dbu->move_next())
			{
				if($dbu->f('members') > 1 )
				{
					$glob['f'] = 'c'.$dbu->f('department_id').'-'.end($pieces).'-'.$dbu->f('member_id');
				}
			}
		}
	}

	$filters = get_filters($glob['t'],$glob['f'],$glob['time']);
	extract($filters,EXTR_OVERWRITE);




	$nodes = explode('-',$glob['f']);
	$department_id = reset($nodes);
	unset($nodes);

	$categories = get_categories($glob['f'],0);

	$additional_application_filter = '';
	if($glob['app'] && !empty($glob['app']) )
	{
		$additional_application_filter .= " AND application.application_id = ".$glob['app'];
		$ft->assign(array(
			'APP' => $glob['app'],
		));
	}

	if($glob['win'] && !empty($glob['win']) )
	{
		$additional_application_filter .= " AND window.window_id = ".$glob['win'];
		$additional_application_filter .= " AND session_log.active = ".$glob['wact'];
		
		$ft->assign(array(
			'WIN' => $glob['win'],
			'WACT' => $glob['wact'],
			
		));
	}

	$ft->assign('EXPORT_APP_FILTER','&app='.$glob['app'].'&win='.$glob['win'].'&wact='.$glob['wact']);

	//add the extra filter for the minutes
	$matches = array();
	preg_match_all('!([0-9]{1,2}/[0-9]{1,2}/[0-9]{4})( [0-9]+\:[0-9]+ [AM|PM]+)?!',$glob['time']['time'],$matches);
	$pieces = array_shift($matches);
	switch (count($pieces)){
		case 1:
			$time = strtotime(current($pieces));
			$app_filter .= ' AND session_log.start_time >= '.$time;
			break;
		case 2:
			$start_time = strtotime(reset($pieces));
			$end_time = strtotime(end($pieces));
			
			$app_filter .= ' AND (session_log.start_time BETWEEN '.$start_time.' AND '.$end_time.')';
			break;
	}

	$dbu->query("SELECT application.description, 
	application.application_id,
	window.name,
	SUM(session_log.duration) AS application_total_time,
	session_log.* 
	FROM session_log 
	INNER JOIN window ON window.window_id = session_log.window_id
	INNER JOIN application ON application.application_id = session_log.application_id 
	INNER JOIN session ON session.session_id = session_log.session_id 
	".$app_join."
	WHERE 1=1 ".$app_filter." GROUP BY application.application_id ORDER BY application_total_time DESC");

	$app_details = array();
	$i = 0;

	while($dbu->move_next()){
			
		if($dbu->f('application_total_time') == 0)
		{
				break;
		}
		
		if($i < 15 )
		{
			$app_details[$dbu->f('application_id')]['color'] = $_SESSION['colors'][$i];
		}
		else 
		{
			$app_details[$dbu->f('application_id')]['color'] = end($_SESSION['colors']);
		}
		$i++;
	}

	$dbu->query("SELECT application.description, 
	application.application_id,
	window.name,
	session_log.* 
	FROM session_log 
	INNER JOIN window ON window.window_id = session_log.window_id
	INNER JOIN application ON application.application_id = session_log.application_id 
	INNER JOIN session ON session.session_id = session_log.session_id 
	".$app_join."
	WHERE 1=1 AND session_log.duration > 0 ".$app_filter." GROUP BY application.application_id, window.window_id ORDER BY start_time ASC");

	while($dbu->move_next()){
		$app_details[$dbu->f('application_id')]['total_windows']++;
	}

	$dbu->query("SELECT application.description, 
	application.application_id,
	window.name,
	session_log.* 
	FROM session_log 
	INNER JOIN window ON window.window_id = session_log.window_id
	INNER JOIN application ON application.application_id = session_log.application_id 
	INNER JOIN session ON session.session_id = session_log.session_id 
	".$app_join."
	WHERE 6=6 ".$app_filter.$additional_application_filter." ORDER BY start_time ASC");

	//$app_details = array();
	// $data = array_fill(0,24,array('tags'=> array(),'total' => 0,'private_end'=> array(),'private_start'=> array(),'private_total' => 0));
	$i = 0;
	$apps = array();
	$index = 0;
	$data = array();
	while ($dbu->move_next()){
		if(!is_array($apps[$dbu->f("application_id")])){
			$apps[$dbu->f("application_id")] = array("count"=>0);
		}
		if(!isset($apps[$dbu->f("application_id")][$dbu->f("window_id")])){
			$apps[$dbu->f("application_id")][$dbu->f("window_id")] = 1;
			$apps[$dbu->f("application_id")]["count"]++;
		}
		
		$type = '';
		switch ($dbu->f('type_id')){
			case 1:
				$type = 'chats';
				break;
			case 2:
				$type = 'documents';
				break;
			case 3:
				$type = 'sites';
				break;
			default:
				$type = 'windows';	
		}
		
		if($dbu->f('duration') == 0){
			continue;
		}
		$index = count($data);
		
		if($index != 0)
		{
			
			$indexprev = $index-1;

			if($data[$indexprev]['APP_ID'] == $dbu->f('application_id') && $data[$indexprev]['EXPAND'][0]['window_id'] == $dbu->f('window_id'))
			{
				// exit;
				$data[$indexprev]['TIME'] += $dbu->f('duration');
				//$data[$indexbefore]['WINDOWS']++;
				$data[$indexprev]['INTERVAL_END'] = date('g:i:s A',$dbu->f('end_time'));
				$data[$indexprev]['TIME_FORMATED'] = format_time($data[$indexprev]['TIME']);
				$str = $data[$indexprev]['TIMELINE_INFO'];
				$end = $data[$indexprev]['END_TIME'];
				$str = str_replace('end='.$end,'end='.$dbu->f('end_time'),$str);
				$data[$indexprev]['TIMELINE_INFO'] = $str;
				$data[$indexprev]['END_TIME'] = $dbu->f('end_time');			
				$data[$indexprev]['EXPAND'][count($data[$indexprev]['EXPAND'])-1]['window_duration'] += $dbu->f('duration');
			}
			else
			{
				$cat_name = $ft->lookup('Uncategorised');
				$cat_id = 1;
				
				if(isset($categories[$dbu->f('application_id').'-0'])){
					$cat_name = $ft->lookup($categories[$dbu->f('application_id').'-0']['category']);
					$cat_id = $categories[$dbu->f('application_id').'-0']['category_id'];
				}
				
				array_push($data, array(
					'APP_ID' => $dbu->f('application_id'),
					'APPLICATION' => $dbu->f('description'),
					'ACTIVITY_TYPE' => $dbu->f('active') == 0 || $dbu->f('active') == 1 ? array(0,1) : array(2,3),
					'TIME' => $dbu->f('duration'),
					'INTERVAL_START' => date('g:i:s A',$dbu->f('start_time')),
					'INTERVAL_END' => date('g:i:s A',$dbu->f('end_time')),
					'TIME_FORMATED' => format_time($dbu->f('duration')),
					'TIMELINE_INFO' => 'index_ajax.php?pag=xsequencedetails&sid='.$dbu->f('session_id').'&app='.$dbu->f('application_id').'&start='.$dbu->f('start_time').'&type='.$dbu->f('type_id').'&end='.$dbu->f('end_time'),
					//'WINDOWS' => 1,
					'APPLICATION_ID' => $dbu->f('application_id'),
					'CATEGORY' => $cat_name,
					'CATEGORY_ID' => $cat_id,
					'APP_FILTER_LINK' => 'index.php?pag=sequence&app='.$dbu->f('application_id'),
					'END_TIME' => $dbu->f('end_time'),
					'WINDOWS_TYPE' => $type,
					'ID' => $dbu->f('application_id'),
					'EXPAND' => array()
					));	
					
					if (is_array($data[$index]['EXPAND'])) {
						array_push($data[$index]['EXPAND'],array(
							'window_id'	=> $dbu->f('window_id'),
							'window_active' => $dbu->f('active'),
							'window_name' => $dbu->f('name'),
							'window_duration' => $dbu->f('duration'),
							'window_filter_link' => 'index.php?pag=sequence&app='.$dbu->f('application_id').'&win='.$dbu->f('window_id').'&wact='.$dbu->f('active')
						));
					}
			}
		}
		else
		{
			$cat_name = $ft->lookup('Uncategorised');
			$cat_id = 1;
			
			if(isset($categories[$dbu->f('application_id').'-0'])){
				$cat_name = $ft->lookup($categories[$dbu->f('application_id').'-0']['category']);
				$cat_id = $categories[$dbu->f('application_id').'-0']['category_id'];
			}

			array_push($data, array(
				'APP_ID' => $dbu->f('application_id'),
				'ACTIVITY_TYPE' => $dbu->f('active') == 0 || $dbu->f('active') == 1 ? array(0,1) : array(2,3),
				'APPLICATION' => $dbu->f('description'),
				'TIME' => $dbu->f('duration'),
				'INTERVAL_START' => date('g:i:s A',$dbu->f('start_time')),
				'INTERVAL_END' => date('g:i:s A',$dbu->f('end_time')),
				'TIME_FORMATED' => format_time($dbu->f('duration')),
				'WINDOWS_TYPE' => $type,
				//'WINDOWS' => 1,
				'APPLICATION_ID' => $dbu->f('application_id'),
				'CATEGORY' => $cat_name,
				'CATEGORY_ID' => $cat_id,
				'APP_FILTER_LINK' => 'index.php?pag=sequence&app='.$dbu->f('application_id'),
				'TIMELINE_INFO' => 'index_ajax.php?pag=xsequencedetails&sid='.$dbu->f('session_id').'&app='.$dbu->f('application_id').'&start='.$dbu->f('start_time').'&type='.$dbu->f('type_id').'&end='.$dbu->f('end_time'),
				'END_TIME' => $dbu->f('end_time'),
				'ID' => $dbu->f('application_id'),
				//'SEL' => !is_null($dbu->f('productive')) && in_array($dbu->f('productive'),array(0,2)) ? $dbu->f('productive') : 1,
				//'CSS_CLASS' => 'neutral',
				'EXPAND' => array()
			));	
			
			if (is_array($data[$index]['EXPAND'])) {
				array_push($data[$index]['EXPAND'],array(
					'window_id'	=> $dbu->f('window_id'),
					'window_active'	=> $dbu->f('active'),
					'window_name'	=> $dbu->f('name'),
					'window_duration'	=> $dbu->f('duration'),
					'window_filter_link' => 'index.php?pag=sequence&app='.$dbu->f('application_id').'&win='.$dbu->f('window_id').'&wact='.$dbu->f('active')
				));
			}
		}

	}

	// clean up the array
	foreach($data as $key => $value) {
		if (!$value['APP_ID']) {
			unset ($data[$key]);
		}
	}
	$sequences = array();
	$sequencecount = array();
	$sequences_db = $dbu->query("SELECT * FROM `sequence_dep` WHERE `department_id` = " . filter_var($department_id, FILTER_SANITIZE_NUMBER_INT));
	while ($sequences_db->next()){
		$sequences[] = $sequences_db->f("sequencegrp_id");
	}
	foreach ($sequences as $k => $v) {
		$sequence_list = array();
		$sequencename = $dbu->field("SELECT `name` FROM `sequence_reports` WHERE `sequencegrp_id` = " . $v);
		$sequencenoise = $dbu->field("SELECT `noise` FROM `sequence_reports` WHERE `sequencegrp_id` = " . $v);
		$sequence_list_db = $dbu->query("SELECT * FROM `sequence_list` WHERE `sequencegrp_id` = " . $v);
		$sequencecount[$sequencename] = 0;
		while ($sequence_list_db->next()){
			$sequence_list[$sequence_list_db->f("weight")] = array('APP_ID' => $sequence_list_db->f("app_id"), 'window_id' => $sequence_list_db->f("form_id"));
		}
		ksort($sequence_list);
		if ($sequencenoise == 1) {
			$intersect = array_uintersect( $data, $sequence_list, 'compareDeepValue');
			$to_elements = find_sequence_in_timeline($sequence_list, $intersect);
		} else {
			$to_elements = find_sequence_in_timeline($sequence_list, $data);
		}
		$data = apply_sequence_to_timeline($data, $to_elements, $sequencename);
		$sequencecount[$sequencename] = count_sequence_in_timeline($data, $to_elements, $sequencename);
	}

	$counter = 1;
	foreach ($sequencecount as $k => $v)
	{
		$ft->assign(array(
			'SEQUENCECOUNTNAME' => $k,
			'SEQUENCECOUNT' => $v,
		));
		
		$ft->parse('SEQUENCECOUNT_ROW_OUT','.sequencecount_row');
	}
// echo '<pre>';print_r($data);exit;
	foreach ($data as $key => $value){

		
		$class = '';
		if($counter % 2 == 0){
			$class .= ' even';
		}
		$counter++;
		
		$total_windows = $app_details[$value['APP_ID']]['total_windows'];

		$ft->assign($value);
			
		$ft->assign(array(
			'CLASS'	=> $class,
			'COUNTER'	=> $counter,
			'DEPARTMENT' => $department_id,
			'TOTAL_WINDOWS'	=> $total_windows,
			'WINDOWS'	=> $value['EXPAND'][0]['window_name'],
			'COLOR'	=> in_array(2,$value['ACTIVITY_TYPE']) ? 'E0E0E2' : $app_details[$value['APP_ID']]['color'],
		));
		
			
		
		$k =0;
		
		$ft->parse('TEMPLATE_ROW_OUT','.template_row');	
		$ft->assign(array(
			'SEQUENCENAME'	=> '',
			'HIGHLIGHT'	=> '',
		));
		
		$ft->clear('WINDOW_TEMPLATE_ROW_OUT');
	}
}
$dates = $dbu->row("SELECT MIN(date) AS genesis,MAX(date) AS last_day_on_earth FROM session ");
$ft->assign(array(
	'DEFAULT_VALUE' => isset($glob['time']) ? $glob['time']['time']: date('n/j/Y',$dates['genesis']).' - '.date('n/j/Y',$dates['last_day_on_earth']) ,
	'DATE_BEFORE' => date('n/j/Y',$dates['genesis']),
	'TIME_'.($glob['time']['type'] ? $glob['time']['type'] : 1) => 'selected="selected"',
	'HELP_LINK' => 'help.php?pag='.$glob['pag'],
));
global $bottom_includes;
$bottom_includes.='<script type="text/javascript">flobn.register("genesis",new Date("'.date('n/j/Y',$dates['genesis']).'"));
flobn.register("app",'.(isset($glob['app']) ? $glob['app'] : '0').');
</script><script type="text/javascript" src="ui/sequence-ui.js"></script>';


$export_header = get_export_header($glob['f']);
extract($export_header,EXTR_OVERWRITE);
$glob['append'] = trialEncrypt($member_name);

$ft->assign('MAX_VALUE',$max_value);
	//	modified for pdf	--->
	$export_header = get_export_header($_SESSION['filters']['f']);
	extract($export_header,EXTR_OVERWRITE);
	$ft->assign(array(
		'PDF_HEADER' => pdf_header(),
		'PDF_HIDE' => pdf_hide(),
		'PDF_CLASS' => pdf_class(),
		'TITLE' => $ft->lookup('Sequence'),
		'APPEND' => $glob['append'],
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
		$page = 'sequence';
		$html = $ft->fetch('CONTENT');
			file_put_contents(CURRENT_VERSION_FOLDER.'temp_pdf/'.$page.'.html',"\xEF\xBB\xBF" . $html);
		loadPDF($page,'inline');exit;
	} else {
		return $ft->fetch('CONTENT');
	}
	//	<---	modified for pdf