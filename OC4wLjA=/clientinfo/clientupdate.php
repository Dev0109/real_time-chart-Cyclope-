<?php
header('Content-type:text/plain');
include('../../config/config.php');
define('INSTALLPATH','../misc/');
include('../misc/cls_mysql_db.php');

$dbu = new mysql_db();
echo $dbu->field("SELECT value FROM settings WHERE constant_name = 'CLIENT_LATESTVERSION'");