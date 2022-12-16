<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => $glob['pag'].".html"));
$ft->define_dynamic('template_row','main');

$dbu = new mysql_db();


// include_once(ADMIN_PATH.MODULE.'classes/cls_category.php');
// $cls_category = new category;
// $dbu->query("UPDATE `application_category` SET `category` = 'Uncategorised', `locked` = '1' WHERE `application_category`.`application_category_id` = 1");
	// $categorynames = $dbu->query("SELECT DISTINCT `category` FROM `application2category2productivity` ORDER BY `category` ASC");
	// while($categorynames->next()){
		// $ldr = array(
				// 'category' => $categorynames->f('category'),
				// 'locked' => 1,
		// );
		// if (!is_numeric($dbu->field("SELECT `application_category_id` FROM `application_category` WHERE `category` = '".$ldr['category']."'"))){
			// $cls_category->add($ldr);
		// }
	// }

$nodes = $dbu->query("SELECT * FROM application_category ORDER BY lft ASC");
$i=0;
$rights = array();
while($nodes->next()){
	$thelink = 'index.php?pag=category&application_category_id='.$dbu->f('application_category_id').'&offset='.$offset;
	$thedeletelink = 'index.php?pag='.$glob['pag'].'&act=category-delete&application_category_id='.$dbu->f('application_category_id').'&offset='.$offset;
	$extratext = '';
	if ($nodes->f('locked') == 1){
		$extratext = $ft->lookup("(Locked)");
		$thelink = '#';
		$thedeletelink = '#';
	}
	while (!empty($rights) && (end($rights) < $nodes->f('rgt'))){
		array_pop($rights);
	}
	$ft->assign(array(
		'SPACER'   => str_repeat('&nbsp;&nbsp;&nbsp;',count($rights)),
		'NAME'     =>  $nodes->f('category')=='Uncategorised?'? $ft->lookup('Uncategorised'):$ft->lookup($nodes->f('category')).' ' .$extratext,
		'LEVEL'    => count($rights)
	));
	$rights[] = $nodes->f('rgt'); 		
	
	$ft->assign(array(	
				'EDIT_LINK' => $thelink,
				'DELETE_LINK' => $thedeletelink,
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
if($i == 0){
	$ft->parse('TEMPLATE_ROW_OUT','.template_row');
	$ft->assign(array(
		'BEGIN_UNLESS' => '',
		'END_UNLESS' => ''
	));
}else{
	$ft->assign(array(
		'BEGIN_UNLESS' => '<!--',
		'END_UNLESS' => '-->'
	));
}

global $bottom_includes;
$bottom_includes.= '<script type="text/javascript" src="ui/categories-ui.js"></script>';
$ft->assign('PAGE_TITLE',$ft->lookup('Application Categories'));
$ft->assign('ADD_LINK','index.php?pag=category');
$ft->assign('MESSAGE',get_error($glob['error']));
$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');

?>