<?php

class Debug_Bar_WP_Query extends Debug_Bar_Panel {
	function init() {
		$this->title( __('WP Query') );
	}

	function prerender() {
		$this->set_visible( defined('SAVEQUERIES') && SAVEQUERIES );
	}

	function render() {
		global $template, $wp_query;

		echo "<div id='debug-bar-wp-query'>";
		echo '<h2><span>Queried Object ID:</span>' . get_queried_object_id() . "</h2>\n";

		// Determine the query type. Follows the template loader order.
		$type = '';
		if ( is_404() )
			$type = '404';
		elseif ( is_search() )
			$type = 'Search';
		elseif ( is_tax() )
			$type = 'Taxonomy';
		elseif ( is_front_page() )
			$type = 'Front Page';
		elseif ( is_home() )
			$type = 'Home';
		elseif ( is_attachment() )
			$type = 'Attachment';
		elseif ( is_single() )
			$type = 'Single';
		elseif ( is_page() )
			$type = 'Page';
		elseif ( is_category() )
			$type = 'Category';
		elseif ( is_tag() )
			$type = 'Tag';
		elseif ( is_author() )
			$type = 'Author';
		elseif ( is_date() )
			$type = 'Date';
		elseif ( is_archive() )
			$type = 'Archive';
		elseif ( is_paged() )
			$type = 'Paged';

		if ( !empty($type) )
			echo '<h2><span>Query Type:</span>' . $type . "</h2>\n";

		if ( !empty($template) )
			echo '<h2><span>Query Template:</span>' . basename($template) . "</h2>\n";

		$show_on_front = get_option( 'show_on_front' );
		$page_on_front = get_option( 'page_on_front' );
		$page_for_posts = get_option( 'page_for_posts' );

		echo '<h2><span>Show on Front:</span>' . $show_on_front . "</h2>\n";
		if ( 'page' == $show_on_front ) {
			echo '<h2><span>Page for Posts:</span>' . $page_for_posts . "</h2>\n";
			echo '<h2><span>Page on Front:</span>' . $page_on_front . "</h2>\n";
		}

		echo '<div class="clear"></div>';

		if ( empty($wp_query->query) )
			$query = 'None';
		else
			$query = http_build_query( $wp_query->query );

		echo '<h3>Query Arguments:</h3>';
		echo '<p>' . esc_html( $query ) . '</p>';

		if ( ! empty($wp_query->request) ) {
			echo '<h3>Query SQL:</h3>';
			echo '<p>' . esc_html( $wp_query->request ) . '</p>';
		}

		$object = get_queried_object();
		if ( ! is_null( $object ) ) {
			echo '<h3>Queried Object:</h3>';
			echo '<ol class="debug-bar-wp-query-list">';
			foreach ($object as $key => $value) {
				echo '<li>' . $key . ' => ' . $value . '</li>';
			}
			echo '</ol>';
		}
		echo '</div>';
	}
}

?>