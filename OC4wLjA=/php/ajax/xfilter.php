<?php
$dbu = new mysql_db();
//search session first
$data = array();
$filter = '';
$filter_type = $glob['type'] ? $glob['type'] : 0;

switch ($_SESSION[ACCESS_LEVEL]){
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

switch ($filter_type){
	case 1:
		$dbu->query("SELECT member.*,
						  department.department_id
						  FROM member
						  INNER JOIN department ON department.department_id = member.department_id
						  WHERE (first_name LIKE '%".$glob['term']."%' OR
												 last_name LIKE '%".$glob['term']."%' OR
												 member.logon LIKE '%".$glob['term']."%'
												 )
											".$filter);
		while ($dbu->move_next()){
			array_push($data,array(
				'label' => $dbu->f('alias') == 1 ? $dbu->f('first_name').' '.$dbu->f('last_name') : $dbu->f('logon'),
				'category' => 'Users',
				'data' => 'u'.$dbu->f('department_id').'-'.$dbu->f('member_id')
			));
		}
		break;
	case 2:	
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
							WHERE manager_id = ?",$_SESSION[U_ID]);
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
		$dbu->query("SELECT 
						  computer.computer_id,
						  computer.name,
						  computer.ip,
						  department.department_id
						  FROM computer
						  INNER JOIN department ON department.department_id = computer.department_id
						  WHERE computer.name LIKE '%".$glob['term']."%'
						  ".$filter);
		while ($dbu->move_next()){
			array_push($data,array(
			'label' => $dbu->f('name').' ('.$dbu->f('ip').')',
			'category' => 'Computers',
			'data' => 'c'.$dbu->f('department_id').'-'.$dbu->f('computer_id'),
			));
		}
		break;
	default:
		$dbu->query("SELECT member.logon,
							member.alias,
						  member.member_id,
						  CONCAT(member.first_name,' ',member.last_name) AS name,
						  computer.computer_id,
						  computer.name AS computer_name,
						  department.department_id
						  FROM member
						  INNER JOIN department ON department.department_id = member.department_id
						  INNER JOIN computer2member ON computer2member.member_id = member.member_id
					      INNER JOIN computer ON computer.computer_id = computer2member.computer_id
						  WHERE (member.first_name LIKE '%".$glob['term']."%' OR
												 member.last_name LIKE '%".$glob['term']."%' OR
												 member.logon LIKE '%".$glob['term']."%'
												 )
						  ".$filter."");
		while ($dbu->move_next()){
			array_push($data,array(
			'label' => ($dbu->f('alias') == 1 ? $dbu->f('name') : $dbu->f('logon')).'('.$dbu->f('computer_name').')',
			'category' => 'Sessions',
			'data' => 's'.$dbu->f('department_id').'-'.$dbu->f('computer_id').'-'.$dbu->f('member_id'),
			));
		}
		break;		
}
echo json_encode($data);
exit();