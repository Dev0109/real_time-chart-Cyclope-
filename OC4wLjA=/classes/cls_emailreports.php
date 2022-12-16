<?php
/***********************************************************************
* @Author: MedeeaWeb Works											   *
***********************************************************************/
class emailreports
{
	var $dbu;
	function emailreports()
	{
		$this->dbu = new mysql_db();
	}
	
	/****************************************************************
	* function add(&$ld)                                			*
	****************************************************************/
	function add(&$ld)
	{
		// $this->dbu = new mysql_db();
		if(!$this->add_validate($ld))
		{
			return false;
		}
		
		$ld['email_report_id'] = $this->dbu->query_get_id("INSERT INTO email_report SET 
										name = '".encode_numericentity($ld['name'])."',
										date = '".time()."',
										description = '".encode_numericentity($ld['description'])."'");
		$this->dbu->query("INSERT INTO email_report_details SET 
										subject = '".encode_numericentity($ld['subject'])."',
										attachment_type = '".$ld['attachmenttype']."',
										time_filter = '".$ld['timespan']."',
										body = '".encode_numericentity($ld['body'])."',
										email_report_id='".$ld['email_report_id']."'"
										);
		
		foreach ($ld['report'] as $key => $value)
		{
			$this->dbu->query("INSERT INTO email_report_type SET
				type='".$value."',
				email_report_id='".$ld['email_report_id']."'");
		}
		
		$selected_departments = array();
		
		foreach ($ld['selected'] as $key => $value)
		{
			$pieces = explode('-',$value);
			list($department_id,$computer_id,$member_id) = $pieces;
			$department_id = substr($department_id,1);
			
			if(!in_array($department_id,$selected_departments))
			{
				array_push($selected_departments,$department_id);
			}
		}
		
		foreach ($selected_departments as $key => $value)
		{
			$this->dbu->query("INSERT INTO email_report_group SET
				department_id='".$value."',
				email_report_id='".$ld['email_report_id']."'");
		}
		
		foreach ($ld['frequency'] as $key => $value)
		{
			$this->dbu->query("INSERT INTO email_report_frequency SET
				frequency='".$value."',
				email_report_id='".$ld['email_report_id']."'");
		}
		
		foreach ($ld['email_report_receiver'] as $key => $value)
		{
			$this->dbu->query("INSERT INTO email_report_receiver SET
				email='".$value."',
				email_report_id='".$ld['email_report_id']."'");
		}
		
		foreach ($ld['email_report_sender'] as $key => $value)
		{
			$this->dbu->query("INSERT INTO email_report_sender SET
				email='".$value."',
				email_report_id='".$ld['email_report_id']."'");
		}

		global $site_url;
		
		$ld['error']='Report set has been added successfully! <br><pre>' . print_r($site_url.CURRENT_VERSION_FOLDER,1) . '</pre>';
		if ($ld['test'] == 'test' AND is_numeric($ld['frequency'][0])) {
				$freq = array(false,'emailreportdaily','emailreportweekly','emailreportmonthly');
				$ld['error']='Report set has been added successfully!<br />Test Email report successfully sent. Please check your email.<br>';
				curl_request_async($site_url.CURRENT_VERSION_FOLDER.'cron.php',array('force' => $freq[$ld['frequency'][0]],'single' => $ld['email_report_id']),'GET');
		}
		
		$ld['pag'] = 'emailreports';
		return true;
	}
	
	/****************************************************************
	* function add_validate(&$ld)                                   *
	****************************************************************/
	function add_validate(&$ld)
	{
		$is_ok = true;
		
		if(!$ld['report'])
		{
			$ld['error'] .= 'Please select at least one report!<br>';
			$is_ok = false;
		}
		
		if(!$ld['selected'])
		{
			$ld['error'] .= 'Please select at least one department!<br>';
			$is_ok = false;
		}
		
		if(!$ld['frequency'])
		{
			$ld['error'] .= 'Please select at least one frequency!<br>';
			$is_ok = false;
		}
		
		if(!$ld['email_report_receiver'])
		{
			$ld['error'] .= 'Please insert at least one email address !<br>';
			$is_ok = false;
		}
		else 
		{
			foreach ($ld['email_report_receiver'] as $key => $value)
			{
				if(!secure_email($value))
				{
					$ld['error'] .= "Please enter a valid Email Address."."<br>";
					$is_ok = false;
				}
			}
		}
		
		if(!$ld['email_report_sender'])
		{
			$ld['error'] .= 'Please insert atleast one email address !<br>';
			$is_ok = false;
		}
		else 
		{
			foreach ($ld['email_report_sender'] as $key => $value)
			{
				if(!secure_email($value))
				{
					$ld['error'] .= "Please enter a valid Email Address."."<br>";
					$is_ok = false;
				}
			}
		}
		
		if(!$ld['name'])
		{
			$ld['error'] .= 'Please insert a report name!<br>';
			$is_ok = false;
		}		
		if(!$ld['description'])
		{
			$ld['error'] .= 'Please insert a report description!<br>';
			$is_ok = false;
		}	
		if(!$ld['attachmenttype'])
		{
			$ld['error'] .= 'Please choose a report attachment type!<br>';
			$is_ok = false;
		}
		return $is_ok;
	}
	
	/****************************************************************
	* function update(&$ld)                                			*
	****************************************************************/
	function update(&$ld)
	{
		// $this->dbu = new mysql_db();
		if(!$this->update_validate($ld))
		{
			return false;
		}
		
		$this->dbu->query("UPDATE email_report SET 
										name = '".encode_numericentity($ld['name'])."',
										date = '".time()."',
										description = '".encode_numericentity($ld['description'])."' WHERE email_report_id='".$ld['email_report_id']."'");
		$this->dbu->query("DELETE FROM email_report_details WHERE email_report_id='".$ld['email_report_id']."'");
		$this->dbu->query("INSERT INTO email_report_details SET 
										subject = '".encode_numericentity($ld['subject'])."',
										attachment_type = '".$ld['attachmenttype']."',
										time_filter = '".$ld['timespan']."',
										body = '".encode_numericentity($ld['body'])."',
										email_report_id='".$ld['email_report_id']."'"
										);
		
		$this->dbu->query("DELETE FROM email_report_type WHERE email_report_id='".$ld['email_report_id']."'");
		
		foreach ($ld['report'] as $key => $value)
		{
			$this->dbu->query("INSERT INTO email_report_type SET
				type='".$value."',
				email_report_id='".$ld['email_report_id']."'");
		}
		
		$this->dbu->query("DELETE FROM email_report_group WHERE email_report_id='".$ld['email_report_id']."'");
		
		$selected_departments = array();
		
		foreach ($ld['selected'] as $key => $value)
		{
			$pieces = explode('-',$value);
			list($department_id,$computer_id,$member_id) = $pieces;
			$department_id = substr($department_id,1);
			
			if(!in_array($department_id,$selected_departments))
			{
				array_push($selected_departments,$department_id);
			}
		}
		
		foreach ($selected_departments as $key => $value)
		{
			$this->dbu->query("INSERT INTO email_report_group SET
				department_id='".$value."',
				email_report_id='".$ld['email_report_id']."'");
		}
		
		$this->dbu->query("DELETE FROM email_report_frequency WHERE email_report_id='".$ld['email_report_id']."'");
		
		foreach ($ld['frequency'] as $key => $value)
		{
			$this->dbu->query("INSERT INTO email_report_frequency SET
				frequency='".$value."',
				email_report_id='".$ld['email_report_id']."'");
		}
		
		$this->dbu->query("DELETE FROM email_report_receiver WHERE email_report_id='".$ld['email_report_id']."'");
		
		foreach ($ld['email_report_receiver'] as $key => $value)
		{
			$this->dbu->query("INSERT INTO email_report_receiver SET
				email='".$value."',
				email_report_id='".$ld['email_report_id']."'");
		}
		
		$this->dbu->query("DELETE FROM email_report_sender WHERE email_report_id='".$ld['email_report_id']."'");
		
		foreach ($ld['email_report_sender'] as $key => $value)
		{
			$this->dbu->query("INSERT INTO email_report_sender SET
				email='".$value."',
				email_report_id='".$ld['email_report_id']."'");
		}
		
		global $site_url;
		
		$ld['error']='Report set has been updated successfully! <br><pre>' . print_r($site_url.CURRENT_VERSION_FOLDER,1) . '</pre>';
		if ($ld['test'] == 'test' AND is_numeric($ld['frequency'][0])) {
				$freq = array(false,'emailreportdaily','emailreportweekly','emailreportmonthly');
				$ld['error']='Report set has been updated successfully!<br />Test Email report successfully sent. Please check your email.<br>';
				curl_request_async($site_url.CURRENT_VERSION_FOLDER.'cron.php',array('force' => $freq[$ld['frequency'][0]],'single' => $ld['email_report_id']),'GET');
		}
		$ld['pag'] = 'emailreports';
		return true;
	}
	
	/****************************************************************
	* function update_validate(&$ld)                                *
	****************************************************************/
	function update_validate(&$ld)
	{
		return $this->add_validate($ld);
	}
	
	/****************************************************************
	* function delete(&$ld)                                			*
	****************************************************************/
	function delete(&$ld)
	{
		// $this->dbu = new mysql_db();
		if(!$this->delete_validate($ld))
		{
			return false;
		}
		
		$this->dbu->query("DELETE FROM email_report WHERE email_report_id='".$ld['email_report_id']."'");
		$this->dbu->query("DELETE FROM email_report_details WHERE email_report_id='".$ld['email_report_id']."'");
		$this->dbu->query("DELETE FROM email_report_type WHERE email_report_id='".$ld['email_report_id']."'");
		$this->dbu->query("DELETE FROM email_report_frequency WHERE email_report_id='".$ld['email_report_id']."'");
		$this->dbu->query("DELETE FROM email_report_receiver WHERE email_report_id='".$ld['email_report_id']."'");
		$this->dbu->query("DELETE FROM email_report_sender WHERE email_report_id='".$ld['email_report_id']."'");
		$this->dbu->query("DELETE FROM email_report_group WHERE email_report_id='".$ld['email_report_id']."'");
			
		$ld['error']='Report set has been deleted successfully! <br>';
		
		return true;
	}
	
	/****************************************************************
	* function delete_validate(&$ld)                                *
	****************************************************************/
	function delete_validate(&$ld)
	{
		if(!$ld['email_report_id'])
		{
			$ld['error'] .= 'Invalid Id!<br>';
			return false;
		}
		else 
		{
			$this->dbu->query("SELECT * FROM email_report WHERE email_report_id='".$ld['email_report_id']."'");
			if(!$this->dbu->move_next())
			{
				$ld['error'] .= 'Invalid Id!<br>';
				return false;
			}
		}
		
		return true;
	}
	
}//end class

