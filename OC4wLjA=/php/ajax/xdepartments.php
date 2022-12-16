<?php
header('Content-type:text/plain');
$dbu = new mysql_db();
$data = array();


function walkTree($parent_id = 0){
	global $members, $departments;
	$dbu = new mysql_db();
	$node = $dbu->query("SELECT * FROM department WHERE parent_id = ?",$parent_id);
	$data = array();
	
	while ($node->next()){
		if(in_array($_SESSION[ACCESS_LEVEL],array(2,3)) && !in_array($dbu->f('department_id'),$departments)){
			continue;
		}
		$row = array('data' => $node->f('name'),
							   'attr' => array('rel' => $node->f('parent_id') == 0 ? 'root' : 'group',
							   				   'rev' => 's'.$node->f('department_id'),
							   				   'id' => 's'. $node->f('department_id'))
		);
		//if there are members for this group we add them and then we check for children
		
		if(isset($members[$node->f('department_id')])){
			
			if(!isset($row['children'])){
				$row['children'] = array();
				$row['state'] = 'open';
			}
			$ids = array();
			for ($j =0,$children = count($members[$node->f('department_id')]); $j < $children; $j++){
				array_push($row['children'],array(
								'data' => $members[$node->f('department_id')][$j]['name'],
							   'attr' => array('rel' => '',
			   				   				   'rev' => 's'.$node->f('department_id').'-'.$members[$node->f('department_id')][$j]['id'],
			   				   				   'id' => 's'.$node->f('department_id').'-'.$members[$node->f('department_id')][$j]['id'])
				));
				array_push($ids,'s'.$node->f('department_id').'-'.$members[$node->f('department_id')][$j]['id']);
			}
			if(in_array($_SESSION['filters']['f'],$ids)){
				$row['state'] = 'open';
			}
		}

		if($node->f('rgt') - $node->f('lft') != 1){
			//we haz children...get out the cigars :D
			if(!isset($row['children'])){
				$row['children'] = array();
				$row['state'] = 'open';
			}
			$row['children'] = array_merge($row['children'],walkTree($node->f('department_id')));
		}
		
		array_push($data,$row);	
	}
	
	return $data;
}
$data = walkTree(0);
echo json_encode($data);
exit();