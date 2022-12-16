<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftsx=new ft(ADMIN_PATH.MODULE."templates/");
$ftsx->define(array('main' => "xworkalert.html"));
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

$department_id = isset($glob['dep']) && is_numeric($glob['dep']) ? $glob['dep'] : 1;

$i = 0;
if(is_numeric($glob['alert_id'])){
	$working_time = $dbu->query("SELECT * FROM alert_time WHERE alert_id = ?",$glob['alert_id']);

	while ($working_time->next()) {
			$glob['w_start'][$working_time->f('day')]['hour'] = date('g',$working_time->f('start_time'));
			$glob['w_start'][$working_time->f('day')]['minutes'] =  (int)date('i',$working_time->f('start_time'));
			$glob['w_start'][$working_time->f('day')]['ampm'] = date('A',$working_time->f('start_time'));
			$glob['w_end'][$working_time->f('day')]['hour'] = date('g',$working_time->f('end_time'));
			$glob['w_end'][$working_time->f('day')]['minutes'] =  (int)date('i',$working_time->f('end_time'));
			$glob['w_end'][$working_time->f('day')]['ampm'] = date('A',$working_time->f('end_time'));
			
			$glob['w'][$working_time->f('day')] = 1;
		}
}else{
	if($department_id)
	{
		$department_name = $dbu->field("SELECT department.name FROM department WHERE department_id='".$department_id."'");
		
		$working_time = $dbu->query("SELECT * FROM workschedule WHERE department_id='".$department_id."' AND activity_type = 1");
		
		while ($working_time->next()) {
			$glob['w_start'][$working_time->f('day')]['hour'] = date('g',$working_time->f('start_time'));
			$glob['w_start'][$working_time->f('day')]['minutes'] =  (int)date('i',$working_time->f('start_time'));
			$glob['w_start'][$working_time->f('day')]['ampm'] = date('A',$working_time->f('start_time'));
			$glob['w_end'][$working_time->f('day')]['hour'] = date('g',$working_time->f('end_time'));
			$glob['w_end'][$working_time->f('day')]['minutes'] =  (int)date('i',$working_time->f('end_time'));
			$glob['w_end'][$working_time->f('day')]['ampm'] = date('A',$working_time->f('end_time'));
			
			$glob['w'][$working_time->f('day')] = 1;
		}
		
	}
}
foreach ($week_days as $day => $day_name )
{
	$ftsx->assign(array(
		
		'WEEKDAY' => $day_name,
		'DAY' => $day,
		
		'W_START_HOUR' => build_numbers_list(0,12,$glob['w_start'][$day]['hour']),
		'W_START_MINUTE' => build_numbers_list(0,59,$glob['w_start'][$day]['minutes']),
		'W_START_AMPM' => build_ampm_list($glob['w_start'][$day]['ampm'] == 'PM' ? 2 : 1),
		
		'W_END_HOUR' => build_numbers_list(0,12,$glob['w_end'][$day]['hour']),
		'W_END_MINUTE' => build_numbers_list(0,59,$glob['w_end'][$day]['minutes']),
		'W_END_AMPM' => build_ampm_list($glob['w_end'][$day]['ampm'] == 'PM' ? 2 : 1),
		
		'CLASS' => ($i% 2 == 0 ) ? '' : 'even',
		
		'W_CHECKED' => $glob['w'][$day] ? 'checked="checked"' : '',
	));
	
	$ftsx->parse('WORKING_ROW_OUT','.working_row');
	$i++;
}

$ftsx->assign(array(
		'USER_NAME' => $department_name,
		'MESSAGE' => get_error($glob['error']),
		'DEPARTMENT_ID' => $department_id,
));

$ftsx->parse('CONTENT','main');
return $ftsx->fetch('CONTENT');