<?php
header('Content-type:text/plain');
include('../../config/config.php');
define('INSTALLPATH','../misc/');
include('../misc/cls_mysql_db.php');
include("../misc/json.php");

/*if(!isset($_POST['report'])){
	echo 'false';
	return false;
}*/

$report = file_get_contents('php://input');

$jsonDecoder = new Services_JSON();
/*
{
   "info" : {
      "clientversion" : "",
      "computer" : "ALLIANZ-PC",
      "connectivity" : 0,
      "domain" : "",
      "idlefactor" : 0,
      "ip" : "192.168.1.126",
      "precision" : 0,
      "username" : "Bayern"
   }
}

*/

$report = urldecode(str_replace( "\0","",iconv ("UTF-8","ASCII", $report)));
$json = $jsonDecoder->decode($report);

/*$json = $jsonDecoder->decode($_POST['report']);*/

$dbu = new mysql_db();

$dbu->query("SELECT * FROM computer WHERE name = '".$json->info->computer."'");
$dbu->move_next();
$computer = array(
	'computer_id' => $dbu->f('computer_id'),
	'altered' => $dbu->f('altered'),
	'precision' => $dbu->f('precision'),
	'connectivity' => $dbu->f('connectivity'),
	'idlefactor' => $dbu->f('idlefactor')
);


/*$dbu->query("SELECT logon FROM uninstall WHERE computer = '".$json->info->computer."'");
while ($dbu->move_next())
{
	$users .= $dbu->f('logon');
	$users .= ",";
}	*/



$dbu->query("SELECT * FROM uninstall WHERE logon = '".$json->info->username."'
									   AND computer = '".$json->info->computer."'");
if($dbu->move_next()){
	echo "[General]|
	ServerName=".$_SERVER['SERVER_NAME']."
	ServerPort=".$_SERVER['SERVER_PORT']."
	Uninstaled=1
	MonitorFiles=1
	[Monitoring] 
	Precision=".(empty($computer['precision']) ? "3" : $computer['precision'])."
	Connection=".(empty($computer['connectivity']) ? "1" : $computer['precision'])."
	Idle=".(empty($computer['idlefactor']) ? "15" : $computer['precision']);

	$dbu->query("UPDATE uninstall   SET uninstalled = 2 
								  WHERE logon = '".$json->info->username."'
									AND computer = '".$json->info->computer."'");
	return ;}

echo 'false';