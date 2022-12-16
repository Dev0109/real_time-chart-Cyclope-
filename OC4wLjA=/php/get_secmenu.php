<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/

$fsecmenu=new ft(ADMIN_PATH.MODULE."templates/");
$pref = $_SESSION[ACCESS_LEVEL] <= 1.5 ? 1 : 2;

if(($_SESSION[ACCESS_LEVEL] == ADMIN_LEVEL) && GO_TO_TRIAL == 1)
{
	$fsecmenu->define(array('main' => $pref."secmenudisabled.html"));	
}
else 
{
	
	$fsecmenu->define(array('main' => $pref."secmenu.html"));
}



global $page_access;
$fsecmenu->assign(array(
	strtoupper($page_access[$glob['pag']]['cssparent']) => 'active',
	strtoupper($glob['pag']) => 'active',
));



$fsecmenu->parse('CONTENT','main');
//$fmainmenu->fastprint('CONTENT');
return $fsecmenu->fetch('CONTENT');