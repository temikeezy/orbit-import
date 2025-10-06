<?php
namespace OUI\Support;

defined( 'ABSPATH' ) || exit;

class Utils {
	public static function generate_password( $length = 20 ) {
		$length = max( 12, (int) $length );
		return wp_generate_password( $length, true, true );
	}

	public static function array_get( $array, $key, $default = null ) {
		return isset( $array[ $key ] ) ? $array[ $key ] : $default;
	}

	public static function render_email_template( $subject, $body, $user, $streams = array() ) {
		$replacements = array(
			'{first_name}'       => isset( $user->first_name ) ? $user->first_name : get_user_meta( $user->ID, 'first_name', true ),
			'{last_name}'        => isset( $user->last_name ) ? $user->last_name : get_user_meta( $user->ID, 'last_name', true ),
			'{display_name}'     => $user->display_name,
			'{site_name}'        => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			'{streams}'          => implode( ', ', array_map( 'sanitize_text_field', $streams ) ),
			'{set_password_url}' => wp_lostpassword_url(),
		);
		return array(
			'subject' => strtr( (string) $subject, $replacements ),
			'body'    => strtr( (string) $body, $replacements ),
		);
	}
}
