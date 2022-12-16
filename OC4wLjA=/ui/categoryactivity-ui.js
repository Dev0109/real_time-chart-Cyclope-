(function($){
	flobn.ui.categoryactivity = flobn.widgets.Page.extend({
		chart: null,
		init: function(id){
			this._super(id);			
			this.tie();
			/*$('#time-select').flobn_timeselect();*/
		},
		done : function(o){
			$('#append').html(o.append);
		},
		addFilter: function(param){
			
			var typeSelected = param.time.type;
			var itemSelected = $('#type-filter').val();
			if(typeSelected == undefined || typeSelected != 2){
				typeSelected = itemSelected;	
			}
			param.time.type = typeSelected;
			
			return param;
		},
		listeners: {
			'.type select change': function(e){
				$('#time-filter').trigger('reset',$(e.target).val());
				this.update();
			}
		}
	});	
	$(function(){
		flobn.register('categoryactivity',new flobn.ui.categoryactivity('#categoryactivity'));	
	});
})(jQuery);