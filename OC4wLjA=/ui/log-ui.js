(function($){
	flobn.ui.log = flobn.widgets.Page.extend({
		listeners:{
			'.quarter click': function(e){
				var $srcTarget = $(e.target);
				
				var conf = new flobn.widgets.Confirm('Clear log?','Are you sure you want to clear the logs older than 3 months?',function(e){window.location.href = $srcTarget.attr('href');});
				conf = null;					
				e.preventDefault();
				return false;
			},
			'.half click': function(e){
				var $srcTarget = $(e.target);
				
				var conf = new flobn.widgets.Confirm('Clear log?','Are you sure you want to clear the logs older than 6 months?',function(e){window.location.href = $srcTarget.attr('href');});
				conf = null;					
				e.preventDefault();
				return false;

			},
			'.clear1y click': function(e){
				var $srcTarget = $(e.target);
				
				var conf = new flobn.widgets.Confirm('Clear log?','Are you sure you want to clear the logs older than 12 months?',function(e){window.location.href = $srcTarget.attr('href');});
				conf = null;					
				e.preventDefault();
				return false;

			},
			'.clearall click': function(e){
				var $srcTarget = $(e.target);
					
				var conf = new flobn.widgets.Confirm('Clear log?','Are you sure you want to clear all logs?',function(e){window.location.href = $srcTarget.attr('href');});
				conf = null;					
				e.preventDefault();
				return false;
			}
		}
	});	
	flobn.register('log',new flobn.ui.log('#log'));	
})(jQuery);