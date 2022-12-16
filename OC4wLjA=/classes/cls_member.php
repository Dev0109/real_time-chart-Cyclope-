<?php
/************************************************************************
* @Author: MedeeaWeb Works
***********************************************************************/
class member
{
	var $dbu;
	
	function member()
	{
		$this->dbu = new mysql_db();
	}
	
	/****************************************************************
	* function add(&$ld)                                            *
	****************************************************************/
	function add(&$ld)
	{
		if(!$this->add_validate($ld))
		{
			return false;
		}
		$ld['first_name'] = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $ld['first_name']);
		$ld['last_name'] = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $ld['last_name']);
		// Armageddon is not around the corner. 
		// This is only what the people of violence want us to believe. 
		// The complexity and diversity of the world is the hope for the future.
		$get_ad = $this->dbu->field("SELECT `ad` FROM `member` WHERE `logon` = '".$ld['username']."'");
		switch ($ld['access_level']){
			case 1:
			case 1.5:
				//insert admin
				$ld['mid'] = $this->dbu->query_get_id("INSERT INTO member SET 
														department_id = 0,
														first_name = '".$ld['first_name']."',
														last_name = '".$ld['last_name']."',
														username = '".$ld['username']."',
														password = '".$ld['password']."',
														email = '".$ld['email']."',
														access_level = ".$ld['access_level'].",
														ad = '".$get_ad."',
														active = '".$ld['active']."'");
				break;
			case 2:
			case 3:
				//clear all
				$ld['mid'] = $this->dbu->query_get_id("INSERT INTO member SET 
													department_id = 0,
													first_name = '".$ld['first_name']."',
													last_name = '".$ld['last_name']."',
													username = '".$ld['username']."',
													password = '".$ld['password']."',
													email = '".$ld['email']."',
													access_level = '".$ld['access_level']."',
														ad = '".$get_ad."',
													active = '".$ld['active']."'");
				$this->dbu->query("DELETE FROM `member2manage` WHERE `manager_id` = ".$ld['mid']);
				//also add it into the right place
				foreach ($ld['monitored_group'] as $group){
				//	create department list for managers
					if(strpos($group,'-') === false ){
						$this->dbu->query("INSERT INTO member2manage2dep SET department_id = '".filter_var($group, FILTER_SANITIZE_NUMBER_INT)."',
																	member_id = '".$ld['mid']."'");
						$memberlist = 	$this->dbu->query("SELECT `member_id` FROM `member` WHERE `department_id` = ".filter_var($group, FILTER_SANITIZE_NUMBER_INT)."");
						while ($memberlist->next()) {
							$this->dbu->query("INSERT INTO member2manage SET member_id = '".$memberlist->f('member_id')."',
																	manager_id = '".$ld['mid']."'");
						}
					}
					$pieces = explode('-',$group);
					if(count($pieces) == 1){
						continue;//we don't need departments because we are member based
					}
					$member_id = end($pieces);
					$this->dbu->query("INSERT INTO member2manage SET member_id = '".$member_id."',
																	manager_id = '".$ld['mid']."'");
				}
				break;
			case 4://this one is interesting
				$ld['mid'] = $ld['monitored'];
				$this->dbu->query("UPDATE member SET first_name = '".$ld['first_name']."',
													last_name = '".$ld['last_name']."',
													username = '".$ld['username']."',
													password = '".$ld['password']."',
													email = '".$ld['email']."',
													active = '".$ld['active']."',
														ad = '".$get_ad."',
													alias = '".$ld['alias']."'
													WHERE member_id = '".$ld['mid']."'");
				break;
		}
		//Done
		$ld['error']='Member has been added successfully.<br>';
		$ld['pag']='member';
		return true;

	}

	/****************************************************************
	* function update(&$ld)                                         *
	****************************************************************/
	function update(&$ld)
	{

		if ($ld['prefilled']){
			$ld['username'] = $ld['username'] ? $ld['username'] : $ld['membername'];
			$ld['password'] = $ld['password'] ? $ld['password'] : rand_string( 8 );
			$ld['password2'] = $ld['password2'] ? $ld['password2'] : $ld['password'];
			$ld['active'] = $ld['activeold'];
			if (strpos($ld['username'], '.rename') === false) {
				$ld['username'] = $ld['username'] . '.rename';
			}
		}
		$get_ad = $this->dbu->field("SELECT `ad` FROM `member` WHERE `logon` = '".$ld['username']."'");
		if(!$this->update_validate($ld))
		{
			return false;
		}
		$ld['first_name'] = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $ld['first_name']);
		$ld['last_name'] = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $ld['last_name']);
		//need to figure out what is happening or as the poet once said: Ce se intampla aici?
		$memberInfo = $this->dbu->row("SELECT * FROM member WHERE member_id = ?",$ld['mid']);
		if($ld['access_level'] != $memberInfo['access_level']){
			//need to figure out what we are turning into
			if($ld['access_level'] == EMPLOYEE_LEVEL && $memberInfo['access_level'] < EMPLOYEE_LEVEL){
				//turning a manager into an employee
				//so we delete the manager from the table, clean his employees and then
				$this->dbu->query("DELETE FROM member2manage WHERE manager_id = '".$ld['mid']."'");
				$this->dbu->query("DELETE FROM member WHERE member_id = '".$ld['mid']."'");
				$ld['mid'] = $ld['monitored'];
			}elseif($ld['access_level'] < EMPLOYEE_LEVEL && $memberInfo['access_level'] == EMPLOYEE_LEVEL){
				//turning an employee into manager
				//so we delete the manager from the table, clean his employees and then
				$this->dbu->query("UPDATE member SET first_name = '',
														last_name = '',
														username = '',
														password = '',
														email = '',
														access_level = 4,
														active = '1',
														alias = '0'
														WHERE member_id = '".$ld['mid']."'");
				$ld['mid'] = $this->dbu->query_get_id("INSERT INTO member SET 
													department_id = 0,
													first_name = '".$ld['first_name']."',
													last_name = '".$ld['last_name']."',
													username = '".$ld['username']."',
													password = '".$ld['password']."',
													email = '".$ld['email']."',
													access_level = '".$ld['access_level']."',
													alias = '0',
														ad = '".$get_ad."',
													active = '".$ld['active']."'");				
			}
		}
		// Armageddon is not around the corner. 
		// This is only what the people of violence want us to believe. 
		// The complexity and diversity of the world is the hope for the future.
		switch ($ld['access_level']){
			case 1:
			case 1.5:
				//insert admin
				$this->dbu->query("UPDATE member SET 
														department_id = 0,
														first_name = '".$ld['first_name']."',
														last_name = '".$ld['last_name']."',
														username = '".$ld['username']."',
														password = '".$ld['password']."',
														email = '".$ld['email']."',
														access_level = '".$ld['access_level']."',
														active = '".$ld['active']."',
														ad = '".$get_ad."',
														alias = '0'
														WHERE member_id = '".$ld['mid']."'");
				break;
			case 2:
			case 3:
				//clear all
				$this->dbu->query("DELETE FROM member2manage WHERE manager_id = '".$ld['mid']."'");
				$this->dbu->query("DELETE FROM member2manage2dep WHERE member_id = '".$ld['mid']."'");
				$this->dbu->query_get_id("UPDATE member SET 
													department_id = 0,
													first_name = '".$ld['first_name']."',
													last_name = '".$ld['last_name']."',
													username = '".$ld['username']."',
													password = '".$ld['password']."',
													email = '".$ld['email']."',
													access_level = '".$ld['access_level']."',
													active = '".$ld['active']."',
														ad = '".$get_ad."',
													alias = '0'
													WHERE member_id = '".$ld['mid']."'");
				//also add it into the right place
				foreach ($ld['monitored_group'] as $group){
				//	create department list for managers
					if(strpos($group,'-') === false ){
						$this->dbu->query("INSERT INTO member2manage2dep SET department_id = '".filter_var($group, FILTER_SANITIZE_NUMBER_INT)."',
																	member_id = '".$ld['mid']."'");
						$memberlist = $this->dbu->query("SELECT `member_id` FROM `member` WHERE `department_id` = ".filter_var($group, FILTER_SANITIZE_NUMBER_INT));
						while ($memberlist->next()) {
							$this->dbu->query("INSERT INTO member2manage SET member_id = '".$memberlist->f('member_id')."',
																	manager_id = '".$ld['mid']."'");
						}
					}
					$pieces = explode('-',$group);
					if(count($pieces) == 1){
						continue;//we don't need departments because we are member based
					}
					$member_id = end($pieces);
					$this->dbu->query("INSERT INTO member2manage SET member_id = '".$member_id."',
																	manager_id = '".$ld['mid']."'");
				}
				break;
			case 4://this one is interesting
				$ld['mid'] = $ld['monitored'];
				$this->dbu->query("UPDATE member SET first_name = '".$ld['first_name']."',
													last_name = '".$ld['last_name']."',
													username = '".$ld['username']."',
													password = '".$ld['password']."',
													email = '".$ld['email']."',
													active = '".$ld['active']."',
													alias = '".$ld['alias']."'
													WHERE member_id = '".$ld['mid']."'");
				break;
		}
		
		
		$ld['error']='Member has been successfully updated.<br>';
		$ld['pag']='member';
		return true;

	}

	function delete(&$ld)
	{
		if(!$this->delete_validate($ld))
		{
			return false;
		}
		if(isset($ld['uninstall']) && $ld['uninstall'] == 1  && $ld['member'] != 15){
			//get computer and
			$query = $this->dbu->query("SELECT member.logon,
											computer.ip,
											computer.name AS computer_name
											FROM member
											INNER JOIN computer2member ON computer2member.member_id = member.member_id
											INNER JOIN computer ON computer.computer_id = computer2member.computer_id
											WHERE member.member_id = ? AND computer.computer_id = ?",array($ld['member'],$ld['computer']));
			while ($query->next()){
				$this->dbu->query("INSERT INTO uninstall SET logon = '".$query->f('logon')."',
															computer = '".$query->f('computer_name')."',
															ip = '".$query->f('ip')."'");
			}
		}
		
		 $memberInfo = $this->dbu->row("SELECT access_level,active FROM member WHERE member_id = '".$ld['member']."'");
		$access_level = $memberInfo['access_level'];
		 switch ($access_level){
			case 1:
			case 1.5:
				$this->dbu->query("DELETE FROM member WHERE member_id = '".$ld['member']."'");
				break;
			case 2:
			case 3:
				$this->dbu->query("DELETE FROM member2manage WHERE manager_id = '".$ld['member']."'");
				$this->dbu->query("DELETE FROM member WHERE member_id = '".$ld['member']."'");
				$this->dbu->query("DELETE FROM member2manage2dep WHERE member_id = '".$ld['member']."'");
				break;
			case 4:
				if(($memberInfo['active'] == 1) || ($memberInfo['active'] == 2)){ 
					$this->dbu->query("DELETE FROM computer2member WHERE member_id = '".$ld['member']."' and computer_id = '".$ld['computer']."'");
					$this->dbu->query("SELECT * from computer2member WHERE member_id = '".$ld['member']."'");
					if(!$this->dbu->move_next()){
						// this user does not exist on other computers
						$this->dbu->query("DELETE FROM member WHERE member_id = '".$ld['member']."'");
					}
					$this->dbu->query("SELECT * from computer2member WHERE computer_id = '".$ld['computer']."'");
					if(!$this->dbu->move_next()){
						// this computer does not have other users
						$this->dbu->query("DELETE FROM computer WHERE computer_id = '".$ld['computer']."'");
					}
					//delete user data
					$sessions = $this->dbu->query("SELECT `session_id` FROM `session` WHERE `member_id` = ".$ld['member']." AND `computer_id` = ".$ld['computer']." ");
						while ($sessions->next()){
							$this->dbu->query("DELETE FROM session WHERE session_id = '".$sessions->f('session_id')."'");
							$this->dbu->query("DELETE FROM session_activity WHERE session_id = '".$sessions->f('session_id')."'");
							$this->dbu->query("DELETE FROM session_application WHERE session_id = '".$sessions->f('session_id')."'");
							$this->dbu->query("DELETE FROM session_attendance WHERE session_id = '".$sessions->f('session_id')."'");
							$this->dbu->query("DELETE FROM session_chat WHERE session_id = '".$sessions->f('session_id')."'");
							$this->dbu->query("DELETE FROM session_document WHERE session_id = '".$sessions->f('session_id')."'");
							$this->dbu->query("DELETE FROM session_file WHERE session_id = '".$sessions->f('session_id')."'");
							$this->dbu->query("DELETE FROM session_log WHERE session_id = '".$sessions->f('session_id')."'");
							$this->dbu->query("DELETE FROM session_website WHERE session_id = '".$sessions->f('session_id')."'");
							$this->dbu->query("DELETE FROM session_website_agg WHERE session_id = '".$sessions->f('session_id')."'");
							$this->dbu->query("DELETE FROM session_window WHERE session_id = '".$sessions->f('session_id')."'");
							$this->dbu->query("DELETE FROM alert_trigger WHERE session_id = '".$sessions->f('session_id')."'");	
						}
				}else/*{
					$this->dbu->query("UPDATE member SET first_name = '',
														last_name = '',
														username = '',
														password = '',
														email = '',
														access_level = 4,
														active = '1',
														alias = '0'
														WHERE member_id = '".$ld['member']."'");
				}*/
				break;
		}
		$ld['error']='Member has been successfully deleted.<br>';
		return true;
	}
	/****************************************************************
	* function add_validate(&$ld)                                   *
	****************************************************************/
	function add_validate(&$ld)
	{
		$is_ok=true;
		if(!$ld['first_name'])
		{
			$ld['error'].='Please fill in the <strong>First name</strong> field.<br>';
			$is_ok=false;
		}
		if(!$ld['last_name'])
		{
			$ld['error'].='Please fill in the <strong>Last Name</strong> field.<br>';
			$is_ok=false;
		}
		if(!$ld['username'])
		{
			$ld['error'].='Please fill in the <strong>Username</strong> field.<br>';
			$is_ok=false;
		}else{
			$this->dbu->query("SELECT member_id FROM member WHERE username = ?",array($ld['username']));
			if($this->dbu->move_next()){
				$ld['error'].='Please fill in the <strong>Username</strong> field with a different username.<br>';
				$is_ok=false;
			}
		}
		if(!$ld['password'])
		{
			$ld['error'].='Please fill in the <strong>Password</strong> field.<br>';
			$is_ok=false;
		}elseif (strlen($ld['password']) < 6){
			$ld['error'].='Please fill in the <strong>Password</strong> field with at least 6 chars.<br>';
			$is_ok=false;
		}
		if(!$ld['password2'])
		{
			$ld['error'].='Please re type your <strong>Password</strong>.<br>';
			$is_ok=false;
		}

		if($ld['password'] != $ld['password2'])	{
			$ld['error'].='The two passwords dont match.<br>';
			$is_ok=false;
		}


		if(!$ld['access_level'])
		{
			$ld['error'].='Please fill in the <strong>Access level</strong> field.<br>';
			$is_ok=false;
		}elseif ($ld['access_level'] != 1){
			switch ($ld['access_level']){
				case 4:
					if( !$ld['monitored']){
						$ld['error'].='Please assign this member a <strong>Monitored User</strong>';
						$is_ok = false;
					}		
					break;
				case 2:
				case 3:	
					if(!is_array($ld['monitored_group']) || empty($ld['monitored_group'])){
						$ld['error'].='Please assign this member one or more <strong>Monitored Users</strong>';
						$is_ok = false;
					}
					break;
			}
		}
		return $is_ok;
	}
	
	/****************************************************************
	* function update_validate(&$ld)                                *
	****************************************************************/
	function update_validate(&$ld)
	{
		if(!$ld['mid'])
		{
			$ld['error']='Invalid ID<br>';
			return false;
		}else{
			$this->dbu->query("SELECT member_id,access_level FROM member WHERE member_id = '".$ld['mid']."'");
			if(!$this->dbu->move_next())
			{
				$ld['error']='Invalid ID<br>';
				return false;
			}
			if($ld['mid'] == 1 && $this->dbu->f('access_level') != $ld['access_level']){
				$ld['error'] = 'The default Admin\'s role can not be changed.<br>';
				return false;
			}
		}
		$is_ok = true;
		if(!$ld['first_name'])
		{
		    $ld['error'].='Please fill in the <strong>First name</strong> field.<br>';
		    $is_ok=false;
		}			
		if(!$ld['last_name'])
		{
		    $ld['error'].='Please fill in the <strong>Last Name</strong> field.<br>';
		    $is_ok=false;
		}			
		if(strstr($ld['username'], '\\'))
		{
		    $ld['error'].='Please do not use "\" in the username.<br>';
		    $is_ok=false;
		}		
		if(!$ld['username'])
		{
		    $ld['error'].='Please fill in the <strong>Username</strong> field.<br>';
		    $is_ok=false;
		}else{
			// $this->dbu->query("SELECT member_id FROM member WHERE username = ? ",array($ld['username']));
			// if($this->dbu->move_next()){
			    // $ld['error'].='Please fill in the <strong>Username</strong> field with a different username.<br>';
			    // $is_ok=false;
			// }
		}
		if(!$ld['password'])
		{
		    $ld['error'].='Please fill in the <strong>Password</strong> field.<br>';
		    $is_ok=false;
		}elseif (strlen($ld['password']) < 6){
			$ld['error'].='Please fill in the <strong>Password</strong> field with at least 6 chars.<br>';
			$is_ok=false;
		}
		
		if(!$ld['password2'])
		{
		    $ld['error'].='Please re type your <strong>Password</strong>.<br>';
		    $is_ok=false;
		}

		if($ld['password'] != $ld['password2'])	{
		    $ld['error'].='The two passwords dont match.<br>';
		    $is_ok=false;			
		}

		if(!$ld['access_level'])
		{
			$ld['error'].='Please fill in the <strong>Access level</strong> field.<br>';
			$is_ok=false;
		}elseif ($ld['access_level'] != 1){
			switch ($ld['access_level']){
				case 4:
					if( !$ld['monitored']){
						$ld['error'].='Please assign this member a <strong>Monitored User</strong>';
						$is_ok = false;
					}		
					break;
				case 2:
				case 3:	
					if(!is_array($ld['monitored_group']) || empty($ld['monitored_group'])){
						$ld['error'].='Please assign this member one or more <strong>Monitored Users</strong>';
						$is_ok = false;
					}
					break;
			}
		}
		return $is_ok;
	}
	
	/****************************************************************
	* function delete_validate(&$ld)                                *
	****************************************************************/	
	function delete_validate(&$ld)
	{
		$is_ok=true;
		if(!$ld['member'])
		{
			$ld['error']='Invalid ID<br>';
			return false;
		}
		$this->dbu->query("SELECT member_id,access_level FROM member WHERE member_id = '".$ld['member']."'");
		if(!$this->dbu->move_next())
		{
			$ld['error']='Invalid ID<br>';
			return false;
		}
		if($this->dbu->f("member_id") == 1){
			$ld['error']='Administrator can not be deleted.<br>';
			return false;
		}
		
		return $is_ok;
	}
	
	function forgot(&$ld)
	{
		global $site_url, $site_name;
		
		if(!secure_email($ld['email']))
		{
			$ld['error'] .= "Please enter a valid Email Address."."<br>";
			return false;
		}
		
		$this->dbu->query("SELECT CONCAT_WS(' ',first_name, last_name) as name,username, password, member_id, email FROM member WHERE email='".$ld['email']."'");
		
		if(!$this->dbu->move_next())
		{
			$ld['error'] .= "There is no member account with that email address. Please contact support"."<br>";
			return false;
		}
		
		$mail=$this->dbu->f('email');
		$message_data=get_sys_message('fpne');
		
		$body=$message_data['text'];
		
		$body=str_replace('[!PASSWORD]',$this->dbu->f('password'), $body );
		$body=str_replace('[!NAME]',$this->dbu->f('name'), $body );
		$body=str_replace('[!USERNAME]',$this->dbu->f('username'), $body );
		
		
		$header = "MIME-Version: 1.0\r\n";
		$header.= "Content-Type: text/html\n";
		$header.= "From: ".$message_data['from_email']." \n";
		$mail_subject=$message_data['subject'];
		
		@mail ( $mail , $mail_subject, nl2br($body) , $header);
		
		$ld['error'] = 'Email has been sent';
		return true;
	}
	
	function account(&$ld){
		if(!$this->account_validate($ld)){
			return false;
		}
		$this->dbu->query("UPDATE member SET first_name = '".$ld['first_name']."',
													last_name = '".$ld['last_name']."',
													password = '".$ld['password']."',
													email = '".$ld['email']."'
													WHERE member_id = '".$ld['mid']."'");
		$ld['error'] = 'Your account has been updated!';
		return true;
	}
	
	function account_validate(&$ld){
		if(!$_SESSION[U_ID])
			return false;
		if(!$ld['mid'])
		{
			$ld['error']='Invalid ID<br>';
			return false;
		}else{
			$this->dbu->query("SELECT member_id FROM member WHERE member_id = '".$ld['mid']."'");
			if(!$this->dbu->move_next())
			{
				$ld['error']='Invalid ID<br>';
				return false;
			}
		}
		$is_ok = true;
		if(!$ld['first_name'])
		{
		    $ld['error'].='Please fill in the <strong>First name</strong> field.<br>';
		    $is_ok=false;
		}			
		if(!$ld['last_name'])
		{
		    $ld['error'].='Please fill in the <strong>Last Name</strong> field.<br>';
		    $is_ok=false;
		}			
		if(!$ld['password'])
		{
		    $ld['error'].='Please fill in the <strong>Password</strong> field.<br>';
		    $is_ok=false;
		}elseif (strlen($ld['password']) < 6){
			$ld['error'].='Please fill in the <strong>Password</strong> field with at least 6 chars.<br>';
			$is_ok=false;
		}
		
		if(!$ld['password2'])
		{
		    $ld['error'].='Please re type your <strong>Password</strong>.<br>';
		    $is_ok=false;
		}

		if($ld['password'] != $ld['password2'])	{
		    $ld['error'].='The two passwords dont match.<br>';
		    $is_ok=false;			
		}
		return $is_ok;
	}
	
	function cleardeleted(&$ld){
		if(!is_numeric($ld['uninstall_id'])){
			$ld['error'] ='Invalid ID';
			return false;
		}
		$this->dbu->query("SELECT uninstall_id,uninstalled FROM uninstall WHERE uninstall_id = ?",$ld['uninstall_id']);
		if(!$this->dbu->move_next()){
			$ld['error'] ='Invalid ID';
			return false;
		}
		// if($this->dbu->f('uninstalled') == 1){
			// $ld['error'] = 'This Member can not be removed.';
			// return false;
		// }
			
		$this->dbu->query("DELETE FROM uninstall WHERE uninstall_id = ?",$ld['uninstall_id']);
		$ld['error'] = 'Member has been cleared.';		
		return true;
	}
	
	
}//end class