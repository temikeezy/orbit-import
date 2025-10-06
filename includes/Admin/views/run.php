<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$job_id = isset( $_GET['job'] ) ? (int) $_GET['job'] : 0;
?>
<div class="oui-panel">
	<h2><?php echo esc_html__( 'Run Import', 'orbit-import' ); ?></h2>
	<input type="hidden" id="oui-job-id" value="<?php echo esc_attr( (int) $job_id ); ?>" />
	<div id="oui-progress" style="background:#f1f1f1;height:20px;position:relative;">
		<div id="oui-progress-bar" style="background:#2271b1;height:100%;width:0%"></div>
	</div>
	<p><span id="oui-progress-text">0%</span></p>
	<p>
		<a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => OUI_PAGE_SLUG, 'step' => 'dry-run', 'job' => (int) $job_id ), admin_url( 'users.php' ) ) ); ?>"><?php echo esc_html__( 'Back', 'orbit-import' ); ?></a>
		<button class="button" id="oui-resume-import"><?php echo esc_html__( 'Resume', 'orbit-import' ); ?></button>
	</p>
	<script>
	(function(){
		var jobId = <?php echo (int) $job_id; ?>;
		function batch(prev){
			var data = new FormData();
			data.append('action','oui_import_run_batch');
			data.append('job_id', jobId);
			fetch(ajaxurl, { method:'POST', credentials:'same-origin', body:data })
			.then(r=>r.json()).then(function(res){
				if(!res || !res.success){ return; }
				var d = res.data||{}; var pct = parseInt(d.percent||0,10);
				document.getElementById('oui-progress-bar').style.width = pct+'%';
				document.getElementById('oui-progress-text').textContent = pct+'%';
				if(d.done){ window.location.href = '<?php echo esc_js( add_query_arg( array( 'page'=>OUI_PAGE_SLUG,'step'=>'report','job'=>(int)$job_id ), admin_url('users.php') ) ); ?>'; return; }
				setTimeout(function(){ batch(pct); }, 600);
			});
		}
		document.getElementById('oui-resume-import').addEventListener('click', function(){ batch(0); });
		// Auto-start
		batch(0);
	})();
	</script>
</div>
