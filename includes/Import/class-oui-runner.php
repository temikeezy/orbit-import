<?php
namespace OUI\Import;

use OUI\Integrations\BuddyBoss;
use OUI\Integrations\OTM;
use OUI\Support\CSV as CSVSupport;
use OUI\Support\Utils;

defined( 'ABSPATH' ) || exit;

class Runner {
	public static function dry_run( $job_id ) {
		$job_id = (int) $job_id;
		if ( $job_id <= 0 ) { return new \WP_Error( 'invalid_job', __( 'Invalid job.', 'orbit-import' ) ); }
		$file = get_post_meta( $job_id, '_file_path', true );
		if ( empty( $file ) || ! file_exists( $file ) ) { return new \WP_Error( 'no_file', __( 'Job file missing.', 'orbit-import' ) ); }
		$dialect = CSVSupport::detect_dialect( $file );
		$gen = CSVSupport::iterate( $file, $dialect['delimiter'], $dialect['enclosure'], $dialect['escape'] );
		$headers = array(); $firstRow = true; $created = 0; $updated = 0; $invalid = 0; $unknown_streams = array(); $rows_total = 0;
		foreach ( $gen as $row ) {
			if ( $firstRow ) { $headers = $row; $firstRow = false; continue; }
			$rows_total++;
			$emailIdx = array_search( 'email', array_map( 'strtolower', $headers ), true );
			$streamsIdx = array_search( 'streams', array_map( 'strtolower', $headers ), true );
			$email = $emailIdx !== false && isset( $row[ $emailIdx ] ) ? sanitize_email( $row[ $emailIdx ] ) : '';
			if ( empty( $email ) || ! is_email( $email ) ) { $invalid++; continue; }
			if ( email_exists( $email ) ) { $updated++; } else { $created++; }
			if ( BuddyBoss::is_active() && $streamsIdx !== false && isset( $row[ $streamsIdx ] ) ) {
				$names = array_filter( array_map( 'trim', explode( '|', (string) $row[ $streamsIdx ] ) ) );
				$map = BuddyBoss::name_to_id_map();
				foreach ( $names as $n ) { if ( $n !== '' && ! isset( $map[ $n ] ) ) { $unknown_streams[ $n ] = true; } }
			}
		}
		update_post_meta( $job_id, '_totals', array( 'created' => $created, 'updated' => $updated, 'invalid' => $invalid, 'errors' => $invalid, 'rows_total' => $rows_total ) );
		return array( 'created' => $created, 'updated' => $updated, 'invalid' => $invalid, 'unknown_streams' => array_keys( $unknown_streams ), 'rows_total' => $rows_total );
	}

