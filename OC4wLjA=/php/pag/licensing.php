<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => "licensing.html"));

$dbu = new mysql_db();

$client_info = unserialize($dbu->field("SELECT long_value FROM settings WHERE constant_name='CLIENT_INFO'"));
$total_computers =  $dbu->field("SELECT count(computer_id) FROM computer");

		//	PERMANENTUS
$jsonDecoder = new Services_JSON();
$data = $dbu->field("SELECT long_value FROM settings WHERE constant_name='LICENCEKEY'");
if($data)
{
	$licence = $jsonDecoder->decode(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, MCRYPT_PASSWORD, base64_decode($data), MCRYPT_MODE_ECB));		
}

$computers = $total_computers * 100 / AC;
$time = ( (time() - SD) / 86400 ) * 100 / ((ED - SD)/86400);


$ft->assign(array(
	'PAGE_TITLE' => $ft->lookup('Licensing'),
	'COMPANY_NAME' => $client_info['company_name'],
	'NAME' => $client_info['name'],
	'EMAIL' => $client_info['email'],
	'PHONE' => $client_info['phone'],
	'COUNTRY' => build_country_list($client_info['country']),
	'TIMEPREFIX' => LP == 1?$ft->lookup('Free tech support, updates and upgrades:'):$ft->lookup('Time Period:'),
	'START' => date('d/m/Y H:i A',SD),
	'END' => date('d/m/Y H:i A',ED),
	'COMPUTERS' => AC,
	'HAS_COMPUTERS' => $total_computers,
	'OVERLOW' => $total_computers > AC ? 'overflow': '',
		//	PERMANENTUS
	'PERMANENT' => ($licence->permanent==1?'YES':'NO'),
));


global $bottom_includes;
$bottom_includes.='
<script type="text/javascript">flobn.register("licensingcomputers","'.$computers.'");flobn.register("licensingtime","'.$time.'");</script><script type="text/javascript" src="ui/licensing-ui.js"></script>';

$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');