<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OGMI_Import_Scheduler {
	const JOB_PREFIX = 'ogmi_job_';

	public function __construct() {
		// Action Scheduler handler
		add_action( 'ogmi/as_run_job', array( $this, 'run_job' ), 10, 1 );
		// WP-Cron handler
		add_action( 'ogmi_cron_run_job', array( $this, 'run_job' ), 10, 1 );
	}

	public function schedule_job( $args ) {
		$job_id = wp_generate_uuid4();
		$status = array(
			'id'        => $job_id,
			'created'   => time(),
			'group_id'  => (int) $args['group_id'],
			'file_id'   => sanitize_text_field( $args['file_id'] ),
			'mapping'   => (array) $args['mapping'],
			'total'     => 0,
			'processed' => 0,
			'created_c' => 0,
			'updated_c' => 0,
			'skipped_c' => 0,
			'errors_c'  => 0,
			'done'      => false,
			'error'     => '',
		);
		set_transient( self::JOB_PREFIX . $job_id, $status, 12 * HOUR_IN_SECONDS );

		$batch_size = (int) apply_filters( 'ogmi_scheduler_batch_size', 200 );
		$use_as = function_exists( 'as_enqueue_async_action' );
		$hook = $use_as ? 'ogmi/as_run_job' : 'ogmi_cron_run_job';

		if ( $use_as ) {
			as_enqueue_async_action( $hook, array( 'job_id' => $job_id, 'batch_size' => $batch_size ), apply_filters( 'ogmi_scheduler_group', 'ogmi' ) );
		} else {
			wp_schedule_single_event( time() + 1, $hook, array( $job_id, $batch_size ) );
		}

		return $job_id;
	}

	public function get_status( $job_id ) {
		$status = get_transient( self::JOB_PREFIX . $job_id );
		if ( ! $status ) {
			return new WP_Error( 'job_not_found', __( 'Job not found or expired', OGMI_TEXT_DOMAIN ) );
		}
		return $status;
	}

	public function run_job( $job_id, $batch_size = 200 ) {
		$status = $this->get_status( $job_id );
		if ( is_wp_error( $status ) || ! empty( $status['done'] ) ) {
			return;
		}

		$file_processor = new OGMI_File_Processor();
		$file = get_transient( 'ogmi_file_' . $status['file_id'] );
		if ( ! $file ) {
			$status['error'] = 'File expired or missing';
			$status['done'] = true;
			set_transient( self::JOB_PREFIX . $job_id, $status, 12 * HOUR_IN_SECONDS );
			return;
		}

		// Initialize totals
		if ( empty( $status['total'] ) && ! empty( $file['total_rows'] ) ) {
			$status['total'] = (int) $file['total_rows'];
		}

		$offset = (int) $status['processed'];
		$result = $file_processor->process_batch( $status['file_id'], $status['mapping'], $batch_size, $offset, $status['group_id'] );
		if ( is_wp_error( $result ) ) {
			$status['error'] = $result->get_error_message();
			$status['done'] = true;
			set_transient( self::JOB_PREFIX . $job_id, $status, 12 * HOUR_IN_SECONDS );
			return;
		}

		$status['processed'] += (int) $result['processed'];
		$status['created_c'] += (int) $result['created'];
		$status['updated_c'] += (int) $result['updated'];
		$status['skipped_c'] += (int) $result['skipped'];
		$status['errors_c']  += (int) $result['errors'];
		$status['done'] = ! (bool) $result['has_more'];
		set_transient( self::JOB_PREFIX . $job_id, $status, 12 * HOUR_IN_SECONDS );

		if ( ! $status['done'] ) {
			// Queue next chunk
			$use_as = function_exists( 'as_enqueue_async_action' );
			$hook = $use_as ? 'ogmi/as_run_job' : 'ogmi_cron_run_job';
			if ( $use_as ) {
				as_enqueue_async_action( $hook, array( 'job_id' => $job_id, 'batch_size' => $batch_size ), apply_filters( 'ogmi_scheduler_group', 'ogmi' ) );
			} else {
				wp_schedule_single_event( time() + 1, $hook, array( $job_id, $batch_size ) );
			}
		}
	}
}


