<?php
/************************************************************************
* @Author: Tinu Coman
***********************************************************************/

/****************************************************************
* function secure_string($string,$max_len=255)                  *
****************************************************************/
function secure_string($string,$max_len=255)
	{
		  if(strlen($string)>$max_len)
		  	{
		  		return 0;
		  	}
		  $string=addslashes($string);
		  return $string;	
	}

function secure_name($string)
	{
	
$bad_key[9]='!';
$bad_key[1]='@';
$bad_key[2]='#';
$bad_key[3]='$';
$bad_key[4]='%';
$bad_key[5]='^';
$bad_key[6]='*';
$bad_key[7]='(';
$bad_key[8]=')';
		$i=0;
        for($i=0; $i<= strlen($string); $i++)
        {
			foreach($bad_key as $nr=>$key)
            {
				if($string[$i]==$key)
                {
				 return false;
				}
			}
		}		  
		return true;	
	}
	
function secure_string_no_spaces($string)
	{
	
		$i=0;
        for($i=0; $i<= strlen($string); $i++)
        {
			if($string[$i]==' ')
            {
		   	    return false;
			}
			
		}		  
		return true;	
	}

function secure_field_name($string)
	{
	
		$i=0;
        for($i=0; $i<= strlen($string); $i++)
        {
			if($string[$i]==' ')
            {
		   	    return false;
			}
			
		}		  
		$bad_key[9]='!';
		$bad_key[1]='@';
		$bad_key[2]='#';
		$bad_key[3]='$';
		$bad_key[4]='%';
		$bad_key[5]='^';
		$bad_key[6]='*';
		$bad_key[7]='(';
		$bad_key[8]=')';
		$bad_key[10]="'";
		$bad_key[11]='"';
		$bad_key[12]=';';
		$bad_key[13]=':';
		$bad_key[14]='<';
		$bad_key[15]='>';
		$bad_key[16]=',';
		$bad_key[17]='.';
		$bad_key[18]='/';
		$bad_key[19]='?';
		$bad_key[20]='\\';
		$bad_key[21]='|';
		$bad_key[22]='`';
		$bad_key[23]='~';
		$bad_key[24]='=';
		$i=0;
        for($i=0; $i<= strlen($string); $i++)
        {
			foreach($bad_key as $nr=>$key)
            {
				if($string[$i]==$key)
                {
				 return false;
				}
			}
		}		  
		
		return true;	
	}

function reserved_name($string)
	{
	
		$i=0;
		$bad_key[2]='Category';
		$bad_key[1]='Name';
		$i=0;
		
		foreach($bad_key as $nr=>$key)
        {
			if($string==$key)
            {
			 	return true;
			}
		}
		
		return false;	
	}

function reserved_field_name($string)
	{
	
		$i=0;
		$bad_key[2]='category_id';
		$bad_key[1]='name';
		$i=0;
		
		foreach($bad_key as $nr=>$key)
        {
			if($string==$key)
            {
			 	return true;
			}
		}
		
		return false;	
	}
	
/****************************************************************
* function secure_int($int_number,$max_len=14)                  *
****************************************************************/
function secure_int($int_number,$max_len=14)
	{
		if(!preg_match("/^([0-9]+)$/",$int_number))
			{
				return 0;
			}
		if(strlen($int_number)>$max_len)
			{
				return 0;
			}
		return	$int_number;
	}
/****************************************************************
* function secure_email($email_address ,$max_len=255);          *
****************************************************************/
function secure_email($email_address ,$max_len=255)
	{
		if(strlen($email_address)>$max_len)
			{
				return false;
			}
		return preg_match("/^[_a-z0-9A-Z-]+(\.[_a-z0-9A-Z-]+)*@[_a-z0-9A-Z-]+(\.[a-z0-9A-Z-]+)*$/i",trim($email_address));
			
	}
function secure_phone($phone ,$max_len=12)
	{
		if(strlen($phone)>$max_len)
			{
				return false;
			}
		return preg_match("/^([0-9]{3})-([0-9]{3})-([0-9]{4})$/i", $phone);
			
	}
//by default max lenght for state is 2 	
function secure_state($state)
{
		if(strlen($state)!=2)
			{
				return false;
			}
	return preg_match("/[a-zA-Z]+/i",$state);		
}	
	
/****************************************************************
* function secure_out($string);                                 *
****************************************************************/
function secure_out($string)
	{
		return htmlspecialchars($string);
	}
//alias for the above function
function so($string)
	{
		return secure_out($string);
	}
/****************************************************************
* function secure_page($pg_name)                                *
****************************************************************/
function secure_page($pg_name)
	{
		//first remove any directory bypass try "../../"
		if( strpos($pg_name,".."))
			{
				//he he, some try.let's remove'it
				$pg_name=str_replace($pg_name,"","..");
			}
		//now get only the file from the path if was ...
		return basename($pg_name);
	}
	
/****************************************************************
* function secure_domain($domain)                               *
****************************************************************/	
function secure_domain($domain)
{
	return preg_match("/^([-_a-z0-9A-Z]+)((\.)([-a-zA-z0-9])+)+$/",$domain); //"^([_a-z0-9A-Z-]+(\.)*)+((\.)+([_a-z0-9A-Z-])+)$"
}

/****************************************************************
* function secure_ip($ip)                                       *
****************************************************************/	
function secure_ip($ip)
{
	return preg_match("/^([0-9]{1,3})(\.)([0-9]{1,3})(\.)([0-9]{1,3})(\.)([0-9]{1,3})$/",$ip);
}

function secure_dnsdomain($domain)
{
	return preg_match("/^([-_a-z0-9A-Z]+)((\.)([-a-zA-z0-9])+)+(\.){0,1}$/",$domain);//'^([_a-z0-9A-Z-]+)(\.)([_a-z0-9A-Z-]+)(\.)([_a-z0-9A-Z-]+)(\.){0,1}$'
}
	
	
/****************************************************************
* function rc4($pwd, $data, $case) {                            *
****************************************************************/
function rc4($pwd, $data, $m_case) 
	{
	    if ($m_case == 'de') {
	      $data = base64_decode($data);
	       
	    }
	    
	    $key[] = "";
	    $box[] = "";
	    $temp_swap = "";
	    $pwd_length = 0;
	    
	    $pwd_length = strlen($pwd);
	    for ($i = 0; $i < 255; $i++) {
	      $key[$i] = ord(substr($pwd, ($i % $pwd_length)+1, 1));
	      $box[$i] = $i;
	    }
	    $x = 0;
	    
	    for ($i = 0; $i < 255; $i++) {
	      $x = ($x + $box[$i] + $key[$i]) % 256;
	      $temp_swap = $box[$i];
	      $box[$i] = $box[$x];
	      $box[$x] = $temp_swap;
	    }
	    
	    $temp = "";
	    $k = "";
	    
	    $cipherby = "";
	    $cipher = "";
	    
	    $a = 0;
	    $j = 0;
	    
	    for ($i = 0; $i < strlen($data); $i++) {
	      $a = ($a + 1) % 256;
	      $j = ($j + $box[$a]) % 256;
	      $temp = $box[$a];
	      $box[$a] = $box[$j];
	      $box[$j] = $temp;
	      $k = $box[(($box[$a] + $box[$j]) % 256)];
	      $cipherby = ord(substr($data, $i, 1)) ^ $k;
	      $cipher .= chr($cipherby);
	    }
	    
	    if ($m_case != 'de') {
	         $cipher = base64_encode($cipher);
	    }
	    //echo $cipher;
	    return $cipher;
	}

?>