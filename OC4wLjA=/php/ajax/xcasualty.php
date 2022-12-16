<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftcasual=new ft(ADMIN_PATH.MODULE."templates/");
$ftcasual->define(array('main' => "xcasualty.html"));

$dbu = new mysql_db();
$glob['t'] = 'session';

$pieces = explode('-',$glob['f']);
$pieces[0] = substr($pieces[0],1);
$department_id = $pieces[0];

$_SESSION['filters']['t'] = $glob['t'];
$_SESSION['filters']['f'] = $glob['f'];

$i = 0;

//	get from db, if does not exist, create it
$managesettings = $dbu->query("SELECT COUNT(*) as totalrows FROM settings WHERE module='extrareporttoggle'");

	if($managesettings->next()){
		$totalrows = $managesettings->f('totalrows');
	}
	
	$extrareport_daily = $dbu->query("SELECT value FROM settings WHERE constant_name='EXTRAREPORT_DAILY' AND module='extrareporttoggle'");
	$extrareport_daily->next();
	$extrareport_daily_value = $extrareport_daily->f('value');

	$extrareport_weekly = $dbu->query("SELECT value FROM settings WHERE constant_name='EXTRAREPORT_WEEKLY' AND module='extrareporttoggle'");
	$extrareport_weekly->next();
	$extrareport_weekly_value = $extrareport_weekly->f('value');

	$extrareport_monthly = $dbu->query("SELECT value FROM settings WHERE constant_name='EXTRAREPORT_MONTHLY' AND module='extrareporttoggle'");
	$extrareport_monthly->next();
	$extrareport_monthly_value = $extrareport_monthly->f('value');

		$ftcasual->assign(array(
			'EXTRAREPORT_DAILY' => $extrareport_daily_value,
			'EXTRAREPORT_WEEKLY' => $extrareport_weekly_value,
			'EXTRAREPORT_MONTHLY' => $extrareport_monthly_value,
		));

// anonymize value
$anonymize = $dbu->query("SELECT value FROM settings WHERE constant_name='ANONYMIZE_NAMES' AND module='anonymizetoggle'");
if($anonymize->next()){
	$ftcasual->assign(array(
		'ANONYMIZE_NAMES' => $anonymize->f('value'),
	));
}

// end anonymize value

if($department_id)
{
	//var_dump($department_id);
	$department_name = $dbu->field("SELECT department.name FROM department WHERE department_id='".$department_id."'");
	
	$data = $dbu->query("SELECT * FROM casualty WHERE department_id='".$department_id."'");
	if($data->next()){

		$ftcasual->assign(array(
			'CURRENCY' => build_currency_list($data->f('currency')),
			'COST_PER_HOUR' => $data->f('cost_per_hour'),
			'USER_NAME' => $department_name,
			'DEPARTMENT_ID' => $department_id,
		));
	}else{
		$ftcasual->assign(array(
			'CURRENCY' => build_currency_list(),
			'COST_PER_HOUR' => '1',
			'USER_NAME' => $department_name,
			'DEPARTMENT_ID' => $department_id,
		));
	}
}
else 
{
	$ftcasual->assign(array(
		'CURRENCY' => build_currency_list(get_currency(1)),
	));
}

$ftcasual->assign(array(
	'MESSAGE' => get_error($glob['error']),
));

if(!$glob['is_ajax']){
	$ftcasual->define_dynamic('casual_ajax','main');
	$ftcasual->define_dynamic('casual_ajax1','main');
	$ftcasual->parse('CASUAL_AJAX_OUT','casual_ajax');
	$ftcasual->parse('CASUAL_AJAX_OUT1','casual_ajax1');
}

$ftcasual->parse('CONTENT','main');
return $ftcasual->fetch('CONTENT');