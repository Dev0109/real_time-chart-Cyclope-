<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => "trial.html"));

include_once(CURRENT_VERSION_FOLDER.'misc/json.php');

$dbu = new mysql_db();
$jsonDecoder = new Services_JSON();

$dbu->query("SELECT long_value FROM settings WHERE constant_name='LICENCEKEY'");
$dbu->move_next();
$licence_key = $dbu->f('long_value');
$dbu->query("SELECT long_value FROM settings WHERE constant_name='CLIENT_INFO'");
$dbu->move_next();
$client_info = unserialize($dbu->f('long_value'));
if($licence_key)
{
	$licence = $jsonDecoder->decode(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, MCRYPT_PASSWORD, base64_decode($licence_key), MCRYPT_MODE_ECB));		
}
if((time() > $licence->end || !$licence->permanent) && $licence_key != '' ){
	$showactivate = 1;
}


$install_key = base64_encode(ioncube_server_data());

$ft->assign(array(
	'SEND_TO'   => (isset($glob['is_activation_page']) && $glob['is_activation_page']) ? 'activate.php' : 'index.php',
	'PAGE_TITLE' => $ft->lookup('Licensing'),
	'PAG' => $glob['pag'],
	'COMPANY_NAME' => $client_info['company_name'],
	'NAME' => $client_info['name'],
	'EMAIL' => $client_info['email'],
	'PHONE' => $client_info['phone'],
	'MESSAGE' => get_error($glob['error']),
	'INSTALL_KEY' => $install_key,
	'CHANGE_FIELDS' => ($_GET['pag'] == 'changelicense' || $_GET['show'] ==  'addlicense' || $showactivate) ? '<div class="req even col-sm-6 p-l-0">
          <label for="install_key">[!L!]Instalation Key[!/L!]</label>
          <span>
          <textarea name="install_key" id="install_key" rows="5"  readonly="readonly">'.$install_key.'</textarea>
          </span> 
		 </div>
		 <div class="req col-sm-6 p-r-0">
          <label for="licencekey">[!L!]Licence Key[!/L!]</label>
          <span>
          <textarea name="licencekey" id="licencekey" rows="5"></textarea>
          </span>
		 </div>' : '<!--div class="req even">
          <label for="install_key">[!L!]Instalation Key[!/L!]</label>
          <span>
          <textarea name="install_key" id="install_key" rows="10"  readonly="readonly">'.$install_key.'</textarea>
          </span> 
		 </div>
		 <div class="req col-sm-6">
          <label for="licencekey">[!L!]Licence Key[!/L!]</label>
          <span>
          <textarea name="licencekey" id="licencekey" rows="10"></textarea>
          </span>
		 </div-->',
	'CHANGE_BUTTON' => ($_GET['pag'] == 'changelicense' || $_GET['show'] ==  'addlicense' || $showactivate) ? '&nbsp;<span class="submit"><button type="submit"><b>[!L!]Activate&nbsp;License[!/L!]</b></button></span>' : '',
	'TRIAL_BUTTON' => $licence_key == '' ? '&nbsp;<span class="submit greenbutton"><button type="submit" id="gettrial"><b>[!L!]Start&nbsp;Evaluation[!/L!]</b></button></span><br><br><h3 style="text-align:center;"><a style="color:#00A344;font-size:12px;font-weight:bold;text-decoration:none;" href="activate.php?pag=trial&show=addlicense">'.$ft->lookup('If you already have a license, click here.').'</a></h3>
		 ' : '',
	'COUNTRY' => build_country_list($client_info['country']),
	'TIMEZONE' => build_timezone_list($client_info['country']),
));

global $bottom_includes;
$bottom_includes.='</script><script type="text/javascript" src="ui/trial-ui.js"></script>';
$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');
