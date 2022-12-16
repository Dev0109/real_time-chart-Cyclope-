<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => "update.html"));

include_once(CURRENT_VERSION_FOLDER."misc/json.php");
$current_version = $dbu->field("SELECT value FROM settings WHERE constant_name='SERVER_VERSION'");
$cyclope_json_version = @file_get_contents(LATEST_RELEASE_LOCATION);
	
$jsonDecoder = new Services_JSON();
$cyclope = $jsonDecoder->decode($cyclope_json_version);
	
if($cyclope->version == $current_version)
{
	header('Location: index.php?pag=updates');
}
$ft->assign('NOTES',$cyclope->notes);
$bottom_includes .='<script type="text/javascript" src="ui/update-ui.js"></script>';
$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');