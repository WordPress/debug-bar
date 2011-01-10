<?php
/*
 Plugin Name: Debug Bar
 Plugin URI: http://wordpress.org/extend/plugins/debug-bar/
 Description: Adds a debug menu to the admin bar that shows query, cache, and other helpful debugging information.
 Author: wordpressdotorg
 Version: 0.4-alpha
 Author URI: http://wordpress.org/
 */

/***
 * Debug Functions
 *
 * When logged in as a super admin, these functions will run to provide
 * debugging information when specific super admin menu items are selected.
 *
 * They are not used when a regular user is logged in.
 */

function debug_bar_menu() {
	global $wp_admin_bar, $wpdb;

	if ( ! is_super_admin() || ! is_admin_bar_showing() )
	return;

	$class = 'ab-debug-bar';
	if ( count( $GLOBALS['_debug_bar_warnings'] ) )
		$class .= ' ab-php-warning';
	elseif ( count( $GLOBALS['_debug_bar_notices'] ) )
		$class .= ' ab-php-notice';

	/* Add the main siteadmin menu item */
	$wp_admin_bar->add_menu( array( 'id' => 'queries', 'title' => __('Debug'), 'href' => 'javascript:toggle_query_list()', 'meta' => array( 'class' => $class ) ) );
}
add_action( 'admin_bar_menu', 'debug_bar_menu', 1000 );

function debug_bar_menu_init() {
	if ( ! is_super_admin() || ! is_admin_bar_showing() )
	return;

	$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
	wp_enqueue_style( 'admin-bar-debug', plugins_url("debug-bar/debug-bar$suffix.css"), array(), '20101216a' );
	wp_enqueue_script( 'admin-bar-debug', plugins_url("debug-bar/debug-bar$suffix.js"), array(), '20101109' );

	// Silence E_NOTICE for deprecated usage.
	foreach ( array( 'function', 'file', 'argument' ) as $item )
		add_filter( "deprecated_{$item}_trigger_error", '__return_false' );

}
add_action('admin_bar_init', 'debug_bar_menu_init');

function debug_bar_list() {
	global $wpdb, $wp_object_cache;

	if ( ! is_super_admin() || ! is_admin_bar_showing() )
		return;

	$debugs = array();

	if ( defined('SAVEQUERIES') && SAVEQUERIES )
	$debugs['wpdb'] = array( __('Queries'), 'debug_bar_queries' );

	if ( is_object($wp_object_cache) && method_exists($wp_object_cache, 'stats') )
	$debugs['object-cache'] = array( __('Object Cache'), 'debug_bar_object_cache' );

	if ( WP_DEBUG ) {
		$debugs['php'] = array( __('Notices / Warnings'), 'debug_bar_php' );
	}

	$debugs['deprecated'] = array( __('Deprecated'), 'debug_bar_deprecated' );
	$debugs['wp_query'] = array( __( 'WP Query' ), 'debug_bar_wp_query' );

	if ( ! is_admin() )
		$debugs['request'] = array( __( 'Request' ), 'debug_bar_request' );

	$debugs = apply_filters( 'debug_bar_list', $debugs );

	if ( empty($debugs) )
		return;

	?>
<div align='left' id='querylist'>

<h1><?php printf( __('Debugging blog #%d on %s'), $GLOBALS['blog_id'], php_uname( 'n' ) ); ?></h1>
<div id="debug-status">
<p class="left"></p>
<p class="right"><?php printf( __('PHP Version: %1$s, DB Version: %2$s'), phpversion(), $wpdb->db_version() ); ?></p>
</div>
<ul class="debug-menu-links">

	<?php	$current = ' class="current"'; foreach ( $debugs as $debug => $debug_output ) : ?>

	<li <?php echo $current; ?>><a
		id="debug-menu-link-<?php echo $debug; ?>"
		href="#debug-menu-target-<?php echo $debug; ?>"
		onclick="try { return clickDebugLink( 'debug-menu-targets', this ); } catch (e) { return true; }"><?php echo $debug_output[0] ?></a></li>

	<?php	$current = ''; endforeach; ?>

</ul>

<div id="debug-menu-targets"><?php	$current = ' style="display: block"'; foreach ( $debugs as $debug => $debug_output ) : ?>

<div id="debug-menu-target-<?php echo $debug; ?>"
	class="debug-menu-target" <?php echo $current; ?>><?php echo str_replace( '&nbsp;', '', call_user_func( $debug_output[1] ) ); ?>
</div>

<?php	$current = ''; endforeach; ?></div>

<?php	do_action( 'debug_bar' ); ?></div>

<?php
}
add_action( 'wp_after_admin_bar_render', 'debug_bar_list' );

