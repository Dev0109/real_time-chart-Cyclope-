<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => $glob['pag'].".html"));


if(!SMTP_SERVER)
{
	header('Location: index.php?pag=settings#emailsettings');
}

$dbu = new mysql_db();

if($glob['sequencegrp_id'])
{
	$ft->assign(array(
		'PAGE_TITLE' => $ft->lookup('Edit Sequence Set'),
		'ACT' => 'emailsequence-update',
		'PAG' => 'emailsequence',
		'MESSAGE' => get_error($glob['error']),
		
		'EMAIL_REPORT_ID' => $glob['sequencegrp_id'],
		'FOR' => include(CURRENT_VERSION_FOLDER.'php/pag/emailsequencefor.php'),
		'FREQUENCY' => include(CURRENT_VERSION_FOLDER.'php/pag/emailsequencefrequency.php'),
		// 'SENDTO' => include(CURRENT_VERSION_FOLDER.'php/pag/emailsequencesendto.php'),
		// 'SENDFROM' => include(CURRENT_VERSION_FOLDER.'php/pag/emailsequencesendfrom.php'),
		// 'DETAILS' => include(CURRENT_VERSION_FOLDER.'php/pag/emailsequencedetails.php'),
		'SAVE' => include(CURRENT_VERSION_FOLDER.'php/pag/emailsequencesave.php'),
		
	));
}
else 
{
		$ft->assign(array(
		'PAGE_TITLE' => $ft->lookup('Add Sequence Set'),
		'FOR' => include(CURRENT_VERSION_FOLDER.'php/pag/emailsequencefor.php'),
		'FREQUENCY' => include(CURRENT_VERSION_FOLDER.'php/pag/emailsequencefrequency.php'),
		// 'SENDTO' => include(CURRENT_VERSION_FOLDER.'php/pag/emailsequencesendto.php'),
		// 'SENDFROM' => include(CURRENT_VERSION_FOLDER.'php/pag/emailsequencesendfrom.php'),
		// 'DETAILS' => include(CURRENT_VERSION_FOLDER.'php/pag/emailsequencedetails.php'),
		'SAVE' => include(CURRENT_VERSION_FOLDER.'php/pag/emailsequencesave.php'),
		
		'ACT' => 'emailsequence-add',
		'PAG' => 'emailsequence',
		'MESSAGE' => get_error($glob['error']),
	));
}

global $bottom_includes;
$bottom_includes.= '<script type="text/javascript" src="ui/emailsequence-ui.js"></script>';

$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');
?>