<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftinst=new ft(ADMIN_PATH.MODULE."templates/");
$ftinst->define(array('main' => 'universalsearch.html'));
set_time_limit(0);

$filters = get_filters($glob['t'],$glob['f'],$glob['time']);
extract($filters,EXTR_OVERWRITE);
$filter = '';
if(isset($glob['search_key'])){
	$glob['search_key'] = filter_var($glob['search_key'],FILTER_SANITIZE_STRING);
	$filter = " LIKE '%".encode_numericentity($glob['search_key']) ."%'";
	file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log',encode_numericentity($glob['search_key'])."\n",FILE_APPEND);
}

if ($_POST['internet']){
	$ftinst->assign(array(
		'INTERNET_CHECKED'		  => 'checked=checked',
		'INTERNET_HIDDEN'		  => ''));
} else {
	$ftinst->assign(array('INTERNET_HIDDEN'		  => 'style="display:none;"'));
}
if (strpos($glob['f'],'-') !== false){
	// user selected
	$globarray = explode('-',$glob['f']);
	$uid = $globarray[2];
	$userfilter_querypart = ' AND member.member_id = ' . $uid;
} else {
	// department selected
	if (!$glob['f']) {$glob['f'] = 's1';}
	$mdepid = filter_var($glob['f'], FILTER_SANITIZE_NUMBER_INT);
	
			$nodeInfo = $dbu->query('SELECT `lft` , `rgt` FROM `department` WHERE `department_id` = ' . $mdepid);
			if($nodeInfo->next()){
					$mdep['lft'] = $nodeInfo->f('lft');
					$mdep['rgt'] = $nodeInfo->f('rgt');
			}

	$dept_list = array();
	$query = $dbu->query("SELECT department_id FROM department WHERE lft BETWEEN ".$mdep['lft'].' AND '.$mdep['rgt']);
	while ($query->next()){
		array_push($dept_list,$query->f('department_id'));
	}
	
	$userfilter_querypart = ' AND member.department_id IN (' . implode(',',$dept_list) . ')';
}

$dbu = new mysql_db();
if ($_POST['member']){
	$ftinst->assign(array(
		'MEMBER_CHECKED'		  => 'checked=checked',
		'MEMBER_HIDDEN'		  => ''));
	$count['member']=0;
	$dbu->query("SELECT COUNT(DISTINCT member.member_id) AS count
		FROM member
		INNER JOIN session ON member.member_id = session.member_id 
		WHERE 	((member.logon ".$filter.") 
				OR (member.last_name ".$filter."))  ".$userfilter_querypart." "
				.clean_filter($time_filter));
						
	while($dbu->move_next()){
		$count['name'] += $dbu->f('count');
	}
	$view_child = '<a id="search_link" href="index_ajax.php?pag=xuniversalsearchdetailsuser&searchtype=1">&nbsp;</a>';
	switch ($count['name']){
		case 0: $result = $ftinst->lookup("No results");
							break;
		case 1: $result = $ftinst->lookup("Distinct Result");
							break;
		default: $result = $ftinst->lookup("Distinct Results");
							break;
	}
	$ftinst->assign(array(
			'RESULT_NAME'		  => $result,
			'NAME_COUNT'  	 	  => $count['name']==0? $count['name']='': $count['name'],	
			'NAME_VIEW_CHILD'     => $count['name']==0? '':$view_child,
			'NAME_CLASS'          => $class,
			'FLAG_NAME'		      => $count['name'] < 4? $ftinst->lookup('(To few or no results? Try changing the search time interval.)'): '',
	));
} else {
	$ftinst->assign(array('MEMBER_HIDDEN'		  => 'style="display:none;"'));
}

