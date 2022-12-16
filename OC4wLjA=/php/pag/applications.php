<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => $glob['pag'].".html"));
$ft->define_dynamic('template_row','main');
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

$categories = get_categories('s1',0);


$dbu->query("SELECT application.* FROM application
				ORDER BY description ASC");
$i=0;

while($dbu->move_next()){
	$cat_name = $ft->lookup('Uncategorised');
	$cat_id = 1;
	if(isset($categories[$dbu->f('application_id').'-0'])){
		$cat_id = $categories[$dbu->f('application_id').'-0']['category_id'];
		$cat_name = $ft->lookup($categories[$dbu->f('application_id').'-0']['category']);
	}
	$ft->assign(array(	
				'EDIT_LINK' => 'index.php?pag=application&application_id='.$dbu->f('application_id').'&offset='.$offset,
				'DELETE_LINK' => 'index.php?pag='.$glob['pag'].'&act=application-delete&application_id='.$dbu->f('application_id'), 
	));
	$ft->assign(array(
		'APPLICATION_ID' => $dbu->f('application_id'),
		'ALIAS' => $dbu->f('alias'),
		'NAME' => $dbu->f('name'),
		'DESCRIPTION' => $dbu->f('description'),
		'APPLICATION_TYPE' => get_application_type($dbu->f('application_type')),
		'APPLICATION_CATEGORY_ID' => $cat_name,
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
if($i==0){
	unset($ft);
	return get_error_message('There is no information to match your request.');
}

$site_meta_title=$meta_title;
$site_meta_keywords=$meta_keywords;
$site_meta_description=$meta_description;


$ft->assign('PAGE_TITLE',$ft->lookup('Applications'));
$ft->assign('MESSAGE',get_error($glob['error']));
$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');