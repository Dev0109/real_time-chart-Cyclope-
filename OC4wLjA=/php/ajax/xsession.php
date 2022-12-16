<?php
header('Content-type:text/plain');
$dbu = new mysql_db();
$data = array();
$members = array();
$filter = '';
$licence = get_license();
switch ( $_SESSION[ACCESS_LEVEL] ) {
	case 1:
		$filter = '';		
		break;
	case 2:
	case 3:
		$mem = array();
		$dbu->query("SELECT member_id FROM member2manage WHERE manager_id = ?",$_SESSION[U_ID]);
		while ($dbu->move_next()){
			array_push($mem,$dbu->f('member_id'));
		}
		$filter = ' AND member.member_id IN ('.join(',',$mem).')';
		break;
	case 4:
		$filter = ' AND member.member_id = '.$_SESSION[U_ID];
		break;
}
$member = $dbu->query("SELECT member.*,
								  computer.name AS computer_name,
								  computer.computer_id,
								  CONCAT(member.first_name,' ',member.last_name) AS name 
								  FROM member
								  INNER JOIN computer2member ON computer2member.member_id = member.member_id
							      INNER JOIN computer ON computer.computer_id = computer2member.computer_id " . get_licensed_computers() . "
								  WHERE 1=1 AND (active != 3 OR active != 0)
								  ".$filter." ORDER BY member.logon asc");
//get all the members and put them into an array based on the department_id
while ($member->next()){
	if(!is_array($members[$member->f('department_id')])){
		$members[$member->f('department_id')] = array();
	}
	array_push($members[$member->f('department_id')],array('name' =>  ($member->f('alias') == 1 ? decode_numericentity($member->f('name')).' / '. decode_numericentity($member->f('computer_name')).'' : decode_numericentity($member->f('logon')).' / '. decode_numericentity($member->f('computer_name')).''),
												  'id' => $member->f('computer_id').'-'.$member->f('member_id')));
}
// echo '<pre>';
// print_r($members);
// echo '</pre>';
if($_SESSION[ACCESS_LEVEL] == 4){
	//employee show only his session
	foreach ($members as $department_id => $nodes){
		$row = array();
		foreach ($nodes  as $node){
			$row = array('data' => $node['name'],
								   'attr' => array('rel' => '',
								   				   'rev' => 's'.$department_id.'-'.$node['id'],
								   				   'id' =>  's'.$department_id.'-'.$node['id'])
			);
		}
		array_push($data,$row);
	}
}else{
	
	$departments = array();
	
	$sel_dep = array_keys($members);
	
	foreach ($sel_dep as $department_id)
	{
		get_parents($department_id);
	}
	//print_r($departments);
	$data = walkTree(0);
	/*echo '<pre>';
	print_r($data);
	echo '</pre>';*/
}

function walkTree($parent_id = 0){
	global $members, $departments;
	$dbu = new mysql_db();
	$node = $dbu->query("SELECT * FROM department WHERE parent_id = ? ORDER BY name asc",$parent_id);
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
								'data' => trialEncrypt($members[$node->f('department_id')][$j]['name']),
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

function get_parents($department_id)
{
	global $departments;
	array_push($departments,$department_id);
	
	$dbu = new mysql_db();
	$parent_id = $dbu->field("SELECT parent_id FROM department WHERE department_id=".$department_id);
		
	if(!$parent_id)
	{
		return;
	}
	else 
	{
		get_parents($parent_id);
	}
}


/*$bug = get_debug_instance();
echo '<pre>';
print_r($bug->display());
*/
echo json_encode($data);
exit();