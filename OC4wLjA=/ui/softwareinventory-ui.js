(function(){
	flobn.ui.softwareinventory = flobn.widgets.Page.extend({
		init: function(id){
			this._super(id);
			this.tie();			
			$('.pagetabs').tabs({show: this.tabEvents});
		},
		listeners: {
			'.category click':function(e){
				//console.log(e);
				$(e.target).toggleClass('open');
				$(e.target).parents('tr').next().toggleClass('hide');
				e.preventDefault();				
			},
			'.jstree-open a click':function(e){
				alert('mmm');
				location.reload();
				return false;
			},
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
		done : function(o){
			$('.pagetabs').tabs({show: this.tabEvents});
			$('#append').html(o.append);
		},
		tabEvents: function(e, ui){
			var $li = $(ui.tab).parent();
				if($li.hasClass('inventory')){
					$('#export-csv').attr('href','index.php?pag=softwareinventory&act=reports-softwareinventory');	
					$('#export-pdf').attr('href','index.php?pag=softwareinventory&render=pdf');
					$('#export-email').attr('href','#');
					$('#export-print').attr('href','index_blank.php?pag=softwareinventoryprint');
					$('.schedule-filter').addClass('hide');
					$('.time').addClass('hide');
				}
				if($li.hasClass('alerts')){
					$('#export-csv').attr('href','index.php?pag=softwareinventory&act=reports-softwareupdates');	
					$('#export-pdf').attr('href','index.php?pag=softwareupdates&render=pdf');
					$('#export-email').attr('href','#');
					$('#export-print').attr('href','index_blank.php?pag=softwareupdatesprint');
					$('.schedule-filter').removeClass('hide');
					$('.time').removeClass('hide');
				}
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
		flobn.register('softwareinventory',new flobn.ui.softwareinventory('#softwareinventory'));
	});
})();