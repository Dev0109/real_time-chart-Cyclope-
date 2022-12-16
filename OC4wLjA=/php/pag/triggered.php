<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftx=new ft(ADMIN_PATH.MODULE."templates/");
$ftx->define(array('main' => $glob['pag'].".html"));
$ftx->define_dynamic('template_row','main');

$l_r = ROW_PER_PAGE;
$dbu = new mysql_db();
if($glob['time']['type'] != 1){
	$glob['time']['type'] = 1;
}
//	lorand
$sortable_columns = array(
	'triggered_date',
	'member.logon',
	);

$sortcolumns = get_sorting($sortable_columns,'','desc');

$ftx->assign(array(
	'ANCHOR_INNER_0' => render_anchor_inner(0),
	'ANCHOR_INNER_1' => render_anchor_inner(1),
	'DEBUGMESSAGE' => '',
	// 'DEBUGMESSAGE' => basename($_SERVER['PHP_SELF']),
	// 'DEBUGMESSAGE' => $sortcolumns,
));
//END

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
$reports = $dbu->query("SELECT * FROM alert");
$i=0;
while($reports->next()){
	$i++;
}

if($i == 0){
	$ftx->assign(array(
		'NO_ALERT_MESSAGE' =>get_error($ftx->lookup('There are no alerts defined. <a href="index.php?pag=alerts">Click here</a> to start adding alerts !'),'warning '),
		'EDIT_ALERT_MESSAGE' => '',
	));
}
else 
{
	$ftx->assign(array(
		'NO_ALERT_MESSAGE' =>'',
		'EDIT_ALERT_MESSAGE' => get_error($ftx->lookup("You have ").$i.$ftx->lookup(" defined alert(s). <a href=\"index.php?pag=alerts\">Click here</a> to view or edit you alerts."),'info'),
	));
}

$filters = get_filters($glob['t'],$glob['f'],$glob['time']);
extract($filters,EXTR_OVERWRITE);
	$pieces = explode('-',$glob['f']);
	$filterCount = count($pieces);
	
	if (strpos($glob['f'],'c') !== false || (strpos($glob['f'],'u') !== false && $filterCount == 3)){
		$onlycompfilter = '';
	}else {
		$onlycompfilter = 'INNER JOIN computer ON computer.computer_id = alert_trigger.computer_id';
	}
	
