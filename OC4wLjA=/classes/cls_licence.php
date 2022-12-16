<?php
/***********************************************************************
* @Author: MedeeaWeb Works											   *
***********************************************************************/
class licence
{
	var $dbu;
	
	function __construct()
	{
		$this->dbu = new mysql_db();
		$this->dbu->query("SELECT value FROM settings WHERE constant_name = 'LICENSING_URL'");
		$this->dbu->move_next();
		define('LICENSING_URL',$this->dbu->f('value'));
		$this->dbu->query("SELECT value FROM settings WHERE constant_name ='SERVER_VERSION'");
		$this->dbu->move_next();
		define('SERVER_VERSION',$this->dbu->f('value'));
	}

	
	/****************************************************************
	* function trial(&$ld)                                          *
	****************************************************************/
	function trial(&$ld)
	{
		if(!$this->trial_validate($ld))
		{
			return false;
		}
		$install_key = base64_encode(ioncube_server_data());
		
		$options = array(
	        CURLOPT_RETURNTRANSFER => true,         // return web page
	        CURLOPT_ENCODING       => "",           // handle all encodings
	        CURLOPT_CONNECTTIMEOUT => 500,          // timeout on connect
	        CURLOPT_TIMEOUT        => 500,          // timeout on response
	        CURLOPT_POST           => 1,            // i am sending post data
	        CURLOPT_POSTFIELDS     => "company_name=".$ld['company_name']."&name=".$ld['name']."&country=".$ld['country']."&email=".$ld['email']."&installkey=".$install_key."&version=".SERVER_VERSION,    // this are my post vars
      	);
		
    	$ch = curl_init(LICENSING_URL);
   		curl_setopt_array($ch,$options);
    	$content = curl_exec($ch);
    	$err     = curl_errno($ch);
    	$errmsg  = curl_error($ch) ;
   	  	$header  = curl_getinfo($ch);

    	$client_info = array(
	    	'company_name' => $ld['company_name'],
	    	'name' => $ld['name'],
	    	'email' => $ld['email'],
	    	'phone' => $ld['phone'],
	    	'country' => $ld['country'],
	    	'mac' => ''
    	);
    	//decode licence to create some stuff :)
    	$json = json_decode($content);
    	if($json === null){
    		$ld['error'] = 'Error decoding licence please contact support.';
    		return false;
    	}
    	$content = $json->licence;
    	//write out the licence file
    	file_put_contents('licence.php',base64_decode($json->key));

    	$this->dbu->query("UPDATE settings SET value='1',long_value='".$content."' WHERE constant_name ='LICENCEKEY'");
		$this->dbu->query("UPDATE settings SET value='0' WHERE constant_name ='GO_TO_TRIAL'");
		$this->dbu->query("UPDATE settings SET long_value='".serialize($client_info)."' WHERE constant_name ='CLIENT_INFO'");
		$this->dbu->query("UPDATE member SET email='".$ld['email']."' WHERE access_level ='1' AND username='admin'");
		$this->dbu->query("UPDATE settings SET value='".$ld['time_zone']."' WHERE constant_name ='TIME_ZONE'");
		$this->dbu->query("UPDATE `department` SET `name` = '" . $client_info['company_name'] . "' WHERE `department`.`department_id` =1;");
		
		$ld['error'] .= 'Success';
		header('Location: help.php?pag=tour');
   		return true;
	}
	
	/****************************************************************
	* function trial_validate(&$ld)                                 *
	****************************************************************/
	
	function trial_validate(&$ld)
	{
		$is_ok = true;
		
		if($_SESSION[ACCESS_LEVEL] != ADMIN_LEVEL)
		{
			$ld['error'] .= "You don't have permisions to execute this function!<br>";
			return false;
		}
		
		if(!$ld['company_name'])
		{
			$ld['error'] .= "Please fill in the company name field<br>";
			$is_ok = false;
		}
		
		if(!$ld['name'])
		{
			$ld['error'] .= "Please fill in the name field<br>";
			$is_ok = false;
		}
		if(!$ld['country'])
		{
			$ld['error'] .= "Please select a country.<br>";
			$is_ok = false;
		}
		
		if(!$ld['email'])
		{
			$ld['error'] .= "Please fill in the Email field<br>";
			$is_ok = false;
		}
		else if(!secure_email($ld['email']))
		{
			$ld['error'] .= "Please fill in the Email field with a valid address<br>";
			$is_ok = false;
		}
		
		return $is_ok;
	}
	
