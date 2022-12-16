<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => "file.html"));


$dbu = new mysql_db();
$ft->assign('APPS', build_drive_dd($glob['app']));
/**
 * "Does this look like the work of little green men?" -Tom Colton 
 * "Grey."-Mulder 
 * "Excuse me?" -Tom Colton 
 * "Grey. You said green men. A Reticulan's skin tone is actually grey. They're notorious for their extraction of human livers." -Mulder 
 * "You can't be serious." -Tom Colton 
 * "Do you have any idea what liver and onions go for on Reticula? Excuse me." -Mulder
 */
$ft->assign('XFILES',include(CURRENT_VERSION_FOLDER.'php/ajax/xfile.php'));

$dates = $dbu->row("SELECT MIN(date) AS genesis,MAX(date) AS last_day_on_earth FROM session ");

	$trial = $dbu->field("SELECT value FROM `settings` WHERE `constant_name` = 'TRUENC'");
	$ecrypted_text = $dbu->field("SELECT message FROM `notification` WHERE `constant_name` = 'ENCRYPTED_TEXT'");
	if ($trial != 2236985){
		$ft->assign('ENCRYPTMESSAGERAW', '<div class="encryptmessage">' . $ft->lookup($ecrypted_text) . '</div>');
	}
$ft->assign(array(
	'DEFAULT_VALUE' => isset($glob['time']) ? $glob['time']['time']: date('n/j/Y',$dates['genesis']).' - '.date('n/j/Y',$dates['last_day_on_earth']) ,
	'DATE_BEFORE' => date('n/j/Y',$dates['genesis']),
	'TIME_'.($glob['time']['type'] ? $glob['time']['type'] : 1) => 'selected="selected"',
	'HELP_LINK' => 'help.php?pag='.$glob['pag'],
));
global $bottom_includes;
$bottom_includes.='<script type="text/javascript">flobn.register("genesis",new Date("'.date('n/j/Y',$dates['genesis']).'"));</script><script type="text/javascript" src="ui/file-ui.js"></script>';

$export_header = get_export_header($glob['f']);
extract($export_header,EXTR_OVERWRITE);
$glob['append'] = trialEncrypt($member_name);

$ft->assign('PAGE_TITLE',$ft->lookup('Files for'));
$ft->assign('APPEND', $glob['append']);

if(!$glob['is_ajax']){
	$ft->define_dynamic('ajax','main');
	$ft->parse('AJAX_OUT','ajax');
}

$ft->assign('MESSAGE',$glob['error']);
$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');