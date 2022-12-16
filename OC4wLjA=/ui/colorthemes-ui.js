(function($){
	flobn.ui.colorthemes = flobn.widgets.Page.extend({
		listeners:{
			'#setdefault click':function(e){
				var $srcTarget = $(e.target);
				
				var conf = new flobn.widgets.Confirm('Reset default color theme?','Are you sure you want to set the default color theme?',function(e){window.location.href = $srcTarget.attr('href');});
				conf = null;					
				e.preventDefault();
				return false;
			}
		},
		init: function(id){
			this._super(id);
			$('.colorSelector2').ColorPicker({
				onSubmit: function(hsb, hex, rgb, el) {
					$(el).val(hex);
					$(el).ColorPickerHide();
				},
				onBeforeShow: function (div) {
					var el = $(div).data('colorpicker').el;
					var hex = $(el).prev().find('input').val();
					$(this).ColorPickerSetColor(hex);
				},
				onShow: function (colpkr) {
					$(colpkr).fadeIn(500);
					return false;
				},
				onHide: function (colpkr) {
					$(colpkr).fadeOut(500);
					return false;
				},
				onChange: function (hsb, hex, rgb) {
					var el = this.data('colorpicker').el;
					$(el).prev().find('input').val(hex);
					$(el).find('> div').css('backgroundColor', '#' + hex);
				}
			});
		}
	});	
	flobn.register('colorthemes',new flobn.ui.colorthemes('#colorthemes'));	
})(jQuery);