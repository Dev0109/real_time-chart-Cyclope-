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

//	resolve application
$select_unparsed = $dbu->query(
				"SELECT `application_id`,`description`, `application_type`
				FROM `application`
				WHERE `synced` = 0
				LIMIT 10000"
			);
	while ($select_unparsed->next()) {
		$get_app = $dbu->query(
				"SELECT `productivity`,`category`
					FROM `application2category2productivity`
					WHERE `name` = '".$select_unparsed->f('description')."'
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
				if ($get_app->f('productivity') != 1) {
					$dbu->query("INSERT INTO application_productivity (	
									link_id, 	department_id, 	productive, 	link_type ) 
									VALUES (".$select_unparsed->f('application_id').", ".$dept.", ".$get_app->f('productivity').", ".$select_unparsed->f('application_type').")
									ON DUPLICATE KEY UPDATE department_id = ".$dept."");
				}
				//	set category
									
				$dbu->query("INSERT INTO application2category (	
									department_id, 	application_category_id, 	link_id, 	link_type ) 
									VALUES (".$dept.", ".$category_id.", ".$select_unparsed->f('application_id').", ".$select_unparsed->f('application_type').")
									ON DUPLICATE KEY UPDATE department_id = ".$dept." ");

			//	set synced
			$dbu->query("UPDATE `application` SET `synced` = 1 WHERE `application_id` = ".$select_unparsed->f('application_id')."");
		}
	}

//	resolve domain
$select_unparsed = $dbu->query(
				"SELECT `domain_id`,`domain`
				FROM `domain`
				WHERE `synced` = 0
				LIMIT 10000"
			);
	while ($select_unparsed->next()) {
		$get_app = $dbu->query(
				"SELECT `productivity`,`category`
					FROM `application2category2productivity`
					WHERE `name` = '".$select_unparsed->f('domain')."'
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
				if ($get_app->f('productivity') != 1) {
					$dbu->query("INSERT INTO application_productivity (	
									link_id, 	department_id, 	productive, 	link_type ) 
									VALUES (".$select_unparsed->f('domain_id').", ".$dept.", ".$get_app->f('productivity').",3)
									ON DUPLICATE KEY UPDATE department_id = ".$dept." ");
				}
				//	set category
				$dbu->query("INSERT INTO application2category (	
									department_id, 	application_category_id, 	link_id, 	link_type ) 
									VALUES (".$dept.", ".$category_id.", ".$select_unparsed->f('domain_id').",3)
									ON DUPLICATE KEY UPDATE department_id = ".$dept." ");

			//	set synced
			$dbu->query("UPDATE `domain` SET `synced` = 1 WHERE `domain_id` = ".$select_unparsed->f('domain_id')."");
		}
	}
	
	
        //end the timer
                        $mtime = microtime();
                        $mtime = explode(" ",$mtime);
                        $mtime = $mtime[1] + $mtime[0];
                        $endtime = $mtime;
        //      print the timer
                        $totaltime = ($endtime - $starttime);