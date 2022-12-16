<?php
/***********************************************************************
* @Author: MedeeaWeb Works											   *
***********************************************************************/
class alert
{
	var $dbu;
	
	function alert()
	{
		$this->dbu = new mysql_db();
	}

	/****************************************************************
	* function add(&$ld)                                            *
	****************************************************************/
	function add(&$ld)
	{
		if(!$this->add_validate($ld))
		{
			return false;
		}
		//create the main alert
		$ld['alert_id'] = $this->dbu->query_get_id("INSERT INTO alert SET alert_type = '".$ld['alert_type']."',
																	name = '".encode_numericentity($ld['name'])."',
																	description = '".encode_numericentity($ld['description'])."'");
		foreach ($ld['selected'] as $department_id){
			$ld['department_id'] = substr($department_id,1);
			$this->dbu->query("INSERT INTO alert_department SET alert_id = '".$ld['alert_id']."', department_id = '".$ld['department_id']."'");
			switch ($ld['alert_type']){
				case 1://work alert
					$this->saveWorkAlert($ld);
					break;
				case 2://idle alert
					$ld['max_time'] = $ld['idle_hour'] * 60  + $ld['idle_minute'];
					$this->saveDurationAlert($ld);
					break;
				case 3://online
					$ld['max_time'] = $ld['online_hour'] * 60  + $ld['online_minute'];
					$this->saveDurationAlert($ld);
					break;
				case 4://app alert
					if(!$this->saveAppAlert($ld)){
						$ld['error'].="Please fill in at leas one valid application !";
						$this->dbu->query("DELETE FROM alert WHERE alert_id='".$ld['alert_id']."'");
						$this->dbu->query("DELETE FROM alert_department WHERE alert_id='".$ld['alert_id']."'");
						unset($ld['alert_id']);
						return false;
					}
					break;
				case 5://monitor
					$ld['max_time'] = $ld['monitor_days'] * 60 * 24+ $ld['monitor_hour'] * 60  + $ld['monitor_minute'];
					$this->saveDurationAlert($ld);
					break;
				case 6://web alert
					if(!$this->saveWebAlert($ld)){
						$ld['error'].="Please fill in at least one valid website/domain !";
						$this->dbu->query("DELETE FROM alert WHERE alert_id='".$ld['alert_id']."'");
						$this->dbu->query("DELETE FROM alert_department WHERE alert_id='".$ld['alert_id']."'");
						unset($ld['alert_id']);
						return false;
					}
					break;
				case 7://seq alert
					if(!$this->saveSeqAlert($ld, true)){
						$ld['error'].="Please fill in at leas one valid sequence!" . print_r($ld,1);
						$this->dbu->query("DELETE FROM alert WHERE alert_id='".$ld['alert_id']."'");
						$this->dbu->query("DELETE FROM alert_department WHERE alert_id='".$ld['alert_id']."'");
						$this->dbu->query("DELETE FROM alert_other WHERE alert_id='".$ld['alert_id']."'");
						unset($ld['alert_id']);
						return false;
					}
					break;
			}
		}
		$ld['error'].="Alert successfully added!";
		$ld['pag'] = 'alerts';
		return true;
	}

	/****************************************************************
	* function update(&$ld)                                         *
	****************************************************************/
	function update(&$ld)
	{
		if(!$this->update_validate($ld))
		{
			return false;
		}
		$this->dbu->query("UPDATE alert SET alert_type = '".$ld['alert_type']."',
											name = '".$ld['name']."',
											description = '".$ld['description']."' 
										WHERE alert_id = '".$ld['alert_id']."'");
		
		$this->dbu->query("DELETE FROM alert_department WHERE alert_id='".$ld['alert_id']."'");
		$this->dbu->query("DELETE FROM alert_trigger WHERE alert_id='".$ld['alert_id']."'");
		foreach ($ld['selected'] as $department_id){
			$ld['department_id'] = substr($department_id,1);
			$this->dbu->query("INSERT INTO alert_department SET alert_id = '".$ld['alert_id']."', department_id = '".$ld['department_id']."'");
			switch ($ld['alert_type']){
				case 1://work alert
					$this->saveWorkAlert($ld, true);
					break;
				case 2://idle alert
					$ld['max_time'] = $ld['idle_hour'] * 60  + $ld['idle_minute'];
					$this->saveDurationAlert($ld, true);
					break;
				case 3://online
					$ld['max_time'] = $ld['online_hour'] * 60  + $ld['online_minute'];
					$this->saveDurationAlert($ld, true);
					break;
				case 4:
					if(!$this->saveAppAlert($ld, true)){
						$ld['error'].="Please fill in at least one valid application !";
						return false;
					}
					break;
				case 5://monitor alert
					$ld['max_time'] = $ld['monitor_days']*24*60 + $ld['monitor_hour'] * 60  + $ld['monitor_minute'];
					$this->saveDurationAlert($ld, true);
					break;
				case 6:
					if(!$this->saveWebAlert($ld, true)){
						$ld['error'].="Please fill in at least one valid website/domain !";
						return false;
					}
					break;
				case 7:
					if(!$this->saveSeqAlert($ld, true)){
						$ld['error'].="Please fill in at least one valid sequence!";
						return false;
					}
					break;
			}
		}
		$ld['error'].='Alert has been successfully updated.';
		$ld['pag'] = 'alerts';
		return true;
	}
	
	/****************************************************************
	* function add_validate(&$ld)                                   *
	****************************************************************/
	function add_validate(&$ld)
	{
		
		$is_ok=true;
		if(!$ld['selected'] || !is_array($ld['selected']) || empty($ld['selected'])){
			$ld['error'] = 'Please select a department! ';
			return false;
		}	
		if(!$ld['alert_type']){
			$ld['error'] = 'Please select an alert type';
			return false;
		}else{
		
			switch ($ld['alert_type']){
				case 1://workschedule alert
					if(!isset($ld['w']) || !is_array($ld['w'])){
						$ld['error'] = 'Please select the desired work schedule. ';
						return false;
					}
				break;
				case 2://idle alert
					if(!$ld['idle_minute'] && !$ld['idle_hour']){
						$ld['error'] = 'Please select a maximum idle time.';
						return false;
					}		
				break;
				case 3:
					if(!$ld['online_minute'] && !$ld['online_hour']){
						$ld['error'] = 'Please select a maximum online time.';
						return false;
					}		
				break;
				case 4:
					if(!isset($ld['app']) || !is_array($ld['app']) || empty($ld['app'])){
						$ld['error'] = 'Please select one or more applications! ';
						return false;
					}
					if(!isset($ld['app_minute']) || !is_array($ld['app_minute']) || empty($ld['app_minute'])){
						$ld['error'] = 'Please select a maximum time for all applications. ';
						return false;
					}
				break;	
				case 5:
					if(!$ld['monitor_days']&&!$ld['monitor_minute'] && !$ld['monitor_hour']){
						$ld['error'] = 'Please select a time limit.';
						return false;
					}
				break;	
				case 6:
					if(!isset($ld['web']) || !is_array($ld['web']) || empty($ld['web'])){
						$ld['error'] = 'Please select one or more websites/domains.';
						return false;
					}
					if(!isset($ld['web_minute']) || !is_array($ld['web_minute']) || empty($ld['web_minute'])){
						$ld['error'] = 'Please select a maximum time for all websites/domains.';
						return false;
					}
				break;	
				case 7:
					if(!isset($ld['app']) || !is_array($ld['app']) || empty($ld['app'])){
						$ld['error'] = 'Please select one or more sequence! ';
						return false;
					}
				break;		
			}
		}
		if(!$ld['name']){
			$ld['error'] = 'Please provide a name for this alert.';
			$is_ok = false;
		}
		return $is_ok;
	}
		
	/****************************************************************
	* function update_validate(&$ld)                                *
	****************************************************************/
	function update_validate(&$ld)
	{
		if(!is_numeric($ld['alert_id'])){
			$ld['error'] = 'Invalid ID';
			return false;
		}
		
		$this->dbu->query("SELECT alert_id FROM alert WHERE alert_id = ?",$ld['alert_id']);
		
		if(!$this->dbu->move_next())
		{
			$ld['error']='Invalid ID<br>';
			return false;
		}
		return $this->add_validate($ld);		
	}
	
	/****************************************************************
	* function delete(&$ld)                                			*
	****************************************************************/
	function delete(&$ld)
	{
	 	if(!$this->delete_validate($ld))
		{
			return false;
		}
		
        $this->dbu->query("DELETE FROM alert WHERE alert_id = ?",$ld['alert_id']);
        $this->dbu->query("DELETE FROM alert_trigger WHERE alert_id = ?",$ld['alert_id']);
        $this->dbu->query("DELETE FROM alert_other WHERE alert_id = ?",$ld['alert_id']);
        $this->dbu->query("DELETE FROM alert_time WHERE alert_id = ?",$ld['alert_id']);
   		
        $ld['error']='Alert has been successfully deleted.<br>';
        return true; 
	}

	/****************************************************************
	* function delete_validate(&$ld)                                *
	****************************************************************/
	function delete_validate(&$ld)
	{
		if(!is_numeric($ld['alert_id'])){
			$ld['error'] = 'Invalid ID';
			return false;
		}
		
		$this->dbu->query("SELECT alert_id FROM alert WHERE alert_id = ?",$ld['alert_id']);
		
		if(!$this->dbu->move_next())
		{
			$ld['error']='Invalid ID<br>';
			return false;
		}
        return true;
	}
	
	function saveWorkAlert($ld,$clear = false){
		if($clear){
			$this->dbu->query("DELETE FROM alert_time WHERE alert_id = ? AND department_id = ?",array($ld['alert_id'],$ld['department_id']));
		}
		
		for($i = 0; $i <= 7; $i++)
		{
			if($ld['w'][$i])
			{
				if($ld['w_start'][$i]['ampm'] == '2' && $ld['w_start'][$i]['hour'] < 12 && $ld['w_start'][$i]['hour'] > 0)
				{

					$ld['w_start'][$i]['hour'] += 12;
				}

				if($ld['w_end'][$i]['ampm'] == '2' && $ld['w_end'][$i]['hour'] < 12 && $ld['w_end'][$i]['hour'] > 0)
				{
					$ld['w_end'][$i]['hour'] += 12;
				}

				$this->dbu->query("INSERT INTO alert_time SET
						alert_id = ".$ld['alert_id'].",
						start_time='".mktime($ld['w_start'][$i]['hour'],$ld['w_start'][$i]['minute'],0,date('m'),date('d'),date('Y'))."',
						end_time='".mktime($ld['w_end'][$i]['hour'],$ld['w_end'][$i]['minute'],0,date('m'),date('d'),date('Y'))."',
						department_id='".$ld['department_id']."',
						day='".$i."'");
			}
		}
		return true;
	}
	
	function saveDurationAlert($ld, $clear = false){
		if($clear){
			$this->dbu->query("DELETE FROM alert_other WHERE alert_id = ? AND department_id = ?",array($ld['alert_id'],$ld['department_id']));
		}
		$this->dbu->query("INSERT INTO alert_other SET alert_id = '".$ld['alert_id']."',
														department_id = '".$ld['department_id']."',
														alert_type = '".$ld['alert_type']."',
														cond = '".$ld['max_time']."',
														cond_type = '2'");
		return true;
	}
	
	function saveAppAlert($ld, $clear = false){
		if($clear){
			$this->dbu->query("DELETE FROM alert_other WHERE alert_id = ? AND department_id = ?",array($ld['alert_id'],$ld['department_id']));
		}
		
		$i = 0;
		foreach ($ld['app'] as $pos => $app_id)
		{
			if(is_numeric($app_id) && isset($app_id))
			{
				$application_name = $this->dbu->field("SELECT description FROM application WHERE application_id = '".$app_id."'");
				if($ld['app_txt'][$pos] == $application_name )
				{
					$cond = $ld['app_hour'][$pos] * 60 + $ld['app_minute'][$pos];
					$this->dbu->query("INSERT INTO alert_other SET alert_id = '".$ld['alert_id']."',
																department_id = '".$ld['department_id']."',
																alert_type = '".$ld['alert_type']."',
																cond = '".$cond."',
																cond_type = 0,
																cond_link = '".$app_id."'
																");
					$i++;
				}
			}
		}
		
		if(!$i) return false;
		
		return true;
	}
	
	function saveSeqAlert($ld, $clear = false){
		if($clear){
			$this->dbu->query("DELETE FROM alert_other WHERE alert_id = ?",$ld['alert_id']);
		}
		
		$i = 0;
		foreach ($ld['app'] as $pos => $app_id)
		{
					$this->dbu->query("INSERT INTO alert_other SET alert_id = '".$ld['alert_id']."',
																department_id = '".$ld['department_id']."',
																alert_type = '".$ld['alert_type']."',
																cond = '".$ld['app_hour'][$pos]."',
																cond_type = 0,
																cond_link = '".$app_id."'
																");
					$i++;
		}
		
		if(!$i) return false;
		
		return true;
	}
	
	function saveWebAlert($ld, $clear = false){
		if($clear){
			$this->dbu->query("DELETE FROM alert_other WHERE alert_id = ? AND department_id = ?",array($ld['alert_id'],$ld['department_id']));
		}
		
		$i = 0;
		foreach ($ld['web'] as $pos => $web_id)
		{
			if(is_numeric($web_id) && isset($web_id))
			{
				$website_name = $this->dbu->field("SELECT domain FROM domain WHERE domain_id = '".$web_id."'");
				if($ld['web_txt'][$pos] == $website_name )
				{
					$cond = $ld['web_hour'][$pos] * 60 + $ld['web_minute'][$pos];
					$this->dbu->query("INSERT INTO alert_other SET alert_id = '".$ld['alert_id']."',
																department_id = '".$ld['department_id']."',
																alert_type = '".$ld['alert_type']."',
																cond = '".$cond."',
																cond_type = 0,
																cond_link = '".$web_id."'
																");
					$i++;
				}
			}
		}
		
		if(!$i) return false;
		
		return true;
	}
	
	
	
}//end class