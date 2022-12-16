<?php
/************************************************************************
* @Author: Tinu Coman
***********************************************************************/
include_once(CURRENT_VERSION_FOLDER."module_config.php");
include_once(CURRENT_VERSION_FOLDER."php/gen/startup.php");

session_start();
if(!isset($glob['pag'])){
	$glob['pag'] = 'soon';
}
include(CURRENT_VERSION_FOLDER.'misc/json.php');
if(DEBUG_CONTEXT){
	error_reporting(E_ALL & ~E_NOTICE);
}else{
	error_reporting(0);
	
}
$folder = CURRENT_VERSION_FOLDER.'help/'.strtolower(LANG).'/'; 
if(!is_dir($folder)){
	$folder = CURRENT_VERSION_FOLDER.'help/en/';
}

/*if(!file_exists($folder.$glob['pag'].'.html'))*/
if(!isset($glob['pag']) || strstr($glob['pag'], '/') || strstr($glob['pag'], '\\')){
	$glob['pag'] = 'soon';
}

$pag = str_replace('{CURRENT_VERSION_FOLDER}',CURRENT_VERSION_FOLDER,file_get_contents($folder.$glob['pag'].'.html'));


if(in_array($glob['pag'],array('tour1','tour2','tour3'))){
	header('Content-type: text/json');
	echo json_encode(array('innerHTML' => $pag));
	exit();
}
$page_access[$glob['pag']]['module'] = 'help';


$ftm=new ft(CURRENT_VERSION_FOLDER.'help/');
$ftm->define(array('main'=>'hlp_template.html'));

if($_SESSION[U_ID]){
	$_db = new mysql_db();
	$memberName = $_db->field("SELECT CONCAT(first_name,' ',last_name) as name FROM member WHERE member_id = ?",$_SESSION[U_ID]);
	$helpheader=substr($memberName,0,1);
	$ftm->assign('USER',$helpheader);
	
	if(($_SESSION[ACCESS_LEVEL] == ADMIN_LEVEL) && GO_TO_TRIAL == 1)
	{
		$ftm->assign('ACCOUNT_LINK','#');
	}
	else 
	{
		$ftm->assign('ACCOUNT_LINK','index.php?pag=myaccount');
	}
}
$ftm->assign('MAIN_MENU',parseCMSTag('[!MAIN_MENU!]'));
$ftm->assign('META_TITLE',$site_meta_title);
$ftm->assign('META_KEYWORDS',$site_meta_keywords);
$ftm->assign('META_KEYWORDS',$site_meta_keywords);
$ftm->assign('PAGE',$pag);
$ftm->assign('CURRENT_VERSION_FOLDER',CURRENT_VERSION_FOLDER);
$ftm->parse('CONTENT','main');
echo compress_output($ftm->fetch('CONTENT'));//this makes for better javascript dom manipulaiton
$dbu = new mysql_db();
$dbu->disconnect();