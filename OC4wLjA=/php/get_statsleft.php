<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/

$ftstats=new ft(ADMIN_PATH.MODULE."templates/");
$ftstats->define(array('main' => "get_statsleft.html"));

$type = $glob['t'];
$id = $glob['f'];
if(!empty($type) && !empty($id)){
	$ftstats->assign('CODE','<script type="text/javascript">flobn.register("initially_select_'.$type.'","'.$id.'");flobn.register("initially_select_type","'.$type.'");</script>');
}else {
	$ftstats->assign('CODE','<script type="text/javascript">flobn.register("initially_select_session","s1");</script>');
}

switch ($type){
	case 'users':
		$ftstats->assign('FILTER','Employee');
		break;
	case 'session':
		$ftstats->assign('FILTER','Logon');
		break;
	case 'computers':
		$ftstats->assign('FILTER','Computer');
		break;
}

if($_SESSION['access_level'] == ADMIN_LEVEL)
{
	$dbu = new mysql_db();
	
	$current_date = mktime(0,0,0,date('m'),date('Y'),date('Y'));
	
	if( TODAY != $current_date )
	{
		$dbu->query("UPDATE settings SET value='".$current_date."' WHERE constant_name ='TODAY'");
		
		include_once(CURRENT_VERSION_FOLDER."misc/json.php");
	
		$cyclope_json_version = file_get_contents(LATEST_RELEASE_LOCATION);

		$jsonDecoder = new Services_JSON();
		$cyclope = $jsonDecoder->decode($cyclope_json_version);
		
		
		
		$version = end(array_keys(get_object_vars($cyclope)));
		$dbu->query("UPDATE settings SET long_value='".$version."' WHERE constant_name='SERVER_VERSION'");
	}
	$update_version = $dbu->field("SELECT long_value FROM settings WHERE constant_name='SERVER_VERSION'");
	
	
	/*if($update_version != SERVER_VERSION)
	{
		$ftstats->assign('UPDATES',
		get_error('<a href="index.php?pag=updates">'.$ftstats->lookup('Version').' '.$update_version.' '.$ftstats->lookup('is now available ! Click here to update').'</a>','info flobn-msg'));
	}*/
	
	if((ED - time() < 432000) && (LP != 1))
	{
		add_notification("LICENSE_EXPIRED");
		/*$ftstats->assign('EXPIRE',
		get_error('<a href="index.php?pag=licensing">'.$ftstats->lookup('Your licence will expire soon! Click here to extend your licence').'</a>','warning flobn-msg'));*/	
	}
	if((ED - time() < 432000) && (LP == 1) && (ED - time() > 1))
	{
		add_notification("SUPPORT_EXPIRED");
		/*$ftstats->assign('EXPIRE',
		get_error('<a href="index.php?pag=licensing">'.$ftstats->lookup('Your licence will expire soon! Click here to extend your licence').'</a>','warning flobn-msg'));*/	
	}
	
	$total_computers =  $dbu->field("SELECT count(computer_id) FROM computer");
	
	$trial = $dbu->field("SELECT value FROM `settings` WHERE `constant_name` = 'TRUENC'");
	$ecrypted_text = $dbu->field("SELECT message FROM `notification` WHERE `constant_name` = 'ENCRYPTED_TEXT'");
	if ($trial != 2236985){
		add_notification("ENCRYPTED_TEXT");
		$ftstats->assign('ENCRYPTMESSAGERAW',		$ftstats->lookup($ecrypted_text));
	}
	
	if( AC < $total_computers )
	{
		add_notification("NUMBER_OF_USERS_EXCEEDED");
		/*$ftstats->assign('OVERLICENCE',
		get_error('<a href="index.php?pag=monitored">'.$ftstats->lookup('You have exceeded the number of computers covered by this license! Please consider upgrading your license.').'</a>','error flobn-msg'));*/
	}
	
	//modificare 1 din 4
	$total_notifications=  $dbu->field("SELECT COUNT(session_notification_id) FROM session_notification WHERE mark_as_read = 0 ");
	//modificare 2 din 4
	$dbu->query("SELECT ( SELECT COUNT(*) FROM session_notification INNER JOIN notification ON notification.notification_id = session_notification.notification_id WHERE mark_as_read = 0 AND notification_type = 0) AS error, 
		                ( SELECT COUNT(*) FROM session_notification INNER JOIN notification ON notification.notification_id = session_notification.notification_id WHERE mark_as_read = 0 AND notification_type = 1) AS warning,
						( SELECT COUNT(*) FROM session_notification INNER JOIN notification ON notification.notification_id = session_notification.notification_id WHERE mark_as_read = 0 AND notification_type = 2) AS info" );
	
	$dbu->move_next();
	
	
	
	if( $total_notifications)
	{
		$ftstats->assign('NOTIFICATIONS',
		get_error('<a href="index.php?pag=notifications">'.$ftstats->lookup('You have notifications pending.').' ('.$total_notifications.')</a>',(((int)$dbu->f("error")) ? 'error' : (((int)$dbu->f("warning")) ? 'warning' : 'info') ).' flobn-msg'));
	}
	
	
	
}
$ftstats->assign('CURRENT_VERSION_FOLDER',CURRENT_VERSION_FOLDER);
$ftstats->parse('CONTENT','main');
return $ftstats->fetch('CONTENT');