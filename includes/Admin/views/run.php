<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$job_id = isset( $_GET['job_id'] ) ? (int) $_GET['job_id'] : 0;
?>
<div class="oui-panel">
	<h2><?php echo esc_html__( 'Run Import', 'orbit-import' ); ?></h2>
	<input type="hidden" id="oui-job-id" value="<?php echo esc_attr( (int) $job_id ); ?>" />
	<div id="oui-progress" style="background:#f1f1f1;height:20px;position:relative;">
		<div id="oui-progress-bar" style="background:#2271b1;height:100%;width:0%"></div>
	</div>
	<p><span id="oui-progress-text">0%</span></p>
	<p>
		<button class="button" id="oui-cancel-import"><?php echo esc_html__( 'Cancel', 'orbit-import' ); ?></button>
		<button class="button" id="oui-resume-import"><?php echo esc_html__( 'Resume', 'orbit-import' ); ?></button>
	</p>
</div>
