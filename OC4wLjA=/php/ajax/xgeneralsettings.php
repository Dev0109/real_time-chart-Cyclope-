<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftsx=new ft(ADMIN_PATH.MODULE."templates/");
$ftsx->define(array('main' => "xgeneralsettings.html"));

$dbu = new mysql_db();

$online_time_include = $dbu->field("SELECT value FROM settings WHERE constant_name='ONLINE_TIME_INCLUDE'");
$client_uninstall_password = $dbu->field("SELECT value FROM settings WHERE constant_name='CLIENT_UNINSTALL_PASSWORD'");
$language_id = $dbu->field("SELECT value FROM settings WHERE constant_name='LANGUAGE_ID'");
$number_of_rows = $dbu->field("SELECT value FROM settings WHERE constant_name='NUMBER_OF_ROWS'");
$character_set_id  = $dbu->field("SELECT value FROM settings WHERE constant_name='CHARACTER_SET_ID'");

if(strcmp($client_uninstall_password,"cyclopeadmin"))
	$ftsx->assign(array(
		'BEGIN_UNLESS' => '<!--',
		'END_UNLESS' => '-->'
	));
	
$ftsx->assign(array(
	'BROWSER_SELECTED' => $online_time_include == '3' ? 'checked="checked"': '',
	'BROWSER_CHAT_SELECTED' => $online_time_include == '1,3' ? 'checked="checked"': '',
	'LANGUAGE_ID' => build_languages_list($glob['language_id'] ? $glob['language_id'] : $language_id),
	'CLIENT_UNINSTALL_PASSWORD' => $glob['client_uninstall_password'] ? $glob['client_uninstall_password'] : $client_uninstall_password,
	'NUMBER_OF_ROWS' => bulid_simple_dropdown(array( 25 => 25 , 50 => 50, 100 => 100, 200 => 200, 500 => 500, 5000 => 5000, '[!L!]All[!/L!]' => '[!L!]All[!/L!]' ),is_numeric($number_of_rows) ? $number_of_rows : '[!L!]All[!/L!]'),
	'CHARACTER_SET' => build_character_sets_list($glob['character_set_id'] ? $glob['character_set_id'] : $character_set_id),
));

$ftsx->parse('CONTENT','main');
return $ftsx->fetch('CONTENT');