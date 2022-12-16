<?php
/*************************************************************************
* @Author: Tinu Coman                                          			 *
*************************************************************************/
//vps multi installation 1 enable,0 disabled
$vps = 1;
//debug mode 1 enable,0 disabled
$debug = 1;

if($vps == 1) {
	$url = $_SERVER["HTTP_HOST"];
	$parsedUrl = parse_url($url);
	$host = explode('.', $parsedUrl['host']);
	$subdomain = $host[0];
	$dbname = $subdomain;
	$domain = $_SERVER["HTTP_HOST"];
} else {
	$dbname = 'cyclope';
	$domain = "localhost:7879";
}

$conf_data = array(
	'MYSQL_DB_HOST' =>                        "localhost:7878",       //7878The host name of your database. But usualy is simply localhost
	// 'MYSQL_DB_HOST' =>                        "192.168.1.115:7878",       //7878The host name of your database. But usualy is simply localhost
	// 'MYSQL_DB_HOST' =>                        "appsrv3:7878",       //7878The host name of your database. But usualy is simply localhost
	// 'MYSQL_DB_HOST' =>                        "108.62.143.66:7878",       //7878The host name of your database. But usualy is simply localhost
	// 'MYSQL_DB_HOST' =>                        "192.168.1.104:7878",       //7878The host name of your database. But usualy is simply localhost
	'MYSQL_DB_USER' =>                        "root",            //The User for your database
	'MYSQL_DB_PASS' =>                        "cyclope2011",                //Password of database's User
	'MYSQL_DB_NAME' =>                        $dbname,  //Database Name
	'MYSQL_EXIT_ON_ERROR' =>                 true  //Exit on Error

);

$site_url="http://" . $domain . "/" . $dirname; //The URL to the public script. (www.your_domain.com/script_folder/)
$site_name="Cyclope"; //The URL to the public script. (www.your_domain.com/script_folder/)

//Meta Settings - changable from admin panel
$meta_title="Cyclope";
$meta_keywords="Cyclope";
$meta_description="Cyclope";

// Is mod_rewrite available for search engine friendly URL?
// 1-yes, 0-no
$rewrite_url=0;

// Witch HTML editor to use?
// 1-TinyMCE, 2-KTML
$htmlEditor=1;

define('DEBUG_CONTEXT',$debug);

//----------------------------------------------
foreach($conf_data as $c_key => $c_val)
{
	if(defined($c_key)){
		continue;
	}
	define($c_key,$c_val);
}
        
$is_live=1;         