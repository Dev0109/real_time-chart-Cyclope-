<?php
/************************************************************************
* @Author: Tinu Coman
***********************************************************************/
$db = new mysql_db();
function attemptToExplainQuery($query) {
	global $db;
	$rs = $db->explain($query['sql']);
	$query['sql'] = nl2br($query['sql']);
	if($rs === false){
		return $query;
	}
	while ($rs->next()) {
		$query['explain'][] = $rs->next_array();
	}
	return $query;
}

function getReadableTime($time) {
	$ret = $time;
	$formatter = 0;
	$formats = array('ms', 's', 'm');
	if($time >= 1000 && $time < 60000) {
		$formatter = 1;
		$ret = ($time / 1000);
	}
	if($time >= 60000) {
		$formatter = 2;
		$ret = ($time / 1000) / 60;
	}
	$ret = number_format($ret,3,'.','') . ' ' . $formats[$formatter];
	return $ret;
}

$queryTotals = array();
$queryCount = 0;
$queryTotals['time'] = 0;
$queryTotals['duplicates'] = 0;
$queries = array();
$duplicates = array();

$executedQueries = database_store::getInstance()->getQueries();

$queryCount = count($executedQueries);
foreach($executedQueries as $key => $query) {
	array_push($duplicates, $query['sql']);
	$query = attemptToExplainQuery($query);
	$queryTotals['time'] += $query['time'];
	$queries[] = $query;
}
$queryTotals['duplicates'] = $queryCount - count(array_unique($duplicates));
$queryTotals['time'] = getReadableTime($queryTotals['time']);

?>
<!-- <table border="5" bordercolor="red" title="Debug information" width=100% bgcolor="#FFFFFF" style="clear:both;" class="">
<tr>
	<td  valign="top">
		<b>Session Id:</b> <?php echo session_id() ?><br>
	 	<b>Language:</b><?echo $lang?>
	</td>
	<td  valign="top">
		<b>Function: </b><?echo $glob['act']?> <br>
		<b>Page: </b><?echo $glob['pag']?><br>
		<b>Permission Level: </b><?echo $user_level?>
	</td>
</tr>
<tr>
	<td colspan="2"><b>Current Directory: </b><?echo getcwd() ?></td>
</tr>

<tr>
	<td ><b>$glob[] collection</b></td>
	<td ><b>$_SESSION content</b></td>
</tr>
<tr>
	<td valign="top" ><pre><?php print_r($glob) ?></pre><pre><?php print_r($_FILES) ?></pre></td>
	<td valign="top" ><pre><?php print_r($_SESSION) ?></pre>&nbsp;</td>	
</tr>
<?php
if(function_exists('get_debug_instance')): ?>
<tr>
	<td colspan="2" style="padding:5px;font-size:12px;color:#000">MySQL executed in: <b><?php echo $queryTotals['time'];?></b> with <b><?php echo $queryTotals['duplicates'];?></b> duplicate(s) out of <b><?php echo $queryCount;?></b> queries</td>
</tr>
<tr><td colspan="2">
<table width="100%">
  <tbody>
<?php foreach ($queries as $query): ?>
          <tr>
            <td style="border-bottom:1px solid #000;padding:5px;color:#000">
            <div style="margin-bottom:5px;font-size:11px;"><?php echo $query['sql']; ?></div>
            <?php if(isset($query['explain']) && is_array($query['explain'])):?>
            <?php foreach ($query['explain'] as $explain):?>
            <em style="display:block;color:#898989">
            Table: <b><?php echo $explain['table']?></b> &bull; 
            <?php if(isset($explain['possible_keys']) && !empty($explain['possible_keys'])):?>Possible keys: <b><?php echo $explain['possible_keys'];?></b> &bull; <?php endif;?>
            <?php if(isset($explain['key']) && !empty($explain['key'])):?>Key used: <b><?php echo $explain['key']?></b> &bull; <?php endif;?>
              Type: <b><?php echo $explain['type']?></b> &bull;  
              Rows: <b><?php echo $explain['rows']?></b> &bull;  
            <?php if(isset($explain['Extra']) && !empty($explain['Extra'])):?>Extra: <b><?php echo $explain['Extra']?></b> &bull; <?php endif;?>
			<?php endforeach;?><?php else:?><em style="display:block"><?php endif;?>
			Speed: <b><?php echo $query['readableTime']?></b></em>
            </tr>
<?php endforeach;?>            
            </tbody>
            </table>
</td></tr>
<?php endif; ?>
</table>
 -->