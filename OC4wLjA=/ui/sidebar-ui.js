//create a scop for myself
(function(){
	flobn.ui.sidebar = function(){
		this.init();
	};
	
	flobn.ui.sidebar.prototype = {
		init: function(){
			$('#sidebar .flobn-msg').css('opacity','0.7').hover(function(){
				$(this).css('opacity','1');
			},function(){
				$(this).css('opacity','0.7');
			}).click(function(){$(this).hide()});
			$('.flobn-msg','#sidebar').each(function(i){
				if(i == 0){
					return true;	
				}
				var prev = $(this).prev();
				var pos = $(this).outerHeight() + 20;
					pos = prev.offset().top - pos;
				$(this).css({'top':pos,'bottom':'auto'});
			});
			
			$('.tabs').tabs({
 				  select: function(e, ui){ 
					var label = ui.tab.getAttribute('rel');
				  	$('#filter').catcomplete({source:'index_ajax.php?pag=xfilter&type='+ui.index}).val(flobn.lang.get('Search '+label));
					
				  }
			});
			var session_selected = [],
				user_selected = [],
				computer_selected = [];
				//scroll it into view
			var searchType = 0;	
			switch(flobn.get('initially_select_type')){
				case 'users':
					$('.tabs').tabs( "select" ,1);
					user_selected.push(flobn.get('initially_select_users'));
					searchType = 1;
					break;
				case 'computers':
					$('.tabs').tabs( "select" ,2);
					computer_selected.push(flobn.get('initially_select_computers'));
					searchType = 2;
					break;					
				case 'session':
				default:
					$('.tabs').tabs( "select" ,0);
					session_selected.push(flobn.get('initially_select_session'));
				break;						
			}				
			$('#filter').catcomplete({
				source: "index_ajax.php?pag=xfilter&type="+searchType,
				minLength: 2,
				select: function(e, ui){					
					/*try to figure out what the dude selected*/
					var type = ui.item.data.substr(0,1);
					switch(type){
						case 's':
							$('.tabs').tabs( "select" ,0);
							$('#sessions').jstree('deselect_all').jstree('select_node','#'+ui.item.data).scrollTo($('#'+ui.item.data,'#sessions'));
							break;
						case 'c':
							$('.tabs').tabs( "select" ,2);
							$('#computers').jstree('deselect_all').jstree('select_node','#'+ui.item.data).scrollTo($('#'+ui.item.data,'#computers'));
							break;
						case 'u':
							$('.tabs').tabs( "select" ,1);
							$('#users').jstree('deselect_all').jstree('select_node','#'+ui.item.data).scrollTo($('#'+ui.item.data,'#users'));
							break;
					}					
					return false;
				}				
			}).live('focus',function(){
				this.value = '';	
			});
			
			
			$('#sessions').jstree({"themes" : {
					"theme" : "default",
					"dots" : true,
					"icons" : true
				},
				"ui":{
					"initially_select" : session_selected
				},
				"types" : {
					"valid_children" : [ "root" ],
					"types" : {
						"root" : {
							"icon" : { 
								"image" : flobn.get('CURRENT_VERSION_FOLDER')+"js/themes/default/root.png" 
							},
							"valid_children" : [ "default","group" ],
							"hover_node" : true,
							"select_node": $.proxy(this.filterSession,this)
						},
						"group" : {
							"icon" : { 
								"image" : flobn.get('CURRENT_VERSION_FOLDER')+"js/themes/default/group.png" 
							},
							"valid_children" : [ "default"],
							"hover_node" : true,
							"select_node": $.proxy(this.filterSession,this)
						},
						"default" : {
							"icon" : { 
								"image" : flobn.get('CURRENT_VERSION_FOLDER')+"js/themes/default/computer-user.png" 
							},
							"valid_children" : "none",
							"hover_node": true,
							"select_node": $.proxy(this.filterSession,this)
						}
					}
				},
				"json_data" : {
					"ajax" : {
						"url" : "index_ajax.php?pag=xsession"
					}					
				},
				"plugins" : [ "themes","json_data","types","ui" ]
			}).bind('load_node.jstree',function(){
				var target = flobn.get('initially_select_session');
				if(!target){
					return;
				}				
				$('#sessions').scrollTo('#'+target);
			});
			$('#users').jstree({"themes" : {"theme" : "default","dots" : true,"icons" : true},
				"ui":{"initially_select" : user_selected},
				"types" : {	"valid_children" : [ "root" ],
					"types" : {	"root" : {	"icon" : { "image" : flobn.get('CURRENT_VERSION_FOLDER')+"js/themes/default/root.png" },
							"valid_children" : [ "default","group" ],"hover_node" : true,
							"select_node": $.proxy(this.filterUsers,this)},
						"group" : {	"icon" : { 	"image" : flobn.get('CURRENT_VERSION_FOLDER')+"js/themes/default/group.png" },
							"valid_children" : [ "default","user-computer"],"hover_node" : true,
							"select_node": $.proxy(this.filterUsers,this)
						},
						"user-computer": {
							"icon" : {"image" : flobn.get('CURRENT_VERSION_FOLDER')+"js/themes/default/default.png" },
							"valid_children" : [ "computer"],
							"hover_node" : true,
							"select_node": $.proxy(this.filterUsers,this)
						},				
						"computer":{"icon":{"image":flobn.get('CURRENT_VERSION_FOLDER')+"js/themes/default/computer-default.png" },
							"valid_children" : "none",
							"hover_node": true,
							"select_node": $.proxy(this.filterUsers,this)					
						},						
						"default" : {"icon" : { "image" : flobn.get('CURRENT_VERSION_FOLDER')+"js/themes/default/default.png" },
							"valid_children" : "none","hover_node": true,
							"select_node": $.proxy(this.filterUsers,this)
						}
					}
				},
				"json_data" : {
					"ajax" : {
						"url" : "index_ajax.php?pag=xusers"
					}
				},
				"plugins" : [ "themes","json_data","types","ui" ]
			}).bind('load_node.jstree',function(){
				var target = flobn.get('initially_select_users');
				if(!target){
					return;	
				}
				$('#users').scrollTo('#'+target);
			});
			$('#computers').jstree({"themes" : {
					"theme" : "default",
					"dots" : true,
					"icons" : true
				},
				"ui":{
					"initially_select" : computer_selected
				},
				"types" : {
					"valid_children" : [ "root" ],
					"types" : {
						"root" : {
							"icon" : { 
								"image" : flobn.get('CURRENT_VERSION_FOLDER')+"js/themes/default/root.png" 
							},
							"valid_children" : [ "default","group" ],
							"hover_node" : true,
							"select_node": $.proxy(this.filterComputers,this)
						},
						"group" : {
							"icon" : { 
								"image" : flobn.get('CURRENT_VERSION_FOLDER')+"js/themes/default/computer-group.png" 
							},
							"valid_children" : [ "default","computer-user","user"],
							"hover_node" : true,
							"select_node": $.proxy(this.filterComputers,this)
						},
						"computer-user": {
							"icon" : {"image" : flobn.get('CURRENT_VERSION_FOLDER')+"js/themes/default/user-computer.png" },
							"valid_children" : [ "user"],
							"hover_node" : true,
							"select_node": $.proxy(this.filterComputers,this)
						},						
						"user" : {"icon" : { "image" : flobn.get('CURRENT_VERSION_FOLDER')+"js/themes/default/default.png" },
							"valid_children" : "none","hover_node": true,
							"select_node": $.proxy(this.filterComputers,this)
						},
						"default" : {
							"icon" : { 
								"image" : flobn.get('CURRENT_VERSION_FOLDER')+"js/themes/default/computer-default.png" 
							},
							"valid_children" : "none",
							"hover_node": true,
							"select_node": $.proxy(this.filterComputers,this)					
						}
					}
				},
				"json_data" : {
					"ajax" : {
						"url" : "index_ajax.php?pag=xcomputers"
					}
				},
				"plugins" : [ "themes","json_data","types","ui" ]
			}).bind('load_node.jstree',function(){
				var target = flobn.get('initially_select_computers');
				if(!target){
					return;
				}
				$('#computers').scrollTo('#'+target);
			});
			this.init_events();
		},
		init_events: function(e){			
		},
		listeners:{'user':[],'computer':[],'session':[]},
		firstRun: true,
		filterSession: function(node){
			$('#users,#computers').jstree('deselect_all');
			if(this.firstRun){
				this.firstRun = false;
				return true;	
			}
			return this.filter('session',node);
		},
		filterUsers: function(node){
			$('#sessions,#computers').jstree('deselect_all');
			if(this.firstRun){
				this.firstRun = false;
				return true;	
			}
			return this.filter('user',node);
		},
		filterComputers: function(node){
			if(this.firstRun){
				this.firstRun = false;
				return true;	
			}
			$('#sessions,#users').jstree('deselect_all');
			return this.filter('computer',node);
		},		
		filter:function(what,node){	
			var node = $(node).get(0);
			if(node.tagName.toUpperCase() != 'LI'){
				node = $(node).parents('li');
			}else{
				node = $(node);	
			}			
			var id = node.attr('rev');
			//don't call on initial load			
			var listeners = this.listeners[what];
			var ret = true;
			for(var i = 0, len = listeners.length; i < len; i++){
				ret = listeners[i].call(this,what,id);
				if(!ret){
					return false;	
				}
			}
			return true;
		},
		register: function(evt,func){
			func = func || $.noop;
			this.listeners[evt].push(func);
			return this.listeners[evt].length;
		},
		unregister: function(evt,ident){
			delete this.listeners[evt][ident];
		}
	};		  
	$(function(){
		flobn.register('sidebar',new flobn.ui.sidebar());  
	});
})();

$.widget( "custom.catcomplete", $.ui.autocomplete, {
	_renderMenu: function( ul, items ) {		
		var self = this, currentCategory = "";		
		$.each( items, function( index, item ) {
			if ( item.category != currentCategory ) {
				ul.append( "<li class='ui-autocomplete-category'>" + item.category + "</li>" );
				currentCategory = item.category;
			}
			self._renderItem( ul, item );
		});
	},
	_resizeMenu: function() {
		var a = this.menu.element;
		a.width(this.element.outerWidth()+12);
		//a.css('left',this.element.parent().attr('offsetLeft')+'px');
		a.css('left',this.element.offsetLeft+'px');
		a.css('top',this.element.offsetTop+'px');
		a.css('overflow-y','scroll');
		a.css('overflow-x','hidden');
		a.css('max-height', '170px');
	}
});