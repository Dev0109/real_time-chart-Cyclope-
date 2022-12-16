<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftm=new ft(ADMIN_PATH.MODULE."templates/");
$ftm->define(array('main' => "emailreportssendto.html"));
$ftm->define_dynamic('template_row','main');

$dbu = new mysql_db();

if($glob['email_report_id'])
{
	$dbu->query("SELECT * FROM email_report_receiver WHERE email_report_id='".$glob['email_report_id']."'");
	
	while ($dbu->move_next()) {
		
		$ftm->assign(array(
			'EMAIL' => $dbu->f('email'),
		));
		
		$ftm->parse('TEMPLATE_ROW_OUT','.template_row');
	}
}
else 
{
	if(is_array($glob['email_report_receiver']) && !empty($glob['email_report_receiver']))
	{
		foreach ($glob['email_report_receiver'] as $key => $value)
		{
			$ftm->assign(array(
				'EMAIL' => $value,
			));
			
			$ftm->parse('TEMPLATE_ROW_OUT','.template_row');
		}
	}
	else 
	{
		$ftm->parse('TEMPLATE_ROW_OUT','.template_row');
	}
}

$ftm->parse('CONTENT','main');
return $ftm->fetch('CONTENT');
?>