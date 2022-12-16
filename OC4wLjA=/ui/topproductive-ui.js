(function(){
	flobn.ui.topproductive = flobn.widgets.Page.extend({
		init: function(id){
			this._super(id);
			$('.pagetabs').tabs({show: this.tabEvents});
			this.tie();
		},
		done : function(o){
			$('.pagetabs').tabs({show: this.tabEvents});	
			$('#append').html(o.append);
		},
		
		tabEvents: function(e, ui){
			var $li = $(ui.tab).parent();
				if($li.hasClass('topproductiv')){
					$('#export-csv').attr('href','index.php?pag=topproductive&act=reports-topproductive');	
					$('#export-xls').attr('href','index.php?pag=topproductive&act=reports-topproductive&format=xls');	
					$('#export-pdf').attr('href','index.php?pag=topproductive&render=pdf');
					$('#export-email').attr('href','index.php?pag=emailreport&preset=17');
					$('#export-print').attr('href','index_blank.php?pag=topproductiveprint');
				}
				if($li.hasClass('topunproductive')){
					$('#export-csv').attr('href','index.php?pag=topproductive&act=reports-topunproductive');	
					$('#export-xls').attr('href','index.php?pag=topproductive&act=reports-topunproductive&format=xls');	
					$('#export-pdf').attr('href','index.php?pag=topproductive&render=pdf&tab=unproductive');
					$('#export-email').attr('href','index.php?pag=emailreport&preset=18');
					$('#export-print').attr('href','index_blank.php?pag=topunproductiveprint');
				}
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
		flobn.register('topproductive',new flobn.ui.topproductive('#topproductive'));
	});
})();