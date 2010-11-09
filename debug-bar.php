<?php
/*
 Plugin Name: Debug Bar
 Plugin URI: http://wordpress.org/extend/plugins/debug-bar/
 Description: Adds a debug menu to the admin bar that shows query, cache, and other helpful debugging information.
 Author: wordpressdotorg
 Version: 0.2
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

	$wp_admin_bar->add_menu( array( 'id' => 'queries', 'title' => __('Debug'), 'href' => 'javascript:toggle_query_list()', 'meta' => array( 'class' => 'ab-sadmin' ) ) );
}
add_action( 'wp_before_admin_bar_render', 'debug_bar_menu', 1000 );

function debug_bar_menu_init() {
	if ( ! is_super_admin() || ! is_admin_bar_showing() )
	return;

	$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
	wp_enqueue_style( 'admin-bar-debug', WP_PLUGIN_URL . "/debug-bar/debug-bar$suffix.css", array(), '20101109' );
	wp_enqueue_script( 'admin-bar-debug', WP_PLUGIN_URL . "/debug-bar/debug-bar$suffix.js", array(), '20101109' );
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
		$debugs['deprecated'] = array( __('Deprecated'), 'debug_bar_deprecated' );
	}

	$debugs = apply_filters( 'debug_bar_list', $debugs );

	if ( empty($debugs) )
	return;

	?>
<div align='left' id='querylist'>

<h1><?php printf( __('Debugging blog #%d on %s'), $GLOBALS['blog_id'], php_uname( 'n' ) ); ?></h1>
<div id="debug-status">
<p class="left"></p>
<p class="right"><?php printf( __('PHP Version: %s'), phpversion() ); ?></p>
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
	echo '<ol class="debug-bar-php-list">';
	foreach ( $_debug_bar_warnings as $location => $message)
		echo "<li class='debug-bar-php-warning'>WARNING: ".str_replace(ABSPATH, '', $location) . ' - ' . strip_tags($message). "</li>";
	echo '</ol';
	echo '<ol class="debug-bar-php-list">';
	foreach ( $_debug_bar_notices as $location => $message)
		echo "<li  class='debug-bar-php-notice'>NOTICE: ".str_replace(ABSPATH, '', $location) . ' - ' . strip_tags($message). "</li>";
	echo '</ol';
	echo "</div>";
}

function debug_bar_deprecated() {
	echo "<div id='debug-bar-deprecated'>";
	echo "</div>";
}

if ( WP_DEBUG ) {
	$_debug_bar_real_error_handler = set_error_handler('debug_bar_error_handler');
	$_debug_bar_warnings = $_debug_bar_notices = array();
}

function debug_bar_error_handler( $type, $message, $file, $line ) {
	global $_debug_bar_real_error_handler, $_debug_bar_notices, $_debug_bar_warnings;

	switch ( $type ) {
		case E_WARNING :
		case E_USER_WARNING :
			$_debug_bar_warnings[$file.':'.$line] = $message; 
			break;
		case E_NOTICE :
		case E_USER_NOTICE :
			$_debug_bar_notices[$file.':'.$line] = $message; 
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
?>