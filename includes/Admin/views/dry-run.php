<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$job_id = isset( $_GET['job'] ) ? (int) $_GET['job'] : 0;
$status = $job_id ? get_post_meta( $job_id, '_status', true ) : '';
if ( $job_id && $status === 'mapped' ) {
	// Auto-dry-run
	$result = OUI\Import\Runner::dry_run( $job_id );
	if ( ! is_wp_error( $result ) ) {
		OUI\Import\Job::set_status( $job_id, OUI\Import\Job::STATUS_DRY_RUN );
	}
}
?>
<div class="oui-panel">
	<h2><?php echo esc_html__( 'Preview Import', 'orbit-import' ); ?></h2>
	<p><?php echo esc_html__( 'This is a zero-risk preview to catch problems early—no database writes, no group joins, no emails.', 'orbit-import' ); ?></p>
	<ul>
		<li><?php echo esc_html__( 'Creates:', 'orbit-import' ); ?> <span id="oui-dry-create"><?php echo isset( $result['created'] ) ? (int) $result['created'] : 0; ?></span></li>
		<li><?php echo esc_html__( 'Updates:', 'orbit-import' ); ?> <span id="oui-dry-update"><?php echo isset( $result['updated'] ) ? (int) $result['updated'] : 0; ?></span></li>
		<li><?php echo esc_html__( 'Invalid rows:', 'orbit-import' ); ?> <span id="oui-dry-invalid"><?php echo isset( $result['invalid'] ) ? (int) $result['invalid'] : 0; ?></span></li>
		<li><?php echo esc_html__( 'Duplicates:', 'orbit-import' ); ?> <span id="oui-dry-dup"><?php echo isset( $result['duplicates'] ) ? (int) $result['duplicates'] : 0; ?></span></li>
	</ul>
	<p>
		<a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => OUI_PAGE_SLUG, 'step' => 'map', 'job' => (int) $job_id ), admin_url( 'users.php' ) ) ); ?>"><?php echo esc_html__( 'Back', 'orbit-import' ); ?></a>
	</p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="oui_import_start_run" />
		<input type="hidden" name="job" value="<?php echo esc_attr( (int) $job_id ); ?>" />
		<?php wp_nonce_field( 'oui_import_start_run' ); ?>
		<button class="button button-primary"><?php echo esc_html__( 'Run Import', 'orbit-import' ); ?></button>
	</form>
</div>
