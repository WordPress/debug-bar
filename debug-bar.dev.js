(function(){

var addEvent, toggleQueryList, clickDebugLink;

addEvent = function( obj, type, fn ) {
	if (obj.addEventListener)
		obj.addEventListener(type, fn, false);
	else if (obj.attachEvent)
		obj.attachEvent('on' + type, function() { return fn.call(obj, window.event);});
};

toggleQueryList = function( e ) {
	var querylist = document.getElementById( 'querylist' );

	if( querylist && querylist.style.display == 'block' ) {
		querylist.style.display='none';
	} else {
		querylist.style.display='block';
	}

	// IE doesn't support preventDefault, and does support returnValue
	if ( e.preventDefault )
		e.preventDefault();
	e.returnValue = false;
};

clickDebugLink = function( targetsGroupId, obj) {
	var sectionDivs, i, j;
	sectionDivs = document.getElementById( targetsGroupId ).childNodes;
	for ( i = 0; i < sectionDivs.length; i++ ) {
		if ( 1 != sectionDivs[i].nodeType ) {
			continue;
		}
		sectionDivs[i].style.display = 'none';
	}
	document.getElementById( obj.href.substr( obj.href.indexOf( '#' ) + 1 ) ).style.display = 'block';

	for ( j = 0; j < obj.parentNode.parentNode.childNodes.length; j++ ) {
		if ( 1 != obj.parentNode.parentNode.childNodes[j].nodeType ) {
			continue;
		}
		obj.parentNode.parentNode.childNodes[j].removeAttribute( 'class' );
	}
	obj.parentNode.setAttribute( 'class', 'current' );
	return false;
};

addEvent(window, 'load', function() {
	var li = document.getElementById('wp-admin-bar-queries');
	addEvent( li, 'click', toggleQueryList );
});

})();