<?php
/**
 * @return unknown
 * @param $str string
 * @desc Enter description here...
 */
function str_to_filename($str)
{
	$out_str='';
	$out_str.=str_replace(" ", "-", strtolower($str));
	$out_str.='.html';
	return $out_str;
}

/**
 * @return unknown
 * @param $selected unknown
 * @desc Enter description here...
 */
function build_content_templates_list($selected=0)
{
	$db=new mysql_db;
	$db->query("select content_template_id, name from cms_content_template order by name");
	while($db->move_next())
	{
		$opt[$db->f('content_template_id')] = $db->f('name');
	}
	
	if($opt)
    foreach($opt as $key => $val)
	{
		$out_str.="<option value=\"".$key."\" ";//options values
		$out_str.=($key==$selected?" SELECTED ":"");//if selected
		$out_str.=">".$val."</option>";//options names
	}
	return $out_str;
}

/**
 * @return unknown
 * @param $selected unknown
 * @desc Enter description here...
 */
function build_cms_menu_list($selected=0)
{
	$db=new mysql_db;
	$db->query("select menu_id, name from cms_menu order by name");
	while($db->move_next())
	{
		$opt[$db->f('menu_id')] = $db->f('name');
	}

	if($opt)
    foreach($opt as $key => $val)
	{
		$out_str.="<option value=\"".$key."\" ";//options values
		$out_str.=($key==$selected?" SELECTED ":"");//if selected
		$out_str.=">".$val."</option>";//options names
	}
	return $out_str;
}


/**
 * @return unknown
 * @param $selected unknown
 * @desc Enter description here...
 */
function build_cms_page_type($selected=0)
{
	$db=new mysql_db;
	$db->query("select page_type_id, name from cms_page_type order by name");
	while($db->move_next())
	{
		$opt[$db->f('page_type_id')] = $db->f('name');
	}

	if($opt)
    foreach($opt as $key => $val)
	{
		$out_str.="<option value=\"".$key."\" ";//options values
		$out_str.=($key==$selected?" SELECTED ":"");//if selected
		$out_str.=">".$val."</option>";//options names
	}
	return $out_str;
}

/**
 * @return unknown
 * @param $tag unknown
 * @desc Enter description here...
 */
function secure_cms_tag($tag)
	{
		return preg_match("/^\[![a-z0-9A-Z!_-]+!\]$/i",$tag);
			
	}

/**
 * @return unknown
 * @param $selected unknown
 * @desc Enter description here...
 */
function build_menu_level_list($selected=0)
{
	$il=0;
	for($il=2; $il <=10; $il++)
	{
		$opt[$il] = $il;
	}

	if($opt)
    foreach($opt as $key => $val)
	{
		$out_str.="<option value=\"".$key."\" ";//options values
		$out_str.=($key==$selected?" SELECTED ":"");//if selected
		$out_str.=">Level ".$val."</option>";//options names
	}
	return $out_str;
}
/**
 * @return unknown
 * @param $selected unknown
 * @desc Enter description here...
 */
function build_h_menu_template_list($selected=0)
{
	$db=new mysql_db;
	$db->query("select menu_template_file_id, name, file_name from cms_menu_template_file where type='1'");
	while($db->move_next())
	{
		$opt[$db->f('file_name')] = $db->f('name');
	}

	if($opt)
    foreach($opt as $key => $val)
	{
		$out_str.="<option value=\"".$key."\" ";//options values
		$out_str.=($key==$selected?" SELECTED ":"");//if selected
		$out_str.=">".$val."</option>";//options names
	}
	return $out_str;
}

/**
 * @return unknown
 * @param $selected unknown
 * @desc Enter description here...
 */
function build_v_menu_template_list($selected=0)
{
	$db=new mysql_db;
	$db->query("select menu_template_file_id, name, file_name from cms_menu_template_file where type='2'");
	while($db->move_next())
	{
		$opt[$db->f('file_name')] = $db->f('name');
	}

	if($opt)
    foreach($opt as $key => $val)
	{
		$out_str.="<option value=\"".$key."\" ";//options values
		$out_str.=($key==$selected?" SELECTED ":"");//if selected
		$out_str.=">".$val."</option>";//options names
	}
	return $out_str;
}

/**
 * @return unknown
 * @param $selected unknown
 * @desc Enter description here...
 */
function build_menu_template_type_list($selected=0)
{

	$opt=array(

            	1	=> 'Horizontal Menu',
            	2	=> 'Vertical Menu'
  			  );
  			  			
	if($opt)
    foreach($opt as $key => $val)
	{
		$out_str.="<option value=\"".$key."\" ";//options values
		$out_str.=($key==$selected?" SELECTED ":"");//if selected
		$out_str.=">".$val."</option>";//options names
	}
	return $out_str;
}

