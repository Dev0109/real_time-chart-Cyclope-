<?php
function get_ltext($msg)
{
	global $lang_message;
	
	if($lang_message[$msg])
		return $lang_message[$msg];
	else
		return $msg;
}


function get_lang_links_list ($pag, $arg, $offset=0)
{
	$arg = str_replace('&l='.LANG, '', $arg);
	if($offset)
	{
		$arg.="&offest=".$offset;
	}
	$dbu=new mysql_db;
	$dbu->query("select * from lang order by `is_main` DESC, name ASC");
	$lang_links='<tr> 
    <td align="right"><div class="RedBorder"> 
        <div class="LangMenu">';
	$i=0;
	while($dbu->move_next())
	{
		if(LANG == $dbu->f('lang_id'))
		{
			$class='TopMenuLinkSelected';
		}
		else 
		{
			$class='RedBoldLink';
		}
		if($i==0)
		{
			$lang_links.='<a href="index.php?pag='.$pag.'&l='.$dbu->f('lang_id').$arg.'" class="'.$class.'">'.$dbu->f('name').'</a>';
		}
		else 
		{
			$lang_links.=' | <a href="index.php?pag='.$pag.'&l='.$dbu->f('lang_id').$arg.'" class="'.$class.'">'.$dbu->f('name').'</a>';
		}
		
		$i++;
	}
	
	$lang_links.=' &nbsp;&nbsp;
		</div> 
      </div></td> 
  </tr> ';

	return $lang_links;
}

function get_lang_links ($glob, $id)
{
	$dbu=new mysql_db;
	$dbu->query("select * from lang order by `is_main` DESC, name ASC");
	$lang_links='<tr> 
    <td align="right"><div class="RedBorder"> 
        <div class="LangMenu">';
	$i=0;
	while($dbu->move_next())
	{
		if(LANG == $dbu->f('lang_id'))
		{
			$class='TopMenuLinkSelected';
		}
		else 
		{
			$class='RedBoldLink';
		}
		if($i==0)
		{
			$lang_links.='<a href="index.php?pag='.$glob['pag'].'&element_id='.$id.'&l='.$dbu->f('lang_id').'" class="'.$class.'">'.$dbu->f('name').'</a>';
		}
		else 
		{
			$lang_links.=' | <a href="index.php?pag='.$glob['pag'].'&element_id='.$id.'&l='.$dbu->f('lang_id').'" class="'.$class.'">'.$dbu->f('name').'</a>';
		}
		
		$i++;
	}
	
	$lang_links.=' &nbsp;&nbsp;
		</div> 
      </div></td> 
  </tr> ';

	return $lang_links;
}

/**
 * @return unknown
 * @param $selected unknown
 * @desc All Languages Drop...
 */
function build_languages_list($selected)
{
        $db=new mysql_db;
        $db->query("SELECT * FROM language ORDER BY name ASC");
        $out_str="";
        
        while($db->move_next()){
                $out_str.="<option value=\"".$db->f('language_id')."\" ";//options values
                $out_str.=($db->f('language_id')==$selected?" SELECTED ":"");//if selected
                $out_str.=">".$db->f('name')."</option>";//options names
        }
        return $out_str;
}

function build_character_sets_list($selected)
{
        $db=new mysql_db;
        $db->query("SELECT * FROM character_set ORDER BY name ASC");
        $out_str="";
        
        while($db->move_next()){
                $out_str.="<option value=\"".$db->f('character_set_id')."\" ";//options values
                $out_str.=($db->f('character_set_id')==$selected?" SELECTED ":"");//if selected
                $out_str.=">".$db->f('display_name')."</option>";//options names
        }
        return $out_str;
}

/**
 * @return unknown
 * @param $link unknown
 * @desc Enter description here...
 */
function get_link ($link)
{
	global $rewrite_url;
	if($rewrite_url != 1)
	{
		return $link;
	}
	else 
	{
		$new_link='';
		if((substr($link, 0, 13) != 'index.php?pag') || ($link=='index.php'))
		{
			return $link;
		}
		elseif (strstr($link, "_")) 
		{
			return $link;
		}
		else
		{
			
			$new_link = str_replace('index.php?', '', $link);
			$new_link = str_replace('/','-', $new_link);
			$new_link = str_replace('&','_', $new_link);
			$new_link = str_replace('=','_', $new_link);
			return $new_link;
		}
	}
}


