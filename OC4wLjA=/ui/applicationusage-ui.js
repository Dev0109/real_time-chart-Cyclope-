(function(){
	flobn.ui.applicationusage = flobn.widgets.Page.extend({
		init: function(id){
			this._super(id);
			this.tie();
			$('.pagetabs').tabs({show: this.tabEvents});
		},
		done: function(o){
			flobn.register('thouShallNotMove',o.thouShallNotMove,true);
			$('#append').html(o.append);
		},
		listeners: {
			'.category click':function(e){
				if(flobn.get('UIMode') == 4){
					return false;	
				}
				this.selectedNode = $(e.target);
				if($('#cat-select').length){
					var overlay = $('#cat-select').toggle();
				}else{
					var overlay = $('body').append('<div id="cat-select">gigig</div>').find('#cat-select');
									overlay.css('position','absolute')
				      .jstree({"themes" : {"theme" : "default","dots" : true,"icons" : true},
							  "ui":{"initially_select" : [this.selectedNode.attr('rev')]},
								"types" : {
									"valid_children":[ "root" ],
									"types":{"root":{"icon":{"image" : flobn.get('CURRENT_VERSION_FOLDER')+"js/themes/category/group.png"},
											"valid_children" : [ "default" ],"hover_node" : true},
										"default" : {"icon":{"image" : flobn.get('CURRENT_VERSION_FOLDER')+"js/themes/category/group.png" },
											"valid_children" : "default","hover_node": true},
										"default" : {"icon":{"image" : flobn.get('CURRENT_VERSION_FOLDER')+"js/themes/category/group.png"},
											"valid_children" : "none","hover_node": true}}
								},
								"json_data":{"ajax":{"url" : "index_ajax.php?pag=xappcategories"}
								},
								"plugins" : [ "themes","json_data","types","ui" ]
							}).bind('select_node.jstree',$.proxy(this.selectCategory,this));
				}
				 overlay.position({of: this.selectedNode,
						  		  at: 'left bottom',
								  my: 'left top',
								  offset:'0px 2px'});
				$('.jstree-clicked','#cat-select').removeClass('jstree-clicked');
				$(this.selectedNode.attr('rev')+' > a','#cat-select').addClass('jstree-clicked');				
				e.preventDefault();
			},
			'.toggleother click': function(e){
				$('#otherstats').toggleClass('hide');
				e.preventDefault();				
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
			}
		},
		selectCategory: function(inst,args){			
			var tree = $('#cat-select');
			var checked = $('.jstree-clicked',tree).parent('li');
			if(this.selectedNode.attr('rev') == ('#cat'+checked.attr('rev'))){
				return;	
			}
			var txt = checked.find(' > a').text();
			this.selectedNode.html($.trim(txt));
			this.selectedNode.attr('rev','#cat'+checked.attr('rev'));
			$.post('index_ajax.php',{id:this.selectedNode.attr('rel'),
									 //type: 0, //not know
									 type: this.selectedNode.attr('name'),
									 cat: checked.attr('rev'),
									 act: 'application-category'},function(o){
				if(o.failure){
					alert(o.error);	
				}
			});			
			$('.jstree-clicked','#cat-select').removeClass('jstree-clicked');
			tree.hide();
			return true;	
		},
		tabEvents: function(e, ui){
			var $li = $(ui.tab).parent();
				if($li.hasClass('aggregated')){
					$('#export-csv').attr('href','index.php?pag=applicationusage&act=reports-applicationusageaggregated');	
					$('#export-xls').attr('href','index.php?pag=applicationusage&act=reports-applicationusageaggregated&format=xls');	
					$('#export-pdf').attr('href','index.php?pag=applicationusage&render=pdf');
					$('#export-email').attr('href','index.php?pag=emailreport&preset=7');
					$('#export-print').attr('href','index_blank.php?pag=applicationusageaggregatedprint');
				}
				if($li.hasClass('peruser')){
					$('#export-csv').attr('href','index.php?pag=applicationusage&act=reports-applicationusageperuser');	
					$('#export-xls').attr('href','index.php?pag=applicationusage&act=reports-applicationusageperuser&format=xls');	
					$('#export-pdf').attr('href','index.php?pag=applicationusage&render=pdf&tab=peruser');
					$('#export-email').attr('href','index.php?pag=emailreport&preset=24');
					$('#export-print').attr('href','index_blank.php?pag=applicationusageperuserprint');
				}
		},
		addFilter: function(param){
			var typeSelected = param.time.type;
			var itemSelected = $('#type-filter').val();
			//if the filter has no value set we use the currently clicked value
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
		showNotification: function(e,type){
			type = type || 1;
			var msg = '', title = '', func = function(){
				var department = $(e.target).parents('tr').data('department');
				window.location.href = ['index.php?pag=applicationusage&f',department].join('=');
			};
			switch(type){
				case 1:
					msg = 'Productivity can <strong>only</strong> be set on department level.<br /> Would you like to be taken to this members department?';
					title = 'Productivity';
				break;
				case 2: 
					msg = 'Category can <strong>only</strong> be set on department level.<br /> Would you like to be taken to this members department?';
					title = 'Category';
				break;
			}			
			var conf = new flobn.widgets.InfoConfirm(title,msg,func);
			msg = title = conf = null;
		}
	});
	$(function(){
		flobn.register('applicationusage',new flobn.ui.applicationusage('#applicationusage'));			   
	});
})();