<?php
echo $site_url.$folder.'/parser.php';
curl_request_async($site_url.$folder.'/parser.php',array('time' => time()));