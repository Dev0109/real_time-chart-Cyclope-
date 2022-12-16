<?php

function create_xls($csvstring, $output_file = null) {
	//	requirements
	date_default_timezone_set('Europe/London');
	require_once CURRENT_VERSION_FOLDER . 'phpexcel/Classes/PHPExcel/IOFactory.php';
	$output_file = isset($output_file) ? str_replace('.csv', '.xls', $output_file) : 'file.xls';
	//	create temp file
	 $file = tempnam(sys_get_temp_dir(), 'excel_');
	 $handle = fopen($file, "w");
	 fwrite($handle, $csvstring);
	//	create xls
	$objReader = PHPExcel_IOFactory::createReader('CSV');
	$objPHPExcel = $objReader->load($file);
	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	// $objWriter->save(str_replace('.php', '.xls', __FILE__));
	// delete temp file
	 fclose($handle);
	 unlink($file);
	// We'll be outputting an excel file
	header('Content-type: application/vnd.ms-excel');
	header('Content-Disposition: attachment; filename="' . $output_file . '"');
	$objWriter->save('php://output');
	return true;
}

function compareDeepValue($val1, $val2)
{
	$apptrue = strcmp($val1['APP_ID'], $val2['APP_ID']);
	$formtrue = strcmp($val1['EXPAND'][0]['window_id'], $val2['window_id']);
	$formtrue = $val2['window_id'] == 0 ? true : $formtrue;
	if ($apptrue && $formtrue) {
		return $apptrue;
		}
	return false;
}

//	sequence
function find_sequence_in_timeline($needle, $haystack) {
	 $keys = array_keys($haystack);
	 $out = array();
	 foreach ($keys as $key) {
		  $add = true;
		  $result = array();
		  foreach ($needle as $i => $value) {
				$currpos = array_search($key, $keys);
				if (
					((!(isset($haystack[$keys[$currpos + $i]]['APP_ID']) && $haystack[$keys[$currpos + $i]]['APP_ID'] == $value['APP_ID'])) 
						&& 
						(!(isset($haystack[$keys[$currpos + $i]]['EXPAND'][0]['window_id']) && $haystack[$keys[$currpos + $i]]['EXPAND'][0]['window_id'] == $value['window_id'])))
					||
					((!(isset($haystack[$keys[$currpos + $i]]['APP_ID']) && $haystack[$keys[$currpos + $i]]['APP_ID'] == $value['APP_ID']))
						&& 
						($value['window_id'] == 0))
					
				) {
					 $add = false;
					 break;
				}
				$result[] = $keys[$currpos + $i];
		  }
		  if ($add == true) { 
				$out[] = $result;
		  }
	 }
	return $out;
}

function apply_sequence_to_timeline($data, $to_elements, $sequencename) {
	foreach ($to_elements as $key => $sequence_element) {
		// $data[$sequence_element[0]]['SEQUENCENAME'] = $sequencename;
		foreach ($sequence_element as $key => $data_element) {
			$data[$data_element]['HIGHLIGHT'] = 'shighlight';
			$data[$data_element]['SEQUENCENAME'] = $sequencename;
		}
		$data[$sequence_element[0]]['HIGHLIGHT'] = 'shighlight first';
	}
	return $data;
}

function count_sequence_in_timeline($data, $to_elements, $sequencename) {
	$count = 0;
	foreach ($to_elements as $key => $sequence_element) {
		$count++;
	}
	//	return, count dracula!
	return $count;
}

//	check if the alert table needs to be updated
function needRerun($timestamp)
{
	$co_today = date("md", time());
	$co_lastday = date("md", $timestamp);
	$need_rerun = ($co_today > $co_lastday) ? 1 : 0;
	return $need_rerun;
}

function get_department_children($department_id,$list = 0){
	global $debug;
	$dbu = new mysql_db();
	$nodes = array();
	$department_id = filter_var($department_id, FILTER_SANITIZE_NUMBER_INT);
		$nodeInfo = $dbu->row("SELECT lft,rgt FROM department WHERE department_id = ?",$department_id);
		$query = $dbu->query("SELECT department_id FROM department WHERE lft BETWEEN ".$nodeInfo['lft'].' AND '.$nodeInfo['rgt']);
		while ($query->next()){
			array_push($nodes,$query->f('department_id'));					
		}
	if ($list == 1) {
		return implode(",", $nodes);
	} else {
		return $nodes;
	}
}

function get_licensed_computers($method = 'sql') {
	global $debug;
	$dbu = new mysql_db();
	$computers = array();
	if ($debug == 1) {
		return '';
	}
	$licence = get_license();
	$computer_limit = $licence->computers;
	$dbu->query("SELECT `computer_id` FROM `computer` LIMIT " . $computer_limit);
		while ($dbu->move_next()){
			array_push($computers,$dbu->f('computer_id'));
		}
		if ($method == 'array') {
			return $computers;
		} else {
			return ' AND computer.computer_id IN (' . join( ',', $computers ) . ')';
		}
}

