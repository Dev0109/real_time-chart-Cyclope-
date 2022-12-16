<?php
echo "begin optimizeproductivity\n";
set_time_limit(0);
ignore_user_abort(true);
include_once('php/gen/startup.php');

$dbu = new mysql_db();

//	delete browsers
$dbu->query("DELETE
				FROM `application_productivity`
				WHERE `productive` =3
				AND `link_type` =3");

//	delete browsers
$dbu->query("DELETE
				FROM `application_productivity`
				WHERE `department_id` > 1");

//	make domains parentless
$dbu->query("UPDATE
				`application_productivity`
				SET `parent_id` = '0'
				WHERE `link_type` =3
				AND `parent_id` !=0");

//	delete domains with parents
$dbu->query("DELETE
				FROM `application_productivity`
				WHERE `link_type` =3
				AND `parent_id` !=0");
				
echo "FINISHED updating productivity tables\n";