/**
 * @return unknown
 * @param $selected unknown
 * @desc Enter description here...
 */
function bulid_simple_dropdown($opt, $selected=0)
{
	$array = is_array($selected);
	if($opt)
    foreach($opt as $key => $val) 
	{
		$out_str.="<option value=\"".$key."\" ";//options values
		if($array){
			$out_str.=(in_array($key,$selected) ? " selected=\"selected\" ":"");//if selected		
		}else{
			$out_str.=($key==$selected?" selected=\"selected\" ":"");//if selected
		}
		$out_str.=">".$val."</option>";//options names
	}
	return $out_str;
}

/**
 * @return unknown
 * @param $selected unknown
 * @desc Enter description here...
 */
function page_redirect ($page)
{
echo '
<script language="javascript">
<!-- 

location.replace("'.$page.'");

-->
</script>
';
}

/**
 * @return unknown
 * @param $selected unknown
 * @desc Enter description here...
 */
function get_safe_text($text)
{
	if($text)
	{
		$ret_text='';
		
	    $text=str_replace("\n", "<br> ", $text);
		$tmp_text=explode(" ",$text);
	    
	    
	    foreach ($tmp_text as $key => $value)
	    {
	    	$lenght=strlen($tmp_text[$key]);
	    	
	    	if($lenght > 40)
	    	{
	    		$split_number=(int)($lenght/40);
	    		for ($i=1; $i<=$split_number; $i++)
	    		{
	    			$replace_number=($i*40);
	    			$tmp_text[$key][$replace_number]=' ';
	    		}
	    		
	    	}
	    	$ret_text.=$tmp_text[$key]." ";
	    }
	
		return $ret_text;
	}
	else 
	{
	    return ' ';
	}	
}


function get_sys_message($name)
{
$_db=new mysql_db();
$_db->query("select * from sys_message where name='".$name."'");
$_db->move_next();
$msg['text']=$_db->f('text');
$msg['from_email']=$_db->f('from_email');
$msg['from_name']=$_db->f('from_name');
$msg['subject']=$_db->f('subject');
return $msg;

}

/**
 * @return unknown
 * @param $sel_year unknown
 * @desc Enter description here...
 */
function build_year_list($start_year, $end_year, $sel_year=0)
        {
        		global $vars;
                 for ($i=$start_year;$i<=$end_year;$i++)
                         {
                                 $ret.="<Option ";
                                if($i==$sel_year)
                                        {
                                                $ret.="selected";
                                        }
                                $ret.=" value='$i'>$i</option>\n";
                         }
                 return $ret;
        }


/**
 * @return unknown
 * @param $sel_year unknown
 * @desc Enter description here...
 */

function get_error_message($error_msg)
{
global $site_meta_title,$meta_title,$site_meta_keywords,$meta_keywords,$site_meta_description,$meta_description; 

	
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define( array(main => "error_msg.html"));

$ft->assign('ERROR_MSG',$error_msg);
$site_meta_title=$meta_title;
$site_meta_keywords=$meta_keywords;
$site_meta_description=$meta_description; 

$ft->parse('content','main');
return $ft->fetch('content');

}

/**
 * @return unknown
 * @param $sel_year unknown
 * @desc Enter description here...
 */

