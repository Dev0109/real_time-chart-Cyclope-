<?php
/***********************************************************************
* @Author: MedeeaWeb Works											   *
***********************************************************************/
class error
{
	function error()
	{
		$this->dbu = new mysql_db();
	}
	
	function send($ld){
		//	add server smtp info
		$ld['smtp_server'] = 'mail.amplusnet.com';
		$ld['smtp_user'] = 'cyclope.reports@amplusnet.com';
		$ld['smtp_password'] = 'CyC|lop3R3ports';
		$ld['smtp_port'] = 25;
		
		// error_reporting(E_ALL);
		error_reporting(0);
		
		include(CURRENT_VERSION_FOLDER.'misc/class.phpmailer.php');
		$mailer = new PHPMailer();
		
		$mailer->SMTPAuth = true ;
		$mailer->Host = $ld['smtp_server'];
		$mailer->Username = $ld['smtp_user'];
		$mailer->Password = $ld['smtp_password'];
		$mailer->Port = $ld['smtp_port'];
		
		$mailer->Mailer   = "smtp";
		$mailer->AddAddress('support@cyclope-series.com','Support');
		// $mailer->AddBCC('lorand.bognar@amplusnet.com','Lorex');
		$my_ip = file_get_contents('http://licensing.cyclope-series.com/my_ip.php');
		$mailer->From = isset($ld['email']) ? $ld['email'] : ADMIN_EMAIL;
		$mailer->FromName = 'Error Report';
		$mailer->Subject = 'Error Report from ' . $my_ip;
		$mailer->Body = "Last Page Visited: ".$ld['lastpage']."\n
Contact email: ".$ld['email']."\n
Comments:".nl2br($ld['comments'])."\n
You can haz fun!!!";
		$mailer->AddAttachment(CURRENT_VERSION_FOLDER.'logs/'.$ld['file']);
		if (!$mailer->Send()) {
			echo "<p>There was an error in sending mail, please try again at a later timeeee</p>";
		}
		switch ($ld['after']){
			default:
			case 1:
				header('Location:index.php?act=auth-logout');
				break;
			default:	
			case 2:
				header('Location:index.php?pag=overview');
				break;
			case 3:
				header('Location: '.$ld['lastpage'].'&clear=all');
				break;
		}
		return true;
	}
	
}//end class