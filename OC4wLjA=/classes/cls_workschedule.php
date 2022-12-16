<?php
/************************************************************************
* @Author: MedeeaWeb Works
***********************************************************************/
class workschedule
{
	var $dbu;
	
	// function workschedule()
	// {
	// 	$this->dbu = new mysql_db();
	// }
	
	/****************************************************************
	* function add(&$ld)                                            *
	****************************************************************/
	function update(&$ld)
	{
		$this->dbu = new mysql_db();
		if(!$this->update_validate($ld))
		{
			return false;
		}
		
		if($ld['department_id'])
		{
			$limits = $this->dbu->row("SELECT lft,rgt FROM department WHERE department_id = ?", $ld['department_id']);
			
			$departments = $this->dbu->query("SELECT department_id FROM department WHERE lft BETWEEN ".$limits['lft']." AND ".$limits['rgt']);
			
			while ($departments->next()) {
				
				$ld['department_id'] = $departments->f('department_id');
				
				$this->dbu->query("DELETE FROM workschedule WHERE department_id='".$ld['department_id']."'");
			
				for($i=0; $i <= 7; $i++)
				{
					if($ld['w'][$i])
					{	
						// if($ld['w_start'][$i]['ampm'] == '2' && $ld['w_start'][$i]['hour'] < 12 && $ld['w_start'][$i]['hour'] > 0)
						// {
							
							// $ld['w_start'][$i]['hour'] += 12;
						// }
						
						// if($ld['w_end'][$i]['ampm'] == '2' && $ld['w_end'][$i]['hour'] < 12 && $ld['w_end'][$i]['hour'] > 0)
						// {
							// $ld['w_end'][$i]['hour'] += 12;
						// }
						
						$this->dbu->query("INSERT INTO workschedule SET
						start_time='".mktime($ld['w_start'][$i]['hour'],$ld['w_start'][$i]['minute'],0,date('m'),date('d'),date('Y'))."',
						end_time='".mktime($ld['w_end'][$i]['hour'],$ld['w_end'][$i]['minute'],0,date('m'),date('d'),date('Y'))."',
						department_id='".$ld['department_id']."',
						day='".$i."',
						activity_type = 1");
					}
					
					if($ld['p'][$i])
					{	
						// if($ld['p_start'][$i]['ampm'] == '2' && $ld['p_start'][$i]['hour'] < 12 && $ld['p_start'][$i]['hour'] > 0)
						// {
							// $ld['p_start'][$i]['hour'] += 12;
						// }
						
						// if($ld['p_end'][$i]['ampm'] == '2' && $ld['p_end'][$i]['hour'] < 12 && $ld['p_end'][$i]['hour'] > 0)
						// {
							// $ld['p_end'][$i]['hour'] += 12;
						// }
						
						$this->dbu->query("INSERT INTO workschedule SET
						start_time='".mktime($ld['p_start'][$i]['hour'],$ld['p_start'][$i]['minute'],0,date('m'),date('d'),date('Y'))."',
						end_time='".mktime($ld['p_end'][$i]['hour'],$ld['p_end'][$i]['minute'],0,date('m'),date('d'),date('Y'))."',
						department_id='".$ld['department_id']."',
						day='".$i."',
						activity_type = 2");
					}
				}
			}
		}
		
		$ld['error']='Workschedule has been successfully updated.<br>';

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
			$ld['error']='Invalid ID<br>';
			return false;	
		}
		
		return $is_ok;
	}
}//end class