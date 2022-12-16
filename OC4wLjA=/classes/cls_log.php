<?php
/***********************************************************************
* @Author: MedeeaWeb Works											   *
***********************************************************************/
class log
{
	var $dbu;
	
	function log()
	{
		$this->dbu = new mysql_db();
	}
	/****************************************************************
	* function clear(&$ld)                                			*
	****************************************************************/
	
	function clear(&$ld){
		
		$time = strtotime('-1 year', time() );
		
		$query = $this->dbu->query("SELECT session_id FROM session WHERE date < ".$time);
		
		$sessions = array();
		
		while ($query->next()){
			array_push($sessions,$query->f('session_id'));
		}
		if(empty($sessions)){
			$ld['error'] .= 'The log has been successfully deleted !';
			return true;
		}
		
		$this->dbu->query("DELETE FROM session_activity WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_application WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_chat WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_document WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_website WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_website_agg WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_file WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_log WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session WHERE session_id IN (".join(',',$sessions).")");
		
		$this->$dbu->query("DELETE FROM chat WHERE last_access < ".$time);
		$this->$dbu->query("DELETE FROM document WHERE last_access < ".$time);
		$this->$dbu->query("DELETE FROM domain WHERE last_access < ".$time);
		$this->$dbu->query("DELETE FROM file WHERE last_access < ".$time);
		$this->$dbu->query("DELETE FROM fileprint WHERE last_access < ".$time);
		$this->$dbu->query("DELETE FROM website WHERE last_access < ".$time);
		$this->$dbu->query("DELETE FROM window WHERE last_access < ".$time);
		
		$ld['error'] .= 'The log has been successfully deleted !';
		return true;
	}
	
	/****************************************************************
	* function clearquarter(&$ld)                                	*
	****************************************************************/
	
	function clearquarter(&$ld){
		
		$time = strtotime('-3 months', time() );
		
		$query = $this->dbu->query("SELECT session_id FROM session WHERE date < ".$time);
		
		$sessions = array();
		
		while ($query->next()){
			array_push($sessions,$query->f('session_id'));
		}
		if(empty($sessions)){
			$ld['error'] .= 'The log has been successfully deleted !';
			return true;
		}
		
		$this->dbu->query("DELETE FROM session_activity WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_application WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_chat WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_document WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_website WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_website_agg WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_file WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_log WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session WHERE session_id IN (".join(',',$sessions).")");
		
		$this->$dbu->query("DELETE FROM chat WHERE last_access < ".$time);
		$this->$dbu->query("DELETE FROM document WHERE last_access < ".$time);
		$this->$dbu->query("DELETE FROM domain WHERE last_access < ".$time);
		$this->$dbu->query("DELETE FROM file WHERE last_access < ".$time);
		$this->$dbu->query("DELETE FROM fileprint WHERE last_access < ".$time);
		$this->$dbu->query("DELETE FROM website WHERE last_access < ".$time);
		$this->$dbu->query("DELETE FROM window WHERE last_access < ".$time);
		
		$ld['error'] .= 'The log has been successfully deleted !';
		return true;
	}
	
	/****************************************************************
	* function clearhalf(&$ld)                                		*
	****************************************************************/
	
	function clearhalf(&$ld){
		
		$time = strtotime('-6 months', time() );
		
		$query = $this->dbu->query("SELECT session_id FROM session WHERE date < ".$time);
		
		$sessions = array();
		
		while ($query->next()){
			array_push($sessions,$query->f('session_id'));
		}
		if(empty($sessions)){
			$ld['error'] .= 'The log has been successfully deleted !';
			return true;
		}
		
		$this->dbu->query("DELETE FROM session_activity WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_application WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_chat WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_document WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_website WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_website_agg WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_file WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_log WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session WHERE session_id IN (".join(',',$sessions).")");
		
		$this->$dbu->query("DELETE FROM chat WHERE last_access < ".$time);
		$this->$dbu->query("DELETE FROM document WHERE last_access < ".$time);
		$this->$dbu->query("DELETE FROM domain WHERE last_access < ".$time);
		$this->$dbu->query("DELETE FROM file WHERE last_access < ".$time);
		$this->$dbu->query("DELETE FROM fileprint WHERE last_access < ".$time);
		$this->$dbu->query("DELETE FROM website WHERE last_access < ".$time);
		$this->$dbu->query("DELETE FROM window WHERE last_access < ".$time);
		
		$ld['error'] .= 'The log has been successfully deleted !';
		return true;
	}
	
