jQuery(document).ready(function($) {
    let chatFlow = {};
    let currentStep = 'user_info';
    let chatEntryId = null;
    let isSaving = false;
    let chatbotSettings = {};
    let isInitialized = false;
    let isConversationComplete = false;
    let hasStoredSession = false;
    
    // Load chatbot settings
    function loadChatbotSettings() {
        $.ajax({
            url: chatbot_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_chatbot_settings',
                nonce: chatbot_ajax.get_settings_nonce
            },
            success: function(response) {
                if (response.success) {
                    chatbotSettings = response.data;
                    updateDynamicContent();
                    initializeChatbot();
                } else {
                    console.error('Failed to load chatbot settings');
                    initializeWithDefaults();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading chatbot settings:', error);
                initializeWithDefaults();
            }
        });
    }
    
    function initializeWithDefaults() {
        chatbotSettings = {
            contact: {
                company_name: 'StoreTransform',
                email: 'hello@storetransform.com',
                phone: '+1 (555) 123-4567',
                status_text: 'Online - Transform your business today'
            },
            messages: {
                welcome: 'Welcome to Store Transform! Please provide your Details to begin.',
                appreciation: 'We appreciate you contacting us!',
                assistance: 'To best assist you, please choose how we can help:',
                thankyou: 'Thank you! We\'ve noted your request. A specialist will review your details and contact you soon. Thanks for your time!',
                final: 'Perfect! That clarifies the scope. We have recorded your interest in {service} - {details}.'
            },
            validation: {
                name_required: 'Please enter your name',
                email_required: 'Please enter your email',
                email_invalid: 'Please enter a valid email',
                phone_required: 'Please enter your phone number',
                save_error: 'Sorry, there was an error saving your information. Please try again.',
                network_error: 'Network error. Please check your connection and try again.'
            },
            options: {
                main_options: ['Web Development', 'E-commerce Development', 'Digital Transformation', 'View Portfolio', 'Get Pricing'],
                ecommerce_stages: ['New Store Build.', 'Platform Migration.', 'Optimization & Growth.'],
                ecommerce_platforms: ['Shopify/Shopify Plus.', 'WooCommerce.', 'Custom/Headless Solution.'],
                digital_focus: ['Process Automation.', 'Customer Experience (CX) Improvement', 'Data & Cloud Infrastructure'],
                digital_automation: ['Sales & CRM.', 'Internal Operations & HR', 'Finance & Accounting'],
                web_project_types: ['New Website Build', 'Existing Site Redesign', 'Ongoing Maintenance/Support'],
                web_technologies: ['Custom Development.', 'CMS (WordPress, Shopify, Magento)', 'Landing Page/Portfolio.'],
                portfolio_interests: ['E-commerce Development Case Studies', 'Digital Transformation Projects', 'General Web Design & Development'],
                pricing_services: ['New Project Estimate', 'Hourly Rate / Ongoing Support Pricing', 'Audit / Consultation Fee'],
                pricing_scales: ['Small Scale (Landing Page, Simple Redesign)', 'Medium Scale (Standard E-commerce Build)', 'Large Scale (Complex Platform Migration)']
            }
        };
        updateDynamicContent();
        initializeChatbot();
    }
    
    function updateDynamicContent() {
        // Update header content
        $('#dynamic-company-name').text(chatbotSettings.contact.company_name);
        $('#dynamic-status-text').text(chatbotSettings.contact.status_text);
        $('#dynamic-email').text(chatbotSettings.contact.email);
        $('#dynamic-phone').text(chatbotSettings.contact.phone);
    }
    
    // Check for existing session on page load
    function checkExistingSession() {
        const userEmail = getStoredUserEmail();
        if (userEmail) {
            // User has existing session, check for chat history
            loadChatHistory(userEmail);
            return true;
        }
        return false;
    }
    
    function getStoredUserEmail() {
        // Try to get from sessionStorage first, then cookies
        return sessionStorage.getItem('chatbot_user_email') || getCookie('chatbot_user_email');
    }
    
    function setStoredUserEmail(email) {
        sessionStorage.setItem('chatbot_user_email', email);
        // Also set cookie for cross-session persistence (optional)
        setCookie('chatbot_user_email', email, 7); // 7 days
    }
    
    function clearStoredSession() {
        sessionStorage.removeItem('chatbot_user_email');
        sessionStorage.removeItem('chatbot_user_info');
        deleteCookie('chatbot_user_email');
    }
    
    function loadChatHistory(userEmail) {
        $.ajax({
            url: chatbot_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_chat_history',
                nonce: chatbot_ajax.nonce,
                user_email: userEmail
            },
            success: function(response) {
                if (response.success) {
                    handleExistingSession(response.data);
                } else {
                    // No history found, clear stored session
                    clearStoredSession();
                    initializeNewChat();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading chat history:', error);
                initializeNewChat();
            }
        });
    }
    
    function handleExistingSession(sessionData) {
        hasStoredSession = true;
        chatFlow = sessionData.chat_data;
        chatEntryId = sessionData.entry_id;
        
        // Store user info for future use
        setStoredUserEmail(sessionData.user_info.email);
        sessionStorage.setItem('chatbot_user_info', JSON.stringify(sessionData.user_info));
        
        // Skip user info form and show chat directly
        $('#user-info-form').hide();
        $('#chatbot-options').show();
        $('#chatbot-session-actions').show();
        
        // Recreate chat history in UI
        recreateChatHistory();
        
        // Continue from where user left off
        continueFromLastStep();
    }
    
    function recreateChatHistory() {
        const messagesContainer = $('#chatbot-messages');
        messagesContainer.empty();
        
        // Add welcome message
        addMessage(chatbotSettings.messages.welcome, 'ai');
        
        // Add appreciation message if user info was already provided
        if (chatFlow.user_info) {
            addMessage(chatbotSettings.messages.appreciation, 'ai');
            addMessage(chatbotSettings.messages.assistance, 'ai');
        }
        
        // Recreate conversation flow based on stored data
        if (chatFlow.main_option) {
            addMessage(chatFlow.main_option, 'user');
            
            // Continue recreating the conversation based on stored data
            if (chatFlow.ecommerce_stage) {
                addMessage(chatFlow.ecommerce_stage, 'user');
            }
            if (chatFlow.ecommerce_platform) {
                addMessage(chatFlow.ecommerce_platform, 'user');
            }
            // Add more conditions for other steps...
        }
    }
    
    function continueFromLastStep() {
        // Determine the last completed step and show appropriate options
        if (chatFlow.pricing_scale || chatFlow.finalized) {
            // Conversation was completed
            finalClosure();
        } else if (chatFlow.pricing_service) {
            showOptions(chatbotSettings.options.pricing_scales || [], 'pricing_scale');
        } else if (chatFlow.portfolio_interest) {
            // Portfolio interest was selected
            handlePortfolioInterest(chatFlow.portfolio_interest);
        } else if (chatFlow.main_option) {
            // Main option was selected, show next appropriate options
            handleMainOptionContinuation(chatFlow.main_option);
        } else {
            // Show main options
            showMainOptions();
        }
    }
    
    function handleMainOptionContinuation(option) {
        let nextOptions = [];
        let message = "Welcome back! Let's continue where we left off.";
        
        switch(option) {
            case 'E-commerce Development':
                if (!chatFlow.ecommerce_stage) {
                    message = "Thanks for choosing E-commerce Development. To best understand your needs, what stage is your project currently in?";
                    nextOptions = chatbotSettings.options.ecommerce_stages || [];
                } else if (!chatFlow.ecommerce_platform) {
                    message = "Excellent. For your new store, which platform are you considering?";
                    nextOptions = chatbotSettings.options.ecommerce_platforms || [];
                }
                break;
            case 'Digital Transformation':
                if (!chatFlow.digital_focus) {
                    message = "Thanks for your interest in Digital Transformation. Which area is your primary focus?";
                    nextOptions = chatbotSettings.options.digital_focus || [];
                } else if (!chatFlow.digital_automation && chatFlow.digital_focus.includes('Process Automation')) {
                    message = "Great. Which specific area of your operations are you looking to automate?";
                    nextOptions = chatbotSettings.options.digital_automation || [];
                }
                break;
            case 'Web Development':
                if (!chatFlow.web_project_type) {
                    message = "Thanks for choosing Web Development. What type of project are you looking to launch?";
                    nextOptions = chatbotSettings.options.web_project_types || [];
                } else if (!chatFlow.web_technology) {
                    message = "Understood. What is the primary function or technology base you need?";
                    nextOptions = chatbotSettings.options.web_technologies || [];
                }
                break;
            case 'Get Pricing':
                if (!chatFlow.pricing_service) {
                    message = "Thanks for requesting pricing! What service are you primarily interested in?";
                    nextOptions = chatbotSettings.options.pricing_services || [];
                } else if (!chatFlow.pricing_scale && chatFlow.pricing_service.includes('New Project Estimate')) {
                    message = "Understood. What is the scale of the new project?";
                    nextOptions = chatbotSettings.options.pricing_scales || [];
                }
                break;
            default:
                showMainOptions();
                return;
        }
        
        if (message) {
            addMessage(message, 'ai');
        }
        
        if (nextOptions.length > 0) {
            showOptions(nextOptions, getNextStep(option));
        } else {
            finalizeConversation(option, "Not specified");
        }
    }
    
    function initializeChatbot() {
        if (isInitialized && !isConversationComplete) return;
        
        // Check for existing session first
        if (!checkExistingSession()) {
            // No existing session, start new chat
            if (isConversationComplete) {
                resetChat();
                isConversationComplete = false;
            }
            
            // Add welcome message for new users
            addMessage(chatbotSettings.messages.welcome, 'ai');
        }
        
        isInitialized = true;
    }
    
    // Toggle chatbot
    $('#chatbot-toggle').on('click', function() {
        $('#chatbot-window').toggleClass('active');
        
        if ($('#chatbot-window').hasClass('active')) {
            // If chat is being opened and conversation is complete, reset it
            if (isConversationComplete && !hasStoredSession) {
                resetChat();
                isConversationComplete = false;
            }
            
            if (!isInitialized) {
                loadChatbotSettings();
            }
        }
    });
    
    $('#close-chatbot').on('click', function() {
        $('#chatbot-window').removeClass('active');
    });
    
    // Start new chat button
    $('#start-new-chat').on('click', function() {
        resetChat();
    });
    
    // Close chatbot when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#store-chatbot-container').length && $('#chatbot-window').hasClass('active')) {
            $('#chatbot-window').removeClass('active');
        }
    });
    
    // User info form submission
    $('#submit-user-info').on('click', function() {
        if (validateUserInfo()) {
            processUserInfo();
        }
    });
    
    // Enter key support for form inputs
    $('#user-info-form input').on('keypress', function(e) {
        if (e.which === 13) {
            if (validateUserInfo()) {
                processUserInfo();
            }
        }
    });
    
    // Real-time validation
    $('#user-name').on('blur', function() {
        validateField('name', $(this).val().trim());
    });
    
    $('#user-email').on('blur', function() {
        validateField('email', $(this).val().trim());
    });
    
    $('#user-phone').on('blur', function() {
        validateField('phone', $(this).val().trim());
    });
    
    function validateField(field, value) {
        const errorElement = $('#' + field + '-error');
        
        switch(field) {
            case 'name':
                if (!value) {
                    errorElement.text(chatbotSettings.validation.name_required);
                    return false;
                }
                break;
            case 'email':
                if (!value) {
                    errorElement.text(chatbotSettings.validation.email_required);
                    return false;
                } else if (!isValidEmail(value)) {
                    errorElement.text(chatbotSettings.validation.email_invalid);
                    return false;
                }
                break;
            case 'phone':
                if (!value) {
                    errorElement.text(chatbotSettings.validation.phone_required);
                    return false;
                }
                break;
        }
        
        errorElement.text('');
        return true;
    }
    
    function validateUserInfo() {
        let isValid = true;
        const name = $('#user-name').val().trim();
        const email = $('#user-email').val().trim();
        const phone = $('#user-phone').val().trim();
        
        // Reset errors
        $('.error-message').text('');
        
        if (!name) {
            $('#name-error').text(chatbotSettings.validation.name_required);
            isValid = false;
        }
        
        if (!email) {
            $('#email-error').text(chatbotSettings.validation.email_required);
            isValid = false;
        } else if (!isValidEmail(email)) {
            $('#email-error').text(chatbotSettings.validation.email_invalid);
            isValid = false;
        }
        
        if (!phone) {
            $('#phone-error').text(chatbotSettings.validation.phone_required);
            isValid = false;
        }
        
        return isValid;
    }
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    function processUserInfo() {
        if (!validateUserInfo()) {
            return;
        }
        
        const userData = {
            name: $('#user-name').val().trim(),
            email: $('#user-email').val().trim(),
            phone: $('#user-phone').val().trim()
        };
        
        chatFlow.user_info = userData;
        
        // Store user email for session management
        setStoredUserEmail(userData.email);
        sessionStorage.setItem('chatbot_user_info', JSON.stringify(userData));
        
        // Save user info to database first
        saveChatData();
    }
    
    function saveChatData() {
        isSaving = true;
        
        // Show saving state
        $('#submit-user-info').prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: chatbot_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'save_chat_data',
                nonce: chatbot_ajax.nonce,
                chat_data: JSON.stringify(chatFlow)
            },
            success: function(response) {
                isSaving = false;
                
                if (response.success && response.data) {
                    if (response.data.entry_id) {
                        chatEntryId = response.data.entry_id;
                        proceedAfterSave();
                    } else {
                        handleSaveError('No entry ID received');
                    }
                } else {
                    handleSaveError(response.data || 'Save failed');
                }
            },
            error: function(xhr, status, error) {
                isSaving = false;
                handleSaveError(chatbotSettings.validation.network_error + ': ' + error);
            }
        });
    }
    
    function proceedAfterSave() {
        // Hide form and show options
        $('#user-info-form').hide();
        $('#chatbot-options').show();
        
        currentStep = 'main_options';
        
        // Add appreciation messages
        addMessage(chatbotSettings.messages.appreciation, 'ai');
        addMessage(chatbotSettings.messages.assistance, 'ai');
        
        // Show main options
        showMainOptions();
        
        // Reset the submit button
        $('#submit-user-info').prop('disabled', false).text('Start Conversation');
    }
    
    function handleSaveError(errorMessage) {
        console.error('Save error:', errorMessage);
        
        // Show error message to user
        addMessage(chatbotSettings.validation.save_error, 'ai');
        
        // Re-enable the form
        $('#submit-user-info').prop('disabled', false).text('Start Conversation');
        
        // Show the form again so user can retry
        $('#user-info-form').show();
        $('#chatbot-options').hide();
    }
    
    function showMainOptions() {
        if (chatbotSettings.options.main_options && chatbotSettings.options.main_options.length > 0) {
            showOptions(chatbotSettings.options.main_options, 'main_options');
        } else {
            // Fallback to default options
            showOptions(['Web Development', 'E-commerce Development', 'Digital Transformation', 'View Portfolio', 'Get Pricing'], 'main_options');
        }
    }
    
    function showOptions(options, step) {
        const container = $('.options-container');
        container.empty();
        
        if (!options || options.length === 0) {
            addMessage('No options available. Please contact administrator.', 'ai');
            return;
        }
        
        options.forEach((option, index) => {
            if (!option || option.trim() === '') return;
            
            const btn = $('<button>')
                .addClass('option-btn')
                .attr('type', 'button')
                .text((index + 1) + '. ' + option.trim())
                .data('step', step)
                .on('click', function() {
                    if (!chatEntryId) {
                        addMessage("System is still processing, please wait...", 'ai');
                        return;
                    }
                    const selectedOption = $(this).text().replace(/^\d+\.\s/, '');
                    const stepType = $(this).data('step');
                    handleOptionSelect(selectedOption, stepType);
                });
            container.append(btn);
        });
        
        // Add animation to options
        container.find('.option-btn').each(function(index) {
            $(this).css({
                'animation-delay': (index * 0.1) + 's'
            }).addClass('option-appear');
        });
    }
    
    function handleOptionSelect(option, step) {
        // Add user message immediately
        addMessage(option, 'user');
        
        // Update chat flow with the selected option IMMEDIATELY
        updateChatFlowImmediately(option, step);
        
        // Disable all option buttons to prevent multiple clicks
        $('.option-btn').prop('disabled', true).css('opacity', '0.6');
        
        // Show typing indicator
        showTypingIndicator();
        
        setTimeout(() => {
            handleFlowLogic(option, step);
        }, 1000);
    }
    
    function updateChatFlowImmediately(option, step) {
        // Append the selected option to chatFlow based on the step
        switch(step) {
            case 'main_options':
                chatFlow.main_option = option;
                break;
            case 'ecommerce_stage':
                chatFlow.ecommerce_stage = option;
                break;
            case 'ecommerce_platform':
                chatFlow.ecommerce_platform = option;
                break;
            case 'digital_focus':
                chatFlow.digital_focus = option;
                break;
            case 'digital_automation':
                chatFlow.digital_automation = option;
                break;
            case 'web_project_type':
                chatFlow.web_project_type = option;
                break;
            case 'web_technology':
                chatFlow.web_technology = option;
                break;
            case 'portfolio_interest':
                chatFlow.portfolio_interest = option;
                break;
            case 'pricing_service':
                chatFlow.pricing_service = option;
                break;
            case 'pricing_scale':
                chatFlow.pricing_scale = option;
                break;
        }
        
        // Update the database with the new chat flow data IMMEDIATELY
        if (chatEntryId) {
            updateChatDataImmediately();
        } else {
            setTimeout(() => {
                if (chatEntryId) {
                    updateChatDataImmediately();
                }
            }, 500);
        }
    }
    
    function handleFlowLogic(option, step) {
        // Re-enable buttons for next step
        $('.option-btn').prop('disabled', false).css('opacity', '1');
        
        switch(step) {
            case 'main_options':
                handleMainOption(option);
                break;
            case 'ecommerce_stage':
                handleEcommerceStage(option);
                break;
            case 'ecommerce_platform':
                handleEcommercePlatform(option);
                break;
            case 'digital_focus':
                handleDigitalFocus(option);
                break;
            case 'digital_automation':
                handleDigitalAutomation(option);
                break;
            case 'web_project_type':
                handleWebProjectType(option);
                break;
            case 'web_technology':
                handleWebTechnology(option);
                break;
            case 'portfolio_interest':
                handlePortfolioInterest(option);
                break;
            case 'pricing_service':
                handlePricingService(option);
                break;
            case 'pricing_scale':
                handlePricingScale(option);
                break;
            default:
                console.warn('Unknown step:', step);
                finalizeConversation("General Inquiry", option);
        }
    }
    
    function handleMainOption(option) {
        let nextOptions = [];
        let message = "";
        
        switch(option) {
            case 'E-commerce Development':
                message = "Thanks for choosing E-commerce Development. To best understand your needs, what stage is your project currently in?";
                nextOptions = chatbotSettings.options.ecommerce_stages || [];
                break;
                
            case 'Digital Transformation':
                message = "Thanks for your interest in Digital Transformation. Which area is your primary focus for modernizing your business processes?";
                nextOptions = chatbotSettings.options.digital_focus || [];
                break;
                
            case 'Web Development':
                message = "Thanks for choosing Web Development. What type of project are you looking to launch or update?";
                nextOptions = chatbotSettings.options.web_project_types || [];
                break;
                
            case 'View Portfolio':
                message = "We'd love to share our work! To give you the most relevant examples, what area of our services interests you most?";
                nextOptions = chatbotSettings.options.portfolio_interests || [];
                break;
                
            case 'Get Pricing':
                message = "Thanks for requesting pricing! To give you an accurate estimate, what service are you primarily interested in?";
                nextOptions = chatbotSettings.options.pricing_services || [];
                break;
                
            default:
                message = "Thank you for your interest! A specialist will contact you soon.";
                finalizeConversation("General Inquiry", option);
                return;
        }
        
        addMessage(message, 'ai');
        
        if (nextOptions.length > 0) {
            showOptions(nextOptions, getNextStep(option));
        } else {
            finalizeConversation(option, "Not specified");
        }
    }
    
    function getNextStep(currentOption) {
        switch(currentOption) {
            case 'E-commerce Development': return 'ecommerce_stage';
            case 'Digital Transformation': return 'digital_focus';
            case 'Web Development': return 'web_project_type';
            case 'View Portfolio': return 'portfolio_interest';
            case 'Get Pricing': return 'pricing_service';
            default: return 'main_options';
        }
    }
    
    function handleEcommerceStage(option) {
        addMessage("Excellent. For your new store, which platform are you considering, or do you have a preference?", 'ai');
        showOptions(chatbotSettings.options.ecommerce_platforms || [], 'ecommerce_platform');
    }
    
    function handleEcommercePlatform(option) {
        finalizeConversation("E-commerce Development", option);
    }
    
    function handleDigitalFocus(option) {
        if (option.includes('Process Automation')) {
            addMessage("Great. Which specific area of your operations are you looking to automate?", 'ai');
            showOptions(chatbotSettings.options.digital_automation || [], 'digital_automation');
        } else {
            finalizeConversation("Digital Transformation", option);
        }
    }
    
    function handleDigitalAutomation(option) {
        finalizeConversation("Digital Transformation", option);
    }
    
    function handleWebProjectType(option) {
        addMessage("Understood. What is the primary function or technology base you need for this new website?", 'ai');
        showOptions(chatbotSettings.options.web_technologies || [], 'web_technology');
    }
    
    function handleWebTechnology(option) {
        finalizeConversation("Web Development", option);
    }
    
    function handlePortfolioInterest(option) {
        let portfolioLink = '#';
        if (option.includes('E-commerce')) {
            portfolioLink = '/portfolio/ecommerce';
        } else if (option.includes('Digital Transformation')) {
            portfolioLink = '/portfolio/digital-transformation';
        } else {
            portfolioLink = '/portfolio/web-development';
        }
        
        addMessage(`Perfect! You can view our portfolio focused on ${option} here: <a href="${portfolioLink}" target="_blank" style="color: #667eea; text-decoration: underline;">View Portfolio</a>`, 'ai');
        finalClosure();
    }
    
    function handlePricingService(option) {
        if (option.includes('New Project Estimate')) {
            addMessage("Understood. What is the scale of the new project you are looking to get a price for?", 'ai');
            showOptions(chatbotSettings.options.pricing_scales || [], 'pricing_scale');
        } else {
            finalizeConversation("Pricing Request", option);
        }
    }
    
    function handlePricingScale(option) {
        finalizeConversation("Pricing Request", option);
    }
    
    function finalizeConversation(service, details) {
        const finalMessage = chatbotSettings.messages.final
            .replace('{service}', service)
            .replace('{details}', details);
            
        addMessage(finalMessage, 'ai');
        
        // Mark as finalized
        chatFlow.finalized = true;
        
        // Update final data before closure
        if (chatEntryId) {
            updateChatDataImmediately();
        }
        
        finalClosure();
    }
    
    function finalClosure() {
        setTimeout(() => {
            addMessage(chatbotSettings.messages.thankyou, 'ai');
            
            // Hide options and session actions
            $('#chatbot-options').hide();
            $('#chatbot-session-actions').hide();
            
            // Send final email with complete data
            sendAdminEmail();
            
            // Mark conversation as complete
            isConversationComplete = true;
            
        }, 1500);
    }
    
    function resetChat() {
        console.log('Resetting chat...');
        
        // Clear form
        $('#user-name').val('');
        $('#user-email').val('');
        $('#user-phone').val('');
        
        // Reset errors
        $('.error-message').text('');
        
        // Show form again
        $('#user-info-form').show();
        $('#chatbot-options').hide();
        $('#chatbot-session-actions').hide();
        
        // Clear all messages
        $('#chatbot-messages').empty();
        
        // Reset variables
        chatFlow = {};
        currentStep = 'user_info';
        chatEntryId = null;
        isConversationComplete = false;
        hasStoredSession = false;
        
        // Clear stored session
        clearStoredSession();
        
        // Add welcome message again
        addMessage(chatbotSettings.messages.welcome, 'ai');
        
        // Reset the submit button
        $('#submit-user-info').prop('disabled', false).text('Start Conversation');
    }
    
    function addMessage(text, type) {
        const messagesContainer = $('#chatbot-messages');
        const messageDiv = $('<div>').addClass('message ' + type + '-message');
        const contentDiv = $('<div>').addClass('message-content');
        
        contentDiv.html('<p>' + text + '</p>');
        messageDiv.append(contentDiv);
        messagesContainer.append(messageDiv);
        
        // Scroll to bottom
        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
        
        // Remove typing indicator if present
        $('.typing-indicator').remove();
        
        // Add message animation
        messageDiv.hide().fadeIn(400);
    }
    
    function showTypingIndicator() {
        const messagesContainer = $('#chatbot-messages');
        const messageDiv = $('<div>').addClass('message ai-message typing-message');
        const contentDiv = $('<div>').addClass('message-content');
        
        contentDiv.html('<div class="typing-indicator"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div>');
        messageDiv.append(contentDiv);
        messagesContainer.append(messageDiv);
        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
    }
    
    function updateChatDataImmediately() {
        if (!chatEntryId) {
            return;
        }
        
        $.ajax({
            url: chatbot_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'update_chat_data',
                nonce: chatbot_ajax.nonce,
                entry_id: chatEntryId,
                chat_data: JSON.stringify(chatFlow)
            },
            success: function(response) {
                // Successfully updated
            },
            error: function(xhr, status, error) {
                console.error('Error in immediate chat data update:', error);
            }
        });
    }
    
    function sendAdminEmail() {
        $.ajax({
            url: chatbot_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'send_admin_email',
                nonce: chatbot_ajax.nonce,
                chat_data: JSON.stringify(chatFlow),
                entry_id: chatEntryId
            },
            success: function(response) {
                // Email sent successfully
            },
            error: function(xhr, status, error) {
                console.error('Error sending email:', error);
            }
        });
    }
    
    // Cookie helper functions
    function setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = "expires=" + date.toUTCString();
        document.cookie = name + "=" + value + ";" + expires + ";path=/";
    }
    
    function getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
    
    function deleteCookie(name) {
        document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
    }
    
    // Initialize the chatbot when page loads
    $(window).on('load', function() {
        // Preload settings but don't initialize chat until opened
        loadChatbotSettings();
    });
});