(function($){
	flobn.ui.notifications = flobn.widgets.Page.extend({
		init: function(id){
			this._super(id);
			$('#type-filter').flobn_select().bind('select',$.proxy(function(){this.getResults()},this));
		},
		listeners:{
			'.category click':function(e){
				//console.log(e);
				$(e.target).toggleClass('open');
				$(e.target).parents('tr').next().toggleClass('hide');
				$.getJSON($(e.target).attr('href')); //modificare 4 din 4 
				e.preventDefault();				
			},
			'.delete click':  function(e){
				var $srcTarget = $(e.target);
				this.remove($srcTarget);
				e.preventDefault();
			},
			'.type-filter select change':  function(e){
				$(e.target).hide();
				this.getResults();
				e.preventDefault();
			},
			//	Lorand: Eye of rabbit, harp string hum, turn this water into rum
			//	converts normal sorter link into ajax one
			'.sorter click': function(e){
				$('#sortcolumn').val($(e.target).attr('column'));	//	e.target is the "this" selector
				$('#sortorder').val($(e.target).attr('order'));	//	e.target is the "this" selector
				// this.update();
				this.request = $.post('index_ajax.php',{'pag':'xnotifications','sortcolumn':$('#sortcolumn').val(),'sortorder':$('#sortorder').val()},$.proxy(this.showResults,this));
				this.delayTimer = true;
				this.runningRequest = true;
				return false;
			}	//	end
		},
		remove:function(el){
			var me = this;
			me.el = el;
			var conf = new flobn.widgets.Confirm('Remove Notification','Are you sure you would like to delete this notification?',$.proxy(me.removeNotification,me),$.noop,{'Yes':'positive','No':'negative'});
		},
		removeNotification: function(e){
			var el = this.el;
			$.getJSON(el.attr('href'),function(o){
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
		getResults: function(){
			this.request = $.post('index_ajax.php',{'pag':'xnotifications','app':$('#type-filter').val()},$.proxy(this.showResults,this));				
		},
		showResults: function(o){ 
			$('#notifications').replaceWith(o.innerHTML);		
			this.runningRequest = false;
		}
	});	
	flobn.register('notifications',new flobn.ui.notifications('#notifications'));	
})(jQuery);