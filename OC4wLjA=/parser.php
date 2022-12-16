<?php
error_reporting(0);
// error_reporting(E_ALL & ~ E_NOTICE);
// if(!isset($_POST['report']) || empty($_POST['report'])){
	// header("HTTP/1.0 404 Not Found");
	// exit();
// }
include_once('../config/config.php');
$parse_begin = time();
ignore_user_abort(true);
set_time_limit(0);

header('Content-type:text/plain');
if(DEBUG_CONTEXT){
	error_reporting(E_ALL & ~ E_NOTICE);
	date_default_timezone_set('Europe/Bucharest');
}

define('CURRENT_VERSION_FOLDER','');

include_once(CURRENT_VERSION_FOLDER . 'module_config.php');
include_once(CURRENT_VERSION_FOLDER . "misc/cyclope_lib.php");
include_once(CURRENT_VERSION_FOLDER . "misc/cls_mysql_db.php");
include_once(CURRENT_VERSION_FOLDER . "misc/json.php");
define('ADMIN_PATH',$script_path);
session_start();
$dbu = new mysql_db();


		set_time_limit(15 * $parser_sleep_high + (60));
		$now = time();
		if($_REQUEST['repair'] == 1){
			$log = $dbu->row("SELECT tmp_id,log FROM tmplog WHERE parsed IN (8,9) ORDER BY tmp_id DESC LIMIT 0,1");
			echo $log['tmp_id'];
		} else {
			$log = $dbu->row("SELECT tmp_id,log FROM tmplog WHERE parsed = 0 ORDER BY tmp_id DESC LIMIT 0,1");
		}
		$log_id = $log['tmp_id'];
		$log_data=$log['log'];
		$log_data = html_entity_decode($log_data);
		$log_data = str_replace (array("\r\n", "\n", "\r", "\t"), ' ', $log_data);	//	remove newlines
		$log_data = text_sanitize($log_data);	//	remove illegal characters
		if (!$log_id || !is_numeric($log_id) || $log_id == ''){
			exit();
		}
		$dbu->query("UPDATE tmplog SET parsed = 9 WHERE tmp_id = ".$log_id);
		if($_GET['repair'] == 1){
			// echo 'tested to 59';
			// echo $log_data;
		}
$json = json_decode($log_data);

if(is_null($json)){
	// header('HTTP/1.1 204 No Content');
	exit();
}
if(empty($json->user->username)){
	// header('HTTP/1.1 206 Partial Content');
	exit();
}

		
// $dbu->query("UPDATE settings SET value = 0 WHERE constant_name = 'FREEZE_TIME_NOW'");
$json->sendtime = $json->sendtime ? $json->sendtime : time();
$is_parser = 2;
include_once(CURRENT_VERSION_FOLDER . "misc/populate_db.php");
$dbu->disconnect();

if(($_REQUEST['batch'] > 1) OR (is_numeric($_REQUEST['time']) AND ((time() - $_REQUEST['time']) < (14*60))))
// if(($_REQUEST['batch'] > 0)
{
	if($_REQUEST['batch'] > 1){
		$batch = $_REQUEST['batch'] - 1;
	}
	$repair = $_REQUEST['repair'];
	echo "\n tmplog nr: " . $log_id . " has successfully started a loop parsing process. It's now safe to close this tab. The process will go on serversided until finished.";
	curl_request_async($site_url.$folder.'/parser.php',array('batch' => $batch,'time'=>$_REQUEST['time'],'repair'=>$repair));
} else {
	echo "tmplog nr: " . $log_id . " was successfully parsed.";
}