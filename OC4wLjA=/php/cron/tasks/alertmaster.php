<?php

$departments = $dbu->query("SELECT DISTINCT department_id FROM `alert_department` WHERE 1");
	while ($departments->next()) {
		curl_request_async($site_url.$folder.'/alert.php',array('department' => $departments->f('department_id')),'GET');
	}