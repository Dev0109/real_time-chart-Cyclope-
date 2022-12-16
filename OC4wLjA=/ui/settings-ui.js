(function(){
	flobn.ui.settings = flobn.widgets.Page.extend({
		init: function(id){
			this._super(id);
			this.tie();
			$('.pagetabs').tabs({show: this.tabEvents});
			$('#client_uninstall_password').pstrength();
			var other = new flobn.ui.othersettings('#othersettings');
		},		
		addFilter: function(params){
			params.pag = 'xworkschedule';
			return params;
		},
		done : function(o){
			//$('.pagetabs').tabs();	
			flobn.register('thouShallNotMove',o.thouShallNotMove,true);
				
		},
		listeners : {
			'#testemail click': function(e){
				
				e.stopPropagation();
				e.preventDefault();
				
				$('#emailupdateact').attr('value','settings-emailtest');
				$('#emailupdate').submit();
			},
			'#modifiable click':function(e){
			},
			'#modifiable mousedown':function(e){
				if(flobn.get('thouShallNotMove')){
					setTimeout(function() {$(e.target).blur();}, 0);
					setTimeout(function() {$(e.target).focus();}, 0);
					$(e.target).focus();
					e.preventDefault();				
				}
			}
		},
		tabEvents: function(e, ui){
			var $li = $(ui.tab).parent();
			if($li.hasClass('othersettings')){
				//window.location = "index.php?pag=casualty";
			}
		},
		showNotification: function(e){
			var msg = '', title = '', func = function(){
				var department = [$(e.target).parents('tr').attr('data-department'),'#workschedule'].join('');
				window.location.href = ['index.php?pag=settings&f',department].join('=s');
			};
			msg = 'Workschedule can <strong>only</strong> be set on department level.<br /> Would you like to be taken to this members department?';
			title = 'Workschedule';
			var conf = new flobn.widgets.InfoConfirm(title,msg,func);
			msg = title = conf = null;
		}
	});
	
	$(function(){
		flobn.register('settings',new flobn.ui.settings('#settings'));
	});
	
	
	flobn.ui.othersettings = flobn.widgets.Page.extend({
		init: function(id){
			this.firstRun = true;
			this._super(id);
			this.initSecond();
		},
		initSecond: function(){
			$('#filter2').catcomplete({
				source: "index_ajax.php?pag=xfilter&type=0",
				minLength: 2,
				select: function(e, ui){					
					/*try to figure out what the dude selected*/
				$('.tabs').tabs( "select" ,0);
				$('#sessions2').jstree('deselect_all').jstree('select_node','#'+ui.item.data).scrollTo($('#'+ui.item.data,'#sessions'));
				}
			}).live('focus',function(){
				this.value = '';	
			});
			$('#sessions2').jstree({"themes" : {
					"theme" : "default",
					"dots" : true,
					"icons" : true
				},
				"ui":{
					"initially_select" : ['s1']
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
								"image" : flobn.get('CURRENT_VERSION_FOLDER')+"js/themes/default/default.png" 
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
				$('#sessions2').scrollTo('#'+target);
			});	
		},
		filterSession: function(node){
			if(this.firstRun){
				this.firstRun = false;
				return true;
			}
			var node = $(node).get(0);
			if(node.tagName.toUpperCase() != 'LI'){
				node = $(node).parents('li');
			}else{
				node = $(node);	
			}			
			var id = node.attr('rev');	
			this.update.call(this,'session',id);
			return true;
		},
		addFilter: function(params){
			params.pag = 'xcasualty';
			return params;
		}
});
	
	
	
})();