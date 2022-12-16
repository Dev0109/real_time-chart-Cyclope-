(function($){
	flobn.ui.update = flobn.widgets.Page.extend({
		start: 2,
		end: 4,
		actions: ['update-download','update-unzip','update-altertables'],
		params: {},
		proggress: 0,
		interval : 0,
		init: function(id){
			this._super(id);
			$( "#progressbar" ).progressbar({
				value: this.proggress
			});
			$(window).load($.proxy(this.proccess,this));			
		},
		listeners: {
			'button click':function(){
				window.location.href='index.php?pag=updates';
			},
			'.expand click': function(e){
			var $next = $(e.target).next();
				if($next.innerHeight() == 100){
					$next.height('auto');
					$(e.target).html(flobn.lang.get('less'));
				}else{
					$next.height(100);
					$(e.target).html(flobn.lang.get('more'));
				}
				
				e.preventDefault();
				e.stopPropagation();
			}
		},
		'proccess': function(){
			this.interval = window.setInterval($.proxy(function(){				
				if(this.proggress < ((this.start-1) * 33)){
					this.proggress+=2;
					$( "#progressbar" ).progressbar('value',this.proggress);
				}
			},this),650);
			$('.checklist li:nth-child('+this.start+')').removeClass('hide');			
			this.params.act = this.actions[this.start - 2];
			$.ajax({url: 'index_ajax.php',
				   	dataType: 'json',
					type: 'POST',
				   	data: this.params,
					success: $.proxy(this.success,this),
					error: $.proxy(this.fail,this)
			});
		},
		success: function(o){
			if(o.failure){
				return this.fail(o);	
			}
			//not done yet so set this one as compleated
			$('.checklist li:nth-child('+this.start+')').removeClass('working').addClass('complete');
			window.clearInterval(this.interval);
			this.proggress = (this.start -1) * 33;
			$( "#progressbar" ).progressbar('value', this.proggress);			
			this.start++;
			this.params = o;
			//are we done yet?
			if(this.start > this.end){				
				return this.finish();
			}			
			//run the next one when we can
			window.setTimeout($.proxy(function(){
				this.proccess();
			},this),0);
		},		
		fail: function(o){
			window.clearInterval(this.interval);
			this.proggress = 100;			
			$('.checklist li:nth-child('+this.start+')').removeClass('working').addClass('fail').append('<div class="hide">'+o.error+'</div>').click(function(){$(this).find('div').toggleClass('hide');});
			$.ajax({url: 'index_ajax.php',
				   	dataType: 'json',
					type: 'POST',
				   	data: {'act':'update-error'}
			});
			$("#progressbar").progressbar('value',100);
			$('#cancel-action').removeClass('hide');
			return false;		
		},
		finish: function(){
			$( "#progressbar" ).progressbar('value',100);
			$('#finalize-action').removeClass('hide');
		}
	});
	
	$(function(){
		flobn.register('update',new flobn.ui.update('#update-content'));
	});
})(jQuery);