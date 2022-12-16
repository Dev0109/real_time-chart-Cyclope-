<?php
/************************************************************************
* @Author: MedeeaWeb Works
* 
* se creaza o tabela session_unknown in care se insereaza dupa modelul session_website / chat / document
* ************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => "applicationforms.html"));

$dbu = new mysql_db();
$filters = get_filters($glob['t'],$glob['f'],$glob['time']);
extract($filters,EXTR_OVERWRITE);

//build application
$apps = $dbu->query("SELECT
SUM(session_log.duration) AS duration,
window.name,
window.application_id,
application.description,
window.window_id FROM session_log
INNER JOIN window ON window.window_id = session_log.window_id
INNER JOIN application ON application.application_id = window.application_id
INNER JOIN session ON session.session_id = session_log.session_id
".$app_join."
WHERE 1=1 
AND session_log.active < 2
".$app_filter."
GROUP BY session_log.application_id
ORDER BY duration desc
LIMIT 15");
$ddr = array();
while ($apps->next()){
	$ddr[$apps->f('application_id')] = $apps->f('description');
}
$ft->assign(array('APPS'=> bulid_simple_dropdown($ddr,$glob['app']),
					'APP_FORMS' => include(CURRENT_VERSION_FOLDER.'php/ajax/xapplicationforms.php')
));

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
	'HELP_LINK' => 'help.php?pag='.$glob['pag']
));
global $bottom_includes;
$bottom_includes.='
<script type="text/javascript">flobn.register("genesis",new Date("'.date('n/j/Y',$dates['genesis']).'"));</script><script type="text/javascript" src="ui/applicationforms-ui.js"></script>';

$export_header = get_export_header($glob['f']);
extract($export_header,EXTR_OVERWRITE);
$glob['append'] = trialEncrypt($member_name);

$ft->assign('PAGE_TITLE',$ft->lookup('Application Forms for'));
$ft->assign('APPEND', $glob['append']);

if(!$glob['is_ajax']){
	$ft->define_dynamic('ajax','main');
	$ft->parse('AJAX_OUT','ajax');
}

$ft->assign(array('PAG'=>$glob['pag']));

$ft->assign('MESSAGE',$glob['error']);
$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');
