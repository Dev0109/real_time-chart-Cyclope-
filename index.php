<?php

 if(ioncube_license_has_expired() || !ioncube_license_matches_server()){

 	header('Location: activate.php');
 	exit();
 }

header('Content-type: text/html; charset=utf-8');
header('Accept-Charset: *');
error_reporting(0);
$dirname=''; //kaz 
include('config/config.php');
include('libs/mysql_light.php');

$dbu = new mysql_light();
$dbu->query('SELECT * FROM `update` WHERE active = 1');
if(!$dbu->move_next()){
	return false;
}
define('CURRENT_VERSION_FOLDER',$dbu->f('folder').'/');


$dbu->query("SELECT long_value FROM settings WHERE constant_name ='SITE_URL'");
if(!$dbu->move_next()){
	return false;
}

define('SITE_URL',$dbu->f('long_value').'/');


$dbu->query("SELECT value FROM settings WHERE constant_name = 'TIME_ZONE'");

if(!$dbu->move_next()){
	return false;
}
date_default_timezone_set($dbu->f('value'));
include(CURRENT_VERSION_FOLDER.'index.php');
