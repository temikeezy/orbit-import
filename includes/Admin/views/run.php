<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$job_id = isset( $_GET['job'] ) ? (int) $_GET['job'] : ( isset( $_GET['job_id'] ) ? (int) $_GET['job_id'] : 0 );
?>
<div class="oui-panel">
	<h2><?php echo esc_html__( 'Run Import', 'orbit-import' ); ?></h2>
	<input type="hidden" id="oui-job-id" value="<?php echo esc_attr( (int) $job_id ); ?>" />
	<div id="oui-progress" style="background:#f1f1f1;height:20px;position:relative;">
		<div id="oui-progress-bar" style="background:#2271b1;height:100%;width:0%"></div>
	</div>
	<p><span id="oui-progress-text">0%</span></p>
	<p>
		<a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'orbit-import', 'step' => 'dry-run', 'job' => (int) $job_id ), admin_url( 'users.php' ) ) ); ?>"><?php echo esc_html__( 'Back', 'orbit-import' ); ?></a>
		<button class="button" id="oui-resume-import"><?php echo esc_html__( 'Resume', 'orbit-import' ); ?></button>
	</p>
</div>
