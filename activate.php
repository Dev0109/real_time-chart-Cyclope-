<?php

error_reporting(0);
include('config/config.php');
if(DEBUG_CONTEXT){
	error_reporting(E_ALL & ~E_NOTICE);
	date_default_timezone_set('Europe/Bucharest');
}
include('libs/mysql_light.php');
if(!class_exists('mysql_db')){
	class mysql_db extends mysql_light {}
}

$dbu = new mysql_light();
$dbu->query('SELECT * FROM `update` WHERE active = 1');
if(!$dbu->move_next()){
	return false;
}
define('CURRENT_VERSION_FOLDER',$dbu->f('folder').'/');
$script_path = CURRENT_VERSION_FOLDER;

define('MODULE', '');
define('CUUUUUUUID', '11111');
define('UID', 'mid');
define('D_ID', 'd_id');
define('U_ID', 'm_id');
define('ACCESS_LEVEL', 'access_level');
define('ADMIN_LEVEL', 1);
define('MANAGER_LEVEL', 2);
define('LIMITED_LEVEL', 3);
define('EMPLOYEE_LEVEL', 4);
define('GO_TO_TRIAL',1);
define('ADMIN_PATH',$script_path);
define('LANG','EN');

$glob = array();
foreach($_GET as $key => $value) {
	$glob[$key]=$value;
}
foreach($_POST as $key => $value){
	$glob[$key]=$value;
//post there isn't value.
}

$glob['is_activation_page'] = true;
include(CURRENT_VERSION_FOLDER.'misc/cls_ft.php');
include(CURRENT_VERSION_FOLDER.'php/gen/func_perm.php');
include(CURRENT_VERSION_FOLDER.'php/gen/page_perm.php');
session_start();
if(isset($_SESSION[UID]) && $_SESSION[UID]){
	$user_level = $_SESSION[ACCESS_LEVEL];
	 if($glob['pag']=='login' && $glob['act'] != 'auth-logout'){
    	$glob['pag']='overview';
    }
}else{
	$user_level = 5;
	$glob['pag'] = 'login';
}
if(!in_array($glob['pag'],array('login','trial'))){
	$glob['pag'] = 'login';
}
$glob['success'] = false;

if(isset($glob['act']) && $glob['act'] && !isset($glob['skip_action'])){
	list($cls_name,$func_name ) = explode("-",$glob['act']);
	if(($cls_name)&&($func_name)&&(is_file(CURRENT_VERSION_FOLDER."classes/cls_".$cls_name.".php"))&&($func_access[$cls_name][$func_name])){
    	if($user_level<=$func_access[$cls_name][$func_name]){
    		include_once(CURRENT_VERSION_FOLDER."classes/cls_".$cls_name.".php");
            $cls_name= new $cls_name;
            if(!$cls_name->$func_name($glob)){
            	if($debug){
            		$glob['error'].="Failed to execute function $func_name";
            	}
            }else{
            	$glob['success'] = true;
            }
            unset($cls_name);
            unset($func_name);
        }else{
            if($debug){
        		$glob['error']= "You are not allowed to run this function !";
            }
            $glob['pag']= "login";
        }
    }else{
		if($debug){
			echo "Can not find cls_".$cls_name.".php file<BR>";
		}
    }
}

if(isset($glob['pag'])){
    if(isset($page_access[$glob['pag']]) && $page_access[$glob['pag']]['perm'] >= $user_level){
    	$folder = isset($page_access[$glob['pag']]['folder']) ? $page_access[$glob['pag']]['folder'].'/' : 'pag/';
		if(isset($page_access[$glob['pag']]['diff'])){
			if(is_array($page_access[$glob['pag']]['diff'])){
				$page = isset($page_access[$glob['pag']]['diff'][$user_level]) ? include(CURRENT_VERSION_FOLDER."php/".$folder.$user_level.$glob['pag'].".php") : include(CURRENT_VERSION_FOLDER."php/".$folder.$page_access[$glob['pag']]['perm'].$glob['pag'].".php");
				$page_access[$glob['pag']]['module'] = isset($page_access[$glob['pag']]['diff'][$user_level]) ? $page_access[$glob['pag']]['diff'][$user_level] : $page_access[$glob['pag']]['module'];
			}elseif ($user_level != $page_access[$glob['pag']]['perm']){
				$page = include(CURRENT_VERSION_FOLDER."php/".$folder.$user_level.$glob['pag'].".php");
				
				$page_access[$glob['pag']]['module'] = $page_access[$glob['pag']]['diff'];
			}else{
				$page = include(CURRENT_VERSION_FOLDER."php/".$folder.$glob['pag'].".php");
			}
		}else{
    		$page = include(CURRENT_VERSION_FOLDER."php/".$folder.$glob['pag'].".php");
		}
    }else{
    	$glob['pag'] = 'login';    	
    	$page = include(CURRENT_VERSION_FOLDER."php/pag/login.php");
    }
}
if(isset($site_module[$page_access[$glob['pag']]['module']]))
{
	$template_file=$site_module[$page_access[$glob['pag']]['module']]['template_file'];
	$current_module=$page_access[$glob['pag']]['module'];
}
else
{
//    $template_file='template02.html';
    $template_file='login_template.html';
}

