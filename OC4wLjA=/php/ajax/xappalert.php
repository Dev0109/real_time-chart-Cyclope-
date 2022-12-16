<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftalert=new ft(ADMIN_PATH.MODULE."templates/");
$ftalert->define(array('main' => "xappalert.html"));
$ftalert->define_dynamic('template_row','main');

$dbu = new mysql_db();
if(is_numeric($glob['alert_id'])){
	$dbu->query("SELECT application.description AS app_name, alert_other.* FROM alert_other
				INNER JOIN application ON application.application_id = alert_other.cond_link 
				WHERE alert_id = ?
				GROUP BY alert_id",$glob['alert_id']);
	$i = 0;
	while ($dbu->move_next()){
		$glob['app_txt'][$i] = $dbu->f('app_name');
		$glob['app'][$i] = $dbu->f('cond_link');
		$glob['app_hour'][$i] = intval($dbu->f('cond') / 60);
		$glob['app_minute'][$i] = $dbu->f('cond') % 60;
		$i++;
	}
}


if(is_array($glob['app']) && !empty($glob['app'])){
	foreach ($glob['app'] as $pos => $app_id){
		$ftalert->assign(array(
			'APP' => $glob['app_txt'][$pos],
			'APP_ID' => $app_id,
			'APP_HOUR' => build_numbers_list(0,24,$glob['app_hour'][$pos]),
			'APP_MINUTE' => build_numbers_list(0,59,$glob['app_minute'][$pos])
		));
		$ftalert->parse('TEMPLATE_ROW_OUT','.template_row');
	}
}else{
	$ftalert->assign(array(
		'APP' => '',
		'APP_ID' => '',
		'APP_HOUR' => build_numbers_list(0,24,0),
		'APP_MINUTE' => build_numbers_list(0,59,0)
	));
	$ftalert->parse('TEMPLATE_ROW_OUT','.template_row');
}


$ftalert->parse('CONTENT','main');
//$ftalert->fastprint('CONTENT');
return $ftalert->fetch('CONTENT');