<?php

$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' =>"attendance.html"));
$ft->define_dynamic('template_row','main');
$l_r = ROW_PER_PAGE;

if(($_SESSION['NUMBER_OF_ROWS']) && (is_numeric($_SESSION['NUMBER_OF_ROWS']))){
	$rowcount =  $_SESSION['NUMBER_OF_ROWS'];
	$number_of_rows =  "LIMIT 0,".$rowcount;
} else {
	$rowcount =  500;
	$number_of_rows =  "";
}

	//	modified for pdf	--->
	if($_REQUEST['render'] == 'pdf'){
		$l_r = $rowcount;
	}
if($_REQUEST['render'] == 'pdf' && $_REQUEST['send'] == 'email'){
	$rowcount = get_email_rowcount();
	$l_r = $rowcount;
	$number_of_rows =  "LIMIT 0,".$rowcount;
}
	//	<---	modified for pdf
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
$_SESSION['filter']['time']['type'] = 1;
// $glob['time']['type'] = 1;


// if($_REQUEST['timefilter']) {
	// $_SESSION['filters']['time'] = array(
		// 'time' => $_REQUEST['timefilter'],
		// 'type' => 1
	// );
	// $glob['time'] = array(
		// 'time' => $_REQUEST['timefilter'],
		// 'type' => 1,
	// );
// }
$json['session'] = $_SESSION;
$json['session'] = $glob;


$filters = get_filters($glob['t'],$glob['f'],$glob['time']);
extract($filters,EXTR_OVERWRITE);
$export_header = get_export_header($glob['f']);
extract($export_header,EXTR_OVERWRITE);
$glob['append'] = trialEncrypt($member_name);
$active = array();
//	schedule array
$dbu->query("SELECT `department_id` , `day` , `start_time` , `end_time` FROM `workschedule`");
while ($dbu->move_next()){
	$schedule[$dbu->f('department_id')][$dbu->f('day')]['start_hour'] = date('G',$dbu->f('start_time'));
	$schedule[$dbu->f('department_id')][$dbu->f('day')]['start_minute'] = date('i',$dbu->f('start_time'));
	$schedule[$dbu->f('department_id')][$dbu->f('day')]['end_hour'] = date('G',$dbu->f('end_time'));
	$schedule[$dbu->f('department_id')][$dbu->f('day')]['end_minute'] = date('i',$dbu->f('end_time'));
}

