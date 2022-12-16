<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => "error.html"));
global $site_url;
$ft->assign(array(
	'LASTPAGE' => $glob['lastpage'],
	'FILE' => $glob['file'],
	'COMMENTS' => $glob['comments'],
	'EMAIL' => ADMIN_EMAIL,
	'PATH' => CURRENT_VERSION_FOLDER.'logs/'.$glob['file']
));

$ft->parse('CONTENT','main');
//$ft->fastprint('CONTENT');
echo $ft->fetch('CONTENT');
exit();