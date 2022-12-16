<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/

$ftinst=new ft(ADMIN_PATH.MODULE."templates/");
$ftinst->define(array('main' => 'xmonitored.html'));
$ftinst->define_dynamic('template_row','main');

$filter = '';
if(isset($glob['q'])){
	$glob['q'] = filter_var($glob['q'],FILTER_SANITIZE_STRING);
	$filter = " AND ((member.logon LIKE '%".$glob['q']."%') OR (member.first_name LIKE '%".$glob['q']."%') OR (member.last_name LIKE '%".$glob['q']."%'))";
}

//	lorand
$sortable_columns = array(
	'last_record',
	'member.logon',
	);

$sortcolumns = get_sorting($sortable_columns,'','desc',1);

$ftinst->assign(array(
	'ANCHOR_INNER_0' => render_anchor_inner(0),
	'ANCHOR_INNER_1' => render_anchor_inner(1),
	'DEBUGMESSAGE' => '',
	// 'DEBUGMESSAGE' => print_r($_POST,1),
	// 'DEBUGMESSAGE' => basename($_SERVER['PHP_SELF']),
	// 'DEBUGMESSAGE' => $sortcolumns,
));
//END

$dbu = new mysql_db();
$dbufield = new mysql_db();
$dbu->query("SELECT member.*,
computer.name AS computer_name,
computer.ip,
computer.clientversion,
computer.connectivity,
computer.computer_id,
count(distinct computer2member.computer_id) as membernumber,
computer2member.last_record
FROM member 
INNER JOIN computer2member ON computer2member.member_id = member.member_id
INNER JOIN computer ON computer.computer_id = computer2member.computer_id
WHERE member.active != 3 AND member.department_id != 0 ".$filter."
GROUP BY member.member_id,computer.computer_id 
" . $sortcolumns . " " );

while($dbu->move_next()){
	$version = str_replace(', ','.',$dbu->f('clientversion'));
	$date = date('Y-m-d', $dbu->f('last_record'));
	$today = date('Y-m-d');
	if (time() - $dbu->f('last_record') < $dbu->f('connectivity')*60*2) {
		$monitoredstatus = 'connect';
	} elseif ($date == $today) {
		$monitoredstatus = 'partialconnect';
	} else {
		$monitoredstatus = 'disconnect';
	}
	$ftinst->assign(array(
		'COMPUTER_ID' => $dbu->f('computer_id'),
		'MEMBER_ID' => $dbu->f('member_id'),
		'MEMBER_NR' => $dbufield->field('SELECT count( * ) AS member_nr FROM `computer2member` WHERE `member_id` = ' . $dbu->f('member_id')),
		'MEMBER_COMPS' => $dbufield->field("SELECT  GROUP_CONCAT(computer.name ORDER BY computer.name DESC SEPARATOR ',') FROM `computer2member` INNER JOIN computer ON computer.computer_id = computer2member.computer_id WHERE `member_id` = " . $dbu->f('member_id')),
		'NAME' => trialEncrypt($dbu->f('alias') == 1 ?  $dbu->f('first_name').' '.$dbu->f('last_name') :$dbu->f('logon')),
		'ALIAS' => '<a href="index.php?pag=member&prefilled=1&alias=1&membername=' . $dbu->f('logon') . '&mid=' . $dbu->f('member_id') . '">' . $ftinst->lookup("Rename") . '</a>',
		'MACHINE' => trialEncrypt($dbu->f('computer_name'),'comp'),
		'IP' => trialEncrypt($dbu->f('ip'),'ip'),
		'LAST_RECORD' => $dbu->f('last_record') ? date('d/m/Y g:i A',$dbu->f('last_record')) : 'N/A',
		'CLIENT_VERSION' => $version ? $version : 'N/A',
		'MONITORED' => $monitoredstatus,
	));
	if($i % 2 != 0 ){
		$ftinst->assign('EVEN','even');
	}else{
		$ftinst->assign('EVEN','');
	}
	
	$ftinst->parse('TEMPLATE_ROW_OUT','.template_row');
	$i++;
}
$ftinst->parse('CONTENT','main');
return $ftinst->fetch('CONTENT');