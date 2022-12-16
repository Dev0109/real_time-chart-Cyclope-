<?php
// var_dump(ADMIN_PATH.MODULE."templates/");
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define( array("main" => "emailreportsselect.html"));
$ft->define_dynamic('template_row','main');

$opt = array(
	1	=> 'Overview',
	// 17	=> 'Top Productive',
	7	=> 'Applications (Aggregated)',
	26	=> 'Internet (Page Titles)', 
	8	=> 'Alerts',
	3	=> 'Attendance',
	// 18	=> 'Top Unproductive',
	24	=> 'Applications (Per user)', 
	9	=> 'Documents',  
	12	=> 'Application Forms',
	22	=> 'Top Websites',
	19	=> 'Top Active',
	27	=> 'Internet (Domains)', 
	// 100 => 'Print',
	// 14	=> 'Timeline',
	5	=> 'Productivity',
	// 16	=> 'Software Inventory',
	21	=> 'Top Online',
	23	=> 'Top Applications',
	10	=> 'Internet (Links)',
	15	=> 'Files',
	13	=> 'Activity Categories',
	20	=> 'Top Idle',

	// 4	=> 'Overtime',
	// 11	=> 'Chat Monitoring',
	// 25	=> 'Software Updates',
	// 2	=> 'Users Activity',
	//6	=> 'Productivity Alerts', 
);

$dbu = new mysql_db();
// var_dump("test");
$selected = array();

if($glob['email_report_id'])
{
	$dbu->query("SELECT * FROM email_report_type WHERE email_report_id='".$glob['email_report_id']."'");
	
	while ($dbu->move_next()) {
		array_push($selected,$dbu->f('type'));
	}
}
else if($glob['report'])
{
	$selected = $glob['report'];
}

// while (list ($key, $val) = each ($opt)) 
foreach($opt as $key => $val)
{
	$ft->assign(array(
		'ACTIVE' => in_array($key,$selected) ? 'active' : '',
		'CHECKED' => in_array($key,$selected) || $_REQUEST['preset'] == $key ? 'checked="checked"' : '',
		'VALUE' => $key,
		'NAME' => $ft->lookup($val),
	));
	
	$ft->parse('TEMPLATE_ROW_OUT','.template_row');
}

$ft->parse('content','main');
return $ft->fetch('content');
?>