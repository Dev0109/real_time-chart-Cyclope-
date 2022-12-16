<?php
/************************************************************************
* @Author: Tinu Coman                                                   *
************************************************************************/
define('ADMIN_PATH',$script_path);
$glob = array();
/*if(DEBUG_CONTEXT){
	date_default_timezone_set('Europe/Bucharest');
}*/

foreach($_GET as $key => $value) {
	//$glob[$key]=mysql_real_escape_string(stripslashes($value));
	$glob[$key]=$value;
}

foreach($_POST as $key => $value){
	//$glob[$key] = !is_array($value) ? mysql_real_escape_string(stripslashes($value)) : $value;
	$glob[$key]=$value;
}

if (!$glob['session_id']) {
    session_start();
 }
else{
	session_id($glob['session_id']);
	session_start();
}


define('FTP_PASSWORD','cyc|0p3');


include_once(ADMIN_PATH."misc/cls_mysql_db.php");

$dbu = new mysql_db();
$dbu->query("SET NAMES 'utf8'");

include_once(ADMIN_PATH."misc/cls_ft.php");
include_once(ADMIN_PATH."misc/gen_lib.php");
include_once(ADMIN_PATH."misc/cms_front_lib.php");
include_once(ADMIN_PATH."misc/security_lib.php");
include_once(ADMIN_PATH."php/gen/func_perm.php");
include_once(ADMIN_PATH."php/gen/page_perm.php");
include_once(ADMIN_PATH."misc/stlib.php");
include_once(ADMIN_PATH."misc/cyclope_lib.php");
// include_once(ADMIN_PATH."misc/ftp_class.php");

//Global Variables
$menu_link_array=array();
$bottom_includes='';
$default_colors = array();

$dbu->query("SELECT theme_color.color FROM theme_color 
INNER JOIN theme ON theme.theme_id = theme_color.theme_id AND theme.selected=1
ORDER BY theme_color_id ASC");
while ($dbu->move_next()) {
	array_push($default_colors,$dbu->f('color'));
}

if($glob['l'])
{
	$_SESSION['LANG'] = $glob['l'];
	define('LANG',$glob['l']);
}
else if($_SESSION['LANG'])
{
	define('LANG',$_SESSION['LANG']);
}
else 
{
	//get language ID
	$lang_id = $dbu->field("SELECT value FROM settings WHERE constant_name = 'LANGUAGE_ID'");
	
	$shortcode = $dbu->field("SELECT shortcode FROM language where language_id = ?",$lang_id);
	$shortcode = !empty($shortcode) ? strtoupper($shortcode) : 'EN';
	
	define('LANG',$shortcode);
	
	$_SESSION['LANG'] = $shortcode;
	
}

if($glob['n'])
{
	$_SESSION['NUMBER_OF_ROWS'] = $glob['n'];
	define('NUMBER_OF_ROWS',$glob['n']);
}
else if($_SESSION['NUMBER_OF_ROWS'])
{
	define('NUMBER_OF_ROWS',$_SESSION['NUMBER_OF_ROWS']);
}
else 
{
	$number_of_rows = $dbu->field("SELECT value FROM settings WHERE constant_name = 'NUMBER_OF_ROWS'");
	if($number_of_rows)
		$_SESSION['NUMBER_OF_ROWS'] = $number_of_rows;	
}

if($glob['c'])
{
	$_SESSION['CHARACTER_SET'] = $glob['c'];
	define('CHARACTER_SET',$glob['c']);
}
else if($_SESSION['CHARACTER_SET'])
{
	define('CHARACTER_SET',$_SESSION['CHARACTER_SET']);
}
else 
{
	$character_set_id = $dbu->field("SELECT value FROM settings WHERE constant_name = 'CHARACTER_SET_ID'");
	if($character_set_id)
		$_SESSION['CHARACTER_SET'] = $character_set_id;	
}



