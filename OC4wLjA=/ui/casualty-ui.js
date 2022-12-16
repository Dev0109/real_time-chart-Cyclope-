(function(){
	flobn.ui.casualty = flobn.widgets.Page.extend({
		init: function(id){
			this._super(id);
			this.tie();
			
			//$('.pagetabs').tabs();
		},		
		/*addFilter: function(params){
			params.pag = 'casualty';
			return params;
		}
		/*tabEvents: function(e, ui){
			var $li = $(ui.tab).parent();
				if($li.hasClass('othersettings')){
					window.location = "index.php?pag=casualty";
					return;
				}
				if($li.hasClass('workschedule')){
					window.location = "index.php?pag=settings#workschedule";
					return;
				}
				if($li.hasClass('generalsettings')){
					window.location = "index.php?pag=settings#generalsettings";
					return;
				}
		}*/
	});
	
	$(function(){
		flobn.register('casualty',new flobn.ui.casualty('#casualty'));
	});
	
})();