/**
 * @return unknown
 * @param $selected unknown
 * @desc Enter description here...
 */
function build_tag_type_list($selected=0)
{

	$opt=array(

            	1	=> 'Content Box',
            	2	=> 'Site Menu',
            	3	=> 'Dynamic Box'
  			  );			
    foreach($opt as $key => $val)
	{
		$out_str.="<option value=\"".$key."\" ";//options values
		$out_str.=($key==$selected?" SELECTED ":"");//if selected
		$out_str.=">".$val."</option>";//options names
	}
	return $out_str;
}

/**
 * @return unknown
 * @param $selected unknown
 * @desc Enter description here...
 */
function build_link_target_list($selected='')
{

	$opt=array(

            	''  	 => 'Normal',
            	'_blank' => 'New Window',
            	'_top'	 => 'Curent Window'
  			  );			
    foreach($opt as $key => $val)
	{
		$out_str.="<option value=\"".$key."\" ";//options values
		$out_str.=($key==$selected?" SELECTED ":"");//if selected
		$out_str.=">".$val."</option>";//options names
	}
	return $out_str;
}


/**
 * @return unknown
 * @param $selected unknown
 * @desc Enter description here...
 */
function build_link_type_category_list($selected='')
{

	$opt=array(

            	'web_page_list2'  	 => 'CMS Web Pages Links',
  			  );			
    foreach($opt as $key => $val)
	{
		$out_str.="<option value=\"".$key."\" ";//options values
		$out_str.=($key==$selected?" SELECTED ":"");//if selected
		$out_str.=">".$val."</option>";//options names
	}
	return $out_str;
}


/**
 * @return unknown
 * @param $selected unknown
 * @desc Enter description here...
 */
function get_menu_template_type($selected)
{

	$opt=array(

            	1	=> 'Horizontal Menu',
            	2	=> 'Vertical Menu'
  			  );			

	return $opt[$selected];
}

/**
 * @return unknown
 * @param $selected unknown
 * @desc Enter description here...
 */
 
function build_web_page_templates_radiobuttons($selected)
{
	$db=new mysql_db;
	$db->query("select template_id, name from cms_template");
	while($db->move_next())
	{
		$opt[$db->f('template_id')] = $db->f('name');
		$det[$db->f('template_id')] = ' <a href="javascript:po3(\'index_blank.php?pag=template_details&template_id='.$db->f('template_id').'\')"><img src="../img/b_help.gif" width="11" height="11" border="0"></a>';
	}
	
	if($opt)
    foreach($opt as $key => $val)
	{
		$out_str.='<input name="template_id" type="radio" value="'.$key.'"';
		$out_str.=($key==$selected?" CHECKED ":"");//if selected
		$out_str.=">".$val.$det[$key]."<br>";//options names
	}
	return $out_str;

}


/**
 * @return unknown
 * @param $selected unknown
 * @desc Enter description here...
 */
function build_content_input_mode_list($selected)
{

	$opt=array(

            	1	=> 'WYSIWYG HTML Editor',
            	2	=> 'Html in Textarea',
            	3	=> 'Plain Text'
  			  );			
    foreach($opt as $key => $val)
	{
		$out_str.="<option value=\"".$key."\" ";//options values
		$out_str.=($key==$selected?" SELECTED ":"");//if selected
		$out_str.=">".$val."</option>";//options names
	}
	return $out_str;
}

/**
 * @return unknown
 * @param $selected unknown
 * @desc Enter description here...
 */
function get_content_input_mode_message($selected)
{

	$opt=array(

            	1	=> 'WYSIWYG Mode',
            	2	=> 'Html in Textarea Mode',
            	3	=> 'Plain Text Mode - New lines will be replaced with Line Breaks (&lt;BR&gt; tag)'
  			  );			

	return $opt[$selected];
}


/**
 * @return unknown
 * @param $mode - Selected mode to edit Content
 		  $field_name - the name of the field
 		  $default_data - preloaded data
 		  $params - array containing cols and rows number for textarea
 * @desc Enter description here...
 */
