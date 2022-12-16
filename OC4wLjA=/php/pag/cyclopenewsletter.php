<?php

require_once("../../misc/class.phpmailer.php");
date_default_timezone_set('Europe/Bucharest');

$mail = new PHPMailer();
$mail->SMTPAuth = true ;
$mail->Host = "mail.amplusnet.com";
echo "emailnews send selected mail options<br>";
echo "emailnews send selected mail options Host<br>";
$mail->Username = "bogdan.dumbrava@amplusnet.com";
echo "emailnews send selected mail options Username<br>";
$mail->Password = "bogdan.dumbrava123x";
echo "emailnews send selected mail options Password<br>";
$mail->Port = 25;
echo "emailnews send selected mail options Port<br>";
$mail->Mailer   = "smtp";
$mail->FromName = "Cyclope Series";
$mail->Sender = 'alex.olah@amplusnet.com';
$mail->Subject = "Cat de productivi sunt angajatii dumneavoastra?";
$mail->Body=@file_get_contents('emailnews.html');
$mail->AddAddress('alex.olah@amplusnet.com');
echo "emailnews send selected mail options";
echo "emailnews send<br>";
$mail->Send();
echo "emailnews sent<br>"; 
echo "emailnews sent error".$mail->ErrorInfo;

