<?php

set_time_limit(0);
ignore_user_abort(true);
include_once('php/gen/startup.php');
include_once('classes/cls_category.php');

$dbu = new mysql_db();

// ANCOM (time frame 14-25.02.2017)
//$sessions = $dbu->query("SELECT `session_id` FROM `session` WHERE `date` >= 1487023200 AND `date` < 1487973600");

// AS TRAVEL (time frame 01.03-23.05.2017)
$sessions = $dbu->query("SELECT `session_id` FROM `session` WHERE `date` >= 1488319200 AND `date` < 1495486800");

while ($sessions->next()){
	
		$activity_update = array();
		$duplicates = $dbu->query("SELECT `session_id`,`application_id`,`link_id`,`window_id`,`type_id`,
					`application_version_id`,`application_path_id`,`start_time`,`end_time`,
					`hour`,`duration`,`active`
					FROM `session_log`
					WHERE `session_id` = ". $sessions->f('session_id')." AND `active` = 1 
					GROUP BY `session_id`,`application_id`,`link_id`,`window_id`,`type_id`,`application_version_id`,`application_path_id`,`start_time`,`end_time`,`hour`,`duration`,`active`
					HAVING COUNT(*)>1 AND COUNT(*)<5");
		
		while ($duplicates->next()){
			$i = -1;
			
			$session_id = $duplicates->f('session_id');
			$i = $duplicates->f('hour');
			
			if ($i != -1) $activity_update[$session_id][$i] += $duplicates->f('duration');
		}
		
		foreach ($activity_update as $session_id) {
			
			foreach ($session_id as $hour => $value) {
			
			
				//print "<pre>";
				//print_r("session:".key($activity_update));
				//print_r("hour:".$hour);
				//print_r("value:".$value);

				$querystring = "UPDATE `session_activity` SET duration = duration - ". $value ." WHERE session_id = ". key($activity_update) ." AND hour = ". $hour.";";
				//$dbu->query($querystring);
				
				echo $querystring;
				echo "\n";
				//print "<pre/>";
			}
		}
		
	//print "<pre>";
	//print_r($activity_update);
	//print "<pre/>";
	
}


