<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/ 
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => "login.html"));
$ft->assign('MESSAGE', get_error($glob['error']));

$page_title='Login Member';
$next_function ='auth-login';

$dbu = new mysql_db();

$ft->assign(array(
			'PAGE_TITLE' => $ft->lookup($page_title),
			'ACT'        => $next_function,
			'MEMBER_ID'=> $glob['member_id'],
			'MESSAGE'    => get_error($glob['error']),
			'SEND_TO'   => isset($glob['is_activation_page']) && $glob['is_activation_page'] ? 'activate.php' : 'index.php'
)); 
$site_meta_title=$meta_title;
$site_meta_keywords=$meta_keywords;
$site_meta_description=$meta_description;


$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');