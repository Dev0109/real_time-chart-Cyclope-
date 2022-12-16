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
echo $dbu->f('folder');