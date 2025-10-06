<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="oui-panel">
	<h2><?php echo esc_html__( 'Upload CSV or Excel (XLSX)', 'orbit-import' ); ?></h2>
	<p>
		<a class="button" href="<?php echo esc_url( plugins_url( 'assets/sample.csv', dirname( dirname( __DIR__ ) ) ) ); ?>"><?php echo esc_html__( 'Download Sample CSV', 'orbit-import' ); ?></a>
		<a class="button" href="<?php echo esc_url( plugins_url( 'assets/sample.xlsx', dirname( dirname( __DIR__ ) ) ) ); ?>"><?php echo esc_html__( 'Download Sample XLSX', 'orbit-import' ); ?></a>
	</p>
	<div id="oui-dropzone" style="border:2px dashed #bbb;padding:20px;text-align:center;margin-bottom:10px;">
		<?php echo esc_html__( 'Drag & drop file here, or choose below', 'orbit-import' ); ?>
	</div>
	<form id="oui-upload-form" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field( 'oui_admin', 'nonce' ); ?>
		<input type="file" name="file" id="oui-file-input" accept=".csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,.xlsx" required />
		<p><button type="submit" class="button button-primary"><?php echo esc_html__( 'Upload', 'orbit-import' ); ?></button></p>
	</form>
</div>
