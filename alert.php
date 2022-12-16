<?php
error_reporting(0);
include('../config/config.php');

if(DEBUG_CONTEXT){
	error_reporting(E_ALL & ~ E_NOTICE);
	date_default_timezone_set('Europe/Bucharest');
}



define('CURRENT_VERSION_FOLDER','');
$glob = array();
include_once("misc/cls_mysql_db.php");
$dbu = new mysql_db();
$dbu->query("SET NAMES 'utf8'");
include_once('module_config.php');
define('ADMIN_PATH',$script_path);
session_start();
//get language ID

$_SESSION['LANG'] = $shortcode;	
	


$dbu->query("SELECT value FROM settings WHERE constant_name='TIME_ZONE'");
if(!$dbu->move_next()){
	return false;
}
date_default_timezone_set($dbu->f('value'));


include('misc/stlib.php');
include_once('misc/cls_ft.php');
include_once('misc/gen_lib.php');
include_once('misc/cyclope_lib.php');
include('php/pag/automaticupdates.php');

function do_post_request($url, $data, $optional_headers = null,$getresponse = false) {
      $params = array('http' => array(
                   'method' => 'POST',
                   'content' => $data
                ));
      if ($optional_headers !== null) {
$lang_id = $dbu->field("SELECT value FROM settings WHERE constant_name = 'LANGUAGE_ID'");

$shortcode = $dbu->field("SELECT shortcode FROM language where language_id = ?",$lang_id);
$shortcode = !empty($shortcode) ? strtoupper($shortcode) : 'EN';

define('LANG',$shortcode);
         $params['http']['header'] = $optional_headers;
      }
      $ctx = stream_context_create($params);
      $fp = @fopen($url, 'rb', false, $ctx);
      if (!$fp) {
        return false;
      }
      if ($getresponse){
        $response = stream_get_contents($fp);
        return $response;
      }
      
      return true;
}

$site_url = $dbu->field("SELECT long_value FROM settings WHERE constant_name ='SITE_URL'");
$folder = $dbu->field("SELECT folder FROM `update` WHERE active = 1");
$tmp = ini_get('upload_tmp_dir');

include('php/pag/dailyemailreport.php');
include('php/pag/weeklyemailreport.php');
include('php/pag/monthlyemailreport.php');

include('php/pag/monitoredreportdaily.php');
include('php/pag/monitoredreportweekly.php');
include('php/pag/monitoredreportmonthly.php');


if(!isset($_POST['department'])){
	exit();
}else{
	$department_id = $_POST['department'];
}
set_time_limit(0);

header('Content-type:text/plain');
$dbu = new mysql_db();
//define online time
define('ONLINE_TIME_INCLUDE',$dbu->field("SELECT value FROM settings WHERE constant_name = 'ONLINE_TIME_INCLUDE'"));

$today = mktime(0,0,0);
$triggered = array();
$query = $dbu->query("SELECT * FROM alert_trigger WHERE department_id = ? AND date = ? ", array($department_id,$today));
while ($query->next()){
	if(!isset($triggered[$query->f('alert_id')]) || !is_array($triggered[$query->f('alert_id')])){
		$triggered[$query->f('alert_id')] = array();
	}
	array_push($triggered[$query->f('alert_id')],$query->f('member_id').'-'.$department_id);
}
//get all the alerts that have been triggered today, because one alert can only be triggered once