if ($_POST['computer']){
	$ftinst->assign(array(
		'COMPUTER_CHECKED'		  => 'checked=checked',
		'COMPUTER_HIDDEN'		  => ''));
	$count['computer']=0;
	$dbu->query("SELECT COUNT(DISTINCT computer.computer_id) AS count 
							FROM computer 
							INNER JOIN session ON computer.computer_id = session.computer_id
							WHERE computer.name ".$filter
							.clean_filter($time_filter));
							
	while($dbu->move_next()){
		$count['computer'] += $dbu->f('count');
	}	
	$view_child = '<a id="search_link" href="index_ajax.php?pag=xuniversalsearchdetailsuser&searchtype=2">&nbsp;</a>';	
	switch ($count['computer']){
		case 0: $result = $ftinst->lookup("No results");
							break;
		case 1: $result = $ftinst->lookup("Distinct Result");
							break;
		default: $result = $ftinst->lookup("Distinct Results");
							break;
	}				
	$ftinst->assign(array(
			'RESULT_COMPUTER'			  	  => $result,
			'COMPUTER_COUNT' 		  => $count['computer']==0? $count['computer']='': $count['computer'],	
			'COMPUTER_VIEW_CHILD'     => $count['computer']==0? '':$view_child,
			'COMPUTER_CLASS'          => $class,
			'FLAG_COMPUTER'			  => $count['computer'] < 4? $ftinst->lookup('(To few or no results? Try changing the search time interval.)'): '',
	));
} else {
	$ftinst->assign(array('COMPUTER_HIDDEN'		  => 'style="display:none;"'));
}

