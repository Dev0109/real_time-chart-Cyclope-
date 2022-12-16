(function(){
	flobn.ui.topapplications = flobn.widgets.Page.extend({
		init: function(id){
			this._super(id);
			//$('.pagetabs').tabs({show: this.tabEvents});
			this.tie();
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
		done : function(o){
			flobn.register('thouShallNotMove',o.thouShallNotMove,true);
			$('#append').html(o.append);
		},
		showNotification: function(e,type){
			var msg = '', title = '', func = function(){
				var department = $(e.target).parents('tr').data('department');
				window.location.href = ['index.php?pag=topapplications&f',department].join('=');
			};
			msg = 'Category can <strong>only</strong> be set on department level.<br /> Would you like to be taken to this members department?';
			title = 'Category';
			var conf = new flobn.widgets.InfoConfirm(title,msg,func);
			msg = title = conf = null;
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
		flobn.register('topapplications',new flobn.ui.topapplications('#topapplications'));
	});
})();