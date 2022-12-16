<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftappusage=new ft(ADMIN_PATH.MODULE."templates/");
$ftappusage->define(array('main' => "xsoftwarealerts.html"));
$ftappusage->define_dynamic('template_row','main');

$l_r = ROW_PER_PAGE;

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
        $ftappusage->assign('OFFSET',$glob['offset']);
}

/*switch ($glob['t']){
		case 'session':
			$pieces = explode('-',$glob['f']);
			$pieces[0] = substr($pieces[0],1);
			if(count($pieces) == 1 && is_numeric(current($pieces))){
				//we are filtering by department so we go and say
				$nodeId = current($pieces);
				//get the main attraction
				$dbu = new mysql_db();
				$nodeInfo = $dbu->row("SELECT lft,rgt FROM department WHERE department_id = ?",$nodeId);
				$nodes = array($nodeId);
				$query = $dbu->query("SELECT department_id FROM department WHERE lft BETWEEN ".$nodeInfo['lft'].' AND '.$nodeInfo['rgt']);
				while ($query->next()){
					array_push($nodes,$query->f('department_id'));					
				}
				
				$filter .= ' AND member.department_id IN ('.join(',',$nodes).')';
			}
			if(count($pieces) == 3){
				//then we have PC and user
			
				$filter = ' AND member.member_id = '.end($pieces).' 
				AND application_inventory.computer_id = '.prev($pieces).' 
				AND member.department_id = '.reset($pieces);
			}
			break;
		case 'user':			
			break;
		case 'computer':
			break;
	}
	
$_SESSION['filters']['t'] = $glob['t'];
$_SESSION['filters']['f'] = $glob['f'];
	
if(!empty($glob['time'])){
		$matches = array();
		preg_match_all('!([0-9]{1,2}/[0-9]{1,2}/[0-9]{4})( [0-9]+\:[0-9]+ [AM|PM]+)?!',$glob['time']['time'],$matches);
		$pieces = array_shift($matches);
		switch (count($pieces)){
			case 1:
				
				$time = strtotime(current($pieces));
				
				$start_time = mktime(0,0,0,date('n',$time),date('d',$time),date('Y',$time));
				$end_time = mktime(23,59,59,date('n',$time),date('d',$time),date('Y',$time));
				
				$filter .= ' AND arrival_date BETWEEN '.$start_time.' AND '.$end_time;
				break;
			case 2:
				
				$start_time = strtotime(reset($pieces));
				$end_time = strtotime(end($pieces));
				
				if($glob['time']['type'] > 1){
					$start_time = mktime(date('G', $start_time),date('i', $start_time),date('s', $start_time),date('n',$start_time),date('d',$start_time),date('Y',$start_time));
					$end_time = mktime(date('G', $start_time),date('i', $start_time),date('s', $start_time),date('n',$end_time),date('d',$end_time),date('Y',$end_time));
				}
				else 
				{	
					$start_time = mktime(0,0,0,date('n',$start_time),date('d',$start_time),date('Y',$start_time));
					$end_time = mktime(23,59,59,date('n',$end_time),date('d',$end_time),date('Y',$end_time));
				}
			
				
				$filter .= ' AND arrival_date BETWEEN '.$start_time.' AND '.$end_time;
				break;
		}
	}

$_SESSION['filters']['time'] = $glob['time'];*/

$filter = '';

$pieces = explode('-',$glob['f']);
$filterCount = count($pieces);

list($department_id,$computer_id,$member_id) = $pieces;

$department_id = substr($department_id,1);

if($glob['t'] == 'users'){
	$member_id = $computer_id;		
}

if($filterCount == 1){
	
	$nodeInfo = $dbu->row("SELECT lft,rgt FROM department WHERE department_id = ?",$department_id);
	
	$nodes = array();
	
	$query = $dbu->query("SELECT department_id FROM department WHERE lft BETWEEN ".$nodeInfo['lft'].' AND '.$nodeInfo['rgt']);
	
	while ($query->next())
	{
		array_push($nodes,$query->f('department_id'));					
	}
	
	$member_list = array();
	
	switch ($_SESSION[ACCESS_LEVEL])
	{
		case MANAGER_LEVEL:
		// case LIMITED_LEVEL:
		case DPO_LEVEL:
			
			$members = $dbu->query("SELECT member_id FROM member2manage WHERE manager_id = ?",$_SESSION[U_ID]);
			
			while ($members->next())
			{
				array_push($member_list,$members->f('member_id'));
			}
				
			break;
			
		case EMPLOYEE_LEVEL:
			$member_list = array($_SESSION[U_ID]);
			
			break;
	}
	switch ($glob['t'])
	{
		case 'session':
		case 'users':
			
			$filter = ' AND member.department_id IN ('.join(',',$nodes).')';	
			break;
		case 'computers':
			
			$filter = ' AND computer.department_id IN ('.join(',',$nodes).')';
			break;
	}
	if(!empty($member_list))
	{
		$filter .= ' AND member.member_id IN ('.join(',',$member_list).')';	
	}
}else{
	
	switch ($glob['t']){
		case 'session':
			$filter = ' AND member.member_id = '.$member_id.' 
						AND application_inventory.computer_id = '.$computer_id.'
						AND member.department_id = '.$department_id;
			
			break;
			
		case 'users':
						
			$filter = ' AND member.member_id = '.$member_id.' 
						AND member.department_id = '.$department_id;
			
			break;
			
		case 'computers':

			$filter = ' AND application_inventory.computer_id = '.$computer_id.' 
						AND computer.department_id = '.$department_id;
			
			break;
	}
}

