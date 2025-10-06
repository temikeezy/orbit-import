<?php
namespace OUI\Admin;

use OUI\Import\Job;
use OUI\Import\Runner;

defined( 'ABSPATH' ) || exit;

class Run_Controller {
	public static function start() {
		if ( ! current_user_can( defined( 'OUI_CAP' ) ? OUI_CAP : 'manage_options' ) ) { wp_die( esc_html__( 'Access denied.', 'orbit-import' ) ); }
		check_admin_referer( 'oui_import_start_run' );
		$job_id = isset( $_POST['job'] ) ? (int) $_POST['job'] : 0;
		if ( ! $job_id ) { Upload_Controller::redirect_step( 'upload', 0, 'no_job' ); }
		Job::set_status( $job_id, Job::STATUS_RUNNING );
		Upload_Controller::redirect_step( 'run', $job_id );
	}

	public static function batch() {
		if ( ! current_user_can( defined( 'OUI_CAP' ) ? OUI_CAP : 'manage_options' ) ) { wp_send_json_error( array( 'message' => __( 'Access denied.', 'orbit-import' ) ), 403 ); }
		$job_id = isset( $_POST['job_id'] ) ? (int) $_POST['job_id'] : 0;
		$result = Runner::run_batch( $job_id );
		if ( is_wp_error( $result ) ) { wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 ); }
		$done = isset( $result['percent'] ) && (int) $result['percent'] >= 100;
		if ( $done ) { Job::set_status( $job_id, Job::STATUS_COMPLETED ); }
		wp_send_json_success( $result + array( 'done' => $done ) );
	}
}