function member2manage_Rebuild(){
	$dbu = new mysql_db();
	//	delete all managers
	$dbu->query("DELETE FROM `member2manage`");
	//	select all manager users from member2manage2dep
	$managers = $dbu->query("SELECT * FROM `member2manage2dep`");
		while ($managers->next()) {
			$users = $dbu->query("SELECT member_id FROM `member` WHERE department_id = " . $managers->f('department_id'));
			while ($users->next()) {
				$dbu->query("INSERT INTO member2manage SET member_id = '".$users->f('member_id')."',
								manager_id = '".$managers->f('member_id')."'");
			}
		}
	return true;
}

function rand_string( $length ) {
	$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

	$size = strlen( $chars );
	for( $i = 0; $i < $length; $i++ ) {
		$str .= $chars[ rand( 0, $size - 1 ) ];
	}

	return $str;
}

function get_email_rowcount(){
	$dbu = new mysql_db();
	$rowcount = $dbu->field("SELECT value FROM `settings` WHERE `constant_name` = 'NUMBER_OF_ROWS_EMAIL'");
	if (!is_numeric($rowcount)){
		$rowcount = 50;
		$dbu->query(	"INSERT INTO `settings` (
							`constant_name` ,
							`value` ,
							`long_value` ,
							`module` ,
							`type`
							)
						VALUES (
							'NUMBER_OF_ROWS_EMAIL', '" . $rowcount . "', NULL , '', '1'
							);");
	}
	return $rowcount;
}

function member2manage_Modify($id,$parent){
$dbu = new mysql_db();
	$dbu->query("DELETE FROM `member2manage2dep` WHERE `department_id`=".$id);
	$managers = $dbu->query("SELECT * FROM `member2manage2dep` WHERE `department_id`=".$parent);
	while ($managers->next()) {
		$dbu->query("INSERT INTO member2manage2dep SET `department_id`='".$id."',
							member_id = '".$managers->f('member_id')."'");
	}
	member2manage_Rebuild();
	return true;
}

function member2manage_Delete($id){
$dbu = new mysql_db();
	$dbu->query("DELETE FROM `member2manage2dep` WHERE `department_id`=".$id);
	member2manage_Rebuild();
	return true;
}

//pdf_header
function pdf_header(){
	if($_REQUEST['render'] == 'pdf'){
		return '<link rel="stylesheet" type="text/css" href="../css/reset.css" /><link rel="stylesheet" type="text/css" href="../css/defaults.css" /><link rel="stylesheet" type="text/css" href="../css/forms.css" /><link rel="stylesheet" type="text/css" href="../css/layout.css"/><link rel="stylesheet" type="text/css" href="../css/tables.css"/><link rel="stylesheet" type="text/css" href="../css/smoothness/jquery-ui-1.8.6.custom.css"/><link rel="stylesheet" type="text/css" href="../css/jquery.ui.css"/><link rel="stylesheet" type="text/css" href="../css/pdf.css">
		<script type="text/javascript" src="../js/jquery-1.7.2.js"></script><script type="text/javascript" src="../js/jquery.class.js"></script><script type="text/javascript" src="../js/jquery-ui.min.js"></script><script type="text/javascript" src="../js/flobn.js"></script><script type="text/javascript" src="xlang.php"></script><script type="text/javascript" src="../js/jquery.jstree.js"></script><script type="text/javascript" src="../js/error_messages.js"></script><script type="text/javascript" src="../js/date.js"></script><script type="text/javascript" src="../js/jquery-ui-1.8.6.dialog.js"></script><script type="text/javascript" src="../js/jquery-idleTimeout.js"></script><script type="text/javascript" src="../js/dynamic_charts.js"></script>
		<script type="text/javascript">flobn.register("genesis",new Date("'.date('n/j/Y',$dates['genesis']).'"));</script><script type="text/javascript" src="../ui/document-ui.js"></script>
		<link rel="stylesheet" type="text/css" href="../css/pdf.css"><img class="headerimg" src="../img/pdf_header.jpg"/><h1 class="pdftitle">[!L!]Title: [!/L!][!L!]{TITLE}[!/L!]</h1><h1 class="pdftitle">[!L!]User(Machine) / Group: [!/L!]{USER_DEPARTMENT_NAME}</h1><h2 class="pdftitle">[!L!]Time period: [!/L!]{TIME_PERIOD}</h2>';
	} else {
		return '';
	}
}

function pdf_multiply($number){
	if($_REQUEST['render'] == 'pdf'){
		return $number * 1.45;
	} else {
		return $number;
	}
}

function pdf_animate(){
	if($_REQUEST['render'] == 'pdf'){
		return false;
	} else {
		return true;
	}
}

function pdf_hide(){
	if($_REQUEST['render'] == 'pdf'){
		return ' style="display:none;" ';
	} else {
		return '';
	}
}

function pdf_class(){
	if($_REQUEST['render'] == 'pdf'){
		return ' pdf ';
	} else {
		return '';
	}
}

function pdf_media_location($media,$extra_path){
	if($_REQUEST['render'] == 'pdf'){
		return '../' . $media;
	} else {
		return $extra_path . $media;
	}
}

//	create a chart
function drawGraph($graph){

	// $graph = array( "settings" => array( "container" => array("selector" => "timechart", "height" => "274px", "width" => "274px")));
	// $graph->settings->container->selector = $graph->settings->container->selector ? $graph->settings->container->selector : 'chartcontainer';
	// $graph->settings->container->height = $graph->settings->container->height ? $graph->settings->container->height : '100%';
	// $graph->settings->container->width = $graph->settings->container->width ? $graph->settings->container->width : '100%';
	
	$js_start = '
	  <div id="' . $graph['settings']['container']['selector'] . '" style="height: ' . $graph['settings']['container']['height'] . '; width: ' . $graph['settings']['container']['width'] . ';margin: auto;"></div>
		 <script type="text/javascript">
			var chart = new CanvasJS.Chart("' . $graph['settings']['container']['selector'] . '",
			';
	$js_end = ');
			chart.render();
		  </script>';
	$output = $js_start . json_encode($graph) . $js_end;
	
	//	building javascript
	return $output;
}
//	ecrypt names in trial
function trialEncrypt($name,$type='user'){
	$dbu = new mysql_db();
	$trial = $dbu->field("SELECT value FROM `settings` WHERE `constant_name` = 'TRUENC'");
	$cryptedname = crc32(base64_encode($name));
	if ($type == 'user'){
		$output = "U" . $cryptedname;
	}
	if ($type == 'comp'){
		$output = "C" . $cryptedname;
	}
	if ($type == 'ip'){
		$output = "IP" . $cryptedname;
	}
	// Micutzu START encodare pt acces cu Limited Manager
	//if ($_SESSION[ACCESS_LEVEL] == LIMITED_LEVEL) return $output;
	// END encodare pt acces cu Limited Manager
	
	// Alex START encodare username + computername if checked
	$anonymize = $dbu->field("SELECT value FROM `settings` WHERE `constant_name` = 'ANONYMIZE_NAMES'");
	if ($anonymize == 'checked') return $output;
	// END encodare username + computername if checked
	
	if ($trial == 2236985){
		return $name;
	} else {
		return $output;
	}
}

// check license and return decrypted license data
function get_license() {
	include_once(CURRENT_VERSION_FOLDER.'misc/json.php');
	$jsonDecoder = new Services_JSON();
	$dbu = new mysql_db();
	//	==================
	$licence_key  = $dbu->field("SELECT long_value FROM settings WHERE constant_name='LICENCEKEY'");
	if($licence_key)
	{
		$licence = $jsonDecoder->decode(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, MCRYPT_PASSWORD,base64_decode($licence_key),MCRYPT_MODE_ECB));		
		return $licence;	
	}
	return false;
}

//	getna
function getna($number){
	if($number < 1){
		return 'n/a';
	} else {
		return $number;
	}
}

/**
 * trims text to a space then adds ellipses if desired
 * @param string $input text to trim
 * @param int $length in characters to trim to
 * @param bool $ellipses if ellipses (...) are to be added
 * @param bool $strip_html if html tags are to be stripped
 * @return string
 */
function trim_text($input, $length=10, $ellipses = true, $strip_html = true) {
	 //strip tags, if desired
	 if ($strip_html) {
		  $input = strip_tags($input);
	 }
  
	 //no need to trim, already shorter than trim length
	 if (strlen($input) <= $length) {
		  return $input;
	 }
	
	mb_internal_encoding("UTF-8");
	 //find last space within length
	 $last_space = strrpos(mb_substr($input, 0, $length), ' ');
	 //$trimmed_text = substr($input, 0, $last_space);
	 $trimmed_text = mb_substr($input, 0, $length);
  
	 //add ellipses (...)
	 if ($ellipses) {
		  $trimmed_text .= '...';
	 }
  
	 return $trimmed_text;
}


//	process a string to clean it
// echo text_process(
	// "Filă științifică.",
		// array(
			// 'transliterate' => true,
			// 'delimiter' => ' ',
			// 'lowercase' => false,
		// )
	// );
function text_process($str, $options = array()) {
	// Make sure string is in UTF-8 and strip invalid UTF-8 characters
	$str = mb_convert_encoding((string)$str, 'UTF-8', mb_list_encodings());
	$defaults = array(
	'delimiter' => ' ',
	'limit' => null,
	'lowercase' => false,
	'replacements' => array(),
	'transliterate' => true,
	);
	// Merge options
	$options = array_merge($defaults, $options);
	$char_map = array(
		// Latin
		'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
		'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
		'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ő' => 'O',
		'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ű' => 'U', 'Ý' => 'Y', 'Þ' => 'TH',
		'ß' => 'ss',
		'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c',
		'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
		'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ő' => 'o',
		'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ű' => 'u', 'ý' => 'y', 'þ' => 'th',
		'ÿ' => 'y',
		 
		// Latin symbols
		'©' => '(c)',
		 
		// illegal characters symbols
		"'" => '',
		"’" => '',
		 
		// Greek
		'Α' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D', 'Ε' => 'E', 'Ζ' => 'Z', 'Η' => 'H', 'Θ' => '8',
		'Ι' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M', 'Ν' => 'N', 'Ξ' => '3', 'Ο' => 'O', 'Π' => 'P',
		'Ρ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y', 'Φ' => 'F', 'Χ' => 'X', 'Ψ' => 'PS', 'Ω' => 'W',
		'Ά' => 'A', 'Έ' => 'E', 'Ί' => 'I', 'Ό' => 'O', 'Ύ' => 'Y', 'Ή' => 'H', 'Ώ' => 'W', 'Ϊ' => 'I',
		'Ϋ' => 'Y',
		'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'h', 'θ' => '8',
		'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => '3', 'ο' => 'o', 'π' => 'p',
		'ρ' => 'r', 'σ' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'ps', 'ω' => 'w',
		'ά' => 'a', 'έ' => 'e', 'ί' => 'i', 'ό' => 'o', 'ύ' => 'y', 'ή' => 'h', 'ώ' => 'w', 'ς' => 's',
		'ϊ' => 'i', 'ΰ' => 'y', 'ϋ' => 'y', 'ΐ' => 'i',
		 
		// Turkish
		'Ş' => 'S', 'İ' => 'I', 'Ç' => 'C', 'Ü' => 'U', 'Ö' => 'O', 'Ğ' => 'G',
		'ş' => 's', 'ı' => 'i', 'ç' => 'c', 'ü' => 'u', 'ö' => 'o', 'ğ' => 'g',
		 
		// Romanian
		'Ă' => 'A', 'Î' => 'I', 'Ș' => 'S', 'Ț' => 'T', 'Â' => 'A',
		'ă' => 'a', 'î' => 'i', 'ș' => 's', 'ț' => 't', 'â' => 'a', 'ţ' => 't',
		 
		// Russian
		'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh',
		'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
		'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
		'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sh', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu',
		'Я' => 'Ya',
		'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
		'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
		'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
		'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sh', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu',
		'я' => 'ya',
		 
		// Ukrainian
		'Є' => 'Ye', 'І' => 'I', 'Ї' => 'Yi', 'Ґ' => 'G',
		'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g',
		 
		// Czech
		'Č' => 'C', 'Ď' => 'D', 'Ě' => 'E', 'Ň' => 'N', 'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T', 'Ů' => 'U',
		'Ž' => 'Z',
		'č' => 'c', 'ď' => 'd', 'ě' => 'e', 'ň' => 'n', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ů' => 'u',
		'ž' => 'z',
		 
		// Polish
		'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'e', 'Ł' => 'L', 'Ń' => 'N', 'Ó' => 'o', 'Ś' => 'S', 'Ź' => 'Z',
		'Ż' => 'Z',
		'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z',
		'ż' => 'z',
		 
		// Latvian
		'Ā' => 'A', 'Č' => 'C', 'Ē' => 'E', 'Ģ' => 'G', 'Ī' => 'i', 'Ķ' => 'k', 'Ļ' => 'L', 'Ņ' => 'N',
		'Š' => 'S', 'Ū' => 'u', 'Ž' => 'Z',
		'ā' => 'a', 'č' => 'c', 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n',
		'š' => 's', 'ū' => 'u', 'ž' => 'z'
	);
	// Make custom replacements
	$str = preg_replace(array_keys($options['replacements']), $options['replacements'], $str);
	// Transliterate characters to ASCII
	if ($options['transliterate']) {
		$str = str_replace(array_keys($char_map), $char_map, $str);
	}
	// Replace non-alphanumeric characters with our delimiter
	// $str = preg_replace('/[^\p{L}\p{Nd}]+/u', $options['delimiter'], $str);
	// Remove duplicate delimiters
	$str = preg_replace('/(' . preg_quote($options['delimiter'], '/') . '){2,}/', '$1', $str);
	// Truncate slug to max. characters
	$str = mb_substr($str, 0, ($options['limit'] ? $options['limit'] : mb_strlen($str, 'UTF-8')), 'UTF-8');
	// Remove delimiter from ends
	$str = trim($str, $options['delimiter']);
	return $options['lowercase'] ? mb_strtolower($str, 'UTF-8') : $str;
}
function text_sanitize($str, $options = array()) {
	// Make sure string is in UTF-8 and strip invalid UTF-8 characters
	$str = mb_convert_encoding((string)$str, 'UTF-8', mb_list_encodings());
	$defaults = array(
	'delimiter' => ' ',
	'limit' => null,
	'lowercase' => false,
	'replacements' => array(),
	'transliterate' => true,
	);
	// Merge options
	$options = array_merge($defaults, $options);
	$char_map = array(
		 
		// Latin symbols
		'©' => '(c)',
		 
		// illegal characters symbols
		"'" => '',
		"’" => '',
	);
	// Make custom replacements
	$str = preg_replace(array_keys($options['replacements']), $options['replacements'], $str);
	// Transliterate characters to ASCII
	if ($options['transliterate']) {
		$str = str_replace(array_keys($char_map), $char_map, $str);
	}
	// Replace non-alphanumeric characters with our delimiter
	// $str = preg_replace('/[^\p{L}\p{Nd}]+/u', $options['delimiter'], $str);
	// Remove duplicate delimiters
	$str = preg_replace('/(' . preg_quote($options['delimiter'], '/') . '){2,}/', '$1', $str);
	// Truncate slug to max. characters
	$str = mb_substr($str, 0, ($options['limit'] ? $options['limit'] : mb_strlen($str, 'UTF-8')), 'UTF-8');
	// Remove delimiter from ends
	$str = trim($str, $options['delimiter']);
	return $options['lowercase'] ? mb_strtolower($str, 'UTF-8') : $str;
}

//	print pdf from html address
function loadPDF($page,$format='inline') {
	$dbu = new mysql_db();
	$localhost = $dbu->field("SELECT long_value FROM `settings` WHERE `constant_name` = 'SITE_URL'");
	$filename = $page.'.pdf';
	if($_SESSION['attachment_name'])
	{
		$filename = $_SESSION['attachment_name'];
	}
	$pdfFilePath = ini_get('upload_tmp_dir').'/'.$filename;
	$htmlPath = $localhost . CURRENT_VERSION_FOLDER . 'temp_pdf/'.$page.'.html';
	$execPathWin = $_SERVER["DOCUMENT_ROOT"].'/'.CURRENT_VERSION_FOLDER."wkhtmltopdf/wkhtmltopdf";
	$execPath = $execPathWin;
	debug_log('EXEC BEGIN: "' . $execPath. '"  --zoom 1.45 --load-media-error-handling ignore "' . $htmlPath . '" "' . $pdfFilePath . '"','pdflog');
	exec('"' . $execPath. '" "' . $htmlPath . '"  --zoom 1.45 --load-media-error-handling ignore "' . $pdfFilePath . '"', $output, $return_var);
	debug_log('OUTPUT:' . print_r($output,1).  ' -- ' . print_r($return_var,1),'pdflog');
	if (!$return) {
		debug_log('EXEC DONE.','pdflog');
	} else {
		debug_log('EXEC FAILED.','pdflog');
	}
		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Type: application/pdf');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: '.filesize($pdfFilePath));
		if ($filename!==null || $inline) {
			$disposition = 'inline';
			header("Content-Disposition: $disposition; filename=\"$filename\"");
		}
		readfile($pdfFilePath);
}

// clean json from error possibilities
function json_prepare($string)
{
	$string=str_replace('%22','',$string);	//	get rid of quotes
	$string=str_replace('&quot;','',$string);	//	get rid of quotes
	$string=str_replace('%26#092%3B%26#092%3B','CEEREPLACETEMPORARY',$string);	//	1 fix for C:\\
	$string=str_replace('%26#092%3B"','',$string);	//	get rid of quotes: problem with "C:\\"
	$string=str_replace('CEEREPLACETEMPORARY','%26#092%3B%26#092%3B',$string);	//	2 fix for C:\\
	return $string;
}

//	clean array from empty items, implode, or string return
function cleanArrayToString($array)
{
	if (!is_array($array) && (strpos($array, ',') !== false))
	{
		$array = explode(',',$array);
	}
	if (is_array($array))
	{
		array_filter($array);
		foreach($array as $k=>$v) {
			if($v == '' || $v == false || !is_numeric($v)) {
				unset($array[$k]);
			}
		}
		if(count($array) > 1 )
		{
			$array_result = implode(',',$array);
		} elseif (count($array) == 1 )  {
			$array_result = $array[0];
		} else  {
			$array_result = '';
		}
	}else{
		$array_result = $array;
	}
	return $array_result;
}

//	request a http page with post data
function do_post_request($url, $data, $optional_headers = null,$getresponse = false) {
		$params = array('http' => array(
						 'method' => 'POST',
						 'content' => $data
					 ));
		if ($optional_headers !== null) {
		$lang_id = $dbu->field("SELECT value FROM settings WHERE constant_name = 'LANGUAGE_ID'");
		$shortcode = $dbu->field("SELECT shortcode FROM language where language_id = ?",$lang_id);
		$shortcode = !empty($shortcode) ? strtoupper($shortcode) : 'EN';
		define('LANG',$shortcode);
			$params['http']['header'] = $optional_headers;
		}
		$ctx = stream_context_create($params);
		$fp = @fopen($url, 'rb', false, $ctx);
		if (!$fp) {
		  return false;
		}
		if ($getresponse){
		  $response = stream_get_contents($fp);
		  return $response;
		}
	 return true;
}

// $request a http page with 'GET' or 'POST' data, without waiting for answer
function curl_request_async($url=NULL, $params=array(), $type='POST')
{
	foreach ($params as $key => &$val) {
		if (is_array($val)) $val = implode(',', $val);
		$post_params[] = $key.'='.urlencode($val);
	}
	$post_string = implode('&', $post_params);

	$parts=parse_url($url);

	$fp =	fsockopen($parts['host'],
			isset($parts['port'])?$parts['port']:80,
			$errno, $errstr, 30);

	// Data goes in the path for a GET request
	if('GET' == $type) $parts['path'] .= '?'.$post_string;

	$out = "$type ".$parts['path']." HTTP/1.1\r\n";
	$out.= "Host: ".$parts['host']."\r\n";
	$out.= "Content-Type: application/x-www-form-urlencoded\r\n";
	$out.= "Content-Length: ".strlen($post_string)."\r\n";
	$out.= "Connection: Close\r\n\r\n";
	// Data goes in the request body for a POST request
	if ('POST' == $type && isset($post_string)) $out.= $post_string;

	fwrite($fp, $out);
	fclose($fp);
}

//	run something after set time periodically
//	table = table name
//	interval = number of seconds
function cronRunInterval($table,$interval){
	$dbu = new mysql_db();
	$dbtime = $dbu->field("SELECT value FROM settings WHERE constant_name='".$table."'");
	$now = time();
	if ($dbtime + $interval > $now) {
		return false;
	} else {
		$dbu->query("UPDATE settings SET value='".$now."' WHERE constant_name='".$table."'");
	}
	return true;
}

//	run something once every day/week/month/year
//	table = table name
//	interval = time unit (day/week...
function cronRunPeriodical($table,$interval = 'day'){
	$dbu = new mysql_db();
	$now = time();
	$dbu->field("SELECT value FROM settings WHERE constant_name='".$table."'");
	$db_start = $dbu->f('value') ? $dbu->f('value') : 0;
	// debug_log('periodcheck START for '.$interval.': '.$db_start,'cronlog');
	$dbu->field("SELECT long_value FROM settings WHERE constant_name='".$table."'");
	$db_end = $dbu->f('long_value') ? $dbu->f('long_value') : 0;
	// debug_log('periodcheck NOW for '.$interval.': '.$now,'cronlog');
	// debug_log('periodcheck END for '.$interval.': '.$db_end,'cronlog');
	if( ($db_start <= $now) && ($db_end >= $now) ) {
		return false;
	} elseif( ($db_start <= 11) && ($db_end <= 11) )  {
			$day_save_start = mktime(0,0,0,date('m'),date('d'),date('Y'));
			$day_save_end = mktime(23,59,59,date('m'),date('d'),date('Y'));
			$week_save_start = mktime(0, 0, 0, date('n'), date('j'), date('Y')) - ((date('N')-1)*3600*24);
			$week_save_end = $week_save_start + 604800;
			$month_save_start = mktime(0, 0, 0, date('m'), 1, date('Y'));
			$month_save_end = mktime(23,59,59,date('m'),date('t',mktime(0, 0, 0, date("m"),date("d"),date("Y"))),date("Y"));
		$dbu->query("UPDATE settings SET value='".$day_save_start."', long_value='".$day_save_end."' WHERE constant_name='UNIVERSAL_CRON_DAILY'");
		$dbu->query("UPDATE settings SET value='".$week_save_start."', long_value='".$week_save_end."' WHERE constant_name='UNIVERSAL_CRON_WEEKLY'");
		$dbu->query("UPDATE settings SET value='".$month_save_start."', long_value='".$month_save_end."' WHERE constant_name='UNIVERSAL_CRON_MONTHLY'");
		debug_log('---> this was dummy for the initial value <--- ','cronlog');
		return false;
	} else {
		if($interval == 'day'){
			$save_start = mktime(0,0,0,date('m'),date('d'),date('Y'));
			$save_end = mktime(23,59,59,date('m'),date('d'),date('Y'));
		} elseif($interval == 'week') {
			$save_start = mktime(0, 0, 0, date('n'), date('j'), date('Y')) - ((date('N')-1)*3600*24);
			$save_end = $save_start + 604800;
		} elseif($interval == 'month') {
			$save_start = mktime(0, 0, 0, date('m'), 1, date('Y'));
			$save_end = mktime(23,59,59,date('m'),date('t',mktime(0, 0, 0, date("m"),date("d"),date("Y"))),date("Y"));
		}
		$dbu->query("UPDATE settings SET value='".$save_start."', long_value='".$save_end."' WHERE constant_name='".$table."'");
		// debug_log('new periodcheck START for '.$interval.': '.$save_start,'cronlog');
		// debug_log('new periodcheck END for '.$interval.': '.$save_end,'cronlog');
		return true;
	}
	return false;
}

function get_division_number($link_id,$dept_id)
{
	$dbu = new mysql_db();
	$division_number_object = $dbu->query("SELECT count(`link_id`) as 'divnum' FROM `application_productivity` WHERE `link_id` = ".$link_id." AND `department_id` = " . $dept_id);
	while ($division_number_object->next()) {
		$division_number = $division_number_object->f('divnum');
	}
	if(!is_numeric($division_number) || (!$division_number) )
		$division_number = 1;
	return $division_number;
}

//	log the log
function debug_log($string_to_log, $logname = 'log', $error = NULL, $debugmode = 0)
{
	global $debug;
	$dbu_debug = new mysql_db();
	$manual_debug_query = $dbu_debug->query(
			"SELECT `value`
				FROM `settings`
				WHERE `constant_name` = 'MANUAL_DEBUG'"
		);
	if ($manual_debug_query->next() == 1) {
		$manual_debug = 1;
	}
	$alwayslog = array(
			'log-emails',
			// 'cronlog',
			'log-emails-report-daily',
			'log-emails-report-weekly',
			'log-emails-report-montly',
			'log-emails-extra-daily',
			'log-emails-extra-weekly',
			'log-emails-extra-montly',
			);
	if($debug == 1 || $debugmode == 1 || in_array($logname,$alwayslog) || $_GET['logit'] == 1 || $manual_debug == 1){
		file_put_contents(CURRENT_VERSION_FOLDER.'logs/' . print_r($logname,1) . '.log',$string_to_log."\n",FILE_APPEND);
		if($db == 1 || $error == 1) {
			$dbu_debug->query("INSERT INTO `logs` (
												`log_id`,
												`longtext`,
												`module`,
												`error`) 
									VALUES (
												NULL,
												'" . $string_to_log . "',
												'" . $logname . "',
												" . $error . ");"
							);
		}
	}
}

