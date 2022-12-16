(function(){
	flobn.ui.universalsearch = flobn.widgets.Page.extend({
		init: function(id){
			this._super(id);
			this.tie();
			$("input#main_search_input").val(flobn.get('search_key'));
		},
		listeners: {
			'.td-application a click': function(e){				
			var srcTarget = $(e.target);		
			var srcParent = srcTarget.closest('li');
			var possibleChildren = srcParent.find('ul');
			if(possibleChildren.length){
				srcParent.toggleClass('open');
			}else{
				srcParent.addClass('loading');
				$.getJSON(e.target.href+'&search_key='+flobn.get('search_key'),function(o){
					var possibleChildren = $('<ul></ul>');
					possibleChildren.append(o.innerHTML);
					srcTarget.closest('li').append(possibleChildren).removeClass('loading').addClass('open');
				});
			}				
			e.preventDefault();
			srcParent = possibleChildren = null;
			}
		},
		addFilter: function(param){
			param.search_key = $('#search_key').val();			
			return param;
		},
		done:function(o){
			flobn.register('search_key',o.search_key,true);
			$("#main_search_form").submit();
		}
	});		  
	$(function(){
		flobn.register('universalsearch',new flobn.ui.universalsearch('#universalsearch'));			   
	});
})();


function validateForm()
{
	// Validate URL
	var searchterm = $("#main_search_input").val();
	if (searchterm.length < 3) {
		alert(tooshort);
		return false;
	}
	
	var checkedAtLeastOne = false;
	$('input[type="checkbox"]').each(function() {
		if ($(this).is(":checked")) {
			checkedAtLeastOne = true;
		}
	});
	if (checkedAtLeastOne == false) {
		alert(atleastone);
		return false;
	}
}