<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => "internet.html"));

$dbu = new mysql_db();
//build application
$apps = $dbu->query("SELECT application.application_id,application.description, COALESCE(application_productivity.productive,1) AS productive FROM application 
LEFT JOIN application_productivity ON application_productivity.link_id = application.application_id
AND application_productivity.link_type = 0
WHERE application.application_type = 3");
$ddr = array();
$apps_productivity = array();
while ($apps->next()){
	$ddr[$apps->f('application_id')] = $apps->f('description');
	$apps_productivity[$apps->f('application_id')] = $apps->f('productive');
}

$ft->assign(array('APPS'=> bulid_simple_dropdown($ddr,$glob['app'])));
	$trial = $dbu->field("SELECT value FROM `settings` WHERE `constant_name` = 'TRUENC'");
	$ecrypted_text = $dbu->field("SELECT message FROM `notification` WHERE `constant_name` = 'ENCRYPTED_TEXT'");
	if ($trial != 2236985){
		$ft->assign('ENCRYPTMESSAGERAW', '<div class="encryptmessage">' . $ft->lookup($ecrypted_text) . '</div>');
	}
if($_REQUEST['render'] && ($_REQUEST['tab'] == 'domains' && $_REQUEST['pag'] == 'internet') ){$ft->assign('DOMAINS',include(CURRENT_VERSION_FOLDER.'php/ajax/xinternetdomains.php'));}
if($_REQUEST['render'] && ($_REQUEST['tab'] == 'urls' && $_REQUEST['pag'] == 'internet') ){$ft->assign('URLS',include(CURRENT_VERSION_FOLDER.'php/ajax/xinterneturls.php'));}
if($_REQUEST['render'] && ($_REQUEST['tab'] == 'windows' && $_REQUEST['pag'] == 'internet') ){$ft->assign('WINDOWS',include(CURRENT_VERSION_FOLDER.'php/ajax/xinternetwindows.php'));}
if($_REQUEST['render'] && (!$_REQUEST['tab'] && $_REQUEST['pag'] == 'internet') ){$ft->assign('DOMAINS',include(CURRENT_VERSION_FOLDER.'php/ajax/xinternetdomains.php'));}


$dates = $dbu->row("SELECT MIN(date) AS genesis,MAX(date) AS last_day_on_earth FROM session ");

$ft->assign(array(
	'DEFAULT_VALUE' => isset($glob['time']) ? $glob['time']['time']: date('n/j/Y',$dates['genesis']).' - '.date('n/j/Y',$dates['last_day_on_earth']) ,
	'DATE_BEFORE' => date('n/j/Y',$dates['genesis']),
	'TIME_'.($glob['time']['type'] ? $glob['time']['type'] : 1) => 'selected="selected"',
	'HELP_LINK' => 'help.php?pag='.str_replace("simple","",$glob['pag']),
));

global $bottom_includes;
$bottom_includes.='
<script type="text/javascript">flobn.register("genesis",new Date("'.date('n/j/Y',$dates['genesis']).'"));</script><script type="text/javascript" src="ui/internet-ui.js"></script>';

$export_header = get_export_header($glob['f']);
extract($export_header,EXTR_OVERWRITE);
$glob['append'] = trialEncrypt($member_name);
$ft->assign('PAGE_TITLE',$ft->lookup('Internet for'));
$ft->assign('APPEND', $glob['append']);
	//	modified for pdf	--->
	$export_header = get_export_header($_SESSION['filters']['f']);
	extract($export_header,EXTR_OVERWRITE);
	$ft->assign(array(
		'PDF_HEADER' => pdf_header(),
		'PDF_HIDE' => pdf_hide(),
		'PDF_CLASS' => pdf_class(),
		'TITLE' => $ft->lookup('Internet'),
		'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
		'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
	));
	//	<---	modified for pdf

if(!$glob['is_ajax']){
	$ft->define_dynamic('ajax','main');
	$ft->parse('AJAX_OUT','ajax');
}
if(count(explode('-',$glob['f'])) == 1){
	$bottom_includes.= '<script type="text/javascript">flobn.register("thouShallNotMove",0);</script>';	
	$glob['thouShallNotMove'] = 0;
}else{
	$bottom_includes.= '<script type="text/javascript">flobn.register("thouShallNotMove",1);</script>';	
	$glob['thouShallNotMove'] = 1;
}

$ft->assign('MESSAGE',$glob['error']);
$ft->parse('CONTENT','main');
	//	modified for pdf	--->
	if($_REQUEST['render'] == 'pdf'){
		$page = 'internet';
		$html = $ft->fetch('CONTENT');
			file_put_contents(CURRENT_VERSION_FOLDER.'temp_pdf/'.$page.'.html',"\xEF\xBB\xBF" . $html);
		loadPDF($page,'inline');exit;
	} else {
		return $ft->fetch('CONTENT');
	}
	//	<---	modified for pdf