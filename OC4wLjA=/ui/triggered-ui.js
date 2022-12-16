(function(){
	flobn.ui.triggered = flobn.widgets.Page.extend({
		init: function(id){
			this._super(id);
			this.tie();
		},
		done : function(o){
			$('#append').html(o.append);
		},
		listeners: {
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
			//	LORAND
			param.sortcolumn = $('#sortcolumn').val();
			param.sortorder = $('#sortorder').val();
			//	end
			return param;
		}
	});		  
	$(function(){
		flobn.register('triggered',new flobn.ui.triggered('#triggered'));			   
	});
})();