function get_content_input_area($mode, $default_data, $field_name, $params='')
{
	global $htmlEditor;
	$ret='';
	
	if(!$params)
	{
	    $params['cols']=50;
	    $params['rows']=8;
	}
	
	switch ($mode)
	{
		case 1:
		{
			if($htmlEditor == 2)
			{
				require_once('ktmlpro/includes/ktedit/activex.php');
				ob_start ();
				$KT_display = "Cut,Copy,Paste,Insert Image,Insert Table,Toggle Vis/Invis,Toggle WYSIWYG,Bold,Italic,Underline,Align Left,Align Center,Align Right,Align Justify,Background Color,Foreground Color,Undo,Redo,Bullet List,Numbered List,Indent,Outdent,HR,Font Type,Font Size,Insert Link,Clean Word,Style List,Heading List,Introspection,TableEdit";
				showActivex($field_name, 590, 350, false,$KT_display, "ktmlpro/", "../style_1.css", "../../../../img_gallery/", "../../../../file_uploads/",1, "English (UK)", -1, "english", "yes", "no");
				$ktml_editor = ob_get_contents();
				ob_end_clean();
	
				$ret='<input name="'.$field_name.'" type="hidden" id="'.$field_name.'" value="'.htmlspecialchars($default_data).'"> ';
				$ret.=$ktml_editor;
			}
			elseif ($htmlEditor == 1)
			{
				$ret='<script language="javascript" type="text/javascript" src="tinymce/tiny_mce.js"></script>';
			/*	$ret.='
						<script language="javascript" type="text/javascript">
							tinyMCE.init({
								mode : "exact",
								elements  : "'.$field_name.'",
								theme : "advanced",
								plugins : "table,advhr,advimage,advlink,insertdatetime,preview,flash,paste,noneditable,contextmenu,code",
								content_css : "../style_1.css"
							});
						</script>';*/
				$ret.='
<script language="javascript" type="text/javascript">
			tinyMCE.init({
		mode : "exact",
		elements  : "'.$field_name.'",
		theme : "advanced",
		plugins : "style,layer,table,advhr,advimage,advlink,emotions,iespell,insertdatetime,flash,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,mwfilemanager",
		theme_advanced_buttons1_add : "fontselect,fontsizeselect",
		theme_advanced_buttons2_add : "separator,insertdate,inserttime,preview,forecolor",
		theme_advanced_buttons2_add_before: "cut,copy,paste,pastetext,pasteword,separator,search,replace,separator",
		theme_advanced_buttons3_add_before : "tablecontrols,separator",
		theme_advanced_buttons3_add : "iespell,flash,advhr,separator,print,separator,fullscreen",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		content_css : "../style_1.css",
	    plugi2n_insertdate_dateFormat : "%Y-%m-%d",
	    plugi2n_insertdate_timeFormat : "%H:%M:%S",
		paste_use_dialog : false,
		theme_advanced_resizing : true,
		theme_advanced_resize_horizontal : false,
		paste_auto_cleanup_on_paste : true,
		paste_convert_headers_to_strong : false,
		paste_strip_class_attributes : "all",
		paste_remove_spans : false,
		paste_remove_styles : false,
		relative_urls : false,
		remove_script_host : false,	
		file_browser_callback : "mwwFileManager.browserCallback",
		document_base_url : ""
	});	</script>';
				$ret.='				
				<textarea id="'.$field_name.'" name="'.$field_name.'" style="width: 590px; height: 600px;">'.htmlspecialchars($default_data).'</textarea>';
				
			}
		}break;
		
		case 2:
		{
			$ret='<textarea name="'.$field_name.'" cols="'.$params['cols'].'" rows="'.$params['rows'].'" class="txtField">'.$default_data.'</textarea>';
		}break;
		
		case 3:
		{
			$ret='<textarea name="'.$field_name.'" cols="'.$params['cols'].'" rows="'.$params['rows'].'" class="txtField">'.$default_data.'</textarea>';
		}break;
	}
	return $ret;
}
//************Menu Links Dropdown Functions*************************************


function build_menu_link_list($menu_id, $selected, $excluded=0)
{
	global $menu_link_array;
/*	
	if(!$selected)
	{
		$selected=0;
	}
*/	
	$old_menu_link_array=build_menu_links_array($menu_id, $excluded);

	if($old_menu_link_array)
		$menu_link_array=sort_menu_links_array($old_menu_link_array);
		
	$out_str="";
	if($menu_link_array)
	foreach ($menu_link_array as $key=>$m_array)
	{
	    $out_str.="<option value=\"".$m_array['menu_link_id']."\" ";//options values
        $out_str.=($m_array['menu_link_id']==$selected?" SELECTED ":"");//if selected
        $out_str.=">".str_repeat("&nbsp;&nbsp;",$m_array['level']).$m_array['name']."</option>";//options names
	}
	
	return $out_str;
}

//build sorted and aranged array with menu links 
function build_menu_link_blank_array($menu_id, $excluded=0)
{
	global $menu_link_array;
	$old_menu_link_array=build_menu_links_array($menu_id, $excluded);

	if($old_menu_link_array)
		$menu_link_array=sort_menu_links_array($old_menu_link_array);
		
	
	return true;
}


