<?php

ini_set('display_errors','on');
include('config/config.php');
 if(DEBUG_CONTEXT){
	error_reporting(E_ALL & ~E_NOTICE);
}else{
	error_reporting(0);
} 

if(ioncube_license_has_expired() || !ioncube_license_matches_server()){
	header('Location: activate.php');
	exit();
}
header('Content-type: text/html; charset=utf-8');
header('Accept-Charset: *');
//error_reporting(0);
include('libs/mysql_light.php');
$dbu = new mysql_light();
$dbu->query('SELECT * FROM `update` WHERE active = 1');
if(!$dbu->move_next()){
	return false;	
}


define('CURRENT_VERSION_FOLDER',$dbu->f('folder').'/');
include(CURRENT_VERSION_FOLDER.'index.php');