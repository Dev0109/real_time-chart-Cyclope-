<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => $glob['pag'].".html"));

// if(!SMTP_SERVER)
// {
	// var_dump("start");
	header('Location: index.php?pag=settings#emailsettings');
// }

$dbu = new mysql_db();

if($glob['email_report_id'])
{
	
	$ft->assign(array(
		'PAGE_TITLE' => $ft->lookup('Edit Reports Set'),
		'ACT' => 'emailreports-update',
		'PAG' => 'emailreport',
		'MESSAGE' => get_error($glob['error']),
		
		'EMAIL_REPORT_ID' => $glob['email_report_id'],
		'SELECTREPORTS' => include(CURRENT_VERSION_FOLDER.'php/pag/emailreportsselect.php'),
		'FREQUENCY' => include(CURRENT_VERSION_FOLDER.'php/pag/emailreportsfrequency.php'),
		'FOR' => include(CURRENT_VERSION_FOLDER.'php/pag/emailreportsfor.php'),
		'SENDTO' => include(CURRENT_VERSION_FOLDER.'php/pag/emailreportssendto.php'),
		'SENDFROM' => include(CURRENT_VERSION_FOLDER.'php/pag/emailreportssendfrom.php'),
		'DETAILS' => include(CURRENT_VERSION_FOLDER.'php/pag/emailreportsdetails.php'),
		'SAVE' => include(CURRENT_VERSION_FOLDER.'php/pag/emailreportssave.php'),
		
	));
}
else 
{
		$ft->assign(array(
		'PAGE_TITLE' => $ft->lookup('Add Reports Set'),
		'SELECTREPORTS' => include(CURRENT_VERSION_FOLDER.'php/pag/emailreportsselect.php'),
		'FOR' => include(CURRENT_VERSION_FOLDER.'php/pag/emailreportsfor.php'),
		'FREQUENCY' => include(CURRENT_VERSION_FOLDER.'php/pag/emailreportsfrequency.php'),
		'SENDTO' => include(CURRENT_VERSION_FOLDER.'php/pag/emailreportssendto.php'),
		'SENDFROM' => include(CURRENT_VERSION_FOLDER.'php/pag/emailreportssendfrom.php'),
		'DETAILS' => include(CURRENT_VERSION_FOLDER.'php/pag/emailreportsdetails.php'),
		'SAVE' => include(CURRENT_VERSION_FOLDER.'php/pag/emailreportssave.php'),
		
		'ACT' => 'emailreports-add',
		'PAG' => 'emailreport',
		'MESSAGE' => get_error($glob['error']),
	));
}
// var_dump("test");
// die();
global $bottom_includes;
$bottom_includes.= '<script type="text/javascript" src="ui/emailreport-ui.js"></script>';

$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');
?>