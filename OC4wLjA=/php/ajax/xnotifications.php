<?php

$ftnotifications=new ft(ADMIN_PATH.MODULE."templates/");
$ftnotifications->define(array('main' => "xnotifications.html"));
$ftnotifications->define_dynamic('template_row','main');

$dbu = new mysql_db();


if(is_numeric($glob['app']) && ( $glob['app'] != -1 ) )
{
	$filter = ' AND notification_type = '.$glob['app'];
}

//	lorand
$sortable_columns = array(
	'eventtime',
	);

$sortcolumns = get_sorting($sortable_columns,'','desc');

$ftnotifications->assign(array(
	'ANCHOR_INNER_0' => render_anchor_inner(0),
	'DEBUGMESSAGE' => '',
	// 'DEBUGMESSAGE' => basename($_SERVER['PHP_SELF']),
	// 'DEBUGMESSAGE' => $sortcolumns,
));
//END

//modificare 3 din 4
$dbu->query("SELECT session_notification_id,
					eventtime,
					mark_as_read,
					message,
					cause,
					solution,
					notification_type,
					notification.notification_id
					FROM session_notification
					INNER JOIN notification ON notification.notification_id = session_notification.notification_id
					WHERE deleted = 0 ".$filter.
					" " . $sortcolumns . " ");

$i = 0;		
$l = new LanguageParser();

while ($dbu->move_next())
{	
	
	$ftnotifications->assign(array(
		'DELETE_LINK' => 'index_ajax.php?act=notification-delete&id='.$dbu->f('session_notification_id'),
		'DATE' => date('m/d/Y h:i:s A', $dbu->f('eventtime')),
		'CAUSE' => $l->lookup($dbu->f('cause')),
		'MESSAGE' => $dbu->f('message'),
		'MARKASREAD_LINK' => 'index_ajax.php?act=notification-mark_as_read&id='.$dbu->f('session_notification_id'),
	));
	
	$ftnotifications->assign('SOLUTION', ($dbu->f('notification_id') == 1) ? '<a href="index.php?pag=licensing"><strong>'.$l->lookup($dbu->f('solution')).'</strong></a>':$l->lookup($dbu->f('solution')));
	switch ( $dbu->f('notification_type')){
		case 0:
			$ftnotifications->assign(array(
				'NOTIFICATION_TYPE' => 'error'
			));
			break;
		case 1:
			$ftnotifications->assign(array(
				'NOTIFICATION_TYPE' => 'warning'
			));
			break;
		case 2:
			$ftnotifications->assign(array(
				'NOTIFICATION_TYPE' => 'info'
			));
			break;
		default:
			$ftnotifications->assign(array(
				'NOTIFICATION_TYPE' => 'info'
			));
				
	}
	
	if(($i % 2)==0 )
	{
		$ftnotifications->assign('CLASS','even');
	}
	else
	{
		$ftnotifications->assign('CLASS','');
	}
	
	$ftnotifications->parse('TEMPLATE_ROW_OUT','.template_row');
	$i++;
}

if($i==0)
{
	$ftnotifications->assign(array(
		'NO_DATA_MESSAGE' => get_error($ftnotifications->lookup('No data to display for your current filters'),'warning'),
		'HIDE_CONTENT'	=> 'hide'
	));
}
else 
{
	$ftnotifications->assign(array(
		'NO_DATA_MESSAGE' => '',
		'HIDE_CONTENT'	=> '',
	));
}




$ftnotifications->parse('CONTENT','main');
return $ftnotifications->fetch('CONTENT');
