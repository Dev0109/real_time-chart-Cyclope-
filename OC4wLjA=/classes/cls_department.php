<?php
/***********************************************************************
* @Author: MedeeaWeb Works											  *
***********************************************************************/
class department
{
	var $dbu;
	
	function department()
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
		$needsUpdate = false;
		if(!$ld['parent']){
			$nodeInfo = $this->dbu->query('SELECT MAX(rgt) AS rgt FROM department');
			if($nodeInfo->next()){
				$ld['lft'] = $nodeInfo->f('rgt') + 1;
				$ld['rgt'] = $nodeInfo->f('rgt') + 2;
			}else{
				$ld['lft'] = 1;
				$ld['rgt'] = 2;
			}
			$ld['parent'] = 0;
		}else{
			$nodeInfo = $this->dbu->query('SELECT MAX(rgt) AS rgt FROM department WHERE parent_id = ? ORDER BY rgt DESC',$ld['parent']);
			if(!$nodeInfo->next()){
				$ld['error'] = 'Could not find parent info';
				return false;
			}
			if(is_null($nodeInfo->f('rgt'))){
				$nodeInfo = $this->dbu->query('SELECT rgt FROM department WHERE department_id = ?',$ld['parent']);
				if(!$nodeInfo->next()){
					$in->lft = 1;
					$in->rgt = 2;
					
				}else{
					$ld['lft'] = $nodeInfo->f('rgt');
					$ld['rgt'] = $nodeInfo->f('rgt') + 1;
				}
			}else{
				$ld['lft'] = $nodeInfo->f('rgt');
				$ld['rgt'] = $nodeInfo->f('rgt') + 1;
			}
			$needsUpdate = true;
		}
		
