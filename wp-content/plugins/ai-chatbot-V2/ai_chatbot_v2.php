<?php
/**
 * Plugin Name: Store Transform AI Chatbot V2
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
        add_action('wp_ajax_nopriv_supdate_chat_data', array($this, 'update_chat_data'));
        add_action('wp_ajax_send_admin_email', array($this, 'send_admin_email'));
        add_action('wp_ajax_nopriv_send_admin_email', array($this, 'send_admin_email'));
        
        register_activation_hook(__FILE__, array($this, 'create_chatbot_table'));
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_style('store-chatbot-css', plugin_dir_url(__FILE__) . 'assets/chatbot.css', array(), '1.0');
        wp_enqueue_script('store-chatbot-js', plugin_dir_url(__FILE__) . 'assets/chatbot.js', array('jquery'), '1.0', true);
        
        wp_localize_script('store-chatbot-js', 'chatbot_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('chatbot_nonce')
        ));
    }
    
    public function create_chatbot_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'store_ai_chatbot_v2';
        
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
                <span>Chat with us!</span>
            </div>
            
            <div id="chatbot-window">
                <!-- StoreTransform Header -->
                <div id="chatbot-header">
                    <div class="header-left">
                        <div class="store-logo">
                            <strong>StoreTransform</strong>
                            <div class="header-status">
                                <div class="status-indicator"></div>
                                <span>Online - Transform your business today</span>
                            </div>
                        </div>
                    </div>
                    <div class="header-right">
                        <div class="contact-info">
                            <div class="contact-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                                </svg>
                                <span>hello@storetransform.com</span>
                            </div>
                            <div class="contact-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                                </svg>
                                <span>+1 (555) 123-4567</span>
                            </div>
                        </div>
                    </div>
                    <button id="close-chatbot">×</button>
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
            </div>
        </div>
        <?php
    }
    
    public function save_chat_data() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'chatbot_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'store_ai_chatbot_v2';
        
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
            // Return proper JSON response with entry_id
            wp_send_json_success(array(
                'message' => 'Data saved successfully',
                'entry_id' => $entry_id
            ));
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
        $table_name = $wpdb->prefix . 'store_ai_chatbot_v2';
        
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
        $subject = 'New Store Transform Chatbot Inquiry';
        
        $message = "New chatbot inquiry received:\n\n";
        $message .= "User Details:\n";
        $message .= "Name: " . sanitize_text_field($user_info['name']) . "\n";
        $message .= "Email: " . sanitize_email($user_info['email']) . "\n";
        $message .= "Phone: " . sanitize_text_field($user_info['phone']) . "\n\n";
        
        $message .= "Conversation Details:\n";
        
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