<?php


// curl "ldaps://appsrv3.amplusnet.ro:636/OU=TestOU,DC=amplusnet,DC=ro?memberuid?sub" -u "Amplusnet\Administrator:DOMCTRLhilton2014@"  --insecure -v

$ch = curl_init("ldaps://appsrv3.amplusnet.ro:636/OU=TestOU,DC=amplusnet,DC=ro?memberuid?sub");
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TRANSFERTEXT, true);
curl_setopt($ch, CURLOPT_USERPWD, 'Amplusnet\Administrator:DOMCTRLhilton2014@');
$result = curl_exec($ch);

echo "<pre>$result</pre>";




/*
 
   // $adServer = "ldaps://appsrv3.amplusnet.ro";
     //192.168.1.125
	 $adServer = "ldaps://192.168.1.125";
    $ldap = ldap_connect($adServer, 636);
    $username = 'Administrator';
    $password = 'DOMCTRLhilton2014@';
 
    $ldaprdn = $username . '@amplusnet.ro';
 //ldap_set_option(ld, LDAP_OPT_ENCRYPT, 1)
 // ldap_set_option($ldap,LDAP_OPT_ENCRYPT, 1); 
    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
 
    $bind = @ldap_bind($ldap, $ldaprdn, $password);
 
                
    if ($bind) {
                                //Instructiuni pentru bind reusit
                echo 'SUCCCESS';
                @ldap_close($ldap);
                }
    else {
        
                                echo ldap_err2str(ldap_errno($ldap));
                }

// phpinfo();
*/