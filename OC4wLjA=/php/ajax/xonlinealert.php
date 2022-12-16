<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftalert	=new ft(ADMIN_PATH.MODULE."templates/");
$ftalert->define(array('main' => "xonlinealert.html"));


if(is_numeric($glob['alert_id'])){
	$dbu->query("SELECT * FROM alert_other WHERE alert_id = ? GROUP BY alert_id",$glob['alert_id']);
	if($dbu->move_next()){
		$hours = intval($dbu->f('cond') / 60);
		$min = $dbu->f('cond') % 60;
		if(!isset($glob['online_hour']) && !isset($glob['online_minute'])){
			$glob['online_hour'] = $hours;
			$glob['online_minute'] = $min;
		}
	}
}

$ftalert->assign(array(
	'ONLINE_HOUR' => build_numbers_list(0,24,$glob['online_hour']),
	'ONLINE_MINUTE' => build_numbers_list(0,59,$glob['online_minute'])
));


$ftalert->parse('CONTENT','main');
//$ftalert->fastprint('CONTENT');
return $ftalert->fetch('CONTENT');