//	print the log
function debug_print($string_to_log, $exit = 0)
{
	 echo '<pre>' . print_r($string_to_log,1) . '</pre>';
	 if($exit == 1){exit;}
}

function multidimensional_implode($mainArray)
{
	foreach ($mainArray as $values)
	{
		$string .= implode(',', $values);
	}
	return $string;
}

//	define the website table needed
//	for hours we need the old table
function get_session_website_table()
{
	if ($_SESSION['filters']['time']['type'] == 1)
	{
		return "website_agg";
	} else {
		return "website";
	}
}

//	create a tree for jstree from OU of ad
function ldap_ou_walkTree($parent_id = 'root',$ldap = NULL)
{	
	//	$parent_id scould be an array always
	//	the array should contain all elements of the path to the root in reverse order: name,parent,parentsparent...
	if($parent_id == 'root')
	{
		$ldap_search = NULL;
	} else {
		$ldap_search = $parent_id;
	}
	//	get OU list from this level (set to false, we don't need recursive now)
	$folders = $ldap->folder()->listing($ldap_search,adLDAP::ADLDAP_FOLDER,false,'folder');
	
	if (!is_array($folders)){
		return false;
	}
	
	//	create initial arrays
	$tree = array();
	$object = array();
	$data = array();
	$tree['objects'] = array();
	array_shift($folders);	//	clean array. first element is always empty, we need to delete it
	
	//	prepare folder list for process
	foreach ($folders as $folder)
	{
		$info = explode(',',$folder['dn']);
		//	set parent. if second element is not OU, it is a root folder
		if(strpos($info[1],"DC=")!==false)
			$object['parent'] = 'root';
		else
			$object['parent'] = substr($info[1],3);
			
		//	remove everything not OU from array, and clean the values for names only
		foreach($info as $key => $one) {
			if(strpos($one, 'DC=') !== false)
				unset($info[$key]);
			else
				$info[$key] = substr($one,3);
		}

		$object['name'] = $info[0];
		$object['searchstring'] = $info;

		array_push($tree['objects'],$object);
	}
	
	//	build json-ready array recursively
	foreach ($tree['objects'] as $object)
	{
		$row = array('data' => $object['name'],
								'attr' => array('rel' => $object['parent'] == 'root' ? 'root' : 'group',
													'rev' => implode(',',$object['searchstring']),
													'id' => str_replace(array(' ',','), '' , implode('',$object['searchstring'])))
		);
		
		//	recursive part
		$children = ldap_ou_walkTree($object['searchstring'],$ldap);
		if($children){
			$row['children'] = $children;
			$row['state'] = 'open';
		}
		
		array_push($data,$row);
	}
	return $data;
}

