(function($){
	$(function(){
		function getJobId(){ return parseInt($('#oui-job-id').val()||'0',10); }

		var $drop = $('#oui-dropzone');
		var $file = $('#oui-file-input');
		if($drop.length){
			$drop.on('dragover dragenter', function(e){ e.preventDefault(); e.stopPropagation(); $drop.css('border-color', '#2271b1'); });
			$drop.on('dragleave dragend drop', function(e){ e.preventDefault(); e.stopPropagation(); $drop.css('border-color', '#bbb'); });
			$drop.on('drop', function(e){
				var dt = e.originalEvent.dataTransfer;
				if(dt && dt.files && dt.files.length){
					$file[0].files = dt.files;
					$('#oui-upload-form').trigger('submit');
				}
			});
		}

		$('#oui-upload-form').on('submit', function(e){
			e.preventDefault();
			var formData = new FormData(this);
			formData.append('action','oui_upload_csv');
			formData.append('nonce', OUI.nonce);
			$.ajax({ url: OUI.ajaxUrl, method: 'POST', data: formData, contentType: false, processData: false })
			.done(function(res){
				if(res && res.success){
					$('#oui-job-id').val(res.data.job_id);
					$.post(OUI.ajaxUrl, { action: 'oui_dry_run', nonce: OUI.nonce, job_id: res.data.job_id })
					.done(function(dr){ if(dr && dr.success){ $('#oui-dry-create').text(dr.data.created); $('#oui-dry-update').text(dr.data.updated); $('#oui-dry-invalid').text(dr.data.invalid); $('#oui-dry-unknown').text((dr.data.unknown_streams||[]).length); if($('#oui-dry-dup').length){ $('#oui-dry-dup').text(dr.data.duplicates||0); } if($('#oui-dry-estimate').length){ $('#oui-dry-estimate').text((dr.data.estimate_per_1k||'-') + ' s'); } } });
				}else{ alert(res && res.data && res.data.message ? res.data.message : 'Error'); }
			});
		});

		$('#oui-save-mapping').on('click', function(){
			var jobId = getJobId(); if(!jobId){ alert('No job. Upload CSV first.'); return; }
			var payload = { action: 'oui_save_mapping', nonce: OUI.nonce, job_id: jobId, mapping: { email: $('#oui-map-email').val(), username: $('#oui-map-username').val(), first_name: $('#oui-map-first_name').val(), last_name: $('#oui-map-last_name').val(), display_name: $('#oui-map-display_name').val(), password: $('#oui-map-password').val(), wp_role: $('#oui-map-wp_role').val(), otm_role: $('#oui-map-otm_role').val(), user_status: $('#oui-map-user_status').val(), streams: $('#oui-map-streams').val(), stream_roles: $('#oui-map-stream_roles').val(), meta_prefix: $('#oui-map-meta_prefix').val() }, bulk: { ids: ($('#oui-bulk-streams').val()||[]), role: $('#oui-bulk-role').val(), mode: ($('input[name="oui-mode"]:checked').val()||'csv_only'), existing_only: $('#oui-bulk-existing-only').is(':checked') ? 1 : 0 } };
			$.post(OUI.ajaxUrl, payload).done(function(res){ if(res && res.success){ window.location.href = res.data.redirect || window.location.href; } else { alert(res && res.data && res.data.message ? res.data.message : 'Error'); } });
		});

		$('#oui-run-dry').on('click', function(){
			var jobId = getJobId(); if(!jobId){ alert('No job. Upload CSV first.'); return; }
			$.post(OUI.ajaxUrl, { action: 'oui_dry_run', nonce: OUI.nonce, job_id: jobId })
			.done(function(res){ if(res && res.success){ $('#oui-dry-create').text(res.data.created); $('#oui-dry-update').text(res.data.updated); $('#oui-dry-invalid').text(res.data.invalid); $('#oui-dry-unknown').text((res.data.unknown_streams||[]).length); if($('#oui-dry-dup').length){ $('#oui-dry-dup').text(res.data.duplicates||0); } if($('#oui-dry-estimate').length){ $('#oui-dry-estimate').text((res.data.estimate_per_1k||'-') + ' s'); } } else { alert(res && res.data && res.data.message ? res.data.message : 'Error'); } });
		});

		function runBatch(jobId, prevProcessed){
			$.post(OUI.ajaxUrl, { action: 'oui_run_batch', nonce: OUI.nonce, job_id: jobId })
			.done(function(res){ if(!(res && res.success)) { alert(res && res.data && res.data.message ? res.data.message : 'Error'); return; } var d = res.data || {}; var percent = parseInt(d.percent||0,10); $('#oui-progress-bar').css('width', percent+'%'); $('#oui-progress-text').text(percent+'%'); var processed = parseInt(d.processed||0,10); if(percent < 100 && processed > (prevProcessed||0)){ setTimeout(function(){ runBatch(jobId, processed); }, 500); } });
		}

		$('#oui-start-import').on('click', function(){ var jobId = getJobId(); if(!jobId){ alert('No job. Upload CSV first.'); return; } runBatch(jobId, 0); });
		$('#oui-resume-import').on('click', function(){ var jobId = getJobId(); if(!jobId){ alert('No job.'); return; } var prev = parseInt($('#oui-progress-text').text()||'0',10); runBatch(jobId, prev); });
	});
})(jQuery);
