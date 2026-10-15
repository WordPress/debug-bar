<?php

class Debug_Bar_Template_Parts extends Debug_Bar_Panel {
	const COLOR_STEP = 13;

	public $template_parts = array();
	public $template_stack = array();

	function init() {
		global $wp_version;

		if ( ! defined( 'SAVE_TEMPLATE_PARTS' ) || ! SAVE_TEMPLATE_PARTS || version_compare( $wp_version, '6.1', '<' ) ) {
			return false;
		}

		$this->title( __( 'Template Parts', 'debug-bar' ) );

		add_action( 'wp_before_load_template', array( $this, 'before_template' ), 10, 3 );
		add_action( 'wp_after_load_template', array( $this, 'after_template' ), 10, 3 );
	}

	function render() {
		echo '<div class="debug-bar-template-parts">';
		echo '<h3>' . esc_html__( 'Templates:', 'debug-bar' ) . '</h3>';

		if ( empty( $this->template_parts ) ) {
			echo '<p>' . esc_html__( 'No template parts were loaded.', 'debug-bar' ) . '</p>';
		} else {
			echo '<div class="debug-bar-template-parts__list">';
			foreach ( $this->template_parts as $template_part ) {
				$this->render_row( $template_part, 232 );
			}
			echo '</div>';
		}

		echo '</div>';
	}

	function render_row( $template_part, $color ) {
		$color = max( 0, $color );

		echo '<div class="debug-bar-template-parts__group" style="background-color: rgb(' . esc_attr( $color ) . ', ' . esc_attr( $color ) . ', ' . esc_attr( $color ) . ');">';
		echo '<div class="debug-bar-template-parts__row">' . esc_html( $template_part['template'] );

		if ( ! empty( $template_part['args'] ) ) {
			echo '<div>' . esc_html__( 'Arguments:', 'debug-bar' ) . ' &laquo;' . esc_html( implode( ', ', $template_part['args'] ) ) . '&raquo;</div>';
		}

		echo '</div>';

		foreach ( $template_part['children'] as $child ) {
			$this->render_row( $child, $color - self::COLOR_STEP );
		}

		echo '</div>';
	}

	function before_template( $_template_file, $load_once, $args ) {
		$template_part = array(
			'template' => $this->relative_template_path( $_template_file ),
			'args'     => array_keys( $args ),
			'children' => array(),
		);

		$children =& $this->template_parts;
		foreach ( $this->template_stack as $index ) {
			$children =& $children[ $index ]['children'];
		}

		$index = count( $children );
		$children[ $index ] = $template_part;
		$this->template_stack[] = $index;
	}

	function after_template( $_template_file, $load_once, $args ) {
		array_pop( $this->template_stack );
	}

	function relative_template_path( $template_file ) {
		$template_file = wp_normalize_path( $template_file );
		$root_path     = trailingslashit( wp_normalize_path( ABSPATH ) );

		if ( 0 === strpos( $template_file, $root_path ) ) {
			return '/' . substr( $template_file, strlen( $root_path ) );
		}

		return $template_file;
	}
}
