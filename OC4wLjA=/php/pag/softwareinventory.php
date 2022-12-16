<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/

$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => $glob['pag'].".html"));

$dbu = new mysql_db();


$filter = '';

$pieces = explode('-',$glob['f']);
$filterCount = count($pieces);
$pieces[0] = substr($pieces[0],1);
if($filterCount == 1){
		$ft->assign(array(
			'NO_DATA_MESSAGE' => get_error($ft->lookup('Please select a user'),'warning'),
			'HIDE_CONTENT'	=> 'hide',
		));
}

$ft->assign('INVENTORY_TABLE',include(CURRENT_VERSION_FOLDER.'php/ajax/xsoftwareinventory.php'));
$ft->assign('ALERTS_TABLE',include(CURRENT_VERSION_FOLDER.'php/ajax/xsoftwarealerts.php'));

$dates = $dbu->row("SELECT MIN(arrival_date) AS genesis,MAX(arrival_date) AS last_day_on_earth FROM application_inventory");



$ft->assign(array(
	'PAGE_TITLE' => $ft->lookup('Software Inventory for'),
	'DEFAULT_VALUE' => isset($glob['time']) ? $glob['time']['time']: date('n/j/Y',$dates['genesis']).' - '.date('n/j/Y',$dates['last_day_on_earth']) ,
	'DATE_BEFORE' => date('n/j/Y',$dates['genesis']),
	'TIME_'.($glob['time']['type'] ? $glob['time']['type'] : 1) => 'selected="selected"',
	'HELP_LINK' => 'help.php?pag='.$glob['pag'],
));

global $bottom_includes;

$export_header = get_export_header($glob['f']);
extract($export_header,EXTR_OVERWRITE);
$glob['append'] = trialEncrypt($member_name);
$ft->assign('APPEND', $glob['append']);

if(!$glob['is_ajax']){
	$ft->define_dynamic('ajax','main');
	$ft->parse('AJAX_OUT','ajax');
}
$bottom_includes .= '
<script type="text/javascript">flobn.register("genesis",new Date("'.date('n/j/Y',$dates['genesis']).'"));</script><script type="text/javascript" src="ui/softwareinventory-ui.js"></script>';


$site_meta_title=$meta_title;
$site_meta_keywords=$meta_keywords;
$site_meta_description=$meta_description;

$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');