$_SESSION['filters']['t'] = $glob['t'];
$_SESSION['filters']['f'] = $glob['f'];

if(!empty($glob['time']))
{
	
	$matches = array();
	preg_match_all('!([0-9]{1,2}/[0-9]{1,2}/[0-9]{4})( [0-9]+\:[0-9]+ [AM|PM]+)?!',$glob['time']['time'],$matches);
	$pieces = array_shift($matches);
	$days = array();
	switch (count($pieces)){
		case 1:
			//echo current($pieces);
			$time = strtotime(current($pieces));
				
			$start_time = mktime(0,0,0,date('n',$time),date('d',$time),date('Y',$time));
			$end_time = mktime(23,59,59,date('n',$time),date('d',$time),date('Y',$time));
			$days = array(date('w',$time));	
			$filter .= ' AND arrival_date BETWEEN '.$start_time.' AND '.$end_time;
			
			break;
			
		case 2:
			$start_time = strtotime(reset($pieces));
			$start_hour = date('G',$start_time);
			$start_time = mktime(0,0,0,date('n',$start_time),date('d',$start_time),date('Y',$start_time));
			//---
			$end_time = strtotime(end($pieces));
			$end_hour = date('G',$end_time);
			$end_time = mktime(0,0,0,date('n',$end_time),date('d',$end_time),date('Y',$end_time));
			//interval see how manny days we have here
			//if more then 7 then we have all the days
			if($end_time-$start_time >= (86400*7)){
				$days = array(0,1,2,3,4,5,6);//all the days
			}else{
				//check which one is bigger
				$start = $sday = date('w',$start_time);
				$end = $eday = date('w',$end_time);
				//if the last day is smaller then the first day then go backwards
				if($sday >= $eday){
					$start = $eday;
					$end = $sday;
				}
				for ($i = $start; $i <= $end;$i++){
					$days[] = $i;
				}
			}
			$filter .= ' AND ( arrival_date >= '.$start_time.' AND arrival_date <= '.$end_time.')';
			
			break;
	}
	
	switch ($glob['time']['type']){			
		case 2://specific time
			$filter.= ' AND (hour BETWEEN '.$start_hour.' AND '.$end_hour.')';
			break;
		case 3://work time
			//for worktime we can haz some interesting query
			$worktimes = get_workschedule($department_id,$days, 1);
			$time_filter = '';
			foreach ($worktimes as $day => $hours){
				$time_filter .='(application_inventory.day = '.$day.' AND hour >= '.$hours['start_hour'].' AND hour < '.$hours['end_hour'].') OR ';
			}
			$time_filter = rtrim($time_filter,' OR ');
			$filter .= ' AND ('.$time_filter.')';
			break;
		case 4://overtime
			$worktimes = get_workschedule($department_id,$days, 1);
			$time_filter = '';
			foreach ($worktimes as $day => $hours){
				$time_filter .='(application_inventory.day = '.$day.' AND NOT(hour >= '.$hours['start_hour'].' AND hour < '.$hours['end_hour'].')) OR ';
			}
			$time_filter = rtrim($time_filter,' OR ');
			$filter .= ' AND ('.$time_filter.')';
			break;
			break;
		case 1://show all/default
		default:
			break;
	}
	$_SESSION['filters']['time'] = $glob['time'];
}



