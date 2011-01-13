(function($) {

$(document).ready( function(){
	var adminBarLink = $('#wp-admin-bar-debug-bar'),
		queryList = $('#querylist'),
		debugMenuLinks = $('.debug-menu-link'),
		debugMenuTargets = $('.debug-menu-target');
	
	adminBarLink.click( function(e){
		queryList.toggle();
		e.preventDefault();
	});
	
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
});

})(jQuery);