(function($) {
    'use strict';
    
    var OUI_Frontend = {
        currentFileId: null,
        currentMapping: {},
        isProcessing: false,
        
        init: function() {
            this.bindEvents();
            this.initTabs();
        },
        
        bindEvents: function() {
            // Tab switching (for full tab interface)
            $(document).on('click', '.oui-tab-button', this.switchTab);
            
            // Individual user forms (both full and quick add)
            $(document).on('submit', '#oui-add-user-form', this.handleAddUser);
            $(document).on('submit', '#oui-quick-add-form', this.handleQuickAddUser);
            
            // File upload
            this.initDropzone();
            $(document).on('change', '#oui-file-input', this.handleFileSelect);
            
            // Mapping and import
            $(document).on('change', '.oui-mapping-item select', this.updateMapping);
            $(document).on('click', '#oui-start-import', this.startImport);
            $(document).on('click', '#oui-start-new-import', this.resetImport);
            
            // Members tab specific events
            $(document).on('click', '#oui-toggle-bulk-import', this.toggleBulkImport);
            $(document).on('click', '#oui-cancel-import', this.cancelImport);
            $(document).on('click', '#oui-close-import', this.closeImport);
        },
        
        initTabs: function() {
            $('.oui-tab-button').first().addClass('active');
            $('.oui-tab-content').first().addClass('active');
        },
        
        switchTab: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var tabId = $button.data('tab');
            
            // Update button states
            $('.oui-tab-button').removeClass('active');
            $button.addClass('active');
            
            // Update content states
            $('.oui-tab-content').removeClass('active');
            $('#' + tabId + '-tab').addClass('active');
            
            // Reset import if switching to individual tab
            if (tabId === 'individual') {
                OUI_Frontend.resetImport();
            }
        },
        
        initDropzone: function() {
            var $dropzone = $('#oui-dropzone');
            var $fileInput = $('#oui-file-input');
            
            if ($dropzone.length === 0) return;
            
            // Drag and drop events
            $dropzone.on('dragover dragenter', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('dragover');
            });
            
            $dropzone.on('dragleave dragend', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
            });
            
            $dropzone.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
                
                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    $fileInput[0].files = files;
                    OUI_Frontend.handleFileSelect();
                }
            });
            
            // Click to browse
            $dropzone.on('click', function() {
                $fileInput.click();
            });
        },
        
        handleFileSelect: function() {
            var fileInput = document.getElementById('oui-file-input');
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                return;
            }
            
            var file = fileInput.files[0];
            OUI_Frontend.uploadFile(file);
        },
        
        uploadFile: function(file) {
            if (!file) {
                this.showError(OUI_Frontend.strings.selectFile);
                return;
            }
            
            // Validate file type
            var validTypes = ['text/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
            var validExtensions = ['.csv', '.xlsx'];
            var fileExtension = file.name.toLowerCase().substring(file.name.lastIndexOf('.'));
            
            if (validTypes.indexOf(file.type) === -1 && validExtensions.indexOf(fileExtension) === -1) {
                this.showError(OUI_Frontend.strings.invalidFile);
                return;
            }
            
            // Validate file size (10MB)
            if (file.size > 10 * 1024 * 1024) {
                this.showError(OUI_Frontend.strings.fileTooLarge);
                return;
            }
            
            // Show progress
            this.showUploadProgress();
            
            // Upload file
            var formData = new FormData();
            formData.append('action', 'oui_frontend_upload');
            formData.append('nonce', OUI_Frontend.nonce);
            formData.append('group_id', OUI_Frontend.groupId);
            formData.append('file', file);
            
            $.ajax({
                url: OUI_Frontend.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    OUI_Frontend.hideUploadProgress();
                    
                    if (response.success) {
                        OUI_Frontend.currentFileId = response.data.file_id;
                        OUI_Frontend.populateMappingOptions(response.data.headers);
                        OUI_Frontend.showFilePreview(response.data.preview_rows, response.data.headers);
                        
                        // Show mapping section (for Members tab interface)
                        $('#oui-mapping-section').show();
                        
                        // Also show mapping step (for full tab interface)
                        OUI_Frontend.showStep('mapping-step');
                    } else {
                        OUI_Frontend.showError(response.data.message || OUI_Frontend.strings.error);
                    }
                },
                error: function(xhr) {
                    OUI_Frontend.hideUploadProgress();
                    var message = OUI_Frontend.strings.error;
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        message = xhr.responseJSON.data.message;
                    }
                    OUI_Frontend.showError(message);
                }
            });
        },
        
        populateMappingOptions: function(headers) {
            var $selects = $('.oui-mapping-item select');
            
            $selects.each(function() {
                var $select = $(this);
                var currentValue = $select.val();
                
                // Clear existing options except the first one
                $select.find('option:not(:first)').remove();
                
                // Add header options
                headers.forEach(function(header, index) {
                    $select.append('<option value="' + header.toLowerCase() + '">' + header + '</option>');
                });
                
                // Restore previous value if it exists
                if (currentValue) {
                    $select.val(currentValue);
                }
            });
        },
        
        showFilePreview: function(rows, headers) {
            var $preview = $('#oui-file-preview');
            var html = '<table class="oui-preview-table"><thead><tr>';
            
            headers.forEach(function(header) {
                html += '<th>' + header + '</th>';
            });
            html += '</tr></thead><tbody>';
            
            rows.forEach(function(row) {
                html += '<tr>';
                row.forEach(function(cell) {
                    html += '<td>' + (cell || '') + '</td>';
                });
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            $preview.html(html);
        },
        
        updateMapping: function() {
            var mapping = {};
            $('.oui-mapping-item select').each(function() {
                var $select = $(this);
                var name = $select.attr('name').match(/\[([^\]]+)\]/)[1];
                var value = $select.val();
                if (value) {
                    mapping[name] = value;
                }
            });
            
            OUI_Frontend.currentMapping = mapping;
        },
        
        startImport: function() {
            if (OUI_Frontend.isProcessing) {
                return;
            }
            
            // Validate mapping
            if (!OUI_Frontend.currentMapping.email) {
                OUI_Frontend.showError('Email mapping is required');
                return;
            }
            
            OUI_Frontend.isProcessing = true;
            
            // Show progress section (for Members tab interface)
            $('#oui-progress-section').show();
            $('#oui-mapping-section').hide();
            
            // Also show processing step (for full tab interface)
            OUI_Frontend.showStep('processing-step');
            
            OUI_Frontend.resetStats();
            OUI_Frontend.processBatch(0);
        },
        
        processBatch: function(offset) {
            if (!OUI_Frontend.isProcessing) {
                return;
            }
            
            $.ajax({
                url: OUI_Frontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'oui_frontend_process_batch',
                    nonce: OUI_Frontend.nonce,
                    file_id: OUI_Frontend.currentFileId,
                    mapping: OUI_Frontend.currentMapping,
                    batch_size: 10,
                    offset: offset
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        OUI_Frontend.updateStats(data);
                        OUI_Frontend.addLogEntry('Processed ' + data.processed + ' rows', 'info');
                        
                        if (data.has_more) {
                            // Continue processing
                            setTimeout(function() {
                                OUI_Frontend.processBatch(data.offset);
                            }, 500);
                        } else {
                            // Import complete
                            OUI_Frontend.completeImport(data);
                        }
                    } else {
                        OUI_Frontend.showError(response.data.message || OUI_Frontend.strings.error);
                        OUI_Frontend.isProcessing = false;
                    }
                },
                error: function(xhr) {
                    var message = OUI_Frontend.strings.error;
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        message = xhr.responseJSON.data.message;
                    }
                    OUI_Frontend.showError(message);
                    OUI_Frontend.isProcessing = false;
                }
            });
        },
        
        completeImport: function(finalData) {
            OUI_Frontend.isProcessing = false;
            
            // Show results section (for Members tab interface)
            $('#oui-results-section').show();
            $('#oui-progress-section').hide();
            
            // Also show results step (for full tab interface)
            OUI_Frontend.showStep('results-step');
            
            // Update final stats
            $('#oui-final-created').text(finalData.created || 0);
            $('#oui-final-updated').text(finalData.updated || 0);
            $('#oui-final-skipped').text(finalData.skipped || 0);
            $('#oui-final-errors').text(finalData.errors || 0);
            
            // Show/hide result items based on data
            if (finalData.skipped > 0) {
                $('#oui-skipped-result').show();
            }
            if (finalData.errors > 0) {
                $('#oui-errors-result').show();
            }
            
            OUI_Frontend.addLogEntry('Import completed successfully!', 'success');
        },
        
        handleAddUser: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $button = $form.find('button[type="submit"]');
            var $result = $('#oui-individual-result');
            
            $button.prop('disabled', true).text(OUI_Frontend.strings.processing);
            $result.hide();
            
            $.ajax({
                url: OUI_Frontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'oui_frontend_add_user',
                    nonce: OUI_Frontend.nonce,
                    group_id: OUI_Frontend.groupId,
                    email: $form.find('#user-email').val(),
                    first_name: $form.find('#user-first-name').val(),
                    last_name: $form.find('#user-last-name').val(),
                    role: $form.find('#user-role').val()
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        var message = data.is_new ? OUI_Frontend.strings.userCreated : OUI_Frontend.strings.userExists;
                        
                        $result.removeClass('error').addClass('success').html(message).show();
                        $form[0].reset();
                    } else {
                        $result.removeClass('success').addClass('error').html(response.data.message || OUI_Frontend.strings.error).show();
                    }
                },
                error: function(xhr) {
                    var message = OUI_Frontend.strings.error;
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        message = xhr.responseJSON.data.message;
                    }
                    $result.removeClass('success').addClass('error').html(message).show();
                },
                complete: function() {
                    $button.prop('disabled', false).text('Add Member');
                }
            });
        },
        
        handleQuickAddUser: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $button = $form.find('button[type="submit"]');
            var $result = $('#oui-quick-result');
            
            $button.prop('disabled', true).text(OUI_Frontend.strings.processing);
            $result.hide();
            
            $.ajax({
                url: OUI_Frontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'oui_frontend_add_user',
                    nonce: OUI_Frontend.nonce,
                    group_id: OUI_Frontend.groupId,
                    email: $form.find('#quick-email').val(),
                    first_name: $form.find('#quick-first-name').val(),
                    last_name: $form.find('#quick-last-name').val(),
                    role: $form.find('#quick-role').val()
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        var message = data.is_new ? OUI_Frontend.strings.userCreated : OUI_Frontend.strings.userExists;
                        
                        $result.removeClass('error').addClass('success').html(message).show();
                        $form[0].reset();
                        
                        // Refresh the page after a short delay to show the new member
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        $result.removeClass('success').addClass('error').html(response.data.message || OUI_Frontend.strings.error).show();
                    }
                },
                error: function(xhr) {
                    var message = OUI_Frontend.strings.error;
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        message = xhr.responseJSON.data.message;
                    }
                    $result.removeClass('success').addClass('error').html(message).show();
                },
                complete: function() {
                    $button.prop('disabled', false).text('Add Member');
                }
            });
        },
        
        toggleBulkImport: function() {
            var $section = $('#oui-bulk-import-section');
            var $button = $('#oui-toggle-bulk-import');
            
            if ($section.is(':visible')) {
                $section.slideUp();
                $button.text('Bulk Import from File');
            } else {
                $section.slideDown();
                $button.text('Hide Bulk Import');
            }
        },
        
        cancelImport: function() {
            OUI_Frontend.resetImport();
            $('#oui-bulk-import-section').slideUp();
            $('#oui-toggle-bulk-import').text('Bulk Import from File');
        },
        
        closeImport: function() {
            $('#oui-results-section').hide();
            $('#oui-bulk-import-section').slideUp();
            $('#oui-toggle-bulk-import').text('Bulk Import from File');
        },
        
        resetImport: function() {
            OUI_Frontend.isProcessing = false;
            OUI_Frontend.currentFileId = null;
            OUI_Frontend.currentMapping = {};
            
            // Reset file input
            $('#oui-file-input').val('');
            
            // Reset steps (for full tab interface)
            $('.oui-step').hide();
            $('#upload-step').show();
            
            // Reset forms
            $('#oui-add-user-form')[0].reset();
            $('#oui-individual-result').hide();
            
            // Reset Members tab interface
            $('#oui-mapping-section').hide();
            $('#oui-progress-section').hide();
            $('#oui-results-section').hide();
            $('#oui-upload-progress').hide();
            
            // Clear preview
            $('#oui-file-preview').empty();
            
            // Reset mapping selects
            $('.oui-mapping-item select').val('');
        },
        
        showStep: function(stepId) {
            $('.oui-step').hide();
            $('#' + stepId).show();
        },
        
        showUploadProgress: function() {
            $('#oui-upload-progress').show();
        },
        
        hideUploadProgress: function() {
            $('#oui-upload-progress').hide();
        },
        
        resetStats: function() {
            $('#oui-stat-created').text('0');
            $('#oui-stat-updated').text('0');
            $('#oui-stat-skipped').text('0');
            $('#oui-stat-errors').text('0');
            $('#oui-import-progress').css('width', '0%');
            $('#oui-import-progress-text').text('0%');
            $('#oui-import-log').empty();
        },
        
        updateStats: function(data) {
            var total = (data.created || 0) + (data.updated || 0) + (data.skipped || 0) + (data.errors || 0);
            var processed = parseInt($('#oui-stat-created').text()) + parseInt($('#oui-stat-updated').text()) + parseInt($('#oui-stat-skipped').text()) + parseInt($('#oui-stat-errors').text());
            
            $('#oui-stat-created').text(parseInt($('#oui-stat-created').text()) + (data.created || 0));
            $('#oui-stat-updated').text(parseInt($('#oui-stat-updated').text()) + (data.updated || 0));
            $('#oui-stat-skipped').text(parseInt($('#oui-stat-skipped').text()) + (data.skipped || 0));
            $('#oui-stat-errors').text(parseInt($('#oui-stat-errors').text()) + (data.errors || 0));
            
            // Update progress (this is a rough estimate)
            var progress = Math.min(100, Math.round((processed / Math.max(1, total)) * 100));
            $('#oui-import-progress').css('width', progress + '%');
            $('#oui-import-progress-text').text(progress + '%');
        },
        
        addLogEntry: function(message, type) {
            var $log = $('#oui-import-log');
            var $entry = $('<div class="oui-log-entry ' + (type || 'info') + '">' + message + '</div>');
            $log.append($entry);
            $log.scrollTop($log[0].scrollHeight);
        },
        
        showError: function(message) {
            // You can implement a global error display system here
            alert(message);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        OUI_Frontend.init();
    });
    
    // Make OUI_Frontend available globally for debugging
    window.OUI_Frontend = OUI_Frontend;
    
})(jQuery);