function get_pagination_dd($selected_offset, $num_rows, $row_per_page, $glob)
{
	$l = new LanguageParser();
	$excluded['o']=1;
	$excluded['ofs']=1;
	$excluded['offset']=1;
	$excluded['act']=1;
	$excluded['error']=1;
	
    if($num_rows>0)
    {
        $pages=ceil($num_rows/$row_per_page);
    }
    else
    {
        $pages=0;
    }
    $args="";
    foreach($glob as $key => $value)
    { 
    	if(!$excluded[$key])
    	{
    		$args.=' <input type="hidden" name="'.$key.'" value="'.$value.'"> ';
    	}
    }
    
    
    if($pages <=1)
    {
        return  "<img src=\"img/spacer.gif\" width=\"35\" height=\"1\">";
    }
    
    $out_str=$args." ". $l->lookup('Go To')." <SELECT name=\"ofs\" onChange=\"form.submit();\" class=txtform>";
    $ofs=0;
    for($i=1;$i<=$pages;$i++)
    {
        $out_str.="<option value=$ofs ";
        $out_str.=($ofs == $selected_offset ? " SELECTED " : "");
		$out_str.=">".'Page'." ".$i."</option>";//option name
		$ofs+=$row_per_page;
    }
    $out_str.="</SELECT>";
    return $out_str;
}



/**
 * @return unknown
 * @param $sel_day unknown
 * @desc Enter description here...
 */
function build_day_list($sel_day=0)
	{
		 global $vars;
		 for ($i=1;$i<=(31);$i++)
		 	{
		 		$ret.="<Option ";
				if($i==$sel_day)
					{
						$ret.="selected";
					}
				$ret.=" value='$i'>$i</option>\n";
		 	}
		 return $ret;
	}		

/**
 * @return unknown
 * @param $selected unknown
 * @desc Enter description here...
 */
function build_month_list($selected=0)
{

	$opt=array(

            	1	=> 'January',
            	2	=> 'February',
            	3	=> 'March',
            	4	=> 'April',
            	5	=> 'May',
            	6	=> 'June', 
            	7	=> 'July', 
            	8	=> 'August',
            	9	=> 'September',
            	10	=> 'October', 
            	11	=> 'November',
            	12	=> 'December'
  			  );			
	foreach($opt as $key => $val)
	{
		$out_str.="<option value=\"".$key."\" ";//options values
		$out_str.=($key==$selected?" SELECTED ":"");//if selected
		$out_str.=">".$val."</option>";//options names
	}
	return $out_str;
}
require(ADMIN_PATH."misc/cmslib.php");
/**
 * @return unknown
 * @param $selected unknown
 * @desc Enter description here...
 */
function get_month($selected)
{

	$opt=array(

            	1	=> 'January',
            	2	=> 'February',
            	3	=> 'March',
            	4	=> 'April',
            	5	=> 'May',
            	6	=> 'June', 
            	7	=> 'July', 
            	8	=> 'August',
            	9	=> 'September',
            	10	=> 'October', 
            	11	=> 'November',
            	12	=> 'December'
  			  );	
  	$out_str=$opt[$selected];		  		
	return $out_str;
}


/**
 * @return unknown
 * @param $selected unknown
 * @desc Enter description here...
 */
function build_yesno_list($selected)
{
        $out_str="";
        $opt=array(
                    "No"           => '0',
                    "Yes"           => '1'
                            );
    foreach ($opt as $key=>$val)
        {
                $out_str.="<option value=\"".$val."\" ";//options values
                $out_str.=($val==$selected?" SELECTED ":"");//if selected
                $out_str.=">".$key."</option>";//options names
        }
        return $out_str;
}

/**
 * @return unknown
 * @param $selected unknown
 * @desc Enter description here...
 */
function build_numbers_list($start_num, $end_num, $selected)
{
		$out_str="";
        for ($i=$start_num;$i<=$end_num;$i++)
        {
                $out_str.="<option value=\"".$i."\" ";//options values
                $out_str.=($i==$selected?" selected=\"selected\" ":"");//if selected
                $out_str.=">".$i."</option>";//options names
        }
        return $out_str;
}


function build_numbers_list_offset($start_num, $end_num,$offset, $selected)
{
        $out_str="";
    for ($i=$start_num;$i<=$end_num;$i+= $offset)
        {
                $out_str.="<option value=\"".$i."\" ";//options values
                $out_str.=($i == $selected?" SELECTED ":"");//if selected
                $out_str.=">".($i == 0 ? '00' : $i)."</option>";//options names
        }
        return $out_str;
}


