(function($){
	flobn.ui.search = flobn.widgets.Page.extend({
		init: function(id){
			this._super(id);
		},
	    delayTimer: true,	
		runningRequest: false,
		request: null,
		listeners:{	
		'#search_key keydown': function(e){
			if(e.which == 13) {
				
				this.request = $.post('index_ajax.php',{'pag':'xuniversalsearch','k':$('#search_key').val()});
				}
		}
		/*$('#main_search').keydown(function(e) {
				if(e.which == 13) {
				this.request = $.post('index_ajax.php',{'pag':'xuniversalsearch','k':$('#main_search').val()});
				}
		'#main_search keyup': function(e){
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
		'#main_search focusin':function(e){
				$(e.target).get(0).select()
		},
		'#main-search click':function(e){
				e.preventDefault();
				$('#main_search').val('');
				this.getResults();
				$(e.target).hide();
				}*/
		},
		getResults: function(){
			this.request = $.post('index_ajax.php',{'pag':'xuniversalsearch','k':$('#main_search').val()},$.proxy(this.showResults,this));			
			this.delayTimer = true;
			this.runningRequest = true;			
		},
		showResults: function(o){ 
			$('#results').replaceWith(o.innerHTML);		
			this.runningRequest = false;
		}
		});	
	flobn.register('search',new flobn.ui.search('#search'));	
})(jQuery);


