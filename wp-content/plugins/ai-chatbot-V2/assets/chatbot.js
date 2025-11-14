jQuery(document).ready(function($) {
    let chatFlow = {};
    let currentStep = 'user_info';
    let chatEntryId = null;
    let isSaving = false;
    
    // Toggle chatbot
    $('#chatbot-toggle').on('click', function() {
        $('#chatbot-window').toggleClass('active');
    });
    
    $('#close-chatbot').on('click', function() {
        $('#chatbot-window').removeClass('active');
    });
    
    // User info form submission
    $('#submit-user-info').on('click', function() {
        if (validateUserInfo()) {
            processUserInfo();
        }
    });
    
    // Enter key support
    $('#user-info-form input').on('keypress', function(e) {
        if (e.which === 13) {
            if (validateUserInfo()) {
                processUserInfo();
            }
        }
    });
    
    function validateUserInfo() {
        let isValid = true;
        const name = $('#user-name').val().trim();
        const email = $('#user-email').val().trim();
        const phone = $('#user-phone').val().trim();
        
        // Reset errors
        $('.error-message').text('');
        
        if (!name) {
            $('#user-name').siblings('.error-message').text('Please enter your name');
            isValid = false;
        }
        
        if (!email || !isValidEmail(email)) {
            $('#user-email').siblings('.error-message').text('Please enter a valid email');
            isValid = false;
        }
        
        if (!phone) {
            $('#user-phone').siblings('.error-message').text('Please enter your phone number');
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
        
       // console.log('Processing user info, starting save...');
        
        // Save user info to database first
        saveChatData();
    }
    
    function saveChatData() {
       // console.log('Saving initial chat data:', chatFlow);
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
              //  console.log('Full AJAX response:', response);
                isSaving = false;
                
                if (response.success && response.data) {
                   // console.log('Chat data saved successfully:', response.data);
                    
                    if (response.data.entry_id) {
                        chatEntryId = response.data.entry_id;
                      //  console.log('Chat entry ID stored:', chatEntryId);
                        
                        // Now proceed with the conversation flow
                        proceedAfterSave();
                        
                    } else {
                        console.error('No entry_id in response:', response);
                        handleSaveError('No entry ID received');
                    }
                } else {
                    console.error('Save failed:', response);
                    handleSaveError(response.data || 'Save failed');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error saving chat data:', error);
               // console.log('XHR response:', xhr.responseText);
                isSaving = false;
                handleSaveError('Network error: ' + error);
            }
        });
    }
    
    function proceedAfterSave() {
        // Hide form and show options
        $('#user-info-form').hide();
        $('#chatbot-options').show();
        
        currentStep = 'main_options';
        
        // Add appreciation messages
        addMessage("We appreciate you contacting us!", 'ai');
        addMessage("To best assist you, please choose how we can help:", 'ai');
        
        // Show main options
        showMainOptions();
        
        // Reset the submit button (though form is hidden)
        $('#submit-user-info').prop('disabled', false).text('Start Conversation');
        
        //console.log('Proceeding to main options with entry ID:', chatEntryId);
    }
    
    function handleSaveError(errorMessage) {
        console.error('Save error:', errorMessage);
        
        // Show error message to user
        addMessage("Sorry, there was an error saving your information. Please try again.", 'ai');
        
        // Re-enable the form
        $('#submit-user-info').prop('disabled', false).text('Start Conversation');
        
        // Show the form again so user can retry
        $('#user-info-form').show();
        $('#chatbot-options').hide();
    }
    
    function showMainOptions() {
        const options = [
            'Web Development',
            'E-commerce Development',
            'Digital Transformation',
            'View Portfolio',
            'Get Pricing'
        ];
        
        showOptions(options, 'main_options');
    }
    
    function showOptions(options, step) {
        const container = $('.options-container');
        container.empty();
        
        options.forEach((option, index) => {
            const btn = $('<button>')
                .addClass('option-btn')
                .text((index + 1) + '. ' + option)
                .data('step', step)
                .on('click', function() {
                    if (!chatEntryId) {
                       // console.log('Waiting for chatEntryId... Current ID:', chatEntryId);
                        addMessage("System is still processing, please wait...", 'ai');
                        return;
                    }
                    const selectedOption = $(this).text().replace(/^\d+\.\s/, '');
                    const stepType = $(this).data('step');
                    handleOptionSelect(selectedOption, stepType);
                });
            container.append(btn);
        });
    }
    
    function handleOptionSelect(option, step) {
       // console.log('Option selected:', option, 'Step:', step, 'Entry ID:', chatEntryId);
        
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
      //  console.log('IMMEDIATE UPDATE - Step:', step, 'Option:', option);
        
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
        
        //console.log('Current chatFlow after immediate update:', chatFlow);
        
        // Update the database with the new chat flow data IMMEDIATELY
        if (chatEntryId) {
            updateChatDataImmediately();
        } else {
           // console.log('No chatEntryId available for immediate update - will retry');
            // Retry after a short delay
            setTimeout(() => {
                if (chatEntryId) {
                    updateChatDataImmediately();
                }
            }, 500);
        }
    }
    
    function handleFlowLogic(option, step) {
        //console.log('Handling flow logic - Step:', step, 'Option:', option);
        
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
        }
    }
    
    function handleMainOption(option) {
        switch(option) {
            case 'E-commerce Development':
                addMessage("Thanks for choosing E-commerce Development. To best understand your needs, what stage is your project currently in?", 'ai');
                showOptions([
                    'New Store Build.',
                    'Platform Migration.',
                    'Optimization & Growth.'
                ], 'ecommerce_stage');
                break;
                
            case 'Digital Transformation':
                addMessage("Thanks for your interest in Digital Transformation. Which area is your primary focus for modernizing your business processes?", 'ai');
                showOptions([
                    'Process Automation.',
                    'Customer Experience (CX) Improvement',
                    'Data & Cloud Infrastructure'
                ], 'digital_focus');
                break;
                
            case 'Web Development':
                addMessage("Thanks for choosing Web Development. What type of project are you looking to launch or update?", 'ai');
                showOptions([
                    'New Website Build',
                    'Existing Site Redesign',
                    'Ongoing Maintenance/Support'
                ], 'web_project_type');
                break;
                
            case 'View Portfolio':
                addMessage("We'd love to share our work! To give you the most relevant examples, what area of our services interests you most?", 'ai');
                showOptions([
                    'E-commerce Development Case Studies',
                    'Digital Transformation Projects',
                    'General Web Design & Development'
                ], 'portfolio_interest');
                break;
                
            case 'Get Pricing':
                addMessage("Thanks for requesting pricing! To give you an accurate estimate, what service are you primarily interested in?", 'ai');
                showOptions([
                    'New Project Estimate',
                    'Hourly Rate / Ongoing Support Pricing',
                    'Audit / Consultation Fee'
                ], 'pricing_service');
                break;
        }
    }
    
    function handleEcommerceStage(option) {
        addMessage("Excellent. For your new store, which platform are you considering, or do you have a preference?", 'ai');
        showOptions([
            'Shopify/Shopify Plus.',
            'WooCommerce.',
            'Custom/Headless Solution.'
        ], 'ecommerce_platform');
    }
    
    function handleEcommercePlatform(option) {
        finalizeConversation("E-commerce Development", option);
    }
    
    function handleDigitalFocus(option) {
        if (option.includes('Process Automation')) {
            addMessage("Great. Which specific area of your operations are you looking to automate?", 'ai');
            showOptions([
                'Sales & CRM.',
                'Internal Operations & HR',
                'Finance & Accounting'
            ], 'digital_automation');
        } else {
            finalizeConversation("Digital Transformation", option);
        }
    }
    
    function handleDigitalAutomation(option) {
        finalizeConversation("Digital Transformation", option);
    }
    
    function handleWebProjectType(option) {
        addMessage("Understood. What is the primary function or technology base you need for this new website?", 'ai');
        showOptions([
            'Custom Development.',
            'CMS (WordPress, Shopify, Magento)',
            'Landing Page/Portfolio.'
        ], 'web_technology');
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
        
        addMessage(`Perfect! You can view our portfolio focused on ${option} here: <a href="${portfolioLink}" target="_blank">View Portfolio</a>`, 'ai');
        finalClosure();
    }
    
    function handlePricingService(option) {
        if (option.includes('New Project Estimate')) {
            addMessage("Understood. What is the scale of the new project you are looking to get a price for?", 'ai');
            showOptions([
                'Small Scale (Landing Page, Simple Redesign)',
                'Medium Scale (Standard E-commerce Build)',
                'Large Scale (Complex Platform Migration)'
            ], 'pricing_scale');
        } else {
            finalizeConversation("Pricing Request", option);
        }
    }
    
    function handlePricingScale(option) {
        finalizeConversation("Pricing Request", option);
    }
    
    function finalizeConversation(service, details) {
        addMessage(`Perfect! That clarifies the scope. We have recorded your interest in ${service} - ${details}.`, 'ai');
        
        // Update final data before closure
        if (chatEntryId) {
            updateChatDataImmediately();
        }
        
        finalClosure();
    }
    
    function finalClosure() {
        setTimeout(() => {
            addMessage("Thank you! We've noted your request. A specialist will review your details and contact you soon. Thanks for your time!", 'ai');
            
            // Hide options
            $('#chatbot-options').hide();
            
            // Send final email with complete data
            sendAdminEmail();
            
        }, 1500);
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
    }
    
    function showTypingIndicator() {
        const messagesContainer = $('#chatbot-messages');
        const messageDiv = $('<div>').addClass('message ai-message');
        const contentDiv = $('<div>').addClass('message-content');
        
        contentDiv.html('<div class="typing-indicator"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div>');
        messageDiv.append(contentDiv);
        messagesContainer.append(messageDiv);
        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
    }
    
    function updateChatDataImmediately() {
        if (!chatEntryId) {
           // console.log('No chat entry ID found for immediate update');
            return;
        }
        
      //  console.log('IMMEDIATE DB UPDATE - Entry:', chatEntryId, 'Data:', chatFlow);
        
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
               // console.log('Chat data updated IMMEDIATELY successfully:', response);
            },
            error: function(xhr, status, error) {
                console.error('Error in immediate chat data update:', error);
              //  console.log('XHR response:', xhr.responseText);
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
               // console.log('Admin notification sent:', response);
            },
            error: function(xhr, status, error) {
                console.error('Error sending email:', error);
            }
        });
    }
});