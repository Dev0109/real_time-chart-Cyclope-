<?php
error_reporting(0);
set_time_limit(0);
ignore_user_abort(true);
$dbu = new mysql_db();


$dbu->query("SELECT value FROM settings WHERE constant_name='EXTRAREPORT_DAILY' AND module='extrareporttoggle'");
$dbu->move_next();
if($dbu->f('value') != 'checked')
{
	return ;
}
require_once(CURRENT_VERSION_FOLDER."misc/class.phpmailer.php");

$l = new LanguageParser();
$mail = new PHPMailer();

//	select yesterday's monitored users
$dbu->query('SELECT COUNT( DISTINCT member_id ) AS member_total
			 FROM session WHERE date = ' . strtotime("yesterday"));
$dbu->move_next();
$users_number = $dbu->f('member_total');

if(!isset($users_number)) 
	$users_number = 0;
	
//	prepare attachment
session_start();
$_SESSION['filters']['t'] = 'session';
$_SESSION['filters']['f'] = 's1';
$_SESSION['temp']['monitoredusers'] = $users_number;
$_SESSION['filters']['time'] = Array
(
	'time' => date("n/d/Y", strtotime("-1 day")),
	'type' => '1'
);
$_SESSION['attachment_name'] = str_replace(' ','_',text_process($l->lookup('Overview').' '.$l->lookup('Daily')).' Cyclope.pdf');
if (file_exists($tmp."/".$_SESSION['attachment_name']))
{
	unlink($tmp."/".$_SESSION['attachment_name']);
}
$_SESSION[ACCESS_LEVEL]=1;
$_SESSION[UID]=1;
session_write_close();
do_post_request($site_url.'/index.php','pag=overview&render=pdf&email=1&session_id='.session_id());
if (file_exists($tmp."/".$_SESSION['attachment_name']))
{
	$mail->AddAttachment($tmp."/".$_SESSION['attachment_name'],$_SESSION['attachment_name']);
}
unset($_SESSION['attachment_name']);
unset($_SESSION['temp']['monitoredusers']);
//	attachment prepared

// $mail->SMTPAuth = true ;
// $mail->SMTPSecure = '' ;
// $mail->Host = 'mail.amplusnet.com';
// $mail->Username = 'cyclope.reports@amplusnet.com';
// $mail->Password = 'CyC|lop3R3ports';
// $mail->Port = 25;
// $mail->Mailer   = 'smtp';

// $mail->FromName = "Cyclope Series";
// $mail->Sender = 'cyclope.reports@amplusnet.com';
// if ($mail->Mailer == 'mail'){
	// $mail->From = 'cyclope.reports@amplusnet.com';
	// $mail->Sender = 'cyclope.reports@amplusnet.com';
// } else {
	// $mail->From = 'cyclope.reports@amplusnet.com';
	// $mail->Sender = 'cyclope.reports@amplusnet.com';
// }
$mail->SMTPAuth = AUTHORISATION ? false : true ;
$mail->SMTPSecure = SSL == 1 ? 'ssl' : '' ;
$mail->Host = SMTP_SERVER;
$mail->Username = SMTP_USER;
$mail->Password = SMTP_PASSWORD;
$mail->Port = SMTP_PORT;
$mail->Mailer   = SMTP_MAILER;
$mail->FromName = "Cyclope Series";
if ($mail->Mailer == 'mail' || $mail->Mailer == 'smtp'){
	$mail->From = SMTP_USER;
	$mail->Sender = SMTP_USER;
} else {
	$mail->From = ADMIN_EMAIL;
	$mail->Sender = ADMIN_EMAIL;
}
$mail->Subject = $l->lookup("Daily Reports: Overview");
$mail->CharSet = 'UTF-8';
	$recievers = unserialize($dbu->field("SELECT long_value FROM settings WHERE constant_name='CLIENT_INFO'"));
	if($recievers){
		$reciever = $recievers['email'];
	}
	$mail->AddAddress($reciever);
	// $mail->AddAddress('lorand.bognar@amplusnet.com','Lorex');
	// $mail->AddAddress('bognar.lorand@gmail.com','Lorex');
	// $mail->AddAddress('alex.olah@yahoo.com','Lorex');
	// $mail->AddBCC('lorand.bognar@amplusnet.com','Lorex');

debug_log("================================================",'log-emails-extra-daily');
debug_log("report send selected mail options: name: DAILY",'log-emails-extra-daily');
debug_log("report send selected mail options Username ".$mail->Username,'log-emails-extra-daily');
debug_log("report send selected mail options From ".$mail->From,'log-emails-extra-daily');
debug_log("report send selected mail options Sender ".$mail->Sender,'log-emails-extra-daily');
debug_log("report send selected mail options Reciever ".$reciever,'log-emails-extra-daily');
debug_log("report send selected mail options Password ".$mail->Password,'log-emails-extra-daily');
debug_log("report send selected mail options Host ".$mail->Host,'log-emails-extra-daily');
debug_log("report send selected mail options Port ".$mail->Port,'log-emails-extra-daily');
debug_log("report send selected mail options Mailer ".$mail->Mailer,'log-emails-extra-daily');
debug_log("report send selected mail options SMTPSecure ".$mail->SMTPSecure,'log-emails-extra-daily');
debug_log("report send selected mail options SMTPAuth ".$mail->SMTPAuth,'log-emails-extra-daily');

	$body =  $l->lookup("Your receive this email automatically from Cyclope Employee Monitoring Software. 
						Attached to this email is a pdf document that shows the active / idle / online time and monitored users (compared to the total number of users).
						There are 4 charts that shows: Productivity level, Most visited websites (Active Time), Most used applications (Active Time) and Active / Idle times by hour.
						Full details are available by accessing the product interface.");

$mail->Body=$body;

	debug_log("report send selected mail options Body ".$mail->Body,'log-emails-extra-daily');
	debug_log("report send attachment ",'log-emails-extra-daily');
	
$mail->Send();

	debug_log("report sent error (if empty, means it is ok): ".$mail->ErrorInfo,'log-emails-extra-daily');
$mail->ClearAllRecipients();
$mail->ClearAttachments();