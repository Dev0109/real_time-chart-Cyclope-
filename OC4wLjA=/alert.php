<?php

include_once('../config/config.php');

ignore_user_abort(true);
set_time_limit(0);

header('Content-type:text/plain');

define('CURRENT_VERSION_FOLDER','');

include_once(CURRENT_VERSION_FOLDER . 'module_config.php');
include_once(CURRENT_VERSION_FOLDER . "misc/cyclope_lib.php");
include_once(CURRENT_VERSION_FOLDER . "misc/cls_mysql_db.php");
include_once(CURRENT_VERSION_FOLDER . "misc/json.php");
define('ADMIN_PATH',$script_path);
session_start();
set_time_limit(0);

if(!isset($_REQUEST['department'])){
	exit();
}else{
	$department_id = $_REQUEST['department'];
}

if(DEBUG_CONTEXT){
	error_reporting(E_ALL & ~ E_NOTICE);
	date_default_timezone_set('Europe/Bucharest');
}else{
	error_reporting(0);
}
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
												member.logon,
												MIN(session_log.start_time) AS start_time,
												MAX(session_log.end_time) AS end_time,
												session.session_id,
												computer.computer_id
												FROM member
										INNER JOIN session ON session.member_id  = member.member_id AND session.date = ".$today."
										INNER JOIN session_log ON session_log.session_id = session.session_id
										INNER JOIN computer ON computer.computer_id = session.computer_id
										WHERE member.department_id =  ".$department_id." AND session_log.start_time > session.date
										GROUP BY member.member_id,computer.computer_id");
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
								INNER JOIN session ON session.member_id = member.member_id AND session.date >= ".$today."
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
									INNER JOIN session ON session.member_id  = member.member_id AND session.date >= ".$today."
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
			$department_id_array = $dbu->query("SELECT department_id FROM alert_other WHERE alert_id = ".$query->f('alert_id'));
			$department_array = array();
			while ($department_id_array->next()){
				$department_array[] = $department_id_array->f('department_id');
			}
			$departments = implode(',',$department_array);
			
			$apps = $dbu->query("SELECT member.member_id,
										session_application.session_id, 
										alert_other.cond_link,
										SUM(session_application.duration) AS app_duration,
										alert_other.cond,
										alert_other.alert_other_id,
										computer.computer_id
										FROM member
								INNER JOIN session ON session.member_id  = member.member_id AND session.date >= ".$today."
								INNER JOIN session_application ON session_application.session_id = session.session_id AND session_application.time_type = 0
								INNER JOIN alert_other ON alert_other.cond_link = session_application.application_id AND alert_other.alert_id = ".$query->f('alert_id')." AND alert_other.alert_type = 4
								INNER JOIN computer ON computer.computer_id = session.computer_id
								WHERE member.department_id IN (".$departments.") 
								GROUP BY alert_other.cond_link,member.member_id");
			$count_other = $dbu->field("SELECT COUNT(*) FROM alert_other WHERE alert_id = ".$query->f('alert_id'));
			while ($apps->next()){
			$good_duration = $apps->f('app_duration') / $count_other;
				if($good_duration < $apps->f('cond') * 60){
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
														   diff = ".($good_duration - ($apps->f('cond') * 60)).",
														   triggered_date = ".time().",
														   date = ".$today.",
														   day = ".date('w',$today).",
														   computer_id = ".$apps->f('computer_id').",
														   triggered = 1 ON DUPLICATE KEY UPDATE diff = ".($good_duration - ($apps->f('cond') * 60)));
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
			while ($monitor->next()){
				//	if user is already logged in, jump out of this script
				if((time()-$monitor->f('last_record')) < $rule['cond'] * 60){
					$dbu->query("DELETE FROM alert_trigger WHERE alert_id = ".$query->f('alert_id')." AND department_id = ".$department_id . " AND member_id = ".$monitor->f('member_id')." AND computer_id = ".$monitor->f('computer_id'));
					continue;
				}
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
			break;
			case 6://web alert
								
			$department_id_array = $dbu->query("SELECT department_id FROM alert_other WHERE alert_id = ".$query->f('alert_id'));
			$department_array = array();
			while ($department_id_array->next()){
				$department_array[] = $department_id_array->f('department_id');
			}
			$departments = implode(',',$department_array);
			
			
			$domains = $dbu->query("SELECT member.member_id,
										member.logon,
										session_website.session_id, 
										alert_other.cond_link,
										SUM(session_website.duration) AS web_duration,
										alert_other.cond,
										alert_other.alert_other_id,
										computer.computer_id
										FROM member
								INNER JOIN session ON session.member_id  = member.member_id AND session.date >= ".$today."
								INNER JOIN session_website ON session_website.session_id = session.session_id AND session_website.time_type = 0
								INNER JOIN alert_other ON alert_other.cond_link = session_website.domain_id AND alert_other.alert_id = ".$query->f('alert_id')." AND alert_other.alert_type = 6
								INNER JOIN computer ON computer.computer_id = session.computer_id
								WHERE member.department_id IN (".$departments.")  
								GROUP BY alert_other.cond_link,member.member_id");
			$count_other = $dbu->field("SELECT COUNT(*) FROM alert_other WHERE alert_id = ".$query->f('alert_id'));
			
			
			while ($domains->next()){
				$good_duration = $domains->f('web_duration') / $count_other;
				if($good_duration < $domains->f('cond') * 60){
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
														   diff = ".($good_duration - ($domains->f('cond') * 60)).",
														   triggered_date = ".time().",
														   date = ".$today.",
														   day = ".date('w',$today).",
														   computer_id = ".$domains->f('computer_id').",
														   triggered = 1  ON DUPLICATE KEY UPDATE diff = ".($good_duration - ($domains->f('cond') * 60)));
			}
			break;
		case 7://seq alert
		
			$rule = $dbu->query("SELECT * FROM alert_other WHERE alert_id = ".$query->f('alert_id'));
			while ($rule->next()){
				$members = $dbu->query("SELECT `session_id`, `member_id`, `computer_id` 
												FROM `session`
												GROUP BY member_id, computer_id");
				$yesterday = time() - 60 * 60 * 24;
				$start_time = mktime(0,0,0,date('n',$yesterday),date('d',$yesterday),date('Y',$yesterday));
				$end_time = mktime(23,59,59,date('n',$yesterday),date('d',$yesterday),date('Y',$yesterday));
				$app_join = "INNER JOIN member ON member.member_id = session.member_id INNER JOIN computer ON computer.computer_id = session.computer_id";
				$additional_application_filter = '';

				while ($members->next()){
					$app_filter = " AND session.member_id = '" . $members->f('member_id') . "' AND session.computer_id = '" . $members->f('computer_id') . "' AND (session.date >= " . $start_time . " AND session.date <= " . $end_time . ") AND session_log.start_time >= " . $start_time;

					$dbu->query("SELECT application.description, 
					application.application_id,
					session.session_id as sessid,
					member.logon as membername,
					computer.name as computername,
					window.name,
					session_log.* 
					FROM session_log 
					INNER JOIN window ON window.window_id = session_log.window_id
					INNER JOIN application ON application.application_id = session_log.application_id 
					INNER JOIN session ON session.session_id = session_log.session_id 
					".$app_join."
					WHERE 6=6 ".$app_filter.$additional_application_filter." ORDER BY start_time ASC");

					//$app_details = array();
					// $data = array_fill(0,24,array('tags'=> array(),'total' => 0,'private_end'=> array(),'private_start'=> array(),'private_total' => 0));
					$i = 0;
					$apps = array();
					$index = 0;
					$data = array();
					while ($dbu->move_next()){
					
						$session_id = $dbu->f('sessid');
						if(!is_array($apps[$dbu->f("application_id")])){
							$apps[$dbu->f("application_id")] = array("count"=>0);
						}
						if(!isset($apps[$dbu->f("application_id")][$dbu->f("window_id")])){
							$apps[$dbu->f("application_id")][$dbu->f("window_id")] = 1;
							$apps[$dbu->f("application_id")]["count"]++;
						}
						
						$type = '';
						switch ($dbu->f('type_id')){
							case 1:
								$type = 'chats';
								break;
							case 2:
								$type = 'documents';
								break;
							case 3:
								$type = 'sites';
								break;
							default:
								$type = 'windows';	
						}
						
						if($dbu->f('duration') == 0){
							continue;
						}
						$index = count($data);
						
						if($index != 0)
						{
							
							$indexprev = $index-1;

							if($data[$indexprev]['APP_ID'] == $dbu->f('application_id') && $data[$indexprev]['EXPAND'][0]['window_id'] == $dbu->f('window_id'))
							{
								$data[$indexprev]['TIME'] += $dbu->f('duration');
								$data[$indexprev]['INTERVAL_END'] = date('g:i:s A',$dbu->f('end_time'));
								$data[$indexprev]['TIME_FORMATED'] = format_time($data[$indexprev]['TIME']);
								$str = $data[$indexprev]['TIMELINE_INFO'];
								$end = $data[$indexprev]['END_TIME'];
								$str = str_replace('end='.$end,'end='.$dbu->f('end_time'),$str);
								$data[$indexprev]['TIMELINE_INFO'] = $str;
								$data[$indexprev]['END_TIME'] = $dbu->f('end_time');			
								$data[$indexprev]['EXPAND'][count($data[$indexprev]['EXPAND'])-1]['window_duration'] += $dbu->f('duration');
							}
							else
							{
								$cat_name = 'Uncategorised';
								$cat_id = 1;
								
								if(isset($categories[$dbu->f('application_id').'-0'])){
									$cat_name = $categories[$dbu->f('application_id').'-0']['category'];
									$cat_id = $categories[$dbu->f('application_id').'-0']['category_id'];
								}
								
								array_push($data, array(
									'APP_ID' => $dbu->f('application_id'),
									'APPLICATION' => $dbu->f('description'),
									'ACTIVITY_TYPE' => $dbu->f('active') == 0 || $dbu->f('active') == 1 ? array(0,1) : array(2,3),
									'TIME' => $dbu->f('duration'),
									'INTERVAL_START' => date('g:i:s A',$dbu->f('start_time')),
									'INTERVAL_END' => date('g:i:s A',$dbu->f('end_time')),
									'TIME_FORMATED' => format_time($dbu->f('duration')),
									'TIMELINE_INFO' => 'index_ajax.php?pag=xtimelinedetails&sid='.$dbu->f('session_id').'&app='.$dbu->f('application_id').'&start='.$dbu->f('start_time').'&type='.$dbu->f('type_id').'&end='.$dbu->f('end_time'),
									//'WINDOWS' => 1,
									'APPLICATION_ID' => $dbu->f('application_id'),
									'CATEGORY' => $cat_name,
									'CATEGORY_ID' => $cat_id,
									'APP_FILTER_LINK' => 'index.php?pag=timeline&app='.$dbu->f('application_id'),
									'END_TIME' => $dbu->f('end_time'),
									'WINDOWS_TYPE' => $type,
									'ID' => $dbu->f('application_id'),
									'EXPAND' => array()
									));	
									
									if (is_array($data[$index]['EXPAND'])) {
										array_push($data[$index]['EXPAND'],array(
											'window_id'	=> $dbu->f('window_id'),
											'window_active' => $dbu->f('active'),
											'window_name' => $dbu->f('name'),
											'window_duration' => $dbu->f('duration'),
											'window_filter_link' => 'index.php?pag=timeline&app='.$dbu->f('application_id').'&win='.$dbu->f('window_id').'&wact='.$dbu->f('active')
										));
									}
							}
						}
						else
						{
							$cat_name = 'Uncategorised';
							$cat_id = 1;
							
							if(isset($categories[$dbu->f('application_id').'-0'])){
								$cat_name = $categories[$dbu->f('application_id').'-0']['category'];
								$cat_id = $categories[$dbu->f('application_id').'-0']['category_id'];
							}
							
							array_push($data, array(
								'APP_ID' => $dbu->f('application_id'),
								'ACTIVITY_TYPE' => $dbu->f('active') == 0 || $dbu->f('active') == 1 ? array(0,1) : array(2,3),
								'APPLICATION' => $dbu->f('description'),
								'TIME' => $dbu->f('duration'),
								'INTERVAL_START' => date('g:i:s A',$dbu->f('start_time')),
								'INTERVAL_END' => date('g:i:s A',$dbu->f('end_time')),
								'TIME_FORMATED' => format_time($dbu->f('duration')),
								'WINDOWS_TYPE' => $type,
								//'WINDOWS' => 1,
								'APPLICATION_ID' => $dbu->f('application_id'),
								'CATEGORY' => $cat_name,
								'CATEGORY_ID' => $cat_id,
								'APP_FILTER_LINK' => 'index.php?pag=timeline&app='.$dbu->f('application_id'),
								'TIMELINE_INFO' => 'index_ajax.php?pag=xtimelinedetails&sid='.$dbu->f('session_id').'&app='.$dbu->f('application_id').'&start='.$dbu->f('start_time').'&type='.$dbu->f('type_id').'&end='.$dbu->f('end_time'),
								'END_TIME' => $dbu->f('end_time'),
								'ID' => $dbu->f('application_id'),
								//'SEL' => !is_null($dbu->f('productive')) && in_array($dbu->f('productive'),array(0,2)) ? $dbu->f('productive') : 1,
								//'CSS_CLASS' => 'neutral',
								'EXPAND' => array()
							));	
							
							if (is_array($data[$index]['EXPAND'])) {
								array_push($data[$index]['EXPAND'],array(
									'window_id'	=> $dbu->f('window_id'),
									'window_active'	=> $dbu->f('active'),
									'window_name'	=> $dbu->f('name'),
									'window_duration'	=> $dbu->f('duration'),
									'window_filter_link' => 'index.php?pag=timeline&app='.$dbu->f('application_id').'&win='.$dbu->f('window_id').'&wact='.$dbu->f('active')
								));
							}
						}

					}

					// clean up the array
					foreach($data as $key => $value) {
						if (!$value['APP_ID']) {
							unset ($data[$key]);
						}
					}


					$sequence_list = array();
					$sequencename = $dbu->field("SELECT `name` FROM `sequence_reports` WHERE `sequencegrp_id` = " . $rule['cond_link']);
					$sequencenoise = $dbu->field("SELECT `noise` FROM `sequence_reports` WHERE `sequencegrp_id` = " . $rule['cond_link']);
					$sequence_list_db = $dbu->query("SELECT * FROM `sequence_list` WHERE `sequencegrp_id` = " . $rule['cond_link']);
					$sequencecount[$sequencename] = 0;
					while ($sequence_list_db->next()){
						$sequence_list[$sequence_list_db->f("weight")] = array('APP_ID' => $sequence_list_db->f("app_id"), 'window_id' => $sequence_list_db->f("form_id"));
					}
					ksort($sequence_list);
					if ($sequencenoise == 1) {
						$intersect = array_uintersect( $data, $sequence_list, 'compareDeepValue');
						$to_elements = find_sequence_in_timeline($sequence_list, $intersect);
					} else {
						$to_elements = find_sequence_in_timeline($sequence_list, $data);
					}
					$data = apply_sequence_to_timeline($data, $to_elements, $sequencename);
					$sequencecount[$sequencename] = count_sequence_in_timeline($data, $to_elements, $sequencename);
					if (($sequencecount[$sequencename] < $rule['cond']) && $session_id) { // sequences appear less than they should, but user has a valid session for today
						echo 'rule breaker found for sequence: ' . $sequencename . ', in alert id: ' . $query->f('alert_id') . 'for sequence id: ' . $rule['cond_link'] . ' in session ' . $session_id . "-" . $members->f('member_id') . "-" . $members->f('computer_id') . "\n";
						//rule breakers
						$dbu->query("INSERT INTO alert_trigger SET session_id = '".$session_id."',
														   alert_id = ".$query->f('alert_id').",
														   member_id = ".$members->f('member_id').",
														   department_id = ".$department_id.",
														   rule_id = ".$rule->f('alert_other_id').",
														   diff = ".($rule['cond'] - $sequencecount[$sequencename]).",
														   triggered_date = ".time().",
														   date = ".$today.",
														   day = ".date('w',$today).",
														   computer_id = ".$members->f('computer_id').",
														   triggered = 1 ON DUPLICATE KEY UPDATE diff = ".($rule['cond'] - $sequencecount[$sequencename]));
					$session_id = false;
					}
				}
				break;
			
			}
	}
}