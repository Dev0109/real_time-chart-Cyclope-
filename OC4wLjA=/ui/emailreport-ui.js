(function($){
	flobn.ui.emailreport = flobn.widgets.Page.extend({
		init: function(id){
			this._super(id);
			$('.pagetabs').tabs({
				 select: $.proxy(function(e, ui){						
					this.position = ui.index;						  
				},this)
								
			});
			 $(".defaultText").focus(function(srcc)
				    {
				        if ($(this).val() == $(this)[0].title)
				        {
				            $(this).removeClass("defaultTextActive");
				            $(this).val("");
				        }
				    });
				    
		    $(".defaultText").blur(function()
		    {
		        if ($(this).val() == "")
		        {
		            $(this).addClass("defaultTextActive");
		            $(this).val($(this)[0].title);
		        }
		    });
		    $("form").submit(function() {
				$(".defaultText").each(function() {
					if($(this).val() == this.title) {
					$(this).val("");
					}
				});
			});
    
    $(".defaultText").blur();
		},
		position: 0,
		listeners:{
			
			'.item click':function(e){
				if($(e.target).parent('li').hasClass('active'))
				{
					$(e.target).parent('li').removeClass('active');
				}
				else
				{
					$(e.target).parent('li').addClass('active');
				}
			},
			'.back click':function(e){
				if(this.position == 0){
					return false;
				}
				$('.pagetabs').tabs('select',--this.position );
				return false;
			},
			'.next click':function(e){
				if(this.position == 6){
					this.getSelectedDepartments();
					return true;	
				}
				$('.pagetabs').tabs('select',++this.position );
				e.preventDefault();
				return false;
			},
//			'.emailremove click':function(e){
//				
//				var tpl = $('.receiver');
//	
//				if(tpl.length == 1){
//					//return false;
//					tpl.addClass('hide');
//					
//					var inputs = tpl[0].getElementsByTagName('INPUT');
//					for(var i = 0; i < inputs.length; i++){
//						inputs[i].value = '';
//					}
//					return false;
//				}
//				var targ;
//				if (e.target) targ = e.target;
//				else if (e.srcElement) targ = e.srcElement;
//				if (targ.nodeType == 3) // defeat Safari bug
//					targ = targ.parentNode;	
//				tpl = $(targ);
//				tpl.parents('div.template').remove();
//				return false;
//			},
			'.emailremove click':function(e){
				var tpl = $('.receiver');
	
				if(tpl.length == 1){
					return false;
				}
				tpl = $(e.target);
				tpl.parents('div.receiver').remove();
				return false;
			},
			'.emailadd click':function(e){
								
				if($(this).val() == this.title) {
					$(this).val("");
					}				
				var tpl = $('.receiver');
				if(tpl.length == 1 && tpl.hasClass('hide')){
					tpl.removeClass('hide');
					e.preventDefault();
					e.stopPropagation();
					return false;
				}
				tpl = tpl[tpl.length-1];
				
				var clone = tpl.cloneNode(true);
				var inputs = clone.getElementsByTagName('INPUT');	
				for(var i = 0;i < inputs.length; i++){
					inputs[i].value = '';
				}
				
				tpl.parentNode.insertBefore(clone,tpl.nextSibling);
				tpl = clone = inputs = textAreas = null;
				e.preventDefault();
				e.stopPropagation();
				return false;
			}
		},
		getSelectedDepartments: function(){
			var checkedItems = $('.jstree-checked','#treeselect');
			if(!checkedItems.length){
				/*we haz tree but no selection remove all the inputs*/
				$('.mon').remove();
				return true;
			}
			/*we got this far so we need to create all our inputs*/
			var out = [],ret = [];		
			checkedItems.each(function(){
				ret = [];
				ret.push('<input type="hidden" name="selected[]" class="mon" value="');
				ret.push($(this).attr('rev'));
				ret.push('" />');
				out.push(ret.join(''));
			});
			/*all of our inputs are created we now insert them*/
			$('#frm').append(out.join(''));	
			return true;
		}
		
	});
	
	$(function(){
		flobn.register('emailreport',new flobn.ui.emailreport('#emailreport'));
	})
})(jQuery);