	public static function run_batch( $job_id ) {
		$job_id = (int) $job_id;
		if ( $job_id <= 0 ) { return new \WP_Error( 'invalid_job', __( 'Invalid job.', 'orbit-import' ) ); }
		$file = get_post_meta( $job_id, '_file_path', true );
		if ( empty( $file ) || ! file_exists( $file ) ) { return new \WP_Error( 'no_file', __( 'Job file missing.', 'orbit-import' ) ); }

		$processed = (int) get_post_meta( $job_id, '_processed', true );
		$totals    = (array) get_post_meta( $job_id, '_totals', true );
		$totals    = wp_parse_args( $totals, array( 'created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0, 'rows_total' => 0 ) );
		$mapping   = (array) get_post_meta( $job_id, '_mapping', true );
		$settings  = get_option( 'oui_settings', array() );
		$settings  = wp_parse_args( $settings, array( 'default_wp_role' => 'subscriber', 'default_otm_role' => 'otm_intern', 'batch_size' => 50, 'welcome_email' => 0, 'autocreate_streams' => 0 ) );

		$bulk_ids  = (array) get_post_meta( $job_id, '_bulk_stream_ids', true );
		$bulk_role = get_post_meta( $job_id, '_bulk_stream_role', true ); $bulk_role = in_array( $bulk_role, array( 'member','mod','admin' ), true ) ? $bulk_role : 'member';
		$mode      = get_post_meta( $job_id, '_stream_assign_mode', true ); $mode = in_array( $mode, array( 'csv_only','append_bulk','replace_with_bulk' ), true ) ? $mode : 'csv_only';
		$apply_bulk_existing_only = (bool) get_post_meta( $job_id, '_bulk_stream_apply_existing_only', true );

		$dialect = CSVSupport::detect_dialect( $file );
		$gen = CSVSupport::iterate( $file, $dialect['delimiter'], $dialect['enclosure'], $dialect['escape'] );

		$index = -1; $created = 0; $updated = 0; $skipped = 0; $errors = 0; $count = 0;
		$headers = array(); $headerMap = array(); $batch_size = (int) $settings['batch_size'];
		$logger = new Logger();

		foreach ( $gen as $row ) {
			$index++;
			if ( 0 === $index ) { $headers = is_array( $row ) ? $row : array(); $headerMap = array(); foreach ( $headers as $i => $h ) { $headerMap[ strtolower( trim( (string) $h ) ) ] = (int) $i; } continue; }
			if ( $index <= $processed ) { continue; }

			$getVal = function( $key ) use ( $mapping, $headerMap, $row ) {
				$col = isset( $mapping[ $key ] ) && $mapping[ $key ] !== '' ? strtolower( $mapping[ $key ] ) : $key;
				if ( isset( $headerMap[ $col ] ) && isset( $row[ $headerMap[ $col ] ] ) ) { return trim( (string) $row[ $headerMap[ $col ] ] ); }
				return '';
			};

			$email = sanitize_email( $getVal( 'email' ) );
			if ( empty( $email ) || ! is_email( $email ) ) { $skipped++; $errors++; $logger->log_error_row( $row, 'Invalid email' ); $count++; if ( $count >= $batch_size ) { break; } continue; }

			$user = get_user_by( 'email', $email );
			$is_new = false; $user_id = 0;

			$username = $getVal( 'username' ); if ( '' === $username ) { $username = sanitize_user( strstr( $email, '@', true ) ); }
			if ( '' === $username ) { $username = sanitize_user( current_time( 'timestamp' ) . '_' . wp_generate_password( 6, false ) ); }
			$username = self::unique_username( $username );

			$first_name = $getVal( 'first_name' ); $last_name  = $getVal( 'last_name' ); $display_name = $getVal( 'display_name' );
			$wp_role = $getVal( 'wp_role' ); $wp_role = $wp_role !== '' ? sanitize_key( $wp_role ) : $settings['default_wp_role'];
			$otm_role = $getVal( 'otm_role' ); $otm_role = $otm_role !== '' ? sanitize_key( $otm_role ) : $settings['default_otm_role'];
			$user_status = $getVal( 'user_status' );
			$password = $getVal( 'password' ); if ( '' === $password ) { $password = wp_generate_password( 20, true, true ); }

			$meta = array(); foreach ( $headerMap as $h => $i ) { if ( strpos( $h, 'meta:' ) === 0 && isset( $row[ $i ] ) ) { $key = sanitize_key( substr( $h, 5 ) ); $meta[ $key ] = sanitize_text_field( (string) $row[ $i ] ); } }

			if ( $user && $user->ID ) {
				$user_id = (int) $user->ID; $update = array( 'ID' => $user_id );
				if ( $first_name !== '' ) { $update['first_name'] = $first_name; }
				if ( $last_name !== '' )  { $update['last_name']  = $last_name; }
				if ( $display_name !== '' ) { $update['display_name'] = $display_name; }
				wp_update_user( $update ); if ( $wp_role ) { $wp_user = new \WP_User( $user_id ); $wp_user->set_role( $wp_role ); }
				$updated++;
			} else {
				$new_user = array( 'user_login' => $username, 'user_email' => $email, 'user_pass' => $password, 'first_name' => $first_name, 'last_name' => $last_name, 'display_name' => $display_name !== '' ? $display_name : ( $first_name !== '' ? $first_name . ' ' . $last_name : $username ), 'role' => $wp_role );
				$user_id = wp_insert_user( $new_user );
				if ( is_wp_error( $user_id ) ) { $skipped++; $errors++; $logger->log_error_row( $row, $user_id->get_error_message() ); $count++; if ( $count >= $batch_size ) { break; } continue; }
				$is_new = true; $created++;
			}

			foreach ( $meta as $k => $v ) { update_user_meta( $user_id, $k, $v ); }
			if ( $user_status !== '' ) { update_user_meta( $user_id, 'user_status', (int) $user_status ); }
			OTM::apply_role( $user_id, $otm_role );

			$csv_streams_raw = $getVal( 'streams' ); $csv_roles_raw = $getVal( 'stream_roles' );
			$csv_stream_names = array_filter( array_map( 'trim', explode( '|', (string) $csv_streams_raw ) ) );
			$csv_role_names   = array_filter( array_map( 'trim', explode( '|', (string) $csv_roles_raw ) ) );
			$csv_roles_aligned = array(); foreach ( $csv_stream_names as $i => $_ ) { $csv_roles_aligned[ $i ] = ( isset( $csv_role_names[ $i ] ) && in_array( $csv_role_names[ $i ], array( 'member','mod','admin' ), true ) ) ? $csv_role_names[ $i ] : 'member'; }

			$csv_group_ids = array(); if ( BuddyBoss::is_active() && ! empty( $csv_stream_names ) ) { $csv_group_ids = BuddyBoss::resolve_ids_from_names( $csv_stream_names, ! empty( $settings['autocreate_streams'] ) ); }

			$target_ids = array(); if ( 'replace_with_bulk' === $mode ) { $target_ids = array_map( 'intval', $bulk_ids ); }
			elseif ( 'append_bulk' === $mode ) { $target_ids = array_values( array_unique( array_map( 'intval', array_merge( $csv_group_ids, $bulk_ids ) ) ) ); }
			else { $target_ids = array_map( 'intval', $csv_group_ids ); }
			if ( $apply_bulk_existing_only && $is_new && 'csv_only' !== $mode ) { $target_ids = array_map( 'intval', $csv_group_ids ); }

			if ( BuddyBoss::is_active() && ! empty( $target_ids ) ) {
				foreach ( $target_ids as $gid ) {
					if ( $gid <= 0 ) { continue; }
					BuddyBoss::join_group( $gid, $user_id );
					$role = 'member'; if ( in_array( $gid, array_map( 'intval', $bulk_ids ), true ) && 'csv_only' !== $mode ) { $role = $bulk_role; } else { $idx = array_search( $gid, $csv_group_ids, true ); if ( false !== $idx && isset( $csv_roles_aligned[ $idx ] ) ) { $role = $csv_roles_aligned[ $idx ]; } }
					BuddyBoss::promote( $gid, $user_id, $role );
				}
			}

			if ( $is_new && ! empty( $settings['welcome_email'] ) ) { wp_new_user_notification( $user_id, null, 'user' ); }

			$count++; if ( $count >= $batch_size ) { break; }
		}

		$processed += $count; update_post_meta( $job_id, '_processed', $processed );
		$totals['created'] += $created; $totals['updated'] += $updated; $totals['skipped'] += $skipped; $totals['errors'] += $errors; update_post_meta( $job_id, '_totals', $totals );

		if ( $logger->has_errors() ) { $uploads = wp_upload_dir(); $dir = trailingslashit( $uploads['basedir'] ) . 'orbit-import/'; wp_mkdir_p( $dir ); $path = $dir . 'errors-job-' . $job_id . '-' . time() . '.csv'; $csv_path = $logger->export_errors_csv( $path ); if ( $csv_path ) { update_post_meta( $job_id, '_errors_csv', $csv_path ); } }

		// Try to estimate percent if rows_total known
		$percent = 0; if ( ! empty( $totals['rows_total'] ) ) { $percent = min( 100, (int) floor( ( $processed / (int) $totals['rows_total'] ) * 100 ) ); }
		return array( 'processed' => $processed, 'created' => $created, 'updated' => $updated, 'skipped' => $skipped, 'errors' => $errors, 'percent' => $percent );
	}

	private static function unique_username( $base ) { $base = sanitize_user( $base, true ); if ( $base === '' ) { $base = 'user'; } $user = get_user_by( 'login', $base ); if ( ! $user ) { return $base; } $suffix = 1; while ( username_exists( $base . '_' . $suffix ) ) { $suffix++; } return $base . '_' . $suffix; }
}
