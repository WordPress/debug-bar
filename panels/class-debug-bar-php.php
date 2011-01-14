<?php

class Debug_Bar_PHP extends Debug_Bar_Panel {
	function init() {
		if ( ! WP_DEBUG )
			return false;
		
		$this->title( __('Notices / Warnings') );
		
		$GLOBALS['_debug_bar_real_error_handler'] = set_error_handler( array( &$this, 'error_handler' ) );
		$GLOBALS['_debug_bar_warnings'] = $GLOBALS['_debug_bar_notices'] = array();
	}

	function prerender() {
		global $_debug_bar_notices, $_debug_bar_warnings;
		$this->set_visible( count( $_debug_bar_notices ) || count( $_debug_bar_warnings ) );
	}

	function error_handler( $type, $message, $file, $line ) {
		global $_debug_bar_real_error_handler, $_debug_bar_notices, $_debug_bar_warnings;

		$_key = md5( $file . ':' . $line . ':' . $message );

		switch ( $type ) {
			case E_WARNING :
			case E_USER_WARNING :
				$_debug_bar_warnings[$_key] = array( $file.':'.$line, $message );
				break;
			case E_NOTICE :
			case E_USER_NOTICE :
				$_debug_bar_notices[$_key] = array( $file.':'.$line, $message );
				break;
			case E_STRICT :
				// TODO
				break;
			case E_DEPRECATED :
			case E_USER_DEPRECATED :
				// TODO
				break;
			case 0 :
				// TODO
				break;
		}

		if ( null != $_debug_bar_real_error_handler )
			return call_user_func( $_debug_bar_real_error_handler, $type, $message, $file, $line );
		else
			return false;
	}
	
	function render() {
		global $_debug_bar_notices, $_debug_bar_warnings;
		
		echo "<div id='debug-bar-php'>";
		echo '<h2><span>Total Warnings:</span>' . number_format( count( $_debug_bar_warnings ) ) . "</h2>\n";
		echo '<h2><span>Total Notices:</span>' . number_format( count( $_debug_bar_notices ) ) . "</h2>\n";
		if ( count( $_debug_bar_warnings ) ) {
			echo '<ol class="debug-bar-php-list">';
			foreach ( $_debug_bar_warnings as $location_message) {
				list( $location, $message) = $location_message;
				echo "<li class='debug-bar-php-warning'>WARNING: ".str_replace(ABSPATH, '', $location) . ' - ' . strip_tags($message). "</li>";
			}
			echo '</ol>';
		}
		if ( count( $_debug_bar_notices ) ) {
			echo '<ol class="debug-bar-php-list">';
			foreach ( $_debug_bar_notices as $location_message) {
				list( $location, $message) = $location_message;
				echo "<li  class='debug-bar-php-notice'>NOTICE: ".str_replace(ABSPATH, '', $location) . ' - ' . strip_tags($message). "</li>";
			}
			echo '</ol>';
		}
		echo "</div>";
		
	}
}

?>