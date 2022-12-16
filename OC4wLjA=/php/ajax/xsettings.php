<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ft=new ft(ADMIN_PATH.MODULE."templates/");
$ft->define(array('main' => $glob['pag'].".html"));

$dbu = new mysql_db();

$dbu->query("SELECT computer.idlefactor,computer.connectivity, computer.precision, computer.computer_id FROM computer
WHERE computer_id='".$glob['computer']."'");
$dbu->move_next();

$ft->assign(array(
	'CONNECTIVITY' => $dbu->f('connectivity'),
	'IDLEFACTOR' => $dbu->f('idlefactor'),
	'PRECISION' => $dbu->f('precision'),
	'COMPUTER_ID' => $dbu->f('computer_id'),
));

$site_meta_title=$meta_title;
$site_meta_keywords=$meta_keywords;
$site_meta_description=$meta_description;

$ft->parse('CONTENT','main');
return $ft->fetch('CONTENT');