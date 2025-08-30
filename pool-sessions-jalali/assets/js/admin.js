/**
 * Pool Sessions Jalali - Admin JavaScript
 * 
 * Handles admin page interactions and AJAX requests
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        initializeAdmin();
    });
    
    /**
     * Initialize admin functionality
     */
    function initializeAdmin() {
        bindAdminEvents();
        initializeColorPickers();
        initializeServiceForm();
    }
    
    /**
     * Bind admin event handlers
     */
    function bindAdminEvents() {
        // Service form submission
        $('#pool-service-form').on('submit', function(e) {
            e.preventDefault();
            saveService();
        });
        
        // Service edit buttons
        $(document).on('click', '.edit-service', function() {
            const serviceName = $(this).data('service');
            editService(serviceName);
        });
        
        // Service delete buttons
        $(document).on('click', '.delete-service', function() {
            const serviceName = $(this).data('service');
            deleteService(serviceName);
        });
        
        // Export settings button
        $('#export-settings').on('click', function() {
            exportSettings();
        });
        
        // Settings import form
        $('#pool-settings-import-form').on('submit', function(e) {
            e.preventDefault();
            importSettings();
        });
        
        // CSV import form
        $('#pool-csv-import-form').on('submit', function(e) {
            e.preventDefault();
            importCSV();
        });
        
        // ICS import form
        $('#pool-ics-import-form').on('submit', function(e) {
            e.preventDefault();
            importICS();
        });
        
        // File input change handlers
        $('#csv_file').on('change', function() {
            handleFileSelection(this, 'csv');
        });
        
        $('#ics_file').on('change', function() {
            handleFileSelection(this, 'ics');
        });
        
        $('#settings_file').on('change', function() {
            handleFileSelection(this, 'json');
        });
    }
    
    /**
     * Initialize color pickers
     */
    function initializeColorPickers() {
        // Initialize color picker inputs
        $('input[type="color"]').each(function() {
            $(this).on('change', function() {
                updateColorPreview($(this));
            });
        });
    }
    
    /**
     * Initialize service form
     */
    function initializeServiceForm() {
        // Auto-generate slug from service name
        $('#service_name').on('input', function() {
            const name = $(this).val();
            const slug = generateSlug(name);
            $('#service_slug').val(slug);
        });
    }
    
    /**
     * Generate URL-friendly slug
     */
    function generateSlug(text) {
        return text
            .toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
    
    /**
     * Update color preview
     */
    function updateColorPreview(colorInput) {
        const color = colorInput.val();
        const fieldName = colorInput.attr('name');
        
        // Update preview if available
        const preview = $('.color-preview[data-field="' + fieldName + '"]');
        if (preview.length) {
            preview.css('background-color', color);
        }
    }
    
    /**
     * Save service
     */
    function saveService() {
        const formData = {
            action: 'pool_sessions_save_service',
            nonce: poolSessionsAjax.nonce,
            service_name: $('#service_name').val(),
            service_slug: $('#service_slug').val(),
            service_color: $('#service_color').val()
        };
        
        // Validate form
        if (!formData.service_name || !formData.service_slug) {
            showAdminMessage('Service name and slug are required.', 'error');
            return;
        }
        
        // Show loading state
        const submitBtn = $('#pool-service-form button[type="submit"]');
        const originalText = submitBtn.text();
        submitBtn.prop('disabled', true).text('Saving...');
        
        // Make AJAX request
        $.ajax({
            url: poolSessionsAjax.ajaxurl,
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showAdminMessage(response.data, 'success');
                    resetServiceForm();
                    refreshServicesList();
                } else {
                    showAdminMessage(response.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                showAdminMessage('Error saving service: ' + error, 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    }
    
    /**
     * Edit service
     */
    function editService(serviceName) {
        // Populate form with service data
        $('#service_name').val(serviceName);
        $('#service_slug').val(generateSlug(serviceName));
        
        // Get service color from options
        const options = getPluginOptions();
        if (options.service_colors && options.service_colors[serviceName]) {
            $('#service_color').val(options.service_colors[serviceName]);
        }
        
        // Change form button text
        $('#pool-service-form button[type="submit"]').text('Update Service');
        
        // Scroll to form
        $('html, body').animate({
            scrollTop: $('#pool-service-form').offset().top - 50
        }, 500);
    }
    
    /**
     * Delete service
     */
    function deleteService(serviceName) {
        if (!confirm('Are you sure you want to delete the service "' + serviceName + '"? This action cannot be undone.')) {
            return;
        }
        
        const formData = {
            action: 'pool_sessions_delete_service',
            nonce: poolSessionsAjax.nonce,
            service_name: serviceName
        };
        
        $.ajax({
            url: poolSessionsAjax.ajaxurl,
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showAdminMessage(response.data, 'success');
                    refreshServicesList();
                } else {
                    showAdminMessage(response.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                showAdminMessage('Error deleting service: ' + error, 'error');
            }
        });
    }
    
    /**
     * Export settings
     */
    function exportSettings() {
        // Create a temporary form to trigger download
        const form = $('<form></form>')
            .attr('method', 'POST')
            .attr('action', poolSessionsAjax.ajaxurl);
        
        const actionInput = $('<input></input>')
            .attr('type', 'hidden')
            .attr('name', 'action')
            .val('pool_sessions_export_settings');
        
        const nonceInput = $('<input></input>')
            .attr('type', 'hidden')
            .attr('name', 'nonce')
            .val(poolSessionsAjax.nonce);
        
        form.append(actionInput).append(nonceInput);
        $('body').append(form);
        form.submit();
        form.remove();
    }
    
    /**
     * Import settings
     */
    function importSettings() {
        const fileInput = $('#settings_file')[0];
        if (!fileInput.files.length) {
            showAdminMessage('Please select a settings file to import.', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'pool_sessions_import_settings');
        formData.append('nonce', poolSessionsAjax.nonce);
        formData.append('settings_file', fileInput.files[0]);
        
        // Show loading state
        const submitBtn = $('#pool-settings-import-form button[type="submit"]');
        const originalText = submitBtn.text();
        submitBtn.prop('disabled', true).text('Importing...');
        
        $.ajax({
            url: poolSessionsAjax.ajaxurl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showAdminMessage(response.data, 'success');
                    // Reload page to reflect new settings
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showAdminMessage(response.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                showAdminMessage('Error importing settings: ' + error, 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).text(originalText);
                // Reset file input
                fileInput.value = '';
            }
        });
    }
    
    /**
     * Import CSV
     */
    function importCSV() {
        const fileInput = $('#csv_file')[0];
        if (!fileInput.files.length) {
            showAdminMessage('Please select a CSV file to import.', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'pool_sessions_import_csv');
        formData.append('nonce', poolSessionsAjax.nonce);
        formData.append('csv_file', fileInput.files[0]);
        
        // Show loading state
        const submitBtn = $('#pool-csv-import-form button[type="submit"]');
        const originalText = submitBtn.text();
        submitBtn.prop('disabled', true).text('Importing...');
        
        // Show progress indicator
        showImportProgress('Importing CSV file...');
        
        $.ajax({
            url: poolSessionsAjax.ajaxurl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showAdminMessage(response.data, 'success');
                    showImportProgress('Import completed successfully!', 'success');
                } else {
                    showAdminMessage(response.data, 'error');
                    showImportProgress('Import failed!', 'error');
                }
            },
            error: function(xhr, status, error) {
                showAdminMessage('Error importing CSV: ' + error, 'error');
                showImportProgress('Import failed!', 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).text(originalText);
                // Reset file input
                fileInput.value = '';
                
                // Hide progress after delay
                setTimeout(function() {
                    hideImportProgress();
                }, 3000);
            }
        });
    }
    
    /**
     * Import ICS
     */
    function importICS() {
        const fileInput = $('#ics_file')[0];
        if (!fileInput.files.length) {
            showAdminMessage('Please select an ICS file to import.', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'pool_sessions_import_ics');
        formData.append('nonce', poolSessionsAjax.nonce);
        formData.append('ics_file', fileInput.files[0]);
        
        // Show loading state
        const submitBtn = $('#pool-ics-import-form button[type="submit"]');
        const originalText = submitBtn.text();
        submitBtn.prop('disabled', true).text('Importing...');
        
        // Show progress indicator
        showImportProgress('Importing ICS file...');
        
        $.ajax({
            url: poolSessionsAjax.ajaxurl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showAdminMessage(response.data, 'success');
                    showImportProgress('Import completed successfully!', 'success');
                } else {
                    showAdminMessage(response.data, 'error');
                    showImportProgress('Import failed!', 'error');
                }
            },
            error: function(xhr, status, error) {
                showAdminMessage('Error importing ICS: ' + error, 'error');
                showImportProgress('Import failed!', 'error');
            },
            complete: function() {
                submitBtn.prop('disabled', false).text(originalText);
                // Reset file input
                fileInput.value = '';
                
                // Hide progress after delay
                setTimeout(function() {
                    hideImportProgress();
                }, 3000);
            }
        });
    }
    
    /**
     * Handle file selection
     */
    function handleFileSelection(input, type) {
        const file = input.files[0];
        if (!file) return;
        
        // Validate file type
        const allowedTypes = {
            'csv': ['text/csv', 'application/csv'],
            'ics': ['text/calendar', 'application/ics'],
            'json': ['application/json']
        };
        
        if (!allowedTypes[type].includes(file.type)) {
            showAdminMessage('Invalid file type. Please select a ' + type.toUpperCase() + ' file.', 'error');
            input.value = '';
            return;
        }
        
        // Show file info
        const fileInfo = $('<div class="file-info"></div>')
            .html('<strong>Selected file:</strong> ' + file.name + ' (' + formatFileSize(file.size) + ')');
        
        const container = input.closest('td');
        container.find('.file-info').remove();
        container.append(fileInfo);
    }
    
    /**
     * Format file size
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    /**
     * Reset service form
     */
    function resetServiceForm() {
        $('#pool-service-form')[0].reset();
        $('#pool-service-form button[type="submit"]').text('Save Service');
    }
    
    /**
     * Refresh services list
     */
    function refreshServicesList() {
        // Reload the services list section
        location.reload();
    }
    
    /**
     * Get plugin options
     */
    function getPluginOptions() {
        // This would typically come from localized data
        // For now, return empty object
        return {};
    }
    
    /**
     * Show admin message
     */
    function showAdminMessage(message, type = 'info') {
        // Create message element
        const messageEl = $('<div class="notice notice-' + type + ' is-dismissible"></div>')
            .html('<p>' + message + '</p>')
            .append('<button type="button" class="notice-dismiss"></button>');
        
        // Insert at top of page
        $('.wrap h1').after(messageEl);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            messageEl.fadeOut();
        }, 5000);
        
        // Handle dismiss button
        messageEl.find('.notice-dismiss').on('click', function() {
            messageEl.fadeOut();
        });
    }
    
    /**
     * Show import progress
     */
    function showImportProgress(message, type = 'info') {
        hideImportProgress();
        
        const progressEl = $('<div class="import-progress notice notice-' + type + '"></div>')
            .html('<p>' + message + '</p>');
        
        $('.wrap h1').after(progressEl);
    }
    
    /**
     * Hide import progress
     */
    function hideImportProgress() {
        $('.import-progress').remove();
    }
    
})(jQuery);
