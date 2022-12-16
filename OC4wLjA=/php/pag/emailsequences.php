<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => $glob['pag'].".html"));
$ft->define_dynamic('template_row','main');

$dbu = new mysql_db();

$reports = $dbu->query("SELECT * FROM sequence_reports ORDER BY name DESC");

$i=0;

while($reports->next()){
	
	$for = $dbu->query("SELECT department.name,sequence_dep.department_id FROM sequence_dep 
	INNER JOIN department ON department.department_id = sequence_dep.department_id WHERE sequencegrp_id='".$reports->f('sequencegrp_id')."'");
	
	$out = '';
	while ($for->next()) {
		$out .= $for->f('name').', ';
	}
	
	$out = rtrim($out,', ');
	
	$ft->assign(array(
		'NAME'     =>  $reports->f('name'),
		'DESCRIPTION'     =>  $reports->f('description'),
		'FOR'     =>  $out,
	));
	
	$ft->assign(array(	
		'EDIT_LINK' => 'index.php?pag=emailsequence&sequencegrp_id='.$reports->f('sequencegrp_id'),
		'DELETE_LINK' => 'index.php?pag='.$glob['pag'].'&act=emailsequence-delete&sequencegrp_id='.$reports->f('sequencegrp_id'),
	));
	
	if(($i % 2)==0 )
	{
		$ft->assign('CLASS','even');
	}
	else
	{
		$ft->assign('CLASS','');
	}
	
	$ft->parse('TEMPLATE_ROW_OUT','.template_row');
	$i++;
}

if(!SMTP_SERVER)
{
	$ft->assign(array(
		'NO_DATA_MESSAGE' =>get_error($ft->lookup('You need to fill in the SMTP details before adding any email report. <a href="index.php?pag=settings#emailsettings">Click here</a> to enter you details !'),'warning '),
		'HIDE_CONTENT' => 'hide'
	));
}
else if($i == 0){
	$ft->assign(array(
		'NO_DATA_MESSAGE' =>get_error($ft->lookup('There are no reports sets defined. <a href="index.php?pag=emailsequence">Click here</a> to start adding sequences !'),'warning '),
		'HIDE_CONTENT' => 'hide'
	));
}
else 
{
	$ft->assign(array(
		'NO_DATA_MESSAGE' =>'',
		'HIDE_CONTENT' => ''
	));
}

$ft->assign('PAGE_TITLE',$ft->lookup('Application Sequences'));

$ft->assign('ADD_LINK','index.php?pag=emailsequence');
$ft->assign('MESSAGE',get_error($glob['error']));
$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');
?>