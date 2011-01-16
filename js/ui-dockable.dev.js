/**
 * Dockable.
 **/
(function($){
	$.widget("db.dockable", $.ui.mouse, {
		options: {
			handle: false,
			axis: 'y',
			resize: function() {},
			resized: function() {}
		},
		_create: function() {
			if ( this.options.axis == 'x' ) {
				this.page = 'pageX';
				this.dimension = 'width';
			} else {
				this.page = 'pageY';
				this.dimension = 'height';
			}

			if ( ! this.options.handle )
				return;

			this.handle = $( this.options.handle );

			this._mouseInit();
		},
		widget: function() {
			return {
				element: this.element,
				handle: this.handle,
				axis: this.options.axis
			};
		},
		_mouseStart: function(event) {
			this._trigger( "start", event, this.widget() );
			this.d0 = this.element[this.dimension]() + event[this.page];
		},
		_mouseDrag: function(event) {
			var resize = this._trigger( "resize", event, this.widget() );

			// If the resize event returns false, we don't resize.
			if ( resize === false )
				return;

			this.element[this.dimension]( this.d0 - event[this.page] );
			this._trigger( "resized", event, this.widget() );
		},
		_mouseCapture: function(event) {
			return !this.options.disabled && event.target == this.handle[0];
		},
		_mouseStop: function(event) {
			this._trigger( "stop", event, this.widget() );
		}
	});
})(jQuery);