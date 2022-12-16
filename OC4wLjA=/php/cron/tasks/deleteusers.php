<?php
echo "begin deleteusers\n";
set_time_limit(0);
ignore_user_abort(true);
include_once('php/gen/startup.php');
include_once(CURRENT_VERSION_FOLDER."classes/cls_member.php");

$member = new member;
$dbu = new mysql_db();

//	cron.php?force=deleteusers&name=User|Administrator
//	cron.php?force=deleteusers&inactive=1&months=3
//	cron.php?force=deleteusers&inactive=1&before=TIMESTAMP
$inactive = $_REQUEST['inactive'];
$lastrecordtodelete = $_REQUEST['before'];
$numbermonthsinactive = $_REQUEST['months'];
$name = $_REQUEST['name']; //	name1|name2|name3
$uninstall = $_REQUEST['uninstall'] ? $_REQUEST['uninstall'] : 0;
$is_test = $_REQUEST['test'];

$i = 0;
if ($inactive == 1) {
	$users_inactive = $dbu->query("SELECT member.member_id,
								  member.logon,
								  computer2member.last_record,
								  session_id,
								  computer.computer_id
								  FROM member
							INNER JOIN session ON session.member_id  = member.member_id 
							INNER JOIN computer ON computer.computer_id = session.computer_id
							INNER JOIN computer2member ON computer2member.member_id = member.member_id AND computer2member.computer_id = computer.computer_id
							GROUP BY member.member_id,computer.computer_id");
							
	/*$users_inactive = $dbu->query("SELECT member.member_id,
								  member.logon,
								  computer2member.last_record,
								  computer.computer_id
								  FROM computer2member
							
							INNER JOIN member ON computer2member.member_id = member.member_id 
							INNER JOIN computer ON computer2member.computer_id = computer.computer_id 
							WHERE computer2member.last_record < 1483221600
							GROUP BY member.member_id,computer.computer_id"); SIBIU*/
		
	$total_rows = 0;
	while ($users_inactive->next()){
		$total_rows++;
		if ($numbermonthsinactive > 0) {
			$diff = time() - $users_inactive->f('last_record');
			echo "Now:".time()."\n";
			echo "Last:".$users_inactive->f('last_record')."\n";
			echo "diff: ".$diff."\n";
			echo "------------------------------\n";
			if( $diff > ($numbermonthsinactive * 30 * 24 * 60 * 60)){
				echo $users_inactive->f('logon') . "\n";
				$userlist_to_delete[$i]['member_id'] = $users_inactive->f('member_id');
				$userlist_to_delete[$i]['computer_id'] = $users_inactive->f('computer_id');
				$i++;
			}
		} elseif ($lastrecordtodelete) {
			if(($users_inactive->f('last_record')) < $lastrecordtodelete){
				echo $users_inactive->f('logon') . "\n";
				$userlist_to_delete[$i]['member_id'] = $users_inactive->f('member_id');
				$userlist_to_delete[$i]['computer_id'] = $users_inactive->f('computer_id');
				$i++;
			}
		}
	}
	echo "Inactive COUNT:".$total_rows."\n";
}

//	name is provided
if ($name) {
	$users_by_name = $dbu->query("SELECT member.member_id,
								member.logon,
								computer.computer_id
								FROM `member`
								INNER JOIN computer2member ON computer2member.member_id = member.member_id
								INNER JOIN computer ON computer2member.computer_id = computer.computer_id
								WHERE member.logon REGEXP '" . $name . "'");
	while ($users_by_name->next()){
			echo $users_by_name->f('logon') . "\n";
			$userlist_to_delete[$i]['member_id'] = $users_by_name->f('member_id');
			$userlist_to_delete[$i]['computer_id'] = $users_by_name->f('computer_id');
			$i++;
	}
}
echo 'To Delete OR Deleted: ' . $i++ . "\n";

foreach ($userlist_to_delete as $k => $v) {
	$ld['member'] = $v['member_id'];
	$ld['computer'] = $v['computer_id'];
	$ld['uninstall'] = $uninstall;
	if (!isset($is_test)) {
		$member->delete($ld);
		echo "deleted member " . $ld['member'] . " with computer " . $ld['computer'] . "\n";
	} else {
		echo "member to delete: " . $ld['member'] . " with computer: " . $ld['computer'] . "\n";
	}
}
echo "FINISHED \n";