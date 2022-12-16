<?php
error_reporting(0);
set_time_limit(0);
ignore_user_abort(true);
$dbu = new mysql_db();
//	execute ldap cron
$ou_checked = $dbu->field("SELECT long_value FROM settings WHERE constant_name='OU_CHECKED'");
if($ou_checked != '')
{
	$base_dn = $dbu->field("SELECT value FROM settings WHERE constant_name='BASE_DN'");
	$account_suffix = $dbu->field("SELECT value FROM settings WHERE constant_name='ACCOUNT_SUFFIX'");
	$domain_controllers = $dbu->field("SELECT value FROM settings WHERE constant_name='DOMAIN_CONTROLLERS'");
	$admin_username = $dbu->field("SELECT value FROM settings WHERE constant_name='ADMIN_USERNAME'");
	$admin_password = $dbu->field("SELECT value FROM settings WHERE constant_name='ADMIN_PASSWORD'");
	include_once(ADMIN_PATH."adldap/adLDAP.php");
	$ldap = new adLDAP(array('base_dn'=>$base_dn, 'account_suffix'=>$account_suffix, 'domain_controllers'=>array($domain_controllers)));
	$userdata = (prepareForImport('update',$ldap));
	populateDepartmentsAndUsers($userdata);
}