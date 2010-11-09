var toggle_query_list, clickDebugLink;

toggle_query_list = function() { 
	var querylist = document.getElementById( 'querylist' );
	if( querylist && querylist.style.display == 'block' ) {
		querylist.style.display='none';
	} else {
		querylist.style.display='block';
	}
}

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