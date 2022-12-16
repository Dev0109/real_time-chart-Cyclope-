<?php
error_reporting(0);
include('config/config.php');
include('libs/mysql_light.php');
$dbu = new mysql_light();
$dbu->query('SELECT * FROM `update` WHERE active = 1');
if(!$dbu->move_next()){
	return false;
}
define('CURRENT_VERSION_FOLDER',$dbu->f('folder').'/');
include(CURRENT_VERSION_FOLDER.'jsonunicode.php');