<?php
include(CURRENT_VERSION_FOLDER.'lang/lang_'.strtolower(LANG).'.php');
if(!isset($lang) || !is_array($lang)){
	return '';
}
header('Content-type:text/javascript');
echo 'flobn.lang.load('.json_encode($lang).')';
exit();