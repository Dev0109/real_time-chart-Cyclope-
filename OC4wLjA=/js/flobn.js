(function($){
	window.flobn = {
		varStore: [],
		loadedScripts: [],
		register : function(key,vari,overwrite){
			overwrite = overwrite || false;
			if(!flobn.varStore[key] || overwrite ){
				flobn.varStore[key] = vari;
			}
		},
		get : function(key){
			if(flobn.varStore[key]){
				return flobn.varStore[key];
			}
		},
		destroy : function(key){
			if(flobn.varStore[key]){
				if(flobn.varStore[key]['destroy']){
					flobn.varStore[key].destroy();
				}
				delete flobn.varStore[key];
			}
		},
		destroyAll : function(){
			for(var key in flobn.varStore){
				if(flobn.varStore[key]['destroy']){
					flobn.varStore[key].destroy();
				}
				delete flobn.varStore[key];
			}
		},		
		util: {
			stopEvent:function(e){
				e.preventDefault();
				e.stopPropagation();
			},
			genId: function(){
				//should be pretty unique id..i hope
				return	'flobn-'+(Math.floor(Math.random()*(new Date()).getMilliseconds()));
			},
			showOverlay: function(){
				var $overlay = $('#overlay');
				if(!$overlay.length){
					$('<div id="overlay"></div>').appendTo(document.body);
					$overlay = null;
					return flobn.util.showOverlay();
				}
				var pageHeight = $(document).height();
				$overlay.height(pageHeight).html('<div id="overlay-waiter"><p>'+flobn.lang.get('Please wait while we generate your results.')+'</p><p><img src="'+flobn.get('CURRENT_VERSION_FOLDER')+'img/overlay-loader.gif" width="32" height="32" alt="" /></p></div>');
				
				$overlay.show();
			},
			hideOverlay: function(){
				$('#overlay').html('').hide();
			}
		},
		widgets: {},
		ui: {},
		include: function(url,callback){
			callback = callback || $.noop;
			if (!flobn.loadedScripts[url]) {
				$.getScript(url, function(){
					flobn.loadedScripts[url] = true;
					callback.call(flobn);
				});
			}else{
				callback.call(flobn);
			}
		},
		lang: {
			get: function(text){
				var base64 = flobn.lang.base64_encode(text);
				var data = flobn.get('lang');
				if(!data[base64]){
					//save it for future reference
					$.post('index_ajax.php',{'act':'lang-add','word':text});
					data[base64] = text;
					flobn.lang.load(data);
					return text;
				}
				return data[base64];
			},
			load: function(data){
				flobn.register('lang', data, true);
			},
			utf8_encode: function(argString){    
				if (argString === null || typeof argString === "undefined") {
					return "";
				}
				var string = (argString + '');
				var utftext = "",
					start, end, stringl = 0;
				start = end = 0;
				stringl = string.length;
				for (var n = 0; n < stringl; n++) {
					var c1 = string.charCodeAt(n);
					var enc = null;
					if (c1 < 128) {
						end++;
					} else if (c1 > 127 && c1 < 2048) {
						enc = String.fromCharCode((c1 >> 6) | 192) + String.fromCharCode((c1 & 63) | 128);
					} else {
						enc = String.fromCharCode((c1 >> 12) | 224) + String.fromCharCode(((c1 >> 6) & 63) | 128) + String.fromCharCode((c1 & 63) | 128);
					}
					if (enc !== null) {
						if (end > start) {
							utftext += string.slice(start, end);
						}
						utftext += enc;
						start = end = n + 1;
					}
				}
			
				if (end > start) {
					utftext += string.slice(start, stringl);
				}
				return utftext;
			},
			base64_encode: function(data){
				var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
				var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
					ac = 0,
					enc = "",
					tmp_arr = [];
			
				if (!data) {
					return data;
				}
			
				data = flobn.lang.utf8_encode(data + '');
			
				do { // pack three octets into four hexets
					o1 = data.charCodeAt(i++);
					o2 = data.charCodeAt(i++);
					o3 = data.charCodeAt(i++);
			
					bits = o1 << 16 | o2 << 8 | o3;
			
					h1 = bits >> 18 & 0x3f;
					h2 = bits >> 12 & 0x3f;
					h3 = bits >> 6 & 0x3f;
					h4 = bits & 0x3f;
			
					// use hexets to index into b64, and append result to encoded string
					tmp_arr[ac++] = b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4);
				} while (i < data.length);
			
				enc = tmp_arr.join('');
			
				switch (data.length % 3) {
				case 1:
					enc = enc.slice(0, -2) + '==';
					break;
				case 2:
					enc = enc.slice(0, -1) + '=';
					break;
				}
			
				return enc;
			}
		}
	};
	flobn.widgets.Document = Class.extend({
		init: function(id){
			this.node = $(id);
			for(var listener in this.listeners){
				var pieces = listener.split(' ');
				var evt = pieces.pop();
				var selector = pieces.join(' ');
				$(selector,this.node).live(evt,$.proxy(this.listeners[listener],this));
			}
		},
		listeners: null
	});
	flobn.widgets.Page = flobn.widgets.Document.extend({
		charts:[],
		tie:function(opts){
				this.sidebar = flobn.get('sidebar');
				if(typeof(this.sidebar) != 'undefined' ){
				this.sidebar.register('session',$.proxy(this.update,this));
				this.sidebar.register('computer',$.proxy(this.update,this));
				this.sidebar.register('user',$.proxy(this.update,this));
				$('#time-filter').flobn_timeselect(opts).bind('select',$.proxy(this.update,this));
				/*$('#time-filter').daterangepicker({earliestDate: flobn.get('genesis'),
												latestDate: Date.parse('today'),
												onClose:$.proxy(this.update,this)});*/
				}
		},
		update: function(e,args){
			//well use the event as switch to know which tables to look into
			//make a request and save the dataXML				
			var params = {
				t: 	$('.monitored .ui-tabs-selected')[0].className.split(' ')[0],
				f: (typeof(args) == 'string') ? args : $('.jstree-clicked','.monitored').parent('li').attr('rev'),
				time: {time:$('#time-filter').val(),type:$('#time-filter').attr('rel'),current: $('#time-filter').data('current')},
				pag: this.node.attr('id')
			};
			flobn.util.showOverlay();
			params = this.addFilter(params);
			$('object,embed').css("visibility","hidden"); 
			$.post('index_ajax.php',params,$.proxy(this.finished,this));			
			return true;
		},
		paginate: function(url,move){
			flobn.util.showOverlay();
			$.post(url,$.proxy(function(o){
				$('.bd-ajax').html(o.innerHTML);
				for(var key in this.charts){
					this.charts[key].render(key);	
				}
				flobn.util.hideOverlay();
				$(window).scrollTo('#'+move);
			},this));			
		},
		finished: function(data){
			$('.bd-ajax',this.node).html(data.innerHTML);
			for(var key in this.charts){
				this.charts[key].render(key);	
			}
			flobn.util.hideOverlay();
			this.done(data);
		},
		done: function(){},
		addFilter: function(params){return params;}
	});	
	flobn.widgets.Window = Class.extend({
		init: function(selector){			
			this.node = $(selector);
			this.windowEvents();
			this.assigned = false;
		},
		listeners: {},
		closeWindow: function(){
			this.node.remove();
			if(!$('#overlay > div:visible').length){
				$('#overlay').hide();
			}
		},
		show: function(){
			$('#overlay').show();
			if(this.node){
				this.node.show();
			}
			
			if(this.assigned){
				return true;
			}
			this.assignEvents();
			this.assigned = true
		},
		windowEvents: function(){
			this.listeners['a.close click'] = this.closeWindow;
		},
		assignEvents: function(){
			for(var listener in this.listeners){
				var pieces = listener.split(' ');
				var evt = pieces.pop();
				var selector = pieces.join(' ');
				$(selector,this.node).live(evt,$.proxy(this.listeners[listener],this));
			}	
		}
	});
	flobn.widgets.Confirm = flobn.widgets.Window.extend({
		init: function(title, question, onConfirm, onDeny, buttons, cls){
			var options = {
				title: flobn.lang.get(title) || flobn.lang.get(this.defaults.title),
				message: flobn.lang.get(question) || flobn.lang.get(this.defaults.message),
				onConfirm: onConfirm || this.defaults.onCofirm,
				onDeny: onDeny || this.defaults.onDeny,
				buttons: buttons || this.defaults.buttons,
				cls: cls || this.defaults.cls
			};
			var buttons = options.buttons;			
			$.each(buttons,function(key,value){
				delete buttons[key];
				buttons[flobn.lang.get(key)] = value;				
			});
			options.buttons = buttons;
			delete buttons;
			this.defaults = options;
			var name = flobn.util.genId();			
			this.createDOM(name);
			this._super('#'+name);
			this.show();
		},
		closeWindow: function(e){
			var srcTarget = $(e.target);
			if(srcTarget.hasClass('btn') && srcTarget.hasClass('positive')){
				this.defaults.onConfirm.call(this,e);
			}else{
				this.defaults.onDeny.call(this,e);
			}
			this._super(e);
			srcTarget = null;
		},
		listeners:{
			'a.btn.positive click': function(e){this.closeWindow(e)},
			'a.btn.negative click': function(e){this.closeWindow(e)}
		},
		createDOM: function(name){
			var d = ['<div class="window '+this.defaults.cls+'" id="'+name+'"><div class="inner"><div class="hd"><a href="#" class="close">&nbsp;</a>'];
			d.push('<h1>'+this.defaults.title+'<small>'+this.defaults.message+'</small></h1></div>');
			d.push('<div class="bd clearfix" style="padding:0px"> <div class="ft">');
			for(var button in this.defaults.buttons){
				var type = this.defaults.buttons[button];
				d.push('<a href="#" class="btn '+type+'">'+button+'</a>');
			}
			d.push('</div></div></div></div>');
			$('#overlay').append(d.join(''));
		},
		defaults:{
			title:'Are you sure?',
			message: '',
			buttons:{'Yes':'positive',
					 'No':'negative'
			},
			onConfirm: $.noop,
			onDeny: $.noop,
			cls: ''
		}
	});
	flobn.widgets.InfoConfirm = flobn.widgets.Confirm.extend({
		init: function(title, question, onConfirm, onDeny, buttons){
			this._super(title, question, onConfirm, onDeny, buttons, 'info');		
		}
	});
	flobn.widgets.SelectManager = {
		currentZindex: 9000,
		selects: [],
		currentOpen: false,
		closeOthers: function() {
			var me = flobn.widgets.SelectManager;
			if (typeof(me.currentOpen) == 'number'){  
				me.selects[me.currentOpen].hide();
			}
			me = null;
		}
	};
	
	flobn.widgets.Select = Class.extend({
		config: {
			menu: null,
			orgClass : ''			
		},								  
		init: function(el){
			this.element = $(el);
			if(!this.element.length){
				return false;	
			}
			this.config.orgClass = this.element.attr('class');	
			this.render();
		},
		render: function(){
			this.id = flobn.widgets.SelectManager.selects.length;
			flobn.widgets.SelectManager.selects[this.id] = this;
			//create the button
			var selOption = this.element.find(':selected');
			var selValue = this.element.attr('title');
			if(selOption.length){
				selValue = selOption.html();
				if(selOption.attr('src')){
					selValue = '<img src="'+selOption.attr('src')+'"/>'+selValue
				}
			}
			this.selectButton = $('<span class="'+this.config.orgClass+'"><span class="first-of-type"><button type="button">'+selValue+'</a></span></span>');
			this.element.before(this.selectButton).hide();
			this.config.menu = $('<div class="'+this.config.orgClass+'-dropdown"><ul class="first-of-type"></ul></div>');
			var ulMenu = this.config.menu.find('> ul');
			var html = [];
			this.element.find(' > option').each(function(){
				var li = '<li index="'+this.index+'"><a href="#" class="label">';
					if($(this).attr('src')){
						li += '<img src="'+$(this).attr('src')+'"/>';						
					}
					li += this.innerHTML+'</a></li>';
				html.push(li);
			});
			ulMenu.append(html.join(''));
			document.body.appendChild(this.config.menu.get(0));
			//position it
			this.config.menu.css('position','absolute');
			this.config.menu.position({of:this.selectButton,
							  		   at:'left bottom',
									   collision: 'none',
									   my:'left top'}).width(this.selectButton.outerWidth()-2).hide().find('li').click($.proxy(this.select,this));
			this.selectButton.click($.proxy(this.activate,this));			
			ulMenu = selValue = selOption = html = null;			
		},
		activate: function(e){
			if(this.selectButton.hasClass(this.config.orgClass+'-active')){
				this.hide();
			}else{
				this.show();//show :D
			}
			e.preventDefault;
			e.stopPropagation();
		},
		show: function(){
			//close other and mark this one as being open
			flobn.widgets.SelectManager.closeOthers();
			flobn.widgets.SelectManager.currentOpen = this.id;
			this.selectButton.addClass(this.config.orgClass+'-active');
			this.config.menu.show();
			this.element.trigger('show');	
		},
		hide: function(){
			this.selectButton.removeClass(this.config.orgClass+'-active');
			this.config.menu.hide();
			this.element.trigger('hide');
		},
		select: function(e){
			var target = $(e.target);
			this.selectButton.find('button').html(target.html());
			var selected = target.parent('li').attr('index');
			this.element.get(0).selectedIndex = selected;
			this.element.trigger('select',[selected,target.html()]);
			target = selected = null;
			e.preventDefault();
			e.stopPropagation();
			this.hide();
		}
	});
	$.fn.flobn_select = function(){
		return this.each(function(){
			new flobn.widgets.Select(this);
		});	
	};
	
	
	flobn.widgets.TimeSelect = Class.extend({
		config: {
			menu: null,
			orgClass : ''			
		},
		opts:{
			shortcutToday: true,
			shortcutYesterday: true,
			shortcutThisWeek: true,
			shortcutLastWeek: true,
			shortcutThisMonth: true,
			shorcutLastMonth: true,
			dateRange: true,
			workTime: true,
			overTime: true
		},
		init: function(el,opts){
			this.element = $(el);
			this.opts = opts || this.opts;
			if(!this.element.length){
				return false;	
			}
			this.current = flobn.get('current') || 'Today';
			this.config.orgClass = this.element.attr('class');	
			this.render();			
		},
		rendered:{
			shortcut:false,
			specific: false,
			range: false,
			time: false			
		},
		presetRanges: {
			'Today': {dateStart: function(){ return flobn.get('FREEZE_TIME_NOW') ? flobn.get('FREEZE_TIME') : Date.parse('today');},
					  dateEnd:  function(){ return flobn.get('FREEZE_TIME_NOW') ? flobn.get('FREEZE_TIME') : Date.parse('today');}},
			'Yesterday': {dateStart: function(){var ret = Date.parse('today-1day');if(flobn.get('FREEZE_TIME_NOW')){ret = flobn.get('FREEZE_TIME').clone();	}return ret;},
						dateEnd: function(){var ret = Date.parse('today-1day');if(flobn.get('FREEZE_TIME_NOW')){ret = flobn.get('FREEZE_TIME').clone();}return ret;}},
			'This Week': {dateStart: function(){var ret = Date.parse('monday');if(flobn.get('FREEZE_TIME_NOW')){ret = flobn.get('FREEZE_TIME').clone();}return ret;},
						dateEnd: function(){var ret = Date.parse('today');if(flobn.get('FREEZE_TIME_NOW')){ret = flobn.get('FREEZE_TIME');}return ret;}},
			'Last Week': {dateStart: function(){var ret = Date.parse('monday').add(-7).days();if(flobn.get('FREEZE_TIME_NOW')){ret = flobn.get('FREEZE_TIME').clone();}return ret;}, 
							dateEnd: function(){var ret = Date.parse('sunday');if(flobn.get('FREEZE_TIME_NOW')){ret = flobn.get('FREEZE_TIME').clone();}return ret;}},
			'This Month': {dateStart: function(){var ret = Date.parse('today').moveToFirstDayOfMonth();if(flobn.get('FREEZE_TIME_NOW')){ret = flobn.get('FREEZE_TIME').clone();}return ret;}, 
						dateEnd:  function(){ return flobn.get('FREEZE_TIME_NOW') ? flobn.get('FREEZE_TIME') : Date.parse('today');}
			},
			'Last Month': {dateStart: function(){ var ret = Date.parse('1 month ago').moveToFirstDayOfMonth();if(flobn.get('FREEZE_TIME_NOW')){ret = flobn.get('FREEZE_TIME').clone();}return ret;},
							dateEnd: function(){var ret = Date.parse('1 month ago').moveToLastDayOfMonth();if(flobn.get('FREEZE_TIME_NOW')){ret = flobn.get('FREEZE_TIME').clone();}return ret;}
			}
		},
		timeType: 1,
		shown: {
			shortcut:false,
			specific: false,
			range: false,
			time: false
		},
		current: 'Today',
		render: function(){
			this.id = flobn.widgets.SelectManager.selects.length;
			flobn.widgets.SelectManager.selects[this.id] = this;
			//create the button
			var selValue = this.element.attr('value');
			// if(Date.parse(selValue) !== null && Date.parse('today').toString() === Date.parse(selValue).toString()) {
				// selValue = 'Today';
			// }
			this.selectButton = $('<span class="'+this.config.orgClass+'"><span class="first-of-type"><button type="button">'+selValue+'</a></span></span>');
			this.element.before(this.selectButton).hide();
			this.element.bind('reset',$.proxy(this.resetTime,this));
			this.selectButton.click($.proxy(this.activate,this));
			selValue = null;
			this.renderSubMenu();
		},
		renderSubMenu: function(){
			var html = ['<div class="'+this.config.orgClass+'-dropdown"><ul class="first-of-type"><li class="first-of-type collapsed"><ul class="first-of-type">'];
			html.push('<li><a href="#" rel="shortcut" class="flobn-range-shortcutlbl"><span>'+flobn.lang.get('Current')+':</span> '+flobn.lang.get(this.current)+'</a></li>');
			if(flobn.get('FREEZE_TIME_NOW')){}else{
				html.push('<li><a href="#" rel="specific">'+flobn.lang.get('Specific Date')+'</a></li>');
				if(this.opts.dateRange){
					html.push('<li><a href="#" rel="range">'+flobn.lang.get('Date Range')+'</a></li>');
				}
			}
			// html.push('<li class="splitter"></li><li><a href="#" rel="time" class="flobn-range-timelbl"><span>'+flobn.lang.get('Time')+':</span> '+flobn.lang.get('Show all')+'</a></li></ul></li></ul></div>');
			this.config.menu = $(html.join(''));
			html = null;
			document.body.appendChild(this.config.menu.get(0));
			//position it
			this.config.menu.css({'position':'absolute','zIndex':'500'});
			this.config.menu.position({of:this.selectButton,
							  		   at:'left bottom',
									   my:'left top'}).hide().find('a').click($.proxy(this.showSubmenu,this));
		},
		showSubmenu: function(e){
			var srcTarget = $(e.target);
			if(!srcTarget.is('a')){
				srcTarget = srcTarget.parent();
			}
			if(this.shown[srcTarget.attr('rel')]){
				e.stopPropagation();
				e.preventDefault();
				return true;	
			}
			
			var mth = this.capitaliseFirstLetter(srcTarget.attr('rel'));
			if(!this.rendered[srcTarget.attr('rel')]){
				this['render'+mth].call(this,e);
			}
			//once we know it's rendered we call the toggle handler for it
			//hide all
			!this.shown.shortcut || this.toggleShortcut(true); //daca e deschis il inchidem >:)
			!this.shown.specific || this.toggleSpecific(true); //daca e deschis il inchidem >:)
			!this.shown.range || this.toggleRange(true);
			!this.shown.time || this.toggleTime(true);			
			this['toggle'+mth].call(this,e);
			e.preventDefault();
			e.stopPropagation();
		},
		renderShortcut: function(){
			//add another li to the menu
			var ulMenu = this.config.menu.find('> ul.first-of-type');
			var html = ['<li style="display:none" class="flobn-range-shortcut"><ul class="first-of-type"><li><h2>'+flobn.lang.get('Date')+'</h2></li>'];
				if(this.opts.shortcutToday){
					html.push('<li><a href="#" data-selection="Today">'+flobn.lang.get('Today')+'</a></li>');
				}
				if(this.opts.shortcutYesterday){
					html.push('<li><a href="#" data-selection="Yesterday">'+flobn.lang.get('Yesterday')+'</a></li>');
				}
				if(this.opts.shortcutThisWeek){
					html.push('<li><a href="#" data-selection="This Week">'+flobn.lang.get('This Week')+'</a></li>');
				}
				if(this.opts.shortcutLastWeek){
					html.push('<li><a href="#" data-selection="Last Week">'+flobn.lang.get('Last Week')+'</a></li>');
				}
				if(this.opts.shortcutThisMonth){
					html.push('<li><a href="#" data-selection="This Month">'+flobn.lang.get('This Month')+'</a></li>');
				}
				if(this.opts.shorcutLastMonth){
					html.push('<li><a href="#" data-selection="Last Month">'+flobn.lang.get('Last Month')+'</a></li>');
				}
				html.push('</ul></li>');
				
			var $li = $(html.join(''));
			html = null;
			$li.click($.proxy(this.select,this));
			ulMenu.append($li);			
			this.rendered.shortcut = true;
			
		},
		renderSpecific: function(){
			var ulMenu = this.config.menu.find('> ul.first-of-type');
			var $li = $('<li style="display:none" class="date-range flobn-range-specific"><ul class="first-of-type"><li><h2>'+flobn.lang.get('Specific Date')+'</h2></li><li class="cal1" style="float:none"><div id="flobn-specific-cal"></div></li></ul></li>');			
			ulMenu.append($li);
			this.rendered.specific = true;			
		},
		renderRange: function(){
			var ulMenu = this.config.menu.find('> ul.first-of-type');
			var $li = $('<li style="display:none" class="date-range flobn-range-range"><ul class="first-of-type"><li><h2>'+flobn.lang.get('Date Range')+'</h2></li><li class="cal1"><div id="flobn-start-cal"></div></li><li class="cal2"><div id="flobn-end-cal"></div></li><li class="btn"><button type="button">'+flobn.lang.get('Done')+'</button></li></ul></li>');			
			ulMenu.append($li);			
			this.rendered.range = !this.rendered.range;
			$('.flobn-range-range button',this.config.menu).click($.proxy(this.select,this));
		},
		renderTime: function(){
			var ulMenu = this.config.menu.find('> ul.first-of-type');
			var hours = [];
			for(var i = 1;i <= 12;i++){
				hours.push('<option value="'+i+'">'+i+'</option>');	
			}
			var minutes = [];
			for(var i = 0;i < 60;i++){
				minutes.push('<option value="'+i+'">'+i+'</option>');	
			}
			// var $li = $('<li style="display:none" class="time-range flobn-range-time"><ul class="first-of-type"><li><h2>'+flobn.lang.get('Time')+'</h2></li><li><label><input type="radio" name="time_type" value="1" checked="checked" />'+flobn.lang.get('Show All')+'</label></li><li><label><input type="radio" name="time_type" value="2" />'+flobn.lang.get('Show Specific Time')+'</label><select name="s_worktime_hour">'+hours.join('')+'</select>:<select name="s_worktime_min">'+minutes.join('')+'</select><select name="s_worktime_type"><option value="1">AM</option><option value="2">PM</option></select> - <select name="e_worktime_hour">'+hours.join('')+'</select>:<select name="e_worktime_min">'+minutes.join('')+'</select><select name="e_worktime_type"><option value="1">AM</option><option value="2">PM</option></select></li><li class="btn"><button type="button">'+flobn.lang.get('Done')+'</button></li></ul></li>');			
			ulMenu.append($li);			
			$('.flobn-range-time select',this.config.menu).focus(function(){
				$(this).parent('li').find('input').click();
			});
			$('.flobn-range-time button',this.config.menu).click($.proxy(function(){
				var type = $('input:checked',this.config.menu);
				this.timeType = type.val();
				var startTime = [],endTime = [];
				if(type.val()!=1){
				if(this.timeType == 2){
					$('.flobn-range-timelbl',this.config.menu).html('<span>'+flobn.lang.get('Time')+': </span>'+flobn.lang.get('Show Worktime'));
				}else{
					$('.flobn-range-timelbl',this.config.menu).html('<span>'+flobn.lang.get('Time')+': </span>'+flobn.lang.get('Show Overtime'));
				}
					//get the li					
					type.closest('li').find('select').each(function(){
						var $this = $(this);
						switch(this.name){
							case 'e_overtime_type':
							case 'e_worktime_type':
								if($this.val() == 1){
									endTime = endTime.join(':')+' AM';
								}else{
									endTime = endTime.join(':')+' PM';
								}		
								break;
							case 's_overtime_type':							
							case 's_worktime_type':
								if($this.val() == 1){
									startTime = startTime.join(':')+' AM';
								}else{
									startTime = startTime.join(':')+' PM';
								}
								break;								
							case 's_worktime_min':
								startTime[1] = $this.val().length < 2 ? '0'+$this.val() : $this.val(); 
								break;
							case 's_worktime_hour':			
								startTime[0] =  $this.val().length < 2 ? '0'+$this.val() : $this.val();
								break;
							case 'e_worktime_min':
								endTime[1] =  $this.val().length < 2 ? '0'+$this.val() : $this.val();
								break;
							case 'e_worktime_hour':			
								endTime[0] =  $this.val().length < 2 ? '0'+$this.val() : $this.val();
								break;
							case 's_overtime_min':
								startTime[1] =  $this.val().length < 2 ? '0'+$this.val() : $this.val();
								break;
							case 's_overtime_hour':			
								startTime[0] =  $this.val().length < 2 ? '0'+$this.val() : $this.val();
								break;
							case 'e_overtime_min':
								endTime[1] =  $this.val().length < 2 ? '0'+$this.val() : $this.val();
								break;
							case 'e_overtime_hour':			
								endTime[0] =  $this.val().length < 2 ? '0'+$this.val() : $this.val();
								break;								
						}
					});					
				}else{
					startTime.join('');endTime.join('');
					$('.flobn-range-timelbl',this.config.menu).html('<span>'+flobn.lang.get('Time')+': </span>'+flobn.lang.get('Show All'));
				}
				this.select({startTime: startTime,
							 endTime: endTime,
							 timeType: type.val()});
			},this));
			this.rendered.time = !this.rendered.time;			
			
		},		
		toggleShortcut: function(e){
			this.config.menu.find('> ul.first-of-type  > li.first-of-type').toggleClass('collapsed');
			this.config.menu.find('.flobn-range-shortcut').toggle('slide');			
			this.shown.shortcut = !this.shown.shortcut;			
		},
		toggleSpecific: function(e){
			this.config.menu.find('> ul.first-of-type  > li.first-of-type').toggleClass('collapsed');
			this.config.menu.find('.flobn-range-specific').toggle('slide');
			$('#flobn-specific-cal').data("datepicker") || $('#flobn-specific-cal').datepicker({
				 onSelect: $.proxy(this.select,this),
				defaultDate: flobn.get('FREEZE_TIME_NOW') ? flobn.get('FREEZE_TIME') : new Date()			 
			});
			this.shown.specific = !this.shown.specific;
		},
		toggleRange: function(e){
			this.config.menu.find('> ul.first-of-type  > li.first-of-type').toggleClass('collapsed');
			this.config.menu.find('.flobn-range-range').toggle('slide');
			$('#flobn-start-cal').data("datepicker") || $('#flobn-start-cal').datepicker({
				defaultDate: flobn.get('FREEZE_TIME_NOW') ? flobn.get('FREEZE_TIME') : new Date()});
			$('#flobn-end-cal').data("datepicker") || $('#flobn-end-cal').datepicker({
				defaultDate: flobn.get('FREEZE_TIME_NOW') ? flobn.get('FREEZE_TIME') : new Date()});				
			this.shown.range = !this.shown.range;			
		},
		toggleTime: function(e){
			this.config.menu.find('> ul.first-of-type  > li.first-of-type').toggleClass('collapsed');
			this.config.menu.find('.flobn-range-time').toggle('slide');
			this.shown.time = !this.shown.time;
		},	

		capitaliseFirstLetter: function(string){		
		    return string.charAt(0).toUpperCase() + string.slice(1);
		},
		select: function(args){
			//we need to know what we are dealing with
			var value = [];
			var selText = this.selectButton.find('button').html();
			var startDate,startTime,endDate,endTime;
			if(this.timeType != 1){
				//we have time as well as a date
				selText = selText.split(' - ');
				if(selText.length == 1){
					//just one date with a time
					selText[0] = selText[0].split(' ');
					if(selText[0].length > 1){ selText[1] += selText[2]; startTime = selText[0][1]; }
					startDate = selText[0][0];
				}else{
					selText[0] = selText[0].split(' ');
					if(selText[0].length > 1){ selText[0][1] += ' ' + selText[0][2]; startTime = selText[0][1]; };
					startDate = selText[0][0];
					selText[1] = selText[1].split(' ');
					if(selText[1].length > 1){ selText[1][1] += ' ' + selText[1][2]; endTime = selText[1][1]; };
					endDate = selText[1][0];
				}
			}			
			
			if(this.shown.shortcut){
				//we need to know which one was selected					
				var sel = $(args.target).html();
				this.current = sel;
				$('.flobn-range-shortcutlbl',this.config.menu).html('<span>'+flobn.lang.get('Current')+': </span>'+sel);
				var preset = this.presetRanges[$(args.target).data('selection')];
				if($.isFunction(preset.dateStart)){
					startDate = preset.dateStart();
				}else{
					startDate = Date.parse(preset.dateStart);
				}
				if($.isFunction(preset.dateEnd)){
					endDate = preset.dateEnd();
				}else{
					endDate = Date.parse(preset.dateEnd);	
				}
				
				var inputDateAtemp = fDate(startDate);
				var inputDateBtemp = fDate(endDate);
				
				if(inputDateAtemp === inputDateBtemp){
					startDate = inputDateAtemp;
					endDate = null;
				}else{
					startDate = inputDateAtemp;
					endDate = inputDateBtemp;
				}				
				//need to also set the value of current
				args.preventDefault();				
			}
			if(this.shown.specific){
				startDate = args;
				endDate = null;
			}
			if(this.shown.range){
				var selStart = $('#flobn-start-cal',this.config.menu).datepicker('getDate');
				var selEnd = $('#flobn-end-cal',this.config.menu).datepicker('getDate');
				startDate = fDate(selStart);
				endDate = fDate(selEnd);
			}
			if(this.shown.time){
				if(args.timeType == 1){
					startTime = null;
					endTime = null;
				}else{
					startTime = args.startTime;
					endTime = args.endTime;
				}
			}
			var evtObj = {
				startDate: startDate,
				startTime: startTime,
				endDate: endDate,
				endTime: endTime,
				timeType: this.timeType,
				current: this.current
			};
			if(startDate){
				value.push(startDate);
			}
			if(startTime){
				value.push(' ');
				value.push(startTime);
			}
			if(endDate){
				value.push(' - ');
				value.push(endDate);				
			}else if(endTime){
				value.push(' - ');
				value.push(startDate);
			}
			
			if(endTime){
				value.push(' ');
				value.push(endTime);
			}			
			this.selectButton.find('button').html(value.join(''));
			this.element.val(value.join('')).attr('rel',this.timeType).data('current',this.current).trigger('select',[evtObj]);
			this.hide();
		},
		activate: function(e){
			if(this.selectButton.hasClass(this.config.orgClass+'-active')){
				this.hide();
			}else{
				this.show();//show :D
			}
			e.preventDefault;
			e.stopPropagation();
		},
		show: function(){
			//close other and mark this one as being open
			flobn.widgets.SelectManager.closeOthers();
			flobn.widgets.SelectManager.currentOpen = this.id;
			this.selectButton.addClass(this.config.orgClass+'-active');
			this.config.menu.show();
			this.element.trigger('show');	
		},
		hide: function(){
			if(this.ignoreHide){
				return;	
			}
			!this.shown.shortcut || this.toggleShortcut(true); //daca e deschis il inchidem >:)
			!this.shown.specific || this.toggleSpecific(true); //daca e deschis il inchidem >:)
			!this.shown.range || this.toggleRange(true);
			!this.shown.time || this.toggleTime(true);
			this.selectButton.removeClass(this.config.orgClass+'-active');
			this.config.menu.hide();
			this.element.trigger('hide');
		},
		resetTime: function(timeType){
			var value = [];
			var selText = this.selectButton.find('button').html();
			var startDate,startTime,endDate,endTime;
			//we have time as well as a date
			selText = selText.split(' - ');
			if(selText.length == 1){
				//just one date with a time
				selText[0] = selText[0].split(' ');
				if(selText[0].length > 1){ selText[1] += selText[2]; startTime = selText[0][1]; }
				startDate = selText[0][0];
			}else{
				selText[0] = selText[0].split(' ');
				if(selText[0].length > 1){ selText[0][1] += ' ' + selText[0][2]; startTime = selText[0][1]; };
				startDate = selText[0][0];
				selText[1] = selText[1].split(' ');
				if(selText[1].length > 1){ selText[1][1] += ' ' + selText[1][2]; endTime = selText[1][1]; };
				endDate = selText[1][0];
			}
			if(startDate){
				value.push(startDate);
			}
			if(endDate){
				value.push(' - ');
				value.push(endDate);				
			}
			this.timeType = timeType;
			this.selectButton.find('button').html(value.join(''));
			this.element.val(value.join('')).attr('rel',this.timeType);
		}
	});
	
	$.fn.flobn_timeselect = function(opts){
		return this.each(function(){
			new flobn.widgets.TimeSelect(this,opts);
		});	
	};
	function fDate(date){
	   if(!date.getDate()){return '';}
	   var day = date.getDate();
	   var month = date.getMonth();
	   var year = date.getFullYear();
	   month++; // adjust javascript month
	   return jQuery.datepicker.formatDate( 'm/d/yy', date ); 
	};
	
})(jQuery);