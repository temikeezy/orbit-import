<?php
namespace OUI\Import;

defined( 'ABSPATH' ) || exit;

class Mapper {
	public static function normalize_headers( array $headers ) {
		return array_map( static function ( $h ) {
			$h = strtolower( trim( (string) $h ) );
			return $h;
		}, $headers );
	}

	public static function validate_row( array $row, array $mapping ) {
		$email_col = isset( $mapping['email'] ) ? $mapping['email'] : '';
		if ( '' === $email_col ) {
			return new \WP_Error( 'missing_email_mapping', __( 'Email mapping is required.', 'orbit-import' ) );
		}
		$email = isset( $row[ $email_col ] ) ? sanitize_email( $row[ $email_col ] ) : '';
		if ( empty( $email ) || ! is_email( $email ) ) {
			return new \WP_Error( 'invalid_email', __( 'Invalid email.', 'orbit-import' ) );
		}
		return true;
	}
}
