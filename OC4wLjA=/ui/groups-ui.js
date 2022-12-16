//create a scop for myself
(function(){
	flobn.ui.groups = function(){
		this.init();
	};
	
	flobn.ui.groups.prototype = {
		active: false,
		init: function(){
			//setup the tree
			$("#usertree").jstree({ 
				"themes" : {
					"theme" : "default",
					"dots" : true,
					"icons" : true
				},
				"types" : {
					"valid_children" : [ "root" ],
					"types" : {
						"root" : {
							"icon" : { 
								"image" : flobn.get('CURRENT_VERSION_FOLDER')+"js/themes/default/root.png" 
							},
							"valid_children" : [ "default","group" ],
							"hover_node" : true
						},
						"group" : {
							"icon" : { 
								"image" : flobn.get('CURRENT_VERSION_FOLDER')+"js/themes/default/group.png" 
							},
							"valid_children" : [ "default","group"],
							"hover_node" : true
						},				
						"default" : {
							"icon" : { 
								"image" : flobn.get('CURRENT_VERSION_FOLDER')+"js/themes/default/default.png" 
							},
							"valid_children" : "none",
							"hover_node": true					
						}
					}
				},
				"json_data" : {
					"ajax" : {
						"url" : "index_ajax.php?pag=xgroups"
					}
				},
				"plugins" : [ "themes","json_data","dnd","ui","types","crrm","contextmenu" ]
			}).bind("remove.jstree",this.removeNode).bind('create.jstree',$.proxy(this.createNode,this)).bind('move_node.jstree',this.moveNode).bind('rename.jstree',this.renameNode).dblclick(this.renameParent);
			this.init_events();
		},
		removeNode: function(e,data){
			var department = data.rslt.obj.attr('rev');
			$.post('index_ajax.php',{'act': 'department-delete',id:department },function(o){
				if(!o.failure){
					$("#usertree").jstree("refresh",-1);
					return;
				}			
				$.jstree.rollback(data.rlbk);
			});	
		},
		createNode: function(e,data){
			this.active = true;
			var me = this;
			var newParent = data.rslt.parent.attr('rev').split('-')[0];
			$.post('index_ajax.php',{'act': 'department-add','name':data.rslt.name,'parent':newParent},function(o){
				me.active = false;
				if(!o.failure){
					data.rslt.obj.attr('rev',o.id);
					return;
				}else{
					alert(o.error);	
				}
				$.jstree.rollback(data.rlbk);
			});
		},
		moveNode: function(e,data){
			if(data.rslt.np.attr('rev') == data.rslt.op.attr('rev')){
				return true;	
			}
			if(data.rslt.o.attr('rel') != ''){
				//moving a tree so let's update it
				var newParent = data.rslt.np.attr('rev').split('-')[0];
				var node = data.rslt.o.attr('rev').split('-')[0];
				$.post('index_ajax.php',{'act': 'department-movenode',
				   					 'parent':newParent,
									 'node':node
									 },function(o){ if(!o.failure){return;}	$.jstree.rollback(data.rlbk);});
				return;	
			}
			flobn.register('nodeData',data,true);
			$('#overlay').show();
			if(flobn.get('assignChoice')){
				flobn.get('assignChoice').show();					
			}else{
				flobn.include(flobn.get('CURRENT_VERSION_FOLDER')+'ui/assignChoice-ui.js',function(){							
					flobn.get('assignChoice').show();						
				});				
			}
		},
		renameNode: function(e,data){
			var id = data.rslt.obj.attr('rev');
			$.post('index_ajax.php',{'act': 'department-update','id':id,'name':data.rslt.new_name},function(o){
				if(!o.failure){
					return;
				}			
				$.jstree.rollback(data.rlbk);
			});
		},
		renameParent: function(e){
			var clicked = $('.jstree-clicked','#usertree');
			if(!clicked.length){
				return;
			}
			clicked = clicked.parents('li');
			if(clicked.attr('rel') == 'root' || clicked.attr('rel') == 'group'){
				$("#usertree").jstree("rename");
			}	
		},
		createGroup: function(e){
			if(this.active){
				return true;	
			}			
			var sel = $('.jstree-clicked','#usertree').closest('li');
			if(!sel.length){
				sel = $('#s1');
			}
			$("#usertree").jstree("create",sel,"first",{'attr':{'rel':'group'}});
		},
		removeGroup: function(e){
			var $t = $('.jstree-clicked').parents('li');
			if($t.attr('rel') == 'group'){
				$("#usertree").jstree("remove"); 
			}
		},
		importGroup: function(e){
			window.location('index.php?pag=importad');
		},
		'contract':function(e){
			$('#usertree').jstree('close_all');
		},
		'expand': function(e){
			$('#usertree').jstree('open_all');
		},
		init_events: function(e){
			var me = this;
			$('#groups').live('click',function(e){
				var rel = e.target.getAttribute('rel');
				if(rel){
					//if we have a rel we know we need to do something so we explode it
					var listener = rel;
					if(me[listener]){
						//call the listener
						me[listener].call(me,e);
					}
					listener = null;
				}
				rel = null;
				flobn.util.stopEvent(e);
			});			
		}
	};		  
$(function(){
	flobn.register('groups',new flobn.ui.groups());  
});
})();