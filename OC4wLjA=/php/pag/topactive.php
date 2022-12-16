<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/

$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => $glob['pag'].".html"));

$dbu = new mysql_db();


	if($_REQUEST['render'] && ($_REQUEST['tab'] == 'active' && $_REQUEST['pag'] == 'topactive') ){$ft->assign('ACTIVE_TABLE',include(CURRENT_VERSION_FOLDER.'php/ajax/xtopactive.php'));}
	
	if($_REQUEST['render'] && ($_REQUEST['tab'] == 'idle' && $_REQUEST['pag'] == 'topactive') ){$ft->assign('IDLE_TABLE',include(CURRENT_VERSION_FOLDER.'php/ajax/xtopidle.php'));}
	
	if($_REQUEST['render'] && (!$_REQUEST['tab'] && $_REQUEST['pag'] == 'topactive') ){$ft->assign('ACTIVE_TABLE',include(CURRENT_VERSION_FOLDER.'php/ajax/xtopactive.php'));}
	
	if(!$_REQUEST['render']){
		$active_usage = include(CURRENT_VERSION_FOLDER.'php/ajax/xtopactive.php');
		
		$idle_usage = include(CURRENT_VERSION_FOLDER.'php/ajax/xtopidle.php');
		
		$ft->assign('ACTIVE_TABLE',$active_usage);
		$ft->assign('IDLE_TABLE',$idle_usage);
	}

$filters = get_filters($glob['t'],$glob['f'],$glob['time'],true,true);
extract($filters,EXTR_OVERWRITE);

$export_header = get_export_header($glob['f']);
extract($export_header,EXTR_OVERWRITE);
$glob['append'] = trialEncrypt($department_name);

$dates = $dbu->row("SELECT MIN(date) AS genesis,MAX(date) AS last_day_on_earth FROM session ".$total_join.' WHERE 1=1 '.$total_filter);
	$trial = $dbu->field("SELECT value FROM `settings` WHERE `constant_name` = 'TRUENC'");
	$ecrypted_text = $dbu->field("SELECT message FROM `notification` WHERE `constant_name` = 'ENCRYPTED_TEXT'");
	if ($trial != 2236985){
		$ft->assign('ENCRYPTMESSAGERAW', '<div class="encryptmessage">' . $ft->lookup($ecrypted_text) . '</div>');
	}

$ft->assign(array(
	'PAGE_TITLE' => $ft->lookup('Top Active / Idle for'),
	'APPEND' => $glob['append'],
	'DEFAULT_VALUE' => isset($glob['time']) ? $glob['time']['time']: date('n/j/Y',$dates['genesis']).' - '.date('n/j/Y',$dates['last_day_on_earth']) ,
	'DATE_BEFORE' => date('n/j/Y',$dates['genesis']),
	'TIME_'.($glob['time']['type'] ? $glob['time']['type'] : 1) => 'selected="selected"',
	
	'HIDE_ACTIVE' => $active_usage != '' ? '' : 'hide',
	'NO_ACTIVE_DATA_MESSAGE' => $active_usage == '' ? get_error($ft->lookup('No data to display for your current filters'), 'warning') : '', 
	
	'HIDE_IDLE' => $idle_usage != '' ? '' : 'hide',
	'NO_IDLE_DATA_MESSAGE' => $idle_usage == '' ? get_error($ft->lookup('No data to display for your current filters'), 'warning') : '',
	'HELP_LINK' => 'help.php?pag='.$glob['pag'], 
));

if($_REQUEST['render'] == 'pdf' && ($active_usage == '' ||$idle_usage != '')){
	
	
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
		if($_REQUEST['tab'] == 'idle'){
			$ft->assign(array(
				'TITLE' => $ft->lookup('Top Idle'),
			));
			$page = 'topidle';
		} else {
			$ft->assign(array(
				'TITLE' => $ft->lookup('Top Active'),
			));
			$page = 'topactive';
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
	<script type="text/javascript">flobn.register("genesis",new Date("'.date('n/j/Y',$dates['genesis']).'"));</script><script type="text/javascript" src="ui/topactive-ui.js"></script>';

	$site_meta_title=$meta_title;
	$site_meta_keywords=$meta_keywords;
	$site_meta_description=$meta_description;

	$ft->parse('CONTENT','main');
	return $ft->fetch('CONTENT');
}