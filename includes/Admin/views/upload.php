<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="oui-panel">
	<h2><?php echo esc_html__( 'Upload CSV', 'orbit-import' ); ?></h2>
	<p><a class="button" href="<?php echo esc_url( plugins_url( 'assets/sample.csv', dirname( dirname( __DIR__ ) ) ) ); ?>"><?php echo esc_html__( 'Download Sample CSV', 'orbit-import' ); ?></a></p>
	<form id="oui-upload-form" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field( 'oui_admin', 'nonce' ); ?>
		<input type="file" name="file" accept=".csv" required />
		<p><button type="submit" class="button button-primary"><?php echo esc_html__( 'Upload', 'orbit-import' ); ?></button></p>
	</form>
</div>
