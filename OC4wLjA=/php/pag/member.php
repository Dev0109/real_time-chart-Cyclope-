<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/

$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => "member.html"));

$page_title='Add Member';
$next_function ='member-add';

$dbu = new mysql_db();
$adusers_db = $dbu->query("SELECT `logon` FROM `member` WHERE `ad` = 1");
if ($adusers_db->next()) {
	$ft->assign(array(
							'ADUSERS' => build_aduser_dd(),
							'ADISPLAY' => '',
	));
} else {
	$ft->assign(array(
							'ADISPLAY' => ' style="display:none;" ',
	));
}

if(!is_numeric($glob['mid']))
{
	$glob['active'] = 2;
    $ft->assign(array(
							'FIRST_NAME' => $glob['first_name'],			
							'LAST_NAME' => $glob['last_name'],			
							'USERNAME' => $glob['username'],			
							'EMAIL' => $glob['email'],			
							'PASSWORD' => $glob['password'],			
							'PASSWORD2' => $glob['password2'],			
							'DESCRIPTION' => $glob['description'],			
							'ACCESS_LEVEL' => bulid_simple_dropdown(array(1 => 'Administrator', 
																		  '1.5' => 'Tech Admin',
																		  2 => 'Manager',
																		  // 3 => 'Limited Manager',
																		  3 => 'DPO',
																		  4 => 'Employee'),$glob['access_level']),			
							'ACTIVE' => $glob['active'] ? 'checked="checked"' : '' ,
							'ALIAS' => $glob['alias'] ? 'checked="checked"' : '' ,
							'PREFILLED' => $glob['prefilled'] ? 1 : 0 ,
    ));
    switch ($glob['access_level']){
    	case 1:
    	case 1.5:
    		$ft->assign('HAS_MONITORED_USERS','hide');
    		
    		global $bottom_includes;
    		
$bottom_includes.= <<<JS
<script type="text/javascript">
$(function(){
	$('#enableAlias').addClass('hide');
});
</script>
JS;
    		break;
    	case 2:
    	case 3:
    		$selected = '';
			if(is_array($glob['monitored_group']) && !empty($glob['monitored_group'])){
				$selected = array();
				foreach ($glob['monitored_group'] as $id){
					if(empty($id)){
						continue;
					}
					array_push($selected,'"#'.$id.'"');
				}
			
    			$selected = '"ui":{"initially_select" : ['.join(',',$selected).']},';
    		}
    		global $bottom_includes;
    		$folder = CURRENT_VERSION_FOLDER;
$bottom_includes.= <<<JS
<script type="text/javascript">
$(function(){
	$('#treeselect').jstree({"themes" : {"theme" : "white","dots" : true,"icons" : true},
			"types" : {
			"valid_children":[ "root" ],
			"types" : {
				"root" : {"icon" : { "image" : "{$folder}js/themes/white/root.png" },
						"valid_children" : [ "default","group" ],"hover_node" : true},
				"group" : {"icon" : { "image" : "{$folder}js/themes/white/group.png" },
						"valid_children" : [ "default"],	"hover_node" : true},				
				"default" : {"icon" : { "image" : "{$folder}js/themes/white/default.png" },
						"valid_children" : "none","hover_node": true}
			}},
			{$selected}
			"json_data":{"ajax":{"url" : "index_ajax.php?pag=xsession&active_only=1"}},
			"plugins" : [ "themes","json_data","types","ui",'checkbox2' ]
	});
	flobn.register('hasTree',true,true);
	$('#enableAlias').addClass('hide');
})
</script>
JS;
    		$ft->assign('HAS_DROPDOWN','display:none');
    		break;
    	
    	case 4:
    		$ft->assign(array(
     			'MONITORED_USERS' => build_data_dd('member',
     											   $glob['monitored'],
     											   "logon",
     											   array('field' => 'logon',
     											   		 'cond' => ' WHERE active = 1 ORDER BY logon ASC'
     											   )),
     			'HAS_TREE' => 'display:none'
     		));
     		
     		global $bottom_includes;
    		
$bottom_includes.= <<<JS
<script type="text/javascript">
$(function(){
	$('#enableAlias').removeClass('hide');
});
</script>
JS;
     		break;    
     	default:
			$ft->assign(array(
     			'MONITORED_USERS' => build_data_dd('member',
     											   $glob['monitored'],
     											   "logon",
     											   array('field' => 'logon',
     											   		 'cond' => ' WHERE active = 1 ORDER BY logon ASC'
     											   )),
     			'HAS_TREE' => 'display:none'
     		));
     		
     		break;     				
    }
}
else
{
    $page_title="Edit Member";
    $next_function='member-update';
    
    $query = $dbu->query("SELECT * FROM member WHERE member_id = '".$glob['mid']."'");
    if(!$query->next())
    {
    	unset($ft);
    	return get_error_message('Invalid ID');
    }
    $ft->assign(array(
							'FIRST_NAME' => $query->gf('first_name'),			
							'LAST_NAME' => $query->gf('last_name'),			
							'USERNAME' => $query->gf('username'),			
							'EMAIL' => $query->gf('email'),			
							'PASSWORD' => $query->gf('password'),			
							'PASSWORD2' => $glob['password2'] ? $glob['password2'] :  $query->f('password'),			
							'DESCRIPTION' => $query->gf('description'),			
							'ACCESS_LEVEL' => bulid_simple_dropdown(array(1 => 'Administrator',
																		  '1.5' => 'Tech Admin',
																		  2 => 'Manager',
																		  // 3 => 'Limited Manager',
																		  3 => 'DPO',
																		  4 => 'Employee'),$query->gf('access_level')),			
							'ACTIVE' => $query->gf('active') ? 'checked="checked"' : '',
							'ACTIVEOLD' => $query->gf('active'),
							'ALIAS' => $query->gf('alias') ? 'checked="checked"' : '' ,
							'PREFILLED' => $glob['prefilled'] ? 1 : 0 ,
							'MEMBERNAME' => $glob['membername'] ? $glob['membername'] : '' ,
							'NODISPLAY' => $glob['prefilled'] ? ' style="display:none;" ' : '' ,
    ));
    switch ($query->gf('access_level')){
    	case 1:
    	case 1.5:
    		$ft->assign('HAS_MONITORED_USERS','hide');
    		
    		global $bottom_includes;
    		
$bottom_includes.= <<<JS
<script type="text/javascript">
$(function(){
	$('#enableAlias').addClass('hide');
});
</script>
JS;
    		break;
    	case 2:
    	case 3:
			$selected = array();
			$departments = $dbu->query("SELECT department_id
								FROM `member2manage2dep`
								WHERE `member_id` = " . $glob['mid']);
			
			while ($departments->next()){
    				array_push($selected,'"#s'.$departments->f('department_id').'"');
    			}
			$selected = '"ui":{"initially_select" : ['.join(',',$selected).']},';
			
    		$folder = CURRENT_VERSION_FOLDER;
    		global $bottom_includes;
$bottom_includes.= <<<JS
<script type="text/javascript">
$(function(){
	$('#treeselect').jstree({"themes" : {"theme" : "white","dots" : true,"icons" : true},
			"types" : {
			"valid_children":[ "root" ],
			"types" : {
				"root" : {"icon" : { "image" : "{$folder}js/themes/white/root.png" },
						"valid_children" : [ "default","group" ],"hover_node" : true},
				"group" : {"icon" : { "image" : "{$folder}js/themes/white/group.png" },
						"valid_children" : [ "default"],	"hover_node" : true},				
				"default" : {"icon" : { "image" : "{$folder}js/themes/white/default.png" },
						"valid_children" : "none","hover_node": true}
			}},
			{$selected}
			"json_data":{"ajax":{"url" : "index_ajax.php?pag=xsession"}},
			"plugins" : [ "themes","json_data","types","ui",'checkbox2' ]
	});
	flobn.register('hasTree',true,true);
	$('#enableAlias').addClass('hide');
})
</script>
JS;
    		$ft->assign('HAS_DROPDOWN','display:none');
    		break;
     	case 4:
     	
     		$selected = isset($glob['monitored']) ? $glob['monitored'] : $query->f('member_id');
			$ft->assign(array(
     			'MONITORED_USERS' => build_data_dd('member',
     											   $selected,
     											   "logon",
     											   array('field' => 'logon',
     											   		 'cond' => ' WHERE active = 1 OR member_id = '.$glob['mid'] . ' ORDER BY logon ASC'
     											   )),
     			'HAS_TREE' => 'display:none'
     		));
     		global $bottom_includes;
    		
$bottom_includes.= <<<JS
<script type="text/javascript">
$(function(){
	$('#enableAlias').removeClass('hide');
});
</script>
JS;
     		break;
     		
    	default:
     		$selected = isset($glob['monitored']) ? $glob['monitored'] : $query->f('member_id');
			$ft->assign(array(
     			'MONITORED_USERS' => build_data_dd('member',
     											   $selected,
     											   "logon",
     											   array('field' => 'logon',
     											   		 'cond' => ' WHERE active = 1 OR member_id = '.$glob['mid'] . ' ORDER BY logon ASC'
     											   )),
     			'HAS_TREE' => 'display:none'
     		));
     		
     		break;     				
    }
    
    
}

$ft->assign(array(
			'PAGE_TITLE' => $ft->lookup($page_title),
			'ACT'        => $next_function,
			'MEMBER_ID'=> $glob['mid'],
			'MESSAGE'    => get_error($glob['error'])
)); 

$site_meta_title=$meta_title;
$site_meta_keywords=$meta_keywords;
$site_meta_description=$meta_description;


$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');

?>