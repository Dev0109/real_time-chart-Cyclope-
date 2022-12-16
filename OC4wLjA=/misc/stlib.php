<?php

/*if(!$is_not_hacked_yet)
{
	exit();
}*/
$_db=new mysql_db;    
$_db->query("select constant_name, value from settings");
while($_db->move_next())
{
	if($_db->f('constant_name') && !defined($_db->f('constant_name')))
	{
		define($_db->f('constant_name'),$_db->f('value'));
	}
} 

$_db->query("SELECT * FROM `member` WHERE `access_level` = 1");
$_db->move_next();

define('ADMIN_EMAIL',$_db->f('email'));
