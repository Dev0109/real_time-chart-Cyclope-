<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => "updates.html"));
$ft->define_dynamic('template_row','main');

$dbu = new mysql_db();
	
include_once(CURRENT_VERSION_FOLDER."misc/json.php");
	
$current_version = $dbu->field("SELECT value FROM settings WHERE constant_name='SERVER_VERSION'");
$cyclope_json_version = @file_get_contents(LATEST_RELEASE_LOCATION);

$jsonDecoder = new Services_JSON();
$cyclope = $jsonDecoder->decode($cyclope_json_version);

$release_version = end(array_keys(get_object_vars($cyclope)));
$object = end(get_object_vars($cyclope));

$jsonDecoder = new Services_JSON();
$data = $dbu->field("SELECT long_value FROM settings WHERE constant_name='LICENCEKEY'");
if($data)
{
	$licence = $jsonDecoder->decode(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, MCRYPT_PASSWORD, base64_decode($data), MCRYPT_MODE_ECB));		
}
$automatic_updates = $dbu->field("SELECT value FROM settings WHERE constant_name = 'AUTOMATIC_UPDATES'");


if(($release_version > SERVER_VERSION)&&(!$automatic_updates))
{
		//	PERMANENTUS
	$expired = ((time() > $licence->end)?true:false);
	$ft->assign(array(
		'SELECTED' => '<img src="'.CURRENT_VERSION_FOLDER.($expired?'img/update.png':'img/update.png').'" />',
		'VERSION' => $release_version,
		'DOWNLOAD_DATE' => $ft->lookup('Available Now'),
		'NOTES' => $object->notes,
		'LINK' => 'index.php?pag=update',
		'LINK_CLASS' => 'settings',
		//	PERMANENTUS
		'LINK_NAME' => ($expired?$ft->lookup('Contact Sales'):$ft->lookup('Download & Install')),
		//	PERMANENTUS
		'LINK' => ($expired?'help.php?pag=support':'index.php?pag=update'),
		'HIDE_LINK' => '',	
		'CLASS' => 'newupdate',	
	));
	
	$ft->parse('TEMPLATE_ROW_OUT','.template_row');
}

$dbu->query("SELECT * FROM `update` WHERE active =1 ORDER BY active DESC, download_date DESC");

while($dbu->move_next())
{
		$ft->assign(array(
			'SELECTED' => $dbu->f('active') ? '<img src="'.CURRENT_VERSION_FOLDER.'img/selected.png" />' : '',
			'VERSION' => $dbu->f('version'),
			'DOWNLOAD_DATE' => date('d/m/y h:i A',$dbu->f('download_date')),
			'NOTES' => $dbu->f('notes'),
			'LINK' => 'index.php?pag='.$glob['pag'].'&act=update-rollback&upid='.$dbu->f('update_id').'',
			'LINK_CLASS' => 'settings',
			'LINK_NAME' => $ft->lookup('Rollback'),
			'HIDE_LINK' => (($dbu->f('active'))||($automatic_updates)) ? 'hide' : 'rollback',	
			'CLASS' => '',	
		));
	$ft->parse('TEMPLATE_ROW_OUT','.template_row');
}

$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');