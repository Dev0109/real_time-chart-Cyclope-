<?php

$session_website = get_session_website_table();

$jsonDecoder = new Services_JSON();
//$mainlog = $dbu->query("SELECT * FROM tmplog WHERE tmp_id = ?",$log_id);

//while ($mainlog->next()){
	$now = mktime(0,0,0,date('m',$json->sendtime),date('d',$json->sendtime),date('Y',$json->sendtime));
	
	//soon to be deleted?
   $dbu->query("SELECT uninstall_id FROM uninstall WHERE logon = '".$json->user->username."'
									   AND computer = '".$json->user->computer."'");
									   //AND uninstalled = 2");//aici mai vedem daca e bine cu 2(AMPLUSNET-Bogdan)
   if($dbu->move_next()){
   		return;
   }
   
   $is_parasite = false;
   
   
	$ext = '';
	$department_id = 1;
	$member = $dbu->query("SELECT * FROM member WHERE logon = '".$json->user->username.$ext."'");
	if(!$member->next()){
		//insert him
		$member_id = $dbu->query_get_id("INSERT INTO member SET logon = '".$json->user->username.$ext."'");
		member2manage_Rebuild();
		
	}else{
		$member_id = $member->f('member_id'); 
		$department_id = $member->f('department_id');
	}
	//are we in private time
	$private_time = $dbu->query("SELECT * FROM workschedule WHERE day = '".date('w',$json->sendtime)."'
															  AND activity_type = 2
															  AND department_id = '".$department_id."'");
	$is_private = false;
	$private_info = array();
	if($private_time->next()){
		$private_info = array('shour' => date('G',$private_time->f('start_time')),
							  'smin' =>  date('i',$private_time->f('start_time')),
							  'ehour' =>  date('G',$private_time->f('end_time')),
							  'emin' =>  date('i',$private_time->f('end_time'))
		);
		//are we in ze private time?
		$sptime = mktime($private_info['shour'],$private_info['smin']);
		$eptime = mktime($private_info['ehour'],$private_info['emin']);
		if($json->sendtime >= $sptime && $json->sendtime <= $eptime){
			$is_private = true;
		}
	}
	
	//does he have a computer?
	$computer = $dbu->query("SELECT * FROM computer WHERE name = '".$json->user->computer.$ext."'");
	if(!$computer->next()){
		$computer_id = $dbu->query_get_id("INSERT INTO computer SET name = ?,
																	ip = ?,
																	connectivity = ?,
																	`precision` = ?,
																	idlefactor = ?,
																	clientversion = ?
																	",array($json->user->computer.$ext,
																			$json->user->ip,
																			$json->user->connectivity,
																			$json->user->precision,
																			$json->user->idlefactor,
																			$json->user->clientversion));
																															

	 if($uc > AC)
	 {
	 	$parasites = $dbu->field("SELECT long_value FROM settings WHERE constant_name='PARASITES'");
	 	$parasites = unserialize(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, MCRYPT_PASSWORD,base64_decode($parasites),MCRYPT_MODE_ECB));
	 	$parasites = is_array($parasites) ? $parasites : array();
	 	
	 	array_push($parasites,$computer_id);
	 	
	 	$dbu->query("UPDATE settings SET long_value='".base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, MCRYPT_PASSWORD,serialize($parasites),MCRYPT_MODE_ECB))." WHERE constant_name='PARASITES'");
	 	
	 	$is_parasite = true;
	 	
	 }
																															
	}else{
		//is client version up to date?
		if($json->user->clientversion != $computer->f('clientversion')){
			//update!
			$dbu->query("UPDATE computer SET clientversion = ? WHERE computer_id = ?",array($json->user->clientversion,$computer->f('computer_id')));
		}
		$dbu->query("UPDATE computer SET ip = ? WHERE computer_id = ?",array($json->user->ip,$computer->f('computer_id')));
		$computer_id = $computer->f('computer_id');
	}
	//does this member have this computor?
	$computer2member = $dbu->query("SELECT * FROM computer2member WHERE computer_id = ".$computer_id." AND member_id = ".$member_id);
	if(!$computer2member->next()){
		//free PC yay
		$dbu->query("INSERT INTO computer2member SET computer_id = ".$computer_id.",member_id = ".$member_id.", last_record =".$json->sendtime);
	}
	else 
	{
		if($computer2member->f('last_record') < $json->sendtime){
			$dbu->query("UPDATE computer2member SET last_record =".$json->sendtime." WHERE computer_id = ".$computer_id." AND member_id = ".$member_id);
		}
	}
	
	if($is_parasite)
	{
		exit();
	}
	else 
	{
		$parasites = $dbu->field("SELECT long_value FROM settings WHERE constant_name='PARASITES'");
	 	$parasites = unserialize(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, MCRYPT_PASSWORD,base64_decode($parasites),MCRYPT_MODE_ECB));
	 	$parasites = is_array($parasites) ? $parasites : array();
	 	
	 	if(in_array($computer_id,$parasites))
	 	{
	 		exit();
	 	}
	}
	
	//let's just save these session
	$query = $dbu->query("SELECT * FROM session WHERE member_id = ".$member_id." AND computer_id = ".$computer_id." AND date = ".$now);
	if($query->next()){
		$session_id = $query->f('session_id');
	}else{
		$session_id = $dbu->query_get_id("INSERT INTO session SET member_id = ".$member_id.", 
													  computer_id = ".$computer_id.",
													  day = ".date('w',$now).",
													  duration = 0,
													  date = ".$now);
	}
	
	//	PREPARE SYNC INFO
		//	get departments
		$departments = $dbu->query("SELECT `department_id` FROM `department`");
		while ($departments->next()) {
			$department_list[] = $departments->f('department_id');
		}

	$session_duration = 0;
	if(!empty($json->activity) && is_array($json->activity)){
		foreach ($json->activity as $activity){
			//if the duration is bigger then the connectivity then there is something wrong with this log so skip it
			//if(180 < $activity->duration || $activity->duration < 0 ){
			//	continue;
			//}
			//do we haz app?
			$app = $dbu->query("SELECT application_id,application_type,alias FROM application WHERE alias = ?",array(
				 $activity->description
			));
			$app_id = 0;
			if(!$app->next()){
				switch ($activity->type){
					case 'chat':
						$app_type = 1;
						break;
					case 'document':
						$app_type = 2;
						break;	
					case 'website':	
						$app_type = 3;
						break;
					default:
						$app_type = 0;
				}
				$app_id = $dbu->query_get_id("INSERT INTO application SET description = ?, application_type = ?, alias = ?",array(
										$activity->description,$app_type,$activity->description));
										

				//	populate the productivity and category also, if in preset db table!!!!!!!!!!!!!!!
				//	need: app name = $activity->description
				
				
					$get_app = $dbu->query(
							"SELECT `productivity`,`category`,`name`
								FROM `application2category2productivity`
								WHERE `name` = '".$activity->description."'
								AND `link_type` < 3"
						);
					
					if ($get_app->next()) {
							$dept = 1;
							//	get category id from name
							$category_id = $dbu->field("SELECT `application_category_id` FROM `application_category` WHERE `category` LIKE '".$get_app->f('category')."'");
							if(!is_numeric($category_id)){
								$ldr = array(
										'category' => $get_app->f('category'),
										'locked' => 1,
								);
								$cls_category->add($ldr);
								$category_id = $dbu->field("SELECT `application_category_id` FROM `application_category` WHERE `category` LIKE '".$get_app->f('category')."'");
							}
							if(!is_numeric($category_id)){$category_id = 1;}
							//	set productivity
							if( $get_app->f('productivity') != 1 ) {
								$dbu->query("INSERT INTO application_productivity (	
												link_id, 	department_id, 	productive, 	link_type ) 
												VALUES (".$app_id.", ".$dept.", ".$get_app->f('productivity')."," . $app_type . ")
												ON DUPLICATE KEY UPDATE department_id = ".$dept."");
							}
							//	set category
												
							$dbu->query("INSERT INTO application2category (	
												department_id, 	application_category_id, 	link_id, 	link_type ) 
												VALUES (".$dept.", ".$category_id.", ".$app_id."," . $app_type . ")
												ON DUPLICATE KEY UPDATE department_id = ".$dept." ");

						//	set synced
						$dbu->query("UPDATE `application` SET `synced` = 1 WHERE `application_id` = ".$app_id."");
					}
				
				
				
			}else{
				$app_id = $app->f('application_id');
				$app_type = $app->f('application_type');
			}
			
			
			$session_duration += $activity->duration;
					
			//do we have this version?
			$dbu->query("SELECT * FROM application_version WHERE application_id = ? AND version = ?",array($app_id,$activity->version));
			if(!$dbu->move_next()){
				$application_version_id = $dbu->query_get_id("INSERT INTO application_version SET application_id = ?, version = ?",array($app_id,$activity->version));
			}else{
				$application_version_id = $dbu->f('application_version_id');
			}
					
			//do we have this inside productivity?
			
			
			//do we have this path?
			$app_path = str_replace('\\','||',$activity->name);
			$dbu->query("SELECT * FROM application_path WHERE application_id = ? AND path = ?",array($app_id,$app_path));
			if(!$dbu->move_next()){
				$application_path_id = $dbu->query_get_id("INSERT INTO application_path SET application_id = ?, path = ?",array($app_id,$app_path));
			}else{
				$application_path_id = $dbu->f('application_path_id');
			}
			$inventory = $dbu->query("SELECT application_inventory_id FROM application_inventory WHERE 
																								application_id='".$app_id."' 
																								AND application_version_id='".$application_version_id."' 
																								AND member_id='".$member_id."'
																								AND application_path_id='".$application_path_id."'
																								AND computer_id='".$computer_id."'");
			
			//do we have this app for this day/hour
			$session_app = $dbu->query("SELECT session_application_id FROM session_application WHERE session_id = ?
																								 AND application_id = ?
																								 AND day = ?
																								 AND hour = ?
																								 AND time_type = ?
																								 ",array(
																								 $session_id,
																								 $app_id,
																								 date('w',$json->sendtime),
																								 date('G',$json->sendtime),
																								 $is_private ? 1 : 0
																								 ));
			$app_active_duration = 0;
			$app_idle_duration = 0;
			$app_active_private_duration = 0;
			$app_idle_private_duration = 0;
			if($session_app->next()){
				$session_application_id = $session_app->f('session_application_id');
			}else{
				$session_application_id = $dbu->query_get_id("INSERT INTO session_application SET duration = 0,
															 session_id = ".$session_id.",
															 application_id = ".$app_id.",
															 application_version_id = ".$application_version_id.",
															 application_path_id = ".$application_path_id.",
															 day = ".date('w',$json->sendtime).",
															 hour = ".date('G',$json->sendtime).",
															 time_type = ".($is_private ? 1 : 0));
			}
			
			if(!is_array($activity->log) || empty($activity->log)){
				continue;
			}
			//also make an entry for the activity log
			foreach ($activity->log as $log){
				if (is_numeric($log->duration)){ // NULL log fix
					
					if($log->active == 'true'){
						if($is_private){
							$app_active_private_duration += $log->duration;
							$activity_type = 3;
						}else{
							$app_active_duration += $log->duration;
							$activity_type = 1;
						}
					}else{
						if($is_private){
							$app_idle_private_duration += $log->duration;
							$activity_type = 2;
						}else{
							$app_idle_duration += $log->duration;
							$activity_type = 0;
						}
					}
					$session_activity = $dbu->query("SELECT session_activity_id FROM session_activity WHERE session_id = ".$session_id." 
																				  AND hour = ".date('G',$log->timestop)."
																				  AND day = ".date('w',$log->timestop)." 
																				  AND activity_type = ".$activity_type);
					if(!$session_activity->next()){
						$session_activity_id = $dbu->query_get_id("INSERT INTO session_activity SET session_id = ".$session_id.",
																				  hour = ".date('G',$log->timestop).", 
																				  day = ".date('w',$log->timestop).", 
																				  activity_type = ".$activity_type.",
																				  duration = ".$log->duration);
					}else{
						$dbu->query("UPDATE session_activity SET duration = duration + ".$log->duration." WHERE session_activity_id = ".$session_activity->f('session_activity_id'));
					}
					//set the right duration field
					$duration_field = 'duration';
					if($log->active != 'true'){
						$duration_field = 'idle_'.$duration_field;
					}
					
					//window is the base so before we chat we see for a window
					//window is always present even if it's nothing :(
					$window = $dbu->query("SELECT window_id FROM window WHERE application_id = ".$app_id." AND name = '".addslashes($log->window)."'");
					if(!$window->next()){
						$window_id = $dbu->query_get_id("INSERT INTO window SET application_id = ".$app_id.", name = '".addslashes($log->window)."', last_access = ".$json->sendtime);
						//$window_id = $dbu->query_get_id("INSERT INTO window SET application_id = ?, name = ?, `count`=?, first_letter=?",array(
						//	$app_id,addslashes($log->window),strlen(addslashes($log->window)), substr(addslashes($log->window)),0,3));
					}else{
						$window_id = $window->f('window_id');
						$dbu->query("UPDATE window SET last_access = ? WHERE application_id = ? AND window_id = ?",array($json->sendtime,$app_id,$window_id));
					}
					$link_id = 0;
					$type_id = 0;//no know type it
					if($app_type >= 0 ){//not application
						//so figure out what we have and what we need
						$appData = array('table' => '',	'primary' => '','field' => '','index' => 'window','active' => true);
						foreach (array('chat','document','website','unknown') as $type){
							if(!isset($log->$type) || empty($log->$type)){
								continue;
							}
							$appData['index'] = $type;
						}
						switch ($app_type){
							case 1://chat
								$appData['table'] = 'chat';
								$appData['primary'] = 'chat_id';
								$appData['field'] = 'name';
								$type_id = 1;
								break;
							case 2://document
								$appData['table'] = 'document';
								$appData['primary'] = 'document_id';
								$appData['field'] = 'name';
								$type_id = 2;
								break;
							case 3://website
								$appData['table'] = 'website';
								$appData['primary'] = 'website_id';
								$appData['secondary'] = 'domain_id';
								$appData['field'] = 'url';
								$type_id = 3;
								break;
							default: //unknown
								$appData['table'] = 'window';
								$appData['primary'] = 'window_id';
								$appData['field'] = 'name';
								break;
						}
						$appData['active'] = $log->active == 'true' ? true : false;
						$extra = $dbu->query("SELECT ".$appData['primary']." FROM ".$appData['table']." WHERE application_id = ".$app_id." 
																										  AND ".$appData['field']." = '".addslashes($log->$appData['index'])."'");
						
						if(!$extra->next()){
							$link_id = $dbu->query_get_id("INSERT INTO ".$appData['table']." SET application_id = ".$app_id.", ".$appData['field']." = '".addslashes($log->$appData['index'])."', last_access = ".$json->sendtime);
						}else{
							$link_id = $extra->f($appData['primary']);
							// UPDATE last access timestamp for the already found chat_id / document_id / website_id / window_id
							$dbu->query("UPDATE ".$appData['table']." SET last_access = ".$json->sendtime." WHERE ".$appData['primary']." = ".$link_id);
						}
						
						$session_extra = $dbu->query("SELECT session_".$appData['primary'].(($app_type == 3)?",".$appData['secondary']:"")." FROM session_".$appData['table']." WHERE session_id = ".$session_id."
																								AND application_id = ".$app_id."
																								AND ".$appData['primary']." = ".$link_id."
																								AND hour = ".date('G',$log->timestop)."
																								AND day = ".date('w',$log->timestop)."
																								AND time_type = ".($is_private ? 1 : 0));
						
						if($session_extra->next()){
							$dbu->query("UPDATE session_".$appData['table']." SET ".$duration_field." = ".$duration_field." + ".$log->duration." WHERE session_".$appData['primary']." = ".$session_extra->f('session_'.$appData['primary']));
							if(!$is_private && $app_type == 3 && $appData['active'] == true)
							{
								$dbu->query("UPDATE session_website_agg" . " SET duration = duration + ".$log->duration." WHERE session_id = ? AND domain_id = ? AND application_id = ?",array($session_id,$session_extra->f($appData['secondary']),$app_id)); 
							}
						}else{
							if($app_type == 3 && $appData['index'] == 'website' && !$is_private )
							{//website?
								//check for domain
								$domain = str_replace('www.','',getDomain($log->website));
								$domains = $dbu->query("SELECT domain_id FROM domain WHERE domain = ?",$domain);
								if(!$domains->next()){
									$domain_id = $dbu->query("INSERT INTO domain SET domain = '".$domain."', last_access = ".$json->sendtime);
									//	populate the productivity and category also, if in db table!!!!!!!!!!!!!!!
									$get_app = $dbu->query(
											"SELECT `productivity`,`category`
												FROM `application2category2productivity`
												WHERE `name` = '".$domain."'
												AND `link_type` = 3"
										);
									if ($get_app->next()) {
											$dept = 1;
											//	get category id from name
											$category_id = $dbu->field("SELECT `application_category_id` FROM `application_category` WHERE `category` LIKE '".$get_app->f('category')."'");
											if(!is_numeric($category_id)){
												$ldr = array(
														'category' => $get_app->f('category'),
														'locked' => 1,
												);
												$cls_category->add($ldr);
												$category_id = $dbu->field("SELECT `application_category_id` FROM `application_category` WHERE `category` LIKE '".$get_app->f('category')."'");
											}
											if(!is_numeric($category_id)){$category_id = 1;}
												//	set productivity for domain, and set parent
												if( $get_app->f('productivity') != 1 ) {
													$dbu->query("INSERT INTO application_productivity (
																	link_id, 	department_id, 	productive, 	link_type ) 
																	VALUES (".$domain_id.", ".$dept.", ".$get_app->f('productivity').",3)
																	ON DUPLICATE KEY UPDATE department_id = ".$dept." ");
												}
												//	set category
												$dbu->query("INSERT INTO application2category (	
																	department_id, 	application_category_id, 	link_id, 	link_type ) 
																	VALUES (".$dept.", ".$category_id.", ".$domain_id.",3)
																	ON DUPLICATE KEY UPDATE department_id = ".$dept." ");

										//	set synced
										$dbu->query("UPDATE `domain` SET `synced` = 1 WHERE `domain_id` = ".$domain_id."");
									}
									
								}else{
									$domain_id = $domains->f('domain_id');
									// UPDATE last access timestamp for already found domain_id
									$dbu->query("UPDATE domain SET last_access = ".$json->sendtime." WHERE domain_id = ".$domain_id);
								}
								if($appData['active'] == true)
									$dbu->query("INSERT INTO session_website_agg" . " SET session_id = ?, domain_id = ?, application_id=?, duration=? ON DUPLICATE KEY UPDATE duration = duration + ". $log->duration,array($session_id,$domain_id,$app_id, $log->duration));
								//update domain
								$dbu->query("UPDATE website SET domain_id = ?, last_access = ? WHERE website_id = ?",array($domain_id,$json->sendtime,$link_id));
								$dbu->query("INSERT INTO session_".$appData['table']." SET session_id = ".$session_id.",
																		application_id = ".$app_id.",
																		".$appData['primary']." = ".$link_id.",
																		window_id = ".$window_id.",
																		domain_id = ".$domain_id.",
																		hour = ".date('G',$log->timestop).",
																		day = ".date('w',$log->timestop).",
																		".$duration_field." = ".$log->duration.",
																		time_type = ".($is_private ? 1 : 0));
							} else if(($app_type == 1) || ($app_type == 2)){
								$dbu->query("INSERT INTO session_".$appData['table']." SET session_id = ".$session_id.",
																			application_id = ".$app_id.",
																			".$appData['primary']." = ".$link_id.",
																			window_id = ".$window_id.",
																			hour = ".date('G',$log->timestop).", 
																			day = ".date('w',$log->timestop).",
																			".$duration_field." = ".$log->duration.",
																			time_type = ".($is_private ? 1 : 0));
							}
							else{
								$dbu->query("INSERT INTO session_".$appData['table']." SET session_id = ".$session_id.",
																			application_id = ".$app_id.",
																			window_id = ".$window_id.",
																			hour = ".date('G',$log->timestop).", 
																			day = ".date('w',$log->timestop).",
																			".$duration_field." = ".$log->duration.",
																			time_type = ".($is_private ? 1 : 0));
							}
						}
						
					}
					
					//bulky bulk
					$dbu->query("INSERT INTO session_log SET session_id = ".$session_id.",
															application_id = ".$app_id.",
															link_id = ".$link_id.",
															window_id = ".$window_id.",
															type_id = ".$type_id.",
															application_version_id = ".$application_version_id.",
															application_path_id = ".$application_path_id.",
															start_time = ".$log->timestart.",
															end_time = ".$log->timestop.",
															hour = ".date('G',$log->timestop).", 
															duration = ".$log->duration.",
															active =".$activity_type);
					
					$dbu->query("INSERT INTO session_attendance SET
										session_id = ".$session_id.",
										start_time = ".$log->timestart.",
										end_time = ".$log->timestop.",
										active =".$activity_type."
									ON DUPLICATE KEY UPDATE start_time = LEAST(start_time, ".$log->timestart."), end_time = GREATEST(end_time, ".$log->timestop.")"
									);
				}
			}
			if($is_private){
				$dbu->query("UPDATE session_application SET 
															duration = duration + ".$app_active_private_duration.",
															idle_duration = idle_duration + ".$app_idle_private_duration."
															WHERE session_application_id = ".$session_application_id);
			}else{
				$dbu->query("UPDATE session_application SET 
															duration = duration + ".$app_active_duration.",
															idle_duration = idle_duration + ".$app_idle_duration."
															WHERE session_application_id = ".$session_application_id);
			}
		}
	}
	//now get the files
	 if(is_array($json->files) && !empty($json->files)){
		
				
		foreach ($json->files as $file){
			$file_drive = str_replace('\\','||',$file->drive);
			$file_path = str_replace('\\','||',$file->path);
			$dbfile = $dbu->query("SELECT file_id FROM file WHERE `count`=? AND first_letter=? AND drive = ? AND fixed = ? AND path = ?",array(
				strlen($file_path), substr($file_path,0,3), $file_drive,$file->fixed == 'true' ? 1 : 0,$file_path
			));
			if(!$dbfile->next()){
				$file_id = $dbu->query_get_id("INSERT INTO file SET drive = ?, fixed = ?, path = ?, `count`=?, first_letter=?, last_access=?",array(
						$file_drive,$file->fixed,$file_path, strlen($file_path), substr($file_path,0,3), $file->eventtime
			));
			}else{
				$file_id = $dbfile->f('file_id');
				// UPDATE last access timestamp for already found file_id
				$dbu->query("UPDATE file SET last_access = ".$file->eventtime." WHERE file_id = ".$file_id);
			}
			$dbu->query("INSERT INTO session_file SET session_id = ".$session_id.",
													file_id = ".$file_id.",
													eventtime = ".$file->eventtime.",
													hour = ".date('G',$file->eventtime).",
													time_type = ".($is_private ? 1 : 0).",
													action = '".$file->action."'");
		}
	} 
	//now get the print
	 if(is_array($json->print) && !empty($json->print)){
		
				
		foreach ($json->print as $print){
			$dbfile = $dbu->query("SELECT file_id FROM fileprint WHERE path = ?",array($print->path));
			if(!$dbfile->next()){
				$file_id = $dbu->query_get_id("INSERT INTO fileprint SET  path = ?, last_access = ?",array(addslashes($print->path), $print->eventtime));
			}else{
				$file_id = $dbfile->f('file_id');
				// UPDATE last access timestamp for already found file_id
				$dbu->query("UPDATE fileprint SET last_access = ".$print->eventtime." WHERE file_id = ".$file_id);
			}
			$dbprinter = $dbu->query("SELECT printer_id FROM printer WHERE name = ?",array($print->printer));
			if(!$dbprinter->next()){
				$printer_id = $dbu->query_get_id("INSERT INTO printer SET name = ?, alias = ?",array($print->printer, $print->printer));
			}else{
				$printer_id = $dbprinter->f('printer_id');
			}
			$dbu->query("INSERT INTO session_print SET session_id = ".$session_id.",
													file_id = ".$file_id.",
													eventtime = ".$print->eventtime.",
													hour = ".date('G',$print->eventtime).",
													time_type = ".($is_private ? 1 : 0).",
													page_num = '".$print->pages."',
													printer_id = '".$printer_id."'");
		}
	} 
	//update the total of apps
	$dbu->query("UPDATE session SET duration = duration+".$session_duration." WHERE session_id = ".$session_id);
	if($is_parser){
		$dbu->query("UPDATE tmplog SET parsed = 2 WHERE tmp_id = ".$log_id);
	} else {
		$dbu->query("UPDATE tmplog SET parsed = 1 WHERE tmp_id = ".$log_id);
	}
//}
$site_url = $dbu->field("SELECT long_value FROM settings WHERE constant_name ='SITE_URL'");
$folder = $dbu->field("SELECT folder FROM `update` WHERE active = 1");