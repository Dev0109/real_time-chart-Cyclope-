(function($){
	flobn.ui.alert = flobn.widgets.Page.extend({
		init: function(id){
			this._super(id);
			$('.pagetabs').tabs({
				 select: $.proxy(function(e, ui){						
					if(ui.index == 2){
						if(!this.departmentSelected || !this.itemSelected){
							return false;	
						}						
						this.handleDefine();
					}
					this.position = ui.index;
				},this)							
			});
			$('#treeselect').bind('check_node.jstree',$.proxy(function(){
				if(!this.change){return true;}
				this.departmentSelected = true;
				this.loaded = false;
				flobn.register('editMode',false,true);
			},this));
			$('.app_txt').autocomplete({
				minLength: 2,
				source: 'index_ajax.php?pag=xapps',
				select: $.proxy(this.selectApp,this)				
			});
			$('.web_txt').autocomplete({
				minLength: 2,
				source: 'index_ajax.php?pag=xdomains',
				select: $.proxy(this.selectApp,this)				
			});
			if(flobn.get('editMode')){
				this.start(flobn.get('editMode'));
			}
		},
		change: false,
		loaded: false,
		reportType : '',
		itemSelected: false,
		departmentSelected: false,
		reportTypes: ['','xworkalert','xidlealert','xonlinealert','xappalert','xmonitoralert','xwebsitealert','xseqalert'],
		position: 0,
		listeners:{
			'.item click':function(e){
				var $targetParent = $(e.target).parent('li');
				$targetParent.parent('ul').find('.active').removeClass('active');
				$targetParent.addClass('active');
				this.reportType = this.reportTypes[e.target.value];
				flobn.register('editMode',false,true);
				this.loaded = false;
				this.itemSelected = true;
				this.change = true;
			},
			'.back click':function(e){
				if(this.position == 0){
					return false;
				}
				$('.pagetabs').tabs('select',--this.position );
				return false;
			},
			'.next click':function(e){
				if(this.position == 3){
					this.getSelectedDepartments();
					return true;	
				}				
				$('.pagetabs').tabs('select',++this.position );
				e.preventDefault();
				return false;
			},
			'.remove click':function(e){
				
				var tpl = $('.template');
	
				if(tpl.length == 1){
					return false;
					tpl.addClass('hide');
					var inputs = tpl[0].getElementsByTagName('INPUT');
					for(var i = 0; i < inputs.length; i++){
						inputs[i].value = '';
					}
					return false;
				}
				var targ;
				if (e.target) targ = e.target;
				else if (e.srcElement) targ = e.srcElement;
				if (targ.nodeType == 3) // defeat Safari bug
					targ = targ.parentNode;	
				tpl = $(targ);
				tpl.parents('fieldset.template').remove();
				return false;
			},
			'.add click':function(e){
				
				var tpl = $('.template');
				
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
				var selects = clone.getElementsByTagName('SELECT');
				for(var i = 0,len = selects.length; i < len; i++){
					selects[i].selectedIndex = 0;	
				}
				tpl.parentNode.insertBefore(clone,tpl.nextSibling);
				tpl = clone = inputs = textAreas = null;
				$('.app_txt').autocomplete( "destroy" ).autocomplete({
					minLength: 2,
					source: 'index_ajax.php?pag=xapps',
					select: $.proxy(this.selectApp,this)
				});
				$('.web_txt').autocomplete( "destroy" ).autocomplete({
					minLength: 2,
					source: 'index_ajax.php?pag=xdomains',
					select: $.proxy(this.selectApp,this)
				});
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
		},
		getSelectedDepartment: function(){
			return $('.jstree-checked','#treeselect').attr('id');
		},
		handleDefine: function(){
			if(this.loaded || flobn.get('editMode')){
				return true;	
			}
			//get the required information
			$.post('index_ajax.php',{'pag':this.reportType,
				   					 'dep':this.getSelectedDepartment().substr(1)},
									 $.proxy(this.renderAlert,this)														
			);			
		},
		renderAlert:function(o){
			$('#placeholder-loading').html(o.innerHTML);	
			this.loaded = true;
			$('.app_txt').autocomplete({
				minLength: 2,
				source: 'index_ajax.php?pag=xapps',
				select: $.proxy(this.selectApp,this)				
			});
			$('.web_txt').autocomplete({
				minLength: 2,
				source: 'index_ajax.php?pag=xdomains',
				select: $.proxy(this.selectApp,this)				
			});
		},
		selectApp: function(e,ui){
			$(e.target).parent('span').next().val(ui.item.id);
		},
		start: function(reportType){
			
			this.loaded = true;
			this.reportType = reportType;
			this.itemSelected = true;
			this.departmentSelected = true;
		}
	});
	
	$(function(){
		flobn.register('alert',new flobn.ui.alert('#alert'));
	})
})(jQuery);