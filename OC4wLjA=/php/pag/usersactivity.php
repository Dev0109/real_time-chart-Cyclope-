<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
* 
* nu se modifica pentru ca nu se lucreaza cu tabela session_log
* se modifica numai clauza pentru filtrul de timp
* 
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
//

$matches = array();
preg_match_all('!([0-9]{1,2}/[0-9]{1,2}/[0-9]{4})( [0-9]+\:[0-9]+ [AM|PM]+)?!',$glob['time']['time'],$matches);
$pieces = array_shift($matches);
$days = array();
switch (count($pieces)){
	case 1:
		$avg = 1;
		break;
	case 2:
		$start_time = strtotime(reset($pieces));
		$start_hour = date('G',$start_time);
		$start_time = mktime(0,0,0,date('n',$start_time),date('d',$start_time),date('Y',$start_time));
		//---
		$end_time = strtotime(end($pieces));
		$end_hour = date('G',$end_time);
		$end_time = mktime(23,59,59,date('n',$end_time),date('d',$end_time),date('Y',$end_time));
		$avg = ceil(($end_time - $start_time) / 86400);
		break;
}

$filters = get_filters($glob['t'],$glob['f'],$glob['time']);
extract($filters,EXTR_OVERWRITE);


$active = array();
$dbu->query("SELECT SUM(session_activity.duration) as duration,
					session.session_id,
					member.member_id 
					FROM session_activity 
					INNER JOIN session ON session.session_id = session_activity.session_id
					".$app_join."
					WHERE session_activity.activity_type = 1 ".$app_filter."
					GROUP BY member.member_id");
while ($dbu->move_next()){
	$active[$dbu->f('member_id')] += $dbu->f('duration');
}


$inactive = array();
$dbu->query("SELECT SUM(session_activity.duration) as duration,session.session_id,member.member_id FROM session_activity 
INNER JOIN session ON session.session_id = session_activity.session_id
".$app_join."
WHERE session_activity.activity_type = 0 ".$app_filter."
GROUP BY member.member_id");
while ($dbu->move_next()){
	$inactive[$dbu->f('member_id')] += $dbu->f('duration');
}

$online = array();
$dbu->query("SELECT SUM(session_application.duration) as duration,session.session_id,member.member_id FROM session_application 
INNER JOIN session ON session.session_id = session_application.session_id
INNER JOIN application ON session_application.application_id = application.application_id
".$app_join."
WHERE 1=1  AND session_application.time_type = 0 AND application.application_type IN (".ONLINE_TIME_INCLUDE.") ".$app_filter."
GROUP BY member.member_id");

while ($dbu->move_next()){
	$online[$dbu->f('member_id')] += $dbu->f('duration');
}

//overtime 

$pieces = explode('-',$glob['f']);
$pieces[0] = substr($pieces[0],1);
$workschedule = get_workschedule($pieces[0]);

$overtime = array();
$dbu->query("SELECT SUM(session_activity.duration) as duration,session.session_id,member.member_id, session_activity.hour, session_activity.day FROM session_activity 
INNER JOIN session ON session.session_id = session_activity.session_id
".$app_join."
WHERE 1 = 1 AND session_activity.activity_type < 2 ".$app_filter."
GROUP BY session_activity.hour, session_activity.day, member.member_id");

while ($dbu->move_next()){
	if( ($dbu->f('hour') < $workschedule[$dbu->f('day')]['start_hour'] ) ||( $dbu->f('hour') >= $workschedule[$dbu->f('day')]['end_hour'] ) )
	{
		$overtime[$dbu->f('member_id')] += $dbu->f('duration');
	}
}

//	lorand
$sortable_columns = array(
	'duration',
	'member.logon',
	);

$sortcolumns = get_sorting($sortable_columns,'','desc');

$ft->assign(array(
	'ANCHOR_INNER_0' => render_anchor_inner(0),
	'ANCHOR_INNER_1' => render_anchor_inner(1),
	'DEBUGMESSAGE' => '',
	// 'DEBUGMESSAGE' => basename($_SERVER['PHP_SELF']),
	// 'DEBUGMESSAGE' => $sortcolumns,
));
//END

$dbu->query("SELECT member.first_name,
			member.last_name,
			member.alias,
			member.logon,
			member.active,
			member.member_id,
			session.session_id,
			SUM(session_application.duration+session_application.idle_duration) as duration
			FROM session_application
			INNER JOIN session ON session.session_id = session_application.session_id
			".$app_join."
			WHERE 1=1 AND session_application.time_type = 0
			".$app_filter."
			GROUP BY member.member_id
			" . $sortcolumns . " ");
$max_rows=$dbu->records_count();
$dbu->move_to($offset);
$i=0;
if($avg == 0 || !is_numeric($avg)){$avg = 1;}

while($dbu->move_next()){
	$ft->assign(array(
		'NAME' => $dbu->f('alias') == 1 ? trialEncrypt($dbu->f('first_name').' '.$dbu->f('last_name')) : trialEncrypt($dbu->f('logon')),
		'TOTAL' => format_time($dbu->f('duration')),
		'ACTIVE' => format_time($active[$dbu->f('member_id')]),
		'ACTIVE_AVG' => format_time($active[$dbu->f('member_id')]/$avg),
		'IDLE' => format_time($inactive[$dbu->f('member_id')]),
		'IDLE_AVG' => format_time($inactive[$dbu->f('member_id')]/$avg),
		'OVERTIME' => format_time($overtime[$dbu->f('member_id')]),
		'OVERTIME_AVG' => format_time($overtime[$dbu->f('member_id')]/$avg),
		'ONLINE' => format_time($online[$dbu->f('member_id')]),
		'ONLINE_AVG' => format_time($online[$dbu->f('member_id')]/$avg),
		'ACTIVITY' => number_format($active[$dbu->f('member_id')] *100 / $dbu->f('duration'),2,',','.').'%'
	));
	
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

if( $i == 0 )
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

$dates = $dbu->row("SELECT MIN(date) AS genesis,MAX(date) AS last_day_on_earth FROM session ");


$ft->assign(array(
	'DEFAULT_VALUE' => isset($glob['time']) ? $glob['time']['time']: date('n/j/Y',$dates['genesis']).' - '.date('n/j/Y',$dates['last_day_on_earth']) ,
	'DATE_BEFORE' => date('n/j/Y',$dates['genesis']),
	'TIME_'.($glob['time']['type'] ? $glob['time']['type'] : 1) => 'selected="selected"',
	'HELP_LINK' => 'help.php?pag='.$glob['pag'],
));
global $bottom_includes;
$bottom_includes .= '
<script type="text/javascript">flobn.register("genesis",new Date("'.date('n/j/Y',$dates['genesis']).'"));</script><script type="text/javascript" src="ui/useractivity-ui.js"></script>';

$ft->assign('PAGE_TITLE',$ft->lookup('Users Activity'));
$ft->assign('MESSAGE',$glob['error']);

if(!$glob['is_ajax']){
	$ft->define_dynamic('ajax','main');
	$ft->parse('AJAX_OUT','ajax');
}

$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');