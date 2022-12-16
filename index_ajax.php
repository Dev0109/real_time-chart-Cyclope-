<?php
header('Content-type: text/html; charset=utf-8');
header('Accept-Charset: *');
error_reporting(0);
include('config/config.php');
include('libs/mysql_light.php');
$dbu = new mysql_light();
$dbu->query('SELECT * FROM `update` WHERE active = 1');
if(!$dbu->move_next()){
	return false;
}
define('CURRENT_VERSION_FOLDER',$dbu->f('folder').'/');
$dbu->query("SELECT value FROM settings WHERE constant_name='TIME_ZONE'");
if(!$dbu->move_next()){
	return false;
}
date_default_timezone_set($dbu->f('value'));
include(CURRENT_VERSION_FOLDER.'index_ajax.php');