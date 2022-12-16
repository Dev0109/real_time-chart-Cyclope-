<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$func_access = array(
	'auth' => array(
		'login' => 5,
		'logout' => 5,
	),
	'department' => array(
		'add'=> 1.5,
		'update'=> 1.5,
		'delete'=> 1.5,
		'movemember' => 1.5,
		'movenode' => 1.5,
	),
	'category' => array(
		'add'=> 2,
		'update'=> 2,
		'delete'=> 2,
	),
	'application' => array(
		'add'=> 2,
		'update'=> 2,
		'delete'=> 2,
		'category' => 3,
		'productive' => 3,
		'application_type' => 3,
	),
	'member' => array(
		'add'=> 1.5,
		'update'=> 1.5,
		'delete'=> 1.5,
		'cleardeleted' => 1.5,
		'account'=> 4,
	),
	'log' => array(
		'clear'=> 1.5,
		'clearall'=> 1.5,
		'clearhalf'=> 1.5,
		'clearquarter'=> 1.5,
		'update'=> 2,
	),
	'colors' => array(
		'add'=> 2,
		'delete'=> 2,
		'setdefault'=> 2,
		'update'=> 2,
		'select'=> 2,
	),
	'settings' => array(
		'update'=> 2,
		'emailupdate'=> 2,
		'adupdate'=> 2,
		'emailtest'=>2,
		'updateupdate'=>2,
		'updateautodelete'=>2,
	),
	'workschedule' => array(
		'update'=> 2,
	),
	
	'reports' => array(
		'overview'=> 4,
		'overtime'=> 4,
		'file'=> 4,
		'csvprint'=> 4,
		'document'=> 4,
		'timeline'=>  4,
		'sequence'=>  4,
		'interneturls'=> 4,
		'internetdomains'=> 4,
		'internetwindows'=> 4,
		'chat'=> 4,
		'usersactivity'=> 4,
		'applicationusageaggregated'=> 4,
		'applicationusageperuser'=> 4,
		'applicationforms'=> 4,
		'productivityreport'=> 4,
		'categoryactivity'=> 4,
		'softwareinventory'=> 4,
		'softwareupdates'=> 4,
		'attendance'=> 4,
		'topproductive'=> 4,
		'topunproductive'=> 4,
		'topactive'=> 4,
		'topidle'=> 4,
		'toponline'=> 4,
		'topwebsites'=> 4,
		'topapplications'=> 4,
		'triggered'=> 4,
	),
	'lang'=>array(
		'add' => 5,
	),
	'cost'=>array(
		'update' => 1.5,
	),
	'emailreports'=>array(
		'add' => 1.5,
		'update' => 1.5,
		'delete' => 1.5,
	),
	'emailsequence'=>array(
		'add' => 1.5,
		'update' => 1.5,
		'delete' => 1.5,
	),
	
	'update' => array(
		'download' => 1.5,
		'unzip' => 1.5,
		'altertables' => 1.5,
		'rollback' => 1.5,
		'error' => 1.5,
	),
	'notification' => array(
		'delete' => 1.5,
		'mark_as_read'=> 1.5,
	),
	
	'licence' => array(
		'trial' => 1.5,
		'activate' => 1.5,
	),
	'error' => array(
		'send' => 5
	),
	'alert' => array(
		'add' => 2,
		'update' => 2,
		'delete' => 2
	)
);