(function($){
	flobn.ui.help = flobn.widgets.Window.extend({
		loading: false,
		assigned: false,
		init:function(selector){		
			this.node = $(selector);
		},
		listeners: {
			'a.negative click':function(){
				this.closeWindow();
			}			
		},		
		closeWindow: function(){
			this.node.hide();
			$('#overlay').css('display','none');
			$('#overlay-waiter').show();
			this.loading = false;
		},
		show:function(target){
			if(this.loading){
				return false;
			}
			flobn.util.showOverlay();
			$.post(target,$.proxy(this.loadedText,this));
			this.loading = true;
			return false;
		},
		loadedText: function(o){
			this.node.show();
			this.node.html(o.innerHTML).css({
				'positiion':'absolute',
				'zIndex' : 4000				
			});
			if(!this.assigned){
				this.windowEvents();
				this.assignEvents();
				this.assigned = true;
			}
			$('#overlay-waiter').hide();
		}
	});	
	$(function(){	
	   flobn.register('help',new flobn.ui.help('#hlp-container'));
		$('.thumbs a').live('click',function(e){
			flobn.get('help').show($(e.target).parents('a').attr('href'));
			return false;							   
		});
	});	
})(jQuery);