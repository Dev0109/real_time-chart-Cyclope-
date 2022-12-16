<?php
header('Content-type:text/plain');
$dbu = new mysql_db();
$data = array();

//get the categories
$data = build_appform_dd($_GET['appid'],1);
echo $data;
exit();

