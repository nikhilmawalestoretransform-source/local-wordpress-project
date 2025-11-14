jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize chatbot admin functionality
    function initChatbotAdmin() {
        setupCharacterCounters();
        setupTabNavigation();
        setupFormValidation();
        setupPreviewFunctionality();
        setupImportExport();
        setupSettingsReset();
    }
    
    // Character counters for textareas
    function setupCharacterCounters() {
        $('textarea.large-text').each(function() {
            const $textarea = $(this);
            const maxLength = $textarea.attr('maxlength') || 1000;
            const $counter = $('<div class="char-counter">0/' + maxLength + ' characters</div>');
            $textarea.after($counter);
            
            function updateCounter() {
                const length = $textarea.val().length;
                $counter.text(length + '/' + maxLength + ' characters');
                
                if (length > maxLength * 0.9) {
                    $counter.addClass('warning');
                    $counter.removeClass('success');
                } else if (length > 0) {
                    $counter.addClass('success');
                    $counter.removeClass('warning');
                } else {
                    $counter.removeClass('warning success');
                }
            }
            
            $textarea.on('input', updateCounter);
            updateCounter();
        });
    }
    
    // Tab navigation
    function setupTabNavigation() {
        $('.chatbot-tab-link').on('click', function(e) {
            e.preventDefault();
            const target = $(this).data('target');
            
            // Update active tab
            $('.chatbot-tab-link').removeClass('active');
            $(this).addClass('active');
            
            // Show target pane
            $('.chatbot-tab-pane').removeClass('active');
            $(target).addClass('active');
            
            // Update URL hash
            window.location.hash = $(this).attr('href');
        });
        
        // Handle initial hash
        if (window.location.hash) {
            const tabLink = $('.chatbot-tab-link[href="' + window.location.hash + '"]');
            if (tabLink.length) {
                tabLink.click();
            }
        }
    }
    
    // Form validation
    function setupFormValidation() {
        $('form').on('submit', function(e) {
            let isValid = true;
            const $form = $(this);
            
            // Validate required fields
            $form.find('input[required], textarea[required]').each(function() {
                const $field = $(this);
                if (!$field.val().trim()) {
                    showFieldError($field, 'This field is required');
                    isValid = false;
                } else {
                    clearFieldError($field);
                }
            });
            
            // Validate emails
            $form.find('input[type="email"]').each(function() {
                const $field = $(this);
                const email = $field.val().trim();
                if (email && !isValidEmail(email)) {
                    showFieldError($field, 'Please enter a valid email address');
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showNotification('Please fix the errors before saving.', 'error');
            } else {
                showNotification('Settings saved successfully!', 'success');
            }
            
            return isValid;
        });
    }
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    function showFieldError($field, message) {
        $field.addClass('error');
        let $error = $field.siblings('.field-error');
        if (!$error.length) {
            $error = $('<div class="field-error" style="color: #d63638; font-size: 12px; margin-top: 5px;"></div>');
            $field.after($error);
        }
        $error.text(message);
    }
    
    function clearFieldError($field) {
        $field.removeClass('error');
        $field.siblings('.field-error').remove();
    }
    
    // Preview functionality
    function setupPreviewFunctionality() {
        $('.chatbot-preview-btn').on('click', function() {
            const $btn = $(this);
            const previewType = $btn.data('preview');
            
            $btn.prop('disabled', true).text('Generating Preview...');
            
            // Simulate preview generation
            setTimeout(() => {
                generatePreview(previewType);
                $btn.prop('disabled', false).text('Generate Preview');
            }, 1000);
        });
    }
    
    function generatePreview(type) {
        const $preview = $('.chatbot-preview-content');
        let content = '';
        
        switch(type) {
            case 'welcome':
                content = '<div class="message ai-message"><div class="message-content"><p>' + 
                         $('#store_chatbot_welcome_message').val() + 
                         '</p></div></div>';
                break;
            case 'validation':
                content = '<div class="form-group"><input type="text" placeholder="Your Name" class="error">' +
                         '<span class="error-message">' + $('#store_chatbot_name_required').val() + '</span></div>';
                break;
            case 'options':
                const options = $('#store_chatbot_main_options').val().split('\n');
                content = '<div class="options-container">';
                options.forEach((option, index) => {
                    if (option.trim()) {
                        content += '<button class="option-btn">' + (index + 1) + '. ' + option.trim() + '</button>';
                    }
                });
                content += '</div>';
                break;
        }
        
        $preview.html(content);
        $('.chatbot-preview').slideDown();
    }
    
    // Import/Export functionality
    function setupImportExport() {
        $('#chatbot-export-btn').on('click', function() {
            exportSettings();
        });
        
        $('#chatbot-import-btn').on('click', function() {
            $('#chatbot-import-file').click();
        });
        
        $('#chatbot-import-file').on('change', function(e) {
            importSettings(e);
        });
    }
    
    function exportSettings() {
        const settings = {
            contact: {
                company_name: $('#store_chatbot_company_name').val(),
                email: $('#store_chatbot_email').val(),
                phone: $('#store_chatbot_phone').val(),
                status_text: $('#store_chatbot_status_text').val()
            },
            messages: {
                welcome: $('#store_chatbot_welcome_message').val(),
                appreciation: $('#store_chatbot_appreciation_message').val(),
                assistance: $('#store_chatbot_assistance_message').val(),
                thankyou: $('#store_chatbot_thankyou_message').val(),
                final: $('#store_chatbot_final_message').val()
            },
            validation: {
                name_required: $('#store_chatbot_name_required').val(),
                email_required: $('#store_chatbot_email_required').val(),
                email_invalid: $('#store_chatbot_email_invalid').val(),
                phone_required: $('#store_chatbot_phone_required').val(),
                save_error: $('#store_chatbot_save_error').val(),
                network_error: $('#store_chatbot_network_error').val()
            },
            options: {
                main_options: $('#store_chatbot_main_options').val(),
                ecommerce_stages: $('#store_chatbot_ecommerce_stages').val(),
                ecommerce_platforms: $('#store_chatbot_ecommerce_platforms').val(),
                digital_focus: $('#store_chatbot_digital_focus').val(),
                digital_automation: $('#store_chatbot_digital_automation').val(),
                web_project_types: $('#store_chatbot_web_project_types').val(),
                web_technologies: $('#store_chatbot_web_technologies').val(),
                portfolio_interests: $('#store_chatbot_portfolio_interests').val(),
                pricing_services: $('#store_chatbot_pricing_services').val(),
                pricing_scales: $('#store_chatbot_pricing_scales').val()
            },
            export_date: new Date().toISOString(),
            version: '3.0'
        };
        
        const dataStr = JSON.stringify(settings, null, 2);
        const dataBlob = new Blob([dataStr], {type: 'application/json'});
        
        const link = document.createElement('a');
        link.href = URL.createObjectURL(dataBlob);
        link.download = 'store-chatbot-settings-' + new Date().toISOString().split('T')[0] + '.json';
        link.click();
        
        showNotification('Settings exported successfully!', 'success');
    }
    
    function importSettings(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const settings = JSON.parse(e.target.result);
                
                if (confirm('This will overwrite all current settings. Are you sure?')) {
                    applyImportedSettings(settings);
                    showNotification('Settings imported successfully!', 'success');
                }
            } catch (error) {
                showNotification('Error importing settings: Invalid file format', 'error');
            }
        };
        reader.readAsText(file);
        
        // Reset file input
        event.target.value = '';
    }
    
    function applyImportedSettings(settings) {
        // Contact settings
        if (settings.contact) {
            $('#store_chatbot_company_name').val(settings.contact.company_name || '');
            $('#store_chatbot_email').val(settings.contact.email || '');
            $('#store_chatbot_phone').val(settings.contact.phone || '');
            $('#store_chatbot_status_text').val(settings.contact.status_text || '');
        }
        
        // Message settings
        if (settings.messages) {
            $('#store_chatbot_welcome_message').val(settings.messages.welcome || '');
            $('#store_chatbot_appreciation_message').val(settings.messages.appreciation || '');
            $('#store_chatbot_assistance_message').val(settings.messages.assistance || '');
            $('#store_chatbot_thankyou_message').val(settings.messages.thankyou || '');
            $('#store_chatbot_final_message').val(settings.messages.final || '');
        }
        
        // Validation settings
        if (settings.validation) {
            $('#store_chatbot_name_required').val(settings.validation.name_required || '');
            $('#store_chatbot_email_required').val(settings.validation.email_required || '');
            $('#store_chatbot_email_invalid').val(settings.validation.email_invalid || '');
            $('#store_chatbot_phone_required').val(settings.validation.phone_required || '');
            $('#store_chatbot_save_error').val(settings.validation.save_error || '');
            $('#store_chatbot_network_error').val(settings.validation.network_error || '');
        }
        
        // Option settings
        if (settings.options) {
            $('#store_chatbot_main_options').val(settings.options.main_options || '');
            $('#store_chatbot_ecommerce_stages').val(settings.options.ecommerce_stages || '');
            $('#store_chatbot_ecommerce_platforms').val(settings.options.ecommerce_platforms || '');
            $('#store_chatbot_digital_focus').val(settings.options.digital_focus || '');
            $('#store_chatbot_digital_automation').val(settings.options.digital_automation || '');
            $('#store_chatbot_web_project_types').val(settings.options.web_project_types || '');
            $('#store_chatbot_web_technologies').val(settings.options.web_technologies || '');
            $('#store_chatbot_portfolio_interests').val(settings.options.portfolio_interests || '');
            $('#store_chatbot_pricing_services').val(settings.options.pricing_services || '');
            $('#store_chatbot_pricing_scales').val(settings.options.pricing_scales || '');
        }
    }
    
    // Settings reset functionality
    function setupSettingsReset() {
        $('.chatbot-reset-btn').on('click', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to reset all settings to default values? This action cannot be undone.')) {
                const $btn = $(this);
                const section = $btn.data('section');
                
                $btn.prop('disabled', true).text('Resetting...');
                
                // Simulate reset
                setTimeout(() => {
                    resetSection(section);
                    $btn.prop('disabled', false).