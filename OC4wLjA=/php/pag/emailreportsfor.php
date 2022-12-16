<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftm=new ft(ADMIN_PATH.MODULE."templates/");
$ftm->define(array('main' => "emailreportsfor.html"));

$dbu = new mysql_db();

$selected = array();

if($glob['email_report_id'])
{
	$managed = $dbu->query("SELECT department_id FROM email_report_group 
							WHERE email_report_id = '".$glob['email_report_id']."'");
	
	while ($managed->next()){
		array_push($selected,'"s'.$managed->f('department_id').'"');
	}
}
else 
{
	if(is_array($glob['selected']) && !empty($glob['selected']))
	{
		foreach ($glob['selected'] as $key => $value)
		{
			$pieces = explode('-',$value);
			list($department_id,$computer_id,$member_id) = $pieces;
			$department_id = substr($department_id,1);
			
			if(!in_array('"s'.$department_id.'"',$selected))
			{
				array_push($selected,'"s'.$department_id.'"');
			}
		}
	}
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
			"json_data":{"ajax":{"url" : "index_ajax.php?pag=xdepartments"}},
			"plugins" : [ "themes","json_data","types","ui",'checkbox2' ]
	});
	flobn.register('hasTree',true,true);
})
</script>
JS;

$ftm->parse('CONTENT','main');
return $ftm->fetch('CONTENT');
?>