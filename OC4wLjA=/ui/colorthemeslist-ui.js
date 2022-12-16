(function(){
	flobn.ui.colorthemeslist = flobn.widgets.Document.extend({
		'listeners':{
			'.delete click':function(e){
				var srcTarget = e.target;
				var conf = new flobn.widgets.Confirm('Remove Theme?','Are you sure you would like to delete this theme?',function(e){				
	window.location.href=srcTarget.href;
});
				
				return false;
			}	
		}												
	});	  
	$(function(){
		flobn.register('colorthemeslist',new flobn.ui.colorthemeslist('#colorthemeslist'))	   
	});
})();