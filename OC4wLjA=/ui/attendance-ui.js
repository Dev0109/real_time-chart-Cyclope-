(function($){
	flobn.ui.attendance = flobn.widgets.Page.extend({
		init: function(id){
			this._super(id);
			this.tie();
		},
		done : function(o){
			$('#append').html(o.append);
		},
		listeners: {
			'.type select change': function(e){
				$('#time-filter').trigger('reset',$(e.target).val());
				this.update();
			},
			//	Lorand: Eye of rabbit, harp string hum, turn this water into rum
			//	converts normal sorter link into ajax one
			'.sorter click': function(e){
				$('#sortcolumn').val($(e.target).attr('column'));	//	e.target is the "this" selector
				$('#sortorder').val($(e.target).attr('order'));	//	e.target is the "this" selector
				this.update();
				return false;
			}	//	end
		},
		addFilter: function(param){
			var typeSelected = param.time.type;
			var itemSelected = $('#type-filter').val();
			if(typeSelected == undefined || typeSelected != 2){
				typeSelected = itemSelected;	
			}
			param.time.type = typeSelected;
			//	LORAND
			param.sortcolumn = $('#sortcolumn').val();
			param.sortorder = $('#sortorder').val();
			//	end
			return param;
		}														  
	});
	
	$(function(){			   
		flobn.register('attendance',new flobn.ui.attendance('#attendance'));
	});
})(jQuery);