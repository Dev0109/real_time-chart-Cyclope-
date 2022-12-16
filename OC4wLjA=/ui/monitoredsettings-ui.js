(function($){
	flobn.ui.monitoredsettings = flobn.widgets.Window.extend({
		init:function(selector){
			this.selector = selector;
		},
		show: function(){
			var $super = this._super;
			$.get('index_ajax.php',{'pag':'xsettings','computer': flobn.get('computer')},$.proxy(function(o){
				$('#overlay').append(o.innerHTML);
				this.node = $(this.selector);
				this.windowEvents();
				this.assignEvents();
				$('#overlay').show();
				$('form',this.node).submit($.proxy(this.save,this));
				this.node.show();
			},this),'json');
		},		
		closeWindow: function(){
			this.node.remove();
			$('#overlay').css('display','none');
		},
		save: function(e){
			var fields = $(e.target).serializeArray();
			var param = {};
			$.each(fields,function(i,field){
				param[field.name] = field.value;
			});
			$.post('index_ajax.php',param,$.proxy(function(o){
				$('.error',this.node).remove();
				$('.success','#content').remove();
				if(o.failure){
					$('<div class="error"></div>').append(o.error).prependTo('.bd');
				}else{
					this.closeWindow();
					$('<div class="success"></div>').append(o.error).prependTo('#content');
				}
			},this));			
			e.preventDefault();
		}
	});
	flobn.register('monitoredsettings',new flobn.ui.monitoredsettings('#monitoredsettings'));
})(jQuery)