<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftm=new ft(ADMIN_PATH.MODULE."templates/");
$ftm->define(array('main' => "emailreportsfrequency.html"));

$dbu = new mysql_db();

if($glob['email_report_id'])
{
	$dbu->query("SELECT * FROM email_report_frequency WHERE email_report_id='".$glob['email_report_id']."'");
	
	while ($dbu->move_next()) 
	{
		switch ($dbu->f('frequency')) {
			case 1:
				$ftm->assign('DAILY_CHECKED','checked="checked"');
				break;
			case 2:
				$ftm->assign('WEEKLY_CHECKED','checked="checked"');
				break;
			case 3:
				$ftm->assign('MONTHLY_CHECKED','checked="checked"');
				break;	
		}
	}
}
else 
{
	if(is_array($glob['frequency']) && !empty($glob['frequency']))
	foreach ($glob['frequency'] as $key => $value)
	{
		switch ($value) {
			case 1:
				$ftm->assign('DAILY_CHECKED','checked="checked"');
				break;
			case 2:
				$ftm->assign('WEEKLY_CHECKED','checked="checked"');
				break;
			case 3:
				$ftm->assign('MONTHLY_CHECKED','checked="checked"');
				break;	
		}
	}

}

$ftm->parse('CONTENT','main');
return $ftm->fetch('CONTENT');
?>