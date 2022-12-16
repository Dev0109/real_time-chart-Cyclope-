<?php
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => $glob['pag'].".html"));

$ft->assign('NOTIFICATIONS',include_once(CURRENT_VERSION_FOLDER.'php/ajax/xnotifications.php'));


$ft->assign('PAGE_TITLE',$ft->lookup('Notifications'));

global $bottom_includes;
$bottom_includes.='</script><script type="text/javascript" src="ui/notifications-ui.js"></script>';


$ft->assign('TYPES', build_notification_dd($glob['app']));

if(!$glob['is_ajax']){
	$ft->define_dynamic('ajax','main');
	$ft->parse('AJAX_OUT','ajax');
}

$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');