function build_numbers_list_nth($start_num, $end_num,$nth, $selected)
{
        $out_str="";
    for ($i=$start_num;$i<=$end_num;$i++)
        {
			if ($i % $nth === 0){
                $out_str.="<option value=\"".$i."\" ";//options values
                $out_str.=($i == $selected?" SELECTED ":"");//if selected
                $out_str.=">".($i == 0 ? '00' : $i)."</option>";//options names
			}
        }
        return $out_str;
}

/**
 * Compress output and remove any extra whitespaces
 *
 * @param string $buffer
 * @return string
 */
function compress_output($buffer){
    $search = array(
        '/\>[^\S ]+/s', //strip whitespaces after tags, except space
        '/[^\S ]+\</s', //strip whitespaces before tags, except space
        '/(\s)+/s',  // shorten multiple whitespace sequences
        '/\>(\s)+\</'
        );
    $replace = array(
        '>',
        '<',
        '\\1',
        '><'
        );
  $buffer = preg_replace($search, $replace, $buffer);
  return $buffer;
}

/**
 * @return string for dropdown
 * @param $id string
 * @desc The list of countries
 */
function build_country_list($selected)
{
        $db=new mysql_db;
        $db->query("SELECT * FROM country ORDER BY is_main DESC, name");
        $out_str="";
        while($db->move_next()){
                $out_str.="<option value=\"".$db->f('name')."\" ";//options values
                $out_str.=($db->f('name')==$selected?" SELECTED ":"");//if selected
                $out_str.=">".$db->f('name')."</option>";//options names
        }
        return $out_str;
}

function build_timezone_list($country,$selected)
{
	$db=new mysql_db();
	$db->query("SELECT iso31661 FROM country WHERE name ='".$country."'");
	$db->move_next();
	$timezone_identifiers = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $db->f('iso31661'));
	$i = 0;
	foreach ($timezone_identifiers as $timezone_identifier){
		$i++;
		$timezone = new DateTimeZone($timezone_identifier);
		$timezone_time = new DateTime('now', $timezone);
		$offset = $timezone->getOffset($timezone_time) / 3600;
		$out_str.="<option value=\"".$timezone_identifier."\" ";
        $out_str.=($timezone_identifier == $selected?" SELECTED ":"");
        $out_str.=">"."( GMT" . ($offset < 0 ? $offset : "+".$offset).' ), '.str_replace('_',' ',substr(strstr($timezone_identifier, '/'),1))."</option>";
	}
	return $out_str;
}
/**
 * @return string for dropdown
 * @param $id string
 * @desc The list of States
 */
