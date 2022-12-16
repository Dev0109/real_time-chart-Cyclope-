<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => "log.html"));

$ft->assign(array(
	'PAGE_TITLE' => $ft->lookup('Logs Management'),
	'MESSAGE' => get_error($glob['error']),
));

$autodelete_logshalf = $dbu->field("SELECT value FROM settings WHERE constant_name='AUTODELETE_LOGSHALF'");

$ft->assign(array(
	'AUTODELETE_LOGSHALF'  => $autodelete_logshalf == 1 ? 'checked="checked"' : '',
));

global $bottom_includes;
$bottom_includes.='</script><script type="text/javascript" src="ui/log-ui.js"></script>';

$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');