$ftm=new ft(CURRENT_VERSION_FOLDER);
$ftm->define(array('main'=>$template_file));

$ftm->assign(array(
	strtoupper($page_access[$glob['pag']]['cssparent']) => 'active',
	strtoupper($glob['pag']) => 'active',
));

if($_SESSION[U_ID]){
	$_db = new mysql_db();
	$_db->query("SELECT CONCAT(first_name,' ',last_name) as name FROM member WHERE member_id = ".$_SESSION[U_ID]);
	$_db->move_next();
	$memberName = $_db->f('name');
	$ftm->assign('USER',$memberName);
	
	if(($_SESSION[ACCESS_LEVEL] == ADMIN_LEVEL) && GO_TO_TRIAL == 1)
	{
		$ftm->assign('ACCOUNT_LINK','#');
	}
	else 
	{
		$ftm->assign('ACCOUNT_LINK','index.php?pag=myaccount');
	}
	
	global $bottom_includes;
	$bottom_includes.= '<script type="text/javascript">flobn.register("UIMode","'.$_SESSION[ACCESS_LEVEL].'");flobn.register("CURRENT_VERSION_FOLDER","'.CURRENT_VERSION_FOLDER.'")</script>';
}
if($template_file == 'admin_template.html'){
	$ftm->assign(array(
		'MAIN_MENU' => include(CURRENT_VERSION_FOLDER.'php/get_mainmenu.php'),
		'SEC_MENU' => include(CURRENT_VERSION_FOLDER.'php/get_secmenu.php'),
	));
}


$ftm->assign('META_TITLE',$site_meta_title);
$ftm->assign('META_KEYWORDS',$site_meta_keywords);
$ftm->assign('META_DESCRIPTION',$site_meta_description);
$ftm->assign('PAGE',$page);
$ftm->assign('CURRENT_VERSION_FOLDER',CURRENT_VERSION_FOLDER);
$ftm->assign('ACCOUNT_LINK','#');
$bottom_includes = str_replace('"ui/','"'.CURRENT_VERSION_FOLDER.'ui/',$bottom_includes);
$ftm->assign('BOTTOM_INCLUDES',$bottom_includes);
$ftm->parse('CONTENT','main');
echo $ftm->fetch('CONTENT');

/*if($debug){
   require($script_path."misc/debug.php");
}*/
function get_error($message = '', $style = ''){
	
	global $glob;
	
	$message = $message ? $message : (isset($glob['error']) ? $glob['error']: '');
	
	$class = $style ? $style : ( $glob['success'] ? 'success' : 'error' );
	
	$out_str = '';
	if(strlen($message) !=0){
		$out_str = '<div class="'.$class.'">'.$message.'</div>';
	}
	return $out_str;
}

function build_country_list($selected)
{
        $db=new mysql_db;
        $db->query("SELECT * FROM country ORDER BY is_main DESC, name");
        $out_str="";
        while($db->move_next()){
                $out_str.="<option value=\"".$db->f('name')."\" ";
                $out_str.=($db->f('name')==$selected?" SELECTED ":"");
                $out_str.=">".$db->f('name')."</option>";
        }
        return $out_str;
}

function build_timezone_list($selected)
{
	$db=new mysql_db();
	if(!$selected){ 
	  $selected = 'Afghanistan';
	  echo '<style type="text/css">
        .time_zone_none {
            display: none !important; 
        }
        </style>';
	}
	$db->query("SELECT iso31661 FROM country WHERE name ='$selected'");
	$db->move_next();
	$timezone_identifiers = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $db->f('iso31661'));
	$i = 0;

	foreach ($timezone_identifiers as $timezone_identifier){
		$i++;
		$timezone = new DateTimeZone($timezone_identifier);
		$timezone_time = new DateTime('now', $timezone);
		$offset = $timezone->getOffset($timezone_time) / 3600;
		$out_str.="<option value=\"".$timezone_identifier."\" ";
        $out_str.=($i==1?" SELECTED ":"");
        $out_str.=">"."( GMT" . ($offset < 0 ? $offset : "+".$offset).' ), '.substr(strstr($timezone_identifier, '/'),1)."</option>";
	}
	return $out_str;
}		


