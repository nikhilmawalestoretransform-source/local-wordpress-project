<?php
/**
 * Plugin Name: Custom CRUD Management
 * Plugin URI: https://yourwebsite.com
 * Description: Advanced CRUD management system with Bootstrap UI and confirmation dialogs
 * Version: 1.1.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: custom-crud
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CUSTOM_CRUD_VERSION', '1.1.0');
define('CUSTOM_CRUD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CUSTOM_CRUD_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Include required files
require_once CUSTOM_CRUD_PLUGIN_PATH . 'includes/class-custom-crud-database.php';
require_once CUSTOM_CRUD_PLUGIN_PATH . 'includes/class-custom-crud-ajax.php';
require_once CUSTOM_CRUD_PLUGIN_PATH . 'includes/class-custom-crud-shortcode.php';

class Custom_CRUD_Plugin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Custom login redirect
        add_filter('login_redirect', array($this, 'login_redirect'), 10, 3);
    }
    
    public function init() {
        // Initialize classes
        new Custom_CRUD_Ajax();
        new Custom_CRUD_Shortcode();
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('login_enqueue_scripts', array($this, 'login_styles'));
    }
    
    public function enqueue_scripts() {
        // Only enqueue on pages that use the shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'custom_crud')) {
            // Bootstrap CSS
            wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', array(), '5.3.0');
            
            // Bootstrap Icons
            wp_enqueue_style('bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css', array(), '1.10.0');
            
            // Font Awesome for additional icons
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');
            
            // Custom CSS
            wp_enqueue_style('custom-crud-css', CUSTOM_CRUD_PLUGIN_URL . 'assets/css/custom-crud.css', array(), CUSTOM_CRUD_VERSION);
            
            // Bootstrap JS
            wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', array('jquery'), '5.3.0', true);
            
            // Chart.js for statistics
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.3.0', true);
            
            // SweetAlert2 for confirmations
            wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), '11.0.0', true);
            
            // Custom JS
            wp_enqueue_script('custom-crud-js', CUSTOM_CRUD_PLUGIN_URL . 'assets/js/custom-crud.js', array('jquery', 'bootstrap-js', 'sweetalert2', 'chart-js'), CUSTOM_CRUD_VERSION, true);
            
            // Localize script for AJAX
            wp_localize_script('custom-crud-js', 'custom_crud_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('custom_crud_nonce'),
                'current_user' => wp_get_current_user()->display_name
            ));
        }
    }
    
    public function login_styles() {
        ?>
        <style type="text/css">
            #login {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .login-container {
                background: rgba(255, 255, 255, 0.95);
                border-radius: 20px;
                padding: 40px;
                box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.2);
                max-width: 400px;
                width: 90%;
            }
            .login h1 a {
                background-image: url('<?php echo CUSTOM_CRUD_PLUGIN_URL . 'assets/images/logo.png'; ?>') !important;
                background-size: contain;
                background-position: center;
                background-repeat: no-repeat;
                width: 80px;
                height: 80px;
                margin: 0 auto 20px;
            }
            .login form {
                background: transparent;
                border: none;
                box-shadow: none;
                padding: 0;
            }
            .login label {
                color: #2d3748;
                font-weight: 600;
                margin-bottom: 8px;
            }
            .login input[type="text"],
            .login input[type="password"] {
                background: rgba(255, 255, 255, 0.9);
                border: 2px solid #e2e8f0;
                border-radius: 12px;
                padding: 12px 16px;
                font-size: 16px;
                transition: all 0.3s ease;
            }
            .login input[type="text"]:focus,
            .login input[type="password"]:focus {
                border-color: #667eea;
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
                background: white;
            }
            .wp-core-ui .button-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border: none;
                border-radius: 12px;
                padding: 12px 24px;
                font-size: 16px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                transition: all 0.3s ease;
                width: 100%;
                margin-top: 20px;
            }
            .wp-core-ui .button-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            }
            .login .message,
            .login #login_error {
                border-radius: 12px;
                border: none;
                padding: 15px;
                margin-bottom: 20px;
            }
            .login-footer {
                text-align: center;
                margin-top: 20px;
                color: #718096;
                font-size: 14px;
            }
            .login-footer a {
                color: #667eea;
                text-decoration: none;
            }
            .login-footer a:hover {
                text-decoration: underline;
            }
        </style>
        <?php
    }
    
    public function login_redirect($redirect_to, $request, $user) {
        // Check if user is logging in from our custom login message
        if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'custom_crud') !== false) {
            return home_url('/custom-crud-page/'); // Change to your page slug
        }
        return $redirect_to;
    }
    
    public function activate() {
        $database = new Custom_CRUD_Database();
        $database->create_tables();
        
        // Set default options
        add_option('custom_crud_version', CUSTOM_CRUD_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Cleanup if needed
        flush_rewrite_rules();
    }
}

// Initialize the plugin
Custom_CRUD_Plugin::get_instance();