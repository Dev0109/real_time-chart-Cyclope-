<?php

error_reporting(0);
include_once('../config/config.php');
ignore_user_abort(true);
set_time_limit(0);
header('Content-type:text/plain');

define('CURRENT_VERSION_FOLDER','');

include_once(CURRENT_VERSION_FOLDER . 'php/gen/startup.php');
include_once(CURRENT_VERSION_FOLDER . "module_config.php");
include_once(CURRENT_VERSION_FOLDER . "misc/cyclope_lib.php");
include_once(CURRENT_VERSION_FOLDER . "misc/cls_mysql_db.php");
include_once(CURRENT_VERSION_FOLDER . "misc/stlib.php");
include_once(CURRENT_VERSION_FOLDER . "misc/cls_ft.php");
include_once(CURRENT_VERSION_FOLDER . "misc/cyclope_lib.php");
include_once(CURRENT_VERSION_FOLDER . "misc/json.php");

define('ADMIN_PATH',$script_path);
session_start();
set_time_limit(0);

$dbu = new mysql_db();
$tmp = ini_get('upload_tmp_dir');
$site_url = $dbu->field("SELECT long_value FROM settings WHERE constant_name ='SITE_URL'");
$folder = $dbu->field("SELECT folder FROM `update` WHERE active = 1");
$runincron = 1;

debug_log(CURRENT_VERSION_FOLDER . 'ENTERED IN CRON ================================= at ' . date('l jS \of F Y h:i:s A'),'cronlog');

//	TIME TABLE
if($_GET['force'] && file_exists(CURRENT_VERSION_FOLDER . 'php/cron/tasks/' . $_GET['force'] . '.php')) {
echo $_GET['force'] . " initiated...\n";
	include(CURRENT_VERSION_FOLDER . 'php/cron/tasks/' . $_GET['force'] . '.php');
	echo 'php/cron/tasks/' . $_GET['force'] . '.php was run at ' . date('l jS \of F Y h:i:s A') . "\n";
	debug_log(CURRENT_VERSION_FOLDER . 'php/cron/tasks/' . $_GET['force'] . '.php was run at ' . date('l jS \of F Y h:i:s A'),'forceparser');
	exit;
}

if (cronRunInterval('UNIVERSAL_CRON_15MIN',15*60) || $_REQUEST['ignore']) {
	debug_log(CURRENT_VERSION_FOLDER . 'ENTERED 15','cronlog');
	include_once(CURRENT_VERSION_FOLDER . 'php/cron/tasks/bootstrap.php');
	debug_log(CURRENT_VERSION_FOLDER . 'php/cron/tasks/bootstrap.php was run at ' . date('l jS \of F Y h:i:s A'),'cronlog');
	include_once(CURRENT_VERSION_FOLDER . 'php/cron/tasks/syncinit.php');
	debug_log(CURRENT_VERSION_FOLDER . 'php/cron/tasks/syncinit.php was run at ' . date('l jS \of F Y h:i:s A'),'cronlog');
	include_once(CURRENT_VERSION_FOLDER . 'php/cron/tasks/parsermaster.php');
	debug_log(CURRENT_VERSION_FOLDER . 'php/cron/tasks/parsermaster.php was run at ' . date('l jS \of F Y h:i:s A'),'cronlog');
	include_once(CURRENT_VERSION_FOLDER . 'php/cron/tasks/alertmaster.php');
	debug_log(CURRENT_VERSION_FOLDER . 'php/cron/tasks/alertmaster.php was run at ' . date('l jS \of F Y h:i:s A'),'cronlog');
	//include_once(CURRENT_VERSION_FOLDER . 'php/cron/tasks/sync_fix.php');
	//debug_log(CURRENT_VERSION_FOLDER . 'php/cron/tasks/sync_fix.php was run at ' . date('l jS \of F Y h:i:s A'),'cronlog');
}

