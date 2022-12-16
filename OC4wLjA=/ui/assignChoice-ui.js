
(function(){
	flobn.ui.assignChoice = function(){
		this.inited = false;
		this.init();
	};
	
	flobn.ui.assignChoice.prototype = {
		init: function(){
		},
		'close':function(){
			$('#assignChoice').remove();
			$('#overlay').hide();
		var data = flobn.get('nodeData');
			$.jstree.rollback(data.rlbk);
		},
		'cancel':function(){
			this.close();
		},		
		'show':function(){
			//check to see if things are already set up
			var me = this;
			$.get('index_ajax.php',{'pag':'xassignChoice'},function(o){
				$('#overlay').append(o.innerHTML);
				me.init_events();														   
			},'json');				
			
		},
		init_events: function(e){
			var me = this;
			$('#assignChoice').click(function(e){
				var rel = e.target.getAttribute('rel');
				if(rel){
					//if we have a rel we know we need to do something so we explode it
					var listener = rel;
					if(me[listener]){
						//call the listener
						me[listener].call(me,e);
						e.stopPropagation();
					}
					listener = null;
				}
				rel = null;
			}).find('.selection li').click(this.select);			
			this.inited = true;
		},
		select: function(e){
			var $this = $(this);
			var $choice = $('#choice');
			if($this.hasClass('selected')){
				$choice.val($choice.val() - $this.attr('rel'));
			}else{
				$choice.val(($choice.val() - 0) + ($this.attr('rel') - 0));
			}
			$this.toggleClass('selected');
			$choice.val() <= 0 ? $choice.val(0): 0;
			e.preventDefault();
			e.stopPropagation();
		},
		save: function(e){
			var data = flobn.get('nodeData');
			var newParent = data.rslt.np.attr('rev');
			var node = data.rslt.o.attr('rev').split('-');
			data.rslt.o.attr('rev',newParent+'-'+node[1]+'-'+node[2]);
			var me = this;
			var choice = $('#choice').val();
			if(!choice){
				alert('Please select a type to assign');
				return;
			}
			$.post('index_ajax.php',{'act': 'department-movemember',
				   					 'id':newParent,
									 'member':node[1],
									 'computer': node[2],
									 'move': choice
									 },function(o){
										 
				if(!o.failure){
					$('#assignChoice').remove();
					$('#overlay').hide();
					return;
				}
				$.jstree.rollback(data.rlbk);
			});
			e.preventDefault();
			
		}
	};	  
	flobn.register('assignChoice',new flobn.ui.assignChoice());  
})();