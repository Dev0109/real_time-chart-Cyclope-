<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/

$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => "overtime.html"));

$dbu = new mysql_db();
$glob['time']['type'] = 4;
$ft->assign('APP_USAGE',include(CURRENT_VERSION_FOLDER.'php/ajax/xappusageovertime.php'));
include(CURRENT_VERSION_FOLDER.'php/ajax/xstats.php');

$filters = get_filters($glob['t'],$glob['f'],$glob['time']);
extract($filters,EXTR_OVERWRITE);

$dates = $dbu->row("SELECT MIN(date) AS genesis,MAX(date) AS last_day_on_earth FROM session ".$total_join.' WHERE 1=1 '.$total_filter);
$total_time = $glob['stats_active'] + $glob['stats_idle'];

$ft->assign(array(
	'PAGE_TITLE' => $ft->lookup('Overtime'),

	'ACTIVE' => format_time($glob['stats_active'],false),
	'IDLE' => format_time($glob['stats_idle'],false),
	'ONLINE' => format_time($glob['stats_online'],false),
	
	'ACTIVE_PROC' => $total_time ? number_format($glob['stats_active'] * 100 / $total_time,2) : 0,
	'IDLE_PROC' => $total_time ? number_format($glob['stats_idle'] * 100 / $total_time,2) : 0,
	'ONLINE_PROC' => $total_time ? number_format($glob['stats_online'] * 100 / $total_time,2) : 0,

	'DEFAULT_VALUE' => isset($glob['time']) ? $glob['time']['time'] : date('n/j/Y',$dates['genesis']).' - '.date('n/j/Y',$dates['last_day_on_earth']) ,
	'DATE_BEFORE' => date('n/j/Y',$dates['genesis']),
	'TIME_4' => 'checked="checked"',
	'HELP_LINK' => 'help.php?pag='.$glob['pag'],
));
	$trial = $dbu->field("SELECT value FROM `settings` WHERE `constant_name` = 'TRUENC'");
	$ecrypted_text = $dbu->field("SELECT message FROM `notification` WHERE `constant_name` = 'ENCRYPTED_TEXT'");
	if ($trial != 2236985){
		$ft->assign('ENCRYPTMESSAGERAW', '<div class="encryptmessage">' . $ft->lookup($ecrypted_text) . '</div>');
	}
if( $glob['stats_active'] == 0 && $glob['stats_idle'] == 0 && $glob['stats_online'] == 0 )
{
	$ft->assign(array(
		'NO_DATA_MESSAGE' => get_error($ft->lookup('No data to display for your current filters'),'warning'),
		'HIDE_CONTENT'	=> 'hide',
	));
}
else 
{
	$ft->assign(array(
		'NO_DATA_MESSAGE' => '',
		'HIDE_CONTENT'	=> '',
	));
}

global $bottom_includes;
$bottom_includes .= '
<script type="text/javascript">flobn.register("genesis",new Date("'.date('n/j/Y',$dates['genesis']).'"));</script>
<script type="text/javascript" src="ui/overtime-ui.js"></script>';

if(!$glob['is_ajax']){
	$ft->define_dynamic('ajax','main');
	$ft->parse('AJAX_OUT','ajax');
}


$glob['time']['type'] = 1;
$_SESSION['filters']['time']['type'] = 1;

$site_meta_title=$meta_title;
$site_meta_keywords=$meta_keywords;
$site_meta_description=$meta_description;

$ft->parse('CONTENT','main');
//$ft->fastprint('CONTENT');
return $ft->fetch('CONTENT');