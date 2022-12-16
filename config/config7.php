<?php
/*************************************************************************
* @Author: Tinu Coman                                          			 *
*************************************************************************/
$conf_data = array(
	'MYSQL_DB_HOST' =>                        "localhost",       //7878The host name of your database. But usualy is simply localhost
	'MYSQL_DB_USER' =>                        "cyclope",            //The User for your database
	'MYSQL_DB_PASS' =>                        "cyclope2012",                //Password of database's User
	'MYSQL_DB_NAME' =>                        "cyclope",  //Database Name
	'MYSQL_EXIT_ON_ERROR' =>                 true  //Exit on Error

);

$site_url="http://localhost:7879/"; //The URL to the public script. (www.your_domain.com/script_folder/)
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
	
 
//debug mode 1 enable,0 disabled
$debug = 0;
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