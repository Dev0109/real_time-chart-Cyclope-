<?php
echo "begin\n";
set_time_limit(0);
ignore_user_abort(true);
include_once('php/gen/startup.php');
include_once('classes/cls_category.php');

$dbu = new mysql_db();
$cls_category = new category;
$done = $dbu->field("SELECT count( * ) FROM `application2category2productivity` WHERE 1");
$filename = CURRENT_VERSION_FOLDER . 'syncdb/sync.sql';
if (file_exists($filename)){
	echo $filename . " found.\n";
} else {
	echo "there is no file: " . $filename . " .\n";
}
echo $done . "\n";


//	sync preset app productions
if ($done < 77000) {
	$dbu->query("TRUNCATE TABLE `application2category2productivity`");
	$templine = '';
	$lines = file($filename);
	foreach ($lines as $line){
		if (substr($line, 0, 2) == '--' || $line == '')
			continue;
		$templine .= $line;
		if (substr(trim($line), -1, 1) == ';'){
			mysql_query($templine) or print("Error performing query \n" . $templine . "\n" . mysql_error() . "\n\n");
			$templine = '';
		}
	}
	 echo "Tables imported successfully";
	 
	//	set all categories
		//	add Uncategorised if does not exist
		$ldr = array(
				'category' => 'Uncategorised',
				'locked' => 1,
		);
		$cls_category->add($ldr);
	$dbu->query("UPDATE `application_category` SET `category` = 'Uncategorised', `locked` = '1' WHERE `application_category`.`application_category_id` = 1");
	$categorynames = $dbu->query("SELECT DISTINCT `category` FROM `application2category2productivity` ORDER BY `category` ASC");
	while($categorynames->next()){
		$ldr = array(
				'category' => $categorynames->f('category'),
				'locked' => 1,
		);
		if (!is_numeric($dbu->field("SELECT `application_category_id` FROM `application_category` WHERE `category` = '".$ldr['category']."'"))){
			$cls_category->add($ldr);
		}
	}
}



//	sync manager departments
$need_manager_sync = $dbu->field("SELECT department_id FROM member2manage2dep LIMIT 1");
if(!$need_manager_sync){
	$managerlines = $dbu->query("SELECT member.member_id, member.department_id, manager_id FROM `member2manage` JOIN member on member.member_id = member2manage.member_id GROUP BY department_id, manager_id");
	while($managerlines->next()){
		$dbu->query("INSERT INTO `member2manage2dep` (`department_id` ,`member_id`) VALUES ('".$managerlines->f('department_id')."', '".$managerlines->f('manager_id')."')");
	}
	 echo "Managers departments synchronized";
}








//	===========================================================
// $catcount = $dbu->field("SELECT count( * ) FROM `application_category` WHERE 1");
// $dbu->query("SELECT category FROM application_category group by category having count(*) >= 2");
// if($this->dbu->move_next())
// {
	// $dbu->query("TRUNCATE TABLE `application_category`");
// }
// $ldr = array(
		// 'category' => 'Uncategorised',
		// 'locked' => 1,
// );
// $cls_category->add($ldr);
// $dbu->query("UPDATE `application_category` SET `category` = 'Uncategorised', `locked` = '1' WHERE `application_category`.`application_category_id` = 1");
// $categorynames = $dbu->query("SELECT DISTINCT `category` FROM `application2category2productivity` ORDER BY `category` ASC");
// while($categorynames->next()){
	// $ldr = array(
			// 'category' => $categorynames->f('category'),
			// 'locked' => 1,
	// );
	// if (!is_numeric($dbu->field("SELECT `application_category_id` FROM `application_category` WHERE `category` = '".$ldr['category']."'"))){
		// $cls_category->add($ldr);
	// }
// }