<?php
namespace OUI\Support;

defined( 'ABSPATH' ) || exit;

class Security {
	public static function verify_nonce( $action, $nonce_field ) {
		$nonce = isset( $_REQUEST[ $nonce_field ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $nonce_field ] ) ) : '';
		return (bool) wp_verify_nonce( $nonce, $action );
	}

	public static function current_user_can_import() {
		return current_user_can( defined( 'OUI_CAP_IMPORT' ) ? OUI_CAP_IMPORT : 'manage_options' );
	}

	public static function sanitize_mapping( $mapping ) {
		$clean = array();
		if ( is_array( $mapping ) ) {
			foreach ( $mapping as $field => $column ) {
				$clean[ sanitize_key( $field ) ] = sanitize_text_field( (string) $column );
			}
		}
		return $clean;
	}

	public static function sanitize_bool( $value ) {
		return (bool) ( $value === true || $value === '1' || $value === 1 || $value === 'on' );
	}

	public static function sanitize_int_array( $values ) {
		$values = is_array( $values ) ? $values : array();
		return array_values( array_filter( array_map( 'intval', $values ), static function ( $v ) { return $v > 0; } ) );
	}
}