$triggered =  $dbu->query("SELECT alert.name,
									alert.alert_type,
									alert_trigger.*, 
									member.logon,
									member.alias,
									member.first_name,
									member.last_name,
									member.active,
									computer.name AS computer_name
									FROM alert_trigger
									INNER JOIN alert ON alert.alert_id = alert_trigger.alert_id
									INNER JOIN session ON session.session_id = alert_trigger.session_id
									INNER JOIN department ON department.department_id = alert_trigger.department_id
									".$onlycompfilter."
									".$app_join."
									WHERE 1=1 ".$alert_filter."
									" . $sortcolumns . " ");



$i=0;
$opts = array('','Work Schedule Alert','Idle Time Alert','Online Time Alert','Applications Alert','Monitor Alert','Website Alert','Sequence Alert');
while($triggered->next()){
	$ftx->assign(array(
		'ALERT_NAME' => $triggered->f('name'),
		'ALERT_TYPE' => $ftx->lookup($opts[$triggered->f('alert_type')]),
		'MEMBER' => trialEncrypt($triggered->f('alias') == 1 ? $triggered->f('first_name').' '.$triggered->f('last_name') : $triggered->f('logon')),
		'DEPARTMENT' => $triggered->f('department_name'),
		'DATE' => date('d/m/Y h:i A',$triggered->f('triggered_date')),
		'COMPUTER' => trialEncrypt($triggered->f('computer_name'),'comp'),
	));
	//get the rules for this alert
	switch ($triggered->f('alert_type')){
		case 1://work alert;
			$rule = $dbu->query("SELECT * FROM alert_time WHERE alert_time_id = ".$triggered->f('rule_id'));
			$rule->next();
			$ftx->assign(array(
				'RULE' => date('g:i A',$rule->f('start_time')).' - '.date('g:i A',$rule->f('end_time')),
				'DETAILS' => date('g:i A',$triggered->f('diff')).' - '.date('g:i A',$triggered->f('diff_alt')),
			));
			break;
		case 4:
			$rule = $dbu->query("SELECT alert_other.cond,application.description FROM alert_other 
								INNER JOIN application ON application.application_id = alert_other.cond_link 
								WHERE alert_other_id = ".$triggered->f('rule_id'));
			$rule->next();
			$ftx->assign(array(
				'RULE' => $rule['description'].' (<b>'.format_time_with_day($rule['cond']*60).'</b>)',
				'DETAILS' => $rule['description'].' (<b>'.format_time_with_day($triggered['diff'] + $rule['cond']*60).'</b>)',
			));
			break;
		case 6:
			$rule = $dbu->query("SELECT alert_other.cond,domain.domain FROM alert_other 
								INNER JOIN domain ON domain.domain_id = alert_other.cond_link 
								WHERE alert_other_id = ".$triggered->f('rule_id'));
			$rule->next();
			$ftx->assign(array(
				'RULE' => $rule['domain'].' (<b>'.format_time_with_day($rule['cond']*60).'</b>)',
				'DETAILS' => $rule['domain'].' (<b>'.format_time_with_day($triggered['diff'] + $rule['cond']*60).'</b>)',
			));
			break;
		case 7:
			$rule = $dbu->query("SELECT * FROM alert_other WHERE alert_other_id = ".$triggered->f('rule_id'));
			$rule->next();
			$ftx->assign(array(
				'RULE' => $rule['cond'],
				'DETAILS' => $rule['cond'] - $triggered['diff'],
			));
			break;
		default:
			$rule = $dbu->query("SELECT * FROM alert_other WHERE alert_other_id = ".$triggered->f('rule_id'));
			$rule->next();
			$ftx->assign(array(
				'RULE' => format_time_with_day($rule['cond']*60),
				'DETAILS' => format_time_with_day($triggered['diff']+$rule['cond']*60),
			));
			break;
	}
	if($i %2){
		$ftx->assign('CLASS','even');
	}else{
		$ftx->assign('CLASS','');
	}
	$ftx->parse('TEMPLATE_ROW_OUT','.template_row');
	$i++;
}
	$trial = $dbu->field("SELECT value FROM `settings` WHERE `constant_name` = 'TRUENC'");
	$ecrypted_text = $dbu->field("SELECT message FROM `notification` WHERE `constant_name` = 'ENCRYPTED_TEXT'");
	if ($trial != 2236985){
		$ftx->assign('ENCRYPTMESSAGERAW', '<div class="encryptmessage">' . $ftx->lookup($ecrypted_text) . '</div>');
	}

if($i==0)
{
	$ftx->assign(array(
		'NO_DATA_MESSAGE' => get_error($ftx->lookup('No data to display for your current filters'),'warning'),
		'HIDE_CONTENT'	=> 'hide',
	));
}
else 
{
	$ftx->assign(array(
		'NO_DATA_MESSAGE' => '',
		'HIDE_CONTENT'	=> '',
	));
}
	
	//	modified for pdf	--->
	$export_header = get_export_header($_SESSION['filters']['f']);
	extract($export_header,EXTR_OVERWRITE);
	$ftx->assign(array(
		'PDF_HEADER' => pdf_header(),
		'PDF_HIDE' => pdf_hide(),
		'PDF_CLASS' => pdf_class(),
		'TITLE' => $ftx->lookup('Alert'),
		'USER_DEPARTMENT_NAME' => trialEncrypt($member_name),
		'TIME_PERIOD' => $_SESSION['filters']['time']['time'],
	));
	//	<---	modified for pdf

$dates = $dbu->row("SELECT MIN(date) AS genesis,MAX(date) AS last_day_on_earth FROM session ");
$ftx->assign('PAG',$glob['pag']);

$ftx->assign(array(
	'DEFAULT_VALUE' => isset($glob['time']) ? $glob['time']['time']: date('n/j/Y',$dates['genesis']).' - '.date('n/j/Y',$dates['last_day_on_earth']) ,
	'DATE_BEFORE' => date('n/j/Y',$dates['genesis']),
	'TIME_'.($glob['time']['type'] ? $glob['time']['type'] : 1) => 'selected="selected"',
		'HELP_LINK' => 'help.php?pag='.str_replace("simple","",$glob['pag']),
));
global $bottom_includes;
$bottom_includes.='
<script type="text/javascript">flobn.register("genesis",new Date("'.date('n/j/Y',$dates['genesis']).'"));</script><script type="text/javascript" src="ui/triggered-ui.js"></script>';

$export_header = get_export_header($glob['f']);
extract($export_header,EXTR_OVERWRITE);
$glob['append'] = trialEncrypt($member_name);

$ftx->assign('PAGE_TITLE',$ftx->lookup('Alerts for'));
$ftx->assign('APPEND', $glob['append']);
if(!$glob['is_ajax']){
	$ftx->define_dynamic('ajax','main');
	$ftx->parse('AJAX_OUT','ajax');
}

$ftx->assign('MESSAGE',$glob['error']);
$ftx->parse('CONTENT','main');
	//	modified for pdf	--->
	if($_REQUEST['render'] == 'pdf'){
		$page = 'triggered';
		$html = $ftx->fetch('CONTENT');
			file_put_contents(CURRENT_VERSION_FOLDER.'temp_pdf/'.$page.'.html',"\xEF\xBB\xBF" . $html);
		loadPDF($page,'inline');exit;
	} else {
		return $ftx->fetch('CONTENT');
	}
	//	<---	modified for pdf