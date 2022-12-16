<?php
header('Content-type:text/plain');
$dbu = new mysql_db();
$data = array();

//get the categories
$data = walkTree(0);


function walkTree($parent_id = 0){
	global $members;
	$dbu = new mysql_db();
	$node = $dbu->query("SELECT * FROM application_category WHERE parent_id = ?",$parent_id);
	$data = array();
	$l = new LanguageParser();
	while ($node->next()){
				$row = array('data' => decode_numericentity($node->f('category')=="Uncategorised"?$l->lookup("Uncategorised"):$l->lookup($node->f('category'))),
							   'attr' => array('rel' => $node->f('parent_id') == 0 ? 'root' : 'group',
							   				   'rev' => $node->f('application_category_id'),
							   				   'id' => 'cat'.$node->f('application_category_id'))
		);
		if($node->f('rgt') - $node->f('lft') != 1){
			//we haz children...get out the cigars :D
			if(!isset($row['children'])){
				$row['children'] = array();
				$row['state'] = 'open';
			}
			$row['children'] = array_merge($row['children'],walkTree($node->f('application_category_id')));
		}
		array_push($data,$row);	
	}
	return $data;
}
echo json_encode($data);
exit();

