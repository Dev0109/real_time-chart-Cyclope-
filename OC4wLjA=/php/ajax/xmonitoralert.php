<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftalert=new ft(ADMIN_PATH.MODULE."templates/");
$ftalert->define(array('main' => "xmonitoralert.html"));

if(is_numeric($glob['alert_id'])){
	$dbu->query("SELECT * FROM alert_other WHERE alert_id = ? GROUP BY alert_id",$glob['alert_id']);
	if($dbu->move_next()){
		$days =	intval($dbu->f('cond') / (24*60));
		$hours = intval($dbu->f('cond') / 60);
		$min = $dbu->f('cond') % 60;
		if(!isset($glob['monitor_hour']) && !isset($glob['monitor_minute'])&&!isset($glob['monitor_days'])){
			$glob['monitor_hour'] = $hours;
			$glob['monitor_minute'] = $min;
			$glob['monitor_days'] = $days;
		}
	}
}

$ftalert->assign(array(
	'MONITOR_DAYS' => build_numbers_list(0,31,$glob['monitor_days']),
	'MONITOR_HOUR' => build_numbers_list(0,23,$glob['monitor_hour']),
	'MONITOR_MINUTE' => build_numbers_list(0,59,$glob['monitor_minute'])
));


$ftalert->parse('CONTENT','main');
//$ftalert->fastprint('CONTENT');
return $ftalert->fetch('CONTENT');