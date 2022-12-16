<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftm=new ft(ADMIN_PATH.MODULE."templates/");
$ftm->define(array('main' => "emailsequencesave.html"));

$dbu = new mysql_db();

if($glob['sequencegrp_id'])
{
	$dbu->query("SELECT * FROM sequence_reports WHERE sequencegrp_id='".$glob['sequencegrp_id']."'");
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