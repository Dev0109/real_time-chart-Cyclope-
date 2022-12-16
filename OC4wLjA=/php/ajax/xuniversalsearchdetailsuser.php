<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/


$fts=new ft(ADMIN_PATH.MODULE."templates/");
$fts->define(array('main' => $glob['pag'].'.html'));
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
		switch ($searchtype){
						case 1://member search

							$dbu->query("SELECT DISTINCT member.logon,
											member.first_name,
											member.last_name, member.alias,
											CONCAT(member.first_name,' ',member.last_name) AS name,
											computer2member.last_record,
											computer.name AS computer_name
								FROM member
								INNER JOIN session ON member.member_id = session.member_id 
								INNER JOIN computer2member ON computer2member.member_id = member.member_id
								INNER JOIN computer ON computer.computer_id =computer2member.computer_id
								WHERE 	((member.logon ".$filter.") 
										OR (member.first_name ".$filter.") 
										OR (member.last_name ".$filter."))"
										.$time_filter);
										
											
											$i=0;
									while($dbu->move_next()){
										$fts->assign(array(
											'NAME' => $dbu->f('alias') == 1 ? highlight(decode_numericentity($dbu->f('name')), $glob['search_key']) : highlight(decode_numericentity($dbu->f('logon')),$glob['search_key']).' ('. decode_numericentity($dbu->f('computer_name')).')',
											'COMPUTER_NAME'=> $dbu->f('computer_name'),
											'DATE' => date('d/m/Y',$time),
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
						case 2://computer search
							
							$dbu->query("SELECT DISTINCT computer.name AS computer_name,
											computer2member.last_record 
								FROM computer 
								INNER JOIN session ON computer.computer_id = session.computer_id
								INNER JOIN computer2member ON computer2member.computer_id = computer.computer_id
								WHERE computer.name ".$filter
								.$time_filter);
							
										$i=0;
								while($dbu->move_next()){
										$fts->assign(array(
											'NAME' => highlight($dbu->f('computer_name'),$glob['search_key']), 
											'DATE' => date('d/m/Y',$time),
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
						
			}
}



global $bottom_includes;
$fts->parse('CONTENT','main');
return $fts->fetch('CONTENT');