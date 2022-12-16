<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/

$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => $glob['pag'].".html"));

$dbu = new mysql_db();
$page_title="My Account";
$next_function='member-account';

$query = $dbu->query("SELECT * FROM member WHERE member_id = '".$_SESSION[U_ID]."'");
if(!$query->next())
{
	unset($ft);
	return get_error_message('Invalid ID');
}
$ft->assign(array(
						'FIRST_NAME' => $query->gf('first_name'),			
						'LAST_NAME' => $query->gf('last_name'),			
						'PASSWORD' => $query->gf('password'),			
						'EMAIL' => $query->gf('email'),						
						'PASSWORD2' => $glob['password2'] ? $glob['password2'] :  $query->f('password'),
));

$ft->assign(array(
			'PAGE_TITLE' => $ft->lookup($page_title),
			'ACT'        => $next_function,
			'MEMBER_ID'=> $_SESSION[U_ID],
			'MESSAGE'    => get_error($glob['error'])
)); 

$site_meta_title=$meta_title;
$site_meta_keywords=$meta_keywords;
$site_meta_description=$meta_description;


$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');