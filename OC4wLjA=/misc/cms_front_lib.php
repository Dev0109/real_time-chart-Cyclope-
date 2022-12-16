<?php
/**
 * @return unknown
 * @param $cms_tag_params array
 * @desc This function gets the array with CMS tag informations and returns the replacement object
  */

function get_cms_tag_content($cms_tag_params)
{
	global $glob;
	switch ($cms_tag_params['type'])
	{
		case 1:
		{
			$_db=new mysql_db();
			$_db->query("select * from cms_content_box where content_box_id='".$cms_tag_params['id']."'");
			$_db->move_next();
			//$cbox_parms['date']=date("m / d / Y", $dbu->f('date'));
			$cbox_parms['content_template_id']=$_db->f('content_template_id');
			$cbox_parms['title']=$_db->f('title');
			$cbox_parms['subtitle']=$_db->f('subtitle');
			$cbox_parms['headline']=$_db->f('headline');
			$cbox_parms['content']=$_db->f('content');
			$cbox_parms['mode']=$_db->f('mode');
			$ret=get_content_article($cbox_parms);
			if($ret)
			{
				return $ret;
			}
			else
			{
				return '';
			}
		}
		
		case 2:
		{
			global $bottom_includes, $menu_link_array;
			$_db=new mysql_db();
			$_db->query("select * from cms_menu where menu_id='".$cms_tag_params['id']."'");
			$_db->move_next();
			$cms_menu_id=$_db->f('menu_id');
			
			if($_db->f('tag_v')==$cms_tag_params['tag'])
			{
				// load the horizontal version of the menu
				$cms_menu=$cms_menu_id."_v";
				if(is_file(CURRENT_VERSION_FOLDER."php/".$_db->f('template_file_v')))
				{
					include(CURRENT_VERSION_FOLDER."php/".$_db->f('template_file_v'));
				}
				$ret='';

				$menu_array=build_cmsobject_menu_links_array($cms_menu_id);
				$mtpl['dynamic_menu_start']=str_replace('[!MENU_NAME!]', $_db->f('name'), $mtpl['dynamic_menu_start']);
				$d_menu=new dynamic_menu($menu_array, $mtpl);
				$ret.= $d_menu->build_menu();
				return $ret;				
			}
			elseif ($_db->f('tag_h')==$cms_tag_params['tag'])
			{
				// load the horizontal version of the menu
				$cms_menu=$cms_menu_id."_h";
				if(is_file(CURRENT_VERSION_FOLDER."php/".$_db->f('template_file_h')))
				{
					include(CURRENT_VERSION_FOLDER."php/".$_db->f('template_file_h'));
				}
				$ret='';

				$menu_array=build_cmsobject_menu_links_array($cms_menu_id);
				$mtpl['dynamic_menu_start']=str_replace('[!MENU_NAME!]', $_db->f('name'), $mtpl['dynamic_menu_start']);
				$d_menu=new dynamic_menu($menu_array, $mtpl);
				$ret.= $d_menu->build_menu();
				return $ret;				
			}
			
			if($cms_tag_params['file_name'] && is_file("php/".$cms_tag_params['file_name']))
			{
				$ret=include(CURRENT_VERSION_FOLDER."php/".$cms_tag_params['file_name']);
			}
			if($ret)
			{
				return $ret;
			}
			else
			{
				return '';
			}
		}
		
		case 3:
		{
			if($cms_tag_params['file_name'] && is_file(CURRENT_VERSION_FOLDER."php/".$cms_tag_params['file_name']))
			{
				$ret=include(CURRENT_VERSION_FOLDER."php/".$cms_tag_params['file_name']);
			}
			if($ret)
			{
				return $ret;
			}
			else
			{
				return '';
			}
		}
	}
	return '';
}

/**
 * @return unknown
 * @param $selected unknown
 * @desc This function builds the array used to build the CMS object menu
 */
function build_cmsobject_menu_links_array($cms_menu_id)
{
	global $menu_link_array;
	build_menu_link_blank_array($cms_menu_id);
	if($menu_link_array)
    foreach($menu_link_array as $key => $tmp_array)
    {
    	$out['key'][$tmp_array['menu_link_id']]=$tmp_array['menu_link_id'];
    	$out[$tmp_array['menu_link_id']]['parent']=$tmp_array['parent'];
    	$out[$tmp_array['menu_link_id']]['url']=get_link($tmp_array['url']);
    	$out[$tmp_array['menu_link_id']]['text']=$tmp_array['name'];
    	$out[$tmp_array['menu_link_id']]['target']=$tmp_array['target'];
    	unset($tmp_array);
    }    
   	
/*	$out[$db->f('menu_link_id')]['parent']=$parent;
	$out[$db->f('menu_link_id')]['url']="index.php?pag=category_page&menu_link_id=".$db->f('menu_link_id');
	$out[$db->f('menu_link_id')]['text']=$db->f('name');
*/
	return $out;
        
}
	
/**
 * @return unknown
 * @param $str string
 * @desc This function gets the string with the complete article data and returns 
 *       all the cms tags to be replaced with cms objects in an array. 
 *       The array also contains information about the tag type and the object ID or file to be included
 */

