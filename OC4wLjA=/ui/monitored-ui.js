(function($){
	flobn.ui.monitored = flobn.widgets.Page.extend({
		init: function(id){
			this._super(id);
			$('.pagetabs').tabs();
		},
	    delayTimer: true,	
		runningRequest: false,
		request: null,
		listeners:{
			'.settings click':function(e){
				this.showSettings(e);
				e.preventDefault();
			},
			'.emptylog click': function(e){
			var conf = new flobn.widgets.Confirm('Clear log','Are you sure you want to clear the log for this user?',function(e){
					$.getJSON($srcTarget.attr('href'),function(o){
						if(!o.failure){
							window.location.reload();
						}													 
					});
				});
				conf = null;		
				e.preventDefault();
			},
			'.delete click': function(e){
				var $srcTarget = $(e.target);
				this.remove($srcTarget);
				e.preventDefault();
			},
			'#search_key keyup': function(e){
				e.preventDefault();
				var $q = $(e.target);
				if($q.val() == ''){
					return false;
				}
				if(this.delayTimer){
 				  window.clearTimeout(this.delayTimer);
				  this.delayTimer = window.setTimeout( $.proxy(this.getResults,this), 200 );
				  $('#results').animate({'opacity':0.5},'slow');
				  if(this.runningRequest){
					  this.request.abort();
				  }
				}
				$('#clear-search').show();
			},
			'#search_key focusin':function(e){
				$(e.target).get(0).select()
			},
			'#clear-search click':function(e){
				e.preventDefault();
				$('#search_key').val('');
				this.getResults();
				$(e.target).hide();
			},
			'.clear-deleted click': function(e){
				var $srcTarget = $(e.target);
				var conf = new flobn.widgets.Confirm('Restore User','Allow this user to send new data to the server?',function(e){				
					$.getJSON($srcTarget.attr('href'),function(o){
						if(!o.failure){
							$srcTarget.parents('tr').remove();
							return;
						}
						$srcTarget.parents('.list').before('<div class="error">'+o.error+'</div>');
					});
				});	
				e.preventDefault();
				return false;
			},
			//	Lorand: Eye of rabbit, harp string hum, turn this water into rum
			//	converts normal sorter link into ajax one
			'.sorter click': function(e){
				$('#sortcolumn').val($(e.target).attr('column'));	//	e.target is the "this" selector
				$('#sortorder').val($(e.target).attr('order'));	//	e.target is the "this" selector
				// this.update();
				this.request = $.post('index_ajax.php',{'pag':'xmonitored','sortcolumn':$('#sortcolumn').val(),'sortorder':$('#sortorder').val()},$.proxy(this.showResults,this));
				this.delayTimer = true;
				this.runningRequest = true;
				return false;
			}	//	end
		},
		getResults: function(){
			this.request = $.post('index_ajax.php',{'pag':'xmonitored','q':$('#search_key').val()},$.proxy(this.showResults,this));	
			this.delayTimer = true;
			this.runningRequest = true;			
		},
		showResults: function(o){ 
			$('#results').replaceWith(o.innerHTML);		
			this.runningRequest = false;
		},
		remove:function(el){
			var me = this;
			me.el = el;
			var conf = new flobn.widgets.Confirm('Remove User',el.attr('data-message'),function(e){				
				var conf2 = new flobn.widgets.Confirm('Remove Client','Would you like to also remove the client application?',$.proxy(me.uninstall,me),$.proxy(me.removeUser,me),{'Uninstall':'positive','No':'negative'});
			});
		},
		uninstall:function(e){
			var el = this.el;
			var parents = el.parents('tr');	//	hack
			parents.fadeOut('slow',function(){parents.remove()});	//	hack
			$.getJSON(el.attr('href')+'&uninstall=1',function(o){
				if(!o.failure){
					var parents = el.parents('tr');
					parents.fadeOut('slow',function(){parents.remove()});
				}
			});
			this.el = null;
			delete this.el;
			e.preventDefault();
			e.stopPropagation();
		},
		removeUser: function(e){
			var el = this.el;
			var parents = el.parents('tr');	//	hack
			parents.fadeOut('slow',function(){parents.remove()});	//	hack
			$.getJSON(el.attr('href'),function(o){
				if(!o.failure){
					var parents = el.parents('tr');
					parents.fadeOut('slow',function(){parents.remove()});
				}
			});
			this.el = null;
			delete this.el;
		},
		showSettings: function(e){
			flobn.register('computer',$(e.target).attr('rel'),true);
			if(flobn.get('monitoredsettings')){
				flobn.get('monitoredsettings').show();	
			}else{
				flobn.include(flobn.get('CURRENT_VERSION_FOLDER')+'ui/monitoredsettings-ui.js',function(){
						flobn.get('monitoredsettings').show();						
				});		
			}
		},
		addFilter: function(param){
			//	LORAND
			param.sortcolumn = $('#sortcolumn').val();
			param.sortorder = $('#sortorder').val();
			//	end
			return param;
		}	
	});	
	flobn.register('monitored',new flobn.ui.monitored('#monitored'));	
})(jQuery);