if (cronRunPeriodical('UNIVERSAL_CRON_DAILY','day') || $_REQUEST['ignore']) {
	debug_log(CURRENT_VERSION_FOLDER . 'ENTERED DAILY','cronlog');
	// include_once(CURRENT_VERSION_FOLDER . 'php/cron/tasks/optimizeproductivity.php');
	// debug_log(CURRENT_VERSION_FOLDER . 'php/cron/tasks/optimizeproductivity.php was run at ' . date('l jS \of F Y h:i:s A'),'cronlog');
	include_once(CURRENT_VERSION_FOLDER . 'php/cron/tasks/automaticupdates.php');
	debug_log(CURRENT_VERSION_FOLDER . 'php/cron/tasks/automaticupdates.php was run at ' . date('l jS \of F Y h:i:s A'),'cronlog');
	include_once(CURRENT_VERSION_FOLDER . 'php/cron/tasks/ldap.php');
	debug_log(CURRENT_VERSION_FOLDER . 'php/cron/tasks/ldap.php was run at ' . date('l jS \of F Y h:i:s A'),'cronlog');
	include_once(CURRENT_VERSION_FOLDER . 'php/cron/tasks/emailreportdaily.php');
	debug_log(CURRENT_VERSION_FOLDER . 'php/cron/tasks/emailreportdaily.php was run at ' . date('l jS \of F Y h:i:s A'),'cronlog');
	include_once(CURRENT_VERSION_FOLDER . 'php/cron/tasks/extrareportdaily.php');
	debug_log(CURRENT_VERSION_FOLDER . 'php/cron/tasks/extrareportdaily.php was run at ' . date('l jS \of F Y h:i:s A'),'cronlog');
	include_once(CURRENT_VERSION_FOLDER . 'php/cron/tasks/sync_appcat.php');
	debug_log(CURRENT_VERSION_FOLDER . 'php/cron/tasks/sync_appcat.php was run at ' . date('l jS \of F Y h:i:s A'),'cronlog');
	include_once(CURRENT_VERSION_FOLDER . 'php/cron/tasks/trimsessionlog.php');
	debug_log(CURRENT_VERSION_FOLDER . 'php/cron/tasks/trimsessionlog.php was run at ' . date('l jS \of F Y h:i:s A'),'cronlog');
	include_once(CURRENT_VERSION_FOLDER . 'php/cron/tasks/autodeletelogshalf.php');
	debug_log(CURRENT_VERSION_FOLDER . 'php/cron/tasks/autodeletelogshalf.php was run at ' . date('l jS \of F Y h:i:s A'),'cronlog');

		if (cronRunPeriodical('UNIVERSAL_CRON_WEEKLY','week') || $_REQUEST['ignore']) {
			debug_log(CURRENT_VERSION_FOLDER . 'ENTERED WEEKLY','cronlog');
			include_once(CURRENT_VERSION_FOLDER . 'php/cron/tasks/emailreportweekly.php');
			debug_log(CURRENT_VERSION_FOLDER . 'php/cron/tasks/emailreportweekly.php was run at ' . date('l jS \of F Y h:i:s A'),'cronlog');
			include_once(CURRENT_VERSION_FOLDER . 'php/cron/tasks/extrareportweekly.php');
			debug_log(CURRENT_VERSION_FOLDER . 'php/cron/tasks/extrareportweekly.php was run at ' . date('l jS \of F Y h:i:s A'),'cronlog');

				if (cronRunPeriodical('UNIVERSAL_CRON_MONTHLY','month') || $_REQUEST['ignore']) {
					debug_log(CURRENT_VERSION_FOLDER . 'ENTERED MONTHLY','cronlog');
					include_once(CURRENT_VERSION_FOLDER . 'php/cron/tasks/emailreportmonthly.php');
					debug_log(CURRENT_VERSION_FOLDER . 'php/cron/tasks/emailreportmonthly.php was run at ' . date('l jS \of F Y h:i:s A'),'cronlog');
					include_once(CURRENT_VERSION_FOLDER . 'php/cron/tasks/extrareportmonthly.php');
					debug_log(CURRENT_VERSION_FOLDER . 'php/cron/tasks/extrareportmonthly.php was run at ' . date('l jS \of F Y h:i:s A'),'cronlog');
				}
		}
}
// else {
	// debug_log(CURRENT_VERSION_FOLDER . 'DID not enter in daily at ' . date('l jS \of F Y h:i:s A'),'cronlog');
// }