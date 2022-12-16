<?php
/*error_reporting(0);*/
set_time_limit(0);
ignore_user_abort(true);
$dbu = new mysql_db();
include_once('php/gen/startup.php');
include_once('misc/json.php');
debug_log('.. ENTERED IN AUTOUPDATES ================================= at ' . date('l jS \of F Y h:i:s A'),'cronlog');

//	PERMANENTUS
$jsonDecoder = new Services_JSON();
$data = $dbu->field("SELECT long_value FROM settings WHERE constant_name='LICENCEKEY'");
if($data)
{
	$licence = $jsonDecoder->decode(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, MCRYPT_PASSWORD, base64_decode($data),MCRYPT_MODE_ECB));		
}
debug_log('.. license: ' . print_r($licence,1),'cronlog');
if( time() <= $licence->end )
{
	$dbu->query("SELECT value FROM settings WHERE constant_name='AUTOMATIC_UPDATES'");
	$dbu->move_next();
	debug_log('.. autoupdate started: ' . print_r($dbu->f('value'),1),'cronlog');
	if($dbu->f('value') == 1)
	{
		require_once("misc/ftp_class.php");
		require_once("classes/cls_update.php");
		$ld =  array();

		// delete duplicate indexes
		// $res = mysql_query("SHOW INDEX FROM `session_website_agg` WHERE `Key_name` LIKE 'session_id_%'");
		// while ($row = mysql_fetch_object($res)) {
			// mysql_query("DROP INDEX `{$row->Key_name}` ON `{$row->Table}`") or die(mysql_error());
		// }
		// $res = mysql_query("SHOW INDEX FROM `application_inventory` WHERE `Key_name` LIKE 'application_id_%'");
		// while ($row = mysql_fetch_object($res)) {
			// mysql_query("DROP INDEX `{$row->Key_name}` ON `{$row->Table}`") or die(mysql_error());
		// }

		$update = new update();
		$update->automatic_update = true;
		if($update->download($ld))
		{
			if($update->unzip($ld))
			{
				if($update->altertables($ld))
				{
					add_notification("UPDATE_SUCCESS");
					file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',"IFLD 46 ---------------\n",FILE_APPEND);
					
				}
				else
				{
					add_notification("UPDATE_ERROR_DATABASE");	
					file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',"IFLD 52 ---------------\n",FILE_APPEND);
				}
			}
			else
			{
				add_notification("UPDATE_ERROR_UNZIP");	
					file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',"IFLD 58 ---------------\n",FILE_APPEND);
			}
		}
		else
		{
			if($ld['error'] != 'No need to update you have the latest version !')
			{
				// add_notification("UPDATE_ERROR_DOWNLOAD");
					file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',"IFLD 66 ---------------\n",FILE_APPEND);
			}	
		}
	}
}