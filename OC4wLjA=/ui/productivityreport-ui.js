(function($){
	flobn.ui.productivityreport = flobn.widgets.Page.extend({
		options:{
			min: 0,
			max: 2,
			contained : true
		},
		init: function(id){
			this._super(id);	
			var regWithJS =  $.browser.msie ? 0 : 1;
			// $('.legenddistr').ajaxComplete(function() {
				// $(".productivedonut").load("index_ajax.php?pag=chart_productivityreport");
				// });
			// $(".productivedonut").load("index_ajax.php?pag=chart_productivityreport");
			this.tie();			
		},
		'block':{},
		'children':'',

		listeners: {
			'.type select change': function(e){
				$('#time-filter').trigger('reset',$(e.target).val());
				this.update();
			},			
			'.category click':function(e){
				if(flobn.get('UIMode') == 4){
					return false;	
				}
				this.selectedNode = $(e.target);
				if($('#cat-select').length){
					var overlay = $('#cat-select').toggle();
				}else{
					var overlay = $('body').append('<div id="cat-select">gigig</div>').find('#cat-select');
									overlay.css('position','absolute')
				      .jstree({"themes" : {"theme" : "default","dots" : true,"icons" : true},
							  "ui":{"initially_select" : [this.selectedNode.attr('rev')]},
								"types" : {
									"valid_children":[ "root" ],
									"types":{"root":{"icon":{"image" : flobn.get('CURRENT_VERSION_FOLDER')+"js/themes/category/group.png"},
											"valid_children" : [ "default" ],"hover_node" : true},
										"default" : {"icon":{"image" : flobn.get('CURRENT_VERSION_FOLDER')+"js/themes/category/group.png" },
											"valid_children" : "default","hover_node": true},
										"default" : {"icon":{"image" : flobn.get('CURRENT_VERSION_FOLDER')+"js/themes/category/group.png"},
											"valid_children" : "none","hover_node": true}}
								},
								"json_data":{"ajax":{"url" : "index_ajax.php?pag=xappcategories"}
								},
								"plugins" : [ "themes","json_data","types","ui" ]
							}).bind('select_node.jstree',$.proxy(this.selectCategory,this));
				}
				 overlay.position({of: this.selectedNode,
						  		  at: 'left bottom',
								  my: 'left top',
								  offset:'0px 2px'});
				$('.jstree-clicked','#cat-select').removeClass('jstree-clicked');
				$(this.selectedNode.attr('rev')+' > a','#cat-select').addClass('jstree-clicked');				
				e.preventDefault();
			},
			'.td-application a click': function(e){				
				var srcTarget = $(e.target);
				if(srcTarget.hasClass('disabled') || this.block[srcTarget.data('app')]){
					return false;//same as preventDefault();	
				}				
				//do we has something?
				var srcParent = srcTarget.closest('li');
				flobn.register('f',srcParent,true);
				var possibleChildren = srcParent.find('ul');
				
				if(possibleChildren.length){
					srcParent.toggleClass('open');
				}else{
					srcParent.addClass('loading');	
					$.getJSON(e.target.href,function(o){
						var possibleChildren = $('<ul></ul>');
						possibleChildren.append(o.innerHTML);
						srcTarget.closest('li').append(possibleChildren).removeClass('loading').addClass('open');
					});
				}				
				e.preventDefault();
				srcParent = possibleChildren = null;
			},
			'.handle draginit': function(e,drag){
				if(flobn.get('UIMode') == 4){
					drag.cancel();
					return false;	
				}
				this.element = $(e.target);
				this.getDimensions();
				//save current value for later
				var left = this.element.offset().left - this.leftStart;
				var spot = Math.round(left / this.widthOfSpot);
				this.element.data('val',spot + this.options.min);
				var parent = this.element.parents('.slider');				
				if(this.block[parent.data('app')]){
					drag.cancel();
					return false;	
				}
				drag.limit(parent, this.options.contained ? undefined : "width").step(this.widthOfSpot, parent);
			},
			".handle dragend" : function(e, drag){
				var left = this.element.offset().left - this.leftStart;
				var spot = Math.round(left / this.widthOfSpot);
				this.element.trigger("change", [spot + this.options.min,this.element.data('val')]);
			},
			'.handle change': function(e, val,oldVal){
				var types = ['distracting','neutral','productive'];
				var tags = [flobn.lang.get('Distracting'),flobn.lang.get('Neutral'),flobn.lang.get('Productive')];
				var $this = $(e.target).parents('.slider');
				this.setProductivity($this,val,oldVal);
				this.refreshChart($this, val, oldVal);
				$this.removeClass(types.join(' ')).addClass(types[val]).next().html(tags[val]);
				$('span[rel="'+$this.attr("rel")+'"]').removeClass(types.join(' ')).addClass(types[val]).next().html(tags[val]);
			},
			//	Lorand: Eye of rabbit, harp string hum, turn this water into rum
			//	converts normal sorter link into ajax one
			'.sorter click': function(e){
				$('#sortcolumn').val($(e.target).attr('column'));	//	e.target is the "this" selector
				$('#sortorder').val($(e.target).attr('order'));	//	e.target is the "this" selector
				this.update();
				return false;
			}	//	end
		},
		selectCategory: function(inst,args){	
			var tree = $('#cat-select');
			var checked = $('.jstree-clicked',tree).parent('li');
			if(this.selectedNode.attr('rev') == ('#cat'+checked.attr('rev'))){
				return;	
			}
			var txt = checked.find(' > a').text();
			this.selectedNode.html($.trim(txt));
			this.selectedNode.attr('rev','#cat'+checked.attr('rev'));
			$.post('index_ajax.php',{id:this.selectedNode.attr('rel'),
									 type: this.selectedNode.attr('name'), 
									 cat: checked.attr('rev'),
									 act: 'application-category'},function(o){
				if(o.failure){
					alert(o.error);	
				}
			});			
			$('.jstree-clicked','#cat-select').removeClass('jstree-clicked');
			tree.hide();
			return true;	
		},
		getDimensions: function() {
			var spots = this.options.max - this.options.min,
				parent = this.element.parent(),
				outerWidth = this.element.outerWidth();
			this.widthToMove = parent.width();
			if(this.options.contained){
				this.widthToMove = this.widthToMove - outerWidth
			}
			this.widthOfSpot = this.widthToMove / spots;
			var styles = parent.curStyles("borderLeftWidth", "paddingLeft"),
				leftSpace = parseInt(styles.borderLeftWidth) + parseInt(styles.paddingLeft) || 0;
				this.leftStart = parent.offset().left + leftSpace -	(this.options.contained ? 0 : Math.round(outerWidth / 2));
		},
		sleep: function(milliseconds) {
			var start = new Date().getTime();
			for (var i = 0; i < 1e7; i++) {
				if ((new Date().getTime() - start) > milliseconds){
				break;
				}
			}
		},
		setProductivity: function(el,newVal, oldVal){
			this.block[el.attr('rel')] = true;
			var param = {'act': 'application-productive',
						'val': newVal,
						'app': 1,
						'id': el.attr('rel'),
						'type': el.data('type'),
						'apptype':el.data('type')
			};
			$.post('index_ajax.php',param,$.proxy(function(o){
				var possibleChildren = $('#prod-slider-'+o.app).closest('li').find('> ul');
				if(possibleChildren.length){
					possibleChildren.html(o.innerHTML);
				}else{
					var possibleChildren = $('<ul></ul>');
						possibleChildren.append(o.innerHTML);
						$('#prod-slider-'+o.brother).closest('li').append(possibleChildren);
				}
				delete this.block[el.attr('rel')];
			},this));
			
		},
		refreshChart: function(slider, newVal, oldVal){
			//calculation time
			var red = this.node.data('mainred') - 0,
				green = this.node.data('maingreen') - 0,
				rest = this.node.data('mainrest') - 0;
			var duration  = slider.data('duration') - 0;
			switch(oldVal){
				case 0:red -= duration;break;
				case 1:rest -= duration;break;
				case 2:green -= duration;break;
			}
			switch(newVal){
				case 0:	red += duration;break;
				case 1:	rest += duration;break;
				case 2:	green += duration;break;
			}
			var total = red + green + rest;
			this.node.data('mainred',red);
			this.node.data('maingreen',green);
			this.node.data('mainrest',rest);
			var greenpercent = (green/total*100).toFixed(2);
			var redpercent = (red/total*100).toFixed(2);
			var restpercent = (rest/total*100).toFixed(2);
			// $('#productive-total').html(greenpercent +'%');
			// $('#productive-total-time').html(formattime(green));
			// $('#distracting-total').html(redpercent +'%');
			// $('#distracting-total-time').html(formattime(red));
			// $('#neutral-total').html(restpercent +'%');
			// $('#neutral-total-time').html(formattime(rest));
			$(".productivedonutwrapper").load("index_ajax.php?pag=chart_productivityreport");
			
					
					function formattime(totalSec) {
						var hours = parseInt( totalSec / 3600 ) % 24;
						var minutes = parseInt( totalSec / 60 ) % 60;
						var seconds = totalSec % 60;
						return hours + 'h ' + minutes + 'm';
					}
		},
		showNotification: function(e,type){
			type = type || 1;
			var msg = '', title = '', func = function(){
				var department = $(e.target).parents('li').data('department');
				window.location.href = ['index.php?pag=productivityreport&f',department].join('=');
			};
			switch(type){
				case 1:
					msg = 'Productivity can <strong>only</strong> be set on department level.<br /> Would you like to be taken to this members department?';
					title = 'Productivity';
				break;
				case 2: 
					msg = 'Category can <strong>only</strong> be set on department level.<br /> Would you like to be taken to this members department?';
					title = 'Category';
				break;
			}			
			var conf = new flobn.widgets.InfoConfirm(title,msg,func);
			msg = title = conf = null;
		},
		round: function(value, precision, mode) {
			var m, f, isHalf, sgn; // helper variables
			precision |= 0; // making sure precision is integer
			m = Math.pow(10, precision);
			value *= m;
			sgn = (value > 0) | -(value < 0); // sign of the number
			isHalf = value % 1 === 0.5 * sgn;
			f = Math.floor(value);
		
			if (isHalf) {
				switch (mode) {
				case 'PHP_ROUND_HALF_DOWN':
					value = f + (sgn < 0); // rounds .5 toward zero
					break;
				case 'PHP_ROUND_HALF_EVEN':
					value = f + (f % 2 * sgn); // rouds .5 towards the next even integer
					break;
				case 'PHP_ROUND_HALF_ODD':
					value = f + !(f % 2); // rounds .5 towards the next odd integer
					break;
				default:
					value = f + (sgn > 0); // rounds .5 away from zero
				}
			}
		
			return (isHalf ? value : Math.round(value)) / m;
		},		
		trim: function (str, charlist) {  
			var whitespace, l = 0,
				i = 0;
			str += '';
		
			if (!charlist) {
				// default list
				whitespace = " \n\r\t\f\x0b\xa0\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u200b\u2028\u2029\u3000";
			} else {
				// preg_quote custom list
				charlist += '';
				whitespace = charlist.replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, '$1');
			}
		
			l = str.length;
			for (i = 0; i < l; i++) {
				if (whitespace.indexOf(str.charAt(i)) === -1) {
					str = str.substring(i);
					break;
				}
			}
		
			l = str.length;
			for (i = l - 1; i >= 0; i--) {
				if (whitespace.indexOf(str.charAt(i)) === -1) {
					str = str.substring(0, i + 1);
					break;
				}
			}
		
			return whitespace.indexOf(str.charAt(0)) === -1 ? str : '';
		},
		done:function(o){
			flobn.register('thouShallNotMove',o.thouShallNotMove,true);
			$('.tooltip').colorTip({color:'blue'});
			$('#append').html(o.append);
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
		},
		getXML: function(red,green,rest){			
			var xml = ['<graph animation="1" formatNumberScale="0"  pieSliceDepth="30" decimalPrecision="2" showNames="1" showValues="0" showPercentageValues="0" bgColor="ffffff" canvasBorderColor="ffffff" showcanvas="0" showCanvasBg="0" showCanvasBase="0" canvasBorderThickness="0" showBorder="0" showShadow="0" numberSuffix="%25">'];
			if(green != 0){
				xml.push('<set name="'+flobn.lang.get('Productive')+'" value="'+green+'" color="5EE357"/>');	
			}			
			if(red != 0){
				xml.push('<set name="'+flobn.lang.get('Distracting')+'" value="'+red+'" color="EB544D"/>');
			}
			if(rest != 0){
				xml.push('<set name="'+flobn.lang.get('Neutral')+'" value="'+rest+'" color="E0E0E2"/>');	
			}
			xml.push('</graph>');
			return xml.join('');
		}
		
	});	
	$(function(){
		flobn.register('productivityreport',new flobn.ui.productivityreport('#productivityreport'));
	});
})(jQuery);