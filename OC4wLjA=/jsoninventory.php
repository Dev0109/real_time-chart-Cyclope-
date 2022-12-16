<?php

error_reporting(0);
//no data == no need to do anything
$report = file_get_contents('php://input');
if(empty($report)){
	header("HTTP/1.0 404 Not Found");
	exit();
}
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
$report = json_prepare($report);
$json = json_decode($report);
debug_log($json,'inventoryjson');
if(is_null($json)){
	$jsonDecoder = new Services_JSON();
	$json = $jsonDecoder->decode($report);
}
if(is_null($json)){
	// header('HTTP/1.1 204 No Content');
	exit();
}
if(empty($json->username)){
	// header('HTTP/1.1 206 Partial Content');
	exit();
}
if($json->username == "SYSTEM"){
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

//	================================================================================
//	================================================================================
//	================================================================================
//	================================================================================
// echo '<pre>'.print_r($json,1).'</pre><hr>';

//	get user id
	$member = $dbu->query("SELECT * FROM member WHERE logon = '".$json->username."'");
	if($member->next()){
		$member_id = $member->f('member_id');
		$department_id = $member->f('department_id');
	}

//	get computer id
	$computer = $dbu->query("SELECT * FROM computer WHERE name = '".$json->computer."'");
	if($computer->next()){
		$computer_id = $computer->f('computer_id');
	}

//	prepare software list
$softwarelist = array();
	foreach ($json->software as $k=>$v){
	if ($v->name){
		$publisher = $v->publisher?$v->publisher:'-';
		$install = $v->install?$v->install:'-';
		$softwarelist[] = array('name'=>base64_encode($v->name), 'publisher'=>base64_encode($publisher), 'install'=>base64_encode($install));
		// $softwarelist[] = array('name'=>'nameee', 'publisher'=>'publisher', 'install'=>'install');
		}
	}
	$s_softwarelist = serialize($softwarelist);

	
// $dbu->query("DELETE FROM `cyclope`.`inventory` WHERE member_id = '".$member_id."' AND computer_id = '".$computer_id."'");
$dbu->query("REPLACE INTO `inventory` (
		`inventory_id` , `member_id` , `computer_id` ,`last_updated` , `ip` , `comptype` , `os` , `cpu` , `ram` , `hdd` , `monitor` , `software`
	)
	VALUES (
		NULL , '".$member_id."', '".$computer_id."', ".time().", '".$json->ip."', '".$json->comptype."', '".$json->os."', '".$json->cpu."', '".$json->ram."', '".$json->hdd."', '".$json->monitor."', '".$s_softwarelist."'
	)");




//	================================================================================
//	================================================================================
//	================================================================================
//	================================================================================
        //end the timer
                        $mtime = microtime();
                        $mtime = explode(" ",$mtime);
                        $mtime = $mtime[1] + $mtime[0];
                        $endtime = $mtime;
        //      print the timer
                        $totaltime = ($endtime - $starttime);
$dbu->disconnect();