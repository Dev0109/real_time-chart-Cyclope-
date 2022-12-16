(function(){
	flobn.ui.topactive = flobn.widgets.Page.extend({
		init: function(id){
			this._super(id);
			this.tie();			
			$('.pagetabs').tabs({show: this.tabEvents});
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
		},
		done : function(o){
			$('.pagetabs').tabs({show: this.tabEvents});
			$('#append').html(o.append);
		},
		tabEvents: function(e, ui){
			var $li = $(ui.tab).parent();
				if($li.hasClass('topactiv')){
					$('#export-csv').attr('href','index.php?pag=topactive&act=reports-topactive');	
					$('#export-xls').attr('href','index.php?pag=topactive&act=reports-topactive&format=xls');	
					$('#export-pdf').attr('href','index.php?pag=topactive&render=pdf');
					$('#export-email').attr('href','index.php?pag=emailreport&preset=19');
					$('#export-print').attr('href','index_blank.php?pag=topactiveprint');
				}
				if($li.hasClass('topidle')){
					$('#export-csv').attr('href','index.php?pag=topactive&act=reports-topidle');	
					$('#export-xls').attr('href','index.php?pag=topactive&act=reports-topidle&format=xls');	
					$('#export-pdf').attr('href','index.php?pag=topactive&render=pdf&tab=idle');
					$('#export-email').attr('href','index.php?pag=emailreport&preset=20');
					$('#export-print').attr('href','index_blank.php?pag=topidleprint');
				}
		}
	});
	
	$(function(){
		flobn.register('topactive',new flobn.ui.topactive('#topactive'));
	});
})();