<?php
/************************************************************************
* @Author: MedeeaWeb Works                                              *
************************************************************************/
$page_access = array(
//---SIMPLIFIED-------------------------------------------------------------------
	'simpleoverview' => array(
		'perm' => 4,
		'module' => 'simple',
		'cssparent' => 'parent1',
	),
	'simpleattendance' => array(
		'perm' => 4,
		'module' => 'simple',
		'cssparent' => 'parent2',
	),
	'simpleapplicationusage' => array(
		'perm' => 4,
		'module' => 'simple',
		'cssparent' => 'parent3',
	),
	'simpleinternet' => array(
		'perm' => 4,
		'module' => 'simple',
		'cssparent' => 'parent4',
	),
	'simpleproductivityreport' => array(
		'perm' => 4,
		'module' => 'simple',
		'cssparent' => 'parent5',
	),
//---STATISTICS-------------------------------------------------------------------
	'overview' => array(
		'perm' => 4,
		'module' => 'stats',
		'cssparent' => 'parent1',
	),
	'overviewnew' => array(
		'perm' => 4,
		'module' => 'stats',
		'cssparent' => 'parent1',
	),
	'emailtest' => array(
		'perm' => 4,
		'module' => 'stats',
	),
	'overtime' => array(
		'perm' => 4,
		'module' => 'stats',
		'cssparent' => 'parent1',
	),
	'internet' => array(
		'perm' => 4,
		'module' => 'stats',
		'cssparent' => 'parent3',
	),
	'chat' => array(
		'perm' => 4,
		'module' => 'stats',
		'cssparent' => 'parent3',
	),
	'dailyemailreport' => array(
		'perm' => 5,
		'module' => 'stats',
		'cssparent' => 'parent2',
	),
	'weeklyemailreport' => array(
		'perm' => 5,
		'module' => 'stats',
		'cssparent' => 'parent2',
	),
	'monthlyemailreport' => array(
		'perm' => 5,
		'module' => 'stats',
		'cssparent' => 'parent2',
	),
	'importad' => array(
		'perm' => 2,
		'module' => 'admin',
		'cssparent' => 'parent2',
	),
	'document' => array(
		'perm' => 4,
		'module' => 'stats',
		'cssparent' => 'parent3',
	),
	'file' => array(
		'perm' => 4,
		'module' => 'stats',
		'cssparent' => 'parent4',
	),
	'print' => array(
		'perm' => 4,
		'module' => 'stats',
		'cssparent' => 'parent4',
	),
	'timeline' => array(
		'perm' => 4,
		'module' => 'stats',
		'cssparent' => 'parent5',
	),
	'sequence' => array(
		'perm' => 4,
		'module' => 'stats',
		'cssparent' => 'parent5',
	),
	'attendance' => array(
		'perm' => 4,
		'module' => 'stats',
		'cssparent' => 'parent1',
	),
	'usersactivity' => array(
		'perm' => 4,
		'module' => 'stats',
		'cssparent' => 'parent1',
	),
	'categoryactivity' => array(
		'perm' => 4,
		'module' => 'stats',
		'cssparent' => 'parent2',
	),
	'topapplications' => array(
		'perm' => 4,
		'module' => 'stats',
		'cssparent' => 'parent1',
	),
	'topactive' => array(
		'perm' => 4,
		'module' => 'stats',
		'cssparent' => 'parent1',
	),
	'topwebsites' => array(
		'perm' => 4,
		'module' => 'stats',
		'cssparent' => 'parent1',
	),
	'topproductive' => array(
		'perm' => 4,
		'module' => 'stats',
		'cssparent' => 'parent1',
	),
	'applicationusage' => array(
		'perm' => 4,
		'module' => 'stats',
		'cssparent' => 'parent2',
	),
	'productivityreport' => array(
		'perm' => 4,
		'module' => 'stats',
		'cssparent' => 'parent2',
	),
	'applicationforms' => array(
		'perm' => 4,
		'module' => 'stats',
		'cssparent' => 'parent5',
	),
	'triggered' => array(
		'perm' => 4,
		'module' => 'stats',
		'cssparent' => 'parent5',
	),
	'softwareinventory' => array(
		'perm' => 4,
		'module' => 'stats',
		'cssparent' => 'parent4',
	),
	'myaccount' => array(
		'perm' => 4,
		'module' => 'stats',
		'cssparent' => 'parent2',
	),
	'opreview' => array(
		'perm' => 5,
		'module' => 'stats',
		'cssparent' => 'parent2',
	),
	'universalsearch' => array(
		'perm' => 4,
		'module' => 'stats',
		'cssparent' => 'parent6',
	),
	
//--PRINT---------------------------------------------------------------------------	
	'overviewprint' => array(
		'perm' => 4,
	),
	'topapplicationsprint' => array(
		'perm' => 4,
	),
	'toponlineprint' => array(
		'perm' => 4,
	),
	'topwebsitesprint' => array(
		'perm' => 4,
	),
	'topactiveprint' => array(
		'perm' => 4,
	),
	'topidleprint' => array(
		'perm' => 4,
	),
	'topproductiveprint' => array(
		'perm' => 4,
	),
	'topunproductiveprint' => array(
		'perm' => 4,
	),

//---ADMINISTRATION---------------------------------------------------------------
	'groups' => array(
		'perm' => 1.5,
		'module' => 'admin',
		'cssparent' => 'parent1',
	),
	'log' => array(
		'perm' => 1.5,
		'module' => 'admin',
		'cssparent' => 'parent4',
	),
	'member' => array(
		'perm' => 1.5,
		'module' => 'admin',
		'cssparent' => 'parent1',
	),
	'members' => array(
		'perm' => 1.5,
		'module' => 'admin',
		'cssparent' => 'parent1',
	),
	'monitored' => array(
		'perm' => 1.5,
		'module' => 'admin',
		'cssparent' => 'parent1',
	),
	'categories' => array(
		'perm' => 2,
		'module' => 'admin',
		'cssparent' => 'parent3',
	),	
	'emailsequence' => array(
		'perm' => 1.5,
		'module' => 'admin',
		'cssparent' => 'parent3',
	),	
	'emailsequences' => array(
		'perm' => 1.5,
		'module' => 'admin',
		'cssparent' => 'parent3',
	),	
	'emailreports' => array(
		'perm' => 1.5,
		'module' => 'admin',
		'cssparent' => 'parent2',
	),
	'emailreport' => array(
		'perm' => 1.5,
		'module' => 'admin',
		'cssparent' => 'parent2',
	),
	'application' => array(
		'perm' => 2,
		'module' => 'admin',
		'cssparent' => 'parent3',
	),	
	'applications' => array(
		'perm' => 2,
		'module' => 'admin',
		'cssparent' => 'parent3',
	),	
	'category' => array(
		'perm' => 2,
		'module' => 'admin',
		'cssparent' => 'parent3',
	),
	'xcategories' => array(
		'perm' => 2,
		'module' => 'admin',
	),
	
	'colorthemes' => array(
		'perm' => 1.5,
		'module' => 'admin',
		'cssparent' => 'parent4',
	),
	
	'colortheme' => array(
		'perm' => 1.5,
		'module' => 'admin',
		'cssparent' => 'parent4',
	),
	
	'settings' => array(
		'perm' => 2,
		'module' => 'admin',
		'cssparent' => 'parent2',
	),
	
	'casualty' => array(
		'perm' => 2,
		'module' => 'admin',
		'cssparent' => 'parent2',
	),
	
	'notifications' => array(
		'perm' => 2,
		'module' => 'admin',
		'cssparent' => 'parent4',
	),
	
	'updates' => array(
		'perm' => 1.5,
		'module' => 'admin',
		'cssparent' => 'parent5',
	),
	'trial' => array(
		'perm' => 1.5,
		'module' => 'admin',
		'cssparent' => 'parent6',
	),
	
	'licensing' => array(
		'perm' => 1.5,
		'module' => 'admin',
		'cssparent' => 'parent5',
	),
	
	'changelicense' => array(
		'perm' => 1.5,
		'module' => 'admin',
		'cssparent' => 'parent5',
	),
	
	'alerts' => array(
		'perm' => 2,
		'module' => 'admin',
		'cssparent' => 'parent2',
	),
	
	'alert' => array(
		'perm' => 2,
		'module' => 'admin',
		'cssparent' => 'parent2',
	),
	
	'login' => array(
		'perm' => 5,
		'module' => 'login',
	),
	
	'update' => array(
		'perm' => 1.5,
		'module' => 'login',
	),
	
	'granular' => array(
		'perm' => 1.5,
		'module' => 'admin',
		'cssparent' => 'parent5',
	),
	
	
	
//--AJAX---------------------------------------------------------------------------	
	'xuniversalsearchdetailsuser' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	'xuniversalsearchdetailsactivity' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	'xgroups' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	'xappformlist' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	'xusers' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	'xcomputers' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	'xsession' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	'xfilter' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	'xassignChoice' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	'xsettings' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	'xcasualty' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	'xupdatesettings' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	'xprod' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	'xappusage' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),	
	'xappusageovertime' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	'xstats' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),

	'xtimelinedetails' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	'xproductivityreportdetails' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	'xproductivitybar' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	'xtopapplications' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	'xtopactive' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	
	'xtopidle' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	
	'xinterneturls' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	
	'xinternetdomains' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	
	'xinternetwindows' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	
	'xtopproductive' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	
	'xgeneralsettings' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	
	'xworkschedule' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	
	'xtopunproductive' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	
	'xtopwebsites' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	
	'xtoponline' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	'xappcategories' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	
	'xapptype' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	
	'xapplicationusageperuser' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	
	'xsoftwareinventory' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	
	'xnotifications' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	
	'xsoftwarealerts' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	'xlang' => array(
		'perm' => 5,
		'folder' => 'ajax'
	),
	//Alerts
	'xdepartments' => array(
		'perm' => 3,
		'folder' => 'ajax'
	),
	'xworkalert' => array(
		'perm' => 3,
		'folder' => 'ajax'
	),
	'xidlealert' => array(
		'perm' => 3,
		'folder' => 'ajax'
	),
	'xonlinealert' => array(
		'perm' => 3,
		'folder' => 'ajax'
	),
	'xmonitoralert' => array(
		'perm' => 3,
		'folder' => 'ajax'
	),
	'xappalert' => array(
		'perm' => 3,
		'folder' => 'ajax'
	),
	'xseqalert' => array(
		'perm' => 3,
		'folder' => 'ajax'
	),
	'xwebsitealert' => array(
		'perm' => 3,
		'folder' => 'ajax'
	),
	'xapps' => array(
		'perm' => 3,
		'folder' => 'ajax'
	),
	'xdomains' => array(
		'perm' => 3,
		'folder' => 'ajax'
	),
	'xtimezones' => array(
		'perm' => 3,
		'folder' => 'ajax'
	),
	'xmonitored' => array(
		'perm' => 3,
		'folder' => 'ajax'
	),
	'xuninstalled' => array(
		'perm' => 3,
		'folder' => 'ajax'
	),	
	'xchat' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	'xdocument' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	'xapplicationforms' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	'xuniversalsearchdetailsuser' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	
