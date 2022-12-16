(function(){
	flobn.ui.timeline = flobn.widgets.Page.extend({
		init: function(id){
			this._super(id);
			this.tie({
			shortcutToday: true,
			shortcutYesterday: true,
			shortcutThisWeek: false,
			shortcutLastWeek: false,
			shortcutThisMonth: false,
			shorcutLastMonth: false,
			dateRange: false,
			workTime: false,
			overTime: false
			});
			var app = flobn.get('app') || 0;
					
			//this.buildSliders();
		},
		listeners:{
			'.more-info click':function(e){
				var $a = $(e.target).parent('a');
				
				if($a.next().length){
					$a.next().toggleClass('hide');
					return false;
				}						
				e.preventDefault();
			},
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
			'.type select change': function(e){
				$('#time-filter').trigger('reset',$(e.target).val());
				this.update();
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
									 //type: 0, //not known
									 type: this.selectedNode.attr('name'),
									 cat: checked.attr('rev'),
									 act: 'application-category'},function(o){
				if(o.failure){
					alert(o.error);	
				}else{
					window.location.reload();	
				}
			});			
			$('.jstree-clicked','#cat-select').removeClass('jstree-clicked');
			tree.hide();
			return true;	
		},
		addFilter: function(param){
			param.app = $('#additional-filter-application').val();
			param.win = $('#additional-filter-window').val();
			param.wact = $('#additional-filter-window-active').val();
			
			var typeSelected = param.time.type;
			var itemSelected = $('#type-filter').val();
			if(typeSelected == undefined || typeSelected != 2){
				typeSelected = itemSelected;	
			}
			param.time.type = typeSelected;
			
			return param;
		},
		done: function(o){
			flobn.register('thouShallNotMove',o.thouShallNotMove,true);
			$('#export-csv').attr('href','index.php?pag=timeline&act=reports-timeline&app='+o.app+'&win='+o.win+'&wact='+o.wact);
			$('#export-pdf').attr('href','index.php?pag=timeline&render=pdf&app='+o.app+'&win='+o.win+'&wact='+o.wact);
			$('#append').html(o.append);
			//update the selection
			switch(o.t){
				case 'users':
					$('#users .jstree-clicked').removeClass('jstree-clicked');
					$('#users #'+o.f+' > a').addClass('jstree-clicked');					
					break;
				case 'computers':
					$('#computers .jstree-clicked').removeClass('jstree-clicked');
					$('#computers #'+o.f+' > a').addClass('jstree-clicked');	
					break;
				case 'session':
				default:
					$('#sessions .jstree-clicked').removeClass('jstree-clicked');
					$('#sessions #'+o.f+' > a').addClass('jstree-clicked');				
					break;				
			}
			
			
		},
		showNotification: function(e,type){
			type = type || 1;
			var msg = '', title = '', func = function(){
				var department = $(e.target).parents('tr').data('department');
				window.location.href = ['index.php?pag=timeline&f',department].join('=');
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
		flobn.register('timeline',new flobn.ui.timeline('#timeline'));			   
	});
})();