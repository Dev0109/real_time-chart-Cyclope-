<?php
/***********************************************************************
* @Author: MedeeaWeb Works											   *
***********************************************************************/
class settings
{
	var $dbu;

	// function settings()
	// {
	// 	$this->dbu = new mysql_db();
	// }

	/****************************************************************
	* function update(&$ld)                                         *
	****************************************************************/
	function update(&$ld)
	{
		
		$this->dbu = new mysql_db();
		if(!$this->update_validate($ld))
		{
			return false;
		}

		$this->dbu->query("UPDATE settings SET value='".$ld['online_time_include']."' WHERE constant_name='ONLINE_TIME_INCLUDE'");
		$this->dbu->query("UPDATE settings SET value='".$ld['client_uninstall_password']."' WHERE constant_name='CLIENT_UNINSTALL_PASSWORD'");
		$this->dbu->query("UPDATE settings SET value='".$ld['language_id']."' WHERE constant_name='LANGUAGE_ID'");
		if(is_numeric($ld['number_of_rows']))
		{
			if($_SESSION['NUMBER_OF_ROWS'])
				$this->dbu->query("UPDATE settings SET value='".$ld['number_of_rows']."' WHERE constant_name='NUMBER_OF_ROWS'");
			else
				$this->dbu->query("INSERT INTO settings SET constant_name='NUMBER_OF_ROWS', value='".$ld['number_of_rows']."'");
			$_SESSION['NUMBER_OF_ROWS'] = $ld['number_of_rows'];
		}
		else
		{
			$this->dbu->query("DELETE FROM settings WHERE constant_name = 'NUMBER_OF_ROWS'");
			unset($_SESSION['NUMBER_OF_ROWS']);
		}
		
		if(is_numeric($ld['character_set_id']))
		{
			if($_SESSION['CHARACTER_SET'])
				$this->dbu->query("UPDATE settings SET value='".$ld['character_set_id']."' WHERE constant_name='CHARACTER_SET_ID'");
			else
				$this->dbu->query("INSERT INTO settings SET constant_name='CHARACTER_SET_ID', value='".$ld['character_set_id']."'");
			$_SESSION['CHARACTER_SET'] = $ld['character_set_id'];
		}
		else
		{
			$this->dbu->query("DELETE FROM settings WHERE constant_name = 'CHARACTER_SET_ID'");
			unset($_SESSION['CHARACTER_SET']);
		}

		$lang = $this->dbu->field("SELECT shortcode FROM language WHERE language_id = ?",$ld['language_id']);
		$_SESSION['LANG'] = $lang;
		
		
		
		$ld['error'].="The settings have been succesfully updated.";
		return true;
	}

	function emailupdate(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->emailupdate_validate($ld))
		{
			return false;
		}
		
		$this->dbu->query("UPDATE settings SET value='".$ld['smtp_server']."' WHERE constant_name='SMTP_SERVER'");
		$this->dbu->query("UPDATE settings SET value='".$ld['smtp_user']."' WHERE constant_name='SMTP_USER'");
		$this->dbu->query("UPDATE settings SET value='".$ld['smtp_password']."' WHERE constant_name='SMTP_PASSWORD'");
		$this->dbu->query("UPDATE settings SET value='".$ld['smtp_port']."' WHERE constant_name='SMTP_PORT'");
		$this->dbu->query("UPDATE settings SET value='".$ld['authorisation']."' WHERE constant_name='AUTHORISATION'");
		$this->dbu->query("UPDATE settings SET value='".$ld['smtp_mailer']."' WHERE constant_name='SMTP_MAILER'");
		$this->dbu->query("UPDATE settings SET value='".$ld['ssl']."' WHERE constant_name='SSL'");

