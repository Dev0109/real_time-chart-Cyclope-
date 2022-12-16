<?php
/***********************************************************************
* @Author: MedeeaWeb Works											   *
***********************************************************************/
class cost
{
	var $dbu;
	
	// function cost()
	// {
	// 	$this->dbu = new mysql_db();
	// }
	
	/****************************************************************
	* function update(&$ld)                                			*
	****************************************************************/
	function update(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->update_validate($ld))
		{
			return false;
		}
		
		$limits = $this->dbu->row("SELECT lft,rgt FROM department WHERE department_id = ?", $ld['department_id']);
		$departments = $this->dbu->query("SELECT department_id FROM department WHERE lft BETWEEN ".$limits['lft']." AND ".$limits['rgt']);
		
		while ($departments->next()) {
			
			$this->dbu->query("DELETE FROM casualty WHERE department_id ='".$departments->f('department_id')."'");
			var_dump("test", $ld['currency']);
			$this->dbu->query("INSERT INTO casualty SET 
										department_id = '".$departments->f('department_id')."',
										date = '".time()."',
										cost_per_hour = '".$ld['cost_per_hour']."',
										currency = '".$ld['currency']."'");	
		}
		
		$this->dbu->query("UPDATE `settings` SET `value` = '" . $ld['extrareport_daily'] . "' WHERE `settings`.`constant_name` = 'EXTRAREPORT_DAILY' AND `settings`.`module` = 'extrareporttoggle' LIMIT 1");
		$this->dbu->query("UPDATE `settings` SET `value` = '" . $ld['extrareport_weekly'] . "' WHERE `settings`.`constant_name` = 'EXTRAREPORT_WEEKLY' AND `settings`.`module` = 'extrareporttoggle' LIMIT 1");
		$this->dbu->query("UPDATE `settings` SET `value` = '" . $ld['extrareport_monthly'] . "' WHERE `settings`.`constant_name` = 'EXTRAREPORT_MONTHLY' AND `settings`.`module` = 'extrareporttoggle' LIMIT 1");
		$this->dbu->query("UPDATE `settings` SET `value` = '" . $ld['anonymize_names'] . "' WHERE `settings`.`constant_name` = 'ANONYMIZE_NAMES' AND `settings`.`module` = 'anonymizetoggle' LIMIT 1");
		
		
		
		
		$ld['error']='Settings updated successfully.<br>';
		return true;
	}
	
	/****************************************************************
	* function update_validate(&$ld)                                *
	****************************************************************/
	function update_validate(&$ld)
	{
		$is_ok = true;
		
		if(!$ld['department_id'])
		{
			$ld['error']='Invalid department ID.<br>';
			$is_ok = false;
		}
		
		/*if(!$ld['currency'])
		{
			$ld['error'] .='Please select a currency from the drowpdown.<br>';
			$is_ok = false;
		}
		
		if(!$ld['cost_per_hour'])
		{
			$ld['error'] .='Please fill in the Cost field.<br>';
			$is_ok = false;
		}*/
		
		return $is_ok;
	}
	
}//end class