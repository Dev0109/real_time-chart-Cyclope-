<?php
/*error_reporting(0);*/
set_time_limit(0);
ignore_user_abort(true);
$dbu = new mysql_db();

include_once('php/gen/startup.php');
include_once('classes/cls_category.php');
$cls_category = new category;
        //      start the timer
                $mtime = microtime();
                $mtime = explode(" ",$mtime);
                $mtime = $mtime[1] + $mtime[0];
                $starttime = $mtime;

//	resolve parents
$select_parents = $dbu->query(
				"SELECT `application_id`,`application_type`,`description`
				FROM `application`
				WHERE `application_type` IN (1,2)
				LIMIT 10000"
			);
	while ($select_parents->next()) {
		$get_app = $dbu->query(
				"SELECT `productivity`,`category`
					FROM `application2category2productivity`
					WHERE `name` = '".$select_parents->f('description')."'
					AND `link_type` = 0"
			);
		if ($get_app->next()) {
				$dept = 1;
				//	set productivity
				if ($get_app->f('productivity') != 1) {
					$dbu->query("DELETE FROM application_productivity WHERE link_id = ".$select_parents->f('application_id')." AND link_type < 3");
					$dbu->query("INSERT INTO application_productivity (	
									link_id, 	department_id, 	productive, 	link_type ) 
									VALUES (".$select_parents->f('application_id').", ".$dept.", ".$get_app->f('productivity')."," . $select_parents->f('application_type') . ")");
				}
		}
	}
	
	
        //end the timer
                        $mtime = microtime();
                        $mtime = explode(" ",$mtime);
                        $mtime = $mtime[1] + $mtime[0];
                        $endtime = $mtime;
        //      print the timer
                        $totaltime = ($endtime - $starttime);