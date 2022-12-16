<?php
ignore_user_abort(true);
set_time_limit(0);
include('../config/config.php');
define('CURRENT_VERSION_FOLDER','');
error_reporting(E_ALL & ~ E_NOTICE);
include_once("module_config.php");
include_once("php/gen/startup.php");
include_once("misc/json.php");

if(!$glob['id']){
	$glob['id'] = 0;
}
$file_id = $glob['id'];

$dbu = new mysql_db();
$dbu2 = new mysql_db();
$i=0;
$dbu->query("select * from file where file_id >".$file_id." ");
//echo $dbu->records_count().' records';
while($dbu->move_next() && $i<10000){
	$dbu2->query("UPDATE file set `count`='".strlen($dbu->f('path'))."', first_letter='".substr($dbu->f('path'),0,3)."' where file_id=".$dbu->f('file_id'));
	echo "<br>".substr($dbu->f('path'),0,3).' '.$dbu->f('file_id');
	$i++;
}

if($i==0 || !$dbu->f('file_id')){
	echo "done";
	exit();
}
echo '<script>window.location.href = "file_table.php?id='.$dbu->f('file_id').'";</script>';
