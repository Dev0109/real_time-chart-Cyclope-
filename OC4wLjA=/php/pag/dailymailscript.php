<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/

error_reporting(0);
set_time_limit(0);
ignore_user_abort(true);
$dbu = new mysql_db();
$dbu->query("SELECT value,long_value FROM settings WHERE constant_name='CRON_DAY'");
$dbu->move_next();
$now = time();
if( ($dbu->f('value') <= $now) && $dbu->f('long_value') >= $now )
{
	return ;
}
$day_start = mktime(0,0,0,date('m'),date('d'),date('Y'));
$day_end = mktime(23,59,59,date('m'),date('d'),date('Y'));

$dbu->query("UPDATE settings SET value='".$day_start."', long_value='".$day_end."' WHERE constant_name='CRON_DAY'");

require_once(CURRENT_VERSION_FOLDER."misc/class.phpmailer.php");

$opt = array(
	1	=> 'Overview',
	2	=> 'Users Activity',
	3	=> 'Attendance',
	4	=> 'Overtime',
	5	=> 'Productivity Report',
	6	=> 'Productivity Alerts', 
	7	=> 'Application Usage (Aggregated)', 
	24	=> 'Application Usage (Per User)',
	8	=> 'Application Alerts',
	9	=> 'Document Monitoring',
	10	=> 'Internet Activity (Links)', 
	26	=> 'Internet Activity (Page Titles)', 
	27	=> 'Internet Activity (Domains)', 
	11	=> 'Chat Monitoring',
	12	=> 'Application Forms',
	13	=> 'Activity Categories',
	15	=> 'File Activity',
	16	=> 'Software Inventory',
	25	=> 'Software Updates',
	17	=> 'Top Productive',
	18	=> 'Top Unproductive',
	19	=> 'Top Active',
	20	=> 'Top Idle',
	21	=> 'Top Online',
	22	=> 'Top Websites',
	23	=> 'Top Applications',
);

$target = array(
	1	=> 'overview',
	2	=> 'usersactivity',
	3	=> 'attendance',
	4	=> 'overtime',
	5	=> 'productivityreport', 
	7	=> 'applicationusageaggregated', 
	24	=> 'applicationusageperuser', 
	9	=> 'document',
	10	=> 'interneturls', 
	11	=> 'chat',
	12	=> 'applicationforms',
	13	=> 'categoryactivity',
	15	=> 'file',
	16	=> 'softwareinventory',
	25	=> 'softwareupdates',
	17	=> 'topproductive',
	18	=> 'topunproductive',
	19	=> 'topactive',
	20	=> 'topidle',
	21	=> 'toponline',
	22	=> 'topwebsites',
	23	=> 'topapplications',
	26	=> 'internetwindows', 
	27	=> 'internetdomains', 
);
$page = array(
	1	=> 'overview',
	2	=> 'usersactivity',
	3	=> 'attendance',
	4	=> 'overtime',
	5	=> 'productivityreport', 
	7	=> 'applicationusage', 
	24	=> 'applicationusage', 
	9	=> 'document',
	10	=> 'internet', 
	11	=> 'chat',
	12	=> 'applicationforms',
	13	=> 'categoryactivity',
	15	=> 'file',
	16	=> 'softwareinventory',
	25	=> 'softwareinventory',
	17	=> 'topproductive',
	18	=> 'topproductive',
	19	=> 'topactive',
	20	=> 'topactive',
	21	=> 'topwebsites',
	22	=> 'topwebsites',
	23	=> 'topapplications',
	26	=> 'internet', 
	27	=> 'internet', 
);
$l = new LanguageParser();
$mail = new PHPMailer();
$mail->SMTPAuth = AUTHORISATION ? false : true ;
$mail->SMTPSecure = SSL == 1 ? 'ssl' : '' ;
$mail->Host = SMTP_SERVER;
$mail->Username = SMTP_USER;
$mail->Password = SMTP_PASSWORD;
$mail->Port = SMTP_PORT;
$mail->Mailer   = SMTP_MAILER;
$mail->FromName = "Cyclope Series";
if ($mail->Mailer == 'mail'){
	$mail->From = SMTP_USER;
	$mail->Sender = SMTP_USER;
} else {
	$mail->From = ADMIN_EMAIL;
	$mail->Sender = ADMIN_EMAIL;
}
$mail->CharSet = 'UTF-8';

$mail->Subject = $l->lookup("Daily Reports");

