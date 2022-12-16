<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => $glob['pag'].".html"));

$ft->assign(array(
	'GENERALSETTINGS' => include(CURRENT_VERSION_FOLDER.'php/ajax/xgeneralsettings.php'),
	'TREE' => parseCMSTag('[!STATS_SIDEBAR!]'),
	'WORKSCHEDULE' => include(CURRENT_VERSION_FOLDER.'php/ajax/xworkschedule.php'),
	'CASUALTY' => include(CURRENT_VERSION_FOLDER.'php/ajax/xcasualty.php'),
	'UPDATESETTINGS' => include(CURRENT_VERSION_FOLDER.'php/ajax/xupdatesettings.php'),
	'MESSAGE' => get_error($glob['error']),
));


$smtp_server = $dbu->field("SELECT value FROM settings WHERE constant_name='SMTP_SERVER'");
$smtp_user = $dbu->field("SELECT value FROM settings WHERE constant_name='SMTP_USER'");
$smtp_password = $dbu->field("SELECT value FROM settings WHERE constant_name='SMTP_PASSWORD'");
$smtp_port = $dbu->field("SELECT value FROM settings WHERE constant_name='SMTP_PORT'");
$authorisation = $dbu->field("SELECT value FROM settings WHERE constant_name='AUTHORISATION'");
$smtp_mailer = $dbu->field("SELECT value FROM settings WHERE constant_name='SMTP_MAILER'");
$ssl = $dbu->field("SELECT value FROM settings WHERE constant_name='SSL'");
$ft->assign(array(
	'SMTP_SERVER' => $glob['smtp_server'] ? $glob['smtp_server'] : $smtp_server,
	'SMTP_USER' => $glob['smtp_user'] ? $glob['smtp_user'] : $smtp_user,
	'SMTP_PASSWORD' => $glob['smtp_password'] ? $glob['smtp_password'] : $smtp_password,
	'SMTP_PORT' => $glob['smtp_port'] ? $glob['smtp_port'] : $smtp_port,
	'AUTHORISATION' => $authorisation == 1 ? 'checked="checked"' : '',
	'SMTP_MAILER_SMTP' => $smtp_mailer == 'smtp' ? 'checked="checked"' : '',
	'SMTP_MAILER_MAILER' => $smtp_mailer == 'mail' ? 'checked="checked"' : '',
	'SMTP_MAILER_NONE' =>  (!in_array($smtp_mailer, array('smtp','mail'))) ? 'checked="checked"' : '',
	'SSL' => $ssl == 1 ? 'checked="checked"' : '',
));

$base_dn = $dbu->field("SELECT value FROM settings WHERE constant_name='BASE_DN'");
$account_suffix = $dbu->field("SELECT value FROM settings WHERE constant_name='ACCOUNT_SUFFIX'");
$domain_controllers = $dbu->field("SELECT value FROM settings WHERE constant_name='DOMAIN_CONTROLLERS'");
$admin_username = $dbu->field("SELECT value FROM settings WHERE constant_name='ADMIN_USERNAME'");
$admin_password = $dbu->field("SELECT value FROM settings WHERE constant_name='ADMIN_PASSWORD'");
$protocol = $dbu->field("SELECT value FROM settings WHERE constant_name='PROTOCOL'");
$ft->assign(array(
	'BASE_DN' => $glob['base_dn'] ? $glob['base_dn'] : $base_dn,
	'ACCOUNT_SUFFIX' => $glob['account_suffix'] ? $glob['account_suffix'] : $account_suffix,
	'DOMAIN_CONTROLLERS' => $glob['domain_controllers'] ? $glob['domain_controllers'] : $domain_controllers,
	'ADMIN_USERNAME' => $glob['admin_username'] ? $glob['admin_username'] : $admin_username,
	'ADMIN_PASSWORD' => $glob['admin_password'] ? $glob['admin_password'] : $admin_password,
	'LDAP' => $protocol != 'ldaps' ? 'checked="checked"' : '',
	'LDAPS' => $protocol == 'ldaps' ? 'checked="checked"' : '',
));


global $bottom_includes;
$bottom_includes .= '<script type="text/javascript" src="ui/settings-ui.js"></script>';
//if(count(explode('-',$glob['f'])) == 1){
//	$bottom_includes.= '<script type="text/javascript">flobn.register("thouShallNotMove",0,true);</script>';	
//	$glob['thouShallNotMove'] = 0;
//}else{
//	$bottom_includes.= '<script type="text/javascript">flobn.register("thouShallNotMove",1,true);</script>';	
//	$glob['thouShallNotMove'] = 1;
//}

$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');