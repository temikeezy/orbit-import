<?php
namespace OUI\Import;

defined( 'ABSPATH' ) || exit;

class Job {
	const POST_TYPE = 'orbit_import_job';

	const STATUS_CREATED = 'created';
	const STATUS_MAPPED = 'mapped';
	const STATUS_DRY_RUN = 'dry_run_complete';
	const STATUS_RUNNING = 'running';
	const STATUS_COMPLETED = 'completed';
	const STATUS_CANCELLED = 'cancelled';

	public static function register_cpt() {
		register_post_type( self::POST_TYPE, array(
			'label' => __( 'Import Jobs', 'orbit-import' ),
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => false,
			'supports' => array( 'title' ),
		) );
	}

	public static function default_meta() {
		return array(
			'_status' => self::STATUS_CREATED,
			'_headers' => array(),
			'_file_path' => '',
			'_file_hash' => '',
			'_file_ext' => 'csv',
			'_csv_dialect' => array(),
			'_totals' => array(),
			'_processed' => 0,
			'_errors_csv' => '',
			'_batch_size' => 50,
			'_bulk_stream_ids' => array(),
			'_bulk_stream_role' => 'member',
			'_stream_assign_mode' => 'csv_only',
			'_bulk_stream_apply_existing_only' => false,
			'_settings_snapshot' => array(),
		);
	}

	public static function set_status( $job_id, $status ) { update_post_meta( (int) $job_id, '_status', sanitize_key( $status ) ); }
	public static function get_status( $job_id ) { return get_post_meta( (int) $job_id, '_status', true ); }
	public static function set_headers( $job_id, array $headers ) { update_post_meta( (int) $job_id, '_headers', array_values( $headers ) ); }
	public static function get_headers( $job_id ) { $h = get_post_meta( (int) $job_id, '_headers', true ); return is_array( $h ) ? $h : array(); }
	public static function has_file( $job_id ) { $p = get_post_meta( (int) $job_id, '_file_path', true ); return ! empty( $p ) && file_exists( $p ); }
	public static function update_meta( $job_id, $key, $value ) { update_post_meta( (int) $job_id, $key, $value ); }

	public static function create_from_upload( array $args ) {
		$job_id = wp_insert_post( array(
			'post_type' => self::POST_TYPE,
			'post_status' => 'publish',
			'post_title' => 'Import ' . current_time( 'mysql' ),
		), true );
		if ( is_wp_error( $job_id ) ) { return 0; }
		$meta = self::default_meta();
		if ( isset( $args['file_path'] ) ) { $meta['_file_path'] = (string) $args['file_path']; }
		if ( isset( $args['file_type'] ) ) { $meta['_file_ext'] = (string) $args['file_type']; }
		if ( isset( $args['headers'] ) ) { $meta['_headers'] = array_values( (array) $args['headers'] ); }
		if ( isset( $args['_csv_dialect'] ) ) { $meta['_csv_dialect'] = (array) $args['_csv_dialect']; }
		foreach ( $meta as $k => $v ) { update_post_meta( $job_id, $k, $v ); }
		return (int) $job_id;
	}
}
