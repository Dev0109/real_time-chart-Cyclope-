<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftm=new ft(ADMIN_PATH.MODULE."templates/");
$ftm->define(array('main' => "emailreportssendfrom.html"));
$ftm->define_dynamic('template_row','main');

$dbu = new mysql_db();
$smtp_user = $dbu->field("SELECT value FROM settings WHERE constant_name='SMTP_USER'");
if($glob['email_report_id'])
{
	$dbu->query("SELECT * FROM email_report_sender WHERE email_report_id='".$glob['email_report_id']."'");
	
	while ($dbu->move_next()) {
		
		$ftm->assign(array(
			'EMAIL' => $dbu->f('email'),
		));
		
		$ftm->parse('TEMPLATE_ROW_OUT','.template_row');
	}
}
else 
{
	if(is_array($glob['email_report_sender']) && !empty($glob['email_report_sender']))
	{
		foreach ($glob['email_report_sender'] as $key => $value)
		{
			$ftm->assign(array(
				'EMAIL' => $value,
			));
			
			$ftm->parse('TEMPLATE_ROW_OUT','.template_row');
		}
	}
	else 
	{
		$ftm->assign(array(
			'EMAIL' => $smtp_user,
		));
		$ftm->parse('TEMPLATE_ROW_OUT','.template_row');
	}
}


$ftm->parse('CONTENT','main');
return $ftm->fetch('CONTENT');
?>