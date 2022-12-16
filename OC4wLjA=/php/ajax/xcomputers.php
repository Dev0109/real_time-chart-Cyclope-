<?php
header('Content-type:text/plain');
$dbu = new mysql_db();
$data = array();

$licence = get_license();
$filter = '';
switch ($_SESSION[ACCESS_LEVEL]){
	case 1:
		$filter = '';		
		break;
	case 2:
	case 3:
		$mem = array();
		$dbu->query("SELECT computer.computer_id FROM computer
					INNER JOIN computer2member ON computer2member.computer_id = computer.computer_id
					INNER JOIN member2manage ON member2manage.member_id = computer2member.member_id
					WHERE manager_id = ? ",$_SESSION[U_ID]);
		while ($dbu->move_next()){
			array_push($mem,$dbu->f('computer_id'));
		}
		$filter = ' AND computer.computer_id IN ('.join(',',$mem).')';
		break;
	case 4:
		$mem = array();
		$dbu->query("SELECT computer.computer_id FROM computer
					INNER JOIN computer2member ON computer2member.computer_id = computer.computer_id
					WHERE computer2member.member_id = ?",$_SESSION[U_ID]);
		while ($dbu->move_next()){
			array_push($mem,$dbu->f('computer_id'));
		}
		$filter = ' AND computer.computer_id IN ('.join(',',$mem).')';
		break;
}

$computers = array();
$computer = $dbu->query("SELECT 
								  computer.*,
								  COUNT(computer2member.computer_id) AS members
								  FROM computer
							      INNER JOIN computer2member ON computer2member.computer_id = computer.computer_id
							      ".$filter." " . get_licensed_computers() . "
								  GROUP BY computer.computer_id
								  ORDER BY computer.name asc LIMIT " . $licence->computers);
//get all the members and put them into an array based on the department_id
while ($computer->next()){
	if(!is_array($computers[$computer->f('department_id')])){
		$computers[$computer->f('department_id')] = array();
	}
	array_push($computers[$computer->f('department_id')],array('name' => decode_numericentity(trialEncrypt($computer->f('name'),'comp')).'('.trialEncrypt($computer->f('ip'),'ip').')',
												  'id' => $computer->f('computer_id')));
	if($computer->f('members') > 1){
		$index = count($computers[$computer->f('department_id')]) - 1;//get ze index and add the information
		$member = $dbu->query("SELECT 
								  member.*,
								  CONCAT(member.first_name,' ',member.last_name) AS name
								  FROM member
							      INNER JOIN computer2member ON computer2member.member_id = member.member_id
							      INNER JOIN computer ON computer.computer_id = computer2member.computer_id
							      WHERE computer2member.computer_id = ".$computer->f('computer_id')."
							      ".$filter."
								  GROUP BY member.member_id");
		while ($member->next()){
			if(!isset($computers[$computer->f('department_id')][$index]['children'])){
				$computers[$computer->f('department_id')][$index]['children'] = array();
				$computers[$computer->f('department_id')][$index]['state'] = 'open';
				
			}
			array_push($computers[$computer->f('department_id')][$index]['children'],array(
												  'data' =>  trialEncrypt(($member->f('alias') == 1 ? decode_numericentity($member->f('name')) : decode_numericentity($member->f('logon')))),
												  'attr' => array(
													  	'rel' => 'user',
													  	'rev' => 'c'.$member->f('department_id').'-'.$computer->f('computer_id').'-'.$member->f('member_id'),
													  	'id' => 'c'.$member->f('department_id').'-'.$computer->f('computer_id').'-'.$member->f('member_id')),
												  ));
		}
	}												  
}
//get the departments

if($_SESSION[ACCESS_LEVEL] == 4){
	//employee show only his session
	foreach ($computers as $department_id => $nodes){
		$row = array();
		foreach ($nodes  as $node){
			$row = array('data' => $node['name'],
								   'attr' => array('rel' => '',
								   				   'rev' => 'c'.$department_id.'-'.$node['id'],
								   				   'id' =>  'c'.$department_id.'-'.$node['id'])
			);
		}
		array_push($data,$row);
	}
}else{
	
	$departments = array();
	
	$sel_dep = array_keys($computers);
	
	foreach ($sel_dep as $department_id)
	{
		get_parents($department_id);
	}
	
	$data = walkTree(0);
}

function walkTree($parent_id = 0){
	global $computers, $departments;
	$dbu = new mysql_db();
	$node = $dbu->query("SELECT * FROM department WHERE parent_id = ? ORDER BY name asc",$parent_id);
	$data = array();
	while ($node->next()){
		
		if(in_array($_SESSION[ACCESS_LEVEL],array(2,3)) && !in_array($dbu->f('department_id'),$departments)){
			continue;
		}
		
		$row = array('data' => $node->f('name'),
							   'attr' => array('rel' => $node->f('parent_id') == 0 ? 'root' : 'group',
							   				   'rev' => 'c'.$node->f('department_id'),
							   				   'id' => 'c'. $node->f('department_id'))
		);
		//if there are members for this group we add them and then we check for children
		if(isset($computers[$node->f('department_id')])){
			if(!isset($row['children'])){
				$row['children'] = array();
				$row['state'] = 'open';
			}
			$ids = array();
			for ($j =0,$children = count($computers[$node->f('department_id')]); $j < $children; $j++){
				$line = array(
								'data' => $computers[$node->f('department_id')][$j]['name'],
							   'attr' => array('rel' => '',
			   				   				   'rev' => 'c'.$node->f('department_id').'-'.$computers[$node->f('department_id')][$j]['id'],
			   				   				   'id' => 'c'.$node->f('department_id').'-'.$computers[$node->f('department_id')][$j]['id'])
				);
				if(isset($computers[$node->f('department_id')][$j]['children'])){
					$line['children'] = $computers[$node->f('department_id')][$j]['children'];
					$line['state'] = 'open';
					$line['attr']['rel'] = 'computer-user';
				}
				
				array_push($row['children'],$line);
				array_push($ids,'c'.$node->f('department_id').'-'.$computers[$node->f('department_id')][$j]['id']);
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

echo json_encode($data);
exit();