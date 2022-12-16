<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftsx=new ft(ADMIN_PATH.MODULE."templates/");
$ftsx->define(array('main' => "casualty.html"));

$dbu = new mysql_db();
$glob['t'] = 'session';

$pieces = explode('-',$glob['f']);
$pieces[0] = substr($pieces[0],1);
$department_id = $pieces[0];

$_SESSION['filters']['t'] = $glob['t'];
$_SESSION['filters']['f'] = $glob['f'];

$i = 0;

if($department_id)
{
	$department_name = $dbu->field("SELECT department.name FROM department WHERE department_id='".$department_id."'");
	
	$data = $dbu->query("SELECT * FROM casualty WHERE department_id='".$department_id."'");
	
	if($data->next()){
		$ftsx->assign(array(
			'CURRENCY' => build_currency_list($data->f('currency')),
			'COST_PER_HOUR' => $data->f('cost_per_hour'),
			'USER_NAME' => $department_name,
			'DEPARTMENT_ID' => $department_id,
			
		));
	}
}
else 
{
	$ftsx->assign(array(
		'CURRENCY' => build_currency_list(get_currency(1)),//ieuro
	));
}

$ftsx->assign(array(
	'MESSAGE' => get_error($glob['error']),
));

if(!$glob['is_ajax']){
	$ftsx->define_dynamic('ajax','main');
	$ftsx->parse('AJAX_OUT','ajax');
}

$ftsx->parse('CONTENT','main');
return $ftsx->fetch('CONTENT');