<?php

set_time_limit(0);
date_default_timezone_set('Europe/Bucharest');
header('Content-type:text/plain');
include('config/config.php');
error_reporting(E_ALL & ~ E_NOTICE);
include_once("misc/cls_mysql_db.php");
include_once("misc/json.php");
include_once("misc/cyclope_lib.php");
$dbu = new mysql_db();

$inventory = $dbu->query("SELECT * FROM application_inventory");

while ($inventory->next()) {
	$dbu->query("UPDATE application_inventory SET hour = '".date('G',$inventory->f('arrival_date'))."',
	day = '".date('w',$inventory->f('arrival_date'))."'
	WHERE application_inventory_id='".$inventory->f('application_inventory_id')."'");
}
echo 'Make this shit work';
?>