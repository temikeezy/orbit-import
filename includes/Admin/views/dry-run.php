<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="oui-panel">
	<h2><?php echo esc_html__( 'Preview Import', 'orbit-import' ); ?></h2>
	<p><?php echo esc_html__( 'This is a zero-risk preview to catch problems early—no database writes, no group joins, no emails.', 'orbit-import' ); ?></p>
	<p><button class="button" id="oui-run-dry"><?php echo esc_html__( 'Run Preview', 'orbit-import' ); ?></button></p>
	<ul>
		<li><?php echo esc_html__( 'Creates:', 'orbit-import' ); ?> <span id="oui-dry-create">0</span></li>
		<li><?php echo esc_html__( 'Updates:', 'orbit-import' ); ?> <span id="oui-dry-update">0</span></li>
		<li><?php echo esc_html__( 'Invalid rows:', 'orbit-import' ); ?> <span id="oui-dry-invalid">0</span></li>
		<li><?php echo esc_html__( 'Duplicates:', 'orbit-import' ); ?> <span id="oui-dry-dup">0</span></li>
		<li><?php echo esc_html__( 'Unknown streams:', 'orbit-import' ); ?> <span id="oui-dry-unknown">0</span></li>
		<li><?php echo esc_html__( 'Est. time per 1k rows:', 'orbit-import' ); ?> <span id="oui-dry-estimate">—</span></li>
	</ul>
	<p>
		<a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'orbit-import', 'step' => 'map' ), admin_url( 'users.php' ) ) ); ?>"><?php echo esc_html__( 'Back', 'orbit-import' ); ?></a>
		<button class="button button-primary" id="oui-start-import"><?php echo esc_html__( 'Run Import', 'orbit-import' ); ?></button>
	</p>
</div>
