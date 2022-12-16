<?php

error_reporting(0);
//no data == no need to do anything
$report = file_get_contents('php://input');
if(empty($report)){
	header("HTTP/1.0 404 Not Found");
	exit();
}
// if(empty($report)){
	// $report = html_entity_decode('');
// }


ignore_user_abort(true);
set_time_limit(0);
// header('Content-type:text/plain');

if(!defined('CURRENT_VERSION_FOLDER'))
{
	define('CURRENT_VERSION_FOLDER','');
	include_once('../config/config.php');
}

include_once(CURRENT_VERSION_FOLDER."misc/cls_mysql_db.php");
include_once(CURRENT_VERSION_FOLDER."misc/json.php");
include_once(CURRENT_VERSION_FOLDER."misc/cyclope_lib.php");

include_once(CURRENT_VERSION_FOLDER.'classes/cls_category.php'); 

$cls_category = new category;
$dbu = new mysql_db();
$dbu->query("SELECT value FROM settings WHERE constant_name='TIME_ZONE'");
if(!$dbu->move_next()){
	return false;
}
        //      start the timer
                $mtime = microtime();
                $mtime = explode(" ",$mtime);
                $mtime = $mtime[1] + $mtime[0];
                $starttime = $mtime; 
				
				
date_default_timezone_set($dbu->f('value'));
$today_start = mktime(0,0,0);
$now = time();

$report = str_replace (array("\r\n", "\n", "\r", "\t"), ' ', $report);	//	remove newlines
$report = text_sanitize($report);	//	remove illegal characters
$report = json_prepare($report);
$report = urldecode(str_replace( "\0","",iconv ("UTF-8","ASCII", $report)));
$json = json_decode($report);
if(is_null($json)){
	$jsonDecoder = new Services_JSON();
	$json = $jsonDecoder->decode($report);
}
if(is_null($json)){
	// header('HTTP/1.1 204 No Content');
	exit();
}
if(empty($json->user->username)){
	// header('HTTP/1.1 206 Partial Content');
	exit();
}
if($json->user->username == "SYSTEM"){
	// header('HTTP/1.1 206 Partial Content');
	exit();
}
if(file_exists(CURRENT_VERSION_FOLDER.'logs/repair_')){
	header('HTTP/1.1 205 Database Repair');
	$filetime = filemtime (CURRENT_VERSION_FOLDER.'logs/repair_');
	$now = time();
	if($now - $filetime > 1800){
		unlink(CURRENT_VERSION_FOLDER.'logs/repair_');
	}
	exit();
}
$dbu->query("SET NAMES utf8");
$dbu->query("SET CHARACTER SET utf8");
// $dbu->query("UPDATE settings SET value = 0 WHERE constant_name = 'FREEZE_TIME_NOW'");
$json->sendtime = $json->sendtime ? $json->sendtime : time();
$log_id = $dbu->query_get_id("INSERT INTO tmplog SET log = '".str_replace("'", "", htmlspecialchars($report))."', arrival_date ='".$json->sendtime."', parsed ='8'");
//	populate the database with the data
include_once(CURRENT_VERSION_FOLDER."misc/populate_db.php");
        //end the timer
                        $mtime = microtime();
                        $mtime = explode(" ",$mtime);
                        $mtime = $mtime[1] + $mtime[0];
                        $endtime = $mtime;
        //      print the timer
                        $totaltime = ($endtime - $starttime);
$site_url = $dbu->field("SELECT long_value FROM settings WHERE constant_name ='SITE_URL'");
$folder = $dbu->field("SELECT folder FROM `update` WHERE active = 1");
$dbu->disconnect();
curl_request_async($site_url.$folder.'/cron.php',array('department' => $department_id));