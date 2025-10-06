<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$job_id = isset( $_GET['job_id'] ) ? (int) $_GET['job_id'] : 0;
$processed = $job_id ? (int) get_post_meta( $job_id, '_processed', true ) : 0;
$totals = $job_id ? (array) get_post_meta( $job_id, '_totals', true ) : array();
$errors_csv = $job_id ? (string) get_post_meta( $job_id, '_errors_csv', true ) : '';
$errors_url = '';
if ( $errors_csv ) {
	$uploads = wp_upload_dir();
	$base = trailingslashit( $uploads['baseurl'] );
	$rel = str_replace( trailingslashit( $uploads['basedir'] ), '', $errors_csv );
	$errors_url = $base . str_replace( '\\', '/', $rel );
}
?>
<div class="oui-panel">
	<h2><?php echo esc_html__( 'Report', 'orbit-import' ); ?></h2>
	<ul>
		<li><?php echo esc_html__( 'Processed:', 'orbit-import' ); ?> <span id="oui-report-processed"><?php echo (int) $processed; ?></span></li>
		<li><?php echo esc_html__( 'Errors:', 'orbit-import' ); ?> <span id="oui-report-errors"><?php echo isset( $totals['errors'] ) ? (int) $totals['errors'] : 0; ?></span></li>
	</ul>
	<?php if ( $errors_url ) : ?>
		<p><a class="button" id="oui-download-errors" href="<?php echo esc_url( $errors_url ); ?>"><?php echo esc_html__( 'Download Error CSV', 'orbit-import' ); ?></a></p>
	<?php endif; ?>
</div>