$dbu->query("SELECT SUM(session_activity.duration) as duration, session.session_id, member.member_id
FROM session_activity 
INNER JOIN session ON session.session_id = session_activity.session_id
".$app_join."
WHERE session_activity.activity_type = 1
".$app_filter."
GROUP BY session_id,member.member_id order by member.member_id DESC, session.session_id DESC");
while ($dbu->move_next()){
	$active[$dbu->f('member_id').'-'.$dbu->f('session_id')] += $dbu->f('duration');
}

$inactive = array();
$dbu->query("SELECT SUM(session_activity.duration) as duration,session.session_id,member.member_id FROM session_activity 
INNER JOIN session ON session.session_id = session_activity.session_id
".$app_join."
WHERE session_activity.activity_type = 0
".$app_filter."
GROUP BY session_id,member.member_id");
while ($dbu->move_next()){
	$inactive[$dbu->f('member_id').'-'.$dbu->f('session_id')] += $dbu->f('duration');
}

$private = array();
$dbu->query("SELECT SUM(session_activity.duration) as duration,session.session_id,member.member_id FROM session_activity 
INNER JOIN session ON session.session_id = session_activity.session_id
".$app_join."
WHERE session_activity.activity_type > 1
".$app_filter."
GROUP BY session_id,member.member_id");
while ($dbu->move_next()){
	$private[$dbu->f('member_id').'-'.$dbu->f('session_id')] += $dbu->f('duration');
}

//overtime 

$pieces = explode('-',$glob['f']);
$pieces[0] = substr($pieces[0],1);
$workschedule = get_workschedule($pieces[0]);

$overtime = array();
$dbu->query("SELECT SUM(session_activity.duration) as duration,session.session_id,member.member_id, session_activity.hour, session_activity.day FROM session_activity 
INNER JOIN session ON session.session_id = session_activity.session_id
".$app_join."
WHERE 1= 1 
AND session_activity.activity_type = 1
".$app_filter."
GROUP BY session_activity.hour, session_activity.day, member.member_id");

while ($dbu->move_next()){
	if( ($dbu->f('hour') < $workschedule[$dbu->f('day')]['start_hour'] ) ||( $dbu->f('hour') >= $workschedule[$dbu->f('day')]['end_hour'] ) )
	{
		$overtime[$dbu->f('member_id').'-'.$dbu->f('session_id')] += $dbu->f('duration');
	}
}

if($glob['first_last_only'] == 1){
	$_SESSION['first_last_only'] = 1;
}
if($glob['first_last_only'] == 2){
	$_SESSION['first_last_only'] = 1;
	// unset($_SESSION['first_last_only']);
}
$consider_first_last_action=1;
if($_SESSION['first_last_only']){
	$consider_first_last_action = 1;
	$ft->assign('FIRST_LAST_ONLY_VAL', 2);
	$ft->assign('FIRST_LAST_ONLY_CHECKED', 'checked');
} else {
	$ft->assign('FIRST_LAST_ONLY_VAL', 1);
	$ft->assign('FIRST_LAST_ONLY_CHECKED', '');
	
}

if($consider_first_last_action==1){
	$secondary_condition = "AND session_activity.activity_type = 1";
} else {
	$secondary_condition = "AND session_activity.activity_type < 2";
}

$_SESSION['first_last_only'] = 1;
$secondary_condition = "AND session_activity.activity_type = 1 AND session_attendance.active = 1";

//	lorand
$sortable_columns = array(
	'session_attendance.start_time',
	'member.logon',
	'session_attendance.start_time',
	);
$sortcolumns = get_sorting($sortable_columns,'','desc');

$ft->assign(array(
	'ANCHOR_INNER_0' => render_anchor_inner(0),
	'ANCHOR_INNER_1' => render_anchor_inner(1),
	'ANCHOR_INNER_0' => render_anchor_inner(0),
	'DEBUGMESSAGE' => '',
	// 'DEBUGMESSAGE' => basename($_SERVER['PHP_SELF']),
	// 'DEBUGMESSAGE' => $sortcolumns,
));
//END

$pieces = explode('-',$glob['f']);
$filterCount = count($pieces);

if (strpos($glob['f'],'c') !== false || (strpos($glob['f'],'u') !== false && $filterCount == 3)){
	$onlycompfilter = '';
}else {
	$onlycompfilter = '
INNER JOIN computer2member ON computer2member.member_id = member.member_id
INNER JOIN computer ON computer.computer_id = session.computer_id';
}

$dbu->query("SELECT member.first_name,
member.last_name,
member.alias,
member.member_id,
member.department_id,
member.logon,
session_activity.activity_type,
computer.name AS computer_name,
session.day,
session_activity.session_id,
session.member_id,
MIN(session_attendance.start_time) AS start_work,
MAX(session_attendance.end_time) AS end_work
FROM session_activity
INNER JOIN session ON session.session_id = session_activity.session_id
".$app_join."
INNER JOIN session_attendance ON session_attendance.session_id = session_activity.session_id
".$onlycompfilter."
WHERE 1=1
".$secondary_condition."
AND session_attendance.start_time >= session.date
".$app_filter."
GROUP BY member.member_id,session_activity.session_id
" . $sortcolumns . " ");
$ai = 0;
$ttl_active = 0;
$ttl_start = 0;
$ttl_end = 0;
$ttl_total = 0;
$ttl_avg = 0;
while($dbu->move_next()){
	$ttl_active = $ttl_active + ($active[$dbu->f('member_id').'-'.$dbu->f('session_id')]);
	$ttl_start = $ttl_start + date('H',$dbu->f('start_work')) * 60 * 60 + date('i',$dbu->f('start_work')) * 60;
	$ttl_end = $ttl_end + date('H',$dbu->f('end_work')) * 60 * 60 + date('i',$dbu->f('end_work')) * 60;
	$ttl_ratio = $ttl_ratio + (((($active[$dbu->f('member_id').'-'.$dbu->f('session_id')]) * 100) / ($dbu->f('end_work') - $dbu->f('start_work'))));
	if ((date('G',$dbu->f('start_work')) > $schedule[$dbu->f('department_id')][date( "w", $dbu->f('start_work'))]['start_hour']) || (date('G',$dbu->f('start_work')) == $schedule[$dbu->f('department_id')][date( "w", $dbu->f('start_work'))]['start_hour'] && date('i',$dbu->f('start_work')) >= $schedule[$dbu->f('department_id')][date( "w", $dbu->f('start_work'))]['start_minute'])){
		$tardy = " tardy ";
	} else {
		$tardy = " punctual ";
	}
	if ((date('G',$dbu->f('end_work')) < $schedule[$dbu->f('department_id')][date( "w", $dbu->f('end_work'))]['end_hour']) || (date('G',$dbu->f('end_work')) == $schedule[$dbu->f('department_id')][date( "w", $dbu->f('end_work'))]['end_hour'] && date('i',$dbu->f('end_work')) <= $schedule[$dbu->f('department_id')][date( "w", $dbu->f('end_work'))]['end_minute'])){
		$early = " early ";
	} else {
		$early = " overtime ";
	}
	if (date('Ymd') == date('Ymd', $dbu->f('end_work'))) {
		$early = " today ";
	}
		$json['data'][$ai] = array(
				'NAME' => trialEncrypt($dbu->f('alias') == 1 ? $dbu->f('first_name').' '.$dbu->f('last_name') : $dbu->f('logon')),
				'COMPUTER' => trialEncrypt($dbu->f('computer_name'),'comp'),
				'DATE' => date('d/m/Y - D',$dbu->f('start_work')),
				'START' => date('H:i',$dbu->f('start_work')),
				'END' => date('H:i',$dbu->f('end_work')),
				'TOTAL' => format_time($active[$dbu->f('member_id').'-'.$dbu->f('session_id')] + $inactive[$dbu->f('member_id').'-'.$dbu->f('session_id')] + $private[$dbu->f('member_id').'-'.$dbu->f('session_id')],true,true),
				'ACTIVE' => format_time($active[$dbu->f('member_id').'-'.$dbu->f('session_id')],true,true),
				'IDLE' => format_time($inactive[$dbu->f('member_id').'-'.$dbu->f('session_id')],true,true),
				'OVERTIME' => format_time($overtime[$dbu->f('member_id').'-'.$dbu->f('session_id')]),
				'PRIVATE' => format_time($private[$dbu->f('member_id').'-'.$dbu->f('session_id')]),
				'RATIO' => number_format(((($active[$dbu->f('member_id').'-'.$dbu->f('session_id')]) * 100) / ($dbu->f('end_work') - $dbu->f('start_work'))),2),
				'TARDY' => $tardy,
				'EARLY' => $early,
				);
	$ft->assign($json['data'][$ai]);
if ($_REQUEST['render'] == 'json') {
	}
	
	if($consider_first_last_action==1){
		$total = $dbu->f('end_work') - $dbu->f('start_work');
		$new_iddle = $total - $active[$dbu->f('member_id').'-'.$dbu->f('session_id')] - $private[$dbu->f('member_id').'-'.$dbu->f('session_id')];
		// HELP start
		if ($active[$dbu->f('member_id').'-'.$dbu->f('session_id')] > $total) {
			$ft->assign('ACTIVE', format_time($total,true,true));
			$new_iddle = 0;
			$ft->assign('RATIO', number_format((($total * 100) / ($dbu->f('end_work') - $dbu->f('start_work'))),2));
		}
		// HELP end
		$ft->assign('IDLE', format_time($new_iddle,true,true));
		$ft->assign('TOTAL', format_time($total,true,true));
	}
	$ttl_total = $ttl_total + ($dbu->f('end_work') - $dbu->f('start_work'));
	$ai++;
	
	
	if(($i % 2)==0 )
	{
		$ft->assign('CLASS','even');
	}
	else
	{
		$ft->assign('CLASS','');
	}
	$ft->parse('TEMPLATE_ROW_OUT','.template_row');
	$i++;
}

if($ai == 0){$ai = 1;}
	$ft->assign(array(
		'AVG_ACTIVE' => format_time($ttl_active / $ai,true,true),
		'AVG_START' => format_time($ttl_start / $ai,false,true),
		'AVG_END' => format_time($ttl_end / $ai,false,true),
		'AVG_TOTAL' => format_time($ttl_total / $ai,true,true),
		'AVG_RATIO' => number_format($ttl_ratio / $ai,2),
		
	));
if($offset>=$l_r)
{
	 $ft->assign('BACKLINK',"<a class=\"RedBoldLink\" href=\"index.php?pag=".$glob['pag']."&offset=".($offset-$l_r)."\">Prev</a>");
}
else
{
	 $ft->assign('BACKLINK',''); 
}
if($offset+$l_r<$max_rows)
{
	 $ft->assign('NEXTLINK',"<a class=\"RedBoldLink\" href=\"index.php?pag=".$glob['pag']."&offset=".($offset+$l_r)."\">Next</a>");
}
else
{
	 $ft->assign('NEXTLINK','');
}

//*****************JUMP TO FORM***************
$ft->assign('PAG_DD',get_pagination_dd($offset, $max_rows, $l_r, $glob));
//*****************JUMP TO FORM***************

$trial = $dbu->field("SELECT value FROM `settings` WHERE `constant_name` = 'TRUENC'");
$ecrypted_text = $dbu->field("SELECT message FROM `notification` WHERE `constant_name` = 'ENCRYPTED_TEXT'");
if ($trial != 2236985){
	$ft->assign('ENCRYPTMESSAGERAW', '<div class="encryptmessage">' . $ft->lookup($ecrypted_text) . '</div>');
}
if(!$i)
{
	$ft->assign(array(
		'NO_DATA_MESSAGE' => get_error($ft->lookup('No data to display for your current filters'),'warning'),
		'HIDE_CONTENT'	=> 'hide',
	));
}
else 
{
	$ft->assign(array(
		'NO_DATA_MESSAGE' => '',
		'HIDE_CONTENT'	=> '',
	));
}
	//	modified for pdf	--->
	$export_header = get_export_header($_SESSION['filters']['f']);
	extract($export_header,EXTR_OVERWRITE);
	$ft->assign(array(
		'PDF_HEADER' => pdf_header(),
		'PDF_HIDE' => pdf_hide(),
		'PDF_CLASS' => pdf_class(),
		'TITLE' => $ft->lookup('Attendance'),
		'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
		'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
	));
	//	<---	modified for pdf
$dates = $dbu->row("SELECT MIN(date) AS genesis,MAX(date) AS last_day_on_earth FROM session ");


$ft->assign(array(
	'DEFAULT_VALUE' => isset($glob['time']) ? $glob['time']['time']: date('n/j/Y',$dates['genesis']).' - '.date('n/j/Y',$dates['last_day_on_earth']) ,
	'DATE_BEFORE' => date('n/j/Y',$dates['genesis']),
	'TIME_'.($glob['time']['type'] ? $glob['time']['type'] : 1) => 'selected="selected"',
	'HELP_LINK' => 'help.php?pag=attendance',
));
global $bottom_includes;
$bottom_includes .= '
<script type="text/javascript">flobn.register("genesis",new Date("'.date('n/j/Y',$dates['genesis']).'"));</script><script type="text/javascript" src="ui/attendance-ui.js"></script>';

$ft->assign('PAGE_TITLE',$ft->lookup('Attendance for'));
$ft->assign('APPEND', $glob['append']);
if(!$glob['is_ajax']){
	$ft->define_dynamic('ajax','main');
	$ft->parse('AJAX_OUT','ajax');
}

$ft->assign('MESSAGE',$glob['error']);
$ft->parse('CONTENT','main');
	
	//	modified for pdf	--->
	if($_REQUEST['render'] == 'pdf'){
		$page = 'attendance';
		$html = $ft->fetch('CONTENT');
			file_put_contents(CURRENT_VERSION_FOLDER.'temp_pdf/'.$page.'.html',"\xEF\xBB\xBF" . $html);
		loadPDF($page,'inline');exit;
	} elseif($_REQUEST['render'] == 'json') {
		print( json_encode($json) );
		exit;
	} else {
		return $ft->fetch('CONTENT');
	}
	//	<---	modified for pdf