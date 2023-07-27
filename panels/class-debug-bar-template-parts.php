<?php

class Debug_Bar_Template_Parts extends Debug_Bar_Panel {

	const COLOR_STEP = 13;

	function init() {
		$this->title( __( 'Template Parts', 'debug-bar' ) );

		add_action( 'wp_before_load_template', array( $this, 'action_template_part' ), 10, 3 );
	}

	function prerender() {
		$this->set_visible( defined( 'SAVE_TEMPLATE_PARTS' ) && SAVE_TEMPLATE_PARTS );
	}

	function render() {
		global $template_parts;

		echo '<div class="debug-bar-template-parts">';

		echo '<h3>' . __( 'Templates:', 'debug-bar' ) . '</h3>';

		if ( $template_parts ) {
			echo '<div class="debug-bar-template-parts__list">';
			foreach ( $template_parts as $hash => $template_part) {
				echo '<div class="debug-bar-template-parts__group debug-bar-template-parts__depth--0" style="background: rgb(232, 232, 232);">';
				$this->list_row($template_part, 0, 232);
				echo '</div>';
			}
			echo '</div>';
		}

		echo '</div>';
	}

	function list_row($template_part, $depth, $color) {
		$params = '';
		if ( ! empty( $template_part['params'] ) ) {
			$params = implode( ',', $template_part['params'] );
			$params = "<div>" . __( 'Params:', 'debug-bar' ) . " «{$params}»</div>";
		}

		echo "<div class='debug-bar-template-parts__row'>{$template_part['template']}{$params}</div>";

		if ($template_part['child']) {
			$color -= Debug_Bar_Template_Parts::COLOR_STEP;
			$depth++;

			echo '<div class="debug-bar-template-parts__child">';
			foreach ($template_part['child'] as $key => $template_part_child) {
				echo "<div class='debug-bar-template-parts__group debug-bar-template-parts__depth--{$depth}' style='background: rgb({$color}, {$color}, {$color});'>";
				$this->list_row($template_part_child, $depth, $color);
				echo '</div>';
			}
			echo '</div>';
		}
	}

	function action_template_part( $_template_file, $load_once, $args ) {
		if ( defined( 'SAVE_TEMPLATE_PARTS' ) && SAVE_TEMPLATE_PARTS ) {

			$template = '/' . str_replace( ABSPATH, '', $_template_file );

			if ( ! isset( $GLOBALS['template_parts'] ) ) {
				$GLOBALS['template_parts'] = array();
			}

			$params = '';
			if ( ! empty( $args ) ) {
				$params = array_keys( $args );
			}

			$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
			$trace_func = '';
			foreach ( $trace as $key => $line ) {
				if ( in_array( $line['function'], array( 'include', 'require', 'require_once' ) ) ) {
					$trace_func = '/' . str_replace( ABSPATH, '', $line['args'][0] );
					break;
				}
			}

			$hash = md5($template);
			$parent_hash = md5($trace_func);

			$setValue = $this->set_value( $GLOBALS['template_parts'], $template, $parent_hash, $params );

			if ( ! $setValue ) {
				$GLOBALS['template_parts'][$hash] = array(
					'template' => $template,
					'params' => $params,
					'child' => array()
				);
			}
		}
	}

	function set_value( & $array, $template, $parent_hash, $params ) {
		$hash = md5($template);
		$status = false;

		foreach ( $array as $key => & $value ) {
			if ( $key == $parent_hash ) {
				$value['child'][$hash] = [
					'template' => $template,
					'params' => $params,
					'child' => array(),
				];
				$status = true;
			}
			if ( $value['child'] ) {
				$result = $this->set_value( $value['child'], $template, $parent_hash, $params );
				if ( $result ) {
					$status = $result;
				}
			}
		}

		return $status;
	}
}
