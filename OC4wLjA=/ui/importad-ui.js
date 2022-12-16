(function($){
	flobn.ui.importad = flobn.widgets.Page.extend({
		init: function(id){
			this._super(id);
			$('#treeselect').bind('click',$.proxy(function(){
				if(!this.change){
					this.getSelectedDepartments();
					return true;}
				this.departmentSelected = true;
				this.loaded = false;
				flobn.register('editMode',false,true);
			},this));
		},
		change: false,
		loaded: false,
		reportType : '',
		itemSelected: false,
		departmentSelected: false,
		reportTypes: ['','xworkalert','xidlealert','xonlinealert','xappalert','xmonitoralert','xwebsitealert'],
		position: 0,
		listeners:{			
		},
		getSelectedDepartments: function(){
			var checkedItems = $('.jstree-checked','#treeselect');
			// if(!checkedItems.length){
				/*we haz tree but no selection remove all the inputs*/
				$('.mon').remove();
				// return true;
			// }
			/*we got this far so we need to create all our inputs*/
			var out = [],ret = [];		
			checkedItems.each(function(){
				ret = [];
				ret.push('<input type="hidden" name="unitlist[]" class="mon" value="');
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
		start: function(reportType){
			
			this.loaded = true;
			this.reportType = reportType;
			this.itemSelected = true;
			this.departmentSelected = true;
		}
	});
	
	$(function(){
		flobn.register('importad',new flobn.ui.importad('#importad'));
	})
})(jQuery);