	/****************************************************************
	* function clearall(&$ld)                                		*
	****************************************************************/
	
	function clearall(&$ld){

		$this->dbu->query("TRUNCATE chat");
		$this->dbu->query("TRUNCATE document");
		$this->dbu->query("TRUNCATE domain");
		$this->dbu->query("TRUNCATE file");
		$this->dbu->query("TRUNCATE fileprint");
		$this->dbu->query("TRUNCATE session");
		$this->dbu->query("TRUNCATE session_activity");
		$this->dbu->query("TRUNCATE session_application");
		$this->dbu->query("TRUNCATE session_chat");
		$this->dbu->query("TRUNCATE session_document");
		$this->dbu->query("TRUNCATE session_file");
		$this->dbu->query("TRUNCATE session_log");
		$this->dbu->query("TRUNCATE session_website");
		$this->dbu->query("TRUNCATE session_website_agg");
		$this->dbu->query("TRUNCATE website");
		$this->dbu->query("TRUNCATE window");
		
		$ld['error'] .= 'The log has been successfully deleted !';
		return true;
	}
	
	/*function clear(&$ld){
		if(!isset($ld['member']) || !is_numeric($ld['member'])){
			$ld['error'] = 'Missing Member!';
			return false;
		}
		if(!isset($ld['computer']) || !is_numeric($ld['computer'])){
			$ld['error'] = 'Missing computer!';
			return false;
		}
		//yum yum
		//step 1 get all the sessions
		$query = $this->dbu->query("SELECT session_id FROM session WHERE member_id = ? AND computer_id = ?",array($ld['member'],$ld['computer']));
		$sessions = array();
		while ($query->next()){
			array_push($sessions,$query->f('session_id'));
		}
		if(empty($sessions)){
			return true;
		}
		//start removing shit
		$this->dbu->query("DELETE FROM session_activity WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_application WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_chat WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_detail WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_document WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session_website WHERE session_id IN (".join(',',$sessions).")");
		$this->dbu->query("DELETE FROM session WHERE session_id IN (".join(',',$sessions).")");
		
		$ld['error'] .= 'The log has been successfully deleted !';
		return true;
	}*/
	
	/****************************************************************
	* function update(&$ld)                                			*
	****************************************************************/
	function update(&$ld)
	{
		if(!$this->update_validate($ld))
		{
			return false;
		}
		
		$this->dbu->query("UPDATE computer SET 			
									computer.connectivity = '".$ld['connectivity']."',			
									computer.idlefactor = '".$ld['idlefactor']."',			
									computer.precision = '".$ld['precision']."',
									computer.altered = 1
									WHERE computer_id='".$ld['computer_id']."'");
		
		$ld['error']='Settings updated successfully.<br>';
		return true;
	}
	
	/****************************************************************
	* function update_validate(&$ld)                                *
	****************************************************************/
	function update_validate(&$ld)
	{
		$is_ok = true;
		
		if(!$ld['connectivity'])
		{
			$ld['error']='Please fill in the Connectivity field.<br>';
			$is_ok = false;
		}
		
		if(!$ld['precision'])
		{
			$ld['error']='Please fill in the Precision field.<br>';
			$is_ok = false;
		}
		
		if(!$ld['idlefactor'])
		{
			$ld['error']='Please fill in the Idle Factor field.<br>';
			$is_ok = false;
		}
		
		if(!$ld['computer_id'])
		{
			$ld['error']='Invalid ID.<br>';
			$is_ok = false;
		}
		else 
		{
			$this->dbu->query("SELECT computer_id FROM computer WHERE computer_id='".$ld['computer_id']."'");
			if(!$this->dbu->move_next())
			{
				$ld['error']='Invalid ID.<br>';
				$is_ok = false;
			}
		}
		
		
		return $is_ok;
	}
	
}//end class