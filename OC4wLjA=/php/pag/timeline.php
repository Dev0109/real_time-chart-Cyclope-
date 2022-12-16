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
$dbu = new mysql_db();
	$trial = $dbu->field("SELECT value FROM `settings` WHERE `constant_name` = 'TRUENC'");
	$ecrypted_text = $dbu->field("SELECT message FROM `notification` WHERE `constant_name` = 'ENCRYPTED_TEXT'");
	if ($trial != 2236985){
		$ft->assign('ENCRYPTMESSAGERAW', '<div class="encryptmessage">' . $ft->lookup($ecrypted_text) . '</div>');
	}
// show next button 
if($glob['app']){
	$ft->assign('BACK_BUTTON', '<a href="index.php?pag=timeline" class="back-link"> <img src="'.CURRENT_VERSION_FOLDER.'img/back.png" /></a>');
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
			$wewantnograph = 1;
} else {
	$wewantnograph = 0;
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

	$categories = get_categories($glob['f'],'all');

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
	application.application_type as type,
	window.name,
	session_log.* 
	FROM session_log 
	INNER JOIN window ON window.window_id = session_log.window_id
	INNER JOIN application ON application.application_id = session_log.application_id 
	INNER JOIN session ON session.session_id = session_log.session_id 
	".$app_join."
	WHERE 6=6 ".$app_filter.$additional_application_filter." ORDER BY start_time ASC");

	//$app_details = array();
	$data = array_fill(0,24,array('tags'=> array(),'total' => 0,'private_end'=> array(),'private_start'=> array(),'private_total' => 0));
	$i = 0;
	$apps = array();
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
		
		if(!isset($data[$dbu->f('hour')])){
			$data[$dbu->f('hour')] = array();
		}
		$index = count($data[$dbu->f('hour')]['tags']);
		
		if($index != 0)
		{
			
			$index--;
			
			if($data[$dbu->f('hour')]['tags'][$index]['APP_ID'] == $dbu->f('application_id') && in_array($dbu->f('active'), $data[$dbu->f('hour')]['tags'][$index]['ACTIVITY_TYPE']) && $data[$dbu->f('hour')]['tags'][$index]['INTERVAL_END'] == date('g:i:s A',$dbu->f('start_time')))
			{
				$data[$dbu->f('hour')]['tags'][$index]['TIME'] += $dbu->f('duration');
				//$data[$dbu->f('hour')]['tags'][$index]['WINDOWS']++;
				$data[$dbu->f('hour')]['tags'][$index]['INTERVAL_END'] = date('g:i:s A',$dbu->f('end_time'));
				$data[$dbu->f('hour')]['tags'][$index]['TIME_FORMATED'] = format_time($data[$dbu->f('hour')]['tags'][$index]['TIME']);
				$str = $data[$dbu->f('hour')]['tags'][$index]['TIMELINE_INFO'];
				$end = $data[$dbu->f('hour')]['tags'][$index]['END_TIME'];
				$str = str_replace('end='.$end,'end='.$dbu->f('end_time'),$str);
				$data[$dbu->f('hour')]['tags'][$index]['TIMELINE_INFO'] = $str;
				$data[$dbu->f('hour')]['tags'][$index]['END_TIME'] = $dbu->f('end_time');
				
				$comp = end($data[$dbu->f('hour')]['tags'][$index]['EXPAND']);
				
				if ( $comp['window_id'] == $dbu->f('window_id') && $comp['window_active'] == $dbu->f('active') )
				{
					$data[$dbu->f('hour')]['tags'][$index]['EXPAND'][count($data[$dbu->f('hour')]['tags'][$index]['EXPAND'])-1]['window_duration'] += $dbu->f('duration');
				}
				else 
				{
					array_push($data[$dbu->f('hour')]['tags'][$index]['EXPAND'],array(
						'window_id'	=> $dbu->f('window_id'),
						'window_active' => $dbu->f('active'),
						'window_name' => $dbu->f('name'),
						'window_duration' => $dbu->f('duration'),
						'window_filter_link' => 'index.php?pag=timeline&app='.$dbu->f('application_id').'&win='.$dbu->f('window_id').'&wact='.$dbu->f('active')
					));
				}
				
				if(in_array($dbu->f('active'),array(2,3)))
				{
					array_push($data[$dbu->f('hour')]['private_end'], date('g:i:s A',$dbu->f('end_time')));
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
				else if(isset($categories[$dbu->f('application_id').'-1'])){
						$cat_name = $ft->lookup($categories[$dbu->f('application_id').'-1']['category']);
						$cat_id = $categories[$dbu->f('application_id').'-1']['category_id'];
					}
					else if(isset($categories[$dbu->f('application_id').'-2'])){
							$cat_name = $ft->lookup($categories[$dbu->f('application_id').'-2']['category']);
							$cat_id = $categories[$dbu->f('application_id').'-2']['category_id'];
						}
						else if(isset($categories[$dbu->f('application_id').'-3'])){
								$cat_name = $ft->lookup($categories[$dbu->f('application_id').'-3']['category']);
								$cat_id = $categories[$dbu->f('application_id').'-3']['category_id'];
							}
				
				if(in_array($dbu->f('active'),array(2,3)))
				{
					$data[$dbu->f('hour')]['total_private_app']++;
					
					array_push($data[$dbu->f('hour')]['private_start'], date('g:i:s A',$dbu->f('start_time')));
					array_push($data[$dbu->f('hour')]['private_end'], date('g:i:s A',$dbu->f('end_time')));
				}
				
				array_push($data[$dbu->f('hour')]['tags'], array(
					'APP_ID' => $dbu->f('application_id'),
					'APPLICATION' => $dbu->f('description'),
					'ACTIVITY_TYPE' => $dbu->f('active') == 0 || $dbu->f('active') == 1 ? array(0,1) : array(2,3),
					'TIME' => $dbu->f('duration'),
					'INTERVAL_START' => date('g:i:s A',$dbu->f('start_time')),
					'INTERVAL_END' => date('g:i:s A',$dbu->f('end_time')),
					'TIME_FORMATED' => format_time($dbu->f('duration')),
					'HOUR_FORMATED' => 'h'.$dbu->f('hour'),
					'TIMELINE_INFO' => 'index_ajax.php?pag=xtimelinedetails&sid='.$dbu->f('session_id').'&app='.$dbu->f('application_id').'&start='.$dbu->f('start_time').'&type='.$dbu->f('type_id').'&end='.$dbu->f('end_time'),
					//'WINDOWS' => 1,
					'APPLICATION_ID' => $dbu->f('application_id'),
					'CATEGORY' => $cat_name,
					'CATEGORY_ID' => $cat_id,
					'CAT_TYPE' => $dbu->f('type'),
					'APP_FILTER_LINK' => 'index.php?pag=timeline&app='.$dbu->f('application_id'),
					'END_TIME' => $dbu->f('end_time'),
					'WINDOWS_TYPE' => $type,
					'ID' => $dbu->f('application_id'),
					'EXPAND' => array()
					));	
					
					array_push($data[$dbu->f('hour')]['tags'][count($data[$dbu->f('hour')]['tags'])-1]['EXPAND'],array(
						'window_id'	=> $dbu->f('window_id'),
						'window_active' => $dbu->f('active'),
						'window_name' => $dbu->f('name'),
						'window_duration' => $dbu->f('duration'),
						'window_filter_link' => 'index.php?pag=timeline&app='.$dbu->f('application_id').'&win='.$dbu->f('window_id').'&wact='.$dbu->f('active')
					));
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
			else if(isset($categories[$dbu->f('application_id').'-1'])){
					$cat_name = $ft->lookup($categories[$dbu->f('application_id').'-1']['category']);
					$cat_id = $categories[$dbu->f('application_id').'-1']['category_id'];
				}
				else if(isset($categories[$dbu->f('application_id').'-2'])){
						$cat_name = $ft->lookup($categories[$dbu->f('application_id').'-2']['category']);
						$cat_id = $categories[$dbu->f('application_id').'-2']['category_id'];
					}
					else if(isset($categories[$dbu->f('application_id').'-3'])){
							$cat_name = $ft->lookup($categories[$dbu->f('application_id').'-3']['category']);
							$cat_id = $categories[$dbu->f('application_id').'-3']['category_id'];
						}
			
			if(in_array($dbu->f('active'),array(2,3)))
			{
				$data[$dbu->f('hour')]['total_private_app']++;
				
				array_push($data[$dbu->f('hour')]['private_start'], date('g:i:s A',$dbu->f('start_time')));
				array_push($data[$dbu->f('hour')]['private_end'], date('g:i:s A',$dbu->f('end_time')));
			}
			
			array_push($data[$dbu->f('hour')]['tags'], array(
				'APP_ID' => $dbu->f('application_id'),
				'ACTIVITY_TYPE' => $dbu->f('active') == 0 || $dbu->f('active') == 1 ? array(0,1) : array(2,3),
				'APPLICATION' => $dbu->f('description'),
				'TIME' => $dbu->f('duration'),
				'INTERVAL_START' => date('g:i:s A',$dbu->f('start_time')),
				'INTERVAL_END' => date('g:i:s A',$dbu->f('end_time')),
				'TIME_FORMATED' => format_time($dbu->f('duration')),
				'HOUR_FORMATED' => 'h'.$dbu->f('hour'),
				'WINDOWS_TYPE' => $type,
				//'WINDOWS' => 1,
				'APPLICATION_ID' => $dbu->f('application_id'),
				'CATEGORY' => $cat_name,
				'CATEGORY_ID' => $cat_id,
				'APP_FILTER_LINK' => 'index.php?pag=timeline&app='.$dbu->f('application_id'),
				'TIMELINE_INFO' => 'index_ajax.php?pag=xtimelinedetails&sid='.$dbu->f('session_id').'&app='.$dbu->f('application_id').'&start='.$dbu->f('start_time').'&type='.$dbu->f('type_id').'&end='.$dbu->f('end_time'),
				'END_TIME' => $dbu->f('end_time'),
				'ID' => $dbu->f('application_id'),
				//'SEL' => !is_null($dbu->f('productive')) && in_array($dbu->f('productive'),array(0,2)) ? $dbu->f('productive') : 1,
				//'CSS_CLASS' => 'neutral',
				'EXPAND' => array()
			));	
			
			array_push($data[$dbu->f('hour')]['tags'][count($data[$dbu->f('hour')]['tags'])-1]['EXPAND'],array(
				'window_id'	=> $dbu->f('window_id'),
				'window_active'	=> $dbu->f('active'),
				'window_name'	=> $dbu->f('name'),
				'window_duration'	=> $dbu->f('duration'),
				'window_filter_link' => 'index.php?pag=timeline&app='.$dbu->f('application_id').'&win='.$dbu->f('window_id').'&wact='.$dbu->f('active')
			));
		}

		$data[$dbu->f('hour')]['total'] += $dbu->f('duration');
		
		
		if(in_array($dbu->f('active'),array(2,3)))
		{
			$data[$dbu->f('hour')]['private_total'] += $dbu->f('duration');
		}
			
		$total_duration += $dbu->f('duration');
		
		if($dbu->f('hour') > 12){
			$data[$dbu->f('hour')]['name'] = ($dbu->f('hour') - 12).':00 PM';	
		}else if ($dbu->f('hour') == 12)
		{
			$data[$dbu->f('hour')]['name'] = '12:00 PM';	
		}else
		{
			$data[$dbu->f('hour')]['name'] = $dbu->f('hour') .':00 AM';	
		}
	}

	$counter = 0;
	for($i = 0,$len = count($data); $i < $len;$i++){
		if(empty($data[$i]['tags'])){
			continue;
		}
		
		$hour_counter = 0;
		$do_not_parse_private_for_this_hour_anymore = false;
		$do_not_parse_rowspan_for_this_hour_anymore = false;
		
		foreach ($data[$i]['tags'] as $key => $value){
			
			if(empty($data[$i]['total_private_app']))
			{
				$rowspan = count($data[$i]['tags']);
				
			}
			else if($data[$i]['total_private_app'] == count($data[$i]['tags']))
			{
				$rowspan = 1;
			}
			else if ($glob['app'])
			{
				$rowspan = 1;
			}
			else 
			{
				$rowspan = count($data[$i]['tags']) - $data[$i]['total_private_app']+1;
			}
			
			if(!$do_not_parse_rowspan_for_this_hour_anymore)
			{
				$ft->assign('ROW','<td rowspan="'.$rowspan.'" class="first-of-type">'.$data[$i]['name'].'<br />'/*.format_time($data[$i]['total']).' total*/.'</td>');
			}
			else
			{
				$ft->assign('ROW','');
			}
			
			$class = '';
			
			if($hour_counter == $rowspan -1 )
			{
				$class ='end ';
			}
			
			if($counter % 2){
				$class .= 'even';
			}
			
			$total_windows = $app_details[$value['APP_ID']]['total_windows'];
			
			if(in_array(2,$value['ACTIVITY_TYPE'])){
				$value['APPLICATION'] = 'Private Time';
				$value['EXPAND'] = array();
				$value['CATEGORY'] = '';
				$value['WINDOWS_TYPE'] = '';
				$value['APP_FILTER_LINK'] = '#';
				$total_windows = 0;
				$value['INTERVAL_START'] = reset($data[$i]['private_start']);
				$value['INTERVAL_END'] = end($data[$i]['private_end']);
				$value['TIME_FORMATED'] = format_time($data[$i]['private_total']);
					$value['HOUR_FORMATED'] = 'h'.$dbu->f('hour');
			}
			
			$ft->assign($value);
			
			$ft->assign(array(
				'CLASS'	=> $class,
				'DEPARTMENT' => $department_id,
				'TOTAL_WINDOWS'	=> $total_windows,
				'WINDOWS'	=> count($value['EXPAND']),
				'COLOR'	=> in_array(2,$value['ACTIVITY_TYPE']) ? 'E0E0E2' : $app_details[$value['APP_ID']]['color'],
			));
			
				
			
			$k =0;
			
			foreach ($value['EXPAND'] as $windows)
			{
				$ft->assign(array(
					'WINDOW_CLASS' => ( $k % 2 == 0 ) ? '' : 'even',
					'WINDOW_TIME' => format_time($windows['window_duration']),
					'WINDOW_FILTER_LINK' => $windows['window_filter_link'],
					'WINDOW_NAME' => $windows['window_name'],
					'WINDOW_ACTIVE' => $windows['window_active'] ? '' : ' (<b>Idle</b>)',
				));

				switch ($windows['window_active']){
					case 0:
						$ft->assign('WINDOW_ACTIVE','(<b>Idle</b>)');
						break;
					case 1:
						$ft->assign('WINDOW_ACTIVE','');
						break;
					case 2:
						$ft->assign('WINDOW_ACTIVE','(<b>Private Idle</b>)');
						break;
					case 3:
						$ft->assign('WINDOW_ACTIVE','(<b>Private</b>)');
						break;
					default:
						$ft->assign('WINDOW_ACTIVE','');
						break;
				}
				
				$ft->parse('WINDOW_TEMPLATE_ROW_OUT','.window_template_row');
				$k++;
			}
			
			/*if( $key == 0 )
			{
				
				
				$counter++;
				$hour_counter++;
				if(in_array(2,$value['ACTIVITY_TYPE']) && !$glob['app'])
				{
					$do_not_parse_private_for_this_hour_anymore = true;
					$ft->assign('HIDE_IF_PRIVATE', 'hide');
				}
				
				$ft->parse('TEMPLATE_ROW_OUT','.template_row');
			}
			else*/ if(in_array(1,$value['ACTIVITY_TYPE']))
			{
				$ft->assign('HIDE_IF_PRIVATE', '');
				$ft->parse('TEMPLATE_ROW_OUT','.template_row');
				$counter++;
				$hour_counter++;
				$do_not_parse_rowspan_for_this_hour_anymore = true;
			}
			else if( !$do_not_parse_private_for_this_hour_anymore && in_array(2,$value['ACTIVITY_TYPE']) && !$glob['app'])
			{
				$ft->assign('HIDE_IF_PRIVATE', 'hide');
				
				$counter++;
				$hour_counter++;
				
				$do_not_parse_rowspan_for_this_hour_anymore = true;
				$do_not_parse_private_for_this_hour_anymore = true;
				
				$ft->parse('TEMPLATE_ROW_OUT','.template_row');
			}
			
			//$ft->parse('TEMPLATE_ROW_OUT','.template_row');
			
			$ft->clear('WINDOW_TEMPLATE_ROW_OUT');
			//$counter++;
			
		}
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
	</script><script type="text/javascript" src="ui/timeline-ui.js"></script>';

	if(!$counter && count($pieces) != 1)
	{
		$ft->assign(array(
			'NO_DATA_MESSAGE' => get_error($ft->lookup('No data to display for your current filters'),'warning'),
			'HIDE_CONTENT'	=> 'hide',
		));
	}
	elseif ($counter && $wewantnograph != 1)
	{
		$ft->assign(array(
			'NO_DATA_MESSAGE' => '',
			'HIDE_CONTENT'	=> '',
		));
		//	recreate array for chart
		$chart_data = array();
		foreach ($data as $hour=>$activity){
			foreach($activity['tags'] as $key=>$oneapp){
				// echo "<pre>" . print_r($oneapp['APP_ID'],1) . "</pre>";
				$chart_data[$oneapp['APP_ID']]['name'] = $oneapp['APPLICATION'];
				$chart_data[$oneapp['APP_ID']]['color'] = $app_details[$oneapp['APP_ID']]['color'];
				$chart_data[$oneapp['APP_ID']]['hour'][$hour]['duration'] = $chart_data[$oneapp['APP_ID']]['hour'][$hour]['duration'] + $oneapp['TIME'];
			}
		}
		//	fill in the empty hours
		foreach($chart_data as $fillhours_appid=>$fillhours_appdata){
			for($everyhour = 0; $everyhour <= 23; $everyhour++){
				$chart_data[$fillhours_appid]['hour'][$everyhour]['duration'] = ($chart_data[$fillhours_appid]['hour'][$everyhour]['duration'] > 0) ? $chart_data[$fillhours_appid]['hour'][$everyhour]['duration'] : 0;
				$chart_data[$fillhours_appid]['hour'][$everyhour]['duration'] = ($chart_data[$fillhours_appid]['hour'][$everyhour]['duration'] < 3600) ? $chart_data[$fillhours_appid]['hour'][$everyhour]['duration'] : 3600;
			}
			ksort($chart_data[$fillhours_appid]['hour']);
		}
			// echo "<pre>" . print_r($chart_data,1) . "</pre>";

		$timelinechart = new stdClass;
		$timelinechart->settings->container->selector = "timelinechart";
		$timelinechart->settings->container->height = "250px";
		$timelinechart->settings->container->width = "685px";

		$timelinechart->height = 250;	//	ajax fix
		$timelinechart->width = 685;		//	ajax fix
		$timelinechart->barwidth = 27;
		$timelinechart->axisX->labelFontSize = 11;
		$timelinechart->axisX->interval = 1;
		$timelinechart->axisY->maximum = 100;
		$timelinechart->axisY->suffix = "%";
		$timelinechart->axisY->interval = 25;
		$timelinechart->legend->verticalAlign = "bottom";
		$timelinechart->legend->horizontalAlign = "center";
		$timelinechart->legend->fontSize = 10;

		$i = 0;
		foreach ($chart_data as $app_id=>$data_i){

			$timelinechart->data[$i]->type = "stackedColumn";
			$timelinechart->data[$i]->toolTipContent = $data_i['name'] . ": {y}%";
			// $timelinechart->data[$i]->showInLegend = true;
			$timelinechart->data[$i]->legendText = $data_i['name'];
			$timelinechart->data[$i]->color = '#' . $data_i['color'];
			
			
			$j = 0;
			foreach ($data_i['hour'] as $hour_key => $hour_i){
					$timelinechart->data[$i]->dataPoints[$j]->y = (float)number_format($hour_i['duration'] * 100 / 3600,2);
					$timelinechart->data[$i]->dataPoints[$j]->label = $hour_key;
				$j++;
			}
			$i++;
		}

		$ft->assign('TIMELINE_CHART',drawGraph($timelinechart));
	}

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
	'TITLE' => $ft->lookup('Timeline'),
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
		$page = 'timeline';
		$html = $ft->fetch('CONTENT');
			file_put_contents(CURRENT_VERSION_FOLDER.'temp_pdf/'.$page.'.html',"\xEF\xBB\xBF" . $html);
		loadPDF($page,'inline');exit;
	} else {
		return $ft->fetch('CONTENT');
	}
	//	<---	modified for pdf