<?php
set_time_limit(0);
ignore_user_abort(true);
include_once('php/gen/startup.php');
include_once('classes/cls_category.php');

$dbu = new mysql_db();
$limit_date = strtotime('-3 months',time());

$dbu->query("DELETE FROM session_log WHERE end_time < " . $limit_date . " ");