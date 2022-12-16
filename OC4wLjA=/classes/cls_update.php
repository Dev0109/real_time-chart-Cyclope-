<?php
/***********************************************************************
* @Author: MedeeaWeb Works											   *
***********************************************************************/
class update
{
	var $dbu;
	var $ftp;
	var $is_connected = false;
	var $_error = array();
	var $automatic_update = false;
	
	function update()
	{
		$this->dbu = new mysql_db();
		set_time_limit(0);
		$this->dbu->query("SELECT value FROM settings WHERE constant_name ='SERVER_VERSION'");
		$this->dbu->move_next();
		define('SERVER_VERSION',$this->dbu->f('value'));
		$this->dbu->query("SELECT value FROM settings WHERE constant_name ='EXTRAINFO_URL'");
		$this->dbu->move_next();
		define('EXTRAINFO_URL',$this->dbu->f('value'));
	}
	
	function _triggerError($errorMSG){
		if(strlen($errorMSG)){
			array_push($this->_error,$erorMSG);
		}
	}
	
	/****************************************************************
	* function download(&$ld)                                       *
	****************************************************************/
	function download(&$ld)
	{	
		if(!$this->download_validate($ld))
		{
			return false;
		}
		
		foreach ($ld['available_updates'] as $version => $object )
		{	
			if(in_array($version,$ld['already_downloded_versions']))
			{
				debug_log('already_downloded_versions','log-update', NULL, 1);
				continue;
			}
			else 
			{	
				file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',$object->archive."\n",FILE_APPEND);
				if($object->archive_full_path) {
					$archive_full_path = $object->archive_full_path;
				} else {
					$archive_full_path = "ftp://".FTP_HOST."/".FTP_PUBLIC_HTML."/".$object->archive;
				}
				if(file_put_contents(($this->automatic_update == true ? '../updates/' : 'updates/').$object->archive, fopen($archive_full_path, 'r')))
				{
					debug_log('file_put_contents OK','log-update', NULL, 1);
					$result = $this->dbu->query("INSERT INTO `update` SET
						version = '".$version."',
						active = 2,
						notes = '".$object->notes."',
						archive = '".($this->automatic_update == true ? '../updates/' : 'updates/').$object->archive."',
						download_date = ".time());
				}
				else 
				{
					debug_log('file_put_contents WRONG','log-update', NULL, 1);
					$ld['error'] .= "ERROR downloading ftp://".FTP_HOST."/".FTP_PUBLIC_HTML."/".$object->archive." on downloaded for version ".$version." !";
					return false;
				}
			}
		}
			
		$ld['error'] .= "ftp://".FTP_HOST."/".FTP_PUBLIC_HTML."/".$object->archive." successfuly downloaded to " . ($this->automatic_update == true ? '../updates/' : 'updates/').$object->archive;
		return true;
	}
	
	/****************************************************************
	* function download_validate(&$ld)                              *
	****************************************************************/
	function download_validate(&$ld)
	{
		//no need for update
		
		include_once(CURRENT_VERSION_FOLDER."misc/json.php");
		
		
		$this->dbu->query("SELECT ( SELECT long_value FROM settings WHERE constant_name = 'LICENCEKEY') AS licencekey, 
						  		  ( SELECT COUNT(*) FROM computer2member) AS number_of_monitored_users,
						  		  ( SELECT COUNT(*) FROM application_productivity) AS number_of_application_productivities,
						  		  ( SELECT COUNT(*) FROM computer) AS number_of_monitored_computers,
								  ( SELECT MAX(last_record) FROM page_tracker) AS last_user_interface_access,
						  		  ( SELECT 
     								CONCAT(\"{\"\"page_tracker\"\" : [ \",
          								SUBSTRING_INDEX(GROUP_CONCAT(
               								CONCAT(\"{\"\"name\"\":\"\"\",name,\"\"\"\"),
			   								CONCAT(\",\"\"total_views\"\":\",total_views,\"\"),
											CONCAT(\",\"\"new_views\"\":\",new_views,\"\"),
               								CONCAT(\",\"\"last_record\"\":\",last_record,\"}\")
											ORDER BY total_views DESC )  , \",\", 40) 
     								,\"]}\") FROM page_tracker ) 
     							  AS page_tracker_json,
								 (SELECT 
								  CONCAT(\"{\"\"clientversion\"\" :[\",
									GROUP_CONCAT( 
										CONCAT(\"{\"\"count\"\":\",computer_computer_id.count,\",\"\"version\"\":\"\"\",computer_computer_id.version,\"\"\"}\") 
									SEPARATOR ',' ),\"]}\")
								    FROM 
										( SELECT REPLACE(clientversion, ',', '.') AS version, 
										  COUNT(*) AS count
  										  FROM computer 
  										  GROUP BY clientversion 
				                          ORDER BY count DESC 
				                          LIMIT 3 ) computer_computer_id) AS clientversion_json");
		
		$this->dbu->move_next();
		
		
		$extra_info = array(
	        "licencekey" => urlencode($this->dbu->f("licencekey")),
	        "server_version" => SERVER_VERSION,
	        "number_of_monitored_users" => $this->dbu->f("number_of_monitored_users"),
	        "number_of_application_productivities" => $this->dbu->f("number_of_application_productivities"),    
			"number_of_monitored_computers" => $this->dbu->f("number_of_monitored_computers"),
			"last_user_interface_access" => $this->dbu->f("last_user_interface_access"),
			"page_tracker_json" => $this->dbu->f("page_tracker_json"),
			"clientversion_json" => $this->dbu->f("clientversion_json"),
      	);	
      	
		 
		
      	$options = array('http' => array(
			'method'  => 'POST',
      		'header'  => 'Content-type: application/x-www-form-urlencoded',
			'content' => implode("&", $extra_info)
		));
		
		$context  = stream_context_create($options);

		$cyclope_json_version = @file_get_contents(EXTRAINFO_URL,false,$context);

		
		$this->dbu->query("UPDATE page_tracker SET new_views = 0");
		
		
		$jsonDecoder = new Services_JSON();
		$cyclope = $jsonDecoder->decode($cyclope_json_version);
		
		
		
		if(!is_array(get_object_vars($cyclope)))
		{
			$ld['error'] .= 'Unable to acces the online update directory 123123!';
			return false;
		}
		
		if(end(array_keys(get_object_vars($cyclope))) <= SERVER_VERSION )
		{
			$ld['error'] .= 'No need to update you have the latest version !';
			return false;
		}
		
		if (!is_dir(($this->automatic_update == true ? '../updates/' : 'updates/')))
		{
			mkdir(($this->automatic_update == true ? '../updates/' : 'updates/'));	
		}
		
		$ld['available_updates'] = get_object_vars($cyclope);
		
		$ld['already_downloded_versions'] = array();
		
		$this->dbu->query("SELECT version FROM `update` WHERE active != 2");
		
		while ($this->dbu->move_next()) {
			array_push($ld['already_downloded_versions'], $this->dbu->f('version'));		
		}
		
		return true;
	}
	
	/****************************************************************
	* function unzip(&$ld)                                          *
	****************************************************************/
	function unzip(&$ld)
	{
		if(!$this->unzip_validate($ld))
		{
			return false;
		}
		
		$versions = $this->dbu->query("SELECT archive, version, update_id FROM `update` WHERE active='2' ORDER BY update_id ASC");
		
		while ($versions->next())
		{
			$zip = new ZipArchive;
			$res = $zip->open($versions->f('archive'));
			debug_log('extractin OK ' . $res,'log-update', NULL, 1);
			if ($res === TRUE)
			{
				debug_log('res OK','log-update', NULL, 1);
				$zip->extractTo(($this->automatic_update == true ? '../' : '').base64_encode($versions->f('version')));
				$zip->close();
				
				$this->dbu->query("UPDATE `update` SET folder = '".base64_encode($versions->f('version'))."' WHERE update_id ='".$versions->f('update_id')."'");
			}
			else 
			{
				$ld['error'] .= "Error on extract: ". $res;
				return false;
			}
			unset($zip);
		}
		  		
    	$ld['error'] .= "Archive successfully extracted!";
    	return true;
	}
	
	/****************************************************************
	* function unzip_validate(&$ld)                                 *
	****************************************************************/
	function unzip_validate(&$ld)
	{
		$this->dbu->query("SELECT archive, version FROM `update` WHERE active ='2' ORDER BY update_id ASC");
		
		if($this->dbu->records_count()==0)
		{
			return false;
		}
		
		while ($this->dbu->move_next()) 
		{
			if(file_exists($this->dbu->f('archive')))
			{	
				if (!is_dir(base64_encode($this->dbu->f('version'))))
				{
					mkdir(base64_encode($this->dbu->f('version')));
				}
			}
			else 
			{
				return false;
			}	
		}
		
		
		return true;
		
	}
	
	/****************************************************************
	* function altertables(&$ld)                                    *
	****************************************************************/
	function altertables(&$ld)
	{
		if(!$this->altertables_validate($ld))
		{
			return false;
		}
		
		$versions = $this->dbu->query("SELECT version, update_id FROM `update` WHERE active='2' ORDER by update_id ASC");
		
		while ($versions->next()) {
			
			if(is_file(($this->automatic_update == true ? '../' : '').base64_encode($versions->f('version')).'/database.sql'))
			{
				$content = @file_get_contents(($this->automatic_update == true ? '../' : '').base64_encode($versions->f('version')).'/database.sql');
				
				$query = explode(';',$content);
				debug_log('queries: ','log-update', NULL, 1);
				debug_log(print_r($query,1),'log-update', NULL, 1);
				debug_log('version: ' . $versions->f('version'),'log-update', NULL, 1);
				foreach ($query as $sql_command)
				{
					debug_log('==============================================','log-update', NULL, 1);
					if($sql_command)
					{
						debug_log($sql_command,'log-update', NULL, 1);
						$this->dbu->query($sql_command);
						debug_log('OK','log-update', NULL, 1);
					}
				}	
			}
			debug_log('version2: ' . $versions->f('version'),'log-update', NULL, 1);
			debug_log('finished all queries successfully','log-update', NULL, 1);
			$last_update_id = $versions->f('update_id');
						debug_log('last update id = ' . $last_update_id,'log-update', NULL, 1);
			$last_version = $versions->f('version');
						debug_log('last version id = ' . $last_version,'log-update', NULL, 1);
		}
		
		
		debug_log("UPDATE settings SET value='".$last_version."', long_value ='".$last_version."' WHERE constant_name ='SERVER_VERSION'",'log-update', NULL, 1);
		$this->dbu->query("UPDATE settings SET value='".$last_version."', long_value ='".$last_version."' WHERE constant_name ='SERVER_VERSION'");
		$this->dbu->query("UPDATE `update` SET active = 0");
		debug_log("UPDATE `update` SET active = 1 WHERE update_id='".$last_update_id."'", 'log-update', NULL, 1);
		$this->dbu->query("UPDATE `update` SET active = 1 WHERE update_id='".$last_update_id."'");
				
		$ld['error'] .= 'Database successfuly updated';
		return true;
	}
	
	/****************************************************************
	* function altertables_validate(&$ld)                           *
	****************************************************************/
	function altertables_validate(&$ld)
	{
		$this->dbu->query("SELECT archive, version FROM `update` WHERE active ='2'");
		
		if($this->dbu->records_count()==0)
		{
			return false;
		}
		
		while ($this->dbu->move_next()) 
		{
			if (!is_dir(base64_encode($this->dbu->f('version'))))
			{
				$ld['error'] = base64_encode($this->dbu->f('version')).'/database.sql version folder is missing !';
				return false;		
			}
		}
		
		return true;
	}

	/****************************************************************
	* function rollback(&$ld)                                       *
	****************************************************************/
	function rollback(&$ld){
		if(!$ld['upid'])
		{
			$ld['error'] = "Invalid Rollback!";
			return false;
		}
		$update = $this->dbu->query("SELECT * FROM `update` WHERE update_id ='".$ld['upid']."'");
		if(!$update->next()){
			$ld['error'] = "Invalid Rollback!";
			return false;
		}
		
		$this->dbu->query("UPDATE `update` SET active = 0");
		$this->dbu->query("UPDATE `update` SET active = 1 WHERE update_id ='".$ld['upid']."'");
		$ld['error'] = 'Your installation has been reverted to version '.$update->f('version').'<br>Please note that sum functionality might not be present anymore.';
		return true;
	}
	
	function error(&$ld){
		$this->dbu->query("DELETE FROM `update` WHERE active = 2");
		return true;
	}
	
}