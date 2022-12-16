<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftx=new ft(ADMIN_PATH.MODULE."templates/");
$ftx->define(array('main' => "xsoftwareinventory.html"));
$ftx->define_dynamic('template_row','main');

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
        $ftx->assign('OFFSET',$glob['offset']);
}

$pieces = explode('-',$glob['f']);
$filterCount = count($pieces);

list($department_id,$computer_id,$member_id) = $pieces;

$department_id = substr($department_id,1);

if($glob['t'] == 'users'){
	$member_id = $computer_id;		
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
						AND inventory.computer_id = '.$computer_id.'
						AND member.department_id = '.$department_id;
			
			break;
			
		case 'users':
						
			$filter = ' AND member.member_id = '.$member_id.' 
						AND member.department_id = '.$department_id;
			
			break;
			
		case 'computers':

			$filter = ' AND inventory.computer_id = '.$computer_id.' 
						AND computer.department_id = '.$department_id;
			
			break;
	}
}

$_SESSION['filters']['t'] = $glob['t'];
$_SESSION['filters']['f'] = $glob['f'];

$dbu->query("SELECT 
member.logon,
member.alias,
CONCAT(member.first_name,' ',member.last_name) as member_name,
member.member_id,
computer.name,
computer.ip,
inventory.last_updated,
inventory.comptype,
inventory.os,
inventory.cpu,
inventory.ram,
inventory.mboard,
inventory.mboardmodel,
inventory.hdd,
inventory.hddsize,
inventory.video,
inventory.videosize,
inventory.software,
inventory.monitor
FROM inventory 
INNER JOIN computer ON computer.computer_id = inventory.computer_id
INNER JOIN member ON member.member_id = inventory.member_id
WHERE 1=1 ".$filter." ".$number_of_rows);

/*$max_rows=$dbu->records_count();
$dbu->move_to($offset*$l_r);*/
$i = 0;
$prev = 0;
while ($dbu->move_next() /*&& $i<$l_r*/){	
	
	if(!$prev)
	{
		$prev= $dbu->f('member_id');
		$ftx->assign('TOP_BORDER','');
	}
	else
	{
		
		if ($dbu->f('member_id') != $prev)
		{
			$prev = $dbu->f('member_id');
			$ftx->assign('TOP_BORDER','top_border');
		}
		else 
		{
			$ftx->assign('TOP_BORDER','');
		}	
	}
	$soft = unserialize($dbu->f('software'));
	if (is_array($soft)){
		$appname = array();
		foreach ($soft as $key => $row)
		{
			$rowname = base64_decode($row['name']);
			$rowpublisher = base64_decode($row['publisher']);
			$rowinstall = base64_decode($row['install']);
			$appname[$rowname]['name'] = $rowname;
			$appname[$rowname]['publisher'] = $rowpublisher;
			$appname[$rowname]['install'] = $rowinstall;
		}
		sort($appname);
		$apptable = '';
		foreach($appname as $applicationrow) {
			$applicationrowname = $applicationrow['name'];
			$applicationrowpublisher = $applicationrow['publisher'];
			$applicationrowinstall = $applicationrow['install'];
			$apptable .= '
					<tr>
					  <td>'.$applicationrowname.'</td>
					  <td>'.$applicationrowpublisher.'</td>
					  <td>'.(is_numeric($applicationrowinstall)?date("Y-m-d",strtotime($applicationrowinstall)):$applicationrowinstall).'</td>
					</tr>';
		}
	} else {
		
				$apptable = '
						<tr>
						  <td>-</td>
						  <td>-</td>
						  <td>-</td>
						</tr>';
	}
	$ftx->assign(array(
		'USER' => trialEncrypt($dbu->f('alias') == 1 ? $dbu->f('member_name') : $dbu->f('logon')),
		'IP' => trialEncrypt($dbu->f('ip'),'ip'),
		'COMPUTER' => trialEncrypt($dbu->f('name'),'comp'),
		'LAST_UPDATE' => date('Y-m-d H:i:s',$dbu->f('last_updated')),
		'SOFTWARE_INFO' => $apptable,
		'USER_TYPE' => $dbu->f('comptype'),
		'USER_OS' => $dbu->f('os'),
		'USER_CPU' => $dbu->f('cpu'),
		'USER_RAM' => $dbu->f('ram'),
		'USER_MB' => $dbu->f('mboard') . ' - ' . $dbu->f('mboardmodel'),
		'USER_HDD' => $dbu->f('hdd') . ' - ' . $dbu->f('hddsize'),
		'USER_VIDEO' => $dbu->f('video') . ' - ' . $dbu->f('videosize'),
		'USER_MONITOR' => $dbu->f('monitor'),
	));
	
	if(($i % 2)==0 )
	{
		$ftx->assign('CLASS','even');
	}
	else
	{
		$ftx->assign('CLASS','');
	}
	$ftx->parse('TEMPLATE_ROW_OUT','.template_row');
	$i++;
}

$start = $offset;
$end = ceil($max_rows/$l_r);
$link = '';
if($end<=5){
	//if there are less then 5 pages then we go about building a normal pagination
	for ($i = 0; $i < $end; $i++){
		$page = $i+1;	
		$class = $page == $start+1 ? 'class="current"' : '';
		$link .= <<<HTML
		<li {$class}><a href="index.php?pag={$glob['pag']}&offset={$i}{$arguments}">{$page}</a></li>
HTML;
	}
}else{
	if($start == 0 || $start <3){
		for ($i = 0; $i < 5; $i++){
			$page = $i+1;	
			$class = $page == $start+1 ? 'class="current"' : '';
			$link .= <<<HTML
			<li><a href="index.php?pag={$glob['pag']}&offset={$i}" {$class}>{$page}</a></li>
HTML;
		}
	}elseif ($start+2 >= $end-1){
		//we are close to the end
		for ($i = $end-5; $i < $end; $i++){
			$page = $i+1;	
			$class = $page == $start+1 ? 'class="current"' : '';
			$link .= <<<HTML
			<li><a href="index.php?pag={$glob['pag']}&offset={$i}" {$class}>{$page}</a></li>
HTML;
		}
	}else{
		for ($i = $start-2; $i < $start; $i++){
			$page = $i+1;	
			$link .= <<<HTML
			<li><a href="index.php?pag={$glob['pag']}&offset={$i}">{$page}</a></li>
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
			<li><a href="index.php?pag={$glob['pag']}&offset={$i}">{$page}</a></li>
HTML;
		}
	}
}
$ftx->assign(array(
	'PAGG' => $link,
));

if($offset > 0)
{
     $ftx->assign('BACKLINK',"index.php?pag=".$glob['pag']."&offset=".($offset-1).$arguments);
}
else
{
     $ftx->assign('BACKLINK','#'); 
}
if($offset < $end-1)
{
     $ftx->assign('NEXTLINK',"index.php?pag=".$glob['pag']."&offset=".($offset+1).$arguments);
}
else
{
     $ftx->assign('NEXTLINK','#');
}
$ftx->assign('LAST_LINK',"index.php?pag=".$glob['pag']."&offset=".($end-1).$arguments);

if(!$dbu->records_count())
{
	$ftx->assign(array(
		'NO_DATA_MESSAGE' => get_error($ftx->lookup('No data to display for your current filters'),'warning'),
		'HIDE_CONTENT'	=> 'hide',
	));
}

$export_header = get_export_header($glob['f']);
extract($export_header,EXTR_OVERWRITE);
$glob['append'] = trialEncrypt($member_name);

	
	//	modified for pdf	--->
	$export_header = get_export_header($_SESSION['filters']['f']);
	extract($export_header,EXTR_OVERWRITE);
	$ftx->assign(array(
		'PDF_HEADER' => pdf_header(),
		'PDF_HIDE' => pdf_hide(),
		'PDF_CLASS' => pdf_class(),
		'TITLE' => $ftx->lookup('Software Inventory'),
		'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
		'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
	));
	//	<---	modified for pdf

$ftx->parse('CONTENT','main');
	//	modified for pdf	--->
	if($_REQUEST['render'] == 'pdf'){
		$page = 'softwareinventory';
		$html = $ftx->fetch('CONTENT');
			file_put_contents(CURRENT_VERSION_FOLDER.'temp_pdf/'.$page.'.html',"\xEF\xBB\xBF" . $html);
		loadPDF($page,'inline');exit;
	} else {
		return $ftx->fetch('CONTENT');
	}
	//	<---	modified for pdf