<?php
/************************************************************************
* @Author: MedeeaWeb Works
***********************************************************************/
class application
{
	var $dbu;
	var $parentapps = array();
	function application()
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
		
		$ld['application_id'] = $this->dbu->query_get_id("INSERT INTO application SET
									description = '".$ld['description']."',			
									application_type = '".$ld['application_type']."'");
		$d = array(
			'type' => 0,
			'cat' => $ld['application_category_id'],
			'id' => $ld['application_id'],
			'dep' => 's1'
		);
		$this->category($d);
		$ld['error']='Application has been added successfully.<br>';
		$ld['pag']='application';
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
		//old application_type 
		$app_type = $this->dbu->field("SELECT application_type FROM application WHERE application_id = '".$ld['application_id']."'");
		
		
		$this->dbu->query("UPDATE application SET
									description = '".$ld['description']."',			
									application_type = '".$ld['application_type']."',
									secondary_type = '".$app_type."'
									WHERE application_id = '".$ld['application_id']."'");
		$d = array(
			'type' => 0,
			'cat' => $ld['application_category_id'],
			'id' => $ld['application_id'],
			'dep' => 's1'
		);
		$this->category($d);
		$ld['error']='Application has been successfully updated.<br>';
		$ld['pag']='applications';
		if($ld['goto'] != ''){
			$ld['pag']=$ld['goto'];
		}
		
		return true;
	}

	/****************************************************************
	* function delete(&$ld)                                         *
	****************************************************************/	
	function delete(&$ld)
	{
		if(!$this->delete_validate($ld))
		{
			return false;
		}
		$this->dbu->query("DELETE FROM application WHERE application_id = '".$ld['application_id']."'");
		$ld['error']='Application has been successfully delete.<br>';
		return true;
	}
	
	/****************************************************************
	* function add_validate(&$ld)                                   *
	****************************************************************/
	function add_validate(&$ld)
	{
		$is_ok=true;
					
			if(!isset($ld['application_type']))
			{
			    $ld['error'].='Please select a value in the <strong>Application Type</strong> dropdown.<br>';
			    $is_ok=false;
			}			
			if(!$ld['description'])
			{
			    $ld['error'].='Please fill in the <strong>Name</strong> field.<br>';
			    $is_ok=false;
			}
						
		return $is_ok;
	}
	
	/****************************************************************
	* function update_validate(&$ld)                                *
	****************************************************************/
	function update_validate(&$ld)
	{
		if(!$ld['application_id'])
		{
			$ld['error']='Invalid ID<br>';
			return false;
		}
		$this->dbu->query("SELECT application_id FROM application WHERE application_id = '".$ld['application_id']."'");
		if(!$this->dbu->move_next())
		{
			$ld['error']='Invalid ID<br>';
			return false;
		}
		return $this->add_validate($ld);
	}
	
	/****************************************************************
	* function delete_validate(&$ld)                                *
	****************************************************************/	
	function delete_validate(&$ld)
	{
		$is_ok=true;
		if(!$ld['application_id'])
		{
			$ld['error']='Invalid ID<br>';
			return false;
		}
		$this->dbu->query("SELECT application_id FROM application WHERE application_id = '".$ld['application_id']."'");
		if(!$this->dbu->move_next())
		{
			$ld['error']='Invalid ID<br>';
			return false;
		}
		return $is_ok;
	}

	function category(&$ld){
		if(!is_numeric($ld['type'])){
			// $ld['error'] = 'No type Found';
			$ld['type'] = 0;
			// return false;
		}
		if(!is_numeric($ld['cat'])){
			$ld['error'] = 'No category Found';
			return false;
		}
		
		
		
		if(!is_numeric($ld['id'])){
			$ld['error'] = 'No ID Found';
			return false;
		}
		
		//0 - not know, 1 - chat, 2 - document, 3 - site
		switch ($ld['type']){
			case 0://window
				$table = 'application';
				break;
			case 1://chat
				$table = 'chat';
				break;
			case 2://document
				$table = 'document';
				break;
			case 3://site
				$table = 'website';
				break;
		}
		$department = isset($ld['dep']) ? $ld['dep'] : $_SESSION['filters']['f'];
		
		
		//get the department
		$pieces = explode('-',$department);
		$nodeId = substr($pieces[0],1);
		$nodeInfo = $this->dbu->row("SELECT lft,rgt FROM department WHERE department_id = ?",$nodeId);
		$nodes = array();
		$query = $this->dbu->query("SELECT department_id FROM department WHERE 1=1");
		while ($query->next()){
			array_push($nodes,$query->f('department_id'));
		}
		//if we have it on this department then we remove it leaving all the children in peace
		if($ld['cat'] == 1){
			$this->dbu->query("DELETE FROM application2category WHERE link_id = ?
																AND link_type = ?",array($ld['id'],
																						   $ld['type']));
			return true;
		}
		
		
		//we need to have fun....
		foreach ($nodes as $department_id){
			$query = $this->dbu->query("SELECT application2category_id FROM application2category WHERE department_id = ? 
																								 AND  link_id = ?
																								 AND link_type = ?",array($department_id,$ld['id'],$ld['type']));
			if($query->next()){
				//okey we can haz it update category
				$this->dbu->query("UPDATE application2category SET application_category_id = ? WHERE application2category_id = ?",array($ld['cat'],$query->f('application2category_id')));
			}else{
				$this->dbu->query("INSERT INTO application2category SET application_category_id = ?,
																		department_id = ?, 
																		link_id = ?,
																		link_type = ?",array($ld['cat'],
																							$department_id,
																							$ld['id'],
																							$ld['type']));
			}
		}
		return true;
	}
	//*************************************************************************************************************
	//	main productivity function
	function productive(&$ld){
		if(!is_numeric($ld['type'])){
			$ld['error'] = 'No type Found';
			return false;
		}
		if(!is_numeric($ld['id'])){
			$ld['error'] = 'No ID Found';
			return false;
		}
		if(!is_numeric($ld['val'])){
			$ld['error'] = 'No Productivity Found';
			return false;
		}
		$pieces = explode('-',$_SESSION['filters']['f']);
		$nodeId = substr($pieces[0],1);
		$nodeInfo = $this->dbu->row("SELECT lft,rgt FROM department WHERE department_id = ?",$nodeId);
		$department_id = 1;
			if( $ld['val'] != 1 )
			{
				$this->dbu->query("INSERT INTO application_productivity SET department_id = ".$department_id.",
																			productive = ".$ld['val'].",
																			link_id = ".$ld['id'].",
																			link_type = ".$ld['type']. "
																			ON DUPLICATE KEY UPDATE productive = " . $ld['val']);
			} else {
				$this->dbu->query("DELETE FROM application_productivity WHERE link_id='".$ld['id'] . "AND link_type = ".$ld['type'] ."' AND department_id = ".$department_id);
			}
			
			//	clean up the db from trash
			$this->dbu->query("DELETE FROM application_productivity WHERE link_id=0");
			$this->dbu->query("DELETE FROM application_productivity WHERE department_id=0");
			
		if(isset($ld['app'])){
			$ld['app'] = $ld['id'];
			$ld['type'] = $this->dbu->field("SELECT application_type FROM application WHERE application_id = ?",$ld['id']);
			if($ld['type'] != 0){
				$ld['pag'] = 'xproductivityreportdetails';
			}
		}
		return true;
	}
	
	/****************************************************************
	* function application_type(&$ld)                               *
	****************************************************************/
	
	function application_type(&$ld)
	{
		if(!$this->application_type_validate($ld))
		{
			return false;
		}
		
		$this->dbu->query("UPDATE application SET
									application_type = '".$ld['cat']."'
									WHERE application_id='".$ld['id']."'");
		
		$ld['error']='Application type has been updated.<br>';
		return true;
	}
	
	/****************************************************************
	* function application_type_validate(&$ld)                      *
	****************************************************************/
	
	function application_type_validate(&$ld)
	{
		$is_ok=true;
					
		if(!isset($ld['cat']))
		{
		    $ld['error'].='Invalit application type Id';
		    $is_ok=false;
		}			
		if(!$ld['id'])
		{
		    $ld['error'].='Invalid application Id';
		    $is_ok=false;
		}
						
		return $is_ok;
	}
}//end class