$query = $dbu->query("SELECT alert.*,alert_department.department_id FROM alert 
					   INNER JOIN alert_department ON alert_department.alert_id = alert.alert_id
						WHERE alert_department.department_id = '".$department_id."' ORDER BY alert_type ASC");
while ($query->next()){
	if(!isset($triggered[$query->f('alert_id')])){
		$triggered[$query->f('alert_id')] = array();
	}
	//let's see what kind of alert it is
	switch ($query->f('alert_type')){
		case 1://work start alert
			//time() - $dbu->f('last_record') < 180*2
			//get the workschedule for today
			$rule = $dbu->query("SELECT * FROM alert_time WHERE alert_id = ? AND department_id = ? AND day = ?",array($query->f('alert_id'),$department_id,date('w',$today)));
			if(!$rule->next()){
				continue;
			}
			$start_time = mktime(date('G',$rule->f('start_time')),date('i',$rule->f('start_time')),0,date('n',$today),date('d',$today),date('Y',$today));
			$end_time = mktime(date('G',$rule->f('end_time')),date('i',$rule->f('end_time')),0,date('n',$today),date('d',$today),date('Y',$today))+(180*2);//add a small delay

			//slow :(
			$workschedule = $dbu->query("SELECT member.member_id,
												MIN(session_log.start_time) AS start_time,
												MAX(session_log.end_time) AS end_time,
												session.session_id,
												computer.computer_id
												FROM member
										INNER JOIN session ON session.member_id  = member.member_id AND session.date = ".$today."
										INNER JOIN session_log ON session_log.session_id = session.session_id
										INNER JOIN computer ON computer.computer_id = session.computer_id
										WHERE member.department_id =  ".$department_id." AND session_log.start_time > session.date
										GROUP BY member.member_id");
			while ($workschedule->next()){
				if($start_time < $workschedule->f('start_time')  && $workschedule->f('end_time') < $end_time )
				{
					$dbu->query("INSERT INTO alert_trigger SET session_id = ".$workschedule->f('session_id').",
														   alert_id = ".$query->f('alert_id').",
														   member_id = ".$workschedule->f('member_id').",
														   department_id = ".$department_id.",
														   rule_id = ".$rule->f('alert_time_id').",
														   
														   diff = ".$workschedule->f('start_time').",
														   
														   triggered_date = ".time().",
														   date = ".$today.",
														   day = ".date('w',$today).",
														   computer_id = ".$workschedule->f('computer_id').",
														   diff_alt = ".$workschedule->f('end_time').",	
														   triggered = 1 ON DUPLICATE KEY UPDATE diff_alt = ".$workschedule->f('end_time'));
				}
			}
			break;
		case 2://idle alert
			//get the rules
			$rule = $dbu->query("SELECT * FROM alert_other WHERE department_id = ? AND alert_id = ? AND alert_type = 2",array($department_id,$query['alert_id']));
			if(!$rule->next()){
				continue;//can't find the rules? then ignore the rule
			}
			//get all the dudes that are idle
			$idle = $dbu->query("SELECT member.member_id,session.session_id,SUM(session_activity.duration) AS idle, computer.computer_id FROM member 
								INNER JOIN session ON session.member_id = member.member_id AND session.date = ".$today."
								INNER JOIN session_activity ON session_activity.session_id = session.session_id AND session_activity.activity_type = 0
								INNER JOIN computer ON computer.computer_id = session.computer_id
								WHERE member.department_id = ".$department_id." GROUP BY member.member_id");
			while ($idle->next()){
				if($idle['idle'] < $rule['cond'] * 60){
					continue;
				}
				// if(in_array($idle->f('member_id').'-'.$department_id,$triggered[$query->f('alert_id')])){
					// continue;
				// }
				//rule breakers
				$dbu->query("INSERT INTO alert_trigger SET session_id = ".$idle->f('session_id').",
														   alert_id = ".$query->f('alert_id').",
														   member_id = ".$idle->f('member_id').",
														   department_id = ".$department_id.",
														   rule_id = ".$rule->f('alert_other_id').",
														   diff = ".($idle['idle'] - ($rule['cond'] * 60)).",
														   triggered_date = ".time().",
														   date = ".$today.",
														   day = ".date('w',$today).",
														   computer_id = ".$idle->f('computer_id').",
														   triggered = 1 ON DUPLICATE KEY UPDATE diff = ".($idle['idle'] - ($rule['cond'] * 60)));
			}
			break;
		case 3://online
			//get the rules
			$rule = $dbu->query("SELECT * FROM alert_other WHERE department_id = ? AND alert_id = ? AND alert_type = 3",array($department_id,$query['alert_id']));
			if(!$rule->next()){
				continue;//can't find the rules? then ignore the rule
			}
			$online = $dbu->query("SELECT member.member_id,session_application.session_id, SUM(session_application.duration) AS app_duration, computer.computer_id FROM member
									INNER JOIN session ON session.member_id  = member.member_id AND session.date = ".$today."
									INNER JOIN session_application ON session_application.session_id = session.session_id AND session_application.time_type = 0
									INNER JOIN application ON application.application_id = session_application.application_id
									INNER JOIN computer ON computer.computer_id = session.computer_id 
									WHERE member.department_id = ".$department_id." AND application.application_type IN (".ONLINE_TIME_INCLUDE.")  GROUP BY member.member_id");
			while ($online->next()){
				if($online->f('app_duration') < $rule['cond'] * 60){
					continue;
				}
				// if(in_array($online->f('member_id').'-'.$department_id,$triggered[$query->f('alert_id')])){
					// continue;
				// }
				
				//rule breakers
				$dbu->query("INSERT INTO alert_trigger SET session_id = ".$online->f('session_id').",
														   alert_id = ".$query->f('alert_id').",
														   member_id = ".$online->f('member_id').",
														   department_id = ".$department_id.",
														   rule_id = ".$rule->f('alert_other_id').",
														   diff = ".($online['app_duration'] - ($rule['cond'] * 60)).",
														   triggered_date = ".time().",
														   date = ".$today.",
														   day = ".date('w',$today).",
														   computer_id = ".$online->f('computer_id').",
														   triggered = 1  ON DUPLICATE KEY UPDATE diff = ".($online['app_duration'] - ($rule['cond'] * 60)));
			}
			break;
		case 4://app alert
			$apps = $dbu->query("SELECT member.member_id,
										session_application.session_id, 
										alert_other.cond_link,
										SUM(session_application.duration) AS app_duration,
										alert_other.cond,
										alert_other.alert_other_id,
										computer.computer_id
										FROM member
								INNER JOIN session ON session.member_id  = member.member_id AND session.date = ".$today."
								INNER JOIN session_application ON session_application.session_id = session.session_id AND session_application.time_type = 0
								INNER JOIN alert_other ON alert_other.cond_link = session_application.application_id AND alert_other.alert_id = ".$query->f('alert_id')." AND alert_other.alert_type = 4
								INNER JOIN computer ON computer.computer_id = session.computer_id
								WHERE member.department_id = ".$department_id." 
								GROUP BY alert_other.cond_link,member.member_id");
			while ($apps->next()){
				if($apps->f('app_duration') < $apps->f('cond') * 60){
					continue;
				}
				// if(in_array($apps->f('member_id').'-'.$department_id,$triggered[$query->f('alert_id')])){
					// continue;
				// }
				
				//rule breakers
				$dbu->query("INSERT INTO alert_trigger SET session_id = ".$apps->f('session_id').",
														   alert_id = ".$query->f('alert_id').",
														   member_id = ".$apps->f('member_id').",
														   department_id = ".$department_id.",
														   rule_id = ".$apps->f('alert_other_id').",
														   diff = ".($apps->f('app_duration') - ($apps->f('cond') * 60)).",
														   triggered_date = ".time().",
														   date = ".$today.",
														   day = ".date('w',$today).",
														   computer_id = ".$apps->f('computer_id').",
														   triggered = 1 ON DUPLICATE KEY UPDATE diff = ".($apps->f('app_duration') - ($apps->f('cond') * 60)));
			}
			break;
		case 5://monitor alert
			$rule = $dbu->query("SELECT * FROM alert_other WHERE department_id = ? AND alert_id = ? AND alert_type = 5",array($department_id,$query['alert_id']));
			if(!$rule->next()){
				continue;//can't find the rules? then ignore the rule
			}
			$monitor = $dbu->query("SELECT member.member_id,
										  computer2member.last_record,
										  session_id,
										  computer.computer_id
										  FROM member
									INNER JOIN session ON session.member_id  = member.member_id 
									INNER JOIN computer ON computer.computer_id = session.computer_id
									INNER JOIN computer2member ON computer2member.member_id = member.member_id AND computer2member.computer_id = computer.computer_id
									WHERE member.department_id = ".$department_id."
									GROUP BY member.member_id,computer.computer_id");
			$members_to_keep = array();
			$computers_to_keep = array();
			while ($monitor->next()){
				//	if user is already logged in, jump out of this script
				if((time()-$monitor->f('last_record')) < $rule['cond'] * 60){
					continue;
				} //	else
				$members_to_keep[] = $monitor->f('member_id');
				$computers_to_keep[] = $monitor->f('computer_id');
				// if(in_array($monitor->f('member_id').'-'.$department_id,$triggered[$query->f('alert_id')])){
					// continue;
				// }
				//rule breakers
				$dbu->query("INSERT INTO alert_trigger SET session_id = ".$monitor->f('session_id').",
														   alert_id = ".$query->f('alert_id').",
														   member_id = ".$monitor->f('member_id').",
														   department_id = ".$department_id.",
														   rule_id = ".$rule->f('alert_other_id').",
														   diff = ".((time()-$monitor->f('last_record')) - ($rule['cond'] * 60)).",
														   triggered_date = ".time().",
														   date = ".$today.",
														   day = ".date('w',$today).",
														   computer_id = ".$monitor->f('computer_id').",
														   triggered = 1 ON DUPLICATE KEY UPDATE diff = ".((time()-$monitor->f('last_record')) - ($rule['cond'] * 60)));
			}
			if (count($members_to_keep) > 0)
			{
				$dbu->query("DELETE FROM alert_trigger WHERE alert_id = ".$query->f('alert_id')." AND department_id = ".$department_id . " AND (member_id NOT IN (".implode(',',$members_to_keep).") OR computer_id NOT IN (".implode(',',$computers_to_keep)."))");
				debug_log("DELETE FROM alert_trigger WHERE alert_id = ".$query->f('alert_id')." AND department_id = ".$department_id . " AND (member_id NOT IN (".implode(',',$members_to_keep).") OR computer_id NOT IN (".implode(',',$computers_to_keep)."))",'monitortest');
			}
			break;
			case 6://web alert
			$domains = $dbu->query("SELECT member.member_id,
										session_website.session_id, 
										alert_other.cond_link,
										SUM(session_website.duration) AS web_duration,
										alert_other.cond,
										alert_other.alert_other_id,
										computer.computer_id
										FROM member
								INNER JOIN session ON session.member_id  = member.member_id AND session.date = ".$today."
								INNER JOIN session_website ON session_website.session_id = session.session_id AND session_website.time_type = 0
								INNER JOIN alert_other ON alert_other.cond_link = session_website.domain_id AND alert_other.alert_id = ".$query->f('alert_id')." AND alert_other.alert_type = 6
								INNER JOIN computer ON computer.computer_id = session.computer_id
								WHERE member.department_id = ".$department_id." 
								GROUP BY alert_other.cond_link,member.member_id");
			
			while ($domains->next()){
				if($domains->f('web_duration') < $domains->f('cond') * 60){
					continue;
				}
				// if(in_array($domains->f('member_id').'-'.$department_id,$triggered[$query->f('alert_id')])){
					// continue;
				// }
				
				//rule breakers
				$dbu->query("INSERT INTO alert_trigger SET session_id = ".$domains->f('session_id').",
														   alert_id = ".$query->f('alert_id').",
														   member_id = ".$domains->f('member_id').",
														   department_id = ".$department_id.",
														   rule_id = ".$domains->f('alert_other_id').",
														   diff = ".($domains->f('web_duration') - ($domains->f('cond') * 60)).",
														   triggered_date = ".time().",
														   date = ".$today.",
														   day = ".date('w',$today).",
														   computer_id = ".$domains->f('computer_id').",
														   triggered = 1  ON DUPLICATE KEY UPDATE diff = ".($domains->f('web_duration') - ($domains->f('cond') * 60)));
			}
			break;
	}
	
}
$dbu->disconnect();
//$bug = get_debug_instance();
//print_r($bug->display());
echo "1";