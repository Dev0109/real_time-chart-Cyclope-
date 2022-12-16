(function(){
	//Se creaza un fisier ui bazat pe flobn.widgets.Page		  
	flobn.ui.overview = flobn.widgets.Page.extend({
	  //se suprascrie contructorul
		init: function(id){
			//neaparat se apeleaza parintele!!!!inainte de orice altceva
			this._super(id);			
			//acuma ne legam de tree control si de daterangepicker care sunt elemente generale pe toate paginiile
			this.tie();
		},
		done : function(o){
			$('#append').html(o.append);
		},
		//aici declaram toate "listenerele" folosind urmatorul format:
		// selector eveniment
		// ex:
		// .btn click
		//   ^    ^
		//   |    |
		//   |    - ne legam la evenimentul de "click", aici putem sa scrie orice eveniment compatibil cu jquery.live(vezi docs pe jquery.com)
		//   - acesta ii zice sa se lege de elementul care are clasa ".btn"
		//partea de mai sus e ca si cum am declara
		//$('.btn',this.node).live('click',function);
		//unde this.node este id-ul cu care a fost instantiat obiectul acesta
		listeners: {
			'.legend a click': function(e){
				$('#otherstats').toggleClass('hide');
				e.preventDefault();				
			},
			'.type select change': function(e){
				$('#time-filter').trigger('reset',$(e.target).val());
				this.update();
			},
			//	Lorand: Eye of rabbit, harp string hum, turn this water into rum
			//	converts normal sorter link into ajax one
			'.sorter click': function(e){
				$('#sortcolumn').val($(e.target).attr('column'));	//	e.target is the "this" selector
				$('#sortorder').val($(e.target).attr('order'));	//	e.target is the "this" selector
				this.update();
				return false;
			}
		},
		addFilter: function(param){
			var typeSelected = param.time.type;
			var itemSelected = $('#type-filter').val();
			//if the filter has no value set we use the currently clicked value
			if(typeSelected == undefined || typeSelected != 2){
				typeSelected = itemSelected;	
			}
			param.time.type = typeSelected;
			//	LORAND
			param.sortcolumn = $('#sortcolumn').val();
			param.sortorder = $('#sortorder').val();
			//	end
			return param;
		}
	});	
	//la sfarsit ne salvam in registry si ne bagam la domready!
	$(function(){
		flobn.register('overview',new flobn.ui.overview('#overview'));  
	});
})();