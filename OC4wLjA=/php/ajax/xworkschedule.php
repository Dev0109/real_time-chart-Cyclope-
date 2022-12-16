<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftsx=new ft(ADMIN_PATH.MODULE."templates/");
$ftsx->define(array('main' => "xworkschedule.html"));
$ftsx->define_dynamic('working_row','main');

$dbu = new mysql_db();

$week_days =  array(
	'1' => 'Monday',
	'2' => 'Tuesday',
	'3' => 'Wednesday',
	'4' => 'Thursday',
	'5' => 'Friday',
	'6' => 'Saturday',
	'0' => 'Sunday',
);

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
	
	$working_time = $dbu->query("SELECT * FROM workschedule WHERE department_id='".$department_id."' AND activity_type = 1");
	
	while ($working_time->next()) {
		$glob['w_start'][$working_time->f('day')]['hour'] = date('G',$working_time->f('start_time'));
		$glob['w_start'][$working_time->f('day')]['minutes'] =  (int)date('i',$working_time->f('start_time'));
		$glob['w_start'][$working_time->f('day')]['ampm'] = date('A',$working_time->f('start_time'));
		$glob['w_end'][$working_time->f('day')]['hour'] = date('G',$working_time->f('end_time'));
		$glob['w_end'][$working_time->f('day')]['minutes'] =  (int)date('i',$working_time->f('end_time'));
		$glob['w_end'][$working_time->f('day')]['ampm'] = date('A',$working_time->f('end_time'));
		
		$glob['w'][$working_time->f('day')] = 1;
	}
}

foreach ($week_days as $day => $day_name )
{
	$ftsx->assign(array(
		
		'WEEKDAY' => $day_name,
		'DAY' => $day,
		
		'W_START_HOUR' => build_numbers_list(0,23,$glob['w_start'][$day]['hour']),
		'W_START_MINUTE' => build_numbers_list_nth(0,59,30,$glob['w_start'][$day]['minutes']),
		'W_START_AMPM' => build_ampm_list($glob['w_start'][$day]['ampm'] == 'PM' ? 2 : 1),
		
		'W_END_HOUR' => build_numbers_list(0,23,$glob['w_end'][$day]['hour']),
		'W_END_MINUTE' => build_numbers_list_nth(0,59,30,$glob['w_end'][$day]['minutes']),
		'W_END_AMPM' => build_ampm_list($glob['w_end'][$day]['ampm'] == 'PM' ? 2 : 1),
		
		
		'P_START_HOUR' => build_numbers_list(0,23,$glob['p_start'][$day]['hour']),
		'P_START_MINUTE' => build_numbers_list_nth(0,59,30,$glob['p_start'][$day]['minutes']),
		'P_START_AMPM' => build_ampm_list($glob['p_start'][$day]['ampm'] == 'PM' ? 2 : 1),
		
		'P_END_HOUR' => build_numbers_list(0,23,$glob['p_end'][$day]['hour']),
		'P_END_MINUTE' => build_numbers_list_nth(0,59,30,$glob['p_end'][$day]['minutes']),
		'P_END_AMPM' => build_ampm_list($glob['p_end'][$day]['ampm'] == 'PM' ? 2 : 1),
		
		'CLASS' => ($i% 2 == 0 ) ? '' : 'even',
		'DEPARTMENT_ID' => $department_id,
		
		
		'W_CHECKED' => $glob['w'][$day] ? 'checked="checked"' : '',
		'P_CHECKED' => $glob['p'][$day] ? 'checked="checked"' : '',
		
	));
	
	$ftsx->parse('WORKING_ROW_OUT','.working_row');
	$i++;
}

$ftsx->assign(array(
		'USER_NAME' => $department_name,
		'MESSAGE' => get_error($glob['error']),
		
	));
	
if(count(explode('-',$glob['f'])) == 1){
	$bottom_includes.= '<script type="text/javascript">flobn.register("thouShallNotMove",0);</script>';	
	$glob['thouShallNotMove'] = 0;
}else{
	$bottom_includes.= '<script type="text/javascript">flobn.register("thouShallNotMove",1);</script>';	
	$glob['thouShallNotMove'] = 1;
}
//echo "<pre />";
//print_r($glob);
//die;

$ftsx->parse('CONTENT','main');
return $ftsx->fetch('CONTENT');