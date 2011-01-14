<?php
// Alot of this code is massaged from Andrew Nacin's log-deprecated-notices plugin

class Debug_Bar_Deprecated extends Debug_Bar_Panel {
	function init() {
		$this->title( __('Deprecated') );
		
		$_debug_bar_deprecated_functions = $_debug_bar_deprecated_files = $_debug_bar_deprecated_arguments = array();
		
		add_action( 'deprecated_function_run', array( &$this, 'deprecated_function_run' ), 10, 3 );
		add_action( 'deprecated_file_included', array( &$this, 'deprecated_file_included' ), 10, 4 );
		add_action( 'deprecated_argument_run',  array( &$this, 'deprecated_argument_run' ),  10, 3 );
	}
	
	function prerender() {
		global $_debug_bar_deprecated_functions, $_debug_bar_deprecated_files, $_debug_bar_deprecated_arguments;
		$this->set_visible(
			count( $_debug_bar_deprecated_functions )
			|| count( $_debug_bar_deprecated_files )
			|| count( $_debug_bar_deprecated_arguments )
		);
	}
	
	function render() {
		global $_debug_bar_deprecated_functions, $_debug_bar_deprecated_files, $_debug_bar_deprecated_arguments;
		echo "<div id='debug-bar-deprecated'>";
		echo '<h2><span>Total Functions:</span>' . number_format( count( $_debug_bar_deprecated_functions ) ) . "</h2>\n";
		echo '<h2><span>Total Arguments:</span>' . number_format( count( $_debug_bar_deprecated_arguments ) ) . "</h2>\n";
		echo '<h2><span>Total Files:</span>' . number_format( count( $_debug_bar_deprecated_files ) ) . "</h2>\n";
		if ( count( $_debug_bar_deprecated_functions ) ) {
			echo '<ol class="debug-bar-deprecated-list">';
			foreach ( $_debug_bar_deprecated_functions as $location => $message)
				echo "<li class='debug-bar-deprecated-function'>".str_replace(ABSPATH, '', $location) . ' - ' . strip_tags($message). "</li>";
			echo '</ol>';
		}
		if ( count( $_debug_bar_deprecated_files ) ) {
			echo '<ol class="debug-bar-deprecated-list">';
			foreach ( $_debug_bar_deprecated_files as $location => $message)
				echo "<li class='debug-bar-deprecated-function'>".str_replace(ABSPATH, '', $location) . ' - ' . strip_tags($message). "</li>";
			echo '</ol>';
		}
		if ( count( $_debug_bar_deprecated_arguments ) ) {
			echo '<ol class="debug-bar-deprecated-list">';
			foreach ( $_debug_bar_deprecated_arguments as $location => $message)
				echo "<li class='debug-bar-deprecated-function'>".str_replace(ABSPATH, '', $location) . ' - ' . strip_tags($message). "</li>";
			echo '</ol>';
		}
		echo "</div>";
	}
	
	function deprecated_function_run($function, $replacement, $version) {
		global $_debug_bar_deprecated_functions;
		$backtrace = debug_backtrace();
		$bt = 4;
		// Check if we're a hook callback.
		if ( ! isset( $backtrace[4]['file'] ) && 'call_user_func_array' == $backtrace[5]['function'] ) {
			$bt = 6;
		}
		$file = $backtrace[ $bt ]['file'];
		$line = $backtrace[ $bt ]['line'];
		if ( ! is_null($replacement) )
			$message = sprintf( __('%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.'), $function, $version, $replacement );
		else
			$message = sprintf( __('%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.'), $function, $version );

		$_debug_bar_deprecated_functions[$file.':'.$line] = $message;
	}
	
	function deprecated_file_included( $old_file, $replacement, $version, $message ) {
		global $_debug_bar_deprecated_files;
		$backtrace = debug_backtrace();
		$file = $backtrace[4]['file'];
		$file_abs = str_replace(ABSPATH, '', $file);
		$line = $backtrace[4]['line'];
		$message = empty( $message ) ? '' : ' ' . $message;
		if ( ! is_null( $replacement ) )
			$message = sprintf( __('%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.'), $file_abs, $version, $replacement ) . $message;
		else
			$message = sprintf( __('%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.'), $file_abs, $version ) . $message;

		$_debug_bar_deprecated_files[$file.':'.$line] = $message;
	}
	
	function deprecated_argument_run( $function, $message, $version) {
		global $_debug_bar_deprecated_arguments;
		$backtrace = debug_backtrace();
		$bt = 4;
		if ( ! isset( $backtrace[4]['file'] ) && 'call_user_func_array' == $backtrace[5]['function'] ) {
			$bt = 6;
		}
		$file = $backtrace[ $bt ]['file'];
		$line = $backtrace[ $bt ]['line'];

		$_debug_bar_deprecated_arguments[$file.':'.$line] = $message;
	}
}


?>