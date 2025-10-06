<?php
namespace OUI\Import;

defined( 'ABSPATH' ) || exit;

class Job {
	const POST_TYPE = 'orbit_import_job';

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
			'_mapping' => array(),
			'_file_path' => '',
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
}
