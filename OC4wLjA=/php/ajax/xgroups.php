<?php
header('Content-type:text/json');
$dbu = new mysql_db();
$data = array();
$members = array();
if(!isset($glob['nomember'])){
	$member = $dbu->query("SELECT member.*,
									  computer.name AS computer_name,
									  computer.computer_id,
									  CONCAT(member.first_name,' ',member.last_name) AS name 
									  FROM member
									  INNER JOIN computer2member ON computer2member.member_id = member.member_id
								      INNER JOIN computer ON computer.computer_id = computer2member.computer_id
								      WHERE member.active > 0 AND member.active < 3
								       ORDER BY member.logon asc ");
	//get all the members and put them into an array based on the department_id
	while ($member->next()){
		if(!is_array($members[$member->f('department_id')])){
			$members[$member->f('department_id')] = array();
		}
		array_push($members[$member->f('department_id')],array('name' =>  ($member->f('alias') == 1 ? decode_numericentity($member->f('name')) : decode_numericentity($member->f('logon'))).' / '.decode_numericentity($member->f('computer_name')).'',
													  'id' => $member->f('member_id').'-'.$member->f('computer_id')));
	}
}
//get the departments
$data = walkTree(0);


function walkTree($parent_id = 0){
	global $members;
	$dbu = new mysql_db();
	$node = $dbu->query("SELECT * FROM department WHERE parent_id = ? ORDER BY name asc",$parent_id);
	$data = array();
	while ($node->next()){
		$row = array('data' => $node->f('name'),
							   'attr' => array('rel' => $node->f('parent_id') == 0 ? 'root' : 'group',
							   				   'rev' => $node->f('parent_id') == 0 ? $node->f('department_id') : $node->f('department_id').'-'.$node->f('parent_id'),
							   				   'id' => 's'. ($node->f('parent_id') == 0 ? $node->f('department_id') : $node->f('department_id').'-'.$node->f('parent_id')))
		);
		//if there are members for this group we add them and then we check for children
		if(isset($members[$node->f('department_id')])){
			if(!isset($row['children'])){
				$row['children'] = array();
				$row['state'] = 'open';
			}
			for ($j =0,$children = count($members[$node->f('department_id')]); $j < $children; $j++){
				array_push($row['children'],array(
								'data' => trialEncrypt($members[$node->f('department_id')][$j]['name']),
							   'attr' => array('rel' => '',
			   				   				   'rev' => $node->f('department_id').'-'.$members[$node->f('department_id')][$j]['id'])
				));
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

/*$bug = get_debug_instance();
print_r($bug->display());
exit();*/
echo json_encode($data);
exit();

