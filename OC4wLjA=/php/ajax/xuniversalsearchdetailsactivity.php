<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/



$fts=new ft(ADMIN_PATH.MODULE."templates/");
$fts->define(array('main' => 'xuniversalsearchdetailsactivity.html'));
$fts->define_dynamic('template_row','main');
set_time_limit(0);

$filters = get_filters($glob['t'],$glob['f'],$glob['time']);
extract($filters,EXTR_OVERWRITE);


$filter = '';
if(isset($glob['search_key'])){
	$glob['search_key'] = filter_var($glob['search_key'],FILTER_SANITIZE_STRING);
	$filter = " LIKE '%".encode_numericentity($glob['search_key'])."%'";
	
}

if (strpos($glob['f'],'-') !== false){
	// user selected
	$globarray = explode('-',$glob['f']);
	$uid = $globarray[2];
	$userfilter_querypart = ' AND member.member_id = ' . $uid;
} else {
	// department selected
	if (!$glob['f']) {$glob['f'] = 's1';}
	$mdepid = filter_var($glob['f'], FILTER_SANITIZE_NUMBER_INT);
	
			$nodeInfo = $dbu->query('SELECT `lft` , `rgt` FROM `department` WHERE `department_id` = ' . $mdepid);
			if($nodeInfo->next()){
					$mdep['lft'] = $nodeInfo->f('lft');
					$mdep['rgt'] = $nodeInfo->f('rgt');
			}

	$dept_list = array();
	$query = $dbu->query("SELECT department_id FROM department WHERE lft BETWEEN ".$mdep['lft'].' AND '.$mdep['rgt']);
	while ($query->next()){
		array_push($dept_list,$query->f('department_id'));
	}
	
	$userfilter_querypart = ' AND member.department_id IN (' . implode(',',$dept_list) . ')';
}

$searchtype = $glob['searchtype'];
$dbu = new mysql_db();

if(strpos($glob['time']['time'],' - ') !== false){
	$day_interval = explode(' - ',$glob['time']['time']);
	$day_start = $day_interval[0];
	$day_ended = $day_interval[1];
	// debug_log(print_r($date->format("Ymd"),1),'timexo');
} else {
	$day_start = $glob['time']['time'];
	$day_ended = $glob['time']['time'];
}
$begin = new DateTime( $day_start );
$end = new DateTime( $day_ended );
$end = $end->modify( '+1 day' );