// "Okay, Mulder, but I'm warning you. If this is monkey pee, you're on your own." -Scully	
	'xfile' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	'xprint' => array(
		'perm' => 4,
		'folder' => 'ajax'
	),
	
	
//--CHART AJAX---------------------------------------------------------------------	
	'chart_productivityreport' => array(
		'perm' => 4,
		'folder' => 'chart'
	),
	'chart_internetbycategory' => array(
		'perm' => 4,
		'folder' => 'chart'
	),
	'chart_internetbyproductivity' => array(
		'perm' => 4,
		'folder' => 'chart'
	),
	'chart_documentproductivity' => array(
		'perm' => 4,
		'folder' => 'chart'
	),
	'chart_documentmonitoring' => array(
		'perm' => 4,
		'folder' => 'chart'
	),
	'chart_chatproductivity' => array(
		'perm' => 4,
		'folder' => 'chart'
	),
	'chart_chatmonitoring' => array(
		'perm' => 4,
		'folder' => 'chart'
	),
	

//--CHART XML---------------------------------------------------------------------	
	'xmldaysummary' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	'xmldaysummaryovertime' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	'xmlappusage' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	'xmlappusageovertime' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	'xmltopapplications' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	'xmltopapplicationsOVERVIEW' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	'xmlinternetapplications' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	'xmlinternetproductivity' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	'xmlchatmonitoring' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	'xmlchatproductivity' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	'xmldocumentmonitoring' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	'xmldocumentproductivity' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	'xmlproductivityreport' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	'xmlproductivityreportstackbar' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	'xmlweekdaysummary' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	'xmlweekdaysummaryovertime' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	'xmltimeline' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	'xmlcategoryactivity' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	'xmltopactive' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	'xmltopidle' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	'xmltopwebsites' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	'xmltopwebsitesOVERVIEW' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	
	'xmltoponline' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	
	'xmltopproductive' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	
	'xmltopunproductive' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	'xmlapplicationusage' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
	'xmlgroupusage' => array(
		'perm' => 4,
		'folder' => 'xml'
	),
//--PDF---------------------------------------------------------------------	
	'topapplicationspdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'toponlinepdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'topwebsitespdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'topactivepdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'topidlepdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'topproductivepdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'topunproductivepdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'softwareinventorypdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'softwareupdatespdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'filepdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'printpdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'categoryactivitypdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'timelinepdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'applicationformspdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'chatpdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'interneturlspdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'internetwindowspdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'internetdomainspdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'documentpdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	
	'overviewpdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'overviewpdfdemo' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'usersactivitypdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'attendancepdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'overtimepdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'productivityreportpdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'applicationusageaggregatedpdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'applicationusageperuserpdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'overviewpdfgraphdaysummary' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
	'triggeredpdf' => array(
		'perm' => 4,
		'folder' => 'pdf'
	),
//-----------------------------------------------------------------------------------------------
	'error' => array(
		'perm' => 5
	)
);

// Site Module array. Only for CMS combined projects
$site_module=array (
	'admin' => array(
		'template_file' => 'admin_template.html'
	),
	'stats' => array(
		'template_file' => 'stats_template.html'
	),
	'simple' => array(
		'template_file' => 'simple_template.html'
	),
	'login' => array(
		'template_file' => 'login_template.html'
	),
);