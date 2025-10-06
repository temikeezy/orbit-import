<?php
namespace OUI\Admin;

use OUI\Import\Job;

defined( 'ABSPATH' ) || exit;

class Map_Controller {
	public static function handle() {
		if ( ! current_user_can( defined( 'OUI_CAP' ) ? OUI_CAP : 'manage_options' ) ) { wp_die( esc_html__( 'Access denied.', 'orbit-import' ) ); }
		check_admin_referer( 'oui_import_save_mapping' );
		$job_id = isset( $_POST['job'] ) ? (int) $_POST['job'] : 0;
		if ( ! $job_id ) { Upload_Controller::redirect_step( 'upload', 0, 'no_job' ); }
		$mapping = isset( $_POST['mapping'] ) ? (array) $_POST['mapping'] : array();
		if ( empty( $mapping['email'] ) ) { wp_safe_redirect( add_query_arg( array( 'page' => OUI_PAGE_SLUG, 'step' => 'map', 'job' => $job_id, 'oui_notice' => 'need_email' ), admin_url( 'users.php' ) ) ); exit; }
		$bulk_ids = isset( $_POST['bulk_stream_ids'] ) ? array_map( 'intval', (array) $_POST['bulk_stream_ids'] ) : array();
		$bulk_role = isset( $_POST['bulk_stream_role'] ) ? sanitize_text_field( $_POST['bulk_stream_role'] ) : 'member';
		$mode = isset( $_POST['stream_assign_mode'] ) ? sanitize_text_field( $_POST['stream_assign_mode'] ) : 'csv_only';
		$existing = ! empty( $_POST['bulk_stream_apply_existing_only'] );
		Job::update_meta( $job_id, '_mapping', $mapping );
		Job::update_meta( $job_id, '_bulk_stream_ids', $bulk_ids );
		Job::update_meta( $job_id, '_bulk_stream_role', $bulk_role );
		Job::update_meta( $job_id, '_stream_assign_mode', $mode );
		Job::update_meta( $job_id, '_bulk_stream_apply_existing_only', $existing );
		Job::set_status( $job_id, Job::STATUS_MAPPED );
		Upload_Controller::redirect_step( 'dry-run', $job_id, 'mapped' );
	}
}
