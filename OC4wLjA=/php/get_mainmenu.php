<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/

$fmainmenu=new ft(ADMIN_PATH.MODULE."templates/");
$pref = 4;

if($_SESSION[ACCESS_LEVEL] >=1 && $_SESSION[ACCESS_LEVEL] < 3){
	$pref = 1;
}

if(($_SESSION[ACCESS_LEVEL] == ADMIN_LEVEL) && GO_TO_TRIAL == 1)
{
	$fmainmenu->define(array('main' => $pref."maintabsdisabled.html"));	
}
elseif ($_SESSION[ACCESS_LEVEL] == 1.5)
{
	$fmainmenu->define(array('main' => $pref."maintabstechadmin.html"));	
}
else 
{
	$fmainmenu->define(array('main' => $pref."maintabs.html"));	
}

global $page_access;
$module = $page_access[$glob['pag']]['module'];
if($glob['pag'] == 'support'){
	$module = 'support';
}
if($glob['pag'] == 'gdpr'){
	$module = 'gdpr';
}
$result = $fmainmenu->lookup("Search everywhere");
$fmainmenu->assign(array(
	'SELECTED_STATS' => '',
	'SELECTED_ADMIN' => '', 
	'SELECTED_HELP' => '', 
	'SELECTED_SUPPORT' => '',
	'SELECTED_GDPR' => '',
	'SELECTED_'.strtoupper($module) => 'class="active"',
	'HELP_LINK' => 'help.php?pag='.$glob['pag'],
	'SEARCH_VALUE' => $result,
));
switch ($_SESSION[ACCESS_LEVEL]){
	case 2:
		$fmainmenu->assign('LINK','settings');
		break;
	default:
		$fmainmenu->assign('LINK','monitored');
		break;
}


$fmainmenu->parse('CONTENT','main');
//$fmainmenu->fastprint('CONTENT');
return $fmainmenu->fetch('CONTENT');