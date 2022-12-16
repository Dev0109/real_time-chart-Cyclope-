<?php
/************************************************************************
* @Author: Tinu Coman                                                   *
************************************************************************/
class auth
{
	var $dbu;
	
	function __construct()
	{
		$this->dbu = new mysql_db();
	}
	/****************************************************************
	* function login(&$ld)                                          *
	****************************************************************/
	function login(&$ld){
		
		global $user_level;
		
	    include_once(ADMIN_PATH."adldap/adLDAP.php");

		$this->dbu->query("SELECT value FROM settings WHERE constant_name='BASE_DN'");
		if($this->dbu->move_next()){
			$base_dn = $this->dbu->f('value');
		}
		$this->dbu->query("SELECT value FROM settings WHERE constant_name='ACCOUNT_SUFFIX'");
		if($this->dbu->move_next()){
			$account_suffix = $this->dbu->f('value');
		}
		$this->dbu->query("SELECT value FROM settings WHERE constant_name='DOMAIN_CONTROLLERS'");
		if($this->dbu->move_next()){
			$domain_controllers = $this->dbu->f('value');
		}
		$this->dbu->query("SELECT value FROM settings WHERE constant_name='ADMIN_USERNAME'");
		if($this->dbu->move_next()){
			$admin_username = $this->dbu->f('value');
		}
		$this->dbu->query("SELECT value FROM settings WHERE constant_name='ADMIN_PASSWORD'");
		if($this->dbu->move_next()){
			$admin_password = $this->dbu->f('value');
		}
		$this->dbu->query("SELECT value FROM settings WHERE constant_name='PROTOCOL'");
		if($this->dbu->move_next()){
			$protocol = $this->dbu->f('value');
		}

		$use_ssl = false;
		$useSSO = false;
		$useTLS = false;
		if ($protocol == 'ldaps') {
				$use_ssl = true;
				$useSSO = true;
				$useTLS = true;
		}

//	    $this->dbu->query("SELECT member_id,username,password,access_level,department_id,ad FROM member WHERE username = '".mysql_real_escape_string($ld['username'])."' AND active = 2");
	    $this->dbu->query("SELECT member_id,username,password,access_level,department_id,ad FROM member WHERE username = '"./*mysqli_real_escape_string($this->dbu->connection_id, */$ld['username']/*)*/."' AND active = 2");
	    
	    if($this->dbu->move_next()){
			if ($this->dbu->f('ad') == 1) {
				$ldap = new adLDAP(array('base_dn'=>$base_dn, 'account_suffix'=>$account_suffix, use_ssl=>$use_ssl, use_tls=>$useTLS, 'sso'=>$useSSO, 'domain_controllers'=>array($domain_controllers)));
				$ldap_authUser = $ldap->user()->authenticate($ld['username'], $ld['password']);
				if ($ldap_authUser != true) {
					$step = 1;
					$adlogin = false;
				$ld['error'] = 'AD Username or password invalid. ';
				return false;
				} else {
					$adlogin = true;
				}
			}

	        if($ld['password'] == $this->dbu->f('password') || $adlogin == true){
	             session_start();
	             $_SESSION[UID]=1;
	             $_SESSION[U_ID] = $this->dbu->f('member_id');
	             $_SESSION[ACCESS_LEVEL] = $this->dbu->f('access_level');
	             $_SESSION[D_ID] = $this->dbu->f('department_id');
	             if($this->dbu->f('access_level') == 4){
	             	$_SESSION['filters']['t'] = 'users';
	             	$_SESSION['filters']['f'] = 'u'.$this->dbu->f('department_id').'-'.$this->dbu->f('member_id');
	             	$ld['t'] = 'users';
	             	$ld['f'] = 'u'.$this->dbu->f('department_id').'-'.$this->dbu->f('member_id');
	             }
	             
	             global $user_level;
	             $user_level = $_SESSION[ACCESS_LEVEL];
	             
	             if($_SESSION[ACCESS_LEVEL] == ADMIN_LEVEL && GO_TO_TRIAL)
				 {
				 	header('Location: activate.php?pag=trial');
				 	$ld['pag'] = 'trial';
	             	return true;
				 }
				 else if(GO_TO_TRIAL)
				 {
				 	$_SESSION[UID]=0;
				    $_SESSION[U_ID]=0;
				    $_SESSION[ACCESS_LEVEL]=5;
				     session_destroy();
				 	$ld['error'] .= "The licence for this software has not been activated";
				 	return false;
				 }
	             
				if ($_SESSION[ACCESS_LEVEL] == 1.5)
				{
					$ld['pag'] = 'monitored';
				} else {
					$ld['pag'] = 'simpleoverview';
				}
	             return true;
	        }
	    }
	    $ld['error'] = 'Username or password invalid. ';
	    return false;
	 }
	 
	/****************************************************************
	* function logout(&$ld)                                         *
	****************************************************************/
	function logout(&$ld)
	{
	   	session_register(UID);
	    
	   	$_SESSION[UID]=0;
	    $_SESSION[U_ID]=0;
	    $_SESSION[ACCESS_LEVEL]=5;
	    session_destroy();
	    return true;
	}
	
}//end class