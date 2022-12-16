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


$dbu->query("SELECT * FROM member WHERE department_id = 0 OR active = 2 ORDER BY username DESC");
$max_rows=$dbu->records_count();
$dbu->move_to($offset);
$i=0;
$types = array(1 => 'Administrator', 
																		  '1.5' => 'Tech Admin',
																		  2 => 'Manager',
																		  // 3 => 'Limited Manager',
																		  3 => 'DPO',
																		  4 => 'Employee');
while($dbu->move_next()){
$ft->assign(array(	
				'EDIT_LINK' => 'index.php?pag=member&mid='.$dbu->f('member_id'),
				'DELETE_LINK' => 'index.php?pag='.$glob['pag'].'&act=member-delete&member='.$dbu->f('member_id'), 
	));
	$ft->assign(array(
		'MEMBER_ID' => $dbu->f('member_id'),
		'EMAIL' => $dbu->f('email'),
		'PASSWORD' => $dbu->f('password'),
		'LOGON' => $dbu->f('logon'),
		'FIRST_NAME' => $dbu->f('first_name'),
		'LAST_NAME' => $dbu->f('last_name'),
		'USERNAME' => $dbu->f('username'),
		'ACCESS_LEVEL' => $types[$dbu->f('access_level')],

	));
	if($dbu->f('active'))
	{
		$ft->assign('ACTIVE_STATUS_LINK','index.php?pag='.$glob['pag'].'&member_id='.$dbu->f('member_id').'&act=member-deactivate&offset='.$offset.$arguments);
		$ft->assign('ACTIVE_ICON_STATUS','status_on.gif');
		$ft->assign('ACTIVE_ICON_STATUS_ALT','Click here to deactivate this item');
		$ft->assign('ACTIVE_JAVASCRIPT_MESSAGE','Are you sure you want to perform this action?');
	}
	else 
	{
		$ft->assign('ACTIVE_STATUS_LINK','index.php?pag='.$glob['pag'].'&member_id='.$dbu->f('member_id').'&act=member-activate&offset='.$offset.$arguments);
		$ft->assign('ACTIVE_ICON_STATUS','status_off.gif');
		$ft->assign('ACTIVE_ICON_STATUS_ALT','Click here to activate this item');
		$ft->assign('ACTIVE_JAVASCRIPT_MESSAGE','Are you sure you want to perform this action?');
	}					
	
	if(($i % 2)==0 )
	{
		$ft->assign('CLASS','');
	}
	else
	{
		$ft->assign('CLASS','even');
	}
	$ft->parse('TEMPLATE_ROW_OUT','.template_row');
	$i++;
}
if($i==0){
	unset($ft);
	return get_error_message('There is no information to match your request.<br /><a href="index.php?pag=member">Click here</a> to a new member');
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


$site_meta_title=$meta_title;
$site_meta_keywords=$meta_keywords;
$site_meta_description=$meta_description;

global $bottom_includes;
$bottom_includes.='<script type="text/javascript" src="ui/members-ui.js"></script>';

$ft->assign('PAGE_TITLE',$ft->lookup('List Members'));
$ft->assign('MESSAGE',get_error($glob['error']));
$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');