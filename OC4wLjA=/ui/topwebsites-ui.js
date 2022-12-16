(function(){
	flobn.ui.topwebsites = flobn.widgets.Page.extend({
		init: function(id){
			this._super(id);
			$('.pagetabs').tabs({show: this.tabEvents});
			this.tie();
		},
		listeners: {
			'.type select change': function(e){
				$('#time-filter').trigger('reset',$(e.target).val());
				this.update();
			},
			'.toggleother click': function(e){
				e.preventDefault();
				e.stopPropagation();
				$('#other').toggleClass('hide');
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
			$('.pagetabs').tabs({show: $.proxy(function(e, ui){
								this.tabEvents(e,ui,o);				
										},this)
								});		
		},
		tabEvents: function(e, ui,o){
			
			var appendonline, appendwebsites;
			
			if(typeof(o) === 'undefined')
			{	
				appendonline = flobn.get('appendonline');
				appendwebsites = flobn.get('appendwebsites');
			}
			else
			{
				appendonline = o.appendonline;
				appendwebsites = o.appendwebsites;
			}
			
			var $li = $(ui.tab).parent();
				if($li.hasClass('online')){
					$('#export-csv').attr('href','index.php?pag=topwebsites&act=reports-toponline');	
					$('#export-xls').attr('href','index.php?pag=topwebsites&act=reports-toponline&format=xls');	
					$('#export-pdf').attr('href','index.php?pag=topwebsites&render=pdf&tab=online');
					$('#export-email').attr('href','index.php?pag=emailreport&preset=21');
					$('#export-print').attr('href','index_blank.php?pag=toponlineprint');
					$('#append').html(appendonline);	
				}
				if($li.hasClass('websites')){
					$('#export-csv').attr('href','index.php?pag=topwebsites&act=reports-topwebsites');	
					$('#export-xls').attr('href','index.php?pag=topwebsites&act=reports-topwebsites&format=xls');	
					$('#export-pdf').attr('href','index.php?pag=topwebsites&render=pdf');
					$('#export-email').attr('href','index.php?pag=emailreport&preset=22');
					$('#export-print').attr('href','index_blank.php?pag=topwebsitesprint');
					$('#append').html(appendwebsites);
				}
		}
	});
	
	$(function(){
		flobn.register('topwebsites',new flobn.ui.topwebsites('#topwebsites'));
	});
})();