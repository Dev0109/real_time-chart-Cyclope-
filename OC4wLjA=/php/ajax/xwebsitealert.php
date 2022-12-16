<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftalert=new ft(ADMIN_PATH.MODULE."templates/");
$ftalert->define(array('main' => "xwebsitealert.html"));
$ftalert->define_dynamic('template_row','main');

$dbu = new mysql_db();
if(is_numeric($glob['alert_id'])){
	$dbu->query("SELECT domain.domain AS web_name, alert_other.* FROM alert_other
				INNER JOIN domain ON domain.domain_id = alert_other.cond_link 
				WHERE alert_id = ?
				GROUP BY alert_id",$glob['alert_id']);
	$i = 0;
	while ($dbu->move_next()){
		$glob['web_txt'][$i] = $dbu->f('web_name');
		$glob['web'][$i] = $dbu->f('cond_link');
		$glob['web_hour'][$i] = intval($dbu->f('cond') / 60);
		$glob['web_minute'][$i] = $dbu->f('cond') % 60;
		$i++;
	}
}



if(is_array($glob['web']) && !empty($glob['web'])){
	foreach ($glob['web'] as $pos => $web_id){
		$ftalert->assign(array(
			'WEB' => $glob['web_txt'][$pos],
			'WEB_ID' => $web_id,
			'WEB_HOUR' => build_numbers_list(0,24,$glob['web_hour'][$pos]),
			'WEB_MINUTE' => build_numbers_list(0,59,$glob['web_minute'][$pos])
		));
		$ftalert->parse('TEMPLATE_ROW_OUT','.template_row');
	}
}else{
	$ftalert->assign(array(
		'WEB' => '',
		'WEB_ID' => '',
		'WEB_HOUR' => build_numbers_list(0,24,0),
		'WEB_MINUTE' => build_numbers_list(0,59,0)
	));
	$ftalert->parse('TEMPLATE_ROW_OUT','.template_row');
}


$ftalert->parse('CONTENT','main');
//$ftalert->fastprint('CONTENT');
return $ftalert->fetch('CONTENT');