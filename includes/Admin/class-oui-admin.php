<?php
namespace OUI\Admin;

use OUI\Import\Job;

defined( 'ABSPATH' ) || exit;

class Admin {
	public function render() {
		$step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : 'upload';
		$job_id = isset( $_GET['job'] ) ? (int) $_GET['job'] : ( isset( $_GET['job_id'] ) ? (int) $_GET['job_id'] : 0 );

		self::assert_step_allowed( $step, $job_id );

		echo '<div class="wrap"><h1>' . esc_html__( 'ORBIT Import', 'orbit-import' ) . '</h1>';
		if ( isset( $_GET['oui_notice'] ) ) {
			$notice = sanitize_key( $_GET['oui_notice'] );
			$messages = array(
				'uploaded' => __( 'File uploaded. Proceed to mapping.', 'orbit-import' ),
				'mapped' => __( 'Mapping saved. Preview ready.', 'orbit-import' ),
				'need_file' => __( 'Please upload a file first.', 'orbit-import' ),
				'need_mapping' => __( 'Please save mapping to continue.', 'orbit-import' ),
				'need_dryrun' => __( 'Please complete the preview to continue.', 'orbit-import' ),
				'upload_error' => __( 'Upload error. Please try again.', 'orbit-import' ),
				'bad_type' => __( 'Unsupported file type.', 'orbit-import' ),
				'no_file' => __( 'No file was selected.', 'orbit-import' ),
			);
			if ( isset( $messages[ $notice ] ) ) { echo '<div class="notice notice-info"><p>' . esc_html( $messages[ $notice ] ) . '</p></div>'; }
		}

		// Inline stepper styles to ensure rendering even if assets are cached
		echo '<style>
		.oui-header{margin:6px 0 10px}
		.oui-steps{list-style:none;margin:0;padding:0;display:flex;gap:20px;align-items:center}
		.oui-step{display:flex;align-items:center;gap:8px;opacity:.65}
		.oui-step.current,.oui-step.completed{opacity:1}
		.oui-step-index{width:26px;height:26px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:600;background:#e2e8f0;color:#111}
		.oui-step.current .oui-step-index{background:#2271b1;color:#fff}
		.oui-step.completed .oui-step-index{background:#16a34a;color:#fff}
		.oui-step-label{font-weight:600;text-decoration:none;color:#1d2327}
		.oui-step.disabled .oui-step-label{color:#7a7a7a;cursor:default}
		</style>';

		echo '<div class="oui-header">';
		$this->stepper( $step, $job_id );
		echo '</div>';
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

	public static function assert_step_allowed( string $step, ?int $job_id ): void {
		if ( ! current_user_can( defined('OUI_CAP') ? OUI_CAP : 'manage_options' ) ) { wp_die( esc_html__( 'Access denied.', 'orbit-import' ) ); }
		if ( $step === 'upload' ) { return; }
		if ( ! $job_id ) { self::redirect_step( 'upload', 0, 'need_file' ); }
		$status = Job::get_status( $job_id );
		switch ( $step ) {
			case 'map':
				if ( ! $status || ! Job::has_file( $job_id ) ) { self::redirect_step( 'upload', $job_id, 'need_file' ); }
				break;
			case 'dry-run':
				if ( $status !== Job::STATUS_MAPPED ) { self::redirect_step( 'map', $job_id, 'need_mapping' ); }
				break;
			case 'run':
				if ( ! in_array( $status, array( Job::STATUS_DRY_RUN, Job::STATUS_RUNNING ), true ) ) { self::redirect_step( 'dry-run', $job_id, 'need_dryrun' ); }
				break;
			case 'report':
				if ( ! in_array( $status, array( Job::STATUS_COMPLETED, Job::STATUS_CANCELLED ), true ) ) { self::redirect_step( 'run', $job_id, 'need_run' ); }
				break;
		}
	}

	private static function redirect_step( string $step, int $job_id = 0, string $notice = '' ): void {
		$args = array( 'page' => defined('OUI_PAGE_SLUG') ? OUI_PAGE_SLUG : 'orbit-import', 'step' => $step );
		if ( $job_id ) { $args['job'] = $job_id; }
		if ( $notice ) { $args['oui_notice'] = $notice; }
		wp_safe_redirect( add_query_arg( $args, admin_url( 'users.php' ) ) );
		exit;
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
			$state = 'disabled'; if ( $i < $current_index ) { $state = 'completed'; } elseif ( $i === $current_index ) { $state = 'current'; }
			$index = $i + 1; $label = $s['label']; $url = add_query_arg( array( 'page' => OUI_PAGE_SLUG, 'step' => $s['slug'], 'job' => (int) $job_id ), admin_url( 'users.php' ) );
			echo '<li class="oui-step oui-' . esc_attr( $state ) . '">';
			echo '<span class="oui-step-index">' . esc_html( (string) $index ) . '</span>';
			if ( 'disabled' === $state ) { echo '<span class="oui-step-label">' . esc_html( $label ) . '</span>'; }
			else { echo '<a class="oui-step-label" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>'; }
			echo '</li>';
		}
		echo '</ol>';
	}

	private function view( $name, $job_id ) {
		$file = plugin_dir_path( dirname( __DIR__ ) ) . 'includes/Admin/views/' . $name . '.php';
		if ( file_exists( $file ) ) { include $file; }
	}
}
