<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/

$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => $glob['pag'].".html"));

$site_meta_title=$meta_title;
$site_meta_keywords=$meta_keywords;
$site_meta_description=$meta_description;

global $bottom_includes;
$bottom_includes='<script type="text/javascript" src="ui/groups-ui.js"></script>';
	$trial = $dbu->field("SELECT value FROM `settings` WHERE `constant_name` = 'TRUENC'");
	$ecrypted_text = $dbu->field("SELECT message FROM `notification` WHERE `constant_name` = 'ENCRYPTED_TEXT'");
	if ($trial != 2236985){
		$ft->assign('ENCRYPTMESSAGE', '<div class="encryptmessage">' . $ft->lookup($ecrypted_text) . '</div>');
	}

$ft->assign(array(
	'LANG' => strtolower(LANG),
));
$folder = CURRENT_VERSION_FOLDER.'help/'.strtolower(LANG).'/'; 
if(!is_dir($folder)){
		$ft->assign(array(
			'LANG' => 'en',
		));
}
$ft->parse('CONTENT','main');
//$ft->fastprint('CONTENT');
return $ft->fetch('CONTENT');