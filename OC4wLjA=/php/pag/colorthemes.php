<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => $glob['pag'].".html"));
$ft->define_dynamic('template_row','main');
$ft->define_dynamic('color_row','template_row');
$l_r = ROW_PER_PAGE;

$dbu = new mysql_db();
if(($glob['ofs']) || (is_numeric($glob['ofs'])))
{
	$glob['offset']=$glob['ofs'];
}
if((!$glob['offset']) || (!is_numeric($glob['offset'])))
{
        $offset=0;
}
else
{
        $offset=$glob['offset'];
        $ft->assign('OFFSET',$glob['offset']);
}

$theme = $dbu->query("SELECT * FROM theme ORDER BY name ASC");

$i=0;
$max_rows=$theme->records_count();
$theme->move_to($offset*$l_r);

while($theme->next() && $i < $l_r){
	
	$ft->assign(array(
		'NAME' => $theme->f('name'),
		'SELECTED' => $theme->f('selected') ? '<img src="'.CURRENT_VERSION_FOLDER.'img/selected.png" />' : '',
		'EDIT_LINK' => $theme->f('default') == 1 ? '#' : 'index.php?pag=colortheme&theme_id='.$theme->f('theme_id').'&offset='.$offset,
		'DELETE_LINK' => $theme->f('default') == 1 ? '#' : 'index.php?pag='.$glob['pag'].'&act=colors-delete&theme_id='.$theme->f('theme_id').'&offset='.$offset, 
		'SELECT_LINK' => 'index.php?pag='.$glob['pag'].'&act=colors-select&theme_id='.$theme->f('theme_id').'&offset='.$offset,
		'HIDE_EDIT_LINK' =>  $theme->f('default') == 1 ? 'hide' : '',
		'HIDE_DELETE_LINK' =>  $theme->f('default') == 1 ? 'hide' : '',
		//'HIDE_SELECT_LINK' =>  $theme->f('selected') == 1 ? 'hide' : '',
	));
	
	$color = $dbu->query("SELECT * FROM theme_color WHERE theme_id='".$theme->f('theme_id')."' ORDER BY theme_color_id ASC");
	
	while ($color->next()) {
		$ft->assign(array(
			'COLOR' => $color->f('color')
		));
		
		$ft->parse('COLOR_ROW_OUT','.color_row');
	}
	
	if(($i % 2)==0 )
	{
		$ft->assign('CLASS','even');
	}
	else
	{
		$ft->assign('CLASS','');
	}
	$ft->parse('TEMPLATE_ROW_OUT','.template_row');
	$ft->clear('COLOR_ROW_OUT');
	$i++;
}


$start = $offset;
$end = ceil($max_rows/$l_r);
$link = '';
if($end<=5){
	//if there are less then 5 pages then we go about building a normal pagination
	for ($i = 0; $i < $end; $i++){
		$page = $i+1;	
		$class = $page == $start+1 ? 'class="current"' : '';
		$link .= <<<HTML
		<li {$class}><a href="index.php?pag={$glob['pag']}&offset={$i}{$arguments}">{$page}</a></li>
HTML;
	}
}else{
	if($start == 0 || $start <3){
		for ($i = 0; $i < 5; $i++){
			$page = $i+1;	
			$class = $page == $start+1 ? 'class="current"' : '';
			$link .= <<<HTML
			<li><a href="index.php?pag={$glob['pag']}&offset={$i}" {$class}>{$page}</a></li>
HTML;
		}
	}elseif ($start+2 >= $end-1){
		//we are close to the end
		for ($i = $end-5; $i < $end; $i++){
			$page = $i+1;	
			$class = $page == $start+1 ? 'class="current"' : '';
			$link .= <<<HTML
			<li><a href="index.php?pag={$glob['pag']}&offset={$i}" {$class}>{$page}</a></li>
HTML;
		}
	}else{
		for ($i = $start-2; $i < $start; $i++){
			$page = $i+1;	
			$link .= <<<HTML
			<li><a href="index.php?pag={$glob['pag']}&offset={$i}">{$page}</a></li>
HTML;
		}
		$page = $start+1;
		$class = $page == $start+1 ? 'class="current"' : '';
		$link .= <<<HTML
		<li><a href="index.php?pag={$glob['pag']}&offset={$start}" {$class}>{$page}</a></li>
HTML;
		for ($i = $start+1; $i < $start+3; $i++){
			$page = $i+1;	
			$link .= <<<HTML
			<li><a href="index.php?pag={$glob['pag']}&offset={$i}">{$page}</a></li>
HTML;
		}
	}
}
$ft->assign(array(
	'PAGG' => $link,
));

if($offset > 0)
{
     $ft->assign('BACKLINK',"index.php?pag=".$glob['pag']."&offset=".($offset-1).$arguments);
}
else
{
     $ft->assign('BACKLINK','#'); 
}
if($offset < $end-1)
{
     $ft->assign('NEXTLINK',"index.php?pag=".$glob['pag']."&offset=".($offset+1).$arguments);
}
else
{
     $ft->assign('NEXTLINK','#');
}
$ft->assign('LAST_LINK',"index.php?pag=".$glob['pag']."&offset=".($end-1).$arguments);

global $bottom_includes;
$bottom_includes.= '<script type="text/javascript" src="ui/colorthemeslist-ui.js"></script>';
$ft->assign('MESSAGE',get_error($glob['error']));
$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');
?>