if ($_POST['internet']){
	$ftinst->assign(array(
		'INTERNET_CHECKED'		  => 'checked=checked',
		'INTERNET_HIDDEN'		  => ''));
	$count['internet']=0;
	$dbu->query("	SELECT COUNT(DISTINCT domain.domain_id) AS count
							FROM session_website
							INNER JOIN domain ON domain.domain_id = session_website.domain_id
							INNER JOIN website ON website.website_id = session_website.website_id AND website.domain_id = domain.domain_id
							INNER JOIN session ON session.session_id = session_website.session_id
							INNER JOIN member ON member.member_id = session.member_id
							WHERE 1=1 ".$userfilter_querypart." ".clean_filter($time_filter)." 
							AND session_website.application_id = website.application_id AND domain.domain ".$filter." 
							GROUP BY member.member_id,domain.domain_id
							ORDER BY member.member_id ");

	while($dbu->move_next()){
		$count['internet'] += $dbu->f('count');
	}
	$view_child = '<a id="search_link" href="index_ajax.php?pag=xuniversalsearchdetailsactivity&searchtype=3">&nbsp;</a>';
	switch ($count['internet']){
		case 0: $result = $ftinst->lookup("No results");
							break;
		case 1: $result = $ftinst->lookup("Distinct Result");
							break;
		default: $result = $ftinst->lookup("Distinct Results");
							break;
	}
	$ftinst->assign(array(
			'RESULT_INTERNET'		  => $result,
			'INTERNET_COUNT' 		  => $count['internet']==0? $count['internet']='': $count['internet'],	
			'INTERNET_VIEW_CHILD'     => $count['internet']==0? '':$view_child,
			'INTERNET_CLASS'          => $class,
			'FLAG_INTERNET'			  => $count['internet'] < 4? $ftinst->lookup('(To few or no results? Try changing the search time interval.)'): '',
	));
} else {
	$ftinst->assign(array('INTERNET_HIDDEN'		  => 'style="display:none;"'));
}

if ($_POST['chat']){
	$ftinst->assign(array(
		'CHAT_CHECKED'		  => 'checked=checked',
		'CHAT_HIDDEN'		  => ''));
	$count['chat']=0;
	$dbu ->query("SELECT COUNT(DISTINCT chat.name)AS count
						FROM chat
						INNER JOIN session_chat ON session_chat.chat_id = chat.chat_id
						INNER JOIN session ON session.session_id = session_chat.session_id
						INNER JOIN member ON member.member_id = session.member_id
						WHERE 1=1 ".$userfilter_querypart." ".clean_filter($time_filter)." AND session_chat.application_id = chat.application_id AND chat.name ".$filter." 
						GROUP BY member.member_id,chat.name
						ORDER BY member.member_id ");

	while($dbu->move_next()){
	$count['chat'] += $dbu->f('count');
	}
	$view_child = '<a id="search_link" href="index_ajax.php?pag=xuniversalsearchdetailsactivity&searchtype=4">&nbsp;</a>';
	switch ($count['chat']){
		case 0: $result = $ftinst->lookup("No results");
							break;
		case 1: $result = $ftinst->lookup("Distinct Result");
							break;
		default: $result = $ftinst->lookup("Distinct Results");
							break;
	}
	$ftinst->assign(array(
		'RESULT_CHAT'		  => $result,
		'CHAT_COUNT'    	  => $count['chat']==0? $count['chat']='': $count['chat'],	
		'CHAT_VIEW_CHILD'     => $count['chat']==0? '':$view_child,
		'CHAT_CLASS'          => $class,
		'FLAG_CHAT'			  => $count['chat'] < 4? $ftinst->lookup('(To few or no results? Try changing the search time interval.)'): '',
	));			
} else {
	$ftinst->assign(array('CHAT_HIDDEN'		  => 'style="display:none;"'));
}

if ($_POST['document']){
	$ftinst->assign(array(
		'DOCUMENT_CHECKED'		  => 'checked=checked',
		'DOCUMENT_HIDDEN'		  => ''));
	$count['document']=0;
	$dbu ->query("SELECT COUNT(DISTINCT document.name)AS count
						FROM document
						INNER JOIN session_document ON session_document.document_id = document.document_id
						INNER JOIN session ON session.session_id = session_document.session_id
						INNER JOIN member ON member.member_id = session.member_id
						WHERE 1=1 ".$userfilter_querypart." ".clean_filter($time_filter)." AND session_document.application_id = document.application_id AND document.name ".$filter."  
						GROUP BY member.member_id,document.name
						ORDER BY member.member_id ");

	while($dbu->move_next()){
		$count['document'] += $dbu->f('count');
	}
	$view_child = '<a id="search_link" href="index_ajax.php?pag=xuniversalsearchdetailsactivity&searchtype=5">&nbsp;</a>';
	switch ($count['document']){
		case 0: $result = $ftinst->lookup("No results");
							break;
		case 1: $result = $ftinst->lookup("Distinct Result");
							break;
		default: $result = $ftinst->lookup("Distinct Results");
							break;
	}
	$ftinst->assign(array(
			'RESULT_DOCUMENT'		  => $result,
			'DOCUMENT_COUNT'		  => $count['document']==0? $count['document']='': $count['document'],	
			'DOCUMENT_VIEW_CHILD'     => $count['document']==0? '':$view_child,
			'DOCUMENT_CLASS'          => $class,
			'FLAG_DOCUMENT'			  => $count['document'] < 4? $ftinst->lookup('(To few or no results? Try changing the search time interval.)'): '',
	));			
} else {
	$ftinst->assign(array('DOCUMENT_HIDDEN'		  => 'style="display:none;"'));
}

if ($_POST['file']){
	$ftinst->assign(array(
		'FILE_CHECKED'		  => 'checked=checked',
		'FILE_HIDDEN'		  => ''));
	
	$count['file']=0;
	$dbu->query("SELECT COUNT( DISTINCT file.file_id)AS count
	  FROM session_file
	INNER JOIN file ON file.file_id = session_file.file_id
	INNER JOIN session ON session.session_id = session_file.session_id
	INNER JOIN member ON member.member_id = session.member_id
	WHERE 1=1 ".$userfilter_querypart." AND session_file.time_type = 0 ".clean_filter($time_filter)." AND SUBSTRING_INDEX(file.path, '&#092;&#092;' , -1)".$filter."
	GROUP by member.member_id,file.path
	ORDER BY member.member_id");

	while($dbu->move_next()){
		$count['file'] += $dbu->f('count');
	}
	$view_child = '<a id="search_link" href="index_ajax.php?pag=xuniversalsearchdetailsactivity&searchtype=7">&nbsp;</a>';
	switch ($count['file']){
		case 0: $result = $ftinst->lookup("No results");
							break;
		case 1: $result = $ftinst->lookup("Distinct Result");
							break;
		default: $result = $ftinst->lookup("Distinct Results");
							break;
	}
	$ftinst->assign(array(
			'RESULT_FILE'		  => $result,
			'FILE_COUNT'		  => $count['file']==0? $count['file']='': $count['file'],	
			'FILE_VIEW_CHILD'     => $count['file']==0? '':$view_child,
			'FILE_CLASS'          => $class,
			'FLAG_FILE'			  => $count['file'] < 4? $ftinst->lookup('(To few or no results? Try changing the search time interval.)'): '',
	));	
} else {
	$ftinst->assign(array('FILE_HIDDEN'		  => 'style="display:none;"'));
}

if ($_POST['print']){
	$ftinst->assign(array(
		'PRINT_CHECKED'		  => 'checked=checked',
		'PRINT_HIDDEN'		  => ''));
	
	$count['print']=0;
	$dbu->query("SELECT COUNT( DISTINCT fileprint.file_id)AS count
	  FROM session_print
	INNER JOIN fileprint ON fileprint.file_id = session_print.file_id
	INNER JOIN session ON session.session_id = session_print.session_id
	INNER JOIN member ON member.member_id = session.member_id
	WHERE 1=1 ".$userfilter_querypart." AND session_print.time_type = 0 ".clean_filter($time_filter)." AND SUBSTRING_INDEX(fileprint.path, '&#092;&#092;' , -1)".$filter."
	GROUP by member.member_id,fileprint.path
	ORDER BY member.member_id");

	while($dbu->move_next()){
		$count['print'] += $dbu->f('count');
	}
	$view_child = '<a id="search_link" href="index_ajax.php?pag=xuniversalsearchdetailsactivity&searchtype=9">&nbsp;</a>';
	switch ($count['print']){
		case 0: $result = $ftinst->lookup("No results");
							break;
		case 1: $result = $ftinst->lookup("Distinct Result");
							break;
		default: $result = $ftinst->lookup("Distinct Results");
							break;
	}
	$ftinst->assign(array(
			'RESULT_PRINT'		  => $result,
			'PRINT_COUNT'		  => $count['print']==0? $count['print']='': $count['print'],	
			'PRINT_VIEW_CHILD'     => $count['print']==0? '':$view_child,
			'PRINT_CLASS'          => $class,
			'FLAG_PRINT'			  => $count['print'] < 4? $ftinst->lookup('(To few or no results? Try changing the search time interval.)'): '',
	));	
} else {
	$ftinst->assign(array('PRINT_HIDDEN'		  => 'style="display:none;"'));
}

if ($_POST['application']){
	$ftinst->assign(array(
		'APPLICATION_CHECKED'		  => 'checked=checked',
		'APPLICATION_HIDDEN'		  => ''));

	$count['application']=0;
	$dbu ->query("SELECT COUNT( DISTINCT application.description)AS count
								FROM session_application
								INNER JOIN application ON application.application_id = session_application.application_id
								INNER JOIN session ON session.session_id = session_application.session_id
								INNER JOIN member ON member.member_id = session.member_id
						WHERE 1=1  ".$userfilter_querypart." ".clean_filter($time_filter)." AND session_application.application_id = application.application_id AND application.description ".$filter."
						 GROUP BY member.member_id,application.description
						ORDER BY member.member_id ");

						
	while($dbu->move_next()){
		$count['application'] += $dbu->f('count');
	}
	$view_child = '<a id="search_link" href="index_ajax.php?pag=xuniversalsearchdetailsactivity&searchtype=6">&nbsp;</a>';
	switch ($count['application']){
		case 0: $result = $ftinst->lookup("No results");
							break;
		case 1: $result = $ftinst->lookup("Distinct Result");
							break;
		default: $result = $ftinst->lookup("Distinct Results");
							break;
	}
	$ftinst->assign(array(
			'RESULT_APPLICATION'		 => $result,
			'APPLICATION_COUNT' 		 => $count['application']==0? $count['application']='': $count['application'],	
			'APPLICATION_VIEW_CHILD'     => $count['application']==0? '':$view_child,
			'APPLICATION_CLASS'          => $class,
			'FLAG_APPLICATION'			 => $count['application'] < 4? $ftinst->lookup('(To few or no results? Try changing the search time interval.)'): '',
	));
} else {
	$ftinst->assign(array('APPLICATION_HIDDEN'		  => 'style="display:none;"'));
}

if ($_POST['window']){
	$ftinst->assign(array(
		'WINDOW_CHECKED'		  => 'checked=checked',
		'WINDOW_HIDDEN'		  => ''));
	$count['window']=0;
	$dbu ->query("SELECT COUNT( DISTINCT window.name)AS count
								FROM session_window
								INNER JOIN window ON window.window_id = session_window.window_id
								INNER JOIN session ON session.session_id = session_window.session_id
								INNER JOIN member ON member.member_id = session.member_id
						WHERE 1=1 ".$userfilter_querypart." ".clean_filter($time_filter)." AND session_window.window_id = window.window_id AND window.name ".$filter."
						 GROUP BY member.member_id,window.name
						ORDER BY member.member_id ");

						
	while($dbu->move_next()){
		$count['window'] += $dbu->f('count');
	}
	$view_child = '<a id="search_link" href="index_ajax.php?pag=xuniversalsearchdetailsactivity&searchtype=8">&nbsp;</a>';
	switch ($count['window']){
		case 0: $result = $ftinst->lookup("No results");
							break;
		case 1: $result = $ftinst->lookup("Distinct Result");
							break;
		default: $result = $ftinst->lookup("Distinct Results");
							break;
	}
	$ftinst->assign(array(
			'RESULT_WINDOW'		 => $result,
			'WINDOW_COUNT' 		 => $count['window']==0? $count['window']='': $count['window'],	
			'WINDOW_VIEW_CHILD'     => $count['window']==0? '':$view_child,
			'WINDOW_CLASS'          => $class,
			'FLAG_WINDOW'			 => $count['window'] < 4? $ftinst->lookup('(To few or no results? Try changing the search time interval.)'): '',
	));
} else {
	$ftinst->assign(array('WINDOW_HIDDEN'		  => 'style="display:none;"'));
}

$dates = $dbu->row("SELECT MIN(date) AS genesis,MAX(date) AS last_day_on_earth FROM session ");
if ($glob['search_key']){
	$ftinst->assign(array(
			'SEARCH_KEY'  	=> " " . $ftinst->lookup("for") . ' "' . $glob['search_key'] . '"',
	));
}
$ftinst->assign(array(
		'DEFAULT_VALUE' => isset($glob['time']) ? $glob['time']['time'] : date('n/j/Y',$dates['genesis']).' - '.date('n/j/Y',$dates['last_day_on_earth']),
		'DATE_BEFORE' => date('n/j/Y',$dates['genesis']),
));
global $bottom_includes;
$bottom_includes .= '<script type="text/javascript">flobn.register("search_key","'.$glob['search_key'].'");flobn.register("genesis",new Date("'.date('n/j/Y',$dates['genesis']).'"));</script><script type="text/javascript" src="'.CURRENT_VERSION_FOLDER.'ui/universalsearch-ui.js"></script>';
if(!$glob['is_ajax']){
	$ftinst->define_dynamic('ajax','main');
	$ftinst->parse('AJAX_OUT','ajax');
}
$ftinst->parse('CONTENT','main');
return $ftinst->fetch('CONTENT');