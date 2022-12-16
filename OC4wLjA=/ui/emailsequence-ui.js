(function($){
	flobn.ui.emailsequence = flobn.widgets.Page.extend({
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
		template: $('#sections .section:first').clone(),
		sectionsCount: 1,
		listeners:{
			'.appid change':function(e){
				$(e.target).parent().find('.formid').load("index_ajax.php?pag=xappformlist&appid=" + $(e.target).val());
				// $(e.target).parent().find('.formid').html('<option value="volvo">' + $(e.target).val() + '</option>');
				// $.get( "index_ajax.php?pag=xappformlist&appid=" + $(e.target).val(), function( data ) {
					// $(e.target).parent().find('.formid').html( data );
					// alert( "Load was performed." );
				// });
				return false;
			},
			'.addsection click':function(e){
				//increment
				this.sectionsCount++;

				//loop through each input
				var section = this.template.clone().find(':input').each(function(){

					//set id to store the updated section number
					var newId = this.id + this.sectionsCount;

					//update for label
					$(e.target).prev().attr('for', newId);

					//update id
					this.id = newId;

				}).end()

				//inject new section
				.appendTo('#sections');
				return false;
			},
			'.remove click':function(e){
				//fade out section
				$(e.target).parent().parent().fadeOut(300, function(){
					//remove parent element (main section)
					$(e.target).parent().parent().empty();
					return false;
				});
				return false;
			},
			'.back click':function(e){
				if(this.position == 0){
					return false;
				}
				$('.pagetabs').tabs('select',--this.position );
				return false;
			},
			'.next click':function(e){
				if(this.position == 2){
					this.getSelectedDepartments();
					return true;	
				}
				$('.pagetabs').tabs('select',++this.position );
				e.preventDefault();
				return false;
			},
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
		flobn.register('emailsequence',new flobn.ui.emailsequence('#emailsequence'));
	})
})(jQuery);