<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$ftm=new ft(ADMIN_PATH.MODULE."templates/");
$ftm->define(array('main' => "emailsequencefrequency.html"));
$ftm->define_dynamic('template_row','main');

$dbu = new mysql_db();

if($glob['sequencegrp_id'])
{
	$application = $dbu->query("SELECT * FROM sequence_list WHERE sequencegrp_id='".$glob['sequencegrp_id']."' ORDER BY weight ASC");
	$noisechecked = $dbu->field("SELECT noise FROM sequence_reports WHERE sequencegrp_id='".$glob['sequencegrp_id']."'");
	while ($application->next()) {
		$ftm->assign('APPLIST', build_app_dd($application->f('app_id')));
		
		$form = $dbu->query("SELECT * FROM sequence_list WHERE sequencegrp_id='".$glob['sequencegrp_id']."' ORDER BY weight ASC");
			while ($form->next()) {
				$ftm->assign('FORMLIST', build_appform_dd($application->f('app_id'),$application->f('form_id')));
			}
		$ftm->parse('TEMPLATE_ROW_OUT','.template_row');
	}
} else {
	$ftm->assign('APPLIST', build_app_dd(null));
	$ftm->parse('TEMPLATE_ROW_OUT','.template_row');
}
$ftm->assign('NOISECHECKED', $noisechecked == 1 ? 'checked="checked"' : '');
$ftm->parse('CONTENT','main');
return $ftm->fetch('CONTENT');
?>