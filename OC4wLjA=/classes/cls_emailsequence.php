<?php
/***********************************************************************
* @Author: MedeeaWeb Works											   *
***********************************************************************/
class emailsequence
{
	var $dbu;
	
	function emailsequence()
	{
		$this->dbu = new mysql_db();
	}
	
	/****************************************************************
	* function add(&$ld)                                			*
	****************************************************************/
	function add(&$ld)
	{
		if(!$this->add_validate($ld))
		{
			return false;
		}
		
		$ld['sequencegrp_id'] = $this->dbu->query_get_id("INSERT INTO `sequence_reports` (
				`sequencegrp_id` ,
				`name` ,
				`description`,
				`noise`
				)
				VALUES (
				NULL , '" . $ld['name'] . "', '" . $ld['description'] . "', '" . $ld['noise'] . "'
				)");
		foreach ($ld['selected'] as $key => $val) {
					$this->dbu->query("INSERT INTO `sequence_dep` (
										`sequencegrp_id` ,
										`department_id`
										)
										VALUES (
										'" . $ld['sequencegrp_id'] . "', '" . filter_var($val, FILTER_SANITIZE_NUMBER_INT) . "'
										)"
										);
		}
		foreach ($ld['appid'] as $key => $val) {
					$this->dbu->query("INSERT INTO `sequence_list` (
										`sequencegrp_id` ,
										`app_id` ,
										`form_id` ,
										`weight`
										)
										VALUES (
										'" . $ld['sequencegrp_id'] . "', '" . $val . "', '" . $ld['formid'][$key] . "', '" . $key . "'
										)"
										);
		}
		
		$ld['error']='Report set has been added successfully!';
		
		$ld['pag'] = 'emailsequences';
		return true;
	}
	
	/****************************************************************
	* function add_validate(&$ld)                                   *
	****************************************************************/
	function add_validate(&$ld)
	{
		$is_ok = true;
		
		if(!$ld['selected'])
		{
			$ld['error'] .= 'Please select at least one department!<br>';
			$is_ok = false;
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
		return $is_ok;
	}
	
	/****************************************************************
	* function update(&$ld)                                			*
	****************************************************************/
	function update(&$ld)
	{
		if(!$this->update_validate($ld))
		{
			return false;
		}
		
		$this->dbu->query("DELETE FROM sequence_list WHERE sequencegrp_id='".$ld['sequencegrp_id']."'");
		$this->dbu->query("DELETE FROM sequence_dep WHERE sequencegrp_id='".$ld['sequencegrp_id']."'");
		$this->dbu->query("UPDATE `sequence_reports` SET `name` = '" . $ld['name'] . "',`description` = '" . $ld['description'] . "',`noise` = '" . $ld['noise'] . "' WHERE `sequence_reports`.`sequencegrp_id` =".$ld['sequencegrp_id']."");
		foreach ($ld['selected'] as $key => $val) {
					$this->dbu->query("INSERT INTO `sequence_dep` (
										`sequencegrp_id` ,
										`department_id`
										)
										VALUES (
										'" . $ld['sequencegrp_id'] . "', '" . filter_var($val, FILTER_SANITIZE_NUMBER_INT) . "'
										)"
										);
		}
		foreach ($ld['appid'] as $key => $val) {
					$this->dbu->query("INSERT INTO `sequence_list` (
										`sequencegrp_id` ,
										`app_id` ,
										`form_id` ,
										`weight`
										)
										VALUES (
										'" . $ld['sequencegrp_id'] . "', '" . $val . "', '" . $ld['formid'][$key] . "', '" . $key . "'
										)"
										);
		}
		
		$ld['error']='Report set has been updated successfully!';
		$ld['pag'] = 'emailsequences';
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
		if(!$this->delete_validate($ld))
		{
			return false;
		}
		
		$this->dbu->query("DELETE FROM sequence_list WHERE sequencegrp_id='".$ld['sequencegrp_id']."'");
		$this->dbu->query("DELETE FROM sequence_dep WHERE sequencegrp_id='".$ld['sequencegrp_id']."'");
		$this->dbu->query("DELETE FROM sequence_reports WHERE sequencegrp_id='".$ld['sequencegrp_id']."'");
			
		$ld['error']='Report set has been deleted successfully! <br>';
		
		return true;
	}
	
	/****************************************************************
	* function delete_validate(&$ld)                                *
	****************************************************************/
	function delete_validate(&$ld)
	{
		if(!$ld['sequencegrp_id'])
		{
			$ld['error'] .= 'Invalid Id!<br>';
			return false;
		}
		else 
		{
			$this->dbu->query("SELECT * FROM sequence_reports WHERE sequencegrp_id='".$ld['sequencegrp_id']."'");
			if(!$this->dbu->move_next())
			{
				$ld['error'] .= 'Invalid Id!<br>';
				return false;
			}
		}
		
		return true;
	}
	
}//end class

