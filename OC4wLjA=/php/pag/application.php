<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/

$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => "application.html"));

$page_title='Add Application';
$next_function ='application-add';

if(!is_numeric($glob['application_id']))
{
	
    $ft->assign(array(
							'APPLICATION_CATEGORY_ID' => build_category_tree($glob['application_category_id']),			
							'NAME' => $glob['name'],			
							'ALIAS' => $glob['alias'],			
							'DESCRIPTION' => $glob['description'],			
							'APPLICATION_TYPE' => build_application_type($glob['application_type']),			
							'COLOR' => $glob['color'],			
    ));
}
else
{
	$categories = $dbu->field("SELECT `application_category_id` FROM `application2category` WHERE `link_id` = ".$glob['application_id']." LIMIT 0 , 1");
    $page_title="Edit Application";
    $next_function='application-update';
    $dbu = new mysql_db();
    $dbu->query("SELECT * FROM application WHERE application_id = '".$glob['application_id']."'");
    if(!$dbu->move_next())
    {
    	unset($ft);
    	return get_error_message('Invalid ID');
    }
    if(isset($glob['application_category_id'])){
		$cat_id = $categories[$glob['application_id'].'-0']['category_id'];
	}elseif(isset($categories)){
		$cat_id = $categories;
	}else{
		$cat_id = 1;
	}
	
    $ft->assign(array(
				'APPLICATION_CATEGORY_ID' => build_category_tree($cat_id),
				'NAME' => $dbu->gf('name'),
				'ALIAS' => $dbu->gf('alias'),
				'DESCRIPTION' => $dbu->gf('description'),			
				'APPLICATION_TYPE' => build_application_type($dbu->gf('application_type'),$dbu->gf('alias')),			
				'COLOR' => $dbu->gf('color'),			
    ));

}

$ft->assign(array(
			'PAGE_TITLE' => $ft->lookup($page_title),
			'ACT'        => $next_function,
			'APPLICATION_ID'=> $glob['application_id'],
			'OFFSET' => $glob['offset'],
			'MESSAGE'    => get_error($glob['error']),
			'GOTO'    => $_REQUEST['goto'],
)); 

$site_meta_title=$meta_title;
$site_meta_keywords=$meta_keywords;
$site_meta_description=$meta_description;


$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');

?>