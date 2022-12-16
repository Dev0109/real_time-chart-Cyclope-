<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftm=new ft(ADMIN_PATH.MODULE."templates/");
$ftm->define(array('main' => "emailsequencedetails.html"));

$dbu = new mysql_db();

if($glob['sequencegrp_id'])
{
	$dbu->query("SELECT * FROM email_report_details WHERE sequencegrp_id='".$glob['sequencegrp_id']."'");
	$dbu->move_next();
	
	$ftm->assign(array(
			'SUBJECT' => $dbu->f('subject'),
			'BODY' => $dbu->f('body'),
			
	)
	);
	switch ($dbu->f('attachment_type')) {
			case 1:
				$ftm->assign('PDF_CHECKED','checked="checked"');
				break;
			case 2:
				$ftm->assign('EXCEL_CHECKED','checked="checked"');
				break;
			case 3:
				$ftm->assign('HTML_CHECKED','checked="checked"');
				break;
			default: $ftm->assign('PDF_CHECKED','checked="checked"');
		}
	switch ($dbu->f('time_filter')) {
			case 1:
				$ftm->assign('ALL_CHECKED','checked="checked"');
				break;
			case 3:
				$ftm->assign('WORKTIME_CHECKED','checked="checked"');
				break;
			case 4:
				$ftm->assign('OVERTIME_CHECKED','checked="checked"');
				break;
			default: $ftm->assign('ALL_CHECKED','checked="checked"');
		}
}
else 
{
	$ftm->assign(array(
			'SUBJECT' => $glob['subject'],
			'BODY' => $glob['body'],
	));
	$ftm->assign('PDF_CHECKED','checked="checked"');
	$ftm->assign('ALL_CHECKED','checked="checked"');
}

$ftm->parse('CONTENT','main');
return $ftm->fetch('CONTENT');
?>