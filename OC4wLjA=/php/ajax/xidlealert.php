<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftalert=new ft(ADMIN_PATH.MODULE."templates/");
$ftalert->define(array('main' => "xidlealert.html"));

if(is_numeric($glob['alert_id'])){
	$dbu->query("SELECT * FROM alert_other WHERE alert_id = ? GROUP BY alert_id",$glob['alert_id']);
	if($dbu->move_next()){
		$hours = intval($dbu->f('cond') / 60);
		$min = $dbu->f('cond') % 60;
		if(!isset($glob['idle_hour']) && !isset($glob['idle_minute'])){
			$glob['idle_hour'] = $hours;
			$glob['idle_minute'] = $min;
		}
	}
}

$ftalert->assign(array(
	'IDLE_HOUR' => build_numbers_list(0,24,$glob['idle_hour']),
	'IDLE_MINUTE' => build_numbers_list(0,59,$glob['idle_minute'])
));


$ftalert->parse('CONTENT','main');
//$ftalert->fastprint('CONTENT');
return $ftalert->fetch('CONTENT');