file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',"report send selected mail options"."\n",FILE_APPEND);
file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',"report send selected mail options Username ".$mail->Username."\n",FILE_APPEND);
file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',"report send selected mail options Password ".$mail->Password."\n",FILE_APPEND);
file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',"report send selected mail options Host ".$mail->Host."\n",FILE_APPEND);
file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',"report send selected mail options Port ".$mail->Port."\n",FILE_APPEND);
$dbu->query("SELECT email_report_frequency.email_report_id,
					email_report.description,
					email_report.name,
					email_report_details.body,
					email_report_details.subject,
					email_report_details.attachment_type
					FROM email_report_frequency 
					INNER JOIN email_report ON email_report.email_report_id = email_report_frequency.email_report_id
					LEFT JOIN email_report_details ON email_report_details.email_report_id = email_report.email_report_id
					WHERE frequency='1'");
$reports = array();
while($dbu->move_next())
{
	$auxiliary_dbu = new mysql_db();
	$report = array();
	$report['description'] = $dbu->f('description');
	$report['email_report_id'] = $dbu->f('email_report_id');
	$report['name'] = $dbu->f('name');
	$report['body'] = $dbu->f('body');
	$report['subject'] = $dbu->f('subject');
	$report['attachment_type'] = $dbu->f('attachment_type');
	$auxiliary_dbu->query("SELECT email_report_group.department_id,
								  department.name 
							FROM email_report_group
							INNER JOIN department ON department.department_id = email_report_group.department_id
							WHERE email_report_id='".$dbu->f('email_report_id')."' ORDER BY department_id ASC");
	$report['groups'] = array();
	while ($auxiliary_dbu->move_next())
	{
		file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',"report group ".$auxiliary_dbu->f('name')."\n",FILE_APPEND);
		$group = array();
		$group['name'] = $auxiliary_dbu->f('name');
		$group['department_id'] = $auxiliary_dbu->f('department_id');
		array_push($report['groups'],$group);
	}
	$auxiliary_dbu->query("SELECT type FROM email_report_type WHERE email_report_id='".$report['email_report_id']."' AND type != 14");
	$report['types'] = array();
	while ($auxiliary_dbu->move_next())
	{
		file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',"report type ".$opt[$auxiliary_dbu->f('type')]."\n",FILE_APPEND);
		array_push($report['types'],$auxiliary_dbu->f('type'));
	}
	$report['sender'] = $auxiliary_dbu->field("SELECT email 
												FROM email_report_sender 
												WHERE email_report_id='".$dbu->f('email_report_id')."'");
	$auxiliary_dbu->query("SELECT email 
							FROM email_report_receiver 
							WHERE email_report_id='".$report['email_report_id']."'");
	$report['receivers'] = array();
	while ($auxiliary_dbu->move_next())
	{
		file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',"report receiver ".$auxiliary_dbu->f('email')."\n",FILE_APPEND);
		array_push($report['receivers'],$auxiliary_dbu->f('email'));
	}
	array_push($reports,$report);
	file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',"report description ".$report['description']."\n",FILE_APPEND);
	file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',"report name ".$report['name']."\n",FILE_APPEND);
	file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',"report id ".$report['email_report_id']."\n",FILE_APPEND);
	file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',"report sender ".$report['sender']."\n",FILE_APPEND);
}
$frequency = "Daily";
foreach ($reports as $report)
{
	$body = "
	[!SELECTED_REPORTS!] 
	
	[!FOR!]

	[!FREQUENCY!]

	[!DESCRIPTION!]"
	;
	file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',"report id ".$report['email_report_id']."\n",FILE_APPEND);
	$description = $report['description'];
 	$mail->FromName = $mail->From = $report['sender'];
 	if ($report['subject']){
 		$mail->Subject = $report['subject'];
 	}
 	foreach ($report['receivers'] as $receiver)
 	{
 		file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',"report receiver ".$receiver."\n",FILE_APPEND);
 		$mail->AddAddress($receiver);
 	}
 	$selected_reports = '';
	foreach ($report['types'] as $type)
 	{
 		file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',"report type ".$opt[$type]."\n",FILE_APPEND);
 		$selected_reports .= $opt[$type].', ';
 		$for = '';
 		foreach ($report['groups'] as $group)
 		{
 			file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',"report group ".$group['name']."\n",FILE_APPEND);
 			$for .= $group['name'].', ';
 			session_start();
			$_SESSION['filters']['t'] = 'session';
			$_SESSION['filters']['f'] = 's'.$group['department_id'];
			$_SESSION['filters']['time'] = Array
    		(
        		'time' => date("n/d/Y", strtotime("- 9 days")),
        		'type' => '1'
    		);
    		switch ($report['attachment_type']){
    			case '1': $_SESSION['attachment_name'] = str_replace(' ','_',$opt[$type].' Daily '.$group['name'].'.pdf'); break;
    			case '2': $_SESSION['attachment_name'] = str_replace(' ','_',$opt[$type].' Daily '.$group['name'].'.csv'); break;
    			
    		}
    		$_SESSION[ACCESS_LEVEL]=1;
    		$_SESSION[UID]=1;
    		session_write_close();
    		file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',"report attachment include"."\n",FILE_APPEND);
    		switch ($report['attachment_type']){
	    		case '1': do_post_request($site_url.'/index.php','pag='.$target[$type].'pdf&session_id='.session_id()); break;
	    		case '2': do_post_request($site_url.'/index.php','pag='.$page[$type].'&act=reports-'.$target[$type].'&session_id='.session_id());
	    				  file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',$site_url.'/index.php?pag='.$page[$type].'&act=reports-'.$target[$type].'&session_id='.session_id()."\n",FILE_APPEND); 
	    				  break;
	 
    		}
    		file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',"report attachment included"."\n",FILE_APPEND);
 			if (file_exists($tmp."/".$_SESSION['attachment_name']))
 			{
    			$mail->AddAttachment($tmp."/".$_SESSION['attachment_name'],$_SESSION['attachment_name']);
    		}
    		unset($_SESSION['attachment_name']);
 		}
 	}

	if ($report['body']){
		 $body = $report['body'];
	} else {
				$body = str_replace('[!SELECTED_REPORTS!]', $selected_reports, $body);
				$body = str_replace('[!FOR!]', $for, $body);
				$body = str_replace('[!FREQUENCY!]', $frequency, $body);
				$body = str_replace('[!DESCRIPTION!]', $description, $body);
	}
	
	$mail->Body=$body;
	
	file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',"report send attachment "."\n",FILE_APPEND);
	$mail->Send();
	file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',"report sent error ".$mail->ErrorInfo."\n",FILE_APPEND);
	$mail->ClearAllRecipients();
	$mail->ClearAttachments();
}

