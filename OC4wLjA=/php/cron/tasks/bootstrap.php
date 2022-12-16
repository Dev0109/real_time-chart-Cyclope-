<?php

set_time_limit(0);
ignore_user_abort(true);
include_once('php/gen/startup.php');
include_once('classes/cls_category.php');

$dbu = new mysql_db();
$cls_category = new category;

//	-----------------------------------------------------------
//	set install date
$install_date = $dbu->field("SELECT `value` FROM `settings` WHERE `constant_name` = 'INSTALL_DATE'");
if(!$install_date)
{
	$dbu->query("INSERT INTO `settings` (`constant_name`, `value`, `long_value`, `module`, `type`) VALUES ('INSTALL_DATE', '".time()."', NULL, 'installdate', '1')");
}

//	-----------------------------------------------------------
//	clean tmplog
$dbu->query("DELETE FROM tmplog WHERE parsed IN(1,2)");