<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/

$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => "document.html"));

$dbu = new mysql_db();
//build application
$apps = $dbu->query("SELECT application.application_id,application.description, COALESCE(application_productivity.productive,1) AS productive FROM application 
LEFT JOIN application_productivity ON application_productivity.link_id = application.application_id
AND application_productivity.link_type = 0
WHERE application.application_type = 2");
$ddr = array();
$apps_productivity = array();
$i = 0;
while ($apps->next()){
	if($i < 15)
	{
		$ddr[$apps->f('application_id')] = $apps->f('description');
	}
	
	$apps_productivity[$apps->f('application_id')] = $apps->f('productive');
	$i++;
}
$ft->assign(array('APPS'=> bulid_simple_dropdown($ddr,$glob['app'])));
$ft->assign('DOCUMENTS',include_once(CURRENT_VERSION_FOLDER.'php/ajax/xdocument.php'));
	$trial = $dbu->field("SELECT value FROM `settings` WHERE `constant_name` = 'TRUENC'");
	$ecrypted_text = $dbu->field("SELECT message FROM `notification` WHERE `constant_name` = 'ENCRYPTED_TEXT'");
	if ($trial != 2236985){
		$ft->assign('ENCRYPTMESSAGERAW', '<div class="encryptmessage">' . $ft->lookup($ecrypted_text) . '</div>');
	}

$dates = $dbu->row("SELECT MIN(date) AS genesis,MAX(date) AS last_day_on_earth FROM session ");


$ft->assign(array(
	'DEFAULT_VALUE' => isset($glob['time']) ? $glob['time']['time']: date('n/j/Y',$dates['genesis']).' - '.date('n/j/Y',$dates['last_day_on_earth']) ,
	'DATE_BEFORE' => date('n/j/Y',$dates['genesis']),
	'TIME_'.($glob['time']['type'] ? $glob['time']['type'] : 1) => 'selected="selected"',
	'HELP_LINK' => 'help.php?pag='.$glob['pag'],
));
global $bottom_includes;
$bottom_includes.='
<script type="text/javascript">flobn.register("genesis",new Date("'.date('n/j/Y',$dates['genesis']).'"));</script><script type="text/javascript" src="ui/document-ui.js"></script>';

$export_header = get_export_header($glob['f']);
extract($export_header,EXTR_OVERWRITE);
$glob['append'] = trialEncrypt($member_name);
$ft->assign(array('PAG'=>$glob['pag']));
$ft->assign('PAGE_TITLE',$ft->lookup('Documents for'));
$ft->assign('APPEND', $glob['append']);
$ft->assign('MESSAGE',$glob['error']);

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


$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');