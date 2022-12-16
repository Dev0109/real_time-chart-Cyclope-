<?php
/*************************************************************************
* @Author: Tinu Coman                                          			 *
*************************************************************************/
class dynamic_menu
{
  	var $menu_container="";
  	var $mtpl="";
  	var $menu_array="";

	function dynamic_menu($menu , $template)
	{
	    $this->mtpl=$template;
	    $this->menu_array=$menu;
	  
	   
	}

	function build_menu()
	{
		$menu_level='';
		$level=0;
		$excluded='';
		
		if($this->menu_array['key'])
		foreach ($this->menu_array['key'] as $menu_key => $menu_id)
		{
			if($this->menu_array[$menu_id]['parent']==0)
			{
				$this->menu_add_main_button($this->menu_array[$menu_id]);
		        if($this->submenu_exist($menu_id))
		        {
		            $this->menu_submenu_open($this->menu_array[$menu_id]);
		        }
				$menu_level[$level]=$level;
				$parent_id=$menu_id;
				$submenu_exist=true;
				$level=0;
				$excluded.=",".$parent_id;
				while($submenu_exist)
				{
					$result=$this->get_submenu($parent_id, $excluded);
		            if($result)
		            {
		            	$menu_level[$level]=$parent_id;
		            	$level++;
		            	$this->menu_add_submenu_button($this->menu_array[$result['out_id']], $result['out_id']);
		            	if($this->submenu_exist($result['out_id']))
		            	{
		            		$this->menu_submenu_open($this->menu_array[$result['out_id']]);
		            	}
		            	
		            	$parent_id=$result['next_parent'];
		            	$excluded.=",".$result['next_parent'];
		           	}
		            else 
		            {
		            	$level--;
		            	$parent_id=$menu_level[$level];
		            	if(!$this->submenu_exist($parent_id, $excluded))
		            	{
		            		$this->menu_submenu_close($this->menu_array[$parent_id]);
		            	}
		            }
		            if($level < 0)
		           	{
		           		$submenu_exist=false;
		           		$this->menu_close_main_button($this->menu_array[$parent_id]);
		           	}
				}		
			}
		}
		
		$menu_lenght=strlen($this->menu_container);
		
		$cut_end_lenght=strlen($this->mtpl['delete_end']);
		$mlenght=$menu_lenght-$cut_end_lenght;
		$this->menu_container=substr($this->menu_container, 0, $mlenght);
		$this->menu_container=$this->mtpl['dynamic_menu_start'].$this->menu_container.$this->mtpl['dynamic_menu_end'];
		return $this->menu_container;
	
	}

	function get_submenu($parent_id, $excluded=0)
	{
		$out_id="";
		$next_parent="";
		$prev_parent="";
		
		$exclude_keys = split(",",$excluded);
		foreach ($exclude_keys as $key=>$value)
		{
			$exclude[$value]=1;
		}
		
		foreach ($this->menu_array['key'] as $m_key => $m_id)
		{
	        if(!$exclude[$m_key] && ($this->menu_array[$m_id]['parent'] == $parent_id))
	        {
	        	$return['out_id']=$m_id;
				$return['next_parent']=$m_id;
				$return['prev_parent']=$parent_id;
				
				return $return;
	        }
		}
		
		return false;
	}

	function submenu_exist($parent_id, $excluded=0)
	{
		$exclude_keys = split(",",$excluded);
		foreach ($exclude_keys as $key=>$value)
		{
			$exclude[$value]=1;
		}
	
		foreach ($this->menu_array['key'] as $m_key => $m_id)
		{
	        if(!$exclude[$m_key] && ($this->menu_array[$m_id]['parent'] == $parent_id))
	        {
	        	
				return true;
	        }
		}
		
		return false;
	}

	function menu_add_main_button($menu)
	{
		$this->menu_container.=$this->mtpl['main_button_start'];
		$tmp_btn=$this->mtpl['main_button_template'];
		if($menu['target'])
		{
			$tmp_btn=str_replace('[!TARGET!]','target="'.$menu['target'].'"', $tmp_btn);
		}
		else
		{
			$tmp_btn=str_replace('[!TARGET!]','', $tmp_btn);
		}
		
		$tmp_btn=str_replace('[!BUTTON_TEXT!]',$menu['text'], $tmp_btn);
		$tmp_btn=str_replace('[!BUTTON_LINK!]',$menu['url'], $tmp_btn);
		$this->menu_container.=$tmp_btn;
	}

	function menu_close_main_button($menu)
	{
		$this->menu_container.=$this->mtpl['main_button_end'];
		
	}

	function menu_add_submenu_button($menu, $menu_id)
	{
	    if(!$this->submenu_exist($menu_id))
	    {
			$tmp_btn=$this->mtpl['sub_button_simple_template'];
	    }
	    else 
	    {
	    	$tmp_btn=$this->mtpl['sub_button_follow_template'];
	    }
		$tmp_btn=str_replace('[!BUTTON_TEXT!]',$menu['text'], $tmp_btn);
		$tmp_btn=str_replace('[!BUTTON_LINK!]',$menu['url'], $tmp_btn);
		if($menu['target'])
		{
			$tmp_btn=str_replace('[!TARGET!]','target="'.$menu['target'].'"', $tmp_btn);
		}
		else
		{
			$tmp_btn=str_replace('[!TARGET!]','', $tmp_btn);
		}
		$this->menu_container.=$tmp_btn;
	
	}

	function menu_submenu_open($menu)
	{
		
		$this->menu_container.=$this->mtpl['submenu_start'];
	}

	function menu_submenu_close($menu)
	{
		if($menu['text'])
		{
			$this->menu_container.=$this->mtpl['submenu_end'];
	
		}
	}

}

// end class
?>