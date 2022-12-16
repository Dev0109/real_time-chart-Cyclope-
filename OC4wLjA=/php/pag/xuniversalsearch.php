<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/

$ftinst=new ft(ADMIN_PATH.MODULE."templates/");
$ftinst->define(array('main' => 'xuniversalsearch.html'));
$filters = get_filters($glob['t'],$glob['f'],$glob['time']);
extract($filters,EXTR_OVERWRITE);
$filter = '';
if(isset($glob['search_key'])){
	$glob['search_key'] = filter_var($glob['search_key'],FILTER_SANITIZE_STRING);
	$filter = " LIKE '%".$glob['search_key']."%'";
	
}

//file_put_contents(CURRENT_VERSION_FOLDER.'logs/log.log'.$tbl,$app_filter."\n",FILE_APPEND);
$dbu = new mysql_db();
$count['member']=0;
$dbu->query("SELECT COUNT(member.member_id) AS count
	FROM member
	INNER JOIN session ON member.member_id = session.member_id 
	WHERE 	((member.logon ".$filter.") 
			OR (member.first_name ".$filter.") 
			OR (member.last_name ".$filter."))"
			.$time_filter);

while($dbu->move_next()){
	$count['name'] += $dbu->f('count');
}
			
			
$count['computer']=0;
$dbu->query("SELECT COUNT(computer.computer_id) AS count 
						FROM computer 
						INNER JOIN session ON computer.computer_id = session.computer_id
						WHERE computer.name ".$filter
						.$time_filter);
while($dbu->move_next()){
	$count['computer'] += $dbu->f('count');
}						
						
$count['internet']=0;
$dbu->query("	SELECT COUNT(DISTINCT domain.domain_id) AS count
						FROM session_website
						INNER JOIN domain ON domain.domain_id = session_website.domain_id
						INNER JOIN website ON website.website_id = session_website.website_id AND website.domain_id = domain.domain_id
						INNER JOIN session ON session.session_id = session_website.session_id
						INNER JOIN member ON member.member_id = session.member_id
						WHERE 1=1 ".$time_filter." 
						AND session_website.application_id = website.application_id AND domain.domain ".$filter." 
						GROUP BY member.member_id,domain.domain_id
						ORDER BY member.member_id ");
while($dbu->move_next()){
	$count['internet'] += $dbu->f('count');
}

$count['chat']=0;
$dbu ->query("SELECT COUNT(DISTINCT chat.name)AS count
					FROM chat
					INNER JOIN session_chat ON session_chat.chat_id = chat.chat_id
					INNER JOIN session ON session.session_id = session_chat.session_id
					INNER JOIN member ON member.member_id = session.member_id
					WHERE 1=1 ".$time_filter." AND session_chat.application_id = chat.application_id AND chat.name ".$filter." 
					GROUP BY member.member_id,chat.name
					ORDER BY member.member_id ");
while($dbu->move_next()){
	$count['chat'] += $dbu->f('count');
}
				
$count['document']=0;
$dbu ->query("SELECT COUNT(DISTINCT document.name)AS count
					FROM document
					INNER JOIN session_document ON session_document.document_id = document.document_id
					INNER JOIN session ON session.session_id = session_document.session_id
					INNER JOIN member ON member.member_id = session.member_id
					WHERE 1=1 ".$time_filter." AND session_document.application_id = document.application_id AND document.name ".$filter."  
					GROUP BY member.member_id,document.name
					ORDER BY member.member_id ");
while($dbu->move_next()){
	$count['document'] += $dbu->f('count');
}

$count['application']=0;
$dbu ->query("SELECT COUNT( DISTINCT application.description)AS count
							FROM session_application
							INNER JOIN application ON application.application_id = session_application.application_id
							INNER JOIN session ON session.session_id = session_application.session_id
							INNER JOIN member ON member.member_id = session.member_id
					WHERE 1=1 ".$time_filter." AND session_application.application_id = application.application_id AND application.description ".$filter."
					 GROUP BY member.member_id,application.description
					ORDER BY member.member_id ");
while($dbu->move_next()){
	$count['application'] += $dbu->f('count');
}

$ftinst->assign(array(
		'NAME_COUNT'  		=> $count['name'],
		'COMPUTER_COUNT'	=> $count['computer'],
		'CHAT_COUNT'    	=> $count['chat'],
		'INTERNET_COUNT'    => $count['internet'],
		'DOCUMENT_COUNT'	=> $count['document'],
		'APPLICATION_COUNT' => $count['application'],
));


$ftinst->parse('CONTENT','main');
return $ftinst->fetch('CONTENT');