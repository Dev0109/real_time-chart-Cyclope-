<?php
/************************************************************************
* @Author: Tinu Coman
***********************************************************************/
include('config/config.php');
if(DEBUG_CONTEXT){
	error_reporting(E_ALL & ~E_NOTICE &  ~E_DEPRECATED);
}else{
	error_reporting(0);
}

include_once("module_config.php");

include_once(CURRENT_VERSION_FOLDER.'php/gen/startup.php');

define('CONVIVAL_MANDATORY',false);


if(LATEST_RELEASE_LOCATION)
{
	ShowUpdateNotice();
}


$dbu->query("SELECT page_tracker_id FROM page_tracker WHERE name = ?",$glob['pag']);
if(!$dbu->move_next()){
	$dbu->query_get_id("INSERT INTO page_tracker SET name = ?,last_record = ?",array($glob['pag'],time()));
}else{
	$dbu->query("UPDATE page_tracker SET last_record = ?,total_views = total_views+1, new_views = new_views+1 WHERE name = ?",array(time(),$glob['pag']));
}


if($_SERVER['HTTP_X_PURPOSE'] == 'preview'){
	$glob['pag'] = 'opreview';
}

if(!$glob['pag']){
	$glob['pag']='login';
}

//auto-session!
//if($page_access[$glob['pag']]['session']){
	
//}
$_SESSION['colors'] = $default_colors;
if(isset($_SESSION[UID]) && $_SESSION[UID]){
	$user_level = $_SESSION[ACCESS_LEVEL];
	 if($glob['pag']=='login' && $glob['act'] != 'auth-logout'){
		 /*$glob['pag']='simpleoverview';*/
		if ($_SESSION[ACCESS_LEVEL] == 1.5)
				{
					$glob['pag'] = 'monitored';
				} else {
					$glob['pag'] = 'simpleoverview';
				}
    }
} elseif ($_REQUEST['render'] == 'json') {
	$adminpassword = $dbu->field('SELECT `password` FROM `member` WHERE `member_id` = 1');
	if ($_REQUEST['key'] == $adminpassword) {
	$user_level = 1;
	} else {
		echo json_encode(array("error" => "key is incorrect"));
		exit;
	}
} else{
	$user_level = 5;
}

if($_REQUEST['timefilter']) {
	$_SESSION['filters']['time'] = array(
		'time' => $_REQUEST['timefilter'],
		'type' => 1,
		'current' => 'Today'
	);
	$glob['time'] = array(
		'time' => $_REQUEST['timefilter'],
		'type' => 1,
	);
}

$glob['success'] = false;
//autoload filters
if(isset($_SESSION['filters']) && is_array($_SESSION['filters']) && !empty($_SESSION['filters']) && !isset($glob['clear'])){
	foreach ($_SESSION['filters'] as $key => $val){
		if(isset($glob[$key]) || empty($val)){
			continue;
		}
		$glob[$key] = $val;
	}
}


if(!isset($glob['f']) || empty($glob['f'])){
	$glob['t'] = 'session';
	$glob['f'] = 's1';
}

if(!isset($glob['time']) || empty($glob['time'])){
	if(FREEZE_TIME_NOW){
		$glob['time'] = array(
			'time' => date('n/d/Y',FREEZE_TIME),
			'type' => 1,
			'current' => 'Today'
		);
	} else {
		$glob['time'] = array(
			'time' => date('n/d/Y',time()),
			'type' => 1,
			'current' => 'Today'
		);
	}
}


global $bottom_includes;
$bottom_includes .= '<script type="text/javascript">flobn.register("FREEZE_TIME_NOW",'.(FREEZE_TIME_NOW ? 'true' : 'false').');flobn.register("FREEZE_TIME",new Date("'.date('n/d/Y',FREEZE_TIME).'"));flobn.register("current","'.$glob['time']['current'].'");</script>';

if(isset($glob['act']) && $glob['act'] && !isset($glob['skip_action'])){
	list($cls_name,$func_name ) = explode("-",$glob['act']);
	if(($cls_name)&&($func_name)&&(is_file(CURRENT_VERSION_FOLDER."classes/cls_".$cls_name.".php"))&&($func_access[$cls_name][$func_name])){
    	if($user_level<=$func_access[$cls_name][$func_name]){
			// var_dump("test", CURRENT_VERSION_FOLDER."classes/cls_".$cls_name.".php");
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
        		$glob['error']= "You are not allowed to run this function (index-2685)!";
            }
            $glob['pag']= "login";
        }
    }else{
		if($debug){

			echo "Can not find cls_".$cls_name.".php file<BR>";
		}
    }
}
// do we need admin access level without login?
if( FREEZE_TIME_NOW){
	// $_SESSION[U_ID] = 1;
	// $_SESSION[ACCESS_LEVEL] = 1;
}


$glob_pag = str_replace('simple','',$glob['pag']);

