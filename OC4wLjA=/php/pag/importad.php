<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => "importad.html"));
$dbu = new mysql_db();
include_once(ADMIN_PATH."adldap/adLDAP.php");
global $bottom_includes;
$folder = CURRENT_VERSION_FOLDER;

$base_dn = $dbu->field("SELECT value FROM settings WHERE constant_name='BASE_DN'");
$account_suffix = $dbu->field("SELECT value FROM settings WHERE constant_name='ACCOUNT_SUFFIX'");
$domain_controllers = $dbu->field("SELECT value FROM settings WHERE constant_name='DOMAIN_CONTROLLERS'");
$admin_username = $dbu->field("SELECT value FROM settings WHERE constant_name='ADMIN_USERNAME'");
$admin_password = $dbu->field("SELECT value FROM settings WHERE constant_name='ADMIN_PASSWORD'");
$protocol = $dbu->field("SELECT value FROM settings WHERE constant_name='PROTOCOL'");
$use_ssl = false;
$useSSO = false;
$useTLS = false;
if ($protocol == 'ldaps') {
	$use_ssl = true;
	$useSSO = true;
	$useTLS = true;
}

// $base_dn = 'DC=amplusnet,DC=ro';
// $account_suffix = '@amplusnet.ro';
// $domain_controllers = "appsrv3";	//	or IP: 192.168.1.125
// $admin_username = "Administrator";
// $admin_password = "DOMCTRLhilton2014@";

// http://adldap.sourceforge.net/wiki/doku.php?id=ldap_over_ssl#tell_apache_how_to_use_ldaps

// $base_dn = $_POST['base_dn'];
// $account_suffix = $_POST['account_suffix'];
// $domain_controllers = $_POST['domain_controllers'];
// $admin_username = $_POST['admin_username'];
// $admin_password = $_POST['admin_password'];

//	create ldap and ldap login. only message on err
$ldap = new adLDAP(array('base_dn'=>$base_dn, 'account_suffix'=>$account_suffix, use_ssl=>$use_ssl, use_tls=>$useTLS, 'sso'=>$useSSO, 'domain_controllers'=>array($domain_controllers)));
$ldap_authUser = $ldap->user()->authenticate($admin_username, $admin_password);
if ($ldap_authUser != true) {
	$step = 1;
	$ft->assign(array(
		'MESSAGE' => get_error("User authentication unsuccessful: " . $ldap->getLastError() . ' Go to <a href="/index.php?pag=settings#adsettings">Administration > Settings > AD Settings</a> and set the correct credentials.'),
		'OULIST' => 'style="display:none;"',
		'STEP' => $step,
	));
} else {
	$step = 2;
	$ft->assign(array(
		'MESSAGE' => get_error("User authentication successful.",'success'),
		'STEP' => $step,
	));
}
	//	import the stuff
	$userdata = (prepareForImport($_POST['unitlist'],$ldap));
	if($users = populateDepartmentsAndUsers($userdata))
	{
		$groups = array();
		foreach ($_POST['unitlist'] as $k => $v) {
			array_push($groups,'"' . str_replace(array(' ',','), '' , $v) . '"');
		}
		$dbu->query("UPDATE settings SET long_value='". addslashes(join(',',$groups)) ."' WHERE constant_name='IMPORTAD_PRESET'");
		$ft->assign(array(
			'MESSAGE' => get_error("Users imported.",'success'),
		));
	}

if ($step==2)
{
	//	create the form tree
	$data=ldap_ou_walkTree('root',$ldap);
	$jsondata = json_encode($data);
}

$ft->assign(array(
	'BASE_DN' => $base_dn,
	'ACCOUNT_SUFFIX' => $account_suffix,
	'DOMAIN_CONTROLLERS' => $domain_controllers,
	'ACCOUNT_ADMIN' => $admin_username,
	'ACCOUNT_PASSWORD' => $admin_password,
));

$ft->assign(array(
	'PAGE_TITLE' => $page_title,
	'ALERT_NAME' => $glob['name'],
	'ALERT_DESCRIPTION' => $glob['description'],
	'ACT' => $func_name,
	'ALERT_ID' => $glob['alert_id'],
	'PAG' => $glob['pag']
));

$groups = $dbu->field("SELECT long_value FROM settings WHERE constant_name='IMPORTAD_PRESET'");
$groups = '"ui":{"initially_select" : ['.stripslashes($groups).']},';
$bottom_includes.= <<<JS
	<script type="text/javascript">
	$(function(){
		$('#treeselect').jstree({"themes" : {"theme" : "white","dots" : true,"icons" : true},
				"types" : {
				"valid_children":[ "root" ],
				"types" : {
					"root" : {"icon" : { "image" : "{$folder}js/themes/white/root.png" },
							"valid_children" : [ "default","group" ],"hover_node" : true},
					"group" : {"icon" : { "image" : "{$folder}js/themes/white/group.png" },
							"valid_children" : [ "default"],	"hover_node" : true},				
					"default" : {"icon" : { "image" : "{$folder}js/themes/white/default.png" },
							"valid_children" : "none","hover_node": true}
				}},
				{$groups}
				"json_data": {"data": {$jsondata}},
				"plugins" : [ "themes","json_data","types","ui",'checkbox2' ]
		});
		{$alert_active}
	});
	</script>
JS;
$bottom_includes.='
<script type="text/javascript">flobn.register("genesis",new Date("'.date('n/j/Y',$dates['genesis']).'"));</script><script type="text/javascript" src="ui/importad-ui.js"></script>';
$ft->parse('CONTENT','main');
//$ft->fastprint('CONTENT');
return $ft->fetch('CONTENT');