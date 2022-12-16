<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/

$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => "category.html"));

$page_title='Add Activity Category';
$next_function ='category-add';

if(!is_numeric($glob['application_category_id']))
{
	
    $ft->assign(array(
							'CATEGORY' => $glob['category'],
    ));
}
else
{
    $page_title="Edit Activity Category";
    $next_function='category-update';
    $dbu = new mysql_db();
    $dbu->query("SELECT * FROM application_category WHERE application_category_id = '".$glob['application_category_id']."'");
    if(!$dbu->move_next())
    {
    	unset($ft);
    	return get_error_message('Invalid ID');
    }
		
    $ft->assign(array(
				'CATEGORY' => $ft->lookup($dbu->gf('category')),
    ));

}

$ft->assign(array(
			'PAGE_TITLE' => $ft->lookup($page_title),
			'ACT'        => $next_function,
			'OFFSET'        => $glob['offset'],
			'APPLICATION_CATEGORY_ID'=> $glob['application_category_id'],
			'MESSAGE'    => get_error($glob['error'])
)); 

$site_meta_title=$meta_title;
$site_meta_keywords=$meta_keywords;
$site_meta_description=$meta_description;


$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');

?>