//	prepare OU and user hierarchy for import
function prepareForImport($unitlist = NULL,$ldap = NULL)
{
	$dbu = new mysql_db();
	$userdata = array();
	if ($unitlist == NULL)
	{
		return false;
	}
	if ($unitlist == 'update')
	{
		$saved_unitlist = $dbu->field("SELECT long_value FROM settings WHERE constant_name='OU_CHECKED'");
		$unitlist = unserialize($saved_unitlist);
	}
	$unitlist_save = serialize($unitlist);
	$dbu->query("UPDATE settings SET long_value='".$unitlist_save."' WHERE constant_name='OU_CHECKED'");
	foreach($unitlist as $unit)
	{
		$unitarray = explode(',',$unit);
		// echo $unit;
		$users = $ldap->folder()->listing($unitarray,adLDAP::ADLDAP_FOLDER,true,'user');
		if(!is_array($users)){continue;}
		foreach($users as $user)
		{
			if(!$user['objectclass']){continue;}
			if (in_array("computer", $user['objectclass']) || !in_array("user", $user['objectclass']))
			{
				unset($user);
				continue;
			}
			$username = $user['samaccountname'][0];
			$userdata[$username]['name'] = $username;
			$usertree = $user['dn'];
			// $userfullname = $user['dn'][0]; //  CN=user fullname,OU=zomg,OU=subysuby,OU=SubTestOu,OU=TestOU,DC=amplusnet,DC=ro
			$usertree_array_raw = explode(',',$usertree);
			foreach($usertree_array_raw as $key => $one) {
				if(strpos($one, 'OU=') !== 0)
					unset($usertree_array_raw[$key]);
				else
					$usertree_array_raw[$key] = substr($one,3);
			}
			$userdata[$username]['parents'] = $usertree_array_raw;
		}
	}
	return $userdata;
	// return $folders;
}

//	import the users and departments
function populateDepartmentsAndUsers($userdata = NULL)
{
	if ($userdata == NULL)
	{
		return false;
	}
	$users = '';
	include_once(CURRENT_VERSION_FOLDER."classes/cls_department.php");
	$dbu = new mysql_db();
	$department_object = new department();
	foreach($userdata as $oneuser)
	{
		$parent_id = 1;
		$departments = array_reverse($oneuser['parents']);
		foreach ($departments as $department)
		{
			$ld_fake = array('parent'=>$parent_id,'name'=>$department);
			$department_object->add($ld_fake);
			$parent_id = $dbu->field("SELECT department_id  FROM `department` WHERE `name` = '".$department."'");
		}
	}

	foreach($userdata as $oneuser)
	{
		$correct_parent_id = 1;	
		$user_parents_count = count($oneuser['parents']);
		$user_dep_ids = $dbu->query("SELECT *  FROM `department` WHERE `name` = '".$oneuser['parents'][1]."'");
		while ($user_dep_ids->next()){
			$user_parent_id = $user_dep_ids->f('department_id');
			$i = 1;
			$parent_temp_id = $user_dep_ids->f('parent_id');
			while ($i <= $user_parents_count) {
				$i++;
				if ($i > $user_parents_count && $good_parent) {
					$correct_parent_id = $user_dep_ids->f('department_id');
					break;
				}
				$parent_temp = $dbu->query("SELECT *  FROM `department` WHERE `department_id` = '".$parent_temp_id."'");
				if ($parent_temp->next()) {
					if ($oneuser['parents'][$i] == $parent_temp->f('name')) {
						$parent_temp_id = $parent_temp->f('parent_id');
						$good_parent = true;
					} else {
						$good_parent = false;
						break;
					}
				}
			}
			if($correct_parent_id != 1) {
				break;
			}
		}
		$dbu->query("INSERT INTO `member` (`member_id` ,`department_id` ,`logon` ,`first_name` ,`last_name` ,`username` ,`password` ,`email` ,`convival` ,`access_level` ,`active` ,`alias` ,`ad`)VALUES (NULL , '".$correct_parent_id."', '".$oneuser['name']."', NULL , NULL , NULL , NULL , NULL , '0', '4', '1', '0', '1') ON DUPLICATE KEY UPDATE department_id='".$correct_parent_id."', ad='1'");
		$users .= $oneuser['name'] . " ";
	}
	return $users;
}

/**
 * Convert Seconds To Hours,Minutes, Seconds
 *
 * @param int $seconds
 * @return string
 */
function format_time($seconds, $show_seconds = true, $colon = false){
	 $ret = "";
	$days = intval(intval($seconds) / (3600*24));
	 // if($days > 0){
		  // $ret .= $days.'d ';
	 // }
	if($colon){
		$h = ':';
		$m = ':';
		$s = '';
			if(!$show_seconds){
				$m = '';
			}
	} else {
		$h = 'h ';
		$m = 'm ';
		$s = 's';
	}
	
	 $hours = intval((intval($seconds) / 3600)%24);
	// if($hours > 0 || $days > 0){
		$ret .= padornopad($hours + ($days * 24),$colon).$h;
	 // }
	 $minutes = (intval($seconds) / 60) % 60;
	 // if($hours > 0 || $minutes > 0){
		  $ret .= padornopad($minutes,$colon).$m;
	 // }		
	 if($show_seconds) 
	 {
		 $seconds = intval($seconds) % 60;
		 $ret .= padornopad($seconds,$colon).$s;
	 }
	 $seconds = intval($seconds) % 60;
		  
	 return trim($ret,' ');
}

function padornopad($number,$pad = false,$length = 2){
	if ($pad) {
		return  str_pad((string)$number, $length, "0", STR_PAD_LEFT);
	} else {
		return $number;
	}
	
}

function format_time_with_day($seconds, $show_seconds = true){
	 $ret = "";
	$days = intval(intval($seconds) / (3600*24));
	 if($days > 0){
		  $ret .= $days.'d ';
	 }
	 $hours = intval((intval($seconds) / 3600)%24);
	if($hours > 0){
		$ret .= $hours .'h ';
	 }
	 $minutes = (intval($seconds) / 60) % 60;
	 if($hours > 0 || $minutes > 0){
		  $ret .= $minutes.'m ';
	 }		
	 if($show_seconds) 
	 {
		 $seconds = intval($seconds) % 60;
		 $ret .= $seconds.'s';
	 }
	 $seconds = intval($seconds) % 60;
	 
	 if($show_seconds == false  && $seconds > 1 && $seconds < 60 && $hours <=0 && $minutes <=0)
	 {
	 	$ret .= '1m ';
	 }
	 elseif($show_seconds == false && (empty($seconds) || $seconds == 0) && $hours <=0 && $minutes <=0){
	 	$ret .= '0m';
	 }
		  
	 return trim($ret,' ');
}

/**
 * Parseaza un url si returneaza domain-ul
 * @example
 * 	Input
 * 		<code>echo getDomain("http://www.google.com/search?q=test&sourceid=opera&num=0&ie=utf-8&oe=utf-8");</code>
 *  Output
 * 		www.google.com
 *
 * @param string $url
 * @return string
 */
function getDomain($url){ 
	if(!$url){
		return ''; 
	}
	$pos = strpos($url,"://");
	if($pos===false || $pos > 5){ 
		$url ="http://".$url;
	}
	$matches = preg_split('![\/]+!',$url);
	return next($matches);
}

/**
 * Generate Random Color
 *
 * @return string
 */
