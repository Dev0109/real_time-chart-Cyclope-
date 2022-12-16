<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => "granular.html"));

$dbu = new mysql_db();
$selected = isset($_REQUEST['username']) ? $_REQUEST['username'] : null;
$granurl = $dbu->field("SELECT `long_value`
						FROM `settings`
						WHERE `constant_name` LIKE 'GRANULAR_URL'");

$ft->assign(array(
		'MONITORED_USERS' => build_granuser_dd($selected),
		'MONITORED_COMPUTERS' => build_grancomp_dd($selected),
		'GRANURL' => $granurl,
));

$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');