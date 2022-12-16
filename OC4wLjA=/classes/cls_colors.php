<?php
/***********************************************************************
* @Author: MedeeaWeb Works											   *
***********************************************************************/
class colors
{
	var $dbu;
	
	function colors()
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
		
		$ld['theme_id'] = $this->dbu->query_get_id("INSERT INTO theme SET name='".$ld['name']."'");
		
		foreach ($ld['color'] as $top_colors_id => $hex)
		{
			$this->dbu->query("INSERT INTO theme_color SET color='".$hex."',theme_id='".$ld['theme_id']."'");
		}

		$ld['error'].="The color theme has been successfully added.";
		
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
		
		$this->dbu->query_get_id("UPDATE theme SET name='".$ld['name']."' WHERE theme_id='".$ld['theme_id']."'");
		
		foreach ($ld['color'] as $top_colors_id => $hex)
		{
			$this->dbu->query("UPDATE theme_color SET color='".$hex."' WHERE theme_color_id='".$top_colors_id."' AND theme_id='".$ld['theme_id']."'");
		}

		$ld['error'].="The color theme has been successfully updated.";
		return true;
	}

	/****************************************************************
	* function setdefault(&$ld)                                     *
	****************************************************************/
	function setdefault(&$ld)
	{
		if(!$this->setdefault_validate($ld))
		{
			return false;
		}
		$default_colors = $this->dbu->query("SELECT color FROM theme_color WHERE theme_id='1' ORDER BY theme_color_id ASC");
		
		$theme_colors = $this->dbu->query("SELECT theme_color_id FROM theme_color WHERE theme_id='".$ld['theme_id']."' ORDER BY theme_color_id ASC");
		
		while ($theme_colors->next()) {
			$default_colors->next();			
			$this->dbu->query("UPDATE theme_color SET color='".$default_colors->f('color')."' WHERE theme_color_id='".$theme_colors->f('theme_color_id')."' AND theme_id='".$ld['theme_id']."'");
		}
		
		$ld['error'].='The color theme has been reset to default.';
		return true;
	}
	
	/****************************************************************
	* function update_validate(&$ld)                                *
	****************************************************************/
	function update_validate(&$ld)
	{
		foreach ($ld['color'] as $top_colors_id => $hex)
		{
			if(!$hex)
			{
				$ld['error'].='Please make sure you have inserted/selected a color code in all fields.';
				return false;
			}
			
			if(preg_match('/[^A-Fa-f0-9]/i', $hex))
			{
				$ld['error'].='Please make sure you have inserted the color code field corect (hexadecimal values).';
				return false;
			}
			
		}
		
		return true;
	}
	
	/****************************************************************
	* function add_validate(&$ld)                                   *
	****************************************************************/
	function add_validate(&$ld)
	{
		foreach ($ld['color'] as $top_colors_id => $hex)
		{
			if(!$hex)
			{
				$ld['error'].='Please make sure you have inserted/selected a color code in all fields.';
				return false;
			}
			
			if(preg_match('/[^A-Fa-f0-9]/i', $hex))
			{
				$ld['error'].='Please make sure you have inserted the color code field corect (hexadecimal values).';
				return false;
			}
		}
		
		if(!$ld['name'])
		{
			$ld['error'].='Please fill in the Theme Name field !';
			return false;
		}
		
		return true;
	}
	
	/****************************************************************
	* function setdefault_validate(&$ld)                            *
	****************************************************************/
	function setdefault_validate(&$ld)
	{
		if(!$ld['theme_id'])
		{
			$ld['error'].='Invalid theme ID!';
			return false;
		}
		else 
		{
			$this->dbu->query("SELECT theme_id FROM theme WHERE theme_id='".$ld['theme_id']."'");
			if(!$this->dbu->move_next())
			{
				$ld['error'].='Invalid theme ID!';
				return false;
			}
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
		
		$selected = $this->dbu->field("SELECT theme.selected FROM theme WHERE theme_id='".$ld['theme_id']."'");
		
		if ($selected)
		{
			$this->dbu->query("UPDATE theme SET selected=1 WHERE theme.default = 1");
		}
		
		$this->dbu->query("DELETE FROM theme WHERE theme_id='".$ld['theme_id']."'");
		$this->dbu->query("DELETE FROM theme_color WHERE theme_id='".$ld['theme_id']."'");
		
		$ld['error'].="The color theme has been successfully deleted.";
		return true;
	}
	
	/****************************************************************
	* function delete_validate(&$ld)                                *
	****************************************************************/
	function delete_validate(&$ld)
	{
		if(!$ld['theme_id'])
		{
			$ld['error'].='Invalid theme ID!';
			return false;
		}
		else 
		{
			$this->dbu->query("SELECT theme_id FROM theme WHERE theme_id='".$ld['theme_id']."' AND theme.default = 0");
			
			if(!$this->dbu->move_next())
			{
				$ld['error'].='Invalid theme ID!';
				return false;
			}
		}
		return true;
	}
	/****************************************************************
	* function select(&$ld)                                         *
	****************************************************************/
	function select(&$ld)
	{
		if(!$this->select_validate($ld))
		{
			return false;
		}
		
		$this->dbu->query("UPDATE theme SET selected = 0");
		$this->dbu->query("UPDATE theme SET selected = 1 WHERE theme_id='".$ld['theme_id']."'");
		
		$ld['error'].="The color theme has been successfully selected.";
		return true;
	}
	
	/****************************************************************
	* function delete_validate(&$ld)                                *
	****************************************************************/
	function select_validate(&$ld)
	{
		if(!$ld['theme_id'])
		{
			$ld['error'].='Invalid theme ID!';
			return false;
		}
		else 
		{
			$this->dbu->query("SELECT theme_id FROM theme WHERE theme_id='".$ld['theme_id']."'");
			
			if(!$this->dbu->move_next())
			{
				$ld['error'].='Invalid theme ID!';
				return false;
			}
		}
		
		return true;
	}
	
}//end class