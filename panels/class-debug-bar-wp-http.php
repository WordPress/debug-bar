<?php

class Debug_Bar_WP_Http extends Debug_Bar_Panel {
	public $requests = [];

	public $time_limit = 250; // milliseconds
	public $total_time = 0;
	public $num_errors = 0;

	function early_init() {
		add_filter( 'http_request_args', [ $this, 'before_http_request' ], 10, 3 );
		add_filter( 'http_api_debug', [ $this, 'after_http_request' ], 10, 5 );
	}

	function before_http_request( $args, $url ) {
		$args['time_start'] = microtime( true );

		$this->requests["{$args['time_start']}"] = [
			'url' => $url,
			'args' => $args
		];

		return $args;
	}

	function after_http_request( $response, $type, $class, $args, $url ) {
		if ( $type !== 'response' ) {
			return;
		}

		$args['time_stop'] = microtime( true );

		$args['duration'] = $args['time_stop'] - $args['time_start'];
		$args['duration'] *= 1000;

		$this->total_time += $args['duration'];

		if ( $this->is_request_error( $response ) ) {
			$this->num_errors++;
		} else {
			if ( ! isset( $_GET['fullbody'] ) ) {
				$response['body'] = '[omitted]';
				unset( $response['http_response'] );
			}
		}

		$this->requests["{$args['time_start']}"] = array_merge(
			$this->requests["{$args['time_start']}"],
			[
				'r' => $response,
				'class' => $class,
				'args' => $args,
				'url' => $url,
				'stack_trace' => wp_debug_backtrace_summary( null, 0, false ),
			]
		);
	}

	function is_request_error( $response ) {
		if (
			empty( $response )
			|| is_wp_error( $response )
			|| $response['response']['code'] >= 400
		) {
			return true;
		}

		return false;
	}

	function init() {
		$this->title( __( 'WP_Http', 'debug-bar' ) );
	}

	function prerender() {
		$this->set_visible( ! empty( $this->requests ) );
	}

	function debug_bar_classes( $classes ) {
		if (
			$this->num_errors > 0
			|| $this->total_time > $this->time_limit
		) {
			$classes[] = 'debug-bar-php-warning-summary';
		}
		return $classes;
	}

	function render() {
		$num_requests = number_format_i18n( count( $this->requests ) );
		$elapsed      = number_format_i18n( $this->total_time, 1 );
		$num_errors   = number_format_i18n( $this->num_errors );

		if ( isset( $_GET['fullbody'] ) ) {
			$fullbody = '<p style="clear:left">' . esc_html__( 'Request and response bodies are included.', 'debug-bar' ) . ' <a href="' . esc_attr( remove_query_arg( 'fullbody' ) ) . '">' . esc_html__( 'Reload with those omitted.', 'debug-bar' ) . '</a></p>';
		} else {
			$fullbody = '<p style="clear:left">' . esc_html__( 'Request and response bodies are omitted.', 'debug-bar' ) . ' <a href="' . esc_attr( add_query_arg( 'fullbody', 'please' ) ) . '">' . esc_html__( 'Reload with those included.', 'debug-bar' ) . '</a></p>';
		}

		$elapsed_class = '';
		if ( $this->total_time > $this->time_limit ) {
			$elapsed_class = 'debug_bar_http_error';
		}

		$errors_class = '';
		if ( $this->num_errors > 0 ) {
			$errors_class = 'debug_bar_http_error';
		}

		?>
<style>
	#debug_bar_http { clear: left; }
	#debug_bar_http .err, .debug_bar_http_error { background-color: #ffebe8; border: 1px solid #d00 !important; }
	#debug_bar_http th, #debug_bar_http td { padding: 8px; }
	#debug_bar_http pre { font-family: monospace; }
</style>

<script>
function debug_bar_http_toggle( id ) {
	var e = document.getElementById( id );
	if ( e.style.display === "" ) {
		e.style.display = "none";
	} else {
		e.style.display = "";
	}
}
</script>

<h2><span><?php esc_html_e( 'HTTP Requests:', 'debug-bar' ); ?></span> <?php echo esc_html( $num_requests ); ?></h2>
<h2 class="<?php echo esc_attr( $elapsed_class ); ?>"><span><?php esc_html_e( 'Total Elapsed:', 'debug-bar' ); ?></span> <?php /* translators: %s = duration in milliseconds. */ printf( esc_html__( '%s ms', 'debug-bar' ), $elapsed ); ?></h2>
<h2 class="<?php echo esc_attr( $errors_class ); ?>"><span><?php esc_html_e( 'Errors:', 'debug-bar' ); ?></span> <?php echo esc_html( $num_errors ); ?></h2>

<?php echo $fullbody; ?>

<table id="debug_bar_http">
	<thead>
		<tr>
			<th><?php esc_html_e( 'More', 'debug-bar' ); ?></th>
			<th><?php esc_html_e( 'Start', 'debug-bar' ); ?></th>
			<th><?php esc_html_e( 'Duration', 'debug-bar' ); ?></th>
			<th><?php esc_html_e( 'Method', 'debug-bar' ); ?></th>
			<th><?php esc_html_e( 'URL', 'debug-bar' ); ?></th>
			<th><?php esc_html_e( 'Code', 'debug-bar' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php

		foreach( $this->requests as $i => $r ) {
			$class = '';
			if (
				( ! empty( $r['r'] ) && $this->is_request_error( $r['r'] ) )
				|| ( ! empty( $r['args']['duration'] ) && $r['args']['duration'] > $this->time_limit )
			) {
				$class = 'err';
			}

			$start = $r['args']['time_start'] - $_SERVER['REQUEST_TIME_FLOAT'];
			$start = number_format_i18n( $start * 1000, 1 );

			$duration = 'error getting request duration';
			if ( ! empty( $r['args']['duration'] ) ) {
				$duration = sprintf(
					/* translators: %s = duration in milliseconds. */
					__( '%s ms', 'debug-bar' ),
					number_format_i18n( $r['args']['duration'], 1 )
				);
			}
			$method = $r['args']['method'];
			$url = $r['url'];

			if ( ! empty( $r['r'] ) && is_wp_error( $r['r'] ) ) {
				$code = esc_html( $r['r']->get_error_code() );
			} else {
				$code = 'error getting response code, most likely a stopped request';
				if ( ! empty( $r['r']['response']['code'] ) ) {
					$code = $r['r']['response']['code'];
				}
			}

			$details = esc_html( print_r( $r, true ) );

			$record_id = 'debug_bar_http_record_' . md5( $i );
			
			?>
		<tr class="<?php echo esc_attr( $class ); ?>">
			<td><a onclick="debug_bar_http_toggle( '<?php echo esc_attr( $record_id ); ?>' )"><?php esc_html_e( 'Toggle', 'debug-bar' ); ?></a></td>
			<td><?php /* translators: %s = duration in milliseconds. */ printf( esc_html__( '%s ms', 'debug-bar' ), $start ); ?></td>
			<td><?php echo esc_html( $duration ); ?></td>
			<td><?php echo esc_html( $method ); ?></td>
			<td><?php echo esc_url( $url ); ?></td>
			<td><?php echo esc_html( $code ); ?></td>
		</tr>

		<tr id="<?php echo esc_attr( $record_id ); ?>" style="display: none">
			<td colspan="5"><pre><?php echo esc_html( $details ); ?></pre></td>
		</tr>
		<?php

		}

		?>
	</tbody>
</table>
	<?php

	}
}
