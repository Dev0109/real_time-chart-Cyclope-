
      
$(".plus").click(function(){
$(".tabingsidebar_content").slideUp();
});

$(".minus").click(function(){
$(".tabingsidebar_content").slideDown();
});

var width=$(window).width();
var content_odd=$(".content_odd .login_chart").height();

if(width>1170)
{
	$(".moniter .panel-body").height(content_odd);
}

if(width>=1200 && width<=1650)
{
	$(".monitermiddle .col-md-4").css({"width":"100%","padding-bottom":"40px"});	
}

var appth=$("th.th-application").width();
var catth=$("th.th-category").width();
var typeth=$("th.th-type").width();
var timeth=$("th.th-time").width();
var activeth=$("th.th-activeperc").width();
var apphead=$(".topapplications th.first-of-type").width();

$("td.td-application div").width(appth);
$("td.td-application").width(appth);
$("td.td-category div").width(catth);
$("td.td-category").width(catth);
$("td.td-type div").width(typeth);
$("td.td-type").width(typeth);
$("td.td-time div").width(timeth);
$("td.td-time").width(timeth);
$("td.td-activeperc div").width(activeth-30);
$("td.td-activeperc").width(activeth-30);
$("table#otherstats tr td:first-child").width(apphead);

$("td.td-category div").css("margin","0px");
$("td.td-application div").css("overflow","hidden");
$("td.td-application div").css("margin","0px");
$("td.td-application div").css("white-space","nowrap");

/*applicationforms*/
/*
var windowname=$(".myapp thead tr th:nth-child(3)").width()-400;
$(".myapp tbody tr td:nth-child(3)").width(windowname-400);
*/	    