<?php
$ftupdate=new ft(ADMIN_PATH.MODULE."templates/");
$ftupdate->define(array('main' =>"xupdatesettings.html"));

$automatic_updates = $dbu->field("SELECT value FROM settings WHERE constant_name='AUTOMATIC_UPDATES'");


$ftupdate->assign(array(
	'AUTOMATIC_UPDATES'  => $automatic_updates == 1 ? 'checked="checked"' : '',
));


$ftupdate->parse('CONTENT','main');
return $ftupdate->fetch('CONTENT');