function debug_bar_queries() {
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

			$query = nl2br(esc_html($query));

			$out .= "<li>$query<br/><div class='qdebug'>$debug <span>#{$counter} (" . number_format(sprintf('%0.1f', $elapsed * 1000), 1, '.', ',') . "ms)</span></div></li>\n";
		}
		$out .= '</ol>';
	} else {
		$out .= "<p><strong>" . __('There are no queries on this page.') . "</strong></p>";
	}

	$query_count = '<h2><span>Total Queries:</span>' . number_format( $wpdb->num_queries ) . "</h2>\n";
	$query_time = '<h2><span>Total query time:</span>' . number_format(sprintf('%0.1f', $total_time * 1000), 1) . "ms</h2>\n";
	$memory_usage = '<h2><span>Peak Memory Used:</span>' . number_format( memory_get_peak_usage( ) ) . " bytes</h2>\n";

	$out = $query_count . $query_time . $memory_usage . $out;

	return $out;
}

function debug_bar_object_cache() {
	global $wp_object_cache;
	ob_start();
	echo "<div id='object-cache-stats'>";
	$wp_object_cache->stats();
	echo "</div>";
	$out = ob_get_contents();
	ob_end_clean();

	return $out;
}


function debug_bar_php() {
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

function debug_bar_deprecated() {
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

function debug_bar_wp_query() {
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

function debug_bar_request() {
	global $wp;

	echo "<div id='debug-bar-request'>";

	if ( empty($wp->request) )
		$request = 'None';
	else
		$request = $wp->request;

	echo '<h3>Request:</h3>';
	echo '<p>' . esc_html( $request ) . '</p>';

	if ( empty($wp->query_string) )
		$query_string = 'None';
	else
		$query_string = $wp->query_string;

	echo '<h3>Query String:</h3>';
	echo '<p>' . esc_html( $query_string ) . '</p>';

	if ( empty($wp->matched_rule) )
		$matched_rule = 'None';
	else
		$matched_rule = $wp->matched_rule;

	echo '<h3>Matched Rewrite Rule:</h3>';
	echo '<p>' . esc_html( $matched_rule ) . '</p>';

	if ( empty($wp->matched_query) )
		$matched_query = 'None';
	else
		$matched_query = $wp->matched_query;

	echo '<h3>Matched Rewrite Query:</h3>';
	echo '<p>' . esc_html( $matched_query ) . '</p>';

	echo '</div>';
}

function debug_bar_error_handler( $type, $message, $file, $line ) {
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
if ( WP_DEBUG ) {
	$GLOBALS['_debug_bar_real_error_handler'] = set_error_handler('debug_bar_error_handler');
	$GLOBALS['_debug_bar_warnings'] = $GLOBALS['_debug_bar_notices'] = array();
}

// Alot of this code is massaged from nacin's log-deprecated-notices plugin
$_debug_bar_deprecated_functions = $_debug_bar_deprecated_files = $_debug_bar_deprecated_arguments = array();
function debug_bar_deprecated_function_run($function, $replacement, $version) {
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
add_action( 'deprecated_function_run',  'debug_bar_deprecated_function_run',  10, 3 );

function debug_bar_deprecated_file_included( $old_file, $replacement, $version, $message ) {
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
add_action( 'deprecated_file_included', 'debug_bar_deprecated_file_included', 10, 4 );

function debug_bar_deprecated_argument_run( $function, $message, $version) {
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
add_action( 'deprecated_argument_run',  'debug_bar_deprecated_argument_run',  10, 3 );

?>
