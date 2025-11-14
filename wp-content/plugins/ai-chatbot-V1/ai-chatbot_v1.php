<?php
/**
 * Plugin Name: Store Transform AI Chatbot-V1
 * Description: AI Chatbot for Store Transform with user data collection and conversation flow
 * Version: 1.0
 * Author: Store Transform
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class StoreTransformChatbot {
    
    public function __construct() {
        add_action('wp_footer', array($this, 'display_chatbot'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_save_chat_data', array($this, 'save_chat_data'));
        add_action('wp_ajax_nopriv_save_chat_data', array($this, 'save_chat_data'));
        add_action('wp_ajax_update_chat_data', array($this, 'update_chat_data'));
        add_action('wp_ajax_nopriv_update_chat_data', array($this, 'update_chat_data'));
        add_action('wp_ajax_send_admin_email', array($this, 'send_admin_email'));
        add_action('wp_ajax_nopriv_send_admin_email', array($this, 'send_admin_email'));
        
        register_activation_hook(__FILE__, array($this, 'create_chatbot_table'));
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_localize_script('jquery', 'chatbot_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('chatbot_nonce')
        ));
    }
    
    public function create_chatbot_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'store_ai_chatbot_v1';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_name varchar(100) NOT NULL,
            user_email varchar(100) NOT NULL,
            user_phone varchar(20) NOT NULL,
            chat_data text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function display_chatbot() {
        ?>
        <div id="store-chatbot-container">
            <div id="chatbot-toggle">
                <div class="chatbot-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 2H4C2.9 2 2 2.9 2 4V22L6 18H20C21.1 18 22 17.1 22 16V4C22 2.9 21.1 2 20 2Z" fill="white"/>
                    </svg>
                </div>
                
            </div>
            
            <div id="chatbot-window">
                <!-- StoreTransform Header -->
                <div id="chatbot-header">
                    <div class="header-content">
                        <div class="store-logo">
                            <div class="logo-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M20 2H4c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-9 18H5v-2h6v2zm8-4h-6v-2h6v2zm0-4h-6v-2h6v2z"/>
                                </svg>
                            </div>
                            <div class="logo-text">
                                <strong>StoreTransform</strong>
                                <span>Online</span>
                            </div>
                        </div>
                    </div>
                    <button id="close-chatbot">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                        </svg>
                    </button>
                </div>
                
                <div id="chatbot-messages">
                    <!-- Welcome Message -->
                    <div class="message ai-message">
                        <div class="message-content">
                            <p>Welcome to Store Transform! Please provide your Details to begin.</p>
                        </div>
                    </div>
                </div>
                
                <!-- User Info Form - Show immediately after welcome -->
                <div id="user-info-form" class="active">
                    <div class="form-section">
                        <div class="form-group">
                            <input type="text" id="user-name" placeholder="Your Name" required>
                            <span class="error-message"></span>
                        </div>
                        <div class="form-group">
                            <input type="email" id="user-email" placeholder="Your Email" required>
                            <span class="error-message"></span>
                        </div>
                        <div class="form-group">
                            <input type="tel" id="user-phone" placeholder="Your Phone" required>
                            <span class="error-message"></span>
                        </div>
                        <button id="submit-user-info" class="btn-primary">Start Conversation</button>
                    </div>
                </div>
                
                <!-- Chat Options -->
                <div id="chatbot-options" style="display: none;">
                    <div class="options-container">
                        <!-- Options will be dynamically populated -->
                    </div>
                </div>
                
                <!-- Chat Input -->
                <div id="chatbot-input" style="display: none;">
                    <input type="text" id="user-input" placeholder="Type your message...">
                    <button id="send-message">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <style>
        /* Previous CSS styles remain exactly the same */
        #store-chatbot-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        #chatbot-toggle {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 50px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }

        #chatbot-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0,0,0,0.2);
        }

        .chatbot-icon {
            width: 20px;
            height: 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #chatbot-window {
            position: absolute;
            bottom: 70px;
            right: 0;
            width: 380px;
            height: 600px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            display: none;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #e1e5e9;
        }

        #chatbot-window.active {
            display: flex;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* StoreTransform Header - Figma Style */
        #chatbot-header {
            background: white;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f0f0f0;
        }

        .header-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .store-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .logo-text {
            display: flex;
            flex-direction: column;
        }

        .logo-text strong {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a1a;
            line-height: 1.2;
        }

        .logo-text span {
            font-size: 12px;
            color: #10b981;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .logo-text span::before {
            content: "";
            width: 6px;
            height: 6px;
            background: #10b981;
            border-radius: 50%;
            display: inline-block;
        }

        #close-chatbot {
            background: #f8f9fa;
            border: none;
            color: #6b7280;
            font-size: 16px;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            width: 32px;
            height: 32px;
        }

        #close-chatbot:hover {
            background: #e5e7eb;
            color: #374151;
        }

        #chatbot-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #fafafa;
        }

        .message {
            margin-bottom: 16px;
            display: flex;
        }

        .ai-message {
            justify-content: flex-start;
        }

        .user-message {
            justify-content: flex-end;
        }

        .message-content {
            max-width: 80%;
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
            font-size: 14px;
            line-height: 1.4;
        }

        .ai-message .message-content {
            background: white;
            border-bottom-left-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
        }

        .user-message .message-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message-content p {
            margin: 0;
            line-height: 1.4;
        }

        /* User Info Form */
        #user-info-form {
            padding: 20px;
            background: white;
            border-top: 1px solid #e5e7eb;
        }

        #user-info-form.active {
            display: block;
        }

        .form-section {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            box-sizing: border-box;
            background: #f9fafb;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .error-message {
            color: #dc2626;
            font-size: 12px;
            margin-top: 4px;
            display: block;
        }

        .btn-primary {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        /* Options */
        #chatbot-options {
            padding: 20px;
            background: white;
            border-top: 1px solid #e5e7eb;
        }

        .options-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .option-btn {
            width: 100%;
            padding: 12px 16px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            text-align: left;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            line-height: 1.4;
            color: #374151;
        }

        .option-btn:hover {
            border-color: #667eea;
            background: #f8faff;
            transform: translateX(4px);
        }

        /* Input */
        #chatbot-input {
            padding: 16px;
            background: white;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 12px;
        }

        #user-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 24px;
            font-size: 14px;
            background: #f9fafb;
        }

        #user-input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
        }

        #send-message {
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            width: 44px;
            height: 44px;
        }

        #send-message:hover {
            transform: scale(1.05);
        }

        /* Typing Indicator */
        .typing-indicator {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 12px 16px;
            background: white;
            border-radius: 18px;
            border: 1px solid #e5e7eb;
        }

        .typing-dot {
            width: 6px;
            height: 6px;
            background: #9ca3af;
            border-radius: 50%;
            animation: typing 1.4s infinite ease-in-out;
        }

        .typing-dot:nth-child(1) { animation-delay: -0.32s; }
        .typing-dot:nth-child(2) { animation-delay: -0.16s; }

        @keyframes typing {
            0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
            40% { transform: scale(1); opacity: 1; }
        }

        /* Scrollbar */
        #chatbot-messages::-webkit-scrollbar {
            width: 4px;
        }

        #chatbot-messages::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        #chatbot-messages::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 2px;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            let chatFlow = {};
            let currentStep = 'user_info';
            let chatEntryId = null; // Store the database entry ID
            
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
                const userData = {
                    name: $('#user-name').val().trim(),
                    email: $('#user-email').val().trim(),
                    phone: $('#user-phone').val().trim()
                };
                
                chatFlow.user_info = userData;
                
                // Show typing indicator
                showTypingIndicator();
                
                setTimeout(() => {
                    // Add appreciation message
                    addMessage("We appreciate you contacting us!", 'ai');
                    addMessage("To best assist you, please choose how we can help:", 'ai');
                    
                    // Show main options
                    showMainOptions();
                    
                    // Hide form and show options
                    $('#user-info-form').hide();
                    $('#chatbot-options').show();
                    
                    currentStep = 'main_options';
                    
                    // Save user info to database (create new entry)
                    saveChatData('initial');
                    
                }, 1000);
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
                        .on('click', function() {
                            handleOptionSelect(option, step);
                        });
                    container.append(btn);
                });
            }
            
            function handleOptionSelect(option, step) {
                addMessage(option, 'user');
                
                // Update chat flow with the selected option
                updateChatFlow(option, step);
                
                // Show typing indicator
                showTypingIndicator();
                
                setTimeout(() => {
                    handleFlowLogic(option, step);
                }, 1000);
            }
            
            function updateChatFlow(option, step) {
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
                
                // Update the database with the new chat flow data
                updateChatData();
            }
            
            function handleFlowLogic(option, step) {
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
                            'New Store Build (Starting from scratch)',
                            'Platform Migration (Moving from a different system)',
                            'Optimization & Growth (Improving an existing store)'
                        ], 'ecommerce_stage');
                        break;
                        
                    case 'Digital Transformation':
                        addMessage("Thanks for your interest in Digital Transformation. Which area is your primary focus for modernizing your business processes?", 'ai');
                        showOptions([
                            'Process Automation (Streamlining workflows)',
                            'Customer Experience (CX) Improvement',
                            'Data & Cloud Infrastructure'
                        ], 'digital_focus');
                        break;
                        
                    case 'Web Development':
                        addMessage("Thanks for choosing Web Development. What type of project are you looking to launch or update?", 'ai');
                        showOptions([
                            'New Website Build (Creating a site from scratch)',
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
                            'New Project Estimate (E-commerce, Website Build, etc.)',
                            'Hourly Rate / Ongoing Support Pricing',
                            'Audit / Consultation Fee'
                        ], 'pricing_service');
                        break;
                }
            }
            
            function handleEcommerceStage(option) {
                addMessage("Excellent. For your new store, which platform are you considering, or do you have a preference?", 'ai');
                showOptions([
                    'Shopify/Shopify Plus (For fast setup and scalability)',
                    'WooCommerce (For deep customization)',
                    'Custom/Headless Solution (For unique requirements)'
                ], 'ecommerce_platform');
            }
            
            function handleEcommercePlatform(option) {
                finalizeConversation("E-commerce Development", option);
            }
            
            function handleDigitalFocus(option) {
                if (option.includes('Process Automation')) {
                    addMessage("Great. Which specific area of your operations are you looking to automate?", 'ai');
                    showOptions([
                        'Sales & CRM (Automating lead nurturing)',
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
                    'Custom Development (Specific features, APIs)',
                    'CMS (WordPress, Shopify, Magento)',
                    'Landing Page/Portfolio (Simple site)'
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
                finalClosure();
            }
            
            function finalClosure() {
                setTimeout(() => {
                    addMessage("Thank you! We've noted your request. A specialist will review your details and contact you soon. Thanks for your time!", 'ai');
                    
                    // Hide options
                    $('#chatbot-options').hide();
                    
                    // Send final email
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
            
            function saveChatData(type = 'initial') {
                console.log('Saving chat data:', chatFlow);
                
                $.ajax({
                    url: chatbot_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'save_chat_data',
                        nonce: chatbot_ajax.nonce,
                        chat_data: JSON.stringify(chatFlow),
                        type: type
                    },
                    success: function(response) {
                        console.log('Chat data saved successfully:', response);
                        if (response.data && response.data.entry_id) {
                            chatEntryId = response.data.entry_id;
                            console.log('Chat entry ID:', chatEntryId);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error saving chat data:', error);
                        console.log('XHR response:', xhr.responseText);
                    }
                });
            }
            
            function updateChatData() {
                if (!chatEntryId) {
                    console.log('No chat entry ID found, saving new entry');
                    saveChatData('update');
                    return;
                }
                
                console.log('Updating chat data for entry:', chatEntryId, chatFlow);
                
                $.ajax({
                    url: chatbot_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'update_chat_data',
                        nonce: chatbot_ajax.nonce,
                        entry_id: chatEntryId,
                        chat_data: JSON.stringify(chatFlow)
                    },
                    success: function(response) {
                        console.log('Chat data updated successfully:', response);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error updating chat data:', error);
                        console.log('XHR response:', xhr.responseText);
                    }
                });
            }
            
            function sendAdminEmail() {
                $.ajax({
                    url: chatbot_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'send_admin_email',
                        nonce: chatbot_ajax.nonce,
                        chat_data: JSON.stringify(chatFlow),
                        entry_id: chatEntryId
                    },
                    success: function(response) {
                        console.log('Admin notification sent:', response);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error sending email:', error);
                    }
                });
            }
        });
        </script>
        <?php
    }
    
    public function save_chat_data() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'chatbot_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'store_ai_chatbot_v1';
        
        // Get and decode chat data
        $chat_data_json = stripslashes($_POST['chat_data']);
        $chat_data = json_decode($chat_data_json, true);
        
        // Check if user_info exists and has required fields
        if (!isset($chat_data['user_info']) || !is_array($chat_data['user_info'])) {
            wp_send_json_error('Invalid user data structure');
            return;
        }
        
        $user_info = $chat_data['user_info'];
        
        // Validate required fields
        if (empty($user_info['name']) || empty($user_info['email']) || empty($user_info['phone'])) {
            wp_send_json_error('Missing required user fields');
            return;
        }
        
        // Sanitize data
        $user_name = sanitize_text_field($user_info['name']);
        $user_email = sanitize_email($user_info['email']);
        $user_phone = sanitize_text_field($user_info['phone']);
        
        // Validate email
        if (!is_email($user_email)) {
            wp_send_json_error('Invalid email address');
            return;
        }
        
        // Prepare data for insertion
        $data = array(
            'user_name' => $user_name,
            'user_email' => $user_email,
            'user_phone' => $user_phone,
            'chat_data' => $chat_data_json
        );
        
        $format = array('%s', '%s', '%s', '%s');
        
        // Insert into database
        $result = $wpdb->insert($table_name, $data, $format);
        
        if ($result !== false) {
            $entry_id = $wpdb->insert_id;
            wp_send_json_success('Data saved successfully', array('entry_id' => $entry_id));
        } else {
            wp_send_json_error('Database error: ' . $wpdb->last_error);
        }
    }
    
    public function update_chat_data() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'chatbot_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'store_ai_chatbot_v1';
        
        $entry_id = intval($_POST['entry_id']);
        $chat_data_json = stripslashes($_POST['chat_data']);
        
        if (!$entry_id) {
            wp_send_json_error('Invalid entry ID');
            return;
        }
        
        // Update the existing entry
        $result = $wpdb->update(
            $table_name,
            array('chat_data' => $chat_data_json),
            array('id' => $entry_id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success('Chat data updated successfully');
        } else {
            wp_send_json_error('Database update error: ' . $wpdb->last_error);
        }
    }
    
    public function send_admin_email() {
        if (!wp_verify_nonce($_POST['nonce'], 'chatbot_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        $chat_data_json = stripslashes($_POST['chat_data']);
        $chat_data = json_decode($chat_data_json, true);
        
        if (!$chat_data || !isset($chat_data['user_info'])) {
            wp_send_json_error('Invalid chat data');
            return;
        }
        
        $user_info = $chat_data['user_info'];
        
        $to = get_option('admin_email');
        $subject = 'New Store Transform Chatbot Inquiry - Complete Conversation';
        
        $message = "Complete chatbot conversation received:\n\n";
        $message .= "User Details:\n";
        $message .= "Name: " . sanitize_text_field($user_info['name']) . "\n";
        $message .= "Email: " . sanitize_email($user_info['email']) . "\n";
        $message .= "Phone: " . sanitize_text_field($user_info['phone']) . "\n\n";
        
        $message .= "Conversation Flow:\n";
        
        if (isset($chat_data['main_option'])) {
            $message .= "Main Interest: " . sanitize_text_field($chat_data['main_option']) . "\n";
        }
        
        if (isset($chat_data['ecommerce_stage'])) {
            $message .= "E-commerce Stage: " . sanitize_text_field($chat_data['ecommerce_stage']) . "\n";
        }
        
        if (isset($chat_data['ecommerce_platform'])) {
            $message .= "Preferred Platform: " . sanitize_text_field($chat_data['ecommerce_platform']) . "\n";
        }
        
        if (isset($chat_data['digital_focus'])) {
            $message .= "Digital Focus: " . sanitize_text_field($chat_data['digital_focus']) . "\n";
        }
        
        if (isset($chat_data['digital_automation'])) {
            $message .= "Automation Area: " . sanitize_text_field($chat_data['digital_automation']) . "\n";
        }
        
        if (isset($chat_data['web_project_type'])) {
            $message .= "Web Project Type: " . sanitize_text_field($chat_data['web_project_type']) . "\n";
        }
        
        if (isset($chat_data['web_technology'])) {
            $message .= "Web Technology: " . sanitize_text_field($chat_data['web_technology']) . "\n";
        }
        
        if (isset($chat_data['portfolio_interest'])) {
            $message .= "Portfolio Interest: " . sanitize_text_field($chat_data['portfolio_interest']) . "\n";
        }
        
        if (isset($chat_data['pricing_service'])) {
            $message .= "Pricing Service: " . sanitize_text_field($chat_data['pricing_service']) . "\n";
        }
        
        if (isset($chat_data['pricing_scale'])) {
            $message .= "Project Scale: " . sanitize_text_field($chat_data['pricing_scale']) . "\n";
        }
        
        $message .= "\nThis message was sent from your Store Transform AI chatbot.";
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        $email_sent = wp_mail($to, $subject, $message, $headers);
        
        if ($email_sent) {
            wp_send_json_success('Email sent successfully');
        } else {
            wp_send_json_error('Failed to send email');
        }
    }
}

new StoreTransformChatbot();
?>