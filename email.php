<?php
include_once('config/config.php');
include_once('php/gen/startup.php');
include('misc/class.phpmailer.php');

error_reporting(E_ALL);
ini_set('display_errors',1);
$mail = new PHPMailer();
$mail->Mailer = 'smtp';
$mail->Host = 'licensing.cyclope-series.com';
//$mail->AddAddress('sales@cyclope-series.com','Sales');
//$mail->AddAddress('sales@cylope.ro','Sales');
$mail->AddAddress('zsolt@medeeaweb.com');
$mail->Sender = "trial@cylope-series.com";
$mail->Subject = "New Trial Request";

$body = "Company Name: zs
Name: z
Email: z
Start: r
End: sss
Computers: 30
Licence: \n\n--------------------------------------------------------------------------------------\ndd";

$mail->Body = $body;
var_dump($mail->Send());
var_dump($mail->ErrorInfo);