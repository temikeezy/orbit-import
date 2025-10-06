<?php
/**
 * Plugin Name: ORBIT Bulk User Importer
 * Description: Bulk-import users and assign them to BuddyBoss/BuddyPress Streams (Groups) with dry-run, mapping, batching, resumable jobs, and role assignment.
 * Version: 1.1.0
 * Author: Ilorin Innovation Hub
 * Text Domain: orbit-import
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'OUI_PLUGIN_FILE' ) ) {
	define( 'OUI_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'OUI_PLUGIN_DIR' ) ) {
	define( 'OUI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'OUI_PLUGIN_URL' ) ) {
	define( 'OUI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'OUI_VERSION' ) ) {
	define( 'OUI_VERSION', '1.1.0' );
}

if ( ! defined( 'OUI_CAP_IMPORT' ) ) {
	define( 'OUI_CAP_IMPORT', 'orbit_import_users' );
}

require_once OUI_PLUGIN_DIR . 'includes/Support/class-oui-autoloader.php';
if ( class_exists( 'OUI\\Support\\Autoloader' ) ) {
	OUI\Support\Autoloader::register( 'OUI\\', OUI_PLUGIN_DIR . 'includes/' );
}

add_action( 'plugins_loaded', static function () {
	load_plugin_textdomain( 'orbit-import', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

register_activation_hook( __FILE__, static function () {
	$role = get_role( 'administrator' );
	if ( $role && ! $role->has_cap( OUI_CAP_IMPORT ) ) {
		$role->add_cap( OUI_CAP_IMPORT );
	}
	if ( class_exists( 'OUI\\Import\\Job' ) ) {
		OUI\Import\Job::register_cpt();
	}
	flush_rewrite_rules();
} );
register_deactivation_hook( __FILE__, static function () {
	flush_rewrite_rules();
} );

add_action( 'init', static function () {
	if ( class_exists( 'OUI\\Import\\Job' ) ) {
		OUI\Import\Job::register_cpt();
	}
} );

add_action( 'admin_menu', static function () {
	if ( ! current_user_can( OUI_CAP_IMPORT ) ) {
		return;
	}
	add_users_page(
		__( 'ORBIT Import', 'orbit-import' ),
		__( 'ORBIT Import', 'orbit-import' ),
		OUI_CAP_IMPORT,
		'orbit-import',
		static function () {
			if ( class_exists( 'OUI\\Admin\\Admin' ) ) {
				( new OUI\Admin\Admin() )->render();
			} else {
				echo '<div class="wrap"><h1>' . esc_html__( 'ORBIT Import', 'orbit-import' ) . '</h1><p>' . esc_html__( 'Admin module not loaded.', 'orbit-import' ) . '</p></div>';
			}
		}
	);
	add_management_page(
		__( 'ORBIT Import', 'orbit-import' ),
		__( 'ORBIT Import', 'orbit-import' ),
		OUI_CAP_IMPORT,
		'orbit-import-tools',
		static function () {
			if ( class_exists( 'OUI\\Admin\\Admin' ) ) {
				( new OUI\Admin\Admin() )->render();
			} else {
				echo '<div class="wrap"><h1>' . esc_html__( 'ORBIT Import', 'orbit-import' ) . '</h1><p>' . esc_html__( 'Admin module not loaded.', 'orbit-import' ) . '</p></div>';
			}
		}
	);

	add_submenu_page(
		'users.php',
		__( 'ORBIT Import Settings', 'orbit-import' ),
		__( 'ORBIT Import Settings', 'orbit-import' ),
		OUI_CAP_IMPORT,
		'oui-settings',
		static function () {
			if ( class_exists( 'OUI\\Admin\\Settings' ) ) {
				OUI\Admin\Settings::page();
			}
		}
	);
} );

add_action( 'admin_init', static function () {
	if ( class_exists( 'OUI\\Admin\\Settings' ) ) {
		OUI\Admin\Settings::register();
	}
} );

add_action( 'admin_enqueue_scripts', static function () {
	$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
	if ( ! in_array( $page, array( 'orbit-import', 'orbit-import-tools', 'oui-settings' ), true ) ) {
		return;
	}
	wp_enqueue_style( 'oui-admin', OUI_PLUGIN_URL . 'assets/css/admin.css', array(), OUI_VERSION );
	wp_enqueue_script( 'oui-admin', OUI_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), OUI_VERSION, true );
	wp_localize_script( 'oui-admin', 'OUI', array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'oui_admin' ),
	) );
} );

add_action( 'wp_ajax_oui_upload_csv', static function () {
	if ( ! current_user_can( OUI_CAP_IMPORT ) || ! check_ajax_referer( 'oui_admin', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'Unauthorized', 'orbit-import' ) ), 403 );
	}
	if ( empty( $_FILES['file'] ) || ! isset( $_FILES['file']['tmp_name'] ) ) {
		wp_send_json_error( array( 'message' => __( 'No file uploaded', 'orbit-import' ) ), 400 );
	}
	$file = $_FILES['file'];
	$size = isset( $file['size'] ) ? (int) $file['size'] : 0;
	$max  = apply_filters( 'oui_max_csv_size', 10 * 1024 * 1024 );
	if ( $size <= 0 || $size > $max ) {
		wp_send_json_error( array( 'message' => __( 'File too large', 'orbit-import' ) ), 400 );
	}
	$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
	if ( ! in_array( $ext, array( 'csv', 'xlsx' ), true ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid file type', 'orbit-import' ) ), 400 );
	}
	$uploads = wp_upload_dir();
	$dir = trailingslashit( $uploads['basedir'] ) . 'orbit-import/';
	wp_mkdir_p( $dir );
	$target = $dir . wp_unique_filename( $dir, sanitize_file_name( $file['name'] ) );
	if ( ! move_uploaded_file( $file['tmp_name'], $target ) ) {
		wp_send_json_error( array( 'message' => __( 'Could not save file', 'orbit-import' ) ), 500 );
	}
	$hash = md5_file( $target );
	$job_id = wp_insert_post( array(
		'post_type' => 'orbit_import_job',
		'post_status' => 'publish',
		'post_title' => 'Import ' . current_time( 'mysql' ),
	), true );
	if ( is_wp_error( $job_id ) ) {
		wp_send_json_error( array( 'message' => $job_id->get_error_message() ), 500 );
	}
	$defaults = OUI\Import\Job::default_meta();
	$defaults['_file_path'] = $target;
	$defaults['_file_hash'] = $hash;
	$defaults['_processed'] = 0;
	$defaults['_file_ext'] = $ext;
	// Extract headers
	$headers = array();
	if ( 'csv' === $ext ) {
		$dialect = OUI\Support\CSV::detect_dialect( $target );
		$defaults['_csv_dialect'] = $dialect;
		$gen = OUI\Support\CSV::iterate( $target, $dialect['delimiter'], $dialect['enclosure'], $dialect['escape'] );
		foreach ( $gen as $row ) { $headers = is_array( $row ) ? $row : array(); break; }
	} else {
		$gen = OUI\Support\XLSX::iterate( $target );
		foreach ( $gen as $row ) { $headers = is_array( $row ) ? $row : array(); break; }
	}
	$headers = array_filter( array_map( 'strval', $headers ) );
	foreach ( $defaults as $k => $v ) { update_post_meta( $job_id, $k, $v ); }
	OUI\Import\Job::set_headers( $job_id, $headers );
	OUI\Import\Job::set_status( $job_id, OUI\Import\Job::STATUS_CREATED );
	$map_url = add_query_arg( array( 'page' => 'orbit-import', 'step' => 'map', 'job' => (int) $job_id ), admin_url( 'users.php' ) );
	wp_send_json_success( array( 'job_id' => (int) $job_id, 'redirect' => $map_url ) );
} );

add_action( 'wp_ajax_oui_save_mapping', static function(){
	if ( ! current_user_can( OUI_CAP_IMPORT ) || ! check_ajax_referer( 'oui_admin', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'Unauthorized', 'orbit-import' ) ), 403 );
	}
	$job_id = isset( $_POST['job_id'] ) ? (int) $_POST['job_id'] : 0;
	if ( $job_id <= 0 ) { wp_send_json_error( array( 'message' => __( 'Invalid job', 'orbit-import' ) ), 400 ); }
	$mapping = isset( $_POST['mapping'] ) ? (array) $_POST['mapping'] : array();
	$bulk = isset( $_POST['bulk'] ) ? (array) $_POST['bulk'] : array();
	$clean_mapping = array(); foreach ( $mapping as $k => $v ) { $clean_mapping[ sanitize_key( $k ) ] = sanitize_text_field( (string) $v ); }
	if ( empty( $clean_mapping['email'] ) ) { wp_send_json_error( array( 'message' => __( 'Email mapping is required.', 'orbit-import' ) ), 400 ); }
	$settings = get_option( 'oui_settings', array() ); $require_streams = ! empty( $settings['require_streams'] );
	$bulk_ids = array(); if ( isset( $bulk['ids'] ) && is_array( $bulk['ids'] ) ) { foreach ( $bulk['ids'] as $id ) { $id = (int) $id; if ( $id > 0 ) { $bulk_ids[] = $id; } } }
	if ( $require_streams && empty( $bulk_ids ) && empty( $clean_mapping['streams'] ) ) {
		wp_send_json_error( array( 'message' => __( 'At least CSV streams or bulk streams must be selected.', 'orbit-import' ) ), 400 );
	}
	update_post_meta( $job_id, '_mapping', $clean_mapping );
	update_post_meta( $job_id, '_bulk_stream_ids', $bulk_ids );
	update_post_meta( $job_id, '_bulk_stream_role', isset( $bulk['role'] ) ? sanitize_key( $bulk['role'] ) : 'member' );
	$mode = isset( $bulk['mode'] ) ? sanitize_key( $bulk['mode'] ) : 'csv_only'; if ( ! in_array( $mode, array( 'csv_only', 'append_bulk', 'replace_with_bulk' ), true ) ) { $mode = 'csv_only'; }
	update_post_meta( $job_id, '_stream_assign_mode', $mode );
	update_post_meta( $job_id, '_bulk_stream_apply_existing_only', ! empty( $bulk['existing_only'] ) ? 1 : 0 );
	OUI\Import\Job::set_status( $job_id, OUI\Import\Job::STATUS_MAPPED );
	$dry_url = add_query_arg( array( 'page' => 'orbit-import', 'step' => 'dry-run', 'job' => (int) $job_id ), admin_url( 'users.php' ) );
	wp_send_json_success( array( 'ok' => true, 'redirect' => $dry_url ) );
} );

add_action( 'wp_ajax_oui_dry_run', static function(){
	if ( ! current_user_can( OUI_CAP_IMPORT ) || ! check_ajax_referer( 'oui_admin', 'nonce', false ) ) { wp_send_json_error( array( 'message' => __( 'Unauthorized', 'orbit-import' ) ), 403 ); }
	$job_id = isset( $_POST['job_id'] ) ? (int) $_POST['job_id'] : 0; $result = OUI\Import\Runner::dry_run( $job_id );
	if ( is_wp_error( $result ) ) { wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 ); }
	OUI\Import\Job::set_status( $job_id, OUI\Import\Job::STATUS_DRY_RUN );
	wp_send_json_success( $result );
} );

add_action( 'wp_ajax_oui_run_batch', static function () {
	if ( ! current_user_can( OUI_CAP_IMPORT ) || ! check_ajax_referer( 'oui_admin', 'nonce', false ) ) { wp_send_json_error( array( 'message' => __( 'Unauthorized', 'orbit-import' ) ), 403 ); }
	if ( class_exists( 'OUI\\Import\\Runner' ) ) {
		$job_id = isset( $_POST['job_id'] ) ? (int) $_POST['job_id'] : 0;
		if ( OUI\Import\Job::get_status( $job_id ) !== OUI\Import\Job::STATUS_RUNNING ) { OUI\Import\Job::set_status( $job_id, OUI\Import\Job::STATUS_RUNNING ); }
		$result = OUI\Import\Runner::run_batch( $job_id );
		if ( is_wp_error( $result ) ) { wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 ); }
		if ( isset( $result['percent'] ) && (int) $result['percent'] >= 100 ) { OUI\Import\Job::set_status( $job_id, OUI\Import\Job::STATUS_COMPLETED ); }
		wp_send_json_success( $result );
	}
	wp_send_json_error( array( 'message' => __( 'Runner not available', 'orbit-import' ) ), 500 );
} );

require_once OUI_PLUGIN_DIR . 'includes/Support/class-oui-security.php';
require_once OUI_PLUGIN_DIR . 'includes/Support/class-oui-utils.php';
require_once OUI_PLUGIN_DIR . 'includes/Support/class-oui-csv.php';
require_once OUI_PLUGIN_DIR . 'includes/Support/class-oui-xlsx.php';
require_once OUI_PLUGIN_DIR . 'includes/Integrations/class-oui-buddyboss.php';
require_once OUI_PLUGIN_DIR . 'includes/Integrations/class-oui-otm.php';
require_once OUI_PLUGIN_DIR . 'includes/Import/class-oui-job.php';
require_once OUI_PLUGIN_DIR . 'includes/Import/class-oui-mapper.php';
require_once OUI_PLUGIN_DIR . 'includes/Import/class-oui-logger.php';
require_once OUI_PLUGIN_DIR . 'includes/Import/class-oui-runner.php';
require_once OUI_PLUGIN_DIR . 'includes/Admin/class-oui-admin.php';
require_once OUI_PLUGIN_DIR . 'includes/Admin/class-oui-jobs-table.php';
require_once OUI_PLUGIN_DIR . 'includes/Admin/class-oui-settings.php';
