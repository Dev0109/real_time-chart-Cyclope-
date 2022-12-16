<?php
error_reporting(0);
set_time_limit(0);
ignore_user_abort(true);
$dbu = new mysql_db();
require_once(CURRENT_VERSION_FOLDER."misc/class.phpmailer.php");

$opt = array(
	1	=> 'Overview',
	2	=> 'Users Activity',
	3	=> 'Attendance',
	4	=> 'Overtime',
	5	=> 'Productivity',
	6	=> 'Productivity Alerts', 
	7	=> 'Applications (Aggregated)', 
	24	=> 'Applications (Per User)',
	8	=> 'Alerts',
	9	=> 'Documents',
	10	=> 'Internet (Links)', 
	26	=> 'Internet (Page Titles)', 
	27	=> 'Internet (Domains)', 
	11	=> 'Chat Monitoring',
	12	=> 'Application Forms',
	13	=> 'Activity Categories',
	15	=> 'Files',
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
	7	=> 'applicationusage',
	8 	=> 'triggered', 
	24	=> 'applicationusage&tab=peruser', 
	9	=> 'document',
	10	=> 'internet&tab=urls', 
	11	=> 'chat',
	12	=> 'applicationforms',
	13	=> 'categoryactivity',
	15	=> 'file',
	16	=> 'softwareinventory',
	25	=> 'softwareupdates',
	17	=> 'topproductive',
	18	=> 'topproductive&tab=unproductive',
	19	=> 'topactive',
	20	=> 'topactive&tab=idle',
	21	=> 'topwebsites&tab=online',
	22	=> 'topwebsites',
	23	=> 'topapplications',
	26	=> 'internet&tab=windows', 
	27	=> 'internet', 
);
$targetcsv = array(
	1	=> 'overview',
	2	=> 'usersactivity',
	3	=> 'attendance',
	4	=> 'overtime',
	5	=> 'productivityreport', 
	7	=> 'applicationusageaggregated',
	8 	=> 'triggered', 
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
	8 	=> 'triggered', 
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

$freq = 'WEEKLY';

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
if ($mail->Mailer == 'mail' || $mail->Mailer == 'smtp'){
	$mail->From = SMTP_USER;
	$mail->Sender = SMTP_USER;
} else {
	$mail->From = ADMIN_EMAIL;
	$mail->Sender = ADMIN_EMAIL;
}
$mail->Subject = $l->lookup("Weekly Reports");
$mail->CharSet = 'UTF-8';

debug_log("================================================",'log-emails-report-weekly');
debug_log("report send selected mail options",'log-emails-report-weekly');
debug_log("report send selected mail options Username ".$mail->Username,'log-emails-report-weekly');
debug_log("report send selected mail options From ".$mail->From,'log-emails-report-weekly');
debug_log("report send selected mail options Sender ".$mail->Sender,'log-emails-report-weekly');
debug_log("report send selected mail options Password ".$mail->Password,'log-emails-report-weekly');
debug_log("report send selected mail options Host ".$mail->Host,'log-emails-report-weekly');
debug_log("report send selected mail options Port ".$mail->Port,'log-emails-report-weekly');
debug_log("report send selected mail options Mailer ".$mail->Mailer,'log-emails-report-weekly');
debug_log("report send selected mail options SMTPSecure ".$mail->SMTPSecure,'log-emails-report-weekly');
debug_log("report send selected mail options SMTPAuth ".$mail->SMTPAuth,'log-emails-report-weekly');
$dbu->query("SELECT email_report_frequency.email_report_id,
					email_report.description,
					email_report.name,
					email_report_details.body,
					email_report_details.subject,
					email_report_details.attachment_type,
					email_report_details.time_filter
					FROM email_report_frequency 
					INNER JOIN email_report ON email_report.email_report_id = email_report_frequency.email_report_id
					LEFT JOIN email_report_details ON email_report_details.email_report_id = email_report.email_report_id
					WHERE frequency='2'");
$reports = array();
while($dbu->move_next())
{
	if(!$_REQUEST['single'] || $_REQUEST['single'] == $dbu->f('email_report_id')){
		$auxiliary_dbu = new mysql_db();
		$report = array();
		$report['description'] = $dbu->f('description');
		$report['email_report_id'] = $dbu->f('email_report_id');
		$report['name'] = $dbu->f('name');
		$report['body'] = decode_numericentity($dbu->f('body'));
		$report['subject'] = decode_numericentity($dbu->f('subject'));
		$report['attachment_type'] = $dbu->f('attachment_type');
		$report['time_filter'] = $dbu->f('time_filter') ? $dbu->f('time_filter') : 1;
		$auxiliary_dbu->query("SELECT email_report_group.department_id,department.name FROM email_report_group
		INNER JOIN department ON department.department_id = email_report_group.department_id
		WHERE email_report_id='".$dbu->f('email_report_id')."' ORDER BY department_id ASC");
		$report['groups'] = array();
		if($report['time_filter'] == 1){
			$timefilter = $l->lookup("Show All");
			$timefilterbody = '';
		}
		if($report['time_filter'] == 3){
			$timefilter = $l->lookup("Worktime Only");
			$timefilterbody = $l->lookup("Worktime Only");
		}
		if($report['time_filter'] == 4){
			$timefilter = $l->lookup("Overtime Only");
			$timefilterbody = $l->lookup("Overtime Only");
		}
		while ($auxiliary_dbu->move_next())
		{
			debug_log("report group ".$auxiliary_dbu->f('name'),'log-emails-report-weekly');
			$group = array();
			$group['name'] = $auxiliary_dbu->f('name');
			$group['department_id'] = $auxiliary_dbu->f('department_id');
			array_push($report['groups'],$group);
		}
		$auxiliary_dbu->query("SELECT type FROM email_report_type WHERE email_report_id='".$report['email_report_id']."' AND type != 14");
		$report['types'] = array();
		while ($auxiliary_dbu->move_next())
		{
			debug_log("report type ".$l->lookup($opt[$auxiliary_dbu->f('type')]),'log-emails-report-weekly');
			array_push($report['types'],$auxiliary_dbu->f('type'));
		}
		$report['sender'] = $auxiliary_dbu->field("SELECT email FROM email_report_sender WHERE email_report_id='".$dbu->f('email_report_id')."'");
		$auxiliary_dbu->query("SELECT email FROM email_report_receiver WHERE email_report_id='".$report['email_report_id']."'");
		$report['receivers'] = array();
		while ($auxiliary_dbu->move_next())
		{
			debug_log("report receiver ".$auxiliary_dbu->f('email'),'log-emails-report-weekly');
			array_push($report['receivers'],$auxiliary_dbu->f('email'));
		}
		array_push($reports,$report);
		debug_log("report description ".$report['description'],'log-emails-report-weekly');
		debug_log("report name ".$report['name'],'log-emails-report-weekly');
		debug_log("report id ".$report['email_report_id'],'log-emails-report-weekly');
		debug_log("report sender ".$report['sender'],'log-emails-report-weekly');
	}
}
$frequency = $l->lookup("Weekly");
foreach ($reports as $report)
{
	$body = "
	[!SELECTED_REPORTS!]<br> 
	<br>
	[!FOR!]<br>
    <br>
	[!FREQUENCY!]<br>
    <br>
	[!DESCRIPTION!]"
	;
	debug_log("report id ".$report['email_report_id'],'log-emails-report-weekly');
	$description = $report['description'];
 	$mail->FromName = $mail->From = $report['sender'];
 	if ($report['subject']){
 		$mail->Subject = $report['subject'];
 	}
 	foreach ($report['receivers'] as $receiver)
 	{
 		debug_log("report receiver ".$receiver,'log-emails-report-weekly');
 		$mail->AddAddress($receiver);
 	}
 	$selected_reports = '';
	foreach ($report['types'] as $type)
 	{
 		debug_log("report type ".$l->lookup($opt[$type]),'log-emails-report-weekly');
 		$selected_reports .= $l->lookup($opt[$type]).', ';
 		$for = '';
 		foreach ($report['groups'] as $group)
 		{
 			debug_log("report group ".$group['name'],'log-emails-report-weekly');
 			$for .= $group['name'].', ';
 			session_start();
			$_SESSION['filters']['t'] = 'session';
			$_SESSION['filters']['f'] = 's'.$group['department_id'];
			$startTime = mktime(0, 0, 0, date('n'), date('j')-6, date('Y')) - ((date('N'))*3600*24);
			$endTime = mktime(23, 59, 59, date('n'), date('j'), date('Y')) - ((date('N'))*3600*24);
			$_SESSION['filters']['time'] = Array
    		(
        		'time' => date('n/d/Y', $startTime).' - '.date('n/d/Y', $endTime),
        		'type' => $report['time_filter']
    		);
    		switch ($report['attachment_type']){
    			case '1': $_SESSION['attachment_name'] = str_replace(' ','_',text_process($l->lookup($opt[$type]).' '.$l->lookup('Weekly').' (' . $timefilter . ') ' .$group['name']).'.pdf'); 
    				break;
    			case '2': $_SESSION['attachment_name'] = str_replace(' ','_',text_process($l->lookup($opt[$type]).' '.$l->lookup('Weekly').' (' . $timefilter . ') ' .$group['name']).'.csv'); 
    				break;
    		}
    		if (file_exists($tmp."/".$_SESSION['attachment_name']))
 			{
 				unlink($tmp."/".$_SESSION['attachment_name']);
 			}
    		$_SESSION[ACCESS_LEVEL]=1;
    		$_SESSION[UID]=1;
    		session_write_close();
			$timer = time();
			debug_log("report attachment include at " . $timer . ": " . $_SESSION['attachment_name'],'log-emails-report-weekly');
    		switch ($report['attachment_type']){
	    		case '1': do_post_request($site_url.'/index.php','pag='.$target[$type].'&render=pdf&send=email&session_id='.session_id());
				debug_log("report attachment url WEEKLY: " . $site_url.'/index.php?pag='.$target[$type].'&render=pdf&send=email&session_id='.session_id(),'log-emails-report-weekly');
	    				 break;
	    		case '2': do_post_request($site_url.'/index.php','pag='.$page[$type].'&act=reports-'.$targetcsv[$type].'&session_id='.session_id()); 
				debug_log("report attachment url WEEKLY: " . $site_url.'/index.php?pag='.$page[$type].'&act=reports-'.$targetcsv[$type].'&session_id='.session_id(),'log-emails-report-weekly');
	    				 break;
	 
    		}
 			if (file_exists($tmp."/".$_SESSION['attachment_name']))
 			{
				debug_log("report attachment included at " . time() . ", diff of " . (time() - $timer) . ": " . $_SESSION['attachment_name'],'log-emails-report-weekly');
				debug_log("memory usage at " . memory_get_usage()/1024.0 . " kb",'log-emails-report-weekly');
    			$mail->AddAttachment($tmp."/".$_SESSION['attachment_name'],$_SESSION['attachment_name']);
    		}
    		unset($_SESSION['attachment_name']);
 		}
 	}
	if ($report['body']){
		
		 $body = $timefilterbody . "\n" . $report['body'];
	} else {
				$body = str_replace('[!SELECTED_REPORTS!]', $selected_reports, $body);
				$body = str_replace('[!FOR!]', $for, $body);
				$body = str_replace('[!FREQUENCY!]', $frequency, $body);
				$body = $timefilterbody . "\n" . str_replace('[!DESCRIPTION!]', $description, $body);
	}
	
	$mail->Body=$body;
	debug_log("report send attachment ",'log-emails-report-weekly');
	$mail->Send();
	debug_log("report sent error for WEEKLY (if empty, means it is ok): ".$mail->ErrorInfo,'log-emails-report-weekly');
	$mail->ClearAllRecipients();
	$mail->ClearAttachments();
}