function get_cms_tags_from_content($str)
{
	$_db=new mysql_db();
	preg_match_all ("/\[![a-z0-9A-Z!_-]+!\]/", $str, $out, PREG_PATTERN_ORDER);
	foreach ($out[0] as $key => $tmp_tag)
	{
		$out_tag_array[$key]['tag']=$tmp_tag;
		$_db->query("select * from cms_tag_library where tag='".$tmp_tag."'");
		$_db->move_next();
		$out_tag_array[$key]['type']=$_db->f('type');
		$out_tag_array[$key]['id']=$_db->f('id');
		$out_tag_array[$key]['file_name']=$_db->f('file_name');
	}
	
	return $out_tag_array;
}

/**
 * @return unknown
 * @param $article_parms array
 * @desc This function gets an array with the complete article data and returns the html to be published.
 */

function get_content_article($article_parms)
{
	if($article_parms['mode']==3)
	{
		$article_parms['content']=nl2br($article_parms['content']);
		$article_parms['headline']=nl2br($article_parms['headline']);
	}
	
	if($article_parms['content_template_id'])
	{
		$_db=new mysql_db();
		$_db->query("select file_name from cms_content_template where content_template_id='".$article_parms['content_template_id']."'");
		$_db->move_next();
		$content_template_file_name=$_db->f('file_name');
		$tmp_ft=new ft("");
		$tmp_ft->define(array('main'=>$content_template_file_name));
		$tmp_ft->assign('DATE', $article_parms['date']);
		$tmp_ft->assign('TITLE', $article_parms['title']);
		$tmp_ft->assign('SUBTITLE', $article_parms['subtitle']);
		$tmp_ft->assign('HEADLINE', $article_parms['headline']);
		$tmp_ft->assign('CONTENT', $article_parms['content']);
		$tmp_ft->assign('BANNER', $article_parms['banner']);
		$tmp_ft->parse('ALL','main');
		$ret = $tmp_ft->fetch('ALL');
		unset($tmp_ft);
		return $ret;

	}
	else 
	{
		return $article_parms['content'];
	}
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * @return unknown
 * @param $str string
 * @desc Enter description here...
 */
function str_to_filename($str)
{
	$allowed['1']=1;
	$allowed['2']=1;
	$allowed['3']=1;
	$allowed['4']=1;
	$allowed['5']=1;
	$allowed['6']=1;
	$allowed['7']=1;
	$allowed['8']=1;
	$allowed['9']=1;
	$allowed['0']=1;
	$allowed['q']=1;
	$allowed['w']=1;
	$allowed['e']=1;
	$allowed['r']=1;
	$allowed['t']=1;
	$allowed['y']=1;
	$allowed['u']=1;
	$allowed['i']=1;
	$allowed['o']=1;
	$allowed['p']=1;
	$allowed['a']=1;
	$allowed['s']=1;
	$allowed['d']=1;
	$allowed['f']=1;
	$allowed['g']=1;
	$allowed['h']=1;
	$allowed['j']=1;
	$allowed['k']=1;
	$allowed['l']=1;
	$allowed['z']=1;
	$allowed['x']=1;
	$allowed['c']=1;
	$allowed['v']=1;
	$allowed['b']=1;
	$allowed['n']=1;
	$allowed['m']=1;
	$allowed[' ']=1;
	$str = strtolower($str);
	$newstr='';
	for($i=0; $i<=strlen($str); $i++)
	{
		if($allowed[$str[$i]])
		{
			$newstr.=$str[$i];
		}
	}
	$str = $newstr;
	$out_str='';
	$out_str=str_replace(" ", "-", $str);
	$out_str=str_replace("---", "-", $out_str);
	$out_str=str_replace("--", "-", $out_str);
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
function build_menu_css_list($selected=0)
{
	$db=new mysql_db;
	$db->query("select menu_template_css_id, name, file_name from cms_menu_template_css");
	while($db->move_next())
	{
		$opt[$db->f('file_name')] = $db->f('name');
	}

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
			require_once('ktmlpro/includes/ktedit/activex.php');
			ob_start ();
			$KT_display = "Cut,Copy,Paste,Insert Image,Insert Table,Toggle Vis/Invis,Toggle WYSIWYG,Bold,Italic,Underline,Align Left,Align Center,Align Right,Align Justify,Background Color,Foreground Color,Undo,Redo,Bullet List,Numbered List,Indent,Outdent,HR,Font Type,Font Size,Insert Link,Clean Word,Style List,Heading List,Introspection,TableEdit";
			showActivex($field_name, 590, 350, false,$KT_display, "ktmlpro/", "../style_1.css", "../../../../img_gallery/", "../../../../file_uploads/",1, "English (UK)", -1, "english", "yes", "no");
			$ktml_editor = ob_get_contents();
			ob_end_clean();

			$ret='<input name="'.$field_name.'" type="hidden" id="'.$field_name.'" value="'.htmlspecialchars($default_data).'"> ';
			$ret.=$ktml_editor;
			
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

