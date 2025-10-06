<?php
namespace OUI\Import;

use OUI\Support\CSV;

defined( 'ABSPATH' ) || exit;

class Logger {
	private $rows = array();
	private $errors = array();

	public function log_row( $index, $status, $message ) {
		$this->rows[] = array( 'index' => (int) $index, 'status' => sanitize_key( $status ), 'message' => sanitize_text_field( $message ) );
	}

	public function log_error_row( array $row, $message ) {
		$this->errors[] = array( 'row' => $row, 'message' => (string) $message );
	}

	public function has_errors() {
		return ! empty( $this->errors );
	}

	public function error_count() {
		return count( $this->errors );
	}

	public function export_errors_csv( $filepath ) {
		if ( empty( $this->errors ) ) {
			return '';
		}
		$fh = fopen( $filepath, 'w' );
		if ( ! $fh ) {
			return '';
		}
		// Write header
		fputcsv( $fh, array( 'message', 'row' ) );
		foreach ( $this->errors as $e ) {
			fputcsv( $fh, array( $e['message'], wp_json_encode( $e['row'] ) ) );
		}
		fclose( $fh );
		return $filepath;
	}

	public function get_rows() {
		return $this->rows;
	}
}
