<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/

$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => $glob['pag'].".html"));
$ft->define_dynamic('template_row','main');

$site_meta_title=$meta_title;
$site_meta_keywords=$meta_keywords;
$site_meta_description=$meta_description;

$selected = array();
$groups = '';
$dbu = new mysql_db();
$page_title = $ft->lookup('Add Alert');
$alert_active = '';
$func_name = 'alert-add';
if(is_numeric($glob['alert_id'])){
	$q = $dbu->query("SELECT * FROM alert WHERE alert_id = ?",$glob['alert_id']);
	if($q->next()){
		$glob['name'] = isset($glob['name']) ? $glob['name'] : $q->f('name');
		$glob['description'] = isset($glob['description']) ? $glob['description'] : $q->f('description');
		array_push($selected,$q->f('alert_type'));
		$page_title = $ft->lookup('Edit Alert');
		$dep = $dbu->query("SELECT * FROM alert_department WHERE alert_id = ?",$glob['alert_id']);
		$sel = array();
		while ($dep->next()){
			array_push($sel,'s'.$dep->f('department_id'));
		}
		$groups = '"ui":{"initially_select" : ["'.join('","',$sel).'"]},';
		//fill in ze form fields so that everything else works
		$alert_active = '';
		$func_name = 'alert-update';
		$glob['alert_type'] = isset($glob['alert_type']) ? $glob['alert_type'] : $q->f('alert_type');
		$glob['dep'] = isset($glob['dep']) ? $glob['dep'] : reset($sel);
	}
}
$department_id = 1;
if (isset($glob['selected']) && is_array($glob['selected']) && !empty($glob['selected'])){
	$groups = '"ui":{"initially_select" : ["'.current($glob['selected']).'"]},';
	$department_id = substr(current($glob['selected']),1);
}
if(isset($glob['alert_type'])){
	switch ($glob['alert_type']){
		case 1:
			$glob['dep'] = $glob['dep'] ? $glob['dep'] : $department_id;
			$alert_active = "flobn.register('editMode','xworkalert');";
			$ft->assign('TAB_CONTENT',include(CURRENT_VERSION_FOLDER.'php/ajax/xworkalert.php'));
			break;
		case 2:
			$alert_active = "flobn.register('editMode','xidlealert');";
			$ft->assign('TAB_CONTENT',include(CURRENT_VERSION_FOLDER.'php/ajax/xidlealert.php'));
			break;
		case 3:
			$alert_active = "flobn.register('editMode','xonlinealert');";
			$ft->assign('TAB_CONTENT',include(CURRENT_VERSION_FOLDER.'php/ajax/xonlinealert.php'));
			break;
		case 4:
			$alert_active = "flobn.register('editMode','xappalert');";
			$ft->assign('TAB_CONTENT',include(CURRENT_VERSION_FOLDER.'php/ajax/xappalert.php'));
			break;	
		case 5:
			$alert_active = "flobn.register('editMode','xmonitoralert');";
			$ft->assign('TAB_CONTENT',include(CURRENT_VERSION_FOLDER.'php/ajax/xmonitoralert.php'));
			break;
		case 6:
			$alert_active = "flobn.register('editMode','xwebsitealert');";
			$ft->assign('TAB_CONTENT',include(CURRENT_VERSION_FOLDER.'php/ajax/xwebsitealert.php'));
			break;
		case 7:
			$alert_active = "flobn.register('editMode','xseqalert');";
			$ft->assign('TAB_CONTENT',include(CURRENT_VERSION_FOLDER.'php/ajax/xseqalert.php'));
			break;

	}
}


$opts = array('','Work Schedule Alert','Idle Time Alert','Online Time Alert','Applications Alert', 'Monitor Alert','Website Alert','Sequence Alert');
foreach ($opts as $key => $opt){
	if(empty($opt)){
		continue;
	}
	$ft->assign(array(
		'ACTIVE' => isset($glob['alert_type']) && $key == $glob['alert_type'] ? 'active' : '',
		'CHECKED' => isset($glob['alert_type']) && $key == $glob['alert_type'] ? 'checked="checked"' : '',
		'VALUE' => $key,
		'ALERT_NAME' => $ft->lookup($opt),
	));
	$ft->parse('TEMPLATE_ROW_OUT','.template_row');
}

$ft->assign(array(
	'PAGE_TITLE' => $page_title,
	'ALERT_NAME' => $glob['name'],
	'ALERT_DESCRIPTION' => $glob['description'],
	'ACT' => $func_name,
	'ALERT_ID' => $glob['alert_id'],
	'MESSAGE' => get_error($glob['error']),
	'PAG' => $glob['pag']
));

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
			{$groups}
			"json_data":{"ajax":{"url" : "index_ajax.php?pag=xdepartments"}},
			"plugins" : [ "themes","json_data","types","ui",'checkbox2' ]
	});
	{$alert_active}
});
</script>
<script type="text/javascript" src="{$folder}ui/alert-ui.js"></script>
JS;


$ft->parse('CONTENT','main');
//$ft->fastprint('CONTENT');
return $ft->fetch('CONTENT');