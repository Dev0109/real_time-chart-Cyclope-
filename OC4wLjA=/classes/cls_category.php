<?php
/************************************************************************
* @Author: MedeeaWeb Works
***********************************************************************/
class category
{
	var $dbu;
	
	function category()
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
		if(!$ld['parent_id']){
			$nodeInfo = $this->dbu->query('SELECT MAX(rgt) AS rgt FROM application_category');
			if($nodeInfo->next()){
				$ld['lft'] = $nodeInfo->f('rgt') + 1;
				$ld['rgt'] = $nodeInfo->f('rgt') + 2;
			}else{
				$ld['lft'] = 1;
				$ld['rgt'] = 2;
			}
			$ld['parent_id'] = 0;
		}else{
			$ld['parent_id'] = 0;
			$nodeInfo = $this->dbu->query('SELECT MAX(rgt) AS rgt FROM application_category WHERE parent_id = ? ORDER BY rgt DESC',$ld['parent_id']);
			if(!$nodeInfo->next()){
				$ld['error'] = 'Could not find parent info';
				return false;
			}
			if(is_null($nodeInfo->f('rgt'))){
				$nodeInfo = $this->dbu->query('SELECT rgt FROM application_category WHERE application_category_id = ?',$ld['parent_id']);
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
		
		if ($ld['locked'] != 1){
			$ld['locked'] = 0;
		}
		$ld['application_category_id'] = $this->dbu->query_get_id("INSERT INTO application_category SET 			
									category = '".encode_numericentity($ld['category'])."',			
									parent_id = '".$ld['parent_id']."',			
									locked = '".$ld['locked']."',					
									lft = '".$ld['lft']."',			
									rgt = '".$ld['rgt']."'");
		if($needsUpdate){
			//daca nu se poate cu vorba frumoasa..merge cu fortza Steaua
			$this->rebuild(0,0);
		}
		
		$ld['error']='Application Category has been added successfully.<br>';
		$ld['pag']='categories';

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
		
		$this->dbu->query("UPDATE application_category SET 			
									category = '".encode_numericentity($ld['category'])."'			
									 WHERE application_category_id = '".$ld['application_category_id']."'");
		$parent = $this->dbu->row("SELECT parent_id,lft FROM application_category WHERE application_category_id = '".$ld['application_category_id']."'");
		
		
		if($ld['parent_id'] != $parent['parent_id']){
			$this->dbu->query("UPDATE application_category SET parent_id = '".$ld['parent_id']."' WHERE application_category_id = '".$ld['application_category_id']."'");
			//parent changed need to rebuild whole tree :(
			$this->rebuild(0, 0);
		}

		$ld['error']='Application Category has been successfully updated.<br>';
		$ld['pag']='categories';
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
		// move all to root 
		$this->dbu->query("SELECT lft,rgt FROM application_category WHERE application_category_id = ?",$ld['application_category_id']);
		$updateTreeAfterDelete = false;
		if($this->dbu->move_next()){
			$ld['lft'] = $this->dbu->f('lft');
			$ld['rgt'] = $this->dbu->f('rgt');
			$updateTreeAfterDelete = true;
			$nodeInfo = array('lft' => $ld['lft'],'rgt'=>$ld['rgt']);
		}
		
		$nodes = array($ld['application_category_id']);
		$query = $this->dbu->query("SELECT application_category_id 
									FROM application_category 
									WHERE lft BETWEEN ".$nodeInfo['lft'].' AND '.$nodeInfo['rgt']
								   );
		while ($query->next()){
			array_push($nodes,$query->f('application_category_id'));
		}
		
		//Lipsa liniei urmatoare a coborat toti Apostoli si Sfinti in 22.02.2011 ora 11:52 AM
		//Linia a fost modificata in 24.02.2011 ora 12:11 PM :))
		/*$this->dbu->query("UPDATE application SET application_category_id = '1'
						   WHERE application_category_id IN (".join(',',$nodes).")
						  ");
		$this->dbu->query("UPDATE document SET application_category_id = '1' 
						   WHERE application_category_id IN (".join(',',$nodes).")
						  ");
		$this->dbu->query("UPDATE chat SET application_category_id = '1' 
		                   WHERE application_category_id IN (".join(',',$nodes).")
		                  ");
		$this->dbu->query("UPDATE website SET application_category_id = '1' 
		                   WHERE application_category_id IN (".join(',',$nodes).")
		                  ");*/
		
		$this->dbu->query("DELETE FROM application_category WHERE application_category_id IN (".join(',',$nodes).")");
		if($updateTreeAfterDelete){            
           $this->rebuild(0,0);                    
		}
				
		$ld['error']='Application Category has been successfully delete.<br>';
		return true;		
	}
	
	/****************************************************************
	* function add_validate(&$ld)                                   *
	****************************************************************/
	function add_validate(&$ld)
	{	
		$is_ok=true;
		if(!$ld['category']){
			$ld['error'] .= 'Please fill in the categories name.<br>';
			$is_ok = false;
		}
		$this->dbu->query("SELECT application_category_id FROM application_category WHERE category = '".encode_numericentity($ld['category'])."'");
		
		if($this->dbu->move_next())
		{
			$ld['error']='The selected category name is already in use.<br>';
			return false;
		}
		return $is_ok;
	}
	
	/****************************************************************
	* function update_validate(&$ld)                                *
	****************************************************************/
	function update_validate(&$ld)
	{
		$is_ok = true;
		
		if(!$ld['application_category_id'])
		{
			$ld['error']='Invalid ID<br>';
			return false;
		}
		$this->dbu->query("SELECT application_category_id FROM application_category WHERE application_category_id = '".$ld['application_category_id']."'");
		if(!$this->dbu->move_next())
		{
			$ld['error']='Invalid ID<br>';
			return false;
		}
		
		if(!$ld['category']){
			$ld['error'] .= 'Please fill in the categories name.<br>';
			$is_ok = false;
		}
		
		$this->dbu->query("SELECT application_category_id FROM application_category WHERE category = '".encode_numericentity($ld['category'])."' AND application_category_id != '".$ld['application_category_id']."'");
		
		if($this->dbu->move_next())
		{
			$ld['error']='The selected category name is already in use.<br>';
			$is_ok = false;
		}
		
		return $is_ok;
	}
	
	/****************************************************************
	* function delete_validate(&$ld)                                *
	****************************************************************/	
	function delete_validate(&$ld)
	{
		$is_ok=true;
		if(!$ld['application_category_id'])
		{
			$ld['error']='Invalid ID<br>';
			return false;
		}elseif ($ld['application_category_id'] == 1){
			$ld['error'] = "You can not delete this category.<pre>
<!-- 
      _.--.
    .'   ` '
     ``'.  .'     .c-.. Skunk
        `.  ``````  .-'
       -'`. )--. .'`
       `-`._   \_`-- 
--></pre>";
			return false;			
		}
		$this->dbu->query("SELECT application_category_id FROM application_category WHERE application_category_id = '".$ld['application_category_id']."'");
		if(!$this->dbu->move_next())
		{
			$ld['error']='Invalid ID<br>';
			return false;
		}
		return $is_ok;
	}
	
	function rebuild($parent, $left){
		$right = $left + 1;
		$nodes = $this->dbu->query("SELECT * FROM application_category WHERE parent_id = ?",$parent);
		while ($nodes->next()){
			$right = $this->rebuild($nodes->f('application_category_id'), $right);
		}
		$this->dbu->query("UPDATE application_category SET lft = ?,rgt = ? WHERE application_category_id = ?",array($left,$right,$parent));
		return $right + 1;
	}

	
}//end class