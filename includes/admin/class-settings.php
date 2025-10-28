<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OGMI_Settings {
	private $option_key = 'ogmi_settings';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function add_menu() {
		add_options_page(
			__( 'ORBIT Group Importer', OGMI_TEXT_DOMAIN ),
			__( 'ORBIT Group Importer', OGMI_TEXT_DOMAIN ),
			'manage_options',
			'ogmi-settings',
			array( $this, 'render_page' )
		);
	}

	public function register_settings() {
		register_setting( 'ogmi_settings', $this->option_key );

		add_settings_section( 'ogmi_main', __( 'General', OGMI_TEXT_DOMAIN ), '__return_false', 'ogmi-settings' );

		add_settings_field( 'batch_size', __( 'Default batch size', OGMI_TEXT_DOMAIN ), array( $this, 'field_batch_size' ), 'ogmi-settings', 'ogmi_main' );
		add_settings_field( 'send_welcome', __( 'Send welcome email', OGMI_TEXT_DOMAIN ), array( $this, 'field_send_welcome' ), 'ogmi-settings', 'ogmi_main' );
		add_settings_field( 'add_to_blog', __( 'Add user to current site (multisite)', OGMI_TEXT_DOMAIN ), array( $this, 'field_add_to_blog' ), 'ogmi-settings', 'ogmi_main' );
		add_settings_field( 'buddyboss_template', __( 'BuddyBoss template slug', OGMI_TEXT_DOMAIN ), array( $this, 'field_template' ), 'ogmi-settings', 'ogmi_main' );
	}

	private function get( $key, $default = '' ) {
		$opt = get_option( $this->option_key, array() );
		return isset( $opt[ $key ] ) ? $opt[ $key ] : $default;
	}

	public function render_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'ORBIT Group Importer', OGMI_TEXT_DOMAIN ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'ogmi_settings' ); ?>
				<?php do_settings_sections( 'ogmi-settings' ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public function field_batch_size() {
		$value = (int) $this->get( 'batch_size', 50 );
		printf( '<input name="%1$s[batch_size]" type="number" min="1" max="1000" value="%2$d" />', esc_attr( $this->option_key ), $value );
	}

	public function field_send_welcome() {
		$value = (int) $this->get( 'send_welcome', 1 );
		printf( '<label><input name="%1$s[send_welcome]" type="checkbox" value="1" %2$s /> %3$s</label>', esc_attr( $this->option_key ), checked( 1, $value, false ), esc_html__( 'Enable welcome email', OGMI_TEXT_DOMAIN ) );
	}

	public function field_add_to_blog() {
		$value = (int) $this->get( 'add_to_blog', 1 );
		printf( '<label><input name="%1$s[add_to_blog]" type="checkbox" value="1" %2$s /> %3$s</label>', esc_attr( $this->option_key ), checked( 1, $value, false ), esc_html__( 'Add new users to current site on multisite', OGMI_TEXT_DOMAIN ) );
	}

	public function field_template() {
		$value = $this->get( 'buddyboss_template', 'orbit-welcome' );
		printf( '<input name="%1$s[buddyboss_template]" type="text" value="%2$s" class="regular-text" />', esc_attr( $this->option_key ), esc_attr( $value ) );
	}
}

// Bridge settings to filters
add_filter( 'ogmi_import_batch_size', function( $size ) {
	$opt = get_option( 'ogmi_settings', array() );
	return isset( $opt['batch_size'] ) ? (int) $opt['batch_size'] : $size;
});

add_filter( 'ogmi_send_welcome_email', function( $enabled ) {
	$opt = get_option( 'ogmi_settings', array() );
	if ( isset( $opt['send_welcome'] ) ) {
		return (bool) $opt['send_welcome'];
	}
	return $enabled;
}, 10, 1 );

add_filter( 'ogmi_multisite_add_to_blog', function( $enabled ) {
	$opt = get_option( 'ogmi_settings', array() );
	if ( isset( $opt['add_to_blog'] ) ) {
		return (bool) $opt['add_to_blog'];
	}
	return $enabled;
}, 10, 1 );

add_filter( 'ogmi_welcome_email_template', function( $slug ) {
	$opt = get_option( 'ogmi_settings', array() );
	if ( ! empty( $opt['buddyboss_template'] ) ) {
		return $opt['buddyboss_template'];
	}
	return $slug;
}, 10, 1 );


