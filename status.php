<?php
error_reporting(0);
include('config/config.php');
include('libs/mysql_light.php');
$dbu = new mysql_light();
$dbu->query('SELECT * FROM `update` WHERE active = 1');
if(!$dbu->move_next()){
	return false;
}
define('CURRENT_VERSION_FOLDER',$dbu->f('folder').'/');

$dbu->query('SELECT table_name "Table", 
( data_length + index_length ) / 1024 / 1024 "Table Size in MB"
FROM information_schema.TABLES 
WHERE table_schema LIKE  "%endd1_cyclope%"');
while ($dbu->move_next()){
	echo "Table : ".$dbu->f('Table')."<br>";
	echo "Table Size in MB: ".$dbu->f('Table Size in MB')."<br>";
}

$dbu->query('SELECT COUNT(*) "COUNT" FROM tmplog');
var_dump("tmplog", $dbu);
if(!$dbu->move_next()){
	return false;
}
echo "<br>Data Base tmplog Size: ".$dbu->f('COUNT')."<br>";


$dbu->query('SELECT COUNT(*) "Log Count", name "Computer Name"
FROM tmplog, computer
WHERE log LIKE CONCAT("%", CONCAT(name, "%")) 
GROUP BY name');
while ($dbu->move_next()){
	echo "Computer Name : ".$dbu->f('Computer Name')."<br>";
	echo "Log Count: ".$dbu->f('Log Count')."<br>";
}

$dbu->query("DELETE FROM tmplog WHERE parsed = 1");

$dbu->query('SELECT COUNT(*) "COUNT" FROM tmplog');
if(!$dbu->move_next()){
	return false;
}
echo "<br>Data Base tmplog Size: ".$dbu->f('COUNT')."<br>";

$dbu->query('TRUNCATE tmplog');