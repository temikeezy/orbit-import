/**
 * ORBIT Group Member Importer - Group Manager JavaScript
 * 
 * Handles AJAX interactions and file processing for the group management interface
 */

(function($) {
    'use strict';
    
    // Script bootstrap
    
    var OGMI = {
        currentFileId: null,
        currentMapping: {},
        isProcessing: false,
        currentStep: 1,
        
        /**
         * Initialize the plugin
         */
        init: function() {
            this.bindEvents();
            this.initDropzone();
            
            // Handle required fields on initialization
            this.handleRequiredFields(1);
        },
        
        /**
         * Show specific wizard step
         */
        showStep: function(step) {
            
            // Hide all steps
            $('.ogmi-wizard-step').hide();
            
            // Show the requested step
            $('#ogmi-step-' + step).show();
            
            // Update step indicator
            $('.ogmi-step').removeClass('ogmi-step-active ogmi-step-completed');
            $('.ogmi-step[data-step="' + step + '"]').addClass('ogmi-step-active');
            
            // Mark previous steps as completed
            for (var i = 1; i < step; i++) {
                $('.ogmi-step[data-step="' + i + '"]').addClass('ogmi-step-completed');
            }
            
            // Handle required attributes for hidden fields
            this.handleRequiredFields(step);
            
            this.currentStep = step;
        },
        
        /**
         * Handle required attributes for hidden fields
         */
        handleRequiredFields: function(step) {
            // Remove required attribute from all mapping fields when not on step 2
            if (step !== 2) {
                $('.ogmi-mapping-item select[required]').removeAttr('required');
            } else {
                // Add required attribute back when on step 2
                $('#map-email').attr('required', 'required');
            }
        },
        
        /**
         * Reset wizard to step 1
         */
        resetWizard: function() {
            this.currentFileId = null;
            this.currentMapping = {};
            this.isProcessing = false;
            this.currentStep = 1;
            
            // Reset all form elements
            $('#ogmi-file-input').val('');
            $('.ogmi-mapping-item select').val('');
            this.resetStats();
            
            // Show step 1
            this.showStep(1);
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var $form = $('#ogmi-quick-add-form');
            // Quick add - handled via button click to avoid nested form submit
            $(document).on('click', '#ogmi-quick-add-form .ogmi-button-primary', this.handleQuickAdd);
            
            // Also try to prevent any form submission on the page
            // Intercept any accidental submit on our container (defensive)
            $(document).on('submit', '#ogmi-quick-add-form', function(e) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            });
            
            // Bulk import toggle
            $(document).on('click', '#ogmi-toggle-bulk-import', this.toggleBulkImport);
            
            // File upload
            $(document).on('change', '#ogmi-file-input', this.handleFileSelect);
            
            // Mapping and import
            $(document).on('change', '.ogmi-mapping-item select', function() {
                OGMI.updateMapping();
            });
            $(document).on('click', '#ogmi-start-import', function() {
                OGMI.startImport();
            });
            $(document).on('click', '#ogmi-back-to-upload', function() {
                OGMI.showStep(1);
            });
            $(document).on('click', '#ogmi-start-new-import', function() {
                OGMI.resetWizard();
            });
            $(document).on('click', '#ogmi-close-import', function() {
                OGMI.closeImport();
            });
            
        },
        
        /**
         * Initialize drag and drop functionality
         */
        initDropzone: function() {
            // Check if OGMI object is available
            if (typeof OGMI === 'undefined') {
                return;
            }
            
            var dropzone = document.getElementById('ogmi-dropzone');
            
            if (!dropzone) {
                return;
            }
            
            // Check if already initialized
            if (dropzone.dataset.ogmiInitialized === 'true') {
                return;
            }
            
            
            // Mark as initialized
            dropzone.dataset.ogmiInitialized = 'true';
            
            // Drag and drop events using vanilla JavaScript
            dropzone.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('dragover');
            });
            
            dropzone.addEventListener('dragenter', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('dragover');
            });
            
            dropzone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('dragover');
            });
            
            dropzone.addEventListener('dragend', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('dragover');
            });
            
            dropzone.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('dragover');
                
                var files = e.dataTransfer.files;
                if (files.length > 0) {
                    var file = files[0];
                    OGMI.uploadFile(file);
                }
            });
            
            // Click to browse using vanilla JavaScript
            dropzone.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Create a new file input element
                var newFileInput = document.createElement('input');
                newFileInput.type = 'file';
                newFileInput.accept = '.csv';
                newFileInput.style.display = 'none';
                
                newFileInput.addEventListener('change', function(event) {
                    var file = event.target.files[0];
                    if (file) {
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
            e.stopPropagation();
            
            // Resolve the quick-add container regardless of whether the event
            // originated from a form submit or a button click inside the container
            var $form = $('#ogmi-quick-add-form');
            if ($form.length === 0 && e && e.currentTarget) {
                $form = $(e.currentTarget).closest('#ogmi-quick-add-form');
            }
            var $button = $form.find('button[type="submit"]');
            var $result = $('#ogmi-quick-result');
            
            // Validate email
            var email = $form.find('#quick-email').val().trim();
            if (!email || !OGMI.isValidEmail(email)) {
                OGMI.showError($result, OGMI_DATA.strings.invalidEmail);
                return;
            }
            
            $button.prop('disabled', true).text(OGMI_DATA.strings.processing);
            $result.hide();
            
            var ajaxData = {
                action: 'ogmi_add_member',
                nonce: OGMI_DATA.nonce,
                group_id: OGMI_DATA.groupId,
                email: email,
                first_name: $form.find('#quick-first-name').val().trim(),
                last_name: $form.find('#quick-last-name').val().trim(),
                role: $form.find('#quick-role').val()
            };
            
            $.ajax({
                url: OGMI_DATA.ajaxUrl,
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        var message = data.is_new ? OGMI_DATA.strings.userCreated : OGMI_DATA.strings.userExists;
                        OGMI.showSuccess($result, message);
                        $form.find('input[type="email"], input[type="text"]').val(''); // Reset only text/email inputs
                        
                        // Refresh the page after a short delay to show the new member
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        OGMI.showError($result, response.data.message || OGMI_DATA.strings.error);
                    }
                },
                error: function(xhr, status, error) {
                    var message = OGMI_DATA.strings.error;
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        message = xhr.responseJSON.data.message;
                    }
                    OGMI.showError($result, message);
                },
                complete: function() {
                    $button.prop('disabled', false).text('Add Member');
                }
            });
            
            return false; // Prevent any form submission
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
            var fileInput = document.getElementById('ogmi-file-input');
            if (!fileInput) {
                return;
            }
            
            if (!fileInput.files || fileInput.files.length === 0) {
                return;
            }
            
            var file = fileInput.files[0];
            OGMI.uploadFile(file);
        },
        
        /**
         * Upload file
         */
        uploadFile: function(file) {
            
            if (!file) {
                OGMI.showAlert(OGMI_DATA.strings.selectFile);
                return;
            }
            
            // Validate file type
            if (!file.name.toLowerCase().endsWith('.csv')) {
                OGMI.showAlert(OGMI_DATA.strings.invalidFile);
                return;
            }
            
            // Validate file size (10MB)
            if (file.size > 10 * 1024 * 1024) {
                OGMI.showAlert(OGMI_DATA.strings.fileTooLarge);
                return;
            }
            
            
            // Show progress
            OGMI.showUploadProgress();
            
            // Upload file
            var formData = new FormData();
            formData.append('action', 'ogmi_upload_file');
            formData.append('nonce', typeof OGMI_DATA !== 'undefined' ? OGMI_DATA.nonce : '');
            formData.append('group_id', typeof OGMI_DATA !== 'undefined' ? OGMI_DATA.groupId : '');
            formData.append('file', file);
            
            // Check if OGMI object is available
            if (typeof OGMI === 'undefined') {
                alert('Script configuration error. Please refresh the page and try again.');
                return;
            }
            
            var nonce = typeof OGMI_DATA !== 'undefined' ? OGMI_DATA.nonce : '';
            var groupId = typeof OGMI_DATA !== 'undefined' ? OGMI_DATA.groupId : '';
            var ajaxUrl = typeof OGMI_DATA !== 'undefined' ? OGMI_DATA.ajaxUrl : (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php');
            
            
            // Check if we have a valid nonce
            if (!nonce) {
                alert('Security token missing. Please refresh the page and try again.');
                return;
            }
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    OGMI.hideUploadProgress();
                    
                    // Check if response is HTML (error page) instead of JSON
                    if (typeof response === 'string' && response.includes('<!doctype html>')) {
                        OGMI.showAlert('Server error: Received HTML response instead of JSON. Please check your WordPress configuration.');
                        return;
                    }
                    
                    // Check if response is a proper JSON object
                    if (typeof response !== 'object' || response === null) {
                        OGMI.showAlert('Invalid response from server. Please try again.');
                        return;
                    }
                    
                    
                    if (response.success) {
                        OGMI.currentFileId = response.data.file_id;
                        OGMI.populateMappingOptions(response.data.headers);
                        OGMI.showFilePreview(response.data.preview_rows, response.data.headers);
                        
                        // Move to step 2 (mapping)
                        OGMI.showStep(2);
                    } else {
                        var errorMessage = OGMI_DATA.strings.error;
                        if (response.data && response.data.message) {
                            errorMessage = response.data.message;
                        }
                        OGMI.showAlert(errorMessage);
                    }
                },
                error: function(xhr, status, error) {
                    OGMI.hideUploadProgress();
                    var message = OGMI_DATA.strings.error;
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
            
            // Move to step 3 (import progress)
            OGMI.showStep(3);
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
            
            var nonce = typeof OGMI_DATA !== 'undefined' ? OGMI_DATA.nonce : '';
            var groupId = typeof OGMI_DATA !== 'undefined' ? OGMI_DATA.groupId : '';
            var ajaxUrl = typeof OGMI_DATA !== 'undefined' ? OGMI_DATA.ajaxUrl : (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php');
            
            
            var ajaxData = {
                action: 'ogmi_process_batch',
                nonce: nonce,
                file_id: OGMI.currentFileId,
                mapping: OGMI.currentMapping,
                batch_size: 10,
                offset: offset,
                group_id: groupId
            };
            
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: ajaxData,
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
                        OGMI.showAlert(response.data.message || OGMI_DATA.strings.error);
                        OGMI.isProcessing = false;
                    }
                },
                error: function(xhr) {
                    
                    var message = OGMI_DATA.strings.error;
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
            
            // Move to step 4 (results)
            OGMI.showStep(4);
            
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
            
            OGMI.addLogEntry(OGMI_DATA.strings.importComplete, 'success');
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
            $('#ogmi-bulk-import-section').slideUp();
            $('#ogmi-toggle-bulk-import').text('Bulk Import from CSV');
            this.resetWizard();
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
    
    // Expose minimal API if needed in future
    window.OGMI = OGMI;
    
})(jQuery);
