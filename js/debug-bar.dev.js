(function($) {

var debugBar, bounds, wpDebugBar, $win, $body;

bounds = {
	adminBarHeight: 0,
	minHeight: 0,
	marginBottom: 0,

	inUpper: function(){
		return debugBar.offset().top - $win.scrollTop() >= bounds.adminBarHeight;
	},
	inLower: function(){
		return debugBar.outerHeight() >= bounds.minHeight
			&& $win.height() >= bounds.minHeight;
	},
	update: function( to ){
		if ( typeof to == "number" )
			debugBar.height( to );
		if ( ! bounds.inUpper() || to == 'upper' )
			debugBar.height( $win.height() - bounds.adminBarHeight );
		if ( ! bounds.inLower() || to == 'lower' )
			debugBar.height( bounds.minHeight );
		$body.css( 'margin-bottom', debugBar.height() + bounds.marginBottom );
	},
	restore: function(){
		$body.css( 'margin-bottom', bounds.marginBottom );
	}
};

wpDebugBar = {
	init: function(){
		// Initialize variables.
		debugBar = $('#querylist');
		$win = $(window);
		$body = $(document.body);

		bounds.minHeight = $('#debug-bar-handle').outerHeight() + $('#debug-bar-menu').outerHeight();
		bounds.adminBarHeight = $('#wpadminbar').outerHeight();
		bounds.marginBottom = parseInt( $body.css('margin-bottom'), 10 );

		wpDebugBar.dock();
		wpDebugBar.toggle();
		wpDebugBar.tabs();
		wpDebugBar.actions();
	},

	dock: function(){
		debugBar.dockable({
			handle: '#debug-bar-handle',
			resize: function( e, ui ) {
				return bounds.inUpper() && bounds.inLower();
			},
			resized: function( e, ui ) {
				bounds.update();
			}
		});

		// If the window is resized, make sure the debug bar isn't too large.
		$win.resize( function(){
			if ( debugBar.is(':visible') )
				bounds.update();
		});
	},

	toggle: function(){
		$('#wp-admin-bar-debug-bar').click( function(e){
			var show = debugBar.is(':hidden');
			e.preventDefault();

			debugBar.toggle( show );
			$(this).toggleClass( 'active', show );

			if ( show )
				bounds.update();
			else
				bounds.restore();
		});
	},

	tabs: function(){
		var debugMenuLinks = $('.debug-menu-link'),
			debugMenuTargets = $('.debug-menu-target');

		debugMenuLinks.click( function(e){
			var t = $(this);

			e.preventDefault();

			if ( t.hasClass('current') )
				return;

			// Deselect other tabs and hide other panels.
			debugMenuTargets.hide();
			debugMenuLinks.removeClass('current');

			// Select the current tab and show the current panel.
			t.addClass('current');
			// The hashed component of the href is the id that we want to display.
			$('#' + this.href.substr( this.href.indexOf( '#' ) + 1 ) ).show();
		});
	},

	actions: function(){
		var actions = $('#debug-bar-actions'),
			maximize = $('.plus', actions),
			restore = $('.minus', actions),
			lastHeight = debugBar.height();

		// @todo: Make this toggle maximize, remove scrollbars, etc.
		maximize.click( function(){
			lastHeight = debugBar.height();
			bounds.update( 'upper' );
			maximize.hide();
			restore.show();
		});
		restore.click( function(){
			bounds.update( lastHeight );
			restore.hide();
			maximize.show();
		})
	}
};

$(document).ready( wpDebugBar.init );

})(jQuery);