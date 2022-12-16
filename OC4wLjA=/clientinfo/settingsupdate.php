<?php
header('Content-type:text/plain');
include('../../config/config.php');
define('INSTALLPATH','../misc/');
include('../misc/cls_mysql_db.php');
include("../misc/json.php");

/*if(!isset($_POST['report'])){
	echo 'false';
	return ;
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
/*$json = json_decode($_POST['report']);*/

$report = urldecode(str_replace( "\0","",iconv ("UTF-8","ASCII", $report)));
$json = $jsonDecoder->decode($report);

$dbu = new mysql_db();

$dbu->query("SELECT member_id FROM member WHERE logon = '".$json->info->username."'");
$dbu->move_next();
$member_id = $dbu->f('member_id');


$dbu->query("SELECT * FROM computer WHERE name = '".$json->info->computer."'");
$dbu->move_next();
$computer = array(
	'computer_id' => $dbu->f('computer_id'),
	'altered' => $dbu->f('altered'),
	'precision' => $dbu->f('precision'),
	'connectivity' => $dbu->f('connectivity'),
	'idlefactor' => $dbu->f('idlefactor')
);

$computer_id = $computer['computer_id'];

//do we haz them?
if(empty($member_id) || empty($computer_id) || (!empty($computer_id) && $computer['altered'] == 0)){
	echo 'false';
	return ;
}


//we now have computer and member let's see if they go togheter
/*$dbu->query("SELECT computer.altered FROM computer
			INNER JOIN computer2member ON computer2member.computer_id = computer.computer_id
			WHERE computer.computer_id = ".$computer_id."
			AND computer2member.member_id = ".$member_id);
if(!$dbu->move_next()){
	echo 'false';	
	return ;
}*/
//send out the ini
echo "[General]

ServerName=".$_SERVER['SERVER_NAME']."
ServerPort=".$_SERVER['SERVER_PORT']."
MonitorFiles=1
ManualInstall=1
[Monitoring]
Precision=".$computer['precision']."
Connection=".$computer['connectivity']."
Idle=".$computer['idlefactor'];
/*$dbu->query("UPDATE computer SET altered = 0 WHERE computer_id = ".$computer_id);*/