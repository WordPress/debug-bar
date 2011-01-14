<?php

class Debug_Bar_Queries extends Debug_Bar_Panel {
	function init() {
		$this->title( __('Queries') );
	}

	function prerender() {
		$this->set_visible( defined('SAVEQUERIES') && SAVEQUERIES );
	}

	function render() {
		global $wpdb;

		$queries = array();
		$out = '';
		$total_time = 0;

		if ( !empty($wpdb->queries) ) {
			$show_many = isset($_GET['debug_queries']);

			if ( $wpdb->num_queries > 500 && !$show_many )
			$out .= "<p>" . sprintf( __('There are too many queries to show easily! <a href="%s">Show them anyway</a>'), add_query_arg( 'debug_queries', 'true' ) ) . "</p>";

			$out .= '<ol id="wpd-queries">';
			$first_query = 0;
			$counter = 0;

			foreach ( $wpdb->queries as $q ) {
				list($query, $elapsed, $debug) = $q;

				$total_time += $elapsed;

				if ( !$show_many && ++$counter > 500 )
				continue;

				$debug = explode( ', ', $debug );
				$debug = array_diff( $debug, array( 'require_once', 'require', 'include_once', 'include' ) );
				$debug = implode( ', ', $debug );
				$debug = str_replace( array( 'do_action, call_user_func_array' ), array( 'do_action' ), $debug );
				$query = nl2br(esc_html($query));

				$out .= "<li>$query<br/><div class='qdebug'>$debug <span>#{$counter} (" . number_format(sprintf('%0.1f', $elapsed * 1000), 1, '.', ',') . "ms)</span></div></li>\n";
			}
			$out .= '</ol>';
		} else {
			$out .= "<p><strong>" . __('There are no queries on this page.') . "</strong></p>";
		}

		$query_count = '<h2><span>Total Queries:</span>' . number_format( $wpdb->num_queries ) . "</h2>\n";
		$query_time = '<h2><span>Total query time:</span>' . number_format(sprintf('%0.1f', $total_time * 1000), 1) . " ms</h2>\n";

		$out = $query_count . $query_time . $out;

		echo $out;
	}
}

?>