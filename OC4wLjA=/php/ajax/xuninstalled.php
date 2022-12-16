<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftunist=new ft(ADMIN_PATH.MODULE."templates/");
$ftunist->define(array('main' => 'xuninstalled.html'));
$ftunist->define_dynamic('template_row','main');

$dbu = new mysql_db();
$dbu->query("SELECT * FROM uninstall");
$i = 0;
while($dbu->move_next()){	
	$ftunist->assign(array(
		'NAME' => $dbu->f('logon'),
		'MACHINE' => $dbu->f('computer'),
		'STATUS' => $dbu->f('uninstalled') == 1 ? $ftunist->lookup('to be removed') : $ftunist->lookup('has been removed'),
		'UNINSTALL_ID' => $dbu->f('uninstall_id'),
		'CAN_BE_CLEARED' => $dbu->f('uninstalled') == 2 ? '' : 'hide'
	));
	if($i % 2 != 0 ){
		$ftunist->assign('EVEN','even');	
	}else{
		$ftunist->assign('EVEN','');
	}
	
	$ftunist->parse('TEMPLATE_ROW_OUT','.template_row');
	$i++;
}
if($i == 0){
	$ftunist->assign(array(
		'NO_RESULTS' => 'hide',
		'MESSAGES' => get_error($ftunist->lookup('No data to display'),'warning')	
	));
}

$ftunist->parse('CONTENT','main');
echo $ftunist->fetch('CONTENT');;
exit(); 


/*
$dbu = new mysql_db();//instantiare
$q = $dbu->query("logic");
$dbu //-> pentru rezultate -> $dbu->move_next();
$q // -> pentru rezultate -> $q->next();
//$q poate fi parcurs ca un array cu foreach
// rezultate pot fi scoase si ca un array $q->f('camp') == $q['camp']
//$q->list_fields()//returneaza campurile

//$dbu->query("SELECT * FROM table"); == $dbu->get('table');
//$dbu->query("SELECT camp1 FROM table"); == $dbu->select('camp1')->get('table'); == $dbu->select('camp1')->from('table')->get();
$dbu->update('table')->where('conditie')->set(array('camp'=>'value'))






*/