<?php
namespace OUI\Admin;

defined( 'ABSPATH' ) || exit;

class Settings {
	const OPTION_KEY = 'oui_settings';

	public static function register() {
		register_setting( 'oui_settings_group', self::OPTION_KEY, array( 'sanitize_callback' => array( __CLASS__, 'sanitize' ) ) );

		add_settings_section( 'oui_main', __( 'ORBIT Import Settings', 'orbit-import' ), '__return_false', 'oui_settings' );

		add_settings_field( 'default_wp_role', __( 'Default WP role', 'orbit-import' ), array( __CLASS__, 'field_wp_role' ), 'oui_settings', 'oui_main' );
		add_settings_field( 'default_otm_role', __( 'Default OTM role', 'orbit-import' ), array( __CLASS__, 'field_otm_role' ), 'oui_settings', 'oui_main' );
		add_settings_field( 'batch_size', __( 'Batch size', 'orbit-import' ), array( __CLASS__, 'field_batch' ), 'oui_settings', 'oui_main' );
		add_settings_field( 'welcome_email', __( 'Send welcome email', 'orbit-import' ), array( __CLASS__, 'field_welcome' ), 'oui_settings', 'oui_main' );
		add_settings_field( 'email_subject', __( 'Email subject', 'orbit-import' ), array( __CLASS__, 'field_email_subject' ), 'oui_settings', 'oui_main' );
		add_settings_field( 'email_body', __( 'Email body', 'orbit-import' ), array( __CLASS__, 'field_email_body' ), 'oui_settings', 'oui_main' );

		if ( function_exists( 'groups_get_groups' ) ) {
			add_settings_field( 'require_streams', __( 'Require streams', 'orbit-import' ), array( __CLASS__, 'field_require_streams' ), 'oui_settings', 'oui_main' );
			add_settings_field( 'autocreate_streams', __( 'Auto-create CSV streams', 'orbit-import' ), array( __CLASS__, 'field_autocreate' ), 'oui_settings', 'oui_main' );
		}
	}

	public static function page() {
		echo '<div class="wrap"><h1>' . esc_html__( 'ORBIT Import Settings', 'orbit-import' ) . '</h1>';
		echo '<form method="post" action="options.php">';
		settings_fields( 'oui_settings_group' );
		do_settings_sections( 'oui_settings' );
		submit_button();
		echo '</form></div>';
	}

	public static function sanitize( $input ) {
		$defaults = self::defaults();
		$out = wp_parse_args( is_array( $input ) ? $input : array(), $defaults );
		$out['default_wp_role'] = sanitize_key( $out['default_wp_role'] );
		$out['default_otm_role'] = sanitize_key( $out['default_otm_role'] );
		$out['batch_size'] = min( 200, max( 10, (int) $out['batch_size'] ) );
		$out['welcome_email'] = ! empty( $out['welcome_email'] ) ? 1 : 0;
		$out['email_subject'] = sanitize_text_field( $out['email_subject'] );
		$out['email_body'] = wp_kses_post( $out['email_body'] );
		$out['require_streams'] = ! empty( $out['require_streams'] ) ? 1 : 0;
		$out['autocreate_streams'] = ! empty( $out['autocreate_streams'] ) ? 1 : 0;
		return $out;
	}

	public static function get() {
		return wp_parse_args( get_option( self::OPTION_KEY, array() ), self::defaults() );
	}

	public static function defaults() {
		return array(
			'default_wp_role' => 'subscriber',
			'default_otm_role' => 'otm_intern',
			'batch_size' => 50,
			'welcome_email' => 0,
			'email_subject' => __( 'Welcome to {site_name}', 'orbit-import' ),
			'email_body' => __( 'Hi {first_name}, set your password here: {set_password_url}', 'orbit-import' ),
			'require_streams' => 0,
			'autocreate_streams' => 0,
		);
	}

	private static function roles_dropdown( $selected ) {
		$roles = wp_roles()->roles;
		echo '<select name="' . esc_attr( self::OPTION_KEY ) . '[default_wp_role]">';
		foreach ( $roles as $key => $role ) {
			echo '<option value="' . esc_attr( $key ) . '"' . selected( $selected, $key, false ) . '>' . esc_html( translate_user_role( $role['name'] ) ) . '</option>';
		}
		echo '</select>';
	}

	public static function field_wp_role() {
		$opt = self::get();
		self::roles_dropdown( $opt['default_wp_role'] );
	}
	public static function field_otm_role() {
		$opt = self::get();
		echo '<input type="text" name="' . esc_attr( self::OPTION_KEY ) . '[default_otm_role]" value="' . esc_attr( $opt['default_otm_role'] ) . '" />';
	}
	public static function field_batch() {
		$opt = self::get();
		echo '<input type="number" min="10" max="200" name="' . esc_attr( self::OPTION_KEY ) . '[batch_size]" value="' . esc_attr( (int) $opt['batch_size'] ) . '" />';
	}
	public static function field_welcome() {
		$opt = self::get();
		echo '<label><input type="checkbox" name="' . esc_attr( self::OPTION_KEY ) . '[welcome_email]" value="1" ' . checked( 1, (int) $opt['welcome_email'], false ) . ' /> ' . esc_html__( 'Send email to new users', 'orbit-import' ) . '</label>';
	}
	public static function field_email_subject() {
		$opt = self::get();
		echo '<input type="text" class="regular-text" name="' . esc_attr( self::OPTION_KEY ) . '[email_subject]" value="' . esc_attr( $opt['email_subject'] ) . '" />';
	}
	public static function field_email_body() {
		$opt = self::get();
		echo '<textarea class="large-text" rows="6" name="' . esc_attr( self::OPTION_KEY ) . '[email_body]">' . esc_textarea( $opt['email_body'] ) . '</textarea>';
	}
	public static function field_require_streams() {
		$opt = self::get();
		echo '<label><input type="checkbox" name="' . esc_attr( self::OPTION_KEY ) . '[require_streams]" value="1" ' . checked( 1, (int) $opt['require_streams'], false ) . ' /> ' . esc_html__( 'Require stream membership to import', 'orbit-import' ) . '</label>';
	}
	public static function field_autocreate() {
		$opt = self::get();
		echo '<label><input type="checkbox" name="' . esc_attr( self::OPTION_KEY ) . '[autocreate_streams]" value="1" ' . checked( 1, (int) $opt['autocreate_streams'], false ) . ' /> ' . esc_html__( 'Auto-create missing streams from CSV', 'orbit-import' ) . '</label>';
	}
}
