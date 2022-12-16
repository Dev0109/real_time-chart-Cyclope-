<?php
/*[ { "id": "Dromas ardeola", "label": "Crab-Plover", "value": "Crab-Plover" }, { "id": "Larus sabini", "label": "Sabine`s Gull", "value": "Sabine`s Gull" }, { "id": "Vanellus gregarius", "label": "Sociable Lapwing", "value": "Sociable Lapwing" }, { "id": "Oenanthe isabellina", "label": "Isabelline Wheatear", "value": "Isabelline Wheatear" } ]*/
$data = array();
if(!isset($glob['term'])){
	echo '[]';
	return ;
}

$dbu = new mysql_db();
$dbu->query("SELECT * FROM application WHERE description LIKE '".$glob['term']."%'");

while ($dbu->move_next()){
	array_push($data,array(
		'id' => $dbu->f('application_id'),
		'label' => $dbu->f('description'),
		'value' => $dbu->f('description')
	));
}
echo json_encode($data);
exit();