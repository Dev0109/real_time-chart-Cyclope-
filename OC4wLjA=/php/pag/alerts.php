<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => $glob['pag'].".html"));
$ft->define_dynamic('template_row','main');

$dbu = new mysql_db();

$reports = $dbu->query("SELECT * FROM alert");

$i=0;

while($reports->next()){
	
	$for = $dbu->query("SELECT department.name AS department_name, 
							   alert_department.department_id
							FROM department 
						
						INNER JOIN alert_department ON alert_department.department_id = department.department_id
						WHERE alert_department.alert_id='".$reports->f('alert_id')."'");
	
	$out = '';
	
	while ($for->next()) {
		$out .= $for->f('department_name').', ';
	}
	
	$out = rtrim($out,', ');
	switch ($reports->f('alert_type')){
				case 1://workschedule alert
					$type= 'Workschedule Alert';
				break;
				case 2://idle alert
					$type ='Idle Time Alert';
				break;
				case 3:
					$type = 'Online Time Alert';
				break;
				case 4:
					$type = 'Applications Alert';
				break;	
				case 5:
					$type = 'Monitor Alert';
				break;	
				case 6:
					$type = 'Website Alert';
				break;	
				case 7:
					$type = 'Sequence Alert';
				break;	
	}
	$ft->assign(array(
		'NAME'     =>  $reports->f('name'),
		'TYPE'	   =>  $ft->lookup($type),
		'DESCRIPTION'     =>  $reports->f('description'),
		'FOR'     =>  $out,
	));
	
	$ft->assign(array(	
		'EDIT_LINK' => 'index.php?pag=alert&alert_id='.$reports->f('alert_id'),
		'DELETE_LINK' => 'index.php?pag='.$glob['pag'].'&act=alert-delete&alert_id='.$reports->f('alert_id'),
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

	$trial = $dbu->field("SELECT value FROM `settings` WHERE `constant_name` = 'TRUENC'");
	$ecrypted_text = $dbu->field("SELECT message FROM `notification` WHERE `constant_name` = 'ENCRYPTED_TEXT'");
	if ($trial != 2236985){
		$ft->assign('ENCRYPTMESSAGERAW', '<div class="encryptmessage">' . $ft->lookup($ecrypted_text) . '</div>');
	}

if($i == 0){
	$ft->assign(array(
		'NO_DATA_MESSAGE' =>get_error($ft->lookup('There are no alerts defined. <a href="index.php?pag=alert">Click here</a> to start adding alerts !'),'warning '),
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

$ft->assign('PAGE_TITLE',$ft->lookup('Alerts'));

$ft->assign('ADD_LINK','index.php?pag=alert');
$ft->assign('MESSAGE',get_error($glob['error']));
$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');
?>