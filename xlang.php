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

//get language ID
$dbu->query("SELECT value FROM settings WHERE constant_name = 'LANGUAGE_ID'");
$dbu->move_next();
$lang_id = $dbu->f('value');

$dbu->query("SELECT shortcode FROM language where language_id = ".$lang_id);
$dbu->move_next();
$shortcode = $dbu->f('shortcode');
$shortcode = !empty($shortcode) ? strtoupper($shortcode) : 'EN';

define('LANG',$shortcode);


include(CURRENT_VERSION_FOLDER.'lang/lang_'.strtolower(LANG).'.php');
if(!isset($lang) || !is_array($lang)){
	return '';
}
header('Content-type:text/javascript');
echo 'flobn.lang.load('.json_encode($lang).')';
exit();