if(isset($glob['pag'])){

    if((isset($page_access[$glob['pag']]) && $page_access[$glob['pag']]['perm'] >= $user_level) || FREEZE_TIME_NOW){

    	$folder = isset($page_access[$glob['pag']]['folder']) ? $page_access[$glob['pag']]['folder'].'/' : 'pag/';
		if(isset($page_access[$glob['pag']]['diff'])){
			if(is_array($page_access[$glob['pag']]['diff'])){
				$page = isset($page_access[$glob['pag']]['diff'][$user_level]) ? include(CURRENT_VERSION_FOLDER."php/".$folder.$user_level.$glob_pag.".php") : include(CURRENT_VERSION_FOLDER."php/".$folder.$page_access[$glob['pag']]['perm'].$glob['pag'].".php");
				$page_access[$glob['pag']]['module'] = isset($page_access[$glob['pag']]['diff'][$user_level]) ? $page_access[$glob['pag']]['diff'][$user_level] : $page_access[$glob['pag']]['module'];
			}elseif ($user_level != $page_access[$glob['pag']]['perm']){
				$page = include(CURRENT_VERSION_FOLDER."php/".$folder.$user_level.$glob_pag.".php");
				$page_access[$glob['pag']]['module'] = $page_access[$glob['pag']]['diff'];
			}else{
				$page = include(CURRENT_VERSION_FOLDER."php/".$folder.$glob_pag.".php");
			}
		}else{
			// var_dump(CURRENT_VERSION_FOLDER."php/".$folder.$glob_pag.".php");
			// die();
    		$page = include(CURRENT_VERSION_FOLDER."php/".$folder.$glob_pag.".php");
		}
    }else{
    	$glob['pag'] = 'login';    	
    	$page = include(CURRENT_VERSION_FOLDER."php/pag/login.php");
    }
}
if(isset($site_module[$page_access[$glob['pag']]['module']]))
{
	
	$template_file=$site_module[$page_access[$glob['pag']]['module']]['template_file'];
	// var_dump("template_file", $template_file);
	// $template_file = 'Dashboard.Default.html';
	$current_module=$page_access[$glob['pag']]['module'];

	$dbu = new mysql_db();
	$dbu->query("select * from ".$current_module."_template_czone");
	while($dbu->move_next())
	{
		$template_tags[$dbu->f('template_czone_id')]=$dbu->f('tag');
		$template_content[$dbu->f('template_czone_id')]=$dbu->f('content');
	}
	
	
}
else
{
//    $template_file='template02.html';
    $template_file='login_template.html';
}

$ftm=new ft(CURRENT_VERSION_FOLDER);
$ftm->define(array('main'=>$template_file));

if($template_tags)
foreach ($template_tags as $template_czone_id => $template_czone_tag)
{
	$tag_content=$template_content[$template_czone_id];
	//get tags from content
	$cms_tag_array=get_cms_tags_from_content($tag_content);
	//****Replacing the CMS tags with objects

	if($cms_tag_array)
	foreach ( $cms_tag_array as $key => $cms_tag_params)
	{
		$tag_content=str_replace($cms_tag_params['tag'], get_cms_tag_content($cms_tag_params), $tag_content);
	}

	$ftm->assign($template_czone_tag, $tag_content);
}
$ftm->assign(array(
	//	PARENT1, PARENT2, PARENT3, PARENT4, PARENT5
	strtoupper($page_access[$glob['pag']]['cssparent']) => 'active',
	strtoupper($glob['pag']) => 'active',
));
if($_SESSION[U_ID] || FREEZE_TIME_NOW){
	$_db = new mysql_db();
	$memberName = $_db->field("SELECT CONCAT(first_name,' ',last_name) as name FROM member WHERE member_id = ?",$_SESSION[U_ID]);
	$member=substr($memberName, 0, 1);
	$ftm->assign('USER',$member);
	
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
if(CONVIVAL_MANDATORY){
	require(CURRENT_VERSION_FOLDER.'misc/convivial_lib.php');
	
	$conv = $dbu->query("SELECT member.department_id,member.member_id, computer.computer_id,member.convival								  
						  FROM member
						  INNER JOIN computer2member ON computer2member.member_id = member.member_id
					      INNER JOIN computer ON computer.computer_id = computer2member.computer_id
						  WHERE 1=1 AND (active != 3 OR active != 0)");
	$style = '<style type="text/css">';
	$keys = array_keys($uicons);
	while ($conv->next()){
		$style .= '#s'.$conv->f('department_id').'-'.$conv->f('computer_id').'-'.$conv->f('member_id').' a ins{ background:url('.$uicons[$keys[$conv->f('convival')]].') no-repeat 0px -6px;width:32px; }'."\n";
	}
	$style .= '</style>';
	if($glob['f'] == 's8-13-17' || $glob['f'] == 's8'){
		$f = array('1304586946_Lightsaber6.png','1304587008_jedi.png','1304587030_lightsaber11.png','1304587052_lightsaber25.png','1304587116_Lightsaber4.png');
		$poz = rand(0,count($f)-1);
		$ftm->assign('CONVIVAL',$style.'<style type="text/css">
		#'.$glob['pag'].' h1{
			background: url('.$icons[$f[$poz]].') no-repeat left center;
			padding-left:80px;
			line-height:70px;
		}
		</style>');
	}else{
		$poz = rand(0,count($icons));
		$key = array_keys($icons);

		$ftm->assign('CONVIVAL',$style.'<style type="text/css">
		#'.$glob['pag'].' h1{
			background: url('.$icons[$key[$poz]].') no-repeat left center;
			padding-left:80px;
			line-height:70px;
		}
		</style>');
	}
}

$ftm->assign('META_TITLE',$site_meta_title);
$ftm->assign('META_KEYWORDS',$site_meta_keywords);
$ftm->assign('META_DESCRIPTION',$site_meta_description);
$ftm->assign('PAGE',$page);
$ftm->assign('CURRENT_VERSION_FOLDER',CURRENT_VERSION_FOLDER);
$bottom_includes = str_replace('"ui/','"'.CURRENT_VERSION_FOLDER.'ui/',$bottom_includes);

$ftm->assign('BOTTOM_INCLUDES',$bottom_includes);
$ftm->parse('CONTENT','main');
echo compress_output($ftm->fetch('CONTENT'));//this makes for better javascript dom manipulaiton
//echo $ftm->fetch('CONTENT');

if($debug){
   require($script_path."misc/debug.php");
}
$dbu = new mysql_db();
$dbu->disconnect();