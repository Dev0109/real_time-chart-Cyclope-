<?php
/************************************************************************
* @Author: Tinu Coman
***********************************************************************/
include('config/config.php');
if(DEBUG_CONTEXT){
	error_reporting(E_ALL & ~E_NOTICE);
}else{
	error_reporting(0);
}

include_once("module_config.php");
include_once("php/gen/startup.php");

if(!isset($glob['pag'])){
	$glob['pag']='login';
}

//auto-session!
//if($page_access[$glob['pag']]['session']){
	session_start();
//}
$_SESSION['colors'] = $default_colors;
if(isset($_SESSION[UID]) && $_SESSION[UID]){
	$user_level = $_SESSION[ACCESS_LEVEL];
}else{
	$user_level = 5;
}
$glob['success'] = false;
if(isset($glob['act']) && $glob['act'] && !isset($glob['skip_action'])){
	list($cls_name,$func_name ) = split("-",$glob['act']);
	if(($cls_name)&&($func_name)&&(is_file("classes/cls_".$cls_name.".php"))&&($func_access[$cls_name][$func_name])){
    	if($user_level<=$func_access[$cls_name][$func_name]){
        	include_once("classes/cls_".$cls_name.".php");
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
//autoload filters
if(isset($_SESSION['filters']) && is_array($_SESSION['filters']) && !empty($_SESSION['filters']) && !isset($glob['clear'])){
	foreach ($_SESSION['filters'] as $key => $val){
		if(isset($glob[$key]) || empty($val)){
			continue;
		}
		$glob[$key] = $val;
	}
}

if(!isset($glob['f']) && empty($glob['f'])){
	$glob['t'] = 'session';
	$glob['f'] = 's1';
}

if(!isset($glob['time']) && empty($glob['time'])){
	$glob['time'] = array(
		'time' => date('n/d/Y',time()),
		'type' => 1
	);
}

if(isset($glob['pag'])){
    if(isset($page_access[$glob['pag']]) && $page_access[$glob['pag']]['perm'] >= $user_level){
    	$folder = isset($page_access[$glob['pag']]['folder']) ? $page_access[$glob['pag']]['folder'].'/' : 'print/';
		if(isset($page_access[$glob['pag']]['diff'])){
			if(is_array($page_access[$glob['pag']]['diff'])){
				$page = isset($page_access[$glob['pag']]['diff'][$user_level]) ? include("php/".$folder.$user_level.$glob['pag'].".php") : include("php/".$folder.$page_access[$glob['pag']]['perm'].$glob['pag'].".php");
				$page_access[$glob['pag']]['module'] = isset($page_access[$glob['pag']]['diff'][$user_level]) ? $page_access[$glob['pag']]['diff'][$user_level] : $page_access[$glob['pag']]['module'];
			}elseif ($user_level != $page_access[$glob['pag']]['perm']){
				$page = include("php/".$folder.$user_level.$glob['pag'].".php");
				$page_access[$glob['pag']]['module'] = $page_access[$glob['pag']]['diff'];
			}else{
				$page = include("php/".$folder.$glob['pag'].".php");
			}
		}else{
    		$page = include("php/".$folder.$glob['pag'].".php");
		}
    }else{
    	$glob['pag'] = 'login';    	
    	$page = include("php/pag/login.php");
    }
}

$ftm=new ft(ADMIN_PATH.MODULE."templates/");
$ftm->define(array('main'=>"index.html"));
$ftm->assign('PAGE',$page);
$ftm->assign('META_TITLE',$site_meta_title);
$ftm->parse('CONTENT','main');
$ftm->ft_print('CONTENT');

if($debug){
   require($script_path."misc/debug.php");
}