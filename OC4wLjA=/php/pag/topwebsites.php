<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => $glob['pag'].".html"));

$dbu = new mysql_db();

	if($_REQUEST['render'] && ($_REQUEST['tab'] == 'websites' && $_REQUEST['pag'] == 'topwebsites') ){$ft->assign('WEBSITE_USAGE',include(CURRENT_VERSION_FOLDER.'php/ajax/xtopwebsites.php'));}
	
	if($_REQUEST['render'] && ($_REQUEST['tab'] == 'online' && $_REQUEST['pag'] == 'topwebsites') ){$ft->assign('ONLINE_USAGE',include(CURRENT_VERSION_FOLDER.'php/ajax/xtoponline.php'));}
	
	if($_REQUEST['render'] && (!$_REQUEST['tab'] && $_REQUEST['pag'] == 'topwebsites') ){$ft->assign('WEBSITE_USAGE',include(CURRENT_VERSION_FOLDER.'php/ajax/xtopwebsites.php'));}
	
	if(!$_REQUEST['render']){
		$website_usage = include(CURRENT_VERSION_FOLDER.'php/ajax/xtopwebsites.php');

		$online_usage = include(CURRENT_VERSION_FOLDER.'php/ajax/xtoponline.php');
		
		$ft->assign('WEBSITE_USAGE',$website_usage);
		$ft->assign('ONLINE_USAGE',$online_usage);
	}
	
$filters = get_filters($glob['t'],$glob['f'],$glob['time'],true);
extract($filters,EXTR_OVERWRITE);

$export_header = get_export_header($glob['f']);
extract($export_header,EXTR_OVERWRITE);
$glob['append'] = trialEncrypt($member_name);

$glob['appendwebsites'] = trialEncrypt($member_name);
$glob['appendonline'] = $department_name;

$dates = $dbu->row("SELECT MIN(date) AS genesis,MAX(date) AS last_day_on_earth FROM session ".$total_join.' WHERE 1=1 '.$total_filter);
	$trial = $dbu->field("SELECT value FROM `settings` WHERE `constant_name` = 'TRUENC'");
	$ecrypted_text = $dbu->field("SELECT message FROM `notification` WHERE `constant_name` = 'ENCRYPTED_TEXT'");
	if ($trial != 2236985){
		$ft->assign('ENCRYPTMESSAGERAW', '<div class="encryptmessage">' . $ft->lookup($ecrypted_text) . '</div>');
	}

$ft->assign(array(
	'PAGE_TITLE' => $ft->lookup('Top Websites / Online for'),
	'APPEND' => $glob['appendonline'],
	'DEFAULT_VALUE' => isset($glob['time']) ? $glob['time']['time']: date('n/j/Y',$dates['genesis']).' - '.date('n/j/Y',$dates['last_day_on_earth']) ,
	'DATE_BEFORE' => date('n/j/Y',$dates['genesis']),
	'TIME_'.($glob['time']['type'] ? $glob['time']['type'] : 1) => 'selected="selected"',
	'HIDE_ONLINE' => $online_usage != '' ? '' : 'hide',
	'NO_ONLINE_DATA_MESSAGE' => $online_usage == '' ? get_error($ft->lookup('No data to display for your current filters'), 'warning') : '', 
	
	'HIDE_WEBSITE' => $website_usage != '' ? '' : 'hide',
	'NO_WEBSITE_DATA_MESSAGE' => $website_usage == '' ? get_error($ft->lookup('No data to display for your current filters'), 'warning') : '', 
	'HELP_LINK' => 'help.php?pag='.$glob['pag'],
	
));

if($_REQUEST['render'] == 'pdf' && ($website_usage == '' ||$online_usage != '')){
	
	
	//	modified for pdf	--->
		$export_header = get_export_header($_SESSION['filters']['f']);
		extract($export_header,EXTR_OVERWRITE);
		$ft->assign(array(
			'PDF_HEADER' => pdf_header(),
			'PDF_HIDE' => pdf_hide(),
			'PDF_CLASS' => pdf_class(),
			'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
			'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
		));
		if($_REQUEST['tab'] == 'online'){
			$ft->assign(array(
				'TITLE' => $ft->lookup('Top Online'),
			));
			$page = 'toponline';
		} else {
			$ft->assign(array(
				'TITLE' => $ft->lookup('Top Websites'),
			));
			$page = 'topwebsites';
		}
	//	<---	modified for pdf

	$ft->parse('CONTENT','main');

	//	modified for pdf	--->
		$html = $ft->fetch('CONTENT');
			file_put_contents(CURRENT_VERSION_FOLDER.'temp_pdf/'.$page.'.html',"\xEF\xBB\xBF" . $html);
		loadPDF($page,'inline');exit;
	//	<---	modified for pdf
} else {

if(!$glob['is_ajax']){
	$ft->define_dynamic('ajax','main');
	$ft->parse('AJAX_OUT','ajax');
}

global $bottom_includes;
$bottom_includes .= '
<script type="text/javascript">flobn.register("genesis",new Date("'.date('n/j/Y',$dates['genesis']).'"));flobn.register("appendonline","'.$glob['appendonline'].'");flobn.register("appendwebsites","'.$glob['appendwebsites'].'");</script><script type="text/javascript" src="ui/topwebsites-ui.js"></script>';

$site_meta_title=$meta_title;
$site_meta_keywords=$meta_keywords;
$site_meta_description=$meta_description;

$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');
}