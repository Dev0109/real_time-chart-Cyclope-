(function($){
	flobn.ui.trial = flobn.widgets.Page.extend({
		init: function(id){
			this._super(id);
			$('#country').change(function() 
			{
				$('#time_zone').load("index_ajax.php?pag=xtimezones&country=" + encodeURIComponent($(this).attr('value')));
			});
		},
		listeners: {
			'#gettrial click': function(e){
				e.preventDefault();
				$('#act').attr( 'value', 'licence-trial');
				$('#form').submit();
			}
		}
	});
	$(function(){
		flobn.register('trial',new flobn.ui.trial('#trial'));
	})	  
})(jQuery);