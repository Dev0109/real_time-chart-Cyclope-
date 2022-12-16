<?php
/************************************************************************
* @Author: Tinu Coman
***********************************************************************/
header('Content-type: text/html; charset=utf-8');
header('Accept-Charset: *');

$ajax_loaded = true;

include_once(CURRENT_VERSION_FOLDER."module_config.php");
session_start();
include_once(CURRENT_VERSION_FOLDER."php/gen/startup.php");
include(CURRENT_VERSION_FOLDER.'misc/json.php');
if(DEBUG_CONTEXT){
	error_reporting(E_ALL & ~E_NOTICE);
}else{
	error_reporting(0);
}
$_SESSION['colors'] = $default_colors;
$user_level = isset($_SESSION[ACCESS_LEVEL]) && $_SESSION[ACCESS_LEVEL] ? $_SESSION[ACCESS_LEVEL] : 5;
$glob['failure'] = false;
$glob['is_ajax'] = true;
if(isset($glob['act']) && $glob['act'] && !$glob['skip_action'])
{
	list($cls_name,$func_name )=explode("-",$glob['act']);
	if(($cls_name)&&($func_name)&&(is_file(CURRENT_VERSION_FOLDER."classes/cls_".$cls_name.".php"))&&($func_access[$cls_name][$func_name]))
	{
    	if($user_level<=$func_access[$cls_name][$func_name])
        {
        	
        	include_once(CURRENT_VERSION_FOLDER."classes/cls_".$cls_name.".php");
            $cls_name= new $cls_name;
            if (!$cls_name->$func_name($glob))
            {
            	if($debug)
            	{
            		$glob['error'].="Failed to execute function $func_name";
            	}
            	$glob['failure'] = true;
            }
            unset($cls_name);
            unset($func_name);
        }
        else
        {
        	$glob['error']= "You are not allowed to run this function (ajax-28656112)!"; 
            $glob['failure'] = true;
        }
    }else{
    	$glob['error']= "You are not allowed to run this function (ajax-275654616)!"; 
    	$glob['failure'] = true;
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

if(!$glob['failure']){
	if($glob['pag']){
	    // if($page_access[$glob['pag']]['perm'] && $page_access[$glob['pag']]['perm'] >= $user_level)
	    // {
	    	$folder = isset($page_access[$glob['pag']]['folder']) ? $page_access[$glob['pag']]['folder'].'/' : 'pag/';
	    	if($folder == 'xml/'){
	    		header('Content-type: text/xml');
	    		echo pack("C3",0xef,0xbb,0xbf);
				echo '<?xml version="1.0" encoding="UTF-8"?>';
				
	    	}
			//	bugfix, needs rework when svn available
			if ($glob['pag'] == 'xinternet') {
				$page = include(CURRENT_VERSION_FOLDER."php/ajax/xinternetdomains.php");
			} else {
				$page = include(CURRENT_VERSION_FOLDER."php/".$folder.$glob['pag'].".php");
			}
	    	$glob['innerHTML'] = compress_output($page);
	    // }
	    // else
	    // {
	    	// $glob['innerHTML'] = '';
	    	// $glob['failure'] = true;
	    	// $glob['error']= "You are not allowed to run this function (ajax-236885)!"; 
	    // }
	}elseif(!$glob['act']){
    	$glob['failure'] = true;
    	$glob['error']= "You are not allowed to run this function (ajax-254254)! : " . print_r($glob['pag'],1); 
	}
}
$json = new Services_JSON();
header('Content-type:text/json');
echo $json->encode($glob);
$dbu = new mysql_db();
$dbu->disconnect();