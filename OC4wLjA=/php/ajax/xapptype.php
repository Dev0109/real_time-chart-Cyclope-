<?php
header('Content-type:text/plain');

$l = new LanguageParser();

$data = array();

$options = array(
	'0' => $l->lookup('Application Forms'),
	'1' => $l->lookup('Chat Monitoring'),
	'2' => $l->lookup( 'Document Monitoring'),
	'3' => $l->lookup('Internet Activity')
);

foreach ($options as $type_id => $app_type)
{
	$row = array('data' => $app_type,
							   'attr' => array('rel' => 'root',
							   				   'rev' => $type_id,
							   				   'id' => 'cat'.$type_id
							   				   )
		   );
		   
	array_push($data,$row);	
}

echo json_encode($data);
exit();