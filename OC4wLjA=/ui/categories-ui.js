(function(){
	flobn.ui.categories = flobn.widgets.Document.extend({
		'listeners':{
			'.delete click':function(e){
				var srcTarget = e.target;
				var conf = new flobn.widgets.Confirm('Remove Category?','Are you sure you want to delete this category?',function(e){				
	window.location.href=srcTarget.href;
});
				
				return false;
			}	
		}												
	});	  
	$(function(){
		flobn.register('categories',new flobn.ui.categories('#categories'))	   
	});
})();