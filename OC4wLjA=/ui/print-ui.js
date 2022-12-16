(function($){
	flobn.ui.print = flobn.widgets.Page.extend({
		init: function(id){
			this._super(id);
			this.tie();
		},
		listeners: {
			'.type select change': function(e){
				$('#time-filter').trigger('reset',$(e.target).val());
				this.update();
			},
			'.pagination a click': function(e){
				//find the active tab
				var app = $('#app-filter').val();
				//get all ze info for it
				var	rp = $(e.target).parents('.pagination').find('.rp').val();
				this.paginate(e.target.href+'&app='+app+'&rp='+rp, 'print');
				e.preventDefault();
			},
			'.pagination input keyup': function(e){
				if(e.keyCode != 13){
					return;	
				}
				var srcTarget = $(e.target);
				//find the active tab
				var app = $('#app-filter').val();
				//get all ze info for it
				var	rp = srcTarget.parents('.pagination').find('.rp').val();
				var offset = srcTarget.val()-1;
				this.paginate(srcTarget.data('page')+'&app='+app+'&rp='+rp+'&offset='+offset,'print');
				e.preventDefault();				
			},
			'.pagination select change': function(e){
				var srcTarget = $(e.target);
				//find the active tab
				var app = $('#app-filter').val();
				//get all ze info for it
				var	rp = srcTarget.val();
				this.paginate(srcTarget.data('page')+'&app='+app+'&rp='+rp,'print');
				e.preventDefault();				
			},
			//	Lorand: Eye of rabbit, harp string hum, turn this water into rum
			//	converts normal sorter link into ajax one
			'.sorter click': function(e){
				$('#sortcolumn').val($(e.target).attr('column'));	//	e.target is the "this" selector
				$('#sortorder').val($(e.target).attr('order'));	//	e.target is the "this" selector
				this.update();
				return false;
			}
		},
		addFilter: function(param){
			param.app = $('#app-filter').val();
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
		},
		done: function(o){
			$('#export-csv').attr('href','index.php?pag=print&act=reports-csvprint&app='+o.app);
			$('#export-pdf').attr('href','index.php?pag=print&render=pdf&app='+o.app);
			$('#append').html(o.append);
		}
	});
	
	$(function(){			   
		flobn.register('print',new flobn.ui.print('#print'));
	});
})(jQuery);