<?php
namespace OUI\Admin;

use OUI\Import\Job;

defined( 'ABSPATH' ) || exit;

class Admin {
	public function render() {
		$step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : 'upload';
		$job_id = isset( $_GET['job'] ) ? (int) $_GET['job'] : ( isset( $_GET['job_id'] ) ? (int) $_GET['job_id'] : 0 );

		$allowed = $this->assert_step_allowed( $step, $job_id );
		if ( ! $allowed['ok'] ) {
			$redirect = $this->step_url( $allowed['redirect'], $job_id );
			add_action( 'admin_notices', function() use ( $allowed ) {
				echo '<div class="notice notice-warning"><p>' . esc_html( $allowed['message'] ) . '</p></div>';
			} );
			wp_safe_redirect( $redirect );
			exit;
		}

		echo '<div class="wrap"><h1>' . esc_html__( 'ORBIT Import', 'orbit-import' ) . '</h1>';
		$this->stepper( $step, $job_id );
		echo '<div class="oui-step-content">';
		switch ( $step ) {
			case 'map': $this->view( 'map', $job_id ); break;
			case 'dry-run': $this->view( 'dry-run', $job_id ); break;
			case 'run': $this->view( 'run', $job_id ); break;
			case 'report': $this->view( 'report', $job_id ); break;
			case 'upload':
			default: $this->view( 'upload', $job_id );
		}
		echo '</div></div>';
	}

	private function stepper( $current, $job_id ) {
		$steps = array(
			array( 'slug' => 'upload',  'label' => __( 'Upload', 'orbit-import' ) ),
			array( 'slug' => 'map',     'label' => __( 'Map & Streams', 'orbit-import' ) ),
			array( 'slug' => 'dry-run', 'label' => __( 'Preview Import', 'orbit-import' ) ),
			array( 'slug' => 'run',     'label' => __( 'Run', 'orbit-import' ) ),
			array( 'slug' => 'report',  'label' => __( 'Report', 'orbit-import' ) ),
		);
		$slugs = array_map( function( $s ){ return $s['slug']; }, $steps );
		$current_index = max( 0, array_search( $current, $slugs, true ) );

		echo '<ol class="oui-steps">';
		foreach ( $steps as $i => $s ) {
			$state = 'disabled';
			if ( $i < $current_index ) { $state = 'completed'; }
			elseif ( $i === $current_index ) { $state = 'current'; }
			$index = $i + 1;
			$label = $s['label'];
			$url = $this->step_url( $s['slug'], $job_id );
			echo '<li class="oui-step oui-' . esc_attr( $state ) . '">';
			echo '<span class="oui-step-index">' . esc_html( (string) $index ) . '</span>';
			if ( 'disabled' === $state ) {
				echo '<span class="oui-step-label">' . esc_html( $label ) . '</span>';
			} else {
				echo '<a class="oui-step-label" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
			}
			echo '</li>';
		}
		echo '</ol>';
	}

	private function view( $name, $job_id ) {
		$file = plugin_dir_path( dirname( __DIR__ ) ) . 'includes/Admin/views/' . $name . '.php';
		if ( file_exists( $file ) ) { include $file; }
	}

	private function step_url( $step, $job_id ) {
		$args = array( 'page' => 'orbit-import', 'step' => $step );
		if ( $job_id ) { $args['job'] = (int) $job_id; }
		return add_query_arg( $args, admin_url( 'users.php' ) );
	}

	private function assert_step_allowed( $step, $job_id ) {
		if ( 'upload' === $step ) { return array( 'ok' => true ); }
		if ( ! $job_id || get_post_type( $job_id ) !== Job::POST_TYPE ) {
			return array( 'ok' => false, 'redirect' => 'upload', 'message' => __( 'Please upload a file to start.', 'orbit-import' ) );
		}
		$status = Job::get_status( $job_id );
		switch ( $step ) {
			case 'map':
				$has_file = (bool) get_post_meta( $job_id, '_file_path', true );
				$headers = Job::get_headers( $job_id );
				if ( ! $has_file ) { return array( 'ok' => false, 'redirect' => 'upload', 'message' => __( 'Upload a file first.', 'orbit-import' ) ); }
				if ( empty( $headers ) ) { return array( 'ok' => false, 'redirect' => 'upload', 'message' => __( 'Could not read headers; please re-upload.', 'orbit-import' ) ); }
				return array( 'ok' => true );
			case 'dry-run':
				if ( $status !== Job::STATUS_MAPPED ) { return array( 'ok' => false, 'redirect' => 'map', 'message' => __( 'Save mapping to continue.', 'orbit-import' ) ); }
				return array( 'ok' => true );
			case 'run':
				if ( $status !== Job::STATUS_DRY_RUN && $status !== Job::STATUS_RUNNING ) { return array( 'ok' => false, 'redirect' => 'dry-run', 'message' => __( 'Complete preview to continue.', 'orbit-import' ) ); }
				return array( 'ok' => true );
			case 'report':
				if ( ! in_array( $status, array( Job::STATUS_COMPLETED, Job::STATUS_CANCELLED ), true ) ) { return array( 'ok' => false, 'redirect' => 'run', 'message' => __( 'Finish or cancel the run to view the report.', 'orbit-import' ) ); }
				return array( 'ok' => true );
		}
		return array( 'ok' => false, 'redirect' => 'upload', 'message' => __( 'Unknown step.', 'orbit-import' ) );
	}
}
