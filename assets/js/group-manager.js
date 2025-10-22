/**
 * ORBIT Group Member Importer - Group Manager JavaScript
 * 
 * Handles AJAX interactions and file processing for the group management interface
 */

(function($) {
    'use strict';
    
    var OGMI = {
        currentFileId: null,
        currentMapping: {},
        isProcessing: false,
        
        /**
         * Initialize the plugin
         */
        init: function() {
            this.bindEvents();
            this.initDropzone();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Quick add form
            $(document).on('submit', '#ogmi-quick-add-form', this.handleQuickAdd);
            
            // Bulk import toggle
            $(document).on('click', '#ogmi-toggle-bulk-import', this.toggleBulkImport);
            
            // File upload
            $(document).on('change', '#ogmi-file-input', this.handleFileSelect);
            
            // Mapping and import
            $(document).on('change', '.ogmi-mapping-item select', this.updateMapping);
            $(document).on('click', '#ogmi-start-import', this.startImport);
            $(document).on('click', '#ogmi-cancel-import', this.cancelImport);
            $(document).on('click', '#ogmi-close-import', this.closeImport);
            $(document).on('click', '#ogmi-start-new-import', this.startNewImport);
        },
        
        /**
         * Initialize drag and drop functionality
         */
        initDropzone: function() {
            // Check if OGMI object is available
            if (typeof OGMI === 'undefined') {
                console.log('OGMI: OGMI object is not defined - script localization failed');
                return;
            }
            
            console.log('OGMI: OGMI object available:', OGMI);
            var dropzone = document.getElementById('ogmi-dropzone');
            
            if (!dropzone) {
                console.log('OGMI: Dropzone not found');
                return;
            }
            
            // Check if already initialized
            if (dropzone.dataset.ogmiInitialized === 'true') {
                console.log('OGMI: Dropzone already initialized');
                return;
            }
            
            console.log('OGMI: Initializing dropzone with vanilla JS');
            
            // Mark as initialized
            dropzone.dataset.ogmiInitialized = 'true';
            
            // Drag and drop events using vanilla JavaScript
            dropzone.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('dragover');
                console.log('OGMI: Drag over');
            });
            
            dropzone.addEventListener('dragenter', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('dragover');
                console.log('OGMI: Drag enter');
            });
            
            dropzone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('dragover');
                console.log('OGMI: Drag leave');
            });
            
            dropzone.addEventListener('dragend', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('dragover');
                console.log('OGMI: Drag end');
            });
            
            dropzone.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('dragover');
                
                console.log('OGMI: File dropped');
                var files = e.dataTransfer.files;
                if (files.length > 0) {
                    console.log('OGMI: Files found:', files.length);
                    var file = files[0];
                    OGMI.uploadFile(file);
                }
            });
            
            // Click to browse using vanilla JavaScript
            dropzone.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('OGMI: Dropzone clicked');
                
                // Create a new file input element
                var newFileInput = document.createElement('input');
                newFileInput.type = 'file';
                newFileInput.accept = '.csv';
                newFileInput.style.display = 'none';
                
                newFileInput.addEventListener('change', function(event) {
                    var file = event.target.files[0];
                    if (file) {
                        console.log('OGMI: File selected via browse:', file.name);
                        OGMI.uploadFile(file);
                    }
                    // Clean up
                    if (document.body.contains(newFileInput)) {
                        document.body.removeChild(newFileInput);
                    }
                });
                
                // Add to body and trigger click
                document.body.appendChild(newFileInput);
                newFileInput.click();
            });
        },
        
        /**
         * Handle quick add form submission
         */
        handleQuickAdd: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $button = $form.find('button[type="submit"]');
            var $result = $('#ogmi-quick-result');
            
            // Validate email
            var email = $form.find('#quick-email').val().trim();
            if (!email || !OGMI.isValidEmail(email)) {
                OGMI.showError($result, OGMI.strings.invalidEmail);
                return;
            }
            
            $button.prop('disabled', true).text(OGMI.strings.processing);
            $result.hide();
            
            $.ajax({
                url: OGMI.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ogmi_add_member',
                    nonce: OGMI.nonce,
                    group_id: OGMI.groupId,
                    email: email,
                    first_name: $form.find('#quick-first-name').val().trim(),
                    last_name: $form.find('#quick-last-name').val().trim(),
                    role: $form.find('#quick-role').val()
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        var message = data.is_new ? OGMI.strings.userCreated : OGMI.strings.userExists;
                        
                        OGMI.showSuccess($result, message);
                        $form[0].reset();
                        
                        // Refresh the page after a short delay to show the new member
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        OGMI.showError($result, response.data.message || OGMI.strings.error);
                    }
                },
                error: function(xhr) {
                    var message = OGMI.strings.error;
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        message = xhr.responseJSON.data.message;
                    }
                    OGMI.showError($result, message);
                },
                complete: function() {
                    $button.prop('disabled', false).text('Add Member');
                }
            });
        },
        
        /**
         * Toggle bulk import section
         */
        toggleBulkImport: function() {
            var $section = $('#ogmi-bulk-import-section');
            var $button = $('#ogmi-toggle-bulk-import');
            
            if ($section.is(':visible')) {
                $section.slideUp();
                $button.text('Bulk Import from CSV');
            } else {
                $section.slideDown();
                $button.text('Hide Bulk Import');
                
                // Initialize dropzone when section is shown
                setTimeout(function() {
                    OGMI.initDropzone();
                }, 300);
            }
        },
        
        /**
         * Handle file selection
         */
        handleFileSelect: function() {
            console.log('OGMI: handleFileSelect called');
            var fileInput = document.getElementById('ogmi-file-input');
            if (!fileInput) {
                console.log('OGMI: File input not found');
                return;
            }
            
            if (!fileInput.files || fileInput.files.length === 0) {
                console.log('OGMI: No files selected');
                return;
            }
            
            var file = fileInput.files[0];
            console.log('OGMI: File selected:', file.name, file.size, file.type);
            OGMI.uploadFile(file);
        },
        
        /**
         * Upload file
         */
        uploadFile: function(file) {
            console.log('OGMI: uploadFile called with:', file);
            
            if (!file) {
                console.log('OGMI: No file provided');
                OGMI.showAlert(OGMI.strings.selectFile);
                return;
            }
            
            // Validate file type
            if (!file.name.toLowerCase().endsWith('.csv')) {
                console.log('OGMI: Invalid file type:', file.name);
                OGMI.showAlert(OGMI.strings.invalidFile);
                return;
            }
            
            // Validate file size (10MB)
            if (file.size > 10 * 1024 * 1024) {
                console.log('OGMI: File too large:', file.size);
                OGMI.showAlert(OGMI.strings.fileTooLarge);
                return;
            }
            
            console.log('OGMI: File validation passed, starting upload');
            
            // Show progress
            OGMI.showUploadProgress();
            
            // Upload file
            var formData = new FormData();
            formData.append('action', 'ogmi_upload_file');
            formData.append('nonce', OGMI.nonce);
            formData.append('group_id', OGMI.groupId);
            formData.append('file', file);
            
            // Check if OGMI object is available
            if (typeof OGMI === 'undefined') {
                console.log('OGMI: OGMI object is undefined - script localization failed');
                alert('Script configuration error. Please refresh the page and try again.');
                return;
            }
            
            console.log('OGMI: Nonce being sent:', OGMI.nonce);
            console.log('OGMI: Group ID being sent:', OGMI.groupId);
            
            // Check if we have a valid nonce
            if (!OGMI.nonce) {
                console.log('OGMI: No nonce available, this will likely fail');
                alert('Security token missing. Please refresh the page and try again.');
                return;
            }
            
            // Ensure we have the AJAX URL
            var ajaxUrl = OGMI.ajaxUrl || ajaxurl || '/wp-admin/admin-ajax.php';
            console.log('OGMI: Sending AJAX request to:', ajaxUrl);
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('OGMI: Upload success response:', response);
                    console.log('OGMI: Response type:', typeof response);
                    OGMI.hideUploadProgress();
                    
                    // Check if response is HTML (error page) instead of JSON
                    if (typeof response === 'string' && response.includes('<!doctype html>')) {
                        console.log('OGMI: Server returned HTML instead of JSON - likely an error');
                        OGMI.showAlert('Server error: Received HTML response instead of JSON. Please check your WordPress configuration.');
                        return;
                    }
                    
                    // Check if response is a proper JSON object
                    if (typeof response !== 'object' || response === null) {
                        console.log('OGMI: Invalid response format:', response);
                        OGMI.showAlert('Invalid response from server. Please try again.');
                        return;
                    }
                    
                    console.log('OGMI: Response success:', response.success);
                    console.log('OGMI: Response data:', response.data);
                    
                    if (response.success) {
                        console.log('OGMI: Processing successful upload');
                        OGMI.currentFileId = response.data.file_id;
                        OGMI.populateMappingOptions(response.data.headers);
                        OGMI.showFilePreview(response.data.preview_rows, response.data.headers);
                        $('#ogmi-mapping-section').show();
                        console.log('OGMI: Mapping section should now be visible');
                    } else {
                        console.log('OGMI: Upload failed:', response.data);
                        var errorMessage = OGMI.strings.error;
                        if (response.data && response.data.message) {
                            errorMessage = response.data.message;
                        }
                        OGMI.showAlert(errorMessage);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('OGMI: Upload error details:');
                    console.log('OGMI: XHR:', xhr);
                    console.log('OGMI: Status:', status);
                    console.log('OGMI: Error:', error);
                    console.log('OGMI: Response text:', xhr.responseText);
                    OGMI.hideUploadProgress();
                    var message = OGMI.strings.error;
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        message = xhr.responseJSON.data.message;
                    }
                    OGMI.showAlert(message);
                }
            });
        },
        
        /**
         * Populate mapping options
         */
        populateMappingOptions: function(headers) {
            var $selects = $('.ogmi-mapping-item select');
            
            $selects.each(function() {
                var $select = $(this);
                var currentValue = $select.val();
                
                // Clear existing options except the first one
                $select.find('option:not(:first)').remove();
                
                // Add header options
                headers.forEach(function(header, index) {
                    $select.append('<option value="' + index + '">' + header + '</option>');
                });
                
                // Restore previous value if it exists
                if (currentValue) {
                    $select.val(currentValue);
                }
            });
        },
        
        /**
         * Show file preview
         */
        showFilePreview: function(rows, headers) {
            var $preview = $('#ogmi-file-preview');
            var html = '<table><thead><tr>';
            
            headers.forEach(function(header) {
                html += '<th>' + OGMI.escapeHtml(header) + '</th>';
            });
            html += '</tr></thead><tbody>';
            
            rows.forEach(function(row) {
                html += '<tr>';
                row.forEach(function(cell) {
                    html += '<td>' + OGMI.escapeHtml(cell || '') + '</td>';
                });
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            $preview.html(html);
        },
        
        /**
         * Update mapping
         */
        updateMapping: function() {
            var mapping = {};
            $('.ogmi-mapping-item select').each(function() {
                var $select = $(this);
                var name = $select.attr('name').match(/\[([^\]]+)\]/)[1];
                var value = $select.val();
                if (value !== '') {
                    mapping[name] = parseInt(value, 10);
                }
            });
            
            OGMI.currentMapping = mapping;
        },
        
        /**
         * Start import
         */
        startImport: function() {
            if (OGMI.isProcessing) {
                return;
            }
            
            // Validate mapping
            if (!OGMI.currentMapping.email && OGMI.currentMapping.email !== 0) {
                OGMI.showAlert('Email mapping is required');
                return;
            }
            
            OGMI.isProcessing = true;
            $('#ogmi-progress-section').show();
            $('#ogmi-mapping-section').hide();
            OGMI.resetStats();
            OGMI.processBatch(0);
        },
        
        /**
         * Process batch
         */
        processBatch: function(offset) {
            if (!OGMI.isProcessing) {
                return;
            }
            
            $.ajax({
                url: OGMI.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ogmi_process_batch',
                    nonce: OGMI.nonce,
                    file_id: OGMI.currentFileId,
                    mapping: OGMI.currentMapping,
                    batch_size: 10,
                    offset: offset,
                    group_id: OGMI.groupId
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        OGMI.updateStats(data);
                        OGMI.addLogEntry('Processed ' + data.processed + ' rows', 'info');
                        
                        if (data.has_more) {
                            // Continue processing
                            setTimeout(function() {
                                OGMI.processBatch(data.offset);
                            }, 500);
                        } else {
                            // Import complete
                            OGMI.completeImport(data);
                        }
                    } else {
                        OGMI.showAlert(response.data.message || OGMI.strings.error);
                        OGMI.isProcessing = false;
                    }
                },
                error: function(xhr) {
                    var message = OGMI.strings.error;
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        message = xhr.responseJSON.data.message;
                    }
                    OGMI.showAlert(message);
                    OGMI.isProcessing = false;
                }
            });
        },
        
        /**
         * Complete import
         */
        completeImport: function(finalData) {
            OGMI.isProcessing = false;
            $('#ogmi-results-section').show();
            $('#ogmi-progress-section').hide();
            
            // Update final stats
            $('#ogmi-final-created').text(finalData.created || 0);
            $('#ogmi-final-updated').text(finalData.updated || 0);
            $('#ogmi-final-skipped').text(finalData.skipped || 0);
            $('#ogmi-final-errors').text(finalData.errors || 0);
            
            // Show/hide result items based on data
            if (finalData.skipped > 0) {
                $('#ogmi-skipped-result').show();
            }
            if (finalData.errors > 0) {
                $('#ogmi-errors-result').show();
            }
            
            OGMI.addLogEntry(OGMI.strings.importComplete, 'success');
        },
        
        /**
         * Cancel import
         */
        cancelImport: function() {
            OGMI.resetImport();
            $('#ogmi-bulk-import-section').slideUp();
            $('#ogmi-toggle-bulk-import').text('Bulk Import from CSV');
        },
        
        /**
         * Close import
         */
        closeImport: function() {
            $('#ogmi-results-section').hide();
            $('#ogmi-bulk-import-section').slideUp();
            $('#ogmi-toggle-bulk-import').text('Bulk Import from CSV');
        },
        
        /**
         * Start new import
         */
        startNewImport: function() {
            OGMI.resetImport();
            $('#ogmi-results-section').hide();
            $('#ogmi-mapping-section').hide();
            $('#ogmi-file-input').val('');
            $('#ogmi-file-preview').empty();
        },
        
        /**
         * Reset import
         */
        resetImport: function() {
            OGMI.isProcessing = false;
            OGMI.currentFileId = null;
            OGMI.currentMapping = {};
            
            // Reset file input
            $('#ogmi-file-input').val('');
            
            // Reset sections
            $('#ogmi-mapping-section').hide();
            $('#ogmi-progress-section').hide();
            $('#ogmi-results-section').hide();
            $('#ogmi-upload-progress').hide();
            
            // Clear preview
            $('#ogmi-file-preview').empty();
            
            // Reset mapping selects
            $('.ogmi-mapping-item select').val('');
        },
        
        /**
         * Show upload progress
         */
        showUploadProgress: function() {
            $('#ogmi-upload-progress').show();
        },
        
        /**
         * Hide upload progress
         */
        hideUploadProgress: function() {
            $('#ogmi-upload-progress').hide();
        },
        
        /**
         * Reset stats
         */
        resetStats: function() {
            $('#ogmi-stat-created').text('0');
            $('#ogmi-stat-updated').text('0');
            $('#ogmi-stat-skipped').text('0');
            $('#ogmi-stat-errors').text('0');
            $('#ogmi-import-progress').css('width', '0%');
            $('#ogmi-import-progress-text').text('0%');
            $('#ogmi-import-log').empty();
        },
        
        /**
         * Update stats
         */
        updateStats: function(data) {
            var total = (data.created || 0) + (data.updated || 0) + (data.skipped || 0) + (data.errors || 0);
            var processed = parseInt($('#ogmi-stat-created').text()) + parseInt($('#ogmi-stat-updated').text()) + parseInt($('#ogmi-stat-skipped').text()) + parseInt($('#ogmi-stat-errors').text());
            
            $('#ogmi-stat-created').text(parseInt($('#ogmi-stat-created').text()) + (data.created || 0));
            $('#ogmi-stat-updated').text(parseInt($('#ogmi-stat-updated').text()) + (data.updated || 0));
            $('#ogmi-stat-skipped').text(parseInt($('#ogmi-stat-skipped').text()) + (data.skipped || 0));
            $('#ogmi-stat-errors').text(parseInt($('#ogmi-stat-errors').text()) + (data.errors || 0));
            
            // Update progress (this is a rough estimate)
            var progress = Math.min(100, Math.round((processed / Math.max(1, total)) * 100));
            $('#ogmi-import-progress').css('width', progress + '%');
            $('#ogmi-import-progress-text').text(progress + '%');
        },
        
        /**
         * Add log entry
         */
        addLogEntry: function(message, type) {
            var $log = $('#ogmi-import-log');
            var $entry = $('<div class="ogmi-log-entry ' + (type || 'info') + '">' + OGMI.escapeHtml(message) + '</div>');
            $log.append($entry);
            $log.scrollTop($log[0].scrollHeight);
        },
        
        /**
         * Show success message
         */
        showSuccess: function($element, message) {
            $element.removeClass('error').addClass('success').html(OGMI.escapeHtml(message)).show();
        },
        
        /**
         * Show error message
         */
        showError: function($element, message) {
            $element.removeClass('success').addClass('error').html(OGMI.escapeHtml(message)).show();
        },
        
        /**
         * Show alert
         */
        showAlert: function(message) {
            alert(OGMI.escapeHtml(message));
        },
        
        /**
         * Validate email
         */
        isValidEmail: function(email) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },
        
        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        OGMI.init();
    });
    
    // Make OGMI available globally for debugging
    window.OGMI = OGMI;
    
})(jQuery);
