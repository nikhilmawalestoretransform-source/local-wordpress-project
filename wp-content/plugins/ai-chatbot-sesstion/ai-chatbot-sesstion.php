<?php
/**
 * Plugin Name:Session Update-V
 * Description: Advanced AI Chatbot with session management and chat history.
 * Version: 3.1
 * Author: Store Transform
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class StoreTransformChatbotV3 {
    
    public function __construct() {
        add_action('wp_footer', array($this, 'display_chatbot'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_save_chat_data', array($this, 'save_chat_data'));
        add_action('wp_ajax_nopriv_save_chat_data', array($this, 'save_chat_data'));
        add_action('wp_ajax_update_chat_data', array($this, 'update_chat_data'));
        add_action('wp_ajax_nopriv_update_chat_data', array($this, 'update_chat_data'));
        add_action('wp_ajax_send_admin_email', array($this, 'send_admin_email'));
        add_action('wp_ajax_nopriv_send_admin_email', array($this, 'send_admin_email'));
        add_action('wp_ajax_get_chatbot_settings', array($this, 'get_chatbot_settings'));
        add_action('wp_ajax_nopriv_get_chatbot_settings', array($this, 'get_chatbot_settings'));
        add_action('wp_ajax_get_chat_history', array($this, 'get_chat_history'));
        add_action('wp_ajax_nopriv_get_chat_history', array($this, 'get_chat_history'));
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Start session for chat history
        add_action('init', array($this, 'start_session'));
        
        register_activation_hook(__FILE__, array($this, 'create_chatbot_table'));
        register_activation_hook(__FILE__, array($this, 'set_default_settings'));
    }
    
    public function start_session() {
        if (!session_id() && !headers_sent()) {
            session_start();
        }
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_style('store-chatbot-css', plugin_dir_url(__FILE__) . 'assets/chatbot.css', array(), '3.1');
        wp_enqueue_script('store-chatbot-js', plugin_dir_url(__FILE__) . 'assets/chatbot.js', array('jquery'), '3.1', true);
        
        wp_localize_script('store-chatbot-js', 'chatbot_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('chatbot_nonce'),
            'get_settings_nonce' => wp_create_nonce('get_chatbot_settings_nonce')
        ));
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'store-chatbot') !== false) {
            wp_enqueue_style('store-chatbot-admin-css', plugin_dir_url(__FILE__) . 'assets/admin.css', array(), '3.1');
            wp_enqueue_script('store-chatbot-admin-js', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '3.1', true);
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Store Transform Chatbot',
            'Chatbot Settings',
            'manage_options',
            'store-chatbot',
            array($this, 'admin_dashboard'),
            'dashicons-format-chat',
            30
        );
        
        add_submenu_page(
            'store-chatbot',
            'Contact Details',
            'Contact Details',
            'manage_options',
            'store-chatbot-contact',
            array($this, 'contact_settings')
        );
        
        add_submenu_page(
            'store-chatbot',
            'Chat Messages',
            'Chat Messages',
            'manage_options',
            'store-chatbot-messages',
            array($this, 'message_settings')
        );
        
        add_submenu_page(
            'store-chatbot',
            'Validation Messages',
            'Validation Messages',
            'manage_options',
            'store-chatbot-validation',
            array($this, 'validation_settings')
        );
        
        add_submenu_page(
            'store-chatbot',
            'Chat Options',
            'Chat Options',
            'manage_options',
            'store-chatbot-options',
            array($this, 'options_settings')
        );
    }
    
    public function register_settings() {
        // Contact Details
        register_setting('store_chatbot_contact', 'store_chatbot_company_name');
        register_setting('store_chatbot_contact', 'store_chatbot_email');
        register_setting('store_chatbot_contact', 'store_chatbot_phone');
        register_setting('store_chatbot_contact', 'store_chatbot_status_text');
        
        // Chat Messages
        register_setting('store_chatbot_messages', 'store_chatbot_welcome_message');
        register_setting('store_chatbot_messages', 'store_chatbot_appreciation_message');
        register_setting('store_chatbot_messages', 'store_chatbot_assistance_message');
        register_setting('store_chatbot_messages', 'store_chatbot_thankyou_message');
        register_setting('store_chatbot_messages', 'store_chatbot_final_message');
        
        // Validation Messages
        register_setting('store_chatbot_validation', 'store_chatbot_name_required');
        register_setting('store_chatbot_validation', 'store_chatbot_email_required');
        register_setting('store_chatbot_validation', 'store_chatbot_email_invalid');
        register_setting('store_chatbot_validation', 'store_chatbot_phone_required');
        register_setting('store_chatbot_validation', 'store_chatbot_save_error');
        register_setting('store_chatbot_validation', 'store_chatbot_network_error');
        
        // Chat Options
        register_setting('store_chatbot_options', 'store_chatbot_main_options');
        register_setting('store_chatbot_options', 'store_chatbot_ecommerce_stages');
        register_setting('store_chatbot_options', 'store_chatbot_ecommerce_platforms');
        register_setting('store_chatbot_options', 'store_chatbot_digital_focus');
        register_setting('store_chatbot_options', 'store_chatbot_digital_automation');
        register_setting('store_chatbot_options', 'store_chatbot_web_project_types');
        register_setting('store_chatbot_options', 'store_chatbot_web_technologies');
        register_setting('store_chatbot_options', 'store_chatbot_portfolio_interests');
        register_setting('store_chatbot_options', 'store_chatbot_pricing_services');
        register_setting('store_chatbot_options', 'store_chatbot_pricing_scales');
    }
    
    public function set_default_settings() {
        // Default contact details
        if (!get_option('store_chatbot_company_name')) {
            update_option('store_chatbot_company_name', 'StoreTransform');
        }
        if (!get_option('store_chatbot_email')) {
            update_option('store_chatbot_email', 'hello@storetransform.com');
        }
        if (!get_option('store_chatbot_phone')) {
            update_option('store_chatbot_phone', '+1 (555) 123-4567');
        }
        if (!get_option('store_chatbot_status_text')) {
            update_option('store_chatbot_status_text', 'Online - Transform your business today');
        }
        
        // Default messages
        if (!get_option('store_chatbot_welcome_message')) {
            update_option('store_chatbot_welcome_message', 'Welcome to Store Transform! Please provide your Details to begin.');
        }
        if (!get_option('store_chatbot_appreciation_message')) {
            update_option('store_chatbot_appreciation_message', 'We appreciate you contacting us!');
        }
        if (!get_option('store_chatbot_assistance_message')) {
            update_option('store_chatbot_assistance_message', 'To best assist you, please choose how we can help:');
        }
        if (!get_option('store_chatbot_thankyou_message')) {
            update_option('store_chatbot_thankyou_message', 'Thank you! We\'ve noted your request. A specialist will review your details and contact you soon. Thanks for your time!');
        }
        if (!get_option('store_chatbot_final_message')) {
            update_option('store_chatbot_final_message', 'Perfect! That clarifies the scope. We have recorded your interest in {service} - {details}.');
        }
        
        // Default validation messages
        if (!get_option('store_chatbot_name_required')) {
            update_option('store_chatbot_name_required', 'Please enter your name');
        }
        if (!get_option('store_chatbot_email_required')) {
            update_option('store_chatbot_email_required', 'Please enter your email');
        }
        if (!get_option('store_chatbot_email_invalid')) {
            update_option('store_chatbot_email_invalid', 'Please enter a valid email');
        }
        if (!get_option('store_chatbot_phone_required')) {
            update_option('store_chatbot_phone_required', 'Please enter your phone number');
        }
        if (!get_option('store_chatbot_save_error')) {
            update_option('store_chatbot_save_error', 'Sorry, there was an error saving your information. Please try again.');
        }
        if (!get_option('store_chatbot_network_error')) {
            update_option('store_chatbot_network_error', 'Network error. Please check your connection and try again.');
        }
        
        // Default options
        $default_options = array(
            'main_options' => "Web Development\nE-commerce Development\nDigital Transformation\nView Portfolio\nGet Pricing",
            'ecommerce_stages' => "New Store Build.\nPlatform Migration.\nOptimization & Growth.",
            'ecommerce_platforms' => "Shopify/Shopify Plus.\nWooCommerce.\nCustom/Headless Solution.",
            'digital_focus' => "Process Automation.\nCustomer Experience (CX) Improvement\nData & Cloud Infrastructure",
            'digital_automation' => "Sales & CRM.\nInternal Operations & HR\nFinance & Accounting",
            'web_project_types' => "New Website Build\nExisting Site Redesign\nOngoing Maintenance/Support",
            'web_technologies' => "Custom Development.\nCMS (WordPress, Shopify, Magento)\nLanding Page/Portfolio.",
            'portfolio_interests' => "E-commerce Development Case Studies\nDigital Transformation Projects\nGeneral Web Design & Development",
            'pricing_services' => "New Project Estimate\nHourly Rate / Ongoing Support Pricing\nAudit / Consultation Fee",
            'pricing_scales' => "Small Scale (Landing Page, Simple Redesign)\nMedium Scale (Standard E-commerce Build)\nLarge Scale (Complex Platform Migration)"
        );
        
        foreach ($default_options as $key => $value) {
            if (!get_option('store_chatbot_' . $key)) {
                update_option('store_chatbot_' . $key, $value);
            }
        }
    }
    
    public function admin_dashboard() {
        ?>
        <div class="wrap">
            <h1>Store Transform Chatbot - Dashboard</h1>
            <div class="chatbot-dashboard">
                <div class="dashboard-cards">
                    <div class="card">
                        <h3>Contact Details</h3>
                        <p>Manage company contact information</p>
                        <a href="<?php echo admin_url('admin.php?page=store-chatbot-contact'); ?>" class="button button-primary">Configure</a>
                    </div>
                    <div class="card">
                        <h3>Chat Messages</h3>
                        <p>Customize chatbot messages</p>
                        <a href="<?php echo admin_url('admin.php?page=store-chatbot-messages'); ?>" class="button button-primary">Configure</a>
                    </div>
                    <div class="card">
                        <h3>Validation Messages</h3>
                        <p>Set validation error messages</p>
                        <a href="<?php echo admin_url('admin.php?page=store-chatbot-validation'); ?>" class="button button-primary">Configure</a>
                    </div>
                    <div class="card">
                        <h3>Chat Options</h3>
                        <p>Manage conversation flow options</p>
                        <a href="<?php echo admin_url('admin.php?page=store-chatbot-options'); ?>" class="button button-primary">Configure</a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function contact_settings() {
        ?>
        <div class="wrap">
            <h1>Contact Details</h1>
            <form method="post" action="options.php">
                <?php settings_fields('store_chatbot_contact'); ?>
                <?php do_settings_sections('store_chatbot_contact'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Company Name</th>
                        <td>
                            <input type="text" name="store_chatbot_company_name" value="<?php echo esc_attr(get_option('store_chatbot_company_name')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Email Address</th>
                        <td>
                            <input type="email" name="store_chatbot_email" value="<?php echo esc_attr(get_option('store_chatbot_email')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Phone Number</th>
                        <td>
                            <input type="text" name="store_chatbot_phone" value="<?php echo esc_attr(get_option('store_chatbot_phone')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Status Text</th>
                        <td>
                            <input type="text" name="store_chatbot_status_text" value="<?php echo esc_attr(get_option('store_chatbot_status_text')); ?>" class="regular-text" />
                            <p class="description">Text displayed next to online status</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function message_settings() {
        ?>
        <div class="wrap">
            <h1>Chat Messages</h1>
            <form method="post" action="options.php">
                <?php settings_fields('store_chatbot_messages'); ?>
                <?php do_settings_sections('store_chatbot_messages'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Welcome Message</th>
                        <td>
                            <textarea name="store_chatbot_welcome_message" class="large-text" rows="3"><?php echo esc_textarea(get_option('store_chatbot_welcome_message')); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Appreciation Message</th>
                        <td>
                            <textarea name="store_chatbot_appreciation_message" class="large-text" rows="3"><?php echo esc_textarea(get_option('store_chatbot_appreciation_message')); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Assistance Message</th>
                        <td>
                            <textarea name="store_chatbot_assistance_message" class="large-text" rows="3"><?php echo esc_textarea(get_option('store_chatbot_assistance_message')); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Thank You Message</th>
                        <td>
                            <textarea name="store_chatbot_thankyou_message" class="large-text" rows="3"><?php echo esc_textarea(get_option('store_chatbot_thankyou_message')); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Final Message</th>
                        <td>
                            <textarea name="store_chatbot_final_message" class="large-text" rows="3"><?php echo esc_textarea(get_option('store_chatbot_final_message')); ?></textarea>
                            <p class="description">Use {service} and {details} as placeholders</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function validation_settings() {
        ?>
        <div class="wrap">
            <h1>Validation Messages</h1>
            <form method="post" action="options.php">
                <?php settings_fields('store_chatbot_validation'); ?>
                <?php do_settings_sections('store_chatbot_validation'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Name Required</th>
                        <td>
                            <input type="text" name="store_chatbot_name_required" value="<?php echo esc_attr(get_option('store_chatbot_name_required')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Email Required</th>
                        <td>
                            <input type="text" name="store_chatbot_email_required" value="<?php echo esc_attr(get_option('store_chatbot_email_required')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Invalid Email</th>
                        <td>
                            <input type="text" name="store_chatbot_email_invalid" value="<?php echo esc_attr(get_option('store_chatbot_email_invalid')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Phone Required</th>
                        <td>
                            <input type="text" name="store_chatbot_phone_required" value="<?php echo esc_attr(get_option('store_chatbot_phone_required')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Save Error</th>
                        <td>
                            <input type="text" name="store_chatbot_save_error" value="<?php echo esc_attr(get_option('store_chatbot_save_error')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Network Error</th>
                        <td>
                            <input type="text" name="store_chatbot_network_error" value="<?php echo esc_attr(get_option('store_chatbot_network_error')); ?>" class="regular-text" />
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function options_settings() {
        ?>
        <div class="wrap">
            <h1>Chat Options</h1>
            <form method="post" action="options.php">
                <?php settings_fields('store_chatbot_options'); ?>
                <?php do_settings_sections('store_chatbot_options'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Main Options</th>
                        <td>
                            <textarea name="store_chatbot_main_options" class="large-text" rows="5"><?php echo esc_textarea(get_option('store_chatbot_main_options')); ?></textarea>
                            <p class="description">Enter each option on a new line</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">E-commerce Stages</th>
                        <td>
                            <textarea name="store_chatbot_ecommerce_stages" class="large-text" rows="5"><?php echo esc_textarea(get_option('store_chatbot_ecommerce_stages')); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">E-commerce Platforms</th>
                        <td>
                            <textarea name="store_chatbot_ecommerce_platforms" class="large-text" rows="5"><?php echo esc_textarea(get_option('store_chatbot_ecommerce_platforms')); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Digital Focus Areas</th>
                        <td>
                            <textarea name="store_chatbot_digital_focus" class="large-text" rows="5"><?php echo esc_textarea(get_option('store_chatbot_digital_focus')); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Digital Automation</th>
                        <td>
                            <textarea name="store_chatbot_digital_automation" class="large-text" rows="5"><?php echo esc_textarea(get_option('store_chatbot_digital_automation')); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Web Project Types</th>
                        <td>
                            <textarea name="store_chatbot_web_project_types" class="large-text" rows="5"><?php echo esc_textarea(get_option('store_chatbot_web_project_types')); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Web Technologies</th>
                        <td>
                            <textarea name="store_chatbot_web_technologies" class="large-text" rows="5"><?php echo esc_textarea(get_option('store_chatbot_web_technologies')); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Portfolio Interests</th>
                        <td>
                            <textarea name="store_chatbot_portfolio_interests" class="large-text" rows="5"><?php echo esc_textarea(get_option('store_chatbot_portfolio_interests')); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Pricing Services</th>
                        <td>
                            <textarea name="store_chatbot_pricing_services" class="large-text" rows="5"><?php echo esc_textarea(get_option('store_chatbot_pricing_services')); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Pricing Scales</th>
                        <td>
                            <textarea name="store_chatbot_pricing_scales" class="large-text" rows="5"><?php echo esc_textarea(get_option('store_chatbot_pricing_scales')); ?></textarea>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function get_chatbot_settings() {
        if (!wp_verify_nonce($_POST['nonce'], 'get_chatbot_settings_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        $settings = array(
            'contact' => array(
                'company_name' => get_option('store_chatbot_company_name', 'StoreTransform'),
                'email' => get_option('store_chatbot_email', 'hello@storetransform.com'),
                'phone' => get_option('store_chatbot_phone', '+1 (555) 123-4567'),
                'status_text' => get_option('store_chatbot_status_text', 'Online - Transform your business today')
            ),
            'messages' => array(
                'welcome' => get_option('store_chatbot_welcome_message', 'Welcome to Store Transform! Please provide your Details to begin.'),
                'appreciation' => get_option('store_chatbot_appreciation_message', 'We appreciate you contacting us!'),
                'assistance' => get_option('store_chatbot_assistance_message', 'To best assist you, please choose how we can help:'),
                'thankyou' => get_option('store_chatbot_thankyou_message', 'Thank you! We\'ve noted your request. A specialist will review your details and contact you soon. Thanks for your time!'),
                'final' => get_option('store_chatbot_final_message', 'Perfect! That clarifies the scope. We have recorded your interest in {service} - {details}.')
            ),
            'validation' => array(
                'name_required' => get_option('store_chatbot_name_required', 'Please enter your name'),
                'email_required' => get_option('store_chatbot_email_required', 'Please enter your email'),
                'email_invalid' => get_option('store_chatbot_email_invalid', 'Please enter a valid email'),
                'phone_required' => get_option('store_chatbot_phone_required', 'Please enter your phone number'),
                'save_error' => get_option('store_chatbot_save_error', 'Sorry, there was an error saving your information. Please try again.'),
                'network_error' => get_option('store_chatbot_network_error', 'Network error. Please check your connection and try again.')
            ),
            'options' => array(
                'main_options' => $this->parse_options(get_option('store_chatbot_main_options')),
                'ecommerce_stages' => $this->parse_options(get_option('store_chatbot_ecommerce_stages')),
                'ecommerce_platforms' => $this->parse_options(get_option('store_chatbot_ecommerce_platforms')),
                'digital_focus' => $this->parse_options(get_option('store_chatbot_digital_focus')),
                'digital_automation' => $this->parse_options(get_option('store_chatbot_digital_automation')),
                'web_project_types' => $this->parse_options(get_option('store_chatbot_web_project_types')),
                'web_technologies' => $this->parse_options(get_option('store_chatbot_web_technologies')),
                'portfolio_interests' => $this->parse_options(get_option('store_chatbot_portfolio_interests')),
                'pricing_services' => $this->parse_options(get_option('store_chatbot_pricing_services')),
                'pricing_scales' => $this->parse_options(get_option('store_chatbot_pricing_scales'))
            )
        );

        wp_send_json_success($settings);
    }
    
    public function get_chat_history() {
        if (!wp_verify_nonce($_POST['nonce'], 'chatbot_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        $user_email = sanitize_email($_POST['user_email']);
        
        if (empty($user_email) || !is_email($user_email)) {
            wp_send_json_error('Invalid email');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'store_ai_chatbot_session';
        
        // Get the latest chat entry for this email
        $chat_entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_email = %s ORDER BY created_at DESC LIMIT 1",
            $user_email
        ));

        if ($chat_entry) {
            $chat_data = json_decode($chat_entry->chat_data, true);
            
            // Store in session for future use
            $_SESSION['chatbot_user_email'] = $user_email;
            $_SESSION['chatbot_entry_id'] = $chat_entry->id;
            $_SESSION['chatbot_user_info'] = $chat_data['user_info'];
            
            wp_send_json_success(array(
                'chat_data' => $chat_data,
                'entry_id' => $chat_entry->id,
                'user_info' => $chat_data['user_info']
            ));
        } else {
            wp_send_json_error('No chat history found');
        }
    }
    
    private function parse_options($option_text) {
        if (empty($option_text)) {
            return array();
        }
        return array_map('trim', explode("\n", $option_text));
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
                <!-- Dynamic Header -->
                <div id="chatbot-header">
                    <div class="header-left">
                        <div class="store-logo">
                            <strong id="dynamic-company-name">StoreTransform</strong>
                            <div class="header-status">
                                <div class="status-indicator"></div>
                                <span id="dynamic-status-text">Online - Transform your business today</span>
                            </div>
                        </div>
                    </div>
                    <div class="header-right">
                        <div class="contact-info">
                            <div class="contact-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                                </svg>
                                <span id="dynamic-email">hello@storetransform.com</span>
                            </div>
                            <div class="contact-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                                </svg>
                                <span id="dynamic-phone">+1 (555) 123-4567</span>
                            </div>
                        </div>
                    </div>
                    <button id="close-chatbot">×</button>
                </div>
                
                <div id="chatbot-messages">
                    <!-- Messages will be dynamically added -->
                </div>
                
                <!-- User Info Form -->
                <div id="user-info-form" class="active">
                    <div class="form-section">
                        <div class="form-group">
                            <input type="text" id="user-name" placeholder="Your Name" required>
                            <span class="error-message" id="name-error"></span>
                        </div>
                        <div class="form-group">
                            <input type="email" id="user-email" placeholder="Your Email" required>
                            <span class="error-message" id="email-error"></span>
                        </div>
                        <div class="form-group">
                            <input type="tel" id="user-phone" placeholder="Your Phone" required>
                            <span class="error-message" id="phone-error"></span>
                        </div>
                        <button id="submit-user-info" class="btn-primary">Start Conversation</button>
                    </div>
                </div>
                
                <!-- Session Actions -->
                <div id="chatbot-session-actions" style="display: none;">
                    <div style="text-align: center; padding: 15px; border-top: 1px solid rgba(102, 126, 234, 0.1);">
                        <p style="margin: 0 0 10px 0; font-size: 12px; color: #666;">
                            Welcome back! Continuing your previous conversation.
                        </p>
                        <button id="start-new-chat" class="btn-secondary" style="background: transparent; border: 1px solid #ddd; color: #666; padding: 8px 16px; border-radius: 8px; font-size: 12px; cursor: pointer;">
                            Start New Conversation
                        </button>
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
    
    public function create_chatbot_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'store_ai_chatbot_session';
        
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
    
    public function save_chat_data() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'chatbot_nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'store_ai_chatbot_session';
        
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
            
            // Store in session
            $_SESSION['chatbot_user_email'] = $user_email;
            $_SESSION['chatbot_entry_id'] = $entry_id;
            $_SESSION['chatbot_user_info'] = $user_info;
            
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
        $table_name = $wpdb->prefix . 'store_ai_chatbot_session';
        
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

new StoreTransformChatbotV3();