function random_color(){
	 return sprintf("%02X%02X%02X", mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
}

function build_category_tree($selected,$spacer = '&nbsp;&nbsp;&nbsp;'){
	$dbu = new mysql_db();
	$l = new LanguageParser();
	$nodes = $dbu->query("SELECT * FROM application_category ORDER BY lft ASC");
	$rights = array();
	$ret = array();
	while ($nodes->next()){
		while (!empty($rights) && (end($rights) < $nodes->f('rgt'))){
			array_pop($rights);
		}
		$ret[$nodes->f('application_category_id')] = str_repeat($spacer,count($rights)).$l->lookup($nodes->f('category'));
		$rights[] = $nodes->f('rgt'); 		
	}
	return bulid_simple_dropdown($ret,$selected);
}



function get_application_type($type = 0){
	$types = array(0 => 'Application Forms',1 => 'Chat',2 => 'Document', 3 => 'Internet');
	return $types[$type];
}

function in_array_match($regex, $array) {
	 if (!is_array($array))
		  trigger_error('Argument 2 must be array');
	 foreach ($array as $v) {
		  // $match = preg_match($regex, $v);
		  $match = strpos($regex, $v);
		  if ($match !== false) {
				return true;
		  }
	 }
	 return false;
}

function build_application_type($selected,$regex){
	$fixedlist = array("Yahoo!", "Office", "Excel", "Word", "PowerPoint", "Microsoft", "Dreamweaver", "Notepad", "wordpad", "communicator", "lync", "Acrobat Reader", "Adobe Reader", "Dreamweaver", "Fireworks", "Flash", "Illustrator", "InDesign", "CorelDrw", "Photoshop", "acrord32", "CorelPP", "YahooMessenger", "ypager", "pidgin", "AIM", "Icq", "Skype", "msnmsgr", "googletalk", "miranda", "trillian", "acad", "AdSync", "Qv.exe", "winmenu", "Charisma", "MSTSC", "DIS31USR", "Devize", "notes", "saplogon", "R25SRV32", "devenv", "iexplore", "Internet Explorer", "opera", "firefox", "chrome", "thunderbird");
	$applist = array(0 => 'Application Forms',1 => 'Chat',2 => 'Document', 3 => 'Internet');
	if($selected > 0 && $regex && in_array_match($regex, $fixedlist)){
		return bulid_simple_dropdown(array($selected => $applist[$selected]),$selected);
	}
	return bulid_simple_dropdown(array(0 => 'Application Forms',1 => 'Chat',2 => 'Document', 3 => 'Internet'),$selected);
}


//highlight search key in file type search results
function highlight_filename($longString, $highlitString)
{	
	$pos=strrpos($longString,'&#092;&#092;');
	$temp_string = substr($longString,$pos,(strlen($longString)-$pos)); 
	$highlighted = str_ireplace($highlitString,"<span class=\"highlight\">".$highlitString."</span>",$temp_string);
	$ret_val = str_ireplace($temp_string,$highlighted,$longString);
	return $ret_val;
}

//	clean filters from hours
function clean_filter($filter){
	$temp =$filter;
	if (substr_count($filter,'hour')){
		$pos=strripos($filter,'AND (');
		if($pos){
			$temp= substr($filter, 0, $pos);
		}
	}
	return $temp;
}

//	needs application type and application id
function calculateComposite($apptype,$appid,$app_join,$app_filter)
{
	
	if ($apptype == 3)
	{
		$session_table = 'website';
		$table = 'domain';
		$primary = $table.'_id';
		$name_field = 'domain';

		$productivity_field = 'COALESCE(application_productivity.productive,1) AS productive';
		$productivity_filter = 'LEFT JOIN application_productivity ON application_productivity.department_id = 1
									AND application_productivity.link_id = '.$table.'.'.$primary.'
									AND application_productivity.link_type = '.$apptype;

		$productivity_mix_uniform = new mysql_db();
		$productivity_mix_uniform->query("SELECT SUM(session_".$session_table.".duration) AS app_duration,
							".$table.".".$primary." AS id,
							".$table.".".$name_field." as name,
							session_".$session_table.".application_id,
							".$productivity_field."
							 FROM session_".$session_table."
							 INNER JOIN ".$table." ON ".$table.".".$primary." = session_".$session_table.".".$primary."
							 INNER JOIN session ON session.session_id = session_".$session_table.".session_id
							".$app_join."
							".$productivity_filter."
							WHERE session_".$session_table.".time_type = 0 AND 2=2 AND session_".$session_table.".application_id = '".$appid."'
							".$app_filter."
							GROUP BY ".$table.".".$primary."
							ORDER BY app_duration DESC");
		$vprod = array();
		while ($productivity_mix_uniform->move_next()){
			$vprod[] .= $productivity_mix_uniform->f('productive');
		}
		$composite = array_unique($vprod);
		if(count($composite) > 1){
			return true;
		}
	}
	return false;
}

//	set a dynamic ORDER BY from session or post on pages where this is implemented
function get_sorting($sortable_columns,$morecolumns = '',$defaultorder = 'asc',$noreset = 0)
{
	$current_page = basename($_SERVER['PHP_SELF']);
	if ($current_page == 'index.php' && $_GET['offset'] == '')
	{
		unset($_SESSION['sorting']);
	}
	
	//	if we have a post we should modify the session accordingly
	if ($_POST['sortcolumn'] != '')
	{
		$_SESSION['sorting']['sortcolumn'] = $_POST['sortcolumn'];
		$_SESSION['sorting']['sortorder'] = $_POST['sortorder'];
	}
	
	//	do we at least have a column value of that index?
	if ($sortable_columns[$_SESSION['sorting']['sortcolumn']] == '')
	{
		$_SESSION['sorting']['sortcolumn'] = 0;
	}
	//	if we have no order, set it to default
	if (!$_SESSION['sorting']['sortorder'])
	{
		$_SESSION['sorting']['sortorder'] = $defaultorder;
	}
	
		$order = $_SESSION['sorting']['sortorder'];
	//	create the ordering
	$sorting = " ORDER BY " . $sortable_columns[$_SESSION['sorting']['sortcolumn']] . $morecolumns . " " . $order . " ";
	
	return $sorting;
}	//	get_sorting

//	render the sortable columns anchor inner data
//	the placeholder for this in the template must be put into an empty anchor
function render_anchor_inner($columnindex)
{
		$anchor .= ' class="sorter ' . $_SESSION['sorting']['sortorder'];	//	class: sorter order
	if ($columnindex == $_SESSION['sorting']['sortcolumn'])
	{
		$anchor .= ' active';	//	class: active
	}
		$anchor .= '" order="';	//	order
	if ($_SESSION['sorting']['sortorder'] == 'desc')
	{
		$anchor .= 'asc';	//	asc
	}
	if ($_SESSION['sorting']['sortorder'] == 'asc')
	{
		$anchor .= 'desc';	//	desc
	}
		$anchor .= '" column="' . $columnindex . '" ';	//	column
		
		$anchor .= 'title="Sort ' . $_SESSION['sorting']['sortorder'] . '"';	//	title
	return $anchor;	//	'Better to sleep with a sober cannibal than a drunk Christian'
}

function get_filters($filter_type = 'session',$node = 's1',$filter_time = array(), $root = false, $department_only = false ){
	$dbu = new mysql_db();
	//define the return values
	$app_filter = $app_join = $total_filter = $total_join = $alert_filter =  '';
	// $exclude_deleted_users = " AND member.logon NOT IN ( SELECT logon FROM uninstall )";
	$exclude_deleted_users = " ";
	
	$pieces = explode('-',$node);
	$filterCount = count($pieces);
	
	list($department_id,$computer_id,$member_id) = $pieces;
	//clean department_id
	$department_id = substr($department_id,1);
	if($department_id == ''){$department_id = 1;}
	if($filterCount == 2){
		if(strpos($pieces[0], 'u') === 0 )
		{
			$filter_type = 'users';
			$_SESSION['filters']['t'] = $filter_type;
		} else {
			$filter_type = 'computers';
			$_SESSION['filters']['t'] = $filter_type;
		}		
	}
	if($filter_type == 'users' && $filterCount == 2){
		$member_id = $computer_id;		
	}
	//filtering
	if($department_only || $filterCount == 1){
		$nodes = array();
		if($filterCount == 1){//selected user/computer session{
			$nodeInfo = $dbu->row("SELECT lft,rgt FROM department WHERE department_id = ?",$department_id);
			$query = $dbu->query("SELECT department_id FROM department WHERE lft BETWEEN ".$nodeInfo['lft'].' AND '.$nodeInfo['rgt']);
			while ($query->next()){
				array_push($nodes,$query->f('department_id'));					
			}
		}else{
			array_push($nodes,$department_id);
		}
		$member_list = array();
		switch ($_SESSION[ACCESS_LEVEL]){
			case MANAGER_LEVEL:
			// case LIMITED_LEVEL:
			case DPO_LEVEL:
				$members = $dbu->query("SELECT member_id FROM member2manage WHERE manager_id = ?",$_SESSION[U_ID]);
				while ($members->next()){
					array_push($member_list,$members->f('member_id'));
				}	
				break;
			case EMPLOYEE_LEVEL:
				$member_list = array($_SESSION[U_ID]);//forever alone!
				break;
		}
		switch ($filter_type){
			case 'session':
			case 'users':
				$total_join = ' INNER JOIN member ON member.member_id = session.member_id';
				$total_filter .= ' AND member.department_id IN ('.implode(',',$nodes).')';

				$app_join = ' INNER JOIN member ON member.member_id = session.member_id';
				$app_filter = ' AND member.department_id IN ('.implode(',',$nodes).')';
				$alert_filter = ' AND member.department_id IN ('.implode(',',$nodes).')';	
				break;
			case 'computers':
				$total_join = ' INNER JOIN computer ON computer.computer_id = session.computer_id 
								INNER JOIN member ON member.member_id = session.member_id';
				$total_filter .= ' AND computer.department_id IN ('.implode(',',$nodes).')';

				$app_join = ' INNER JOIN computer ON computer.computer_id = session.computer_id 
							  INNER JOIN member ON member.member_id = session.member_id';
				$app_filter = ' AND computer.department_id IN ('.implode(',',$nodes).')';
				$alert_filter = ' AND computer.department_id IN ('.implode(',',$nodes).')';
				break;
		}
		if(!empty($member_list)){
			$total_filter .= ' AND member.member_id IN ('.implode(',',$member_list).')';
			$app_filter .= ' AND member.member_id IN ('.implode(',',$member_list).')';
			$alert_filter .= ' AND member.member_id IN ('.implode(',',$member_list).')';		
		}
	}else{
		//not filtering by department so we need everybody onboard here		
		switch ($filter_type){
			case 'session':
				$total_join = ' INNER JOIN member ON member.member_id = session.member_id';
				$total_filter = ' AND session.member_id = '.$member_id.' 
								  AND session.computer_id = '.$computer_id.' 
								  AND member.department_id = '.$department_id;
				$app_filter = ' AND session.member_id = '.$member_id.' 
									AND session.computer_id = '.$computer_id.' 
									AND member.department_id = '.$department_id;
				$alert_filter = ' AND session.member_id = '.$member_id.' 
									AND session.computer_id = '.$computer_id.' 
									AND member.department_id = '.$department_id;
				$app_join = ' INNER JOIN member ON member.member_id = session.member_id';				
				break;
			case 'users':
				if($filterCount == 2){
					//then we have user
					$total_join = ' INNER JOIN member ON member.member_id = session.member_id';
					$total_filter = ' AND session.member_id = '.$member_id.' 
									  AND member.department_id = '.$department_id;
					$app_filter = ' AND session.member_id = '.$member_id.' 
										AND member.department_id = '.$department_id;
					$alert_filter = ' AND session.member_id = '.$member_id.' 
										AND member.department_id = '.$department_id;
					$app_join = ' INNER JOIN member ON member.member_id = session.member_id';
					
				}else{
					//then we have user
					$total_join = ' INNER JOIN computer ON computer.computer_id = session.computer_id 
									INNER JOIN member ON member.member_id = session.member_id
									INNER JOIN computer2member ON computer2member.member_id = member.member_id AND computer2member.computer_id = computer.computer_id';
					$total_filter = ' AND session.member_id = '.$member_id.' 
									  AND member.member_id = '.$member_id.'
									  AND computer.computer_id = '.$computer_id.'	
									  AND member.department_id = '.$department_id;
	
					$app_filter = ' AND session.member_id = '.$member_id.'  
									AND member.member_id = '.$member_id.'
									AND computer.computer_id = '.$computer_id.'	
									AND member.department_id = '.$department_id;
					$alert_filter = ' AND session.member_id = '.$member_id.'  
									AND member.member_id = '.$member_id.'
									AND computer.computer_id = '.$computer_id.'	
									AND member.department_id = '.$department_id;
					$app_join = ' INNER JOIN member ON member.member_id = session.member_id
								  INNER JOIN computer ON computer.computer_id = session.computer_id 
								  INNER JOIN computer2member ON computer2member.member_id = member.member_id AND computer2member.computer_id = computer.computer_id';
				}
				break;
			case 'computers':
				if($filterCount == 2){
					$total_join = ' INNER JOIN computer ON computer.computer_id = session.computer_id 
									INNER JOIN member ON member.member_id = session.member_id';
					$total_filter = ' AND session.computer_id = '.$computer_id.' 
									  AND computer.department_id = '.$department_id;
					$app_filter = ' AND session.computer_id = '.$computer_id.' 
									AND computer.department_id = '.$department_id;
					$alert_filter = ' AND session.computer_id = '.$computer_id.' 
									AND computer.department_id = '.$department_id;
					$app_join = ' INNER JOIN computer ON computer.computer_id = session.computer_id 
								  INNER JOIN member ON member.member_id = session.member_id';				
				}else{
					$total_join = ' INNER JOIN computer ON computer.computer_id = session.computer_id 
									INNER JOIN member ON member.member_id = session.member_id
									INNER JOIN computer2member ON computer2member.member_id = member.member_id AND computer2member.computer_id = computer.computer_id';
					$total_filter = ' AND session.computer_id = '.$computer_id.' 
									  AND computer.computer_id = '.$computer_id.'
									  AND member.member_id = '.$member_id.'	
									  AND computer.department_id = '.$department_id;
					$app_filter = ' AND session.computer_id = '.$computer_id.' 
									AND computer.computer_id = '.$computer_id.'
									AND member.member_id = '.$member_id.'	
									AND computer.department_id = '.$department_id;
					$alert_filter = ' AND session.computer_id = '.$computer_id.' 
									AND computer.computer_id = '.$computer_id.'
									AND member.member_id = '.$member_id.'	
									AND computer.department_id = '.$department_id;
					$app_join = ' INNER JOIN computer ON computer.computer_id = session.computer_id 
								  INNER JOIN member ON member.member_id = session.member_id
								  INNER JOIN computer2member ON computer2member.member_id = member.member_id AND computer2member.computer_id = computer.computer_id';
				}
				break;
		}
		$total_join .= $exclude_deleted_users;
		$total_filter .= $exclude_deleted_users;
		$app_filter .= $exclude_deleted_users;
		$alert_filter .= $exclude_deleted_users;
		$app_join .= $exclude_deleted_users;
	}
	
	$_SESSION['filters']['t'] = $filter_type;
	$_SESSION['filters']['f'] = $node;
	
	
	if(!empty($filter_time)){
		$matches = array();
		preg_match_all('!([0-9]{1,2}/[0-9]{1,2}/[0-9]{4})( [0-9]+\:[0-9]+ [AM|PM]+)?!',$filter_time['time'],$matches);
		$pieces = array_shift($matches);
		$days = array(0,1,2,3,4,5,6);
		$time_filter='';
		if($_GET['pag'] == 'timeline' && count($pieces) > 1){
			$filter_time['time'] = date('n/j/Y');
			$_SESSION['filters']['time']['time'] = $filter_time['time'];
			$_SESSION['filters']['time']['current'] = 'Today';
			$host  = $_SERVER['HTTP_HOST'];
			$uri	= rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
			$extra = 'index.php?pag=timeline';
			header("Location: http://$host$uri/$extra");
			exit;
			$matches = array();
			preg_match_all('!([0-9]{1,2}/[0-9]{1,2}/[0-9]{4})( [0-9]+\:[0-9]+ [AM|PM]+)?!',$filter_time['time'],$matches);
			$pieces = array_shift($matches);
			$days = array(0,1,2,3,4,5,6);
			$time_filter='';
		}
		switch (count($pieces)){
			case 1:
				$time = strtotime(current($pieces));
				$start_time = mktime(0,0,0,date('n',$time),date('d',$time),date('Y',$time));
				$end_time = mktime(23,59,59,date('n',$time),date('d',$time),date('Y',$time));
				$days = array(date('w',$time));
				$app_filter .= ' AND (session.date >= '.$start_time.' AND session.date <= '.$end_time.')';
				$time_filter .= ' AND (session.date >= '.$start_time.' AND session.date <= '.$end_time.')';
				$total_filter .= ' AND (session.date >= '.$start_time.' AND session.date <= '.$end_time.')';
				$alert_filter .= ' AND ( alert_trigger.triggered_date >= '.$start_time.' AND alert_trigger.triggered_date <= '.($end_time + 86400).')';
				break;
			case 2:
				$start_time = strtotime(reset($pieces));
				$start_hour = date('G',$start_time);
				$start_time = mktime(0,0,0,date('n',$start_time),date('d',$start_time),date('Y',$start_time));
				$end_time = strtotime(end($pieces));
				$end_hour = date('G',$end_time);
				$end_time = mktime(0,0,0,date('n',$end_time),date('d',$end_time),date('Y',$end_time));
					$days = array(0,1,2,3,4,5,6);//all the days
				$files_filter .= '';
				$app_filter .= ' AND (session.date >= '.$start_time.' AND session.date <= '.$end_time.')';
				$time_filter .= ' AND (session.date >= '.$start_time.' AND session.date <= '.$end_time.')';
				$total_filter .= ' AND (session.date >= '.$start_time.' AND session.date <= '.$end_time.')';
				$alert_filter .= ' AND ( alert_trigger.triggered_date >= '.$start_time.' AND alert_trigger.triggered_date <= '.($end_time + 86400).')';
				break;
		}

		switch ($filter_time['type']){			
			case 2://specific time
				$app_filter.= ' AND (hour BETWEEN '.$start_hour.' AND '.$end_hour.')';
				$time_filter.= ' AND (hour BETWEEN '.$start_hour.' AND '.$end_hour.')';
				break;
			case 3://work time
				//for worktime we can haz some interesting query
				$days = array(0,1,2,3,4,5,6);
				$worktimes = get_workschedule($department_id,$days, 1);
				$local_time_filter = '';
				foreach ($worktimes as $day => $hours){
					$local_time_filter .='(session.day = '.$day.' AND hour >= '.$hours['start_hour'].' AND hour < '.$hours['end_hour'].') OR ';
				}
				$local_time_filter = rtrim($local_time_filter,' OR ');
				$app_filter .= ' AND ('.$local_time_filter.')';
				$time_filter.= ' AND ('.$local_time_filter.')';
				break;
			case 4://overtime
				
		$days = array(0,1,2,3,4,5,6);
				$worktimes = get_workschedule($department_id,$days, 1);
				$local_time_filter = '';
				foreach ($worktimes as $day => $hours){
					$local_time_filter .='(session.day = '.$day.' AND NOT(hour >= '.$hours['start_hour'].' AND hour < '.$hours['end_hour'].')) OR ';
				}
				$local_time_filter = rtrim($local_time_filter,' OR ');
				$app_filter .= ' AND ('.$local_time_filter.')';
				$time_filter.= ' AND ('.$local_time_filter.')';
				break;
			case 1://show all/default
				break;
			default:
				break;
		}
		$_SESSION['filters']['time'] = $filter_time;
	}
	return array('app_filter' => $app_filter,
				 'app_join' => $app_join,
				 'time_filter'=> $time_filter,
				 'files_filter'=> $files_filter,
				 'total_filter' => $total_filter,
				 'total_join' => $total_join,
				 'main_department_ids' => $nodes,
				 'alert_filter' => $alert_filter
	);
}

function get_categories($node = 's1',$type = 0){
	$pieces = explode('-',$node);
	$department_id = substr(reset($pieces),1);
	unset($pieces);
	
	$type_filter = ' AND application2category.link_type = '.$type;
	if(!is_numeric($type)  && $type == 'all'){
		$type_filter = '';
	}
	
	$dbu = new mysql_db();
	$categories = array();
	$dbu->query("SELECT application_category.category,
				 application_category.application_category_id,
				 application2category.link_type,
				 application2category.link_id
 				 FROM application_category
				 INNER JOIN application2category ON application2category.application_category_id = application_category.application_category_id
				 WHERE application2category.department_id = ".$department_id."
				 AND 1=1".$type_filter);
	
	while ($dbu->move_next()){		
		$categories[$dbu->f('link_id').'-'.$dbu->f('link_type')] = array('category_id' => $dbu->f('application_category_id'),'category'=> $dbu->f('category'));
	}
	return $categories;
}

function get_workschedule($department_id = 1, $days = array(0,1,2,3,4,5,6), $type = 1){
	$dbu = new mysql_db();
	$workschedule = array();
	
	$dbu->query("SELECT workschedule.day,
				 workschedule.start_time,
				 workschedule.end_time
 				 FROM workschedule
				 WHERE day IN (".join(',',$days).") 
				 AND workschedule.department_id = ".$department_id." AND activity_type = ".$type);
				 
	while ($dbu->move_next()){
		$workschedule[$dbu->f('day')] = array(
			'start_hour' => date('G', $dbu->f('start_time')),
			'end_hour' => date('G', $dbu->f('end_time'))
		);
	}
	
	return $workschedule;
}

function get_export_header($node)
{
	
	$dbu = new mysql_db();
	
	$pieces = explode('-',$_SESSION['filters']['f']);
	$prefix = substr($pieces[0],0,1);
	$department_id = $pieces[0] = substr($pieces[0],1);
	$members = 0;
	$member_name = '';
	$department_name = '';
	
	$filter = $filter_join = '';
	// if(in_array($_SESSION[ACCESS_LEVEL],array(LIMITED_LEVEL,MANAGER_LEVEL))){
	if(in_array($_SESSION[ACCESS_LEVEL],array(DPO_LEVEL,MANAGER_LEVEL))){
		$filter = 'AND manager_id = '.$_SESSION[U_ID];
		$filter_join = 'INNER JOIN member2manage ON member2manage.member_id = member.member_id';
	}

	if( count($pieces) == 3 )
	{
		//tab sesiune, member, computer si departament
		
		$member_row = $dbu->row("SELECT member.logon, CONCAT(member.first_name,' ',member.last_name) AS member_name,member.alias, computer.name as computer_name FROM member
		INNER JOIN computer2member ON member.member_id = computer2member.member_id
		INNER JOIN computer ON computer2member.computer_id = computer.computer_id
		WHERE member.member_id='".end($pieces)."'AND computer.computer_id='".prev($pieces)."'");
		
		$member_name = ( $member_row['alias'] == 1 ? $member_row['member_name'] : $member_row['logon']).' / ' .$member_row['computer_name'].'';
		
		$members = 1;
	}
	else if(count($pieces) == 2)
	{
		//tab users sau computers
		
		if($prefix == 'u')
		{
			$member_row = $dbu->row("SELECT member.*,
								  CONCAT(member.first_name,' ',member.last_name) AS name 
								  FROM member WHERE member.member_id = ".end($pieces)." AND member.department_id = ".reset($pieces));
			
			$member_name = $member_row['alias'] == 1 ?  $member_row['name'] : $member_row['logon'];
			
			$members = $dbu->field("SELECT COUNT(computer2member.member_id) FROM computer2member WHERE member_id = ".end($pieces));
		}
		else if ($prefix == 'c')
		{
			// if(in_array($_SESSION[ACCESS_LEVEL],array(LIMITED_LEVEL,MANAGER_LEVEL))){
			if(in_array($_SESSION[ACCESS_LEVEL],array(DPO_LEVEL,MANAGER_LEVEL))){
				$filter_join = 'INNER JOIN member2manage ON member2manage.member_id = computer2member.member_id';
			}
			
			$member_row = $dbu->row("SELECT computer.*
								  FROM computer
								  ".$filter_join."
								  WHERE computer.computer_id = ".end($pieces)." 
								  AND computer.department_id = ".reset($pieces)." ".$filter);	
			
			$member_name = $member_row['name'].' / '.$member_row['ip'].'';
			
			$members = $dbu->field("SELECT COUNT(member_id) FROM computer2member 
											INNER JOIN computer ON computer2member.computer_id = computer.computer_id
											".$filter_join."
											WHERE computer2member .computer_id = ".end($pieces)." 
											AND computer.department_id = ".reset($pieces)." ".$filter);
			
		}
	}
	else 
	{
		//toate taburile pe departament 
		if(!$pieces[0]){ $pieces[0] = 1; }
		$positions = $dbu->row("SELECT lft,rgt,name FROM department WHERE department_id =".$pieces[0]);
		
		$member_name = $positions['name'];
		
		$members = $dbu->field("SELECT count(member.member_id) FROM department
		INNER JOIN member ON member.department_id = department.department_id
		INNER JOIN computer2member ON computer2member.member_id = member.member_id		
		".$filter_join."
		WHERE lft >= ".$positions['lft']." and lft <= ".$positions['rgt']." ".$filter);
	}
	
	$department_name  = $dbu->field("SELECT name FROM department WHERE department_id =".$pieces[0]);
	
	
	return array('member_name' => $member_name,'members' => $members, 'department_name' => $department_name);
}

function getMac()
{
	/*exec("ipconfig /all", $output);
	foreach($output as $line){
	  if (preg_match("/(.*)Physical Address(.*)/", $line)){
			$mac = $line;
			$mac = str_replace("Physical Address. . . . . . . . . :","",$mac);
	  }
	}
	
	$mac = trim($mac);*/
	return '';
}

function ShowUpdateNotice()
{
	
	global $glob;
	
	$allowed = array(
			'1'=>'login',
			'2'=>'trial',
	);
	
	
	include_once(CURRENT_VERSION_FOLDER."misc/json.php");
	$jsonDecoder = new Services_JSON();
	$dbu = new mysql_db();
	$data = $dbu->field("SELECT long_value FROM settings WHERE constant_name='LICENCEKEY'");
	
	if($data)
	{
		$licence = $jsonDecoder->decode(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, MCRYPT_PASSWORD,base64_decode($data),MCRYPT_MODE_ECB));

		if ($licence->permanent)
		{
			define('LP',$licence->permanent);
		} else {
			define('LP',0);
		}
		
		define('AC',$licence->computers);
		define('SD',$licence->start);
		define('ED',$licence->end);
		define('LM',$licence->mac);
		define('MM',getMac());
		
		
		if( GO_TO_TRIAL == 1 && LP == 1)
		{
			
			$dbu->query("UPDATE settings SET value='0' WHERE constant_name='GO_TO_TRIAL'");
			unset($glob['pag']);
		}
		
		if( (time() > ED) && !GO_TO_TRIAL && LP < 1)
		{
			$dbu->query("UPDATE settings SET value='1' WHERE constant_name='GO_TO_TRIAL'");
			
			
			if($_SESSION[ACCESS_LEVEL] < 5)
			{
				session_start();
			 
					$_SESSION[UID]=0;
				 $_SESSION[U_ID]=0;
				 $_SESSION[ACCESS_LEVEL]=5;
				 
				 session_destroy();
				 $glob['pag'] = 'trial';
				 header('Location: activate.php?pag=trial');
				 return;
			}
		}
		
		if( (LM != MM) && !GO_TO_TRIAL && LP < 1)
		{
			
			$dbu->query("UPDATE settings SET value='1' WHERE constant_name='GO_TO_TRIAL'");
			
			session_start();
			if($_SESSION[ACCESS_LEVEL] < 5)
			{
				session_register(UID);
			 
					$_SESSION[UID]=0;
				 $_SESSION[U_ID]=0;
				 $_SESSION[ACCESS_LEVEL]=5;
				 
				 session_destroy();
				 $glob['pag'] = 'trial';
				 header('Location: activate.php?pag=trial');
				 return;
			}
		}
		
		if($glob['pag'])
		{
			
			session_start();
			if($_SESSION[ACCESS_LEVEL] == ADMIN_LEVEL && GO_TO_TRIAL && !in_array($glob['pag'],$allowed))
			{	
				$glob['pag'] = 'trial';
			}
		}
	}
	else 
	{
		if($glob['pag'])
		{
			
			session_start();
			if($_SESSION[ACCESS_LEVEL] == ADMIN_LEVEL && GO_TO_TRIAL && !in_array($glob['pag'],$allowed))
			{	
				$glob['pag'] = 'trial';
				header('Location: activate.php?pag=trial');
			}
		}
		
	}	
}

function get_icon($application,$application_type = '')
{
	if(preg_match("/360/i", $application)) return '360';
	if(preg_match("!^[Microsoft]{0,}[ Office]{0,}Access$!",$application)) return 'access';
	if(preg_match("!^[Adobe ]{0,}Acrobat$!",$application)) return 'acrobat';
	if(preg_match("!^[Adobe ]{0,}Acrobat Reader$!",$application)) return 'adobereader';
	if(preg_match("/AIM/i",$application)) return 'aim';
	if(preg_match("/ArchiCAD/i", $application)) return 'archicad';
	if(preg_match("/AutoCAD/i", $application)) return 'autocad';
	if(preg_match("/Bridge/i", $application)) return 'bridge';
	if(preg_match("!^(Google)[ \\t]Chrome$!",$application)) return 'chrome';
	if(preg_match("/Corel/i", $application)) return 'corel';
	if(preg_match("!^(Microsoft)[ \\t]Project$!",$application)) return 'default';
	if(preg_match("!^(Reports)[ \\t](Server)|([ \\t]Background[ \\t]Engine)$!",$application)) return 'default';
	if(preg_match("/DreamWeaver/i", $application)) return 'dreamweaver';
	if(preg_match("!^[Microsoft]{0,}[ Office]{0,}Excel$!",$application)) return 'excel';
	if(preg_match("!^(Facebook)[ \\t]Messenger$!",$application)) return 'fbmsg';
	if(preg_match("/Firefox/i", $application)) return 'firefox';
	if(preg_match("/Fireworks/i", $application)) return 'fireworks';
	if(preg_match("/Flash/i", $application)) return 'flash';
	if(preg_match("!^(Microsoft)[ \\t]FrontPage$!",$application)) return 'frontpage';
	if(preg_match("/Google/i", $application)) return 'gtalk';
	if(preg_match("/ICQ/i", $application)) return 'icq';
	if(preg_match("!^(Microsoft[ \\t]Internet)|(Internet)[ \\t]Explorer$!",$application)) return 'iexplorer';
	if(preg_match("/Illustrator/i", $application)) return 'illustrator';
	if(preg_match("/InDesign/i", $application)) return 'indesign';
	if(preg_match("/Infopath/i", $application)) return 'infopath';
	if(preg_match("/LibreOffice/i", $application)) return 'libreoffice';
	if(preg_match("/Lotus/i", $application)) return 'lotus';
	if(preg_match("/Impress/i", $application)) return 'lync';
	if(preg_match("/MediaEncoder/i", $application)) return 'mediaencoder';
	if(preg_match("/Miranda/i", $application)) return 'miranda';
	if(preg_match("/Notepad/i",$application)) return 'notepad';
	if(preg_match("/Notepad++/i", $application)) return 'notepadplusplus';
	if(preg_match("/OneNote/i", $application)) return 'onenote';
	if(preg_match("/OpenOffice/i", $application)) return 'openoffice';
	if(preg_match("!^(Opera)[ \\t]Internet[ \\t]Browser$!",$application)) return 'opera';
	if(preg_match("/Oracle/i", $application)) return 'oracle';
	if(preg_match("!^[Microsoft]{0,}[ Office]{0,}Outlook$!",$application)) return 'outlook';
	if(preg_match("/photoshop/i", $application)) return 'photoshop';
	if(preg_match("/Pidgin/i", $application)) return 'pidgin';
	if(preg_match("!^[Microsoft]{0,}[ Office]{0,}PowerPoint$!",$application)) return 'powerpoint';
	if(preg_match("/Project/i", $application)) return 'project';
	if(preg_match("/Publisher/i", $application)) return 'publisher';
	if(preg_match("/Remote/i", $application)) return 'rdesk';
	if(preg_match("/Safari/i", $application)) return 'safari';
	if(preg_match("!^(SAP)([ \\t]Logon[ \\t]for[ \\t]Windows)$!",$application)) return 'sap';
	if(preg_match("!^(Skype)[ \\t]$!",$application)) return 'skype';
	if(preg_match("/Thunderbird/i", $application)) return 'thunderbird';
	if(preg_match("/Trillian/i", $application)) return 'trillian';
	if(preg_match("/VirtualBox/i", $application)) return 'virtualbox';
	if(preg_match("/Visual Studio/i", $application)) return 'visualstudio';
	if(preg_match("/VNC/i", $application)) return 'vnc';
	if(preg_match("/Windev/i", $application)) return 'windev';
	if(preg_match("!^[Microsoft]{0,}[ Office]{0,}Word$!",$application)) return 'word';
	if(preg_match("!^(Yahoo[ \\!])[ \\t]Messenger$!",$application)) return 'ymessenger';
	if(preg_match("!^(Windows)([ \\t]Live[ \\t])|[ \\t](Messenger)|(Essentials)$!",$application)) return 'windowslive';
	
	if(preg_match("!^(Microsoft)[ \\t]Visio$!",$application)) return 'visio';
	if(preg_match("/Publisher/i", $application)) return 'publisher';
	
	return 'default'.$application_type;	
}

function build_app_dd($selected)
{
	$dbu = new mysql_db();
	
	$dbu->query("SELECT `application_id`, `alias` FROM `application` ORDER BY alias ASC");
				 
	$out_str = "";
	while ($dbu->move_next()){
		$out_str.="<option value=\"".$dbu->f('application_id')."\" ";//options values
		$out_str.= ( $dbu->f('application_id') == $selected ? " SELECTED " : "" );//if selected
		$out_str.=">".$dbu->f('alias')."</option>";//options names
	}
	
	return $out_str;
}

function build_seq_dd($selected = null)
{
	$dbu = new mysql_db();
	
	$dbu->query("SELECT `sequencegrp_id`, `name` FROM `sequence_reports` ORDER BY name ASC");
				 
	$out_str = "";
	while ($dbu->move_next()){
		$out_str.="<option value=\"".$dbu->f('sequencegrp_id')."\" ";//options values
		$out_str.= ( $dbu->f('sequencegrp_id') == $selected ? " SELECTED " : "" );//if selected
		$out_str.=">".$dbu->f('name')."</option>";//options names
	}
	
	return $out_str;
}

function build_appform_dd($appid, $selected)
{
	if (is_numeric($appid)) {
		$dbu = new mysql_db();
		$dbu->query("SELECT `window_id` , `name` FROM `window` WHERE `application_id` = " . $appid . " ORDER BY name ASC");		 
		$out_str = '<option value="0">-- Any Form --</option>';
		while ($dbu->move_next()){
			$out_str.="<option value=\"".$dbu->f('window_id')."\" ";//options values
			$out_str.= ( $dbu->f('window_id') == $selected ? " SELECTED " : "" );//if selected
			$out_str.=">".$dbu->f('name')."</option>";//options names
		}
		return $out_str;
	}
	return "";
}

function build_aduser_dd()
{

	$dbu = new mysql_db();
	$dbu->query("SELECT * FROM `member` WHERE `ad` = 1 AND logon IS NOT NULL ORDER BY logon ASC");		 
	$out_str = "";
	while ($dbu->move_next()){
		$out_str.="<option value=\"".$dbu->f('logon')."\" ";//options values
		$out_str.=">".$dbu->f('logon')."</option>";//options names
	}
	return $out_str;

}

function build_granuser_dd($selected = null)
{

	$dbu = new mysql_db();
	$dbu->query("SELECT * FROM `member` WHERE logon IS NOT NULL ORDER BY logon ASC");		 
	$out_str = "";
	while ($dbu->move_next()){
		$out_str.="<option value=\"".$dbu->f('logon')."\" ";//options values
		$out_str.= ( $dbu->f('logon') == $selected ? " SELECTED " : "" );//if selected
		$out_str.=">".$dbu->f('logon')."</option>";//options names
	}
	return $out_str;

}
function build_grancomp_dd($member = null)
{

	$dbu = new mysql_db();
	$member_id = $dbu->field("SELECT  `member_id` FROM  `member` WHERE  `logon` LIKE  '" . $member . "'");
	$dbu->query("SELECT * FROM `computer2member` WHERE  `member_id` = '" . $member_id . "'");		 
	$out_str = "";
	if ($member) {
		while ($dbu->move_next()){
			$computer_name = $dbu->field("SELECT  `name` FROM  `computer` WHERE  `computer_id` =  '" . $dbu->f('computer_id') . "'");
			$out_str.="<option value=\"".$computer_name."\" ";//options values
			$out_str.=">".$computer_name."</option>";//options names
		}
	}
	return $out_str;

}

function build_drive_dd($selected)
{
	if(!is_numeric($selected))
	{
		$selected = 9999;
	}
	$l = new LanguageParser();
	
	$opt = array(
		1 => $l->lookup('Show fixed drives'),
		0 => $l->lookup('Show removable drives'),
		2 => $l->lookup('Show Remote drives'),
		3 => $l->lookup('Show CDRom drives'),
		4 => $l->lookup('Show RAMDisk drives'),
		5 => $l->lookup('Show Unknown drives'),
	);
	
	$out_str = "";
	
	foreach ( $opt as $key => $value )
	{
		$out_str.="<option value=\"".$key."\" ";//options values
		$out_str.= ( $key == $selected ? " SELECTED " : "" );//if selected
		$out_str.=">".$value."</option>";//options names
	}
	
	return $out_str;
}

function number_of_days($day, $start, $end)
{
	 $w = array(date('w', $start), date('w', $end));

	 return floor( ( date('z', $end) - date('z', $start) ) / 7) + ($day == $w[0] || $day == $w[1] || $day < ((7 + $w[1] - $w[0]) % 7));
}

function encode_numericentity($string)
{
	$convmap = array(0x80, 0xffff, 0, 0xffff);
	return mb_encode_numericentity($string, $convmap, 'UTF-8');
}

function decode_numericentity($string)
{
	$convmap = array(0, 0xffff, 0, 0xffff);
	return mb_decode_numericentity($string, $convmap, 'UTF-8');
}

function build_notification_dd($selected)
{
	if(!is_numeric($selected))
	{
		$selected = 9999;
	}
	$l = new LanguageParser();
	
	$opt = array(
		0 => $l->lookup('Errors'),
		1 => $l->lookup('Warnings'),
		2 => $l->lookup('Information'),
	);
	
	$out_str = "";
	
	foreach ( $opt as $key => $value )
	{
		$out_str.="<option value=\"".$key."\" ";//options values
		$out_str.= ( $key == $selected ? " SELECTED " : "" );//if selected
		$out_str.=">".$value."</option>";//options names
	}
	
	return $out_str;
}

function add_notification($notification_constant)
{
	$dbu = new mysql_db();
	if(($notification_constant == 'NUMBER_OF_USERS_EXCEEDED') || ($notification_constant == 'LICENSE_EXPIRED') || ($notification_constant == 'ENCRYPTED_TEXT') || ($notification_constant == 'SUPPORT_EXPIRED')){
		$dbu->query("DELETE FROM session_notification WHERE notification_id = ( SELECT notification_id FROM notification WHERE constant_name='".$notification_constant."' )");
	}
	$dbu->query("INSERT INTO session_notification SET notification_id = ( SELECT notification_id FROM notification WHERE constant_name='".$notification_constant."' ), eventtime = ".time());
}

function sortArrayByArray($array,$orderArray) 
{
	 $ordered = array();
	 foreach($orderArray as $key) 
	 {
		  if(array_key_exists($key,$array)) 
		  {
					 $ordered[$key] = $array[$key];
					 unset($array[$key]);
		  }
	 }
	 return $ordered + $array;
}

function highlight($longString, $highlitString)
{
	$ret_val = "";
	$longStringExploaded = explode(" ",$longString);
	foreach($longStringExploaded as $shortString)
	{
		$startPos = stripos($shortString,$highlitString);
		if($startPos === false)
		{
			$ret_val .= $shortString." ";
			continue;
		}
		$highlighted= substr($shortString,$startPos,strlen($highlitString));
		$ret_val .= substr($shortString,0,$startPos)."<span class=\"highlight\">".$highlighted."</span>".substr($shortString,$startPos+strlen($highlitString))." ";
	}
	
	return $ret_val;
}


function multi_array_flip($arrayIn, $DesiredKey, $DesiredKey2=false, $OrigKeyName=false) 
{ 
	$ArrayOut=array(); 
	foreach ($arrayIn as $Key=>$Value) 
	{ 
		  
	 	if ($OrigKeyName) $Value[$OrigKeyName]=$Key; 
		  if (!is_string($Value[$DesiredKey])) return false; 
		  if (is_string($DesiredKey2)) 
		  { 
				if (!is_string($Value[$DesiredKey2])) return false;  
				$ArrayOut[$Value[$DesiredKey]][$Value[$DesiredKey2]]=$Value; 
		  }  
		  else $ArrayOut[$Value[$DesiredKey]][]=$Value; 
	} 
	return $ArrayOut; 
}