$interval = new DateInterval('P1D');
$daterange = new DatePeriod($begin, $interval ,$end);
// debug_log(print_r($time_filter,1) . ' --- ','timexo');
foreach($daterange as $date){
	$time = strtotime($date->format("m/d/Y"));
	$start_time = mktime(0,0,0,date('n',$time),date('d',$time),date('Y',$time));
	$end_time = mktime(23,59,59,date('n',$time),date('d',$time),date('Y',$time));
	$time_filter = ' AND (session.date >= '.$start_time.' AND session.date <= '.$end_time.')';
	// debug_log(print_r($time_filter,1),'timexo');
	switch ($searchtype){
					case 3://internet search
					
					$dbu->query(" SELECT member.logon,
										member.first_name,
										member.last_name, member.alias,
										computer.name AS computer_name,
										SUM(session_website.duration)AS duration,
										session.date,
										CONCAT(member.first_name,' ',member.last_name) AS name,
										domain.domain AS domain_name
									FROM session_website
									INNER JOIN domain ON domain.domain_id = session_website.domain_id
									INNER JOIN website ON website.website_id = session_website.website_id AND website.domain_id = domain.domain_id
									INNER JOIN session ON session.session_id = session_website.session_id
									INNER JOIN member ON member.member_id = session.member_id
									INNER JOIN computer2member ON computer2member.member_id = member.member_id
									INNER JOIN computer ON computer.computer_id =computer2member.computer_id
									WHERE 1=1 ".$userfilter_querypart." ".clean_filter($time_filter)." 
									AND session_website.application_id = website.application_id AND domain.domain ".$filter." 
									GROUP BY member.member_id,domain.domain
									ORDER BY member.member_id ");
								$i=0;
							while($dbu->move_next()){
									$fts->assign(array(
										'NAME' 		=> $dbu->f('alias') == 1 ? decode_numericentity($dbu->f('name')) : decode_numericentity($dbu->f('logon')).' ('. decode_numericentity($dbu->f('computer_name')).')',
										'ITEM_NAME' => highlight($dbu->f('domain_name'),$glob['search_key']),
										'COMPUTER'	=> $dbu->f('computer_name'),
										'DURATION_D'=> intval(($dbu->f('duration'))/(24*3600)),
										'DURATION_H'=> (intval($dbu->f('duration')/3600))%24,
										'DURATION_M'=> (intval($dbu->f('duration')/60))%60,
										'DURATION_S'=> intval($dbu->f('duration'))%60,
										'DATE' 		=> date('d/m/Y',$time),
										'FIRST'		=> $i==0? 'search_border':'',
										));
									
									if($i % 2 != 0 ){
										$fts->assign('EVEN','even');
									}else{
										$fts->assign('EVEN','');
									}
									$fts->parse('TEMPLATE_ROW_OUT','.template_row');
									$i++;
								}
					break;
					case 4://chat search
						$dbu ->query("SELECT member.logon,
								member.first_name,
								member.last_name, member.alias,
								computer.name AS computer_name,
								SUM(session_chat.duration)AS duration,
								session.date,
								CONCAT(member.first_name,' ',member.last_name) AS name,
								chat.name AS chat_name
						FROM chat
						INNER JOIN session_chat ON session_chat.chat_id = chat.chat_id
						INNER JOIN session ON session.session_id = session_chat.session_id
						INNER JOIN member ON member.member_id = session.member_id
						INNER JOIN computer2member ON computer2member.member_id = member.member_id
						INNER JOIN computer ON computer.computer_id =computer2member.computer_id
						WHERE 1=1 ".$userfilter_querypart." ".clean_filter($time_filter)." AND session_chat.application_id = chat.application_id AND chat.name ".$filter." 
						GROUP BY member.member_id,chat.name
						ORDER BY member.member_id ");
						

							$i=0;
							while($dbu->move_next()){
									$fts->assign(array(
										'NAME' 		=> $dbu->f('alias') == 1 ? decode_numericentity($dbu->f('name')) : decode_numericentity($dbu->f('logon')).' ('. decode_numericentity($dbu->f('computer_name')).')',
										'ITEM_NAME' => highlight($dbu->f('chat_name'),$glob['search_key']),
										'COMPUTER'	=> $dbu->f('computer_name'),
										'DURATION_D'=> intval(($dbu->f('duration'))/(24*3600)),
										'DURATION_H'=> (intval($dbu->f('duration')/3600))%24,
										'DURATION_M'=> (intval($dbu->f('duration')/60))%60,
										'DURATION_S'=> intval($dbu->f('duration'))%60,
										'DATE' 		=> date('d/m/Y',$time),
										'FIRST'		=> $i==0? 'search_border':'',
										));
									
									if($i % 2 != 0 ){
										$fts->assign('EVEN','even');
									}else{
										$fts->assign('EVEN','');
									}
									$fts->parse('TEMPLATE_ROW_OUT','.template_row');
									$i++;
								}
					break;	
					case 5://document search
					
					$dbu ->query("SELECT member.logon,
								member.first_name,
								member.last_name, member.alias,
								computer.name AS computer_name,
								SUM(session_document.duration)AS duration,
								document.name AS document_name,
								session.date,
								CONCAT(member.first_name,' ',member.last_name) AS name
						FROM document
						INNER JOIN session_document ON session_document.document_id = document.document_id
						INNER JOIN session ON session.session_id = session_document.session_id
						INNER JOIN member ON member.member_id = session.member_id
						INNER JOIN computer2member ON computer2member.member_id = member.member_id
						INNER JOIN computer ON computer.computer_id =computer2member.computer_id
						WHERE 1=1 ".$userfilter_querypart." ".clean_filter($time_filter)." AND session_document.application_id = document.application_id AND document.name ".$filter."  
						GROUP BY member.member_id,document.name
						ORDER BY member.member_id ");
						$i=0;
							while($dbu->move_next()){
									$fts->assign(array(
										'NAME' 		=> $dbu->f('alias') == 1 ? decode_numericentity($dbu->f('name')) : decode_numericentity($dbu->f('logon')).' ('. decode_numericentity($dbu->f('computer_name')).')',
										'ITEM_NAME' => highlight($dbu->f('document_name'),$glob['search_key']),
										'COMPUTER'	=> $dbu->f('computer_name'),
										'DURATION_D'=> intval(($dbu->f('duration'))/(24*3600)),
										'DURATION_H'=> (intval($dbu->f('duration')/3600))%24,
										'DURATION_M'=> (intval($dbu->f('duration')/60))%60,
										'DURATION_S'=> intval($dbu->f('duration'))%60,
										'DATE' 		=> date('d/m/Y',$time),
										'FIRST'		=> $i==0? 'search_border':'',
										));
									
									if($i % 2 != 0 ){
										$fts->assign('EVEN','even');
									}else{
										$fts->assign('EVEN','');
									}
									$fts->parse('TEMPLATE_ROW_OUT','.template_row');
									$i++;
								}
					break;	
					case 6: //application search
						
						$dbu ->query("SELECT member.logon,
								member.first_name,
								member.last_name, member.alias,
								computer.name AS computer_name,
								session.date,
								SUM(session_application.duration)AS duration,
								application.description AS application_name,
								CONCAT(member.first_name,' ',member.last_name) AS name
								FROM session_application
								INNER JOIN application ON application.application_id = session_application.application_id
								INNER JOIN session ON session.session_id = session_application.session_id
								INNER JOIN member ON member.member_id = session.member_id
								INNER JOIN computer2member ON computer2member.member_id = member.member_id
								INNER JOIN computer ON computer.computer_id =computer2member.computer_id
						WHERE 1=1 ".$userfilter_querypart." ".clean_filter($time_filter)." AND session_application.application_id = application.application_id AND application.description ".$filter."
						 GROUP BY member.member_id,application.description
						ORDER BY member.member_id ");
						$i=0;
							while($dbu->move_next()){
									$fts->assign(array(
										'NAME' 		=> $dbu->f('alias') == 1 ? decode_numericentity($dbu->f('name')) : decode_numericentity($dbu->f('logon')).' ('. decode_numericentity($dbu->f('computer_name')).')',
										'ITEM_NAME' => highlight($dbu->f('application_name'),$glob['search_key']),
										'COMPUTER'	=> $dbu->f('computer_name'),
										'DURATION_D'=> (intval(($dbu->f('duration'))/(3600*24))),
										'DURATION_H'=> (intval($dbu->f('duration')/3600))%24,
										'DURATION_M'=> (intval($dbu->f('duration')/60))%60,
										'DURATION_S'=> intval($dbu->f('duration'))%60,
										'DATE' 		=> date('d/m/Y',$time),
										'FIRST'		=> $i==0? 'search_border':'',
										));
									
									if($i % 2 != 0 ){
										$fts->assign('EVEN','even');
									}else{
										$fts->assign('EVEN','');
									}
									$fts->parse('TEMPLATE_ROW_OUT','.template_row');
									$i++;
								}
					break;
				case 7://file search
					
					$dbu ->query("SELECT member.logon,
								member.first_name,
								member.last_name, member.alias,
								computer.name AS computer_name,
								file.path AS file_name,
								session_file.eventtime,
								CONCAT(member.first_name,' ',member.last_name) AS name
						FROM file
						INNER JOIN session_file ON session_file.file_id = file.file_id
						INNER JOIN session ON session.session_id = session_file.session_id
						INNER JOIN member ON member.member_id = session.member_id
						INNER JOIN computer2member ON computer2member.member_id = member.member_id
						INNER JOIN computer ON computer.computer_id =computer2member.computer_id
						WHERE 1=1 ".$userfilter_querypart." ".clean_filter($time_filter)."  AND SUBSTRING_INDEX(file.path, '&#092;&#092;' , -1)".$filter."  
						GROUP BY member.member_id,file.path
						ORDER BY member.member_id ");
	// SUBSTRING_INDEX(file.path, '&#092;&#092;' , -1) returns last substring after '//' which are encoded in the DB as '&#092;&#092;'
						$j=0;
							while($dbu->move_next()){
									$fts->assign(array(
										'NAME' 		=> $dbu->f('alias') == 1 ? decode_numericentity($dbu->f('name')) : decode_numericentity($dbu->f('logon')).' ('. decode_numericentity($dbu->f('computer_name')).')',
										'ITEM_NAME' => highlight_filename($dbu->f('file_name'),$glob['search_key']),
										'COMPUTER'	=> $dbu->f('computer_name'),
										'DURATION_D'=> 0,
										'DURATION_H'=> date('H',$dbu->f('eventtime')),
										'DURATION_M'=> date('i',$dbu->f('eventtime')),
										'DURATION_S'=> 0,
										'DATE' 		=> date('d/m/Y',$time),
										'BORDER'	=> $j==0? ' border-top: 1px solid #D7D7D7; ':'',
										));
									
									$fts->parse('TEMPLATE_ROW_OUT','.template_row');
									$j++;
								}
					break;
					case 8: //window search
						
						$dbu ->query("SELECT member.logon,
								member.first_name,
								member.last_name, member.alias,
								computer.name AS computer_name,
								session.date,
								SUM(session_window.duration)AS duration,
								window.name AS window_name,
								CONCAT(member.first_name,' ',member.last_name) AS name
								FROM session_window
								INNER JOIN window ON window.window_id = session_window.window_id
								INNER JOIN session ON session.session_id = session_window.session_id
								INNER JOIN member ON member.member_id = session.member_id
								INNER JOIN computer2member ON computer2member.member_id = member.member_id
								INNER JOIN computer ON computer.computer_id =computer2member.computer_id
						WHERE 1=1 ".$userfilter_querypart." ".clean_filter($time_filter)." AND session_window.window_id = window.window_id AND window.name ".$filter."
						 GROUP BY member.member_id,window.name
						ORDER BY member.member_id ");
						$i=0;
							while($dbu->move_next()){
									$fts->assign(array(
										'NAME' 		=> $dbu->f('alias') == 1 ? decode_numericentity($dbu->f('name')) : decode_numericentity($dbu->f('logon')).' ('. decode_numericentity($dbu->f('computer_name')).')',
										'ITEM_NAME' => highlight($dbu->f('window_name'),$glob['search_key']),
										'COMPUTER'	=> $dbu->f('computer_name'),
										'DURATION_D'=> (intval(($dbu->f('duration'))/(3600*24))),
										'DURATION_H'=> (intval($dbu->f('duration')/3600))%24,
										'DURATION_M'=> (intval($dbu->f('duration')/60))%60,
										'DURATION_S'=> intval($dbu->f('duration'))%60,
										'DATE' 		=> date('d/m/Y',$time),
										'FIRST'		=> $i==0? 'search_border':'',
										));
									
									if($i % 2 != 0 ){
										$fts->assign('EVEN','even');
									}else{
										$fts->assign('EVEN','');
									}
									$fts->parse('TEMPLATE_ROW_OUT','.template_row');
									$i++;
								}
					break;
				case 9://print search
					
					$dbu ->query("SELECT member.logon,
								member.first_name,
								member.last_name, member.alias,
								computer.name AS computer_name,
								fileprint.path AS file_name,
								session_print.eventtime,
								CONCAT(member.first_name,' ',member.last_name) AS name
						FROM fileprint
						INNER JOIN session_print ON session_print.file_id = fileprint.file_id
						INNER JOIN session ON session.session_id = session_print.session_id
						INNER JOIN member ON member.member_id = session.member_id
						INNER JOIN computer2member ON computer2member.member_id = member.member_id
						INNER JOIN computer ON computer.computer_id =computer2member.computer_id
						WHERE 1=1 ".$userfilter_querypart." ".clean_filter($time_filter)."  AND SUBSTRING_INDEX(fileprint.path, '&#092;&#092;' , -1)".$filter."  
						GROUP BY member.member_id,fileprint.path
						ORDER BY member.member_id ");
						$j=0;
							while($dbu->move_next()){
									$fts->assign(array(
										'NAME' 		=> $dbu->f('alias') == 1 ? decode_numericentity($dbu->f('name')) : decode_numericentity($dbu->f('logon')).' ('. decode_numericentity($dbu->f('computer_name')).')',
										'ITEM_NAME' => highlight_filename($dbu->f('file_name'),$glob['search_key']),
										'COMPUTER'	=> $dbu->f('computer_name'),
										'DURATION_D'=> 0,
										'DURATION_H'=> date('H',$dbu->f('eventtime')),
										'DURATION_M'=> date('i',$dbu->f('eventtime')),
										'DURATION_S'=> 0,
										'DATE' 		=> date('d/m/Y',$time),
										'BORDER'	=> $j==0? ' border-top: 1px solid #D7D7D7; ':'',
										));
									
									$fts->parse('TEMPLATE_ROW_OUT','.template_row');
									$j++;
								}
					break;
		}
}


$dates = $dbu->row("SELECT MIN(date) AS genesis,MAX(date) AS last_day_on_earth FROM session ");

global $bottom_includes;
$bottom_includes .= '<script type="text/javascript">flobn.register("genesis",new Date("'.date('n/j/Y',$dates['genesis']).'"));</script><script type="text/javascript" src="'.CURRENT_VERSION_FOLDER.'ui/universalsearch-ui.js"></script>';

$fts->parse('CONTENT','main');
return $fts->fetch('CONTENT');