		$ld['error'].="The settings have been succesfully updated.";
		return true;
	}

	function adupdate(&$ld)
	{
		$this->dbu = new mysql_db();
		$this->dbu->query("UPDATE settings SET value='".$ld['base_dn']."' WHERE constant_name='BASE_DN'");
		$this->dbu->query("UPDATE settings SET value='".$ld['account_suffix']."' WHERE constant_name='ACCOUNT_SUFFIX'");
		$this->dbu->query("UPDATE settings SET value='".$ld['domain_controllers']."' WHERE constant_name='DOMAIN_CONTROLLERS'");
		$this->dbu->query("UPDATE settings SET value='".$ld['admin_username']."' WHERE constant_name='ADMIN_USERNAME'");
		$this->dbu->query("UPDATE settings SET value='".$ld['admin_password']."' WHERE constant_name='ADMIN_PASSWORD'");
		$this->dbu->query("UPDATE settings SET value='".$ld['admin_password']."' WHERE constant_name='ADMIN_PASSWORD'");
		$this->dbu->query("UPDATE settings SET value='".$ld['protocol']."' WHERE constant_name='PROTOCOL'");

		$ld['error'].="The settings have been succesfully updated.";
		return true;
	}

	/****************************************************************
	* function update_validate(&$ld)                                *
	****************************************************************/
	function update_validate(&$ld)
	{
		$is_ok = true;
		
		if(!$ld['online_time_include'])
		{
			$ld['error'].='Please make sure you have selected one option for the Browser Time.<br>';
			$is_ok = false;
		}
		
		if(!$ld['client_uninstall_password'])
		{
			$ld['error'].='Please fill in the Client Uninstall Password field .<br>';
			$is_ok = false;
		}elseif (strlen($ld['client_uninstall_password']) < 6){
			$ld['error'].='Please fill in the Client Uninstall Password field with at least 6 chars.<br>';
			$is_ok = false;
		}
		
		if(!$ld['language_id'])
		{
			$ld['error'].='Please make sure you have selected one option for the Language field.<br>';
			$is_ok = false;
		}
		return $is_ok;
	}
	
	function emailupdate_validate(&$ld){
		$is_ok = true;
		if(!$ld['smtp_server'])
		{
			$ld['error'].='Please fill in the SMTP Server field .<br>';
			$is_ok = false;
		}
		
		if(!$ld['authorisation'])
		{
			if(!$ld['smtp_user'])
			{
				$ld['error'].='Please fill in the SMTP User field .<br>';
				$is_ok = false;
			}
			
			if(!$ld['smtp_password'])
			{
				$ld['error'].='Please fill in the SMTP Password field .<br>';
				$is_ok = false;
			}
		}
		
		if(!$ld['smtp_port'])
		{
			$ld['error'].='Please fill in the SMTP Port field .<br>';
			$is_ok = false;
		}
		
		return $is_ok;
	}
	
	function emailtest(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->emailtest_validate($ld))
		{
			return false;
		}
		
		require(CURRENT_VERSION_FOLDER."misc/class.phpmailer.php");
		
		$mail = new PHPMailer();
		
		$mail->SMTPAuth = AUTHORISATION == 1 ? false : true ;
		$mail->SMTPSecure = SSL == 1 ? 'ssl' : '' ;
		$mail->Mailer = SMTP_MAILER;
		$mail->Host = $ld['smtp_server'];
		$mail->Username = $ld['smtp_user'];
		$mail->Password = $ld['smtp_password'];
		$mail->Port = $ld['smtp_port'];
		$mail->AddAddress(ADMIN_EMAIL,'Admin');
		// $mail->AddAddress('lorand.bognar@amplusnet.com','Admin');
		if ($mail->Mailer == 'mail'){
			$mail->From = $ld['smtp_user'];
		} else {
			$mail->From = ADMIN_EMAIL;
		}
		$mail->CharSet = 'UTF-8';
		
		$mail->FromName = 'Admin';
		$mail->Subject = "Test Email -- " . ADMIN_EMAIL;
		$mail->Body = "This is a test email to verify if your smtp settings are correct!";
		
		if($mail->Send())
		{
			$ld['error'].="Test mail has been successfully sent! ";	
			return true;
		}
		else 
		{
			$ld['error'].="Could not send mail with the specified details! Please review your email settings or, if you are using the default settings, check your firewall settings.";
			debug_log("report sent error (if empty, means it is ok): ".$mail->ErrorInfo,'log-error-emails');
			return false;
		}
	}
	
	function emailtest_validate(&$ld){
		return $this->emailupdate_validate($ld);
	}
	
	
	function updateupdate(&$ld)
	{
		$this->dbu = new mysql_db();
		$this->dbu->query("UPDATE settings SET value='".$ld['automatic_updates']."' WHERE constant_name='AUTOMATIC_UPDATES'");
		$ld['error'].="The settings have been succesfully updated.";
		
		return true;
		
	}
	
	function updateautodelete(&$ld)
	{
		$this->dbu = new mysql_db();
		$this->dbu->query("UPDATE settings SET value='".$ld['autodelete_logshalf']."' WHERE constant_name='AUTODELETE_LOGSHALF'");
		$ld['error'].="The option has been succesfully updated.";
		
		return true;
		
	}
	
	
}//end class