function build_state_list($selected, $country='')
{
        $db=new mysql_db;        	
        if(!$country)
        {
        	$country=MAIN_COUNTRY;
        }
        $db->query("select distinct state.name as state_name from state 
        			inner join country on state.country_id=country.country_id
        			where country.name='".$country."' order by state.name");
        $out_str="";
        while($db->move_next()){
                $out_str.="<option value=\"".$db->f('state_name')."\" ";//options values
                $out_str.=($db->f('state_name')==$selected?" SELECTED ":"");//if selected
                $out_str.=">".$db->f('state_name')."</option>";//options names
        }
        return $out_str;
}  

/**
 * Redirect user to pag with the use of a header
 *
 * @param string $pag
 */
function redirect_with_header($pag){
	if($pag == '')
	{
		return '';
	}
	header('Location: index.php?pag='.$pag);
	exit(0);
}

function get_data($table_name='', $selected = '', $field ='name', $opt = array()){
	if($table_name == ''){
		return array();
	}
	$dbu = new mysql_db();
	$id = isset($opt['id']) ? $opt['id'] : $table_name.'_id';
	$cond = isset($opt['cond']) ? $opt['cond'] : '';
	$selfield = isset($opt['field']) ? $opt['field'] : $field;
	
	$dbu->query('SELECT '.$id.','.$field.' FROM '.$table_name.' '.$cond);
	$ret_val = array();
	while ($dbu->move_next()){
		$ret_val[$dbu->f($id)] = $dbu->f($selfield);
	}
	return $selected!='' ? $ret_val[$selected] : $ret_val;
}

function build_data_dd($table_name,$selected='',$field='name',$opt = array()){
	return bulid_simple_dropdown(get_data($table_name,'',$field,$opt),$selected);
	
}


function build_data_dd2($table_name,$selected=''){
	$options = get_data($table_name);
	$out_html ='';
	foreach ($options as $key => $val){
		if(is_array($selected)){
			if(in_array($key,$selected)){
				$sel = 'selected="selected"';
			}else{
				$sel = '';
			}
		}else{
			$sel = $key == $selected ? 'selected="selected"' : '';			
		}
		$out_html.=<<<HTML
		<option value="{$key}" {$sel} >{$val}</option>
HTML;
	}
	return $out_html;	
}

/**
 * Return a dropdown with am/pm
 *
 * @param integer $sel
 * @return string
 */
function build_ampm_list($sel)
{
	return bulid_simple_dropdown(array(1=>'AM',2=>'PM'),$sel);
} 

function get_error($message = '', $style = ''){
	
	global $glob;
	
	$message = $message ? $message : (isset($glob['error']) ? $glob['error']: '');
	$lines = explode('<br>',$message);
	$lang = new LanguageParser();
	foreach ($lines as $pos => $line){
	// if (ereg('[^0-9]', $line)){
		// $lines[$pos] = $line;
	// } else {
		$lines[$pos] = $lang->lookup($line);
	// }		
	}
	$message = join('<br />',$lines);
	
	$class = $style ? $style : ( $glob['success'] ? 'success' : 'error' );
	
	$out_str = '';
	if(strlen($message) !=0){
		$out_str = '<div class="'.$class.'">'.$message.'</div>';
	}
	return $out_str;
}

function build_optgroup($label, $selected ,$children = array()){
	return '<optgroup label="'.$label.'" rel="group">'.bulid_simple_dropdown($children,$selected).'</optgroup>';
}

function parseCMSTag($sTag)
{
	$cms_tags = get_cms_tags_from_content($sTag);
	$tag_list = $sTag;
	if($cms_tags)
	{
		foreach ($cms_tags as $key => $cms_tag_params)
		{
			$tag_list=str_replace($cms_tag_params['tag'], get_cms_tag_content($cms_tag_params), $tag_list);
		}
	}
	return $tag_list;
}

function build_currency_list($selected=1)
{
	$opt=array(
            	1	=> 'EUR', 
            	2	=> 'AUD',
            	3	=> 'CAD',
            	4	=> 'CHF',
            	6	=> 'CZK',
            	6	=> 'EEK',
            	7	=> 'HRK', 
            	8	=> 'HUF',
            	9	=> 'INR',
            	10	=> 'LTL', 
            	11	=> 'LVL',
            	12	=> 'MXN',
            	13	=> 'PLN',
            	14	=> 'RON',
            	15	=> 'RUB',
            	16	=> 'USD',
            	17	=> 'GBP',
  			  );		
			//   var_dump("test", $opt);	
    // while (list ($key, $val) = each ($opt)) 
	foreach($opt as $key => $val) 
	{

		$out_str.="<option value=\"".$key."\" ";//options values
		$out_str.=($key==$selected?" SELECTED ":" ");//if selected
		$out_str.=">".$val."</option>";//options names
	}
	
	return $out_str;
}

function get_currency($selected)
{
	$opt=array(
				1	=> 'EUR', 
            	2	=> 'AUD',
            	3	=> 'CAD',
            	4	=> 'CHF',
            	6	=> 'CZK',
            	6	=> 'EEK',
            	7	=> 'HRK', 
            	8	=> 'HUF',
            	9	=> 'INR',
            	10	=> 'LTL', 
            	11	=> 'LVL',
            	12	=> 'MXN',
            	13	=> 'PLN',
            	14	=> 'RON',
            	15	=> 'RUB',
            	16	=> 'USD',
  			  );	
  			  
  	return $opt[$selected];
}

function get_pdf_error($message=''){
	$out_string = '<table cellspacing="0" class="list" cellpadding="4" ><tr><td class="th"></td></tr><tr>
		<td >'.$message.'</td></tr></table>';
	return $out_string;
}