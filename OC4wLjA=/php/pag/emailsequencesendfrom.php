<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftm=new ft(ADMIN_PATH.MODULE."templates/");
$ftm->define(array('main' => "emailsequencesendfrom.html"));
$ftm->define_dynamic('template_row','main');

$dbu = new mysql_db();

if($glob['sequencegrp_id'])
{
	$dbu->query("SELECT * FROM email_report_sender WHERE sequencegrp_id='".$glob['sequencegrp_id']."'");
	
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
			'EMAIL' => 'cyclope.reports@amplusnet.com',
		));
		$ftm->parse('TEMPLATE_ROW_OUT','.template_row');
	}
}


$ftm->parse('CONTENT','main');
return $ftm->fetch('CONTENT');
?>