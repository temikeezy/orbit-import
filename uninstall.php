<?php
// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete options
delete_option( 'ogmi_settings' );

// Clean transient/upload remnants
global $wpdb;
$transients = $wpdb->get_results( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_transient_ogmi_file_%'" );
foreach ( $transients as $transient ) {
	$file_id = str_replace( '_transient_ogmi_file_', '', $transient->option_name );
	$file_data = get_transient( 'ogmi_file_' . $file_id );
	if ( $file_data && isset( $file_data['file_path'] ) && file_exists( $file_data['file_path'] ) ) {
		@unlink( $file_data['file_path'] );
	}
	delete_transient( 'ogmi_file_' . $file_id );
}

// Optionally remove upload dir contents (keep dir)
$upload_dir = wp_upload_dir();
$import_dir = trailingslashit( $upload_dir['basedir'] ) . 'orbit-group-import/';
if ( is_dir( $import_dir ) ) {
	$files = glob( $import_dir . '*' );
	if ( is_array( $files ) ) {
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				@unlink( $file );
			}
		}
	}
}


