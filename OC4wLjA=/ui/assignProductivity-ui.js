(function($){
	flobn.ui.assignProductivity = flobn.widgets.Window.extend({
		init:function(selector){
			var me = this;
			$.get('index_ajax.php',{'pag':'xprod'},$.proxy(function(o){
				$('#overlay').append(o.innerHTML);
				this.node = $(selector);
				this.windowEvents();
				this.initTree();
			},this),'json');			
		},
		listeners: {
			'a.negative click':function(){
				this.closeWindow();
			},
			'button.positive click':function(e){
				//save me :D
				var prod = flobn.get('prodSlide');
				var param = {'department':[],
							 'act': 'application-productive',
							 'val': prod.val,
							 'id': prod.id,
							 'type': prod.type
				};
				//get all checked
				$('.jstree-checked','#prod-tree').each(function(){
					param.department.push($(this).attr('rev'));
				});
				$.post('index_ajax.php',param,$.proxy(function(o){
						if(o.failure){
							alert(o.error);	
						}else{
							this.closeWindow();							
						}
					},this));
				e.preventDefault();
				e.stopPropagation();
			}
		},		
		closeWindow: function(){
			this.node.hide();
			$('#overlay').css('display','none');
			$('#prod-tree').jstree('uncheck_all');
		},
		initTree: function(){
			$('#prod-tree').jstree({"themes" : {
					"theme" : "default",
					"dots" : true,
					"icons" : true
				},
				"types" : {
					"valid_children" : [ "root" ],
					"types" : {
						"root" : {
							"icon" : { 
								"image" : "js/themes/default/root.png" 
							},
							"valid_children" : [ "default","group" ],
						},
						"group" : {
							"icon" : { 
								"image" : "js/themes/default/group.png" 
							},
							"valid_children" : [ "default"]
						},
						"default" : {
							"icon" : { 
								"image" : "js/themes/default/default.png" 
							},
							"valid_children" : "none"
						}
					}
				},
				"json_data" : {
					"ajax" : {
						"url" : "index_ajax.php?pag=xgroups&nomember=1"
					}
				},
				"plugins" : [ "themes","json_data","types","ui","checkbox2" ]
			})
		}
	});
	flobn.register('assignProductivity',new flobn.ui.assignProductivity('#prod-window'))
})(jQuery);