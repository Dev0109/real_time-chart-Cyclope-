<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => $glob['pag'].".html"));
$ft->define_dynamic('template_row','main');

$dbu = new mysql_db();
$i=0;
if(!is_numeric($glob['theme_id']))
{
	$colors = $dbu->query("SELECT theme_color.*,theme.name FROM theme_color
	INNER JOIN theme ON theme.theme_id = theme_color.theme_id AND theme.default=1
	ORDER BY theme_color_id ASC");
	
	while($colors->next()){
	
		$ft->assign(array(
			'INDEX' => $colors->f('theme_color_id'),
			'LABEL' => $i < 15  ? 'Top '.($i+1) : '[!L!]Others[!/L!]',
			'COLOR' => $colors->f('color'),
			'NAME' => '',
		));
		
		if(($i % 2)==0 )
		{
			$ft->assign('CLASS','even');
		}
		else
		{
			$ft->assign('CLASS','');
		}
		$ft->parse('TEMPLATE_ROW_OUT','.template_row');
		$i++;
	}
	
	$ft->assign(array(
		'PAGE_TITLE' => $ft->lookup('Add New Theme'),
		'RESET_LINK' => '#',
		'ACT' => 'colors-add',
		'HIDE_RESET_LINK' => 'hide',
	));

}
else 
{
	$colors = $dbu->query("SELECT theme_color.*,theme.name FROM theme_color
	INNER JOIN theme ON theme.theme_id = theme_color.theme_id AND theme.theme_id='".$glob['theme_id']."'
	ORDER BY theme_color_id ASC");	
	
	$i=0;
	
	while($colors->next()){
	
		$ft->assign(array(
			'INDEX' => $colors->f('theme_color_id'),
			'THEME_ID' => $colors->f('theme_id'),
			'COLOR' => $colors->f('color'),
			'NAME' => $colors->f('name'),
			'LABEL' => $i < 15  ? 'Top '.($i+1) : '[!L!]Others[!/L!]',
		));
		
		if(($i % 2)==0 )
		{
			$ft->assign('CLASS','even');
		}
		else
		{
			$ft->assign('CLASS','');
		}
		$ft->parse('TEMPLATE_ROW_OUT','.template_row');
		$i++;
	}
	
	$ft->assign(array(
		'PAGE_TITLE' => $ft->lookup('Edit Theme'),
		'HIDE_RESET_LINK' => '',
		'ACT' => 'colors-update',
		'RESET_LINK' => 'index.php?pag=colortheme&act=colors-setdefault&theme_id='.$glob['theme_id'],
	));
}

global $bottom_includes;
$bottom_includes.= '<script type="text/javascript" src="ui/colorthemes-ui.js"></script>';
$ft->assign('MESSAGE',get_error($glob['error']));
$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');
?>