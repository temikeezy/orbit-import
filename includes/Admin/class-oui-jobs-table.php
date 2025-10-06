<?php
namespace OUI\Admin;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Jobs_Table extends \WP_List_Table {
	public function get_columns() {
		return array(
			'title'     => __( 'Job', 'orbit-import' ),
			'processed' => __( 'Processed', 'orbit-import' ),
			'created'   => __( 'Created', 'orbit-import' ),
			'updated'   => __( 'Updated', 'orbit-import' ),
			'errors'    => __( 'Errors', 'orbit-import' ),
			'file'      => __( 'Errors CSV', 'orbit-import' ),
			'date'      => __( 'Date', 'orbit-import' ),
		);
	}
	public function prepare_items() {
		$args = array( 'post_type' => 'orbit_import_job', 'posts_per_page' => 20, 'post_status' => 'any' );
		$q = new \WP_Query( $args );
		$this->items = array();
		foreach ( $q->posts as $p ) {
			$processed = (int) get_post_meta( $p->ID, '_processed', true );
			$totals = (array) get_post_meta( $p->ID, '_totals', true );
			$errors_csv = (string) get_post_meta( $p->ID, '_errors_csv', true );
			$this->items[] = array(
				'ID' => $p->ID,
				'title' => $p->post_title,
				'processed' => $processed,
				'created' => isset( $totals['created'] ) ? (int) $totals['created'] : 0,
				'updated' => isset( $totals['updated'] ) ? (int) $totals['updated'] : 0,
				'errors' => isset( $totals['errors'] ) ? (int) $totals['errors'] : 0,
				'file' => $errors_csv,
				'date' => get_date_from_gmt( $p->post_date_gmt, 'Y-m-d H:i' ),
			);
		}
	}
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'title': return esc_html( $item['title'] );
			case 'processed': return (int) $item['processed'];
			case 'created': return (int) $item['created'];
			case 'updated': return (int) $item['updated'];
			case 'errors': return (int) $item['errors'];
			case 'file':
				if ( ! empty( $item['file'] ) ) {
					$uploads = wp_upload_dir();
					$base = trailingslashit( $uploads['baseurl'] );
					$rel = str_replace( trailingslashit( $uploads['basedir'] ), '', $item['file'] );
					$url = $base . str_replace( '\\', '/', $rel );
					return '<a href="' . esc_url( $url ) . '" class="button">' . esc_html__( 'Download', 'orbit-import' ) . '</a>';
				}
				return '';
			case 'date': return esc_html( $item['date'] );
		}
		return '';
	}
}