		$ld['id'] = $this->dbu->query_get_id("INSERT INTO department SET name = '".$ld['name']."',
																		parent_id = '".$ld['parent']."',			
																		lft = '".$ld['lft']."',			
																		rgt = '".$ld['rgt']."'");
		member2manage_Modify($ld['id'],$ld['parent']);
		if($needsUpdate){
			//		       ___ 
			$this->rebuild(0,0);
			//	  	      {`"'}
			//		      -"-"-
		}

		if($ld['parent']){
			$ld['id'] = $ld['id'].'-'.$ld['parent'];
			
			//workschedule of the parent 

			$workschedule =$this->dbu->query("SELECT * FROM workschedule WHERE department_id='".$ld['parent']."'");
			
			while ($workschedule->next())
			{
				$this->dbu->query("INSERT INTO workschedule SET
						start_time='".$workschedule->f('start_time')."',
						end_time='".$workschedule->f('end_time')."',
						department_id='".$ld['id']."',
						day='".$workschedule->f('day')."',
						activity_type = '".$workschedule->f('activity_type')."'");
			}
			
			
			$application_categories =$this->dbu->query("SELECT * FROM application2category WHERE department_id='".$ld['parent']."'");	
			while ($application_categories->next())
			{
				$this->dbu->query("INSERT INTO application2category SET department_id = '".$ld['id']."',
																			application_category_id = '".$application_categories->f('application_category_id')."',
																			link_id = '".$application_categories->f('link_id')."',			
																			link_type = '".$application_categories->f('link_type')."'");
				
			} 	
		
		}
		else
		{
			// default workschedule for department 9AM-5PM Monday to Friday
			
			for($i = 1; $i<=5; $i++);
			{
				$this->dbu->query("INSERT INTO workschedule SET
					start_time='".mktime(9,0,0,date('m'),date('d'),date('Y'))."',
					end_time='".mktime(17,0,0,date('m'),date('d'),date('Y'))."',
					department_id='".$ld['id']."',
					day='".$i."',
					activity_type = 1");
			}
		}
		
		
		
		$ld['error'].="New Department successfully added!";
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
		$this->dbu->query("UPDATE department SET name = '".$ld['name']."'
											WHERE department_id = ?",$ld['id']);

		$parent = $this->dbu->row("SELECT parent_id,lft FROM department WHERE department_id = '".$ld['id']."'");
		
		
		if($ld['parent'] != $parent['parent_id']){
			$this->dbu->query("UPDATE department SET parent_id = '".$ld['parent']."' WHERE department_id = '".$ld['id']."'");
			//parent changed need to rebuild whole tree :(
			$this->rebuild(0,0);
		}
		
		member2manage_Modify($ld['id'],$ld['parent']);
		
		$ld['error'].='Department has been successfully updated.';
		return true;
	}
	
	/****************************************************************
	* function add_validate(&$ld)                                   *
	****************************************************************/
	function add_validate(&$ld)
	{
		$is_ok=true;
		if(!isset($ld['name']) || empty($ld['name'])){
			$ld['error'] = 'Please provide a new name';
			return false;
		}else{
			$this->dbu->query("SELECT department_id FROM department WHERE name = '".$ld['name']."' AND parent_id = '" . $ld['parent'] . "'");
			
			if($this->dbu->move_next())
			{
				$ld['error']='The selected department name is already in use.';
				return false;
			}
		}
		if(!isset($ld['parent']) || !is_numeric($ld['parent'])){
			$ld['error'] = 'Invalid Root Node';
			return false;
		}
		return $is_ok;
	}
		
	/****************************************************************
	* function update_validate(&$ld)                                *
	****************************************************************/
	function update_validate(&$ld)
	{
		if(!isset($ld['id'])){
			$ld['error'] = 'Missing Department!';
			return false;
		}else{
			$ld['id'] = explode('-',$ld['id']);
			if(count($ld['id']) > 1){
				$ld['parent'] = end($ld['id']);
			}
			$ld['id'] = reset($ld['id']);
		}
        
        $this->dbu->query("SELECT department_id FROM department WHERE department_id = ?",$ld['id']);
        if(!$this->dbu->move_next()){
          $ld['error'].="Invalid ID"."";
          return false;
        }
        
        
        if(!isset($ld['name'])){
        	$ld['error'] = 'No new name specified';
        	return false;
        }else{
        	$this->dbu->query("SELECT department_id FROM department WHERE name = '".$ld['name']."' AND department_id!= '".$ld['id']."'");
			if($this->dbu->move_next())
			{
				$ld['error']='The selected department name is already in use.';
				return false;
			}

        }
        
		return true;
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
		//move to root
		$this->dbu->query("SELECT lft,rgt, parent_id FROM department WHERE department_id = ?",$ld['id']);
		$updateTreeAfterDelete = false;
		if($this->dbu->move_next()){
			$ld['lft'] = $this->dbu->f('lft');
			$ld['rgt'] = $this->dbu->f('rgt');
			$parent_id = $this->dbu->f('parent_id');
			$updateTreeAfterDelete = true;
			$nodeInfo = array('lft' => $ld['lft'],'rgt'=>$ld['rgt']);
		}
		$nodes = array($ld['id']);
		$query = $this->dbu->query("SELECT department_id FROM department WHERE lft BETWEEN ".$nodeInfo['lft'].' AND '.$nodeInfo['rgt']);
		while ($query->next()){
			array_push($nodes,$query->f('department_id'));
		}
		$this->dbu->query("UPDATE member SET department_id = ".$parent_id ." WHERE department_id IN (".join(',',$nodes).")");
		$this->dbu->query("UPDATE computer SET department_id = ".$parent_id ."  WHERE department_id IN (".join(',',$nodes).")");
	   
		$this->dbu->query("DELETE FROM department WHERE department_id IN (".join(',',$nodes).")");
		$this->dbu->query("DELETE FROM application2category WHERE department_id IN (".join(',',$nodes).")");
		if($updateTreeAfterDelete){
			$this->rebuild(0,0);
		}

		$this->dbu->query("DELETE FROM workschedule WHERE department_id IN (".join(',',$nodes).")");
		//kill alerts for this department
		$alertDepartments = $this->dbu->query("SELECT * FROM alert_department WHERE department_id IN (".join(',',$nodes).")");
		while ($alertDepartments->next()){
			$this->dbu->query("DELETE FROM alert WHERE alert_id = ?",$alertDepartments->f('alert_id'));	
			$this->dbu->query("DELETE FROM alert_other  WHERE alert_id = ? AND department_id = ?",array($alertDepartments->f('alert_id'),$alertDepartments->f('department_id')));
			$this->dbu->query("DELETE FROM alert_time WHERE alert_id = ? AND department_id = ?",array($alertDepartments->f('alert_id'),$alertDepartments->f('department_id')));
			$this->dbu->query("DELETE FROM alert_trigger WHERE alert_id = ? AND department_id = ?",array($alertDepartments->f('alert_id'),$alertDepartments->f('department_id')));
		}
		$this->dbu->query("DELETE FROM alert_department WHERE department_id IN (".join(',',$nodes).")");
		
		//email reports!
		$emailDepartments = $this->dbu->query("SELECT * FROM email_report_group WHERE department_id IN (".join(',',$nodes).")");
		while ($emailDepartments->next()){
			$this->dbu->query("DELETE FROM email_report WHERE email_report_id='".$emailDepartments->f('email_report_id')."'");
			$this->dbu->query("DELETE FROM email_report_type WHERE email_report_id='".$emailDepartments->f('email_report_id')."'");
			$this->dbu->query("DELETE FROM email_report_frequency WHERE email_report_id='".$emailDepartments->f('email_report_id')."'");
			$this->dbu->query("DELETE FROM email_report_receiver WHERE email_report_id='".$emailDepartments->f('email_report_id')."'");
		}
		$this->dbu->query("DELETE FROM email_report_group WHERE department_id IN (".join(',',$nodes).")");
		
		$pieces = split('-', $_SESSION['filters']['f']);
		
		if($pieces[0] == 's'.$ld['id'])
		{
			$_SESSION['filters']['f'] = 's'.$parent_id;
		}
        
		member2manage_Delete($ld['id']);
		$ld['error']='Department has been successfully deleted.';
        return true; 
	}

	/****************************************************************
	* function delete_validate(&$ld)                                *
	****************************************************************/
	function delete_validate(&$ld)
	{
		if(!isset($ld['id'])){
			$ld['error'] = 'Missing Department!';
			return false;
		}else{
			$ld['id'] = explode('-',$ld['id']);
			if(count($ld['id']) > 1){
				$ld['parent'] = end($ld['id']);
			}
			$ld['id'] = reset($ld['id']);
		}
        
        $this->dbu->query("SELECT department_id FROM department WHERE department_id = ?",$ld['id']);
        if(!$this->dbu->move_next()){
          $ld['error'].="Invalid ID"."";
          return false;
        }        
        return true;
	}
	
	function movemember(&$ld){
		//do a small validation
		if(!isset($ld['move']) || !is_numeric($ld['move'])){
			$ld['error'] = 'Missing Move!';
			return false;
		}
		if(!isset($ld['id'])){
			$ld['error'] = 'Missing Department!';
			return false;
		}else{
			$ld['id'] = explode('-',$ld['id']);
			if(count($ld['id']) == 1){
				$ld['id'] = current($ld['id']);
			}else{
				$ld['id'] = reset($ld['id']);
			}
		}
		if(!isset($ld['member']) || !is_numeric($ld['member'])){
			$ld['error'] = 'Missing Member!';
			return false;
		}
		if(!isset($ld['computer']) || !is_numeric($ld['computer'])){
			$ld['error'] = 'Missing computer!';
			return false;
		}
		//everything looks okey..so let's do this
		switch ($ld['move']){
			case 1:
				$this->dbu->query("UPDATE member SET department_id = ? WHERE member_id = ?",array($ld['id'],$ld['member']));
				break;
			case 2:
				$this->dbu->query("UPDATE computer SET department_id = ? WHERE computer_id = ?",array($ld['id'],$ld['computer']));	
				break;
			case 3:
				$this->dbu->query("UPDATE computer SET department_id = ? WHERE computer_id = ?",array($ld['id'],$ld['computer']));	
				$this->dbu->query("UPDATE member SET department_id = ? WHERE member_id = ?",array($ld['id'],$ld['member']));
				break;//set all :)
		}
		member2manage_Rebuild();
		$managers = $this->dbu->query("SELECT DISTINCT manager_id FROM member2manage INNER JOIN member ON member.member_id = member2manage.member_id WHERE member.department_id = '".$ld['id']."'");
		
		while ($managers->next()) {
			$this->dbu->query("INSERT INTO member2manage SET member_id='".$ld['member']."', manager_id='".$managers->f('manager_id')."'");
		}
		
		$bug = get_debug_instance();
		$ld['sql'] = $bug->display();
		return true;
	}

	function rebuild($parent, $left){
		$right = $left + 1;
		$nodes = $this->dbu->query("SELECT * FROM department WHERE parent_id = ?",$parent);
		while ($nodes->next()){
			$right = $this->rebuild($nodes->f('department_id'), $right);
		}
		$this->dbu->query("UPDATE department SET lft = ?,rgt = ? WHERE department_id = ?",array($left,$right,$parent));
		return $right + 1;
	}

	function movenode(&$ld){
		//do a small validation
		if(!isset($ld['parent'])){
			$ld['error'] = 'Missing Department!';
			return false;
		}
		if(!isset($ld['node'])){
			$ld['error'] = 'Missing Department!';
			return false;
		}
		
		
		$parent = $this->dbu->row("SELECT parent_id,lft FROM department WHERE department_id = '".$ld['node']."'");
		if($ld['parent'] != $parent['parent']){
			$this->dbu->query("UPDATE department SET parent_id = '".$ld['parent']."' WHERE department_id = '".$ld['node']."'");
			//parent changed need to rebuild whole tree :(
			$this->rebuild(0,0);
		}		

		$this->dbu->query("DELETE FROM application2category WHERE department_id='".$ld['node']."'");
		
		
			
			$application_categories =$this->dbu->query("SELECT * FROM application2category WHERE department_id='".$ld['parent']."'");			
			
			while ($application_categories->next())
			{
				$this->dbu->query("INSERT INTO application2category SET department_id = '".$ld['id']."',
																			application_category_id = '".$application_categories->f('application_category_id')."',
																			link_id = '".$application_categories->f('link_id')."',			
																			link_type = '".$application_categories->f('link_type')."'");
				
			}
		return true;//ouch!
	}
	
}//end class