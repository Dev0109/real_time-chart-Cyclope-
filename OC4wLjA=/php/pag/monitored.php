<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/

$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => $glob['pag'].'.html'));
$session_website = get_session_website_table();

$hidemodal = $_SESSION['hidemodal'];
$membercount =  $dbu->field("SELECT count( member_id ) FROM `member` WHERE 1");
if ($hidemodal != 1 && $membercount < 2){
	$_SESSION['hidemodal'] = 1;
	$ft->assign(array(
		'HIDEMODAL' => 'showmodal'
	));
}
$ft->assign(array(
	'MONITORED_USERS' => include_once(CURRENT_VERSION_FOLDER.'php/ajax/xmonitored.php'),
	'LANG' => strtolower(LANG),
));
$folder = CURRENT_VERSION_FOLDER.'help/'.strtolower(LANG).'/'; 
if(!is_dir($folder)){
		$ft->assign(array(
			'LANG' => 'en',
		));
}

global $bottom_includes;
$bottom_includes='<script type="text/javascript" src="ui/monitored-ui.js"></script>';

	
	$trial = $dbu->field("SELECT value FROM `settings` WHERE `constant_name` = 'TRUENC'");
	$ecrypted_text = $dbu->field("SELECT message FROM `notification` WHERE `constant_name` = 'ENCRYPTED_TEXT'");
	if ($trial != 2236985){
		$ft->assign('ENCRYPTMESSAGE', '<div class="encryptmessage">' . $ft->lookup($ecrypted_text) . '</div>');
	}

$ft->assign('PAGE_TITLE',$ft->lookup('Monitored Employees'));
$ft->assign('MESSAGE',$glob['error']);

$total_computers =  $dbu->field("SELECT count(computer_id) FROM computer");

if(!$glob['error'] && ( AC < $total_computers))
{
	$ft->assign('MESSAGE',get_error($ft->lookup('You have exceeded the total of ').AC.$ft->lookup(' computers included in your license by ').($total_computers - AC).$ft->lookup('. In order to be able to monitor the exceeding users please consider upgrading your license, or delete some of the other entries.'),'error'));
}

$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');