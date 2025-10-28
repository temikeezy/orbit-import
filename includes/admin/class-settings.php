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
		add_action( 'wp_ajax_ogmi_send_test_email', array( $this, 'ajax_send_test_email' ) );
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

		add_settings_section( 'ogmi_tools', __( 'Tools', OGMI_TEXT_DOMAIN ), '__return_false', 'ogmi-settings' );
		add_settings_field( 'test_email', __( 'Send test welcome email', OGMI_TEXT_DOMAIN ), array( $this, 'field_test_email' ), 'ogmi-settings', 'ogmi_tools' );
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

	public function field_test_email() {
		wp_nonce_field( 'ogmi_test_email', 'ogmi_test_email_nonce' );
		?>
		<p>
			<input type="email" id="ogmi_test_email_address" placeholder="user@example.com" class="regular-text" />
			<button type="button" class="button" id="ogmi_send_test_email"><?php echo esc_html__( 'Send Test', OGMI_TEXT_DOMAIN ); ?></button>
		</p>
		<script>
		(function(){
			var btn = document.getElementById('ogmi_send_test_email');
			if (!btn) return;
			btn.addEventListener('click', function(){
				var email = document.getElementById('ogmi_test_email_address').value;
				var nonce = document.getElementById('ogmi_test_email_nonce');
				var data = new FormData();
				data.append('action', 'ogmi_send_test_email');
				data.append('nonce', document.getElementById('ogmi_test_email_nonce').value);
				data.append('email', email);
				fetch(ajaxurl, { method:'POST', body:data }).then(function(r){ return r.json(); }).then(function(resp){
					alert(resp.data && resp.data.message ? resp.data.message : (resp.success ? 'Sent' : 'Failed'));
				});
			});
		})();
		</script>
		<?php
	}

	public function ajax_send_test_email() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', OGMI_TEXT_DOMAIN ) ) );
		}
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ogmi_test_email' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', OGMI_TEXT_DOMAIN ) ) );
		}
		$email = sanitize_email( $_POST['email'] );
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid email address', OGMI_TEXT_DOMAIN ) ) );
		}
		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			// Create a temporary user for testing
			$username = sanitize_user( current_time('timestamp') . '_ogmi_test' );
			$pwd = wp_generate_password( 20, true, true );
			$uid = wp_insert_user( array( 'user_login' => $username, 'user_email' => $email, 'user_pass' => $pwd, 'role' => 'subscriber' ) );
			if ( is_wp_error( $uid ) ) {
				wp_send_json_error( array( 'message' => __( 'Unable to create test user', OGMI_TEXT_DOMAIN ) ) );
			}
			$user = get_user_by( 'id', $uid );
		}
		// Build reset URL
		$key = get_password_reset_key( $user );
		if ( is_wp_error( $key ) ) {
			wp_send_json_error( array( 'message' => __( 'Unable to generate reset link', OGMI_TEXT_DOMAIN ) ) );
		}
		$reset_url = add_query_arg( array( 'action' => 'rp', 'key' => $key, 'login' => rawurlencode( $user->user_login ) ), wp_login_url() );
		// Prefer BuddyBoss
		$sent = false;
		if ( function_exists( 'bp_send_email' ) ) {
			$tokens = apply_filters( 'ogmi_welcome_email_tokens', array(
				'recipient.name' => $user->display_name ?: $user->user_login,
				'reset.url' => $reset_url,
				'site.name' => wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
				'site.url' => home_url( '/' ),
			), $user, $reset_url );
			$type = apply_filters( 'ogmi_welcome_email_template', 'orbit-welcome', $user );
			try { $sent = (bool) bp_send_email( $type, $user->ID, array( 'tokens' => $tokens ) ); } catch ( Exception $e ) { $sent = false; }
		}
		if ( ! $sent ) {
			$subject = sprintf( 'Welcome to %s', wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) );
			$body = '<p>Test email. Set password:</p><p><a href="' . esc_url( $reset_url ) . '">' . esc_html( $reset_url ) . '</a></p>';
			$set_html = function() { return 'text/html'; };
			add_filter( 'wp_mail_content_type', $set_html );
			wp_mail( $user->user_email, $subject, $body );
			remove_filter( 'wp_mail_content_type', $set_html );
		}
		wp_send_json_success( array( 'message' => __( 'Test email dispatched (check mail logs).', OGMI_TEXT_DOMAIN ) ) );
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


