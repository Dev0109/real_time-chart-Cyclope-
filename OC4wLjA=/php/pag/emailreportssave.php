<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftm=new ft(ADMIN_PATH.MODULE."templates/");
$ftm->define(array('main' => "emailreportssave.html"));

$dbu = new mysql_db();

if($glob['email_report_id'])
{
	$dbu->query("SELECT * FROM email_report WHERE email_report_id='".$glob['email_report_id']."'");
	$dbu->move_next();
	
	$ftm->assign(array(
			'NAME' => $dbu->f('name'),
			'DESCRIPTION' => $dbu->f('description'),
	));
}
else 
{
	$ftm->assign(array(
			'NAME' => $glob['name'],
			'DESCRIPTION' => $glob['description'],
	));
}

$ftm->parse('CONTENT','main');
return $ftm->fetch('CONTENT');
?>