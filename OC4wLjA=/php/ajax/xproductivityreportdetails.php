<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
if(!isset($glob['app']) || !isset($glob['type'])){
	return '';
}

$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => $glob['pag'].".html"));
$ft->define_dynamic('template_row','main');
$session_website = get_session_website_table();
$filters = get_filters($glob['t'],$glob['f'],$glob['time'],true);
extract($filters,EXTR_OVERWRITE);

$categories = get_categories($glob['f'],$glob['type']);

$nodes = explode('-',$glob['f']);
$department_id = reset($nodes);
unset($nodes);
$dbu = new mysql_db();

$session = $dbu->row("SELECT SUM(session_application.duration) AS duration,
							 COALESCE(application_productivity.productive,1) AS productive
						FROM session_application
						INNER JOIN session ON session.session_id = session_application.session_id 
						".$app_join."
						LEFT JOIN application_productivity ON application_productivity.department_id = 1 
														  AND application_productivity.link_id = session_application.application_id 
													      AND application_productivity.link_type = 0						
						WHERE session_application.time_type = 0 AND session_application.application_id = '".$glob['app']."' AND 1=1 ".$app_filter);
$total = $session['duration'];
$table = '';
$primary = '';
$name_field = 'name';
switch ($glob['type']){
	case 1:
    // 1 chat
    	$session_table = 'chat';
	    $table = 'chat';
	    $primary = $table.'_id';
    	break;
    case 2:
    // 2 document
    	$session_table = 'document';
    	$table = 'document';
	    $primary = $table.'_id';
    	break;
    case 3:
    // 3 site 
    	$session_table = $session_website;
    	$table = 'domain';
	    $primary = $table.'_id';
	    $name_field = 'domain';
    	break;	
    default:
   		return '';
}
$productivity_field = 'COALESCE(application_productivity.productive,1) AS productive';
if($session['productive'] != 3  && $session['productive'] != 1){
	$productivity_field = $session['productive'].'  AS productive';
	$productivity_filter = '';
}else{
	$productivity_filter = 'LEFT JOIN application_productivity ON application_productivity.department_id = 1
							AND application_productivity.link_id = '.$table.'.'.$primary.'
							AND application_productivity.link_type = '.$glob['type'];
}

// $dbu->query("SELECT ".(($glob['type'] == 3)?"session_".$session_table.".duration":"SUM(session_".$session_table.".duration)")." AS app_duration,
$dbu->query("SELECT SUM(session_".$session_table.".duration) AS app_duration,
	    			".$table.".".$primary." AS id,
	    			".$table.".".$name_field." as name,
	    			session_".$session_table.".application_id,
	    			".$productivity_field."
	    			 FROM session_".$session_table."
	    			 INNER JOIN ".$table." ON ".$table.".".$primary." = session_".$session_table.".".$primary."
					 INNER JOIN session ON session.session_id = session_".$session_table.".session_id
					".$app_join."
					".$productivity_filter."
					WHERE session_".$session_table.".time_type = 0 AND 2=2 AND session_".$session_table.".application_id = '".$glob['app']."'
					".$app_filter."
					GROUP BY ".$table.".".$primary."
					ORDER BY app_duration DESC");


$i = 0;
$z = 0;
while ($dbu->move_next()){
	$z += $dbu->f('app_duration');
	if($i % 2){
		$class = 'even';
	}else{
		$class = '';
	}
	
	$cat_name = $ft->lookup('Uncategorised');
	$cat_id = 1;
	if(isset($categories[$dbu->f('id').'-'.$glob['type']])){
		$cat_name = $ft->lookup($categories[$dbu->f('id').'-'.$glob['type']]['category']);
		$cat_id = $categories[$dbu->f('id').'-'.$glob['type']]['category_id'];
	}	


	$ft->assign(array(	
		'CHILD_TYPE'	 => $glob['type'],
		'CLASS'	         => $class,
		'NAME'       	 => $dbu->f('name'),
		'DURATION'       => $dbu->f('app_duration'),
		'TIME'      	 => format_time($dbu->f('app_duration')),
		'WIDTH'  => ((($dbu->f('app_duration') * 100) / $total) > 1) ? number_format((($dbu->f('app_duration') * 100) / $total),2,',','.') : ' < 1',
		'ID'        	 => $dbu->f('id'),
		'APPLICATION_ID' => $dbu->f('application_id'),
		'CATEGORY'       => $cat_name,
		'CATEGORY_ID' 	 => $cat_id,
		'DEPARTMENT'     => $department_id,
		'FIRST_APPLICATION_ID'=> $dbu->f('application_id'),
	));
		$dbu_prod = new mysql_db();
		$productive = $dbu_prod->field("SELECT `productive`
							FROM `application_productivity`
							WHERE `department_id` = 1
							AND `link_id` = " . $dbu->f('id') . "
							AND `link_type` = " . $glob['type'] . "
							LIMIT 1 ");
		if ($productive === false){
			$productive = 1;
		}
	switch ($productive){
		case 0:
			$ft->assign(array(
				'SEL' => 0,
				'CSS_CLASS'=> 'distracting',
				'TYPE'=> $ft->lookup('Distracting'),
			));
			break;
		case 2:
			$ft->assign(array(
				'SEL' => 2,
				'CSS_CLASS'=> 'productive',
				'TYPE'=> $ft->lookup('Productive'),
			));
			break;
			
		default:
			$ft->assign(array(
				'SEL' => 1,
				'CSS_CLASS'=> 'neutral',
				'TYPE'=> $ft->lookup('Neutral'),
			));
		
	}	
	$ft->parse('TEMPLATE_ROW_OUT','.template_row');
	$i++;
}
if($i == 0){
	//empty
	$glob['y_u_get_here'] = true;
	unset($ft);
	return '';
}

$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');