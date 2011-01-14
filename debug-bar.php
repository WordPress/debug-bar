<?php
/*
 Plugin Name: Debug Bar
 Plugin URI: http://wordpress.org/extend/plugins/debug-bar/
 Description: Adds a debug menu to the admin bar that shows query, cache, and other helpful debugging information.
 Author: wordpressdotorg
 Version: 0.5-alpha
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

class Debug_Bar {
	var $panels = array();
	
	function Debug_Bar() {
		add_action( 'admin_bar_menu',               array( &$this, 'admin_bar_menu' ), 1000 );
		add_action( 'admin_bar_init',               array( &$this, 'admin_bar_init' ) );
		add_action( 'wp_after_admin_bar_render',    array( &$this, 'render' ) );
	}
	
	function requirements() {
		$recs = array( 'panel', 'php', 'queries', 'request', 'wp-query', 'object-cache', 'deprecated' );
		foreach ( $recs as $rec )
			require_once "panels/class-debug-bar-$rec.php";
	}
	
	function enqueue() {
		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';

		$url = plugin_dir_url( __FILE__ );

		wp_enqueue_style( 'admin-bar-debug', "{$url}css/debug-bar$suffix.css", array(), '20110113' );
		wp_enqueue_script( 'admin-bar-ui-dockable', "{$url}js/ui-dockable$suffix.js", array('jquery-ui-mouse'), '20110113' );
		wp_enqueue_script( 'admin-bar-debug', "{$url}js/debug-bar$suffix.js", array('jquery', 'admin-bar-ui-dockable'), '20110113' );
	}
	
	function init_panels() {
		$classes = array(
			'Debug_Bar_PHP',
			'Debug_Bar_WP_Query',
			'Debug_Bar_Queries',
			'Debug_Bar_Deprecated',
			'Debug_Bar_Request',
			'Debug_Bar_Object_Cache'
		);

		foreach ( $classes as $class ) {
			$this->panels[] = new $class;
		}

		$this->panels = apply_filters( 'debug_bar_panels', $this->panels );

		foreach ( $this->panels as $panel_key => $panel ) {
			if ( ! $panel->is_visible() )
				unset( $this->panels[ $panel_key ] );
		}
	}
	
	function admin_bar_menu() {
		global $wp_admin_bar;
		
		if ( ! is_super_admin() || ! is_admin_bar_showing() )
			return;

		$this->init_panels();

		$class = '';
		if ( count( $GLOBALS['_debug_bar_warnings'] ) )
			$class = 'warning';
		elseif ( count( $GLOBALS['_debug_bar_notices'] ) )
			$class = 'notice';

		/* Add the main siteadmin menu item */
		$wp_admin_bar->add_menu( array( 'id' => 'debug-bar', 'title' => __('Debug'), 'meta' => array( 'class' => $class ) ) );
	}
	
	function admin_bar_init() {
		if ( ! is_super_admin() || ! is_admin_bar_showing() )
			return;

		$this->requirements();
		$this->enqueue();

		// Silence E_NOTICE for deprecated usage.
		foreach ( array( 'function', 'file', 'argument' ) as $item )
			add_filter( "deprecated_{$item}_trigger_error", '__return_false' );

	}
	
	function render() {
		global $wpdb, $wp_object_cache, $_debug_bar_notices, $_debug_bar_warnings;

		if ( ! is_super_admin() || ! is_admin_bar_showing() || empty( $this->panels ) )
			return;

		foreach ( $this->panels as $panel_key => $panel ) {
			$panel->prerender();
			if ( ! $panel->is_visible() )
				unset( $this->panels[ $panel_key ] );
		}

		?>
	<div id='querylist'>

	<div id='debug-bar-handle'></div>
	<div id='debug-bar-menu'>
		<div id="debug-status">
			<?php //@todo: Add a link to information about WP_DEBUG. ?>
			<?php if ( ! WP_DEBUG ): ?>
				<span id="debug-status-warning"><?php _e('WP_DEBUG OFF'); ?></span> | 
			<?php endif; ?>
			<span id="debug-status-title"><?php printf( __('Blog #%d on %s'), $GLOBALS['blog_id'], php_uname( 'n' ) ); ?></span>
			| <span id="debug-status-version"><?php printf( __('PHP: %1$s | DB: %2$s'), phpversion(), $wpdb->db_version() ); ?></span>
			| <span id="debug-status-peak-memory"><?php printf( __( 'Peak Memory: %s' ), number_format( memory_get_peak_usage( ) ) ); ?> bytes</span>
		</div>
		<ul id="debug-menu-links">

	<?php
		$current = ' current';
		foreach ( $this->panels as $panel ) :
			$class = get_class( $panel );
			?>
			<li><a
				id="debug-menu-link-<?php echo esc_attr( $class ); ?>"
				class="debug-menu-link<?php echo $current; ?>"
				href="#debug-menu-target-<?php echo esc_attr( $class ); ?>">
				<?php
				// Not escaping html here, so panels can use html in the title.
				echo $panel->title();
				?>
			</a></li>
			<?php
			$current = '';
		endforeach; ?>

		</ul>
	</div>

	<div id="debug-menu-targets"><?php
	$current = ' style="display: block"';
	foreach ( $this->panels as $panel ) :
		$class = get_class( $panel ); ?>

		<div id="debug-menu-target-<?php echo $class; ?>" class="debug-menu-target" <?php echo $current; ?>>
			<?php $panel->render(); ?>
			<?php // echo str_replace( '&nbsp;', '', $panel->run() ); ?>
		</div>

		<?php
		$current = '';
	endforeach;
	?>
	</div>

	<?php do_action( 'debug_bar' ); ?>
	</div>
	<?php
	}
}

$GLOBALS['debug_bar'] = new Debug_Bar();

?>