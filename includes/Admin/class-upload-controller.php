<?php
namespace OUI\Admin;

use OUI\Import\Job;
use OUI\Support\CSV;
use OUI\Support\XLSX;

defined( 'ABSPATH' ) || exit;

class Upload_Controller {
	public static function handle() {
		if ( ! current_user_can( defined( 'OUI_CAP' ) ? OUI_CAP : 'manage_options' ) ) { wp_die( esc_html__( 'Access denied.', 'orbit-import' ) ); }
		check_admin_referer( 'oui_import_upload' );
		if ( empty( $_FILES['import_file']['name'] ) ) { self::redirect_step( 'upload', 0, 'no_file' ); }
		$uploaded = wp_handle_upload( $_FILES['import_file'], array( 'test_form' => false ) );
		if ( isset( $uploaded['error'] ) ) { self::redirect_step( 'upload', 0, 'upload_error' ); }
		$path = $uploaded['file']; $ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, array( 'csv', 'xlsx' ), true ) ) { self::redirect_step( 'upload', 0, 'bad_type' ); }
		if ( $ext === 'csv' ) { $dialect = CSV::detect_dialect( $path ); $headers = CSV::read_headers( $path, $dialect ); $extra = array( '_csv_dialect' => $dialect ); $filetype = 'csv'; }
		else { $headers = XLSX::read_headers( $path ); $extra = array(); $filetype = 'xlsx'; }
		$job_id = Job::create_from_upload( array( 'file_path' => $path, 'file_type' => $filetype, 'headers' => $headers ) + $extra );
		if ( ! $job_id ) { self::redirect_step( 'upload', 0, 'job_failed' ); }
		self::redirect_step( 'map', $job_id, 'uploaded' );
	}
	public static function redirect_step( $step, $job_id = 0, $notice = '' ) {
		$args = array( 'page' => defined( 'OUI_PAGE_SLUG' ) ? OUI_PAGE_SLUG : 'orbit-import', 'step' => $step ); if ( $job_id ) { $args['job'] = (int) $job_id; } if ( $notice ) { $args['oui_notice'] = $notice; }
		wp_safe_redirect( add_query_arg( $args, admin_url( 'users.php' ) ) );
		exit;
	}
}
