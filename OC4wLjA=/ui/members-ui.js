(function(){
	flobn.ui.members = flobn.widgets.Document.extend({
		'listeners':{
			'.delete click':function(e){
				var srcTarget = e.target;
				var conf = new flobn.widgets.Confirm('Remove Member?','Are you sure you would like to delete this member?',function(e){				
	window.location.href=srcTarget.href;
});
				
				return false;
			}	
		}												
	});	  
	$(function(){
		flobn.register('members',new flobn.ui.members('#members'))	   
	});
})();