	/****************************************************************
	* function activate(&$ld)                                       *
	****************************************************************/
	function activate(&$ld)
	{
		if(!$this->activate_validate($ld))
		{
			return false;
		}
		
		$client_info = array(
	    	'company_name' => $ld['company_name'],
	    	'name' => $ld['name'],
	    	'email' => $ld['email'],
	    	'phone' => $ld['phone'],
	    	'country' => $ld['country'],
	    	'mac' => ''
    	);
    	
		$json = json_decode(base64_decode($ld['licencekey']));
    	if(is_null($json)){
    		$ld['error'] = 'Error decoding licence please contact support.';
    		return false;
    	}
    	$content = $json->licence;
    	//write out the licence file
    	file_put_contents('licence.php',base64_decode($json->key));

    	$this->dbu->query("UPDATE settings SET value='0' WHERE constant_name ='GO_TO_TRIAL'");
    	$this->dbu->query("UPDATE settings SET value='1',long_value='".$content."' WHERE constant_name ='LICENCEKEY'");
		$this->dbu->query("UPDATE settings SET long_value='".serialize($client_info)."' WHERE constant_name ='CLIENT_INFO'");
		$this->dbu->query("UPDATE settings SET value='".$ld['time_zone']."' WHERE constant_name ='TIME_ZONE'");
		$this->dbu->query("UPDATE settings SET value='2236985' WHERE constant_name ='TRUENC'");
		$this->dbu->query("DELETE FROM session_notification WHERE notification_id = 7");
		$this->dbu->query("UPDATE `department` SET `name` = '" . $client_info['company_name'] . "' WHERE `department`.`department_id` =1;");
		$ld['error'] .= 'Success';

		header('Location: index.php?pag=licensing');
	
   		return true;
	}
	
	/****************************************************************
	* function activate_validate(&$ld)                              *
	****************************************************************/
	
	function activate_validate(&$ld)
	{
		$is_ok = true;
		
		if($_SESSION[ACCESS_LEVEL] != ADMIN_LEVEL)
		{
			$ld['error'] .= "You don't have permisions to execute this function!<br>";
			return false;
		}
		
		if(!$ld['company_name'])
		{
			$ld['error'] .= "Please fill in the company name field<br>";
			$is_ok = false;
		}
		
		if(!$ld['name'])
		{
			$ld['error'] .= "Please fill in the name field<br>";
			$is_ok = false;
		}
		if(!$ld['country'])
		{
			$ld['error'] .= "Please select a country<br>";
			$is_ok = false;
		}
		
		if(!$ld['email'])
		{
			$ld['error'] .= "Please fill in the Email field<br>";
			$is_ok = false;
		}
		else if(!secure_email($ld['email']))
		{
			$ld['error'] .= "Please fill in the Email field with a valid address<br>";
			$is_ok = false;
		}
		
		if(!$ld['licencekey'])
		{
			$ld['error'] .= "Please fill in the licencekey </br>";
			$is_ok = false;
		}
		if(!$is_ok){
			return false;
		}
		
		//starting having fun
		$json = json_decode(base64_decode($ld['licencekey']));

		if(is_null($json)){
			$ld['error'] = 'Invalid Licence Key please contact support<br>';
			return false;
		}

		return $is_ok;
	}
	
}//end class

if(!function_exists('secure_email')){
	function secure_email($email_address ,$max_len=255)
	{
		if(strlen($email_address)>$max_len)
			{
				return false;
			}
		return preg_match ("/^[_a-z0-9A-Z-]+(\.[_a-z0-9A-Z-]+)*@[_a-z0-9A-Z-]+(\.[a-z0-9A-Z-]+)*$/",trim($email_address));		
	}
}
