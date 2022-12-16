<?php

if(!defined('CURRENT_VERSION_FOLDER'))
{
	define('CURRENT_VERSION_FOLDER','');
	include_once('../config/config.php');
}

include_once(CURRENT_VERSION_FOLDER."misc/cls_mysql_db.php");
include_once(CURRENT_VERSION_FOLDER."misc/json.php");
include_once(CURRENT_VERSION_FOLDER."misc/cyclope_lib.php");

// member2manage_Rebuild();

$dbu = new mysql_db();

//	get info
//	==========================================================
$tmplog_all = $dbu->field("SELECT count(`tmp_id`) FROM `tmplog`");
$tmplog_unparsed = $dbu->field("SELECT count(`tmp_id`) FROM `tmplog` WHERE `parsed` = 0");
$tmplog_failed = $dbu->field("SELECT count(`tmp_id`) FROM `tmplog` WHERE `parsed` IN (8,9)");

//	render info
//	==========================================================

echo '<link href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css" rel="stylesheet"><link href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap-glyphicons.css" rel="stylesheet"><script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js" ></script><script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>';
echo "Statistics:"."<br />";
echo "--- LOGS:"."<br />";
echo "--- ---- all: ".$tmplog_all."<br />";
echo "--- ---- unparsed: ".$tmplog_unparsed."<br />";
echo "--- ---- failed: ".$tmplog_failed."<br />";
echo "<hr />";
echo "<a class='btn btn-large btn-block btn-success' target='_blank' href='".CURRENT_VERSION_FOLDER."parser.php?batch=5000'>process 5000 unparsed logs (this will take time check back later)</a>"."<br />";
echo "<a class='btn btn-large btn-block btn-danger' target='_blank' href='".CURRENT_VERSION_FOLDER."parser.php?repair=1&batch=5000'>repair 5000 failed logs (this will take time check back later)</a>"."<br />";
echo "<a class='btn btn-large btn-block btn-info' target='_blank' href='".CURRENT_VERSION_FOLDER."cron.php?force=bootstrap'>clean parsed logs</a>"."<br />";
echo "<a class='btn btn-large btn-block btn-primary' target='_blank' href='".CURRENT_VERSION_FOLDER."cron.php'>run cron manually</a>"."<br />";

echo '<script src="//code.jquery.com/jquery-1.10.2.js"></script>';
echo '<div id="ajaxcontent"></div><script>$("#ajaxcontent").load("index_ajax.php?pag=chart_productivityreport");</script>';