(function() {

	var event = jQuery.event,

		//helper that finds handlers by type and calls back a function, this is basically handle
		findHelper = function( events, types, callback ) {
			var t, type, typeHandlers, all, h, handle, namespaces, namespace;
			for ( t = 0; t < types.length; t++ ) {
				type = types[t];
				all = type.indexOf(".") < 0;
				if (!all ) {
					namespaces = type.split(".");
					type = namespaces.shift();
					namespace = new RegExp("(^|\\.)" + namespaces.slice(0).sort().join("\\.(?:.*\\.)?") + "(\\.|$)");
				}
				typeHandlers = (events[type] || []).slice(0);

				for ( h = 0; h < typeHandlers.length; h++ ) {
					handle = typeHandlers[h];
					if (!handle.selector && (all || namespace.test(handle.namespace)) ) {
						callback(type, handle.origHandler || handle.handler);
					}
				}
			}
		};

	/**
	 * Finds event handlers of a given type on an element.
	 * @param {HTMLElement} el
	 * @param {Array} types an array of event names
	 * @param {String} [selector] optional selector
	 * @return {Array} an array of event handlers
	 */
	event.find = function( el, types, selector ) {
		var events = $.data(el, "events"),
			handlers = [],
			t, liver, live;

		if (!events ) {
			return handlers;
		}

		if ( selector ) {
			if (!events.live ) {
				return [];
			}
			live = events.live;

			for ( t = 0; t < live.length; t++ ) {
				liver = live[t];
				if ( liver.selector === selector && $.inArray(liver.origType, types) !== -1 ) {
					handlers.push(liver.origHandler || liver.handler);
				}
			}
		} else {
			// basically re-create handler's logic
			findHelper(events, types, function( type, handler ) {
				handlers.push(handler);
			});
		}
		return handlers;
	};
	/**
	 * Finds 
	 * @param {HTMLElement} el
	 * @param {Array} types
	 */
	event.findBySelector = function( el, types ) {
		var events = $.data(el, "events"),
			selectors = {},
			//adds a handler for a given selector and event
			add = function( selector, event, handler ) {
				var select = selectors[selector] || (selectors[selector] = {}),
					events = select[event] || (select[event] = []);
				events.push(handler);
			};

		if (!events ) {
			return selectors;
		}
		//first check live:
		$.each(events.live || [], function( i, live ) {
			if ( $.inArray(live.origType, types) !== -1 ) {
				add(live.selector, live.origType, live.origHandler || live.handler);
			}
		});
		//then check straight binds
		findHelper(events, types, function( type, handler ) {
			add("", type, handler);
		});

		return selectors;
	};
	$.fn.respondsTo = function( events ) {
		if (!this.length ) {
			return false;
		} else {
			//add default ?
			return event.find(this[0], $.isArray(events) ? events : [events]).length > 0;
		}
	};
	$.fn.triggerHandled = function( event, data ) {
		event = (typeof event == "string" ? $.Event(event) : event);
		this.trigger(event, data);
		return event.handled;
	};
	/**
	 * Only attaches one event handler for all types ...
	 * @param {Array} types llist of types that will delegate here
	 * @param {Object} startingEvent the first event to start listening to
	 * @param {Object} onFirst a function to call 
	 */
	event.setupHelper = function( types, startingEvent, onFirst ) {
		if (!onFirst ) {
			onFirst = startingEvent;
			startingEvent = null;
		}
		var add = function( handleObj ) {

			var bySelector, selector = handleObj.selector || "";
			if ( selector ) {
				bySelector = event.find(this, types, selector);
				if (!bySelector.length ) {
					$(this).delegate(selector, startingEvent, onFirst);
				}
			}
			else {
				//var bySelector = event.find(this, types, selector);
				if (!event.find(this, types, selector).length ) {
					event.add(this, startingEvent, onFirst, {
						selector: selector,
						delegate: this
					});
				}

			}

		},
			remove = function( handleObj ) {
				var bySelector, selector = handleObj.selector || "";
				if ( selector ) {
					bySelector = event.find(this, types, selector);
					if (!bySelector.length ) {
						$(this).undelegate(selector, startingEvent, onFirst);
					}
				}
				else {
					if (!event.find(this, types, selector).length ) {
						event.remove(this, startingEvent, onFirst, {
							selector: selector,
							delegate: this
						});
					}
				}
			};
		$.each(types, function() {
			event.special[this] = {
				add: add,
				remove: remove,
				setup: function() {},
				teardown: function() {}
			};
		});
	};
})(jQuery);
(function( $ ) {

	var getComputedStyle = document.defaultView && document.defaultView.getComputedStyle,
		rupper = /([A-Z])/g,
		rdashAlpha = /-([a-z])/ig,
		fcamelCase = function( all, letter ) {
			return letter.toUpperCase();
		},
		getStyle = function( elem ) {
			if ( getComputedStyle ) {
				return getComputedStyle(elem, null);
			}
			else if ( elem.currentStyle ) {
				return elem.currentStyle;
			}
		},
		rfloat = /float/i,
		rnumpx = /^-?\d+(?:px)?$/i,
		rnum = /^-?\d/;
	/**
	 * @add jQuery
	 */
	//
	/**
	 * @function curStyles
	 * @param {HTMLElement} el
	 * @param {Array} styles An array of style names like <code>['marginTop','borderLeft']</code>
	 * @return {Object} an object of style:value pairs.  Style names are camelCase.
	 */
	$.curStyles = function( el, styles ) {
		if (!el ) {
			return null;
		}
		var currentS = getStyle(el),
			oldName, val, style = el.style,
			results = {},
			i = 0,
			left, rsLeft, camelCase, name;

		for (; i < styles.length; i++ ) {
			name = styles[i];
			oldName = name.replace(rdashAlpha, fcamelCase);

			if ( rfloat.test(name) ) {
				name = jQuery.support.cssFloat ? "float" : "styleFloat";
				oldName = "cssFloat";
			}

			if ( getComputedStyle ) {
				name = name.replace(rupper, "-$1").toLowerCase();
				val = currentS.getPropertyValue(name);
				if ( name === "opacity" && val === "" ) {
					val = "1";
				}
				results[oldName] = val;
			} else {
				camelCase = name.replace(rdashAlpha, fcamelCase);
				results[oldName] = currentS[name] || currentS[camelCase];


				if (!rnumpx.test(results[oldName]) && rnum.test(results[oldName]) ) { //convert to px
					// Remember the original values
					left = style.left;
					rsLeft = el.runtimeStyle.left;

					// Put in the new values to get a computed value out
					el.runtimeStyle.left = el.currentStyle.left;
					style.left = camelCase === "fontSize" ? "1em" : (results[oldName] || 0);
					results[oldName] = style.pixelLeft + "px";

					// Revert the changed values
					style.left = left;
					el.runtimeStyle.left = rsLeft;
				}

			}
		}

		return results;
	};
	/**
	 *  @add jQuery.fn
	 */


	$.fn
	/**
	 * @parent dom
	 * @plugin jquery/dom/cur_styles
	 * @download http://jmvcsite.heroku.com/pluginify?plugins[]=jquery/dom/cur_styles/cur_styles.js
	 * @test jquery/dom/cur_styles/qunit.html
	 * Use curStyles to rapidly get a bunch of computed styles from an element.
	 * <h3>Quick Example</h3>
	 * @codestart
	 * $("#foo").curStyles('float','display') //->
	 * // {
	 * //  cssFloat: "left", display: "block"
	 * // }
	 * @codeend
	 * <h2>Use</h2>
	 * <p>An element's <b>computed</b> style is the current calculated style of the property.
	 * This is different than the values on <code>element.style</code> as
	 * <code>element.style</code> doesn't reflect styles provided by css or the browser's default
	 * css properties.</p>
	 * <p>Getting computed values individually is expensive! This plugin lets you get all
	 * the style properties you need all at once.</p>
	 * <h2>Demo</h2>
	 * <p>The following demo illustrates the performance improvement curStyle provides by providing
	 * a faster 'height' jQuery function called 'fastHeight'.</p>
	 * @demo jquery/dom/cur_styles/cur_styles.html
	 * @param {String} style pass style names as arguments
	 * @return {Object} an object of style:value pairs
	 */
	.curStyles = function() {
		return $.curStyles(this[0], $.makeArray(arguments));
	};
})(jQuery);
(function( $ ) {
	//modify live
	//steal the live handler ....
	var bind = function( object, method ) {
		var args = Array.prototype.slice.call(arguments, 2);
		return function() {
			var args2 = [this].concat(args, $.makeArray(arguments));
			return method.apply(object, args2);
		};
	},
		event = $.event;
	// var handle = event.handle; //unused
	/**
	 * @class jQuery.Drag
	 * @parent specialevents
	 * @plugin jquery/event/drag
	 * @download  http://jmvcsite.heroku.com/pluginify?plugins[]=jquery/event/drag/drag.js
	 * @test jquery/event/drag/qunit.html
	 * Provides drag events as a special events to jQuery.  
	 * A jQuery.Drag instance is created on a drag and passed
	 * as a parameter to the drag event callbacks.  By calling
	 * methods on the drag event, you can alter the drag's
	 * behavior.
	 * <h2>Drag Events</h2>
	 * The drag plugin allows you to listen to the following events:
	 * <ul>
	 *  <li><code>dragdown</code> - the mouse cursor is pressed down</li>
	 *  <li><code>draginit</code> - the drag motion is started</li>
	 *  <li><code>dragmove</code> - the drag is moved</li>
	 *  <li><code>dragend</code> - the drag has ended</li>
	 *  <li><code>dragover</code> - the drag is over a drop point</li>
	 *  <li><code>dragout</code> - the drag moved out of a drop point</li>
	 * </ul>
	 * <p>Just by binding or delegating on one of these events, you make
	 * the element dragable.  You can change the behavior of the drag
	 * by calling methods on the drag object passed to the callback.
	 * <h3>Example</h3>
	 * Here's a quick example:
	 * @codestart
	 * //makes the drag vertical
	 * $(".drags").live("draginit", function(event, drag){
	 *   drag.vertical();
	 * })
	 * //gets the position of the drag and uses that to set the width
	 * //of an element
	 * $(".resize").live("dragmove",function(event, drag){
	 *   $(this).width(drag.position.left() - $(this).offset().left   )
	 * })
	 * @codeend
	 * <h2>Drag Object</h2>
	 * <p>The drag object is passed after the event to drag 
	 * event callback functions.  By calling methods
	 * and changing the properties of the drag object,
	 * you can alter how the drag behaves.
	 * </p>
	 * <p>The drag properties and methods:</p>
	 * <ul>
	 *  <li><code>[jQuery.Drag.prototype.cancel cancel]</code> - stops the drag motion from happening</li>
	 *  <li><code>[jQuery.Drag.prototype.ghost ghost]</code> - copys the draggable and drags the cloned element</li>
	 *  <li><code>[jQuery.Drag.prototype.horizontal horizontal]</code> - limits the scroll to horizontal movement</li>
	 *  <li><code>[jQuery.Drag.prototype.location location]</code> - where the drag should be on the screen</li>
	 *  <li><code>[jQuery.Drag.prototype.mouseElementPosition mouseElementPosition]</code> - where the mouse should be on the drag</li>
	 *  <li><code>[jQuery.Drag.prototype.only only]</code> - only have drags, no drops</li>
	 *  <li><code>[jQuery.Drag.prototype.representative representative]</code> - move another element in place of this element</li>
	 *  <li><code>[jQuery.Drag.prototype.revert revert]</code> - animate the drag back to its position</li>
	 *  <li><code>[jQuery.Drag.prototype.vertical vertical]</code> - limit the drag to vertical movement</li>
	 *  <li><code>[jQuery.Drag.prototype.limit limit]</code> - limit the drag within an element (*limit plugin)</li>
	 *  <li><code>[jQuery.Drag.prototype.scrolls scrolls]</code> - scroll scrollable areas when dragging near their boundries (*scroll plugin)</li>
	 * </ul>
	 * <h2>Demo</h2>
	 * Now lets see some examples:
	 * @demo jquery/event/drag/drag.html 1000
	 * @constructor
	 * The constructor is never called directly.
	 */
	$.Drag = function() {};

	/**
	 * @Static
	 */
	$.extend($.Drag, {
		lowerName: "drag",
		current: null,
		/**
		 * Called when someone mouses down on a draggable object.
		 * Gathers all callback functions and creates a new Draggable.
		 * @hide
		 */
		mousedown: function( ev, element ) {
			var isLeftButton = ev.button === 0 || ev.button == 1;
			if (!isLeftButton || this.current ) {
				return;
			} //only allows 1 drag at a time, but in future could allow more
			//ev.preventDefault();
			//create Drag
			var drag = new $.Drag(),
				delegate = ev.liveFired || element,
				selector = ev.handleObj.selector,
				self = this;
			this.current = drag;

			drag.setup({
				element: element,
				delegate: ev.liveFired || element,
				selector: ev.handleObj.selector,
				moved: false,
				callbacks: {
					dragdown: event.find(delegate, ["dragdown"], selector),
					draginit: event.find(delegate, ["draginit"], selector),
					dragover: event.find(delegate, ["dragover"], selector),
					dragmove: event.find(delegate, ["dragmove"], selector),
					dragout: event.find(delegate, ["dragout"], selector),
					dragend: event.find(delegate, ["dragend"], selector)
				},
				destroyed: function() {
					self.current = null;
				}
			}, ev);
		}
	});





	/**
	 * @Prototype
	 */
	$.extend($.Drag.prototype, {
		setup: function( options, ev ) {
			//this.noSelection();
			$.extend(this, options);
			this.element = $(this.element);
			this.event = ev;
			this.moved = false;
			this.allowOtherDrags = false;
			var mousemove = bind(this, this.mousemove),
				mouseup = bind(this, this.mouseup);
			this._mousemove = mousemove;
			this._mouseup = mouseup;
			$(document).bind('mousemove', mousemove);
			$(document).bind('mouseup', mouseup);

			if (!this.callEvents('down', this.element, ev) ) {
				ev.preventDefault();
			}
		},
		/**
		 * Unbinds listeners and allows other drags ...
		 * @hide
		 */
		destroy: function() {
			$(document).unbind('mousemove', this._mousemove);
			$(document).unbind('mouseup', this._mouseup);
			if (!this.moved ) {
				this.event = this.element = null;
			}
			//this.selection();
			this.destroyed();
		},
		mousemove: function( docEl, ev ) {
			if (!this.moved ) {
				this.init(this.element, ev);
				this.moved = true;
			}

			var pointer = ev.vector();
			if ( this._start_position && this._start_position.equals(pointer) ) {
				return;
			}
			//e.preventDefault();
			this.draw(pointer, ev);
		},
		mouseup: function( docEl, event ) {
			//if there is a current, we should call its dragstop
			if ( this.moved ) {
				this.end(event);
			}
			this.destroy();
		},
		noSelection: function() {
			document.documentElement.onselectstart = function() {
				return false;
			};
			document.documentElement.unselectable = "on";
			$(document.documentElement).css('-moz-user-select', 'none');
		},
		selection: function() {
			document.documentElement.onselectstart = function() {};
			document.documentElement.unselectable = "off";
			$(document.documentElement).css('-moz-user-select', '');
		},
		init: function( element, event ) {
			element = $(element);
			var startElement = (this.movingElement = (this.element = $(element))); //the element that has been clicked on
			//if a mousemove has come after the click
			this._cancelled = false; //if the drag has been cancelled
			this.event = event;
			this.mouseStartPosition = event.vector(); //where the mouse is located
			/**
			 * @attribute mouseElementPosition
			 * The position of start of the cursor on the element
			 */
			this.mouseElementPosition = this.mouseStartPosition.minus(this.element.offsetv()); //where the mouse is on the Element
			//this.callStart(element, event);
			this.callEvents('init', element, event);

			//Check what they have set and respond accordingly
			//  if they canceled
			if ( this._cancelled === true ) {
				return;
			}
			//if they set something else as the element
			this.startPosition = startElement != this.movingElement ? this.movingElement.offsetv() : this.currentDelta();

			this.makePositioned(this.movingElement);
			this.oldZIndex = this.movingElement.css('zIndex');
			this.movingElement.css('zIndex', 1000);
			if (!this._only && this.constructor.responder ) {
				this.constructor.responder.compile(event, this);
			}
		},
		makePositioned: function( that ) {
			var style, pos = that.css('position');

			if (!pos || pos == 'static' ) {
				style = {
					position: 'relative'
				};

				if ( window.opera ) {
					style.top = '0px';
					style.left = '0px';
				}
				that.css(style);
			}
		},
		callEvents: function( type, element, event, drop ) {
			var i, cbs = this.callbacks[this.constructor.lowerName + type];
			for ( i = 0; i < cbs.length; i++ ) {
				cbs[i].call(element, event, this, drop);
			}
			return cbs.length;
		},
		/**
		 * Returns the position of the movingElement by taking its top and left.
		 * @hide
		 * @return {Vector}
		 */
		currentDelta: function() {
			return new $.Vector(parseInt(this.movingElement.css('left'), 10) || 0, parseInt(this.movingElement.css('top'), 10) || 0);
		},
		//draws the position of the dragmove object
		draw: function( pointer, event ) {
			// only drag if we haven't been cancelled;
			if ( this._cancelled ) {
				return;
			}
			/**
			 * @attribute location
			 * The location of where the element should be in the page.  This 
			 * takes into account the start position of the cursor on the element.
			 */
			this.location = pointer.minus(this.mouseElementPosition); // the offset between the mouse pointer and the representative that the user asked for
			// position = mouse - (dragOffset - dragTopLeft) - mousePosition
			this.move(event);
			if ( this._cancelled ) {
				return;
			}
			if (!event.isDefaultPrevented() ) {
				this.position(this.location);
			}

			//fill in
			if (!this._only && this.constructor.responder ) {
				this.constructor.responder.show(pointer, this, event);
			}
		},
		/**
		 * Sets the position of this drag.  
		 * 
		 * The limit and scroll plugins
		 * overwrite this to make sure the drag follows a particular path.
		 * 
		 * @param {jQuery.Vector} newOffsetv the position of the element (not the mouse)
		 */
		position: function( newOffsetv ) { //should draw it on the page
			var style, dragged_element_css_offset = this.currentDelta(),
				//  the drag element's current left + top css attributes
				dragged_element_position_vector = // the vector between the movingElement's page and css positions
				this.movingElement.offsetv().minus(dragged_element_css_offset); // this can be thought of as the original offset
			this.required_css_position = newOffsetv.minus(dragged_element_position_vector);

			this.offsetv = newOffsetv;
			//dragged_element vector can probably be cached.
			style = this.movingElement[0].style;
			if (!this._cancelled && !this._horizontal ) {
				style.top = this.required_css_position.top() + "px";
			}
			if (!this._cancelled && !this._vertical ) {
				style.left = this.required_css_position.left() + "px";
			}
		},
		move: function( event ) {
			this.callEvents('move', this.element, event);
		},
		over: function( event, drop ) {
			this.callEvents('over', this.element, event, drop);
		},
		out: function( event, drop ) {
			this.callEvents('out', this.element, event, drop);
		},
		/**
		 * Called on drag up
		 * @hide
		 * @param {Event} event a mouseup event signalling drag/drop has completed
		 */
		end: function( event ) {
			if ( this._cancelled ) {
				return;
			}
			if (!this._only && this.constructor.responder ) {
				this.constructor.responder.end(event, this);
			}

			this.callEvents('end', this.element, event);

			if ( this._revert ) {
				var self = this;
				this.movingElement.animate({
					top: this.startPosition.top() + "px",
					left: this.startPosition.left() + "px"
				}, function() {
					self.cleanup.apply(self, arguments);
				});
			}
			else {
				this.cleanup();
			}
			this.event = null;
		},
		/**
		 * Cleans up drag element after drag drop.
		 * @hide
		 */
		cleanup: function() {
			this.movingElement.css({
				zIndex: this.oldZIndex
			});
			if ( this.movingElement[0] !== this.element[0] ) {
				this.movingElement.css({
					display: 'none'
				});
			}
			if ( this._removeMovingElement ) {
				this.movingElement.remove();
			}

			this.movingElement = this.element = this.event = null;
		},
		/**
		 * Stops drag drop from running.
		 */
		cancel: function() {
			this._cancelled = true;
			//this.end(this.event);
			if (!this._only && this.constructor.responder ) {
				this.constructor.responder.clear(this.event.vector(), this, this.event);
			}
			this.destroy();

		},
		/**
		 * Clones the element and uses it as the moving element.
		 * @return {jQuery.fn} the ghost
		 */
		ghost: function( loc ) {
			// create a ghost by cloning the source element and attach the clone to the dom after the source element
			var ghost = this.movingElement.clone().css('position', 'absolute');
			(loc ? $(loc) : this.movingElement).after(ghost);
			ghost.width(this.movingElement.width()).height(this.movingElement.height());

			// store the original element and make the ghost the dragged element
			this.movingElement = ghost;
			this._removeMovingElement = true;
			return ghost;
		},
		/**
		 * Use a representative element, instead of the movingElement.
		 * @param {HTMLElement} element the element you want to actually drag
		 * @param {Number} offsetX the x position where you want your mouse on the object
		 * @param {Number} offsetY the y position where you want your mouse on the object
		 */
		representative: function( element, offsetX, offsetY ) {
			this._offsetX = offsetX || 0;
			this._offsetY = offsetY || 0;

			var p = this.mouseStartPosition;

			this.movingElement = $(element);
			this.movingElement.css({
				top: (p.y() - this._offsetY) + "px",
				left: (p.x() - this._offsetX) + "px",
				display: 'block',
				position: 'absolute'
			}).show();

			this.mouseElementPosition = new $.Vector(this._offsetX, this._offsetY);
		},
		/**
		 * Makes the movingElement go back to its original position after drop.
		 * @codestart
		 * ".handle dragend" : function( el, ev, drag ) {
		 *    drag.revert()
		 * }
		 * @codeend
		 * @param {Boolean} [val] optional, set to false if you don't want to revert.
		 */
		revert: function( val ) {
			this._revert = val === undefined ? true : val;
		},
		/**
		 * Isolates the drag to vertical movement.
		 */
		vertical: function() {
			this._vertical = true;
		},
		/**
		 * Isolates the drag to horizontal movement.
		 */
		horizontal: function() {
			this._horizontal = true;
		},


		/**
		 * Respondables will not be alerted to this drag.
		 */
		only: function( only ) {
			return (this._only = (only === undefined ? true : only));
		}
	});

	/**
	 * @add jQuery.event.special
	 */
	event.setupHelper([
	/**
	 * @attribute dragdown
	 * <p>Listens for when a drag movement has started on a mousedown.
	 * If you listen to this, the mousedown's default event (preventing
	 * text selection) is not prevented.  You are responsible for calling it
	 * if you want it (you probably do).  </p>
	 * <p><b>Why might you not want it?</b></p>
	 * <p>You might want it if you want to allow text selection on element
	 * within the drag element.  Typically these are input elements.</p>
	 * <p>Drag events are covered in more detail in [jQuery.Drag].</p>
	 * @codestart
	 * $(".handles").live("dragdown", function(ev, drag){})
	 * @codeend
	 */
	'dragdown',
	/**
	 * @attribute draginit
	 * Called when the drag starts.
	 * <p>Drag events are covered in more detail in [jQuery.Drag].</p>
	 */
	'draginit',
	/**
	 * @attribute dragover
	 * Called when the drag is over a drop.
	 * <p>Drag events are covered in more detail in [jQuery.Drag].</p>
	 */
	'dragover',
	/**
	 * @attribute dragmove
	 * Called when the drag is moved.
	 * <p>Drag events are covered in more detail in [jQuery.Drag].</p>
	 */
	'dragmove',
	/**
	 * @attribute dragout
	 * When the drag leaves a drop point.
	 * <p>Drag events are covered in more detail in [jQuery.Drag].</p>
	 */
	'dragout',
	/**
	 * @attribute dragend
	 * Called when the drag is done.
	 * <p>Drag events are covered in more detail in [jQuery.Drag].</p>
	 */
	'dragend'], "mousedown", function( e ) {
		$.Drag.mousedown.call($.Drag, e, this);

	});
})(jQuery);
(function( $ ) {
	$.Drag.prototype
	/**
	 * @function limit
	 * @plugin jquery/event/drag/limit
	 * @download  http://jmvcsite.heroku.com/pluginify?plugins[]=jquery/event/event/drag/limit/limit.js
	 * limits the drag to a containing element
	 * @param {jQuery} container
	 * @param {Object} [center] can set the limit to the center of the object.  Can be 
	 *   'x', 'y' or 'both'
	 * @return {$.Drag}
	 */
	.limit = function( container, center ) {
		//on draws ... make sure this happens
		var styles = container.curStyles('borderTopWidth', 'paddingTop', 'borderLeftWidth', 'paddingLeft');
		
		var paddingBorder = new $.Vector(
			parseInt(styles.borderLeftWidth, 10) + parseInt(styles.paddingLeft, 10) || 0, parseInt(styles.borderTopWidth, 10) + parseInt(styles.paddingTop, 10) || 0);

		this._limit = {
			offset: container.offsetv().plus(paddingBorder),
			size: container.dimensionsv(),
			center : center
		};
		return this;
	};

	var oldPosition = $.Drag.prototype.position;
	$.Drag.prototype.position = function( offsetPositionv ) {
		//adjust required_css_position accordingly
		if ( this._limit ) {
			var limit = this._limit,
				center = limit.center && limit.center.toLowerCase(),
				movingSize = this.movingElement.dimensionsv('outer'),
				halfHeight = center && center != 'x' ? movingSize.height() / 2 : 0,
				halfWidth = center && center != 'y' ? movingSize.width() / 2 : 0,
				lot = limit.offset.top(),
				lof = limit.offset.left(),
				height = limit.size.height(),
				width = limit.size.width();

			//check if we are out of bounds ...
			//above
			if ( offsetPositionv.top()+halfHeight < lot ) {
				offsetPositionv.top(lot - halfHeight);
			}
			//below
			if ( offsetPositionv.top() + movingSize.height() - halfHeight > lot + height ) {
				offsetPositionv.top(lot + height - movingSize.height() + halfHeight);
			}
			//left
			if ( offsetPositionv.left()+halfWidth < lof ) {
				offsetPositionv.left(lof - halfWidth);
			}
			//right
			if ( offsetPositionv.left() + movingSize.width() -halfWidth > lof + width ) {
				offsetPositionv.left(lof + width - movingSize.left()+halfWidth);
			}
		}

		oldPosition.call(this, offsetPositionv);
	};
})(jQuery);
(function( $ ) {
	var round = function( x, m ) {
		return Math.round(x / m) * m;
	};

	$.Drag.prototype.
	/**
	 * @function step
	 * @plugin jquery/event/drag/step
	 * @download  http://jmvcsite.heroku.com/pluginify?plugins[]=jquery/event/drag/step/step.js
	 * makes the drag move in steps of amount pixels.
	 * @codestart
	 * drag.step({x: 5}, $('foo'))
	 * @codeend
	 * @param {number} amount
	 * @param {jQuery} container
	 * @return {jQuery.Drag} the drag
	 */
	step = function( amount, container, center ) {
		//on draws ... make sure this happens
		if ( typeof amount == 'number' ) {
			amount = {
				x: amount,
				y: amount
			}
		}
		container = container || $(document.body);
		this._step = amount;

		var styles = container.curStyles("borderTopWidth", "paddingTop", "borderLeftWidth", "paddingLeft");
		var top = parseInt(styles.borderTopWidth) + parseInt(styles.paddingTop),
			left = parseInt(styles.borderLeftWidth) + parseInt(styles.paddingLeft);

		this._step.offset = container.offsetv().plus(left, top);
		this._step.center = center;
		return this;
	};


	var oldPosition = $.Drag.prototype.position;
	$.Drag.prototype.position = function( offsetPositionv ) {
		//adjust required_css_position accordingly
		if ( this._step ) {
			var step = this._step,
				center = step.center && step.center.toLowerCase(),
				movingSize = this.movingElement.dimensionsv('outer'),
				lot = step.offset.top()- (center && center != 'x' ? movingSize.height() / 2 : 0),
				lof = step.offset.left() - (center && center != 'y' ? movingSize.width() / 2 : 0);

			if ( this._step.x ) {
				offsetPositionv.left(Math.round(lof + round(offsetPositionv.left() - lof, this._step.x)))
			}
			if ( this._step.y ) {
				offsetPositionv.top(Math.round(lot + round(offsetPositionv.top() - lot, this._step.y)))
			}
		}

		oldPosition.call(this, offsetPositionv)
	}

})(jQuery);
(function( $ ) {
	var round = function( x, m ) {
		return Math.round(x / m) * m;
	};

	$.Drag.prototype.
	/**
	 * @function step
	 * @plugin jquery/event/drag/step
	 * @download  http://jmvcsite.heroku.com/pluginify?plugins[]=jquery/event/drag/step/step.js
	 * makes the drag move in steps of amount pixels.
	 * @codestart
	 * drag.step({x: 5}, $('foo'))
	 * @codeend
	 * @param {number} amount
	 * @param {jQuery} container
	 * @return {jQuery.Drag} the drag
	 */
	step = function( amount, container, center ) {
		//on draws ... make sure this happens
		if ( typeof amount == 'number' ) {
			amount = {
				x: amount,
				y: amount
			}
		}
		container = container || $(document.body);
		this._step = amount;

		var styles = container.curStyles("borderTopWidth", "paddingTop", "borderLeftWidth", "paddingLeft");
		var top = parseInt(styles.borderTopWidth) + parseInt(styles.paddingTop),
			left = parseInt(styles.borderLeftWidth) + parseInt(styles.paddingLeft);

		this._step.offset = container.offsetv().plus(left, top);
		this._step.center = center;
		return this;
	};


	var oldPosition = $.Drag.prototype.position;
	$.Drag.prototype.position = function( offsetPositionv ) {
		//adjust required_css_position accordingly
		if ( this._step ) {
			var step = this._step,
				center = step.center && step.center.toLowerCase(),
				movingSize = this.movingElement.dimensionsv('outer'),
				lot = step.offset.top()- (center && center != 'x' ? movingSize.height() / 2 : 0),
				lof = step.offset.left() - (center && center != 'y' ? movingSize.width() / 2 : 0);

			if ( this._step.x ) {
				offsetPositionv.left(Math.round(lof + round(offsetPositionv.left() - lof, this._step.x)))
			}
			if ( this._step.y ) {
				offsetPositionv.top(Math.round(lot + round(offsetPositionv.top() - lot, this._step.y)))
			}
		}

		oldPosition.call(this, offsetPositionv)
	}

})(jQuery);
(function($){
	var getSetZero = function(v){ return v !== undefined ? (this.array[0] = v) : this.array[0] },
		getSetOne = function(v){ return v !== undefined ? (this.array[1] = v) : this.array[1] };
/**
 * @class jQuery.Vector
 * A vector class
 * @constructor creates a new vector instance from the arguments.  Example:
 * @codestart
 * new jQuery.Vector(1,2)
 * @codeend
 * 
 */
	$.Vector = function() {
		this.update($.makeArray(arguments));
	};
	$.Vector.prototype =
	/* @Prototype*/
	{
		/**
		 * Applys the function to every item in the vector.  Returns the new vector.
		 * @param {Function} f
		 * @return {jQuery.Vector} new vector class.
		 */
		app: function( f ) {
			var i, vec, newArr = [];

			for ( i = 0; i < this.array.length; i++ ) {
				newArr.push(f(this.array[i]));
			}
			vec = new $.Vector();
			return vec.update(newArr);
		},
		/**
		 * Adds two vectors together.  Example:
		 * @codestart
		 * new Vector(1,2).plus(2,3) //-> &lt;3,5>
		 * new Vector(3,5).plus(new Vector(4,5)) //-> &lt;7,10>
		 * @codeend
		 * @return {$.Vector}
		 */
		plus: function() {
			var i, args = arguments[0] instanceof $.Vector ? arguments[0].array : $.makeArray(arguments),
				arr = this.array.slice(0),
				vec = new $.Vector();
			for ( i = 0; i < args.length; i++ ) {
				arr[i] = (arr[i] ? arr[i] : 0) + args[i];
			}
			return vec.update(arr);
		},
		/**
		 * Like plus but subtracts 2 vectors
		 * @return {jQuery.Vector}
		 */
		minus: function() {
			var i, args = arguments[0] instanceof $.Vector ? arguments[0].array : $.makeArray(arguments),
				arr = this.array.slice(0),
				vec = new $.Vector();
			for ( i = 0; i < args.length; i++ ) {
				arr[i] = (arr[i] ? arr[i] : 0) - args[i];
			}
			return vec.update(arr);
		},
		/**
		 * Returns the current vector if it is equal to the vector passed in.  
		 * False if otherwise.
		 * @return {jQuery.Vector}
		 */
		equals: function() {
			var i, args = arguments[0] instanceof $.Vector ? arguments[0].array : $.makeArray(arguments),
				arr = this.array.slice(0),
				vec = new $.Vector();
			for ( i = 0; i < args.length; i++ ) {
				if ( arr[i] != args[i] ) {
					return null;
				}
			}
			return vec.update(arr);
		},
/*
	 * Returns the 2nd value of the vector
	 * @return {Number}
	 */
		x: getSetZero,
		width: getSetZero,
		/**
		 * Returns the first value of the vector
		 * @return {Number}
		 */
		y: getSetOne,
		height: getSetOne,
		/**
		 * Same as x()
		 * @return {Number}
		 */
		top: getSetOne,
		/**
		 * same as y()
		 * @return {Number}
		 */
		left: getSetZero,
		/**
		 * returns (x,y)
		 * @return {String}
		 */
		toString: function() {
			return "(" + this.array[0] + "," + this.array[1] + ")";
		},
		/**
		 * Replaces the vectors contents
		 * @param {Object} array
		 */
		update: function( array ) {
			var i;
			if ( this.array ) {
				for ( i = 0; i < this.array.length; i++ ) {
					delete this.array[i];
				}
			}
			this.array = array;
			for ( i = 0; i < array.length; i++ ) {
				this[i] = this.array[i];
			}
			return this;
		}
	};

	$.Event.prototype.vector = function() {
		if ( this.originalEvent.synthetic ) {
			var doc = document.documentElement,
				body = document.body;
			return new $.Vector(this.clientX + (doc && doc.scrollLeft || body && body.scrollLeft || 0) - (doc.clientLeft || 0), this.clientY + (doc && doc.scrollTop || body && body.scrollTop || 0) - (doc.clientTop || 0));
		} else {
			return new $.Vector(this.pageX, this.pageY);
		}
	};

	$.fn.offsetv = function() {
		if ( this[0] == window ) {
			return new $.Vector(window.pageXOffset ? window.pageXOffset : document.documentElement.scrollLeft, window.pageYOffset ? window.pageYOffset : document.documentElement.scrollTop);
		} else {
			var offset = this.offset();
			return new $.Vector(offset.left, offset.top);
		}
	};

	$.fn.dimensionsv = function( which ) {
		if ( this[0] == window || !which ) {
			return new $.Vector(this.width(), this.height());
		}
		else {
			return new $.Vector(this[which + "Width"](), this[which + "Height"]());
		}
	};
})(jQuery);