$dbu->query("SELECT 
member.logon,
member.member_id,
member.alias,
CONCAT(member.first_name,' ',member.last_name) AS member_name,
computer.name,
computer.ip,
application.description,
application_version.version,
application_path.path,
application_inventory.arrival_date
FROM application_inventory 
INNER JOIN application ON application.application_id = application_inventory.application_id
INNER JOIN computer ON computer.computer_id = application_inventory.computer_id
INNER JOIN member ON member.member_id = application_inventory.member_id
INNER JOIN application_version ON application_inventory.application_version_id = application_version.application_version_id
INNER JOIN application_path ON application_path.application_path_id = application_inventory.application_path_id
WHERE 1=1 ".$filter."
ORDER BY member.logon, application.description ASC");

/*$max_rows=$dbu->records_count();
$dbu->move_to($offset*$l_r);*/
$i = 0;
$prev = 0;
while ($dbu->move_next()/* && $i<$l_r*/){	
	if(!$prev)
	{
		$prev= $dbu->f('member_id');
		$ftappusage->assign('TOP_BORDER','');
	}
	else
	{
		
		if ($dbu->f('member_id') != $prev)
		{
			$prev = $dbu->f('member_id');
			$ftappusage->assign('TOP_BORDER','top_border');
		}
		else 
		{
			$ftappusage->assign('TOP_BORDER','');
		}	
	}
	
	$ftappusage->assign(array(
		'USER' => $dbu->f('alias') == 1 ? $dbu->f('member_name') : $dbu->f('logon'),
		'DATE' => date('m/d/Y h:i:s A', $dbu->f('arrival_date')),
		'COMPUTER' => $dbu->f('name'),
		'IP' => $dbu->f('ip'),
		'APPLICATION' => str_replace('||','\\',$dbu->f('description')),
		'VERSION' => $dbu->f('version'),
		'PATH' => str_replace('||','\\',$dbu->f('path')),
	));
	
	if(($i % 2)==0 )
	{
		$ftappusage->assign('CLASS','even');
	}
	else
	{
		$ftappusage->assign('CLASS','');
	}
	
	$ftappusage->parse('TEMPLATE_ROW_OUT','.template_row');
	$i++;
}

/*$start = $offset;
$end = ceil($max_rows/$l_r);
$link = '';
if($end<=5){
	//if there are less then 5 pages then we go about building a normal pagination
	for ($i = 0; $i < $end; $i++){
		$page = $i+1;	
		$class = $page == $start+1 ? 'class="current"' : '';
		$link .= <<<HTML
		<li {$class}><a href="index.php?pag={$glob['pag']}&offset={$i}{$arguments}#alerts">{$page}</a></li>
HTML;
	}
}else{
	if($start == 0 || $start <3){
		for ($i = 0; $i < 5; $i++){
			$page = $i+1;	
			$class = $page == $start+1 ? 'class="current"' : '';
			$link .= <<<HTML
			<li><a href="index.php?pag={$glob['pag']}&offset={$i}#alerts" {$class}>{$page}</a></li>
HTML;
		}
	}elseif ($start+2 >= $end-1){
		//we are close to the end
		for ($i = $end-5; $i < $end; $i++){
			$page = $i+1;	
			$class = $page == $start+1 ? 'class="current"' : '';
			$link .= <<<HTML
			<li><a href="index.php?pag={$glob['pag']}&offset={$i}#alerts" {$class}>{$page}</a></li>
HTML;
		}
	}else{
		for ($i = $start-2; $i < $start; $i++){
			$page = $i+1;	
			$link .= <<<HTML
			<li><a href="index.php?pag={$glob['pag']}&offset={$i}#alerts">{$page}</a></li>
HTML;
		}
		$page = $start+1;
		$class = $page == $start+1 ? 'class="current"' : '';
		$link .= <<<HTML
		<li><a href="index.php?pag={$glob['pag']}&offset={$start}" {$class}>{$page}</a></li>
HTML;
		for ($i = $start+1; $i < $start+3; $i++){
			$page = $i+1;	
			$link .= <<<HTML
			<li><a href="index.php?pag={$glob['pag']}&offset={$i}#alerts">{$page}</a></li>
HTML;
		}
	}
}
$ftappusage->assign(array(
	'PAGG' => $link,
));

if($offset > 0)
{
     $ftappusage->assign('BACKLINK',"index.php?pag=".$glob['pag']."&offset=".($offset-1).$arguments."#alerts");
}
else
{
     $ftappusage->assign('BACKLINK','#'); 
}
if($offset < $end-1)
{
     $ftappusage->assign('NEXTLINK',"index.php?pag=".$glob['pag']."&offset=".($offset+1).$arguments."#alerts");
}
else
{
     $ftappusage->assign('NEXTLINK','#');
}
$ftappusage->assign('LAST_LINK',"index.php?pag=".$glob['pag']."&offset=".($end-1).$arguments."#alerts");*/

if(!$dbu->records_count())
{
	return get_error($ft->lookup('No data to display for your current filters'),'warning');
}
$ftappusage->parse('CONTENT','main');
return $ftappusage->fetch('CONTENT');