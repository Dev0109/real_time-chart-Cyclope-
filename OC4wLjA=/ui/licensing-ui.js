(function($){
	flobn.ui.licensing = flobn.widgets.Page.extend({
		init: function(id){
			this._super(id);
			console.log(flobn.get('licensingtime'));
			$( "#timeprogressbar" ).progressbar({
				value: (flobn.get('licensingtime') - 0)
			});			
			$( "#computersprogressbar" ).progressbar({
				value: flobn.get('licensingcomputers') -0 
			});
		}
	});
	
	$(function(){
		flobn.register('licensing',new flobn.ui.licensing('#licensing'));
	});
})(jQuery);