<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftalert=new ft(ADMIN_PATH.MODULE."templates/");
$ftalert->define(array('main' => "xseqalert.html"));
$ftalert->define_dynamic('template_row','main');

$dbu = new mysql_db();
if(is_numeric($glob['alert_id'])){
	$dbu->query("SELECT sequence_reports.name AS app_name, alert_other.* FROM alert_other
				INNER JOIN sequence_reports ON sequence_reports.sequencegrp_id = alert_other.cond_link 
				WHERE alert_id = ?",$glob['alert_id']);
	$i = 0;
	while ($dbu->move_next()){
		$glob['app_txt'][$i] = $dbu->f('app_name');
		$glob['app'][$i] = $dbu->f('cond_link');
		$glob['app_hour'][$i] = $dbu->f('cond');
		$i++;
	}
}


if(is_array($glob['app']) && !empty($glob['app'])){
	foreach ($glob['app'] as $pos => $app_id){
		$ftalert->assign(array(
			'SEQLIST' => build_seq_dd($app_id),
			'APP' => $glob['app_txt'][$pos],
			'APP_ID' => $app_id,
			'APP_HOUR' => $glob['app_hour'][$pos],
		));
		$ftalert->parse('TEMPLATE_ROW_OUT','.template_row');
	}
}else{
	$ftalert->assign(array(
		'SEQLIST' => build_seq_dd(),
		'APP' => '',
		'APP_ID' => '',
		'APP_HOUR' => 1,
	));
	$ftalert->parse('TEMPLATE_ROW_OUT','.template_row');
}


$ftalert->parse('CONTENT','main');
return $ftalert->fetch('CONTENT');