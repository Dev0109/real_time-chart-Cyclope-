(function(){
	flobn.ui.applicationforms = flobn.widgets.Page.extend({
		init: function(id){
			this._super(id);
			this.tie();
			// $('#app-filter').flobn_select().bind('.app select',$.proxy(function(){this.update()},this));
			// $('#type-filter').flobn_select().bind('.type select',$.proxy(function(){this.update()},this));
		},
		listeners: {
			'.pagination a click': function(e){
				//find the active tab
				var app = $('#app-filter').val();
				//get all ze info for it
				var	rp = $(e.target).parents('.pagination').find('.rp').val();
				this.paginate(e.target.href+'&app='+app+'&rp='+rp, 'applicationforms');
				e.preventDefault();
			},
			'.pagination input keydown': function(e){
				if(e.keyCode != 13){
					return;	
				}
				var srcTarget = $(e.target);
				//find the active tab
				var app = $('#app-filter').val();
				//get all ze info for it
				var	rp = srcTarget.parents('.pagination').find('.rp').val();
				var offset = srcTarget.val()-1;
				this.paginate(srcTarget.data('page')+'&app='+app+'&rp='+rp+'&offset='+offset,'applicationforms');
				e.preventDefault();				
			},
			'.pagination select change': function(e){
				var srcTarget = $(e.target);
				//find the active tab
				var app = $('#app-filter').val();
				//get all ze info for it
				var	rp = srcTarget.val();
				this.paginate(srcTarget.data('page')+'&app='+app+'&rp='+rp,'applicationforms');
				e.preventDefault();				
			},
			'.type select change': function(e){
				$('#time-filter').trigger('reset',$(e.target).val());
				this.update();
			},
			'.app select change': function(e){
				$('#time-filter').trigger('reset',$(e.target).val());
				this.update();
			},
			'.category click':function(e){
				if(flobn.get('UIMode') == 4){
					return false;	
				}
				this.selectedNode = $(e.target);
				if($('#cat-select').length){
					// var overlay = $('#cat-select').toggle();
				}else{
					// var overlay = $('body').append('<div id="cat-select">gigig</div>').find('#cat-select');
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
								// "json_data":{"ajax":{"url" : "index_ajax.php?pag=xapptype"}
								// },
								// "plugins" : [ "themes","json_data","types","ui" ]
							}).bind('select_node.jstree',$.proxy(this.selectCategory,this));
				}
				 // overlay.position({of: this.selectedNode,
						  		  // at: 'left bottom',
								  // my: 'left top',
								  // offset:'0px 2px'});
				$('.jstree-clicked','#cat-select').removeClass('jstree-clicked');
				$(this.selectedNode.attr('rev')+' > a','#cat-select').addClass('jstree-clicked');				
				e.preventDefault();
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
		showNotification: function(e,type){
			var msg = '', title = '', func = function(){
				var department = $(e.target).parents('tr').data('department');
				window.location.href = ['index.php?pag=applicationforms&f',department].join('=');
			};
			msg = 'Category can <strong>only</strong> be set on department level.<br /> Would you like to be taken to this members department?';
			title = 'Category';
			var conf = new flobn.widgets.InfoConfirm(title,msg,func);
			msg = title = conf = null;
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
									 cat: checked.attr('rev'),
									 act: 'application-application_type'},$.proxy(function(o){
				if(o.failure){
					alert(o.error);
					return;
				}
				this.update();
			},this));			
			$('.jstree-clicked','#cat-select').removeClass('jstree-clicked');
			tree.hide();
			return true;	
		},
		done: function(o){
			$('#export-csv').attr('href','index.php?pag=applicationforms&act=reports-applicationforms&app='+o.app);
			$('#export-pdf').attr('href','index.php?pag=applicationforms&render=pdf&app='+o.app);
			$('#append').html(o.append);
			flobn.register('thouShallNotMove',o.thouShallNotMove,true);	
		}
	});		  
	$(function(){
		flobn.register('applicationforms',new flobn.ui.applicationforms('#applicationforms'));			   
	});
})();