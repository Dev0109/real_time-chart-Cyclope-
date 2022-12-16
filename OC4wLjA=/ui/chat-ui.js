(function($){
	flobn.ui.chat = flobn.widgets.Page.extend({
		options:{
			min: 0,
			max: 2,
			contained : true
		},
		'block':{},
		init: function(id){
			this._super(id);
			this.tie();
			// $('#app-filter').flobn_select().bind('select',$.proxy(function(){this.update()},this));
		},
		listeners: {
			'.pagination a click': function(e){
				//find the active tab
				var app = $('#app-filter').val();
				//get all ze info for it
				var	rp = $(e.target).parents('.pagination').find('.rp').val();
				this.paginate(e.target.href+'&app='+app+'&rp='+rp, 'chat');
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
				this.paginate(srcTarget.data('page')+'&app='+app+'&rp='+rp+'&offset='+offset,'chat');
				e.preventDefault();				
			},
			'.pagination select change': function(e){
				var srcTarget = $(e.target);
				//find the active tab
				var app = $('#app-filter').val();
				//get all ze info for it
				var	rp = srcTarget.val();
				this.paginate(srcTarget.data('page')+'&app='+app+'&rp='+rp,'chat');
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
			'.handle draginit': function(e,drag){
				this.element = $(e.target);
				this.getDimensions();
				//save current value for later
				var left = this.element.offset().left - this.leftStart;
				var spot = Math.round(left / this.widthOfSpot);
				this.element.data('val',spot + this.options.min);
				var parent = this.element.parents('.slider');				
				if(this.block[parent.data('app')]){
					drag.cancel();
					return false;	
				}
				drag.limit(parent, this.options.contained ? undefined : "width").step(this.widthOfSpot, parent);
			},
			".handle dragend" : function(e, drag){
				var left = this.element.offset().left - this.leftStart;
				var spot = Math.round(left / this.widthOfSpot);
				this.element.trigger("change", [spot + this.options.min,this.element.data('val')]);
			},
			'.handle change': function(e, val,oldVal){
				var types = ['distracting','neutral','productive'];
				var tags = [flobn.lang.get('Distracting'),flobn.lang.get('Neutral'),flobn.lang.get('Productive')];
				var $this = $(e.target).parents('.slider');
				this.saveChildSlider($this,val,oldVal);	
				$this.removeClass(types.join(' ')).addClass(types[val]).next().html(tags[val]);
			},
			'.type select change': function(e){
				$('#time-filter').trigger('reset',$(e.target).val());
				this.update();
			},
			'.app select change': function(e){
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
									 type: 1, //chat
									 cat: checked.attr('rev'),
									 act: 'application-category'},$.proxy(function(o){
				if(o.failure){
					alert(o.error);	
					return;
				}
				this.refreshChart('category');
			},this));			
			$('.jstree-clicked','#cat-select').removeClass('jstree-clicked');
			tree.hide();
			return true;	
		},
		addFilter: function(param){
			param.app = $('#app-filter').val();
			$('.slider').slider('destroy');
			
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
		getDimensions: function() {
			var spots = this.options.max - this.options.min,
				parent = this.element.parent(),
				outerWidth = this.element.outerWidth();
			this.widthToMove = parent.width();
			if(this.options.contained){
				this.widthToMove = this.widthToMove - outerWidth
			}
			this.widthOfSpot = this.widthToMove / spots;
			var styles = parent.curStyles("borderLeftWidth", "paddingLeft"),
				leftSpace = parseInt(styles.borderLeftWidth) + parseInt(styles.paddingLeft) || 0
				this.leftStart = parent.offset().left + leftSpace -
				(this.options.contained ? 0 : Math.round(outerWidth / 2));
		},
		saveChildSlider: function(el, newVal, oldVal){
			//save me and get back the parent Slider
			this.block[el.data('parentapp')] = true;
			var param = {'act': 'application-productive',
						 'val': newVal,
						 'oldVal':oldVal,
						 'parent': el.data('parentapp'),
						 'id': el.attr('rel'),
						 'type': el.data('childtype')					 
			};
			
			//send the request to save us and get back the parent slider
			$.post('index_ajax.php',param,$.proxy(function(o){
				delete this.block[el.data('parentapp')];
				this.refreshChart();						
			},this));
		},
		done: function(o){
			flobn.register('thouShallNotMove',o.thouShallNotMove,true);
			$('#export-csv').attr('href','index.php?pag=chat&act=reports-chat&app='+o.app);
			$('#export-pdf').attr('href','index.php?pag=chat&render=pdf&app='+o.app);
			$('#append').html(o.append);
		},
		refreshChart: function(type){
			type = type || 'productivity';
			switch(type){
				case 'productivity'	:				
					$("#productivity_chart .chart").load("index_ajax.php?pag=chart_chatproductivity");
					break;
				case 'category':
					// $("#categories_chart .chart").load("index_ajax.php?pag=chart_chatmonitoring");
					break;				
			}
		},
		showNotification: function(e,type){
			type = type || 1;
			var msg = '', title = '', func = function(){
				var department = $(e.target).parents('tr').data('department');
				window.location.href = ['index.php?pag=chat&f',department].join('=');
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
		flobn.register('chat',new flobn.ui.chat('#chat'));
	});		  
})(jQuery);