function build_menu_links_array($menu_id, $excluded=0)
{
	
    $db=new mysql_db;
    $db->query("select cms_menu_link.name, cms_menu_link.menu_link_id, cms_menu_link.url, cms_menu_link.sort_order, cms_menu_link.target,
     				   cms_menu_submenu.parent_id from cms_menu_link 
        			   inner join cms_menu_submenu on cms_menu_submenu.menu_link_id = cms_menu_link.menu_link_id
        		       where cms_menu_link.menu_link_id!='".$excluded."' and cms_menu_submenu.menu_link_id!='".$excluded."' and cms_menu_link.menu_id='".$menu_id."'
        			   order by cms_menu_link.sort_order, cms_menu_link.name ");
        
    $out=array();
    while($db->move_next())
    {
        if($db->f('parent_id') != $db->f('menu_link_id'))
        {
        	$parent=$db->f('parent_id');
        }
        else
        {
        	$parent=0;
        }
        
        	$out['menu_link_id'][$db->f('menu_link_id')]=$db->f('menu_link_id');
			$out[$db->f('menu_link_id')]['menu_link_id']=$db->f('menu_link_id');
			$out[$db->f('menu_link_id')]['parent']=$parent;
			$out[$db->f('menu_link_id')]['url']=$db->f('url');
			$out[$db->f('menu_link_id')]['sort_order']=$db->f('sort_order');
			$out[$db->f('menu_link_id')]['target']=$db->f('target');
			$out[$db->f('menu_link_id')]['name']=$db->f('name');
    }
     
    return $out;
}


function sort_menu_links_array($m_links_array)
{
	
	$m_link_level='';
	$level=0;
	$excluded=0;
	$out='';
	$i=0;
	foreach ($m_links_array['menu_link_id'] as $menu_link_id => $men_l_id)
	{
		if($m_links_array[$menu_link_id]['parent']==0)
		{
			$out[$i]['menu_link_id']=$menu_link_id;
			$out[$i]['level']=$level;
			$out[$i]['name']=$m_links_array[$menu_link_id]['name'];
			$out[$i]['url']=$m_links_array[$menu_link_id]['url'];
			$out[$i]['sort_order']=$m_links_array[$menu_link_id]['sort_order'];
			$out[$i]['target']=$m_links_array[$menu_link_id]['target'];
			$out[$i]['parent']=$m_links_array[$menu_link_id]['parent'];
			$i++;

			if(submenu_exist($m_links_array, $menu_link_id, $excluded))
			{
				$submenu_exist=true;
			}
			else 
			{
				$submenu_exist=false;
			}
			
			$m_link_level[$level]=$level;
			$parent_id=$menu_link_id;
			
			$excluded.=",".$parent_id;
			
			while($submenu_exist)
			{
				$result=get_submenu_array($m_links_array, $parent_id, $excluded);
	            if($result)
	            {
	            	$m_link_level[$level]=$parent_id;
	            	$level++;
	            		
					$out[$i]['menu_link_id']=$result['out_id'];
					$out[$i]['level']=$level;
					$out[$i]['name']=$m_links_array[$result['out_id']]['name'];
					$out[$i]['url']=$m_links_array[$result['out_id']]['url'];
					$out[$i]['target']=$m_links_array[$result['out_id']]['target'];
					$out[$i]['sort_order']=$m_links_array[$result['out_id']]['sort_order'];
					$out[$i]['parent']=$m_links_array[$result['out_id']]['parent'];
					$i++;
			
	            	$parent_id=$result['next_parent'];
		           	$excluded.=",".$result['next_parent'];
	            }
		        else 
		        {
		           	$level--;
		           	$parent_id=$m_link_level[$level];
		        }
		        if($level < 0)
		        {
		        	$submenu_exist=false;
		           	$level=0;
		        }
			}		
				
		}
	}
	
    return $out;
}


function submenu_exist($m_links_array, $parent_id, $excluded=0)
{
	$exclude_keys = split(",",$excluded);
	foreach ($exclude_keys as $key=>$value)
	{
		$exclude[$value]=1;
	}

	foreach ($m_links_array['menu_link_id'] as $c_key => $c_id)
	{
        if(!$exclude[$c_key] && ($m_links_array[$c_id]['parent'] == $parent_id))
        {
        	
			return true;
        }
	}
	
	return false;
}


function get_submenu_array($m_links_array, $parent_id, $excluded=0)
{
	$out_id="";
	$next_parent="";
	$prev_parent="";
	$exclude_keys = split(",",$excluded);
	foreach ($exclude_keys as $key=>$value)
	{
		$exclude[$value]=1;
	}
	foreach ($m_links_array['menu_link_id'] as $c_key => $c_id)
	{
        if(!$exclude[$c_key] && ($m_links_array[$c_id]['parent'] == $parent_id))
        {
        	$return['out_id']=$c_id;
			$return['next_parent']=$c_id;
			$return['prev_parent']=$parent_id;